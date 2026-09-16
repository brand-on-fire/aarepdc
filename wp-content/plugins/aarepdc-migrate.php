<?php
/**
 * Plugin Name: AAREP DC — Legacy Member Migration (WP-CLI)
 * Description: Migrates legacy AAREP members from a JSON export (produced by system/aarepdc-export-members.sh) into Paid Memberships Pro. CURRENT (unexpired) members become ACTIVE with full access; LAPSED members are imported as EXPIRED so the WS5 gate routes them to /renew/. Idempotent, dry-run-capable, sends NO email (relies on the mail guard; also suppresses PMPro level emails during the run). CLI-only — no front-end footprint. Built 2026-06-16.
 * Version: 1.0.0
 * Author: Brand on Fire
 *
 * Usage:
 *   wp aarepdc-migrate run --file=/path/to/aarep-members-*.json --dry-run
 *   wp aarepdc-migrate run --file=/path/to/aarep-members-*.json
 *   Legacy paid-through dates are always preserved; calendar-year truncation is not allowed.
 *   [--only=members|sponsors] defaults to members; sponsor migration is a separate scope
 *   [--force]          re-process users already flagged for the selected migration scope
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	return; // CLI-only; nothing loads on the front end
}

if ( ! function_exists( 'aarepdc_private_storage_path' ) ) {
	function aarepdc_private_storage_path( $filename ) {
		$dir = trailingslashit( ABSPATH ) . '_wpeprivate/aarepdc';
		if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
			return '';
		}
		@chmod( $dir, 0700 );
		return is_writable( $dir ) ? trailingslashit( $dir ) . sanitize_file_name( $filename ) : '';
	}
}

class AAREPDC_Migrate_Command {

	/** Legacy membership_type / sponsorhip_type => PMPro level NAME (resolved to id at runtime). */
	private function level_name_for( $raw, $is_sponsor ) {
		$v = strtolower( trim( (string) $raw ) );
		if ( $is_sponsor ) {
			if ( strpos( $v, 'platinum' ) !== false ) { return 'Platinum Sponsor'; }
			if ( strpos( $v, 'gold' ) !== false )     { return 'Gold Sponsor'; }
			if ( strpos( $v, 'silver' ) !== false )   { return 'Silver Sponsor'; }
			if ( strpos( $v, 'bronze' ) !== false )   { return 'Bronze Sponsor'; }
			return '';
		}
		if ( strpos( $v, 'general' ) !== false )                               { return 'General Membership'; }
		if ( strpos( $v, 'young' ) !== false || strpos( $v, 'student' ) !== false ) { return 'Young Professional / Student'; }
		if ( strpos( $v, 'government' ) !== false || strpos( $v, 'non-profit' ) !== false || strpos( $v, 'nonprofit' ) !== false ) { return 'Government / Non-Profit'; }
		return '';
	}

	/** member JSON field => PMPro user_meta key (non-payment only). */
	private function meta_map() {
		return array(
			'phone_number'                      => 'aarepdc_phone_number',
			'phone_type'                        => 'aarepdc_phone_type',
			'address1'                          => 'aarepdc_address1',
			'address2'                          => 'aarepdc_address2',
			'city'                              => 'aarepdc_city',
			'state'                             => 'aarepdc_state',
			'postal_code'                       => 'aarepdc_postal_code',
			'country'                           => 'aarepdc_country',
			'company_name'                      => 'aarepdc_company_name',
			'comme_real_estate_sector'          => 'aarepdc_real_estate_sector',
			'real_estate_industry_professional' => 'aarepdc_professional_level',
			'experience_real_estate'            => 'aarepdc_experience_real_estate',
			'college_university'                 => 'aarepdc_college_university',
			'date_education'                    => 'aarepdc_date_education',
			'degree_expected'                   => 'aarepdc_degree_expected',
			'credits_completed'                 => 'aarepdc_credits_completed',
			'relevant_experience'               => 'aarepdc_relevant_experience',
			// billing_* are stashed separately (legacy proof-of-no-loss); committee_preference handled specially.
		);
	}

	private function is_valid_date( $value ) {
		$value = trim( (string) $value );
		if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts ) ) {
			return false;
		}
		return checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] );
	}

	private function is_current( $end_date ) {
		$end = trim( (string) $end_date );
		if ( ! $this->is_valid_date( $end ) ) {
			return false; // unknown/invalid end date -> treat as lapsed (safer: they get /renew)
		}
		$ts = strtotime( $end . ' 23:59:59' );
		if ( ! $ts ) {
			return false;
		}
		$grace = function_exists( 'aarepdc_grace_days' ) ? aarepdc_grace_days() : 30;
		return ( $ts + ( $grace * DAY_IN_SECONDS ) ) >= current_time( 'timestamp' );
	}

	/** Write member metadata and verify the exact stored value before allowing a commit. */
	private function set_user_meta_verified( $user_id, $key, $value ) {
		$expected = is_array( $value ) || is_object( $value ) ? $value : (string) $value;
		// WordPress unslashes metadata internally, so slash first to preserve literal export data.
		update_user_meta( (int) $user_id, $key, wp_slash( $expected ) );
		return get_user_meta( (int) $user_id, $key, true ) === $expected;
	}

	public function run( $args, $assoc ) {
		global $wpdb;
		$file = isset( $assoc['file'] ) ? $assoc['file'] : '';
		if ( ! $file || ! is_readable( $file ) ) {
			WP_CLI::error( 'Provide a readable --file=<json export> (from system/aarepdc-export-members.sh).' );
		}
		$dry           = ! empty( $assoc['dry-run'] );
		$force         = ! empty( $assoc['force'] );
		$calendar_year = ! empty( $assoc['calendar-year'] );
		$only          = isset( $assoc['only'] ) ? sanitize_key( $assoc['only'] ) : 'members';
		if ( $calendar_year ) {
			WP_CLI::error( '--calendar-year is disabled: migration must preserve each legacy paid-through date.' );
		}
		if ( ! in_array( $only, array( 'members', 'sponsors' ), true ) ) {
			WP_CLI::error( '--only must be either members or sponsors. The default is members.' );
		}
		if ( ! function_exists( 'aarepdc_user_has_active_subscription' ) || ! function_exists( 'aarepdc_find_level_id_by_name' ) || ! function_exists( 'aarepdc_level_group_topology_state' ) || ! function_exists( 'pmpro_changeMembershipLevel' ) || ! function_exists( 'pmpro_cancelMembershipLevel' ) ) {
			WP_CLI::error( 'Required migration safety dependencies are unavailable; migration stopped before reading or changing member data.' );
		}
		$group_state = aarepdc_level_group_topology_state();
		if ( is_wp_error( $group_state ) || empty( $group_state['ready'] ) ) {
			WP_CLI::error( 'The paid/Board level-group topology is not ready; reconcile it before migration so a paid import cannot remove Board access.' );
		}
		$primary_level_ids = array_map( 'intval', $group_state['primary_members'] );
		$primary_id_sql    = implode( ',', $primary_level_ids );
		$board_level_id    = (int) $group_state['board_members'][0];
		$memberships_table = $wpdb->prefix . 'pmpro_memberships_users';
		$scope_marker = 'aarepdc_migrated_from_legacy_' . ( 'sponsors' === $only ? 'sponsor' : 'member' );
		$logfile       = aarepdc_private_storage_path( 'aarepdc-migrate-' . ( $dry ? 'DRYRUN-' : '' ) . gmdate( 'Ymd-His' ) . '.log' );
		if ( '' === $logfile ) {
			WP_CLI::error( 'Private migration-log storage is unavailable; no records were changed.' );
		}

		$data = json_decode( file_get_contents( $file ), true );
		if ( ! is_array( $data ) || empty( $data[ $only ] ) || ! is_array( $data[ $only ] ) ) {
			WP_CLI::error( sprintf( 'JSON has no selected %s[] records.', $only ) );
		}

		// This CLI import must have no email, provider, CRM, or registration-integration side effects.
		// Remove runtime integration callbacks for this process only; the migration writes and verifies
		// every required user/meta/membership field itself.
		add_filter( 'pmpro_send_admin_change_email', '__return_false' );
		add_filter( 'pmpro_email_filter', '__return_false', PHP_INT_MAX );
		add_filter( 'pre_wp_mail', '__return_false', PHP_INT_MAX );
		foreach ( array( 'user_register', 'added_user_meta', 'updated_user_meta', 'pmpro_before_change_membership_level', 'pmpro_after_change_membership_level', 'pmpro_after_all_membership_level_changes' ) as $integration_hook ) {
			remove_all_actions( $integration_hook );
		}
		foreach ( array( 'pmpro_change_level', 'pmpro_deactivate_old_levels', 'pmpro_remove_duplicate_membership_entries', 'pmpro_cancel_previous_subscriptions' ) as $integration_filter ) {
			remove_all_filters( $integration_filter );
		}
		// Restore only the deterministic provider-cancellation safeguard after clearing integrations.
		add_filter( 'pmpro_cancel_previous_subscriptions', '__return_false', PHP_INT_MAX );

		$log = array( 'SCOPE ' . $only );
		$tally = array( 'created' => 0, 'matched' => 0, 'active' => 0, 'expired' => 0, 'skipped' => 0, 'missing_emails' => 0, 'invalid_emails' => 0, 'missing_dates' => 0, 'shared_email_conflicts' => 0, 'duplicate_tier_conflicts' => 0, 'identity_mismatches' => 0, 'primary_level_conflicts' => 0, 'protected_subscriptions' => 0, 'unmapped' => 0, 'dup' => 0, 'errors' => 0, 'rolled_back' => 0 );

		$sets = array(
			array(
				'rows'     => $data[ $only ],
				'sponsor'  => 'sponsors' === $only,
				'type_key' => 'sponsors' === $only ? 'sponsorhip_type' : 'membership_type',
			),
		);

		// Compare identities across both exported sets even though only one scope is selected,
		// so no account is claimed with an email that belongs to a different person.
		$email_identities = array();
		foreach ( array( 'members', 'sponsors' ) as $identity_set ) {
			foreach ( ! empty( $data[ $identity_set ] ) && is_array( $data[ $identity_set ] ) ? $data[ $identity_set ] : array() as $r ) {
				$email = strtolower( trim( (string) ( $r['email_address'] ?? '' ) ) );
				if ( '' === $email || ! is_email( $email ) ) {
					continue;
				}
				$name = strtolower( trim( preg_replace( '/\s+/', ' ', ( $r['first_name'] ?? '' ) . ' ' . ( $r['last_name'] ?? '' ) ) ) );
				$email_identities[ $email ][ $name ] = true;
			}
		}
		$conflict_emails = array_filter( $email_identities, static function( $names ) {
			return count( $names ) > 1;
		} );
		$counted_conflicts = array();
		$planned_rows      = array();

		foreach ( $sets as $set ) {
			// Resolve rows before any member write. Different people sharing one email wait for
			// unique addresses. Same-person renewals use the greatest paid-through date.
			$rows_by_email = array();
			foreach ( $set['rows'] as $r ) {
				$email = strtolower( trim( (string) ( $r['email_address'] ?? '' ) ) );
				if ( $email === '' ) {
					$log[] = 'SKIP blank email [UNIQUE EMAIL REQUIRED]';
					$tally['skipped']++;
					$tally['missing_emails']++;
					continue;
				}
				if ( ! is_email( $email ) ) {
					$log[] = 'SKIP invalid email [VALID UNIQUE EMAIL REQUIRED]';
					$tally['skipped']++;
					$tally['invalid_emails']++;
					continue;
				}
				$name = strtolower( trim( preg_replace( '/\s+/', ' ', ( $r['first_name'] ?? '' ) . ' ' . ( $r['last_name'] ?? '' ) ) ) );
				if ( '' === $name ) {
					$tally['skipped']++;
					$tally['identity_mismatches']++;
					$log[] = "SKIP missing member name for {$email} [MANUAL REVIEW REQUIRED]";
					continue;
				}
				if ( ! $this->is_valid_date( $r['end_date'] ?? '' ) ) {
					$tally['skipped']++;
					$tally['missing_dates']++;
					$log[] = "SKIP missing/invalid paid-through date for {$email} [MANUAL REVIEW REQUIRED]";
					continue;
				}
				if ( isset( $conflict_emails[ $email ] ) ) {
					$tally['skipped']++;
					if ( ! isset( $counted_conflicts[ $email ] ) ) {
						$tally['shared_email_conflicts']++;
						$counted_conflicts[ $email ] = true;
					}
					$log[] = "SKIP shared email {$email}: {$r['first_name']} {$r['last_name']} [UNIQUE EMAIL REQUIRED]";
					continue;
				}
				$r['_import_email'] = $email;
				$r['_import_name']  = $name;
				$rows_by_email[ $email ][] = $r;
			}

			$import_rows = array();
			foreach ( $rows_by_email as $email => $email_rows ) {
				$level_names = array();
				foreach ( $email_rows as $candidate ) {
					$level_names[] = $this->level_name_for( $candidate[ $set['type_key'] ] ?? '', $set['sponsor'] );
				}
				$level_names = array_values( array_unique( $level_names ) );
				if ( count( $level_names ) > 1 ) {
					$tally['duplicate_tier_conflicts']++;
					$tally['skipped'] += count( $email_rows );
					$log[] = "SKIP duplicate rows with conflicting tiers for {$email} [MANUAL REVIEW REQUIRED]";
					continue;
				}
				usort( $email_rows, function( $a, $b ) {
					$a_end = $this->is_valid_date( $a['end_date'] ?? '' ) ? $a['end_date'] : '';
					$b_end = $this->is_valid_date( $b['end_date'] ?? '' ) ? $b['end_date'] : '';
					if ( $a_end !== $b_end ) {
						return strcmp( $b_end, $a_end );
					}
					$a_added = isset( $a['date_added'] ) ? (string) $a['date_added'] : '';
					$b_added = isset( $b['date_added'] ) ? (string) $b['date_added'] : '';
					return strcmp( $b_added, $a_added );
				} );
				$import_rows[] = $email_rows[0];
				if ( count( $email_rows ) > 1 ) {
					$tally['dup'] += count( $email_rows ) - 1;
					$log[] = sprintf( 'DUP (same person) %s: selected latest paid-through %s; skipped %d older row(s)', $email, $email_rows[0]['end_date'], count( $email_rows ) - 1 );
				}
			}

			foreach ( $import_rows as $r ) {
				$email = $r['_import_email'];
				$level_name = $this->level_name_for( $r[ $set['type_key'] ] ?? '', $set['sponsor'] );
				if ( ! $level_name ) {
					$tally['unmapped']++;
					$log[] = "UNMAPPED tier '" . ( $r[ $set['type_key'] ] ?? '' ) . "' for {$email}";
					continue;
				}
				$level_id = aarepdc_find_level_id_by_name( $level_name );
				if ( ! $level_id ) { $tally['unmapped']++; $log[] = "NO PMPRO LEVEL '{$level_name}' for {$email}"; continue; }

				$legacy_end = trim( (string) ( $r['end_date'] ?? '' ) );
				if ( ! $this->is_valid_date( $legacy_end ) ) {
					$tally['skipped']++;
					$tally['missing_dates']++;
					$log[] = "SKIP missing/invalid paid-through date for {$email} [MANUAL REVIEW REQUIRED]";
					continue;
				}
				$current = $this->is_current( $legacy_end );

				$user = get_user_by( 'email', $email );
				if ( $user && aarepdc_user_has_active_subscription( $user->ID ) ) {
					$tally['skipped']++;
					$tally['protected_subscriptions']++;
					$log[] = "SKIP active provider subscription for {$email} [MANUAL REVIEW REQUIRED]";
					continue;
				}
				if ( $user ) {
					$normalize_name = static function( $value ) {
						return strtolower( trim( preg_replace( '/\s+/', ' ', (string) $value ) ) );
					};
					$source_name  = $normalize_name( ( $r['first_name'] ?? '' ) . ' ' . ( $r['last_name'] ?? '' ) );
					$profile_name = $normalize_name( get_user_meta( $user->ID, 'first_name', true ) . ' ' . get_user_meta( $user->ID, 'last_name', true ) );
					$display_name = $normalize_name( $user->display_name );
					if ( '' === $source_name || ( $source_name !== $profile_name && $source_name !== $display_name ) ) {
						$tally['skipped']++;
						$tally['identity_mismatches']++;
						$log[] = "SKIP existing user identity mismatch for {$email} [MANUAL REVIEW REQUIRED]";
						continue;
					}
					$active_primary_ids = array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT membership_id FROM {$memberships_table} WHERE user_id = %d AND status = 'active' AND membership_id IN ({$primary_id_sql}) ORDER BY id", $user->ID ) ) );
					$other_primary_ids  = array_diff( $active_primary_ids, array( (int) $level_id ) );
					if ( $other_primary_ids ) {
						$tally['skipped']++;
						$tally['primary_level_conflicts']++;
						$log[] = "SKIP existing different primary level for {$email} [MANUAL REVIEW REQUIRED]";
						continue;
					}
					if ( get_user_meta( $user->ID, $scope_marker, true ) && ! $force ) {
						$tally['skipped']++;
						$log[] = "SKIP already-migrated {$email}";
						continue;
					}
				}

				$action = $user ? 'MATCH' : 'CREATE';
				$state  = $current ? 'ACTIVE' : 'EXPIRED';
				$log[]  = "{$action} {$email} -> {$level_name} [{$state}] (legacy end {$r['end_date']})";
				$r['_level_name'] = $level_name;
				$r['_level_id']   = (int) $level_id;
				$r['_legacy_end'] = $legacy_end;
				$r['_current']    = $current;
				$r['_user']       = $user;
				$r['_sponsor']    = $set['sponsor'];
				$r['_had_board']  = $user ? (bool) $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM {$memberships_table} WHERE user_id = %d AND membership_id = %d AND status = 'active' LIMIT 1", $user->ID, $board_level_id ) ) : false;
				$planned_rows[]   = $r;
			}
		}

		$review_count = $tally['errors'] + $tally['missing_emails'] + $tally['invalid_emails'] + $tally['missing_dates'] + $tally['shared_email_conflicts'] + $tally['duplicate_tier_conflicts'] + $tally['identity_mismatches'] + $tally['primary_level_conflicts'] + $tally['protected_subscriptions'] + $tally['unmapped'];
		if ( false === file_put_contents( $logfile, implode( "\n", $log ) . "\n", LOCK_EX ) ) {
			WP_CLI::error( 'The private migration preflight log could not be written; no member records were changed.' );
		}
		@chmod( $logfile, 0600 );
		$logged_lines = count( $log );
		WP_CLI::log( ( $dry ? '[DRY-RUN] ' : '' ) . 'Preflight: ' . wp_json_encode( $tally ) );
		WP_CLI::log( 'Scope: ' . $only );
		WP_CLI::log( 'Log: ' . $logfile );
		if ( $review_count > 0 ) {
			WP_CLI::error( sprintf( 'Preflight found %d issue(s) requiring review; zero member records were changed.', $review_count ) );
		}

		if ( $dry ) {
			foreach ( $planned_rows as $r ) {
				$r['_user'] ? $tally['matched']++ : $tally['created']++;
				$r['_current'] ? $tally['active']++ : $tally['expired']++;
			}
		} else {
			$transaction_tables = array( $wpdb->users, $wpdb->usermeta, $wpdb->prefix . 'pmpro_memberships_users' );
			$table_placeholders  = implode( ', ', array_fill( 0, count( $transaction_tables ), '%s' ) );
			$engines = $wpdb->get_results( $wpdb->prepare( "SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ({$table_placeholders})", ...$transaction_tables ) );
			if ( count( $transaction_tables ) !== count( $engines ) || array_filter( $engines, static function( $row ) { return 'InnoDB' !== $row->ENGINE; } ) ) {
				WP_CLI::error( 'Migration tables are not all transactional InnoDB tables; no member records were changed.' );
			}
			if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
				WP_CLI::error( 'Could not start the migration transaction; no member records were changed.' );
			}
			foreach ( $planned_rows as $r ) {
				$email       = $r['_import_email'];
				$level_name  = $r['_level_name'];
				$level_id    = (int) $r['_level_id'];
				$legacy_end  = $r['_legacy_end'];
				$current     = (bool) $r['_current'];
				$user        = $r['_user'];
				if ( ! $user ) {
					$uid = wp_insert_user( array(
						'user_login'   => $email,
						'user_email'   => $email,
						'user_pass'    => wp_generate_password( 18, true ),
						'first_name'   => $r['first_name'] ?? '',
						'last_name'    => $r['last_name'] ?? '',
						'display_name' => trim( ( $r['first_name'] ?? '' ) . ' ' . ( $r['last_name'] ?? '' ) ),
						'role'         => 'subscriber',
					) );
					if ( is_wp_error( $uid ) ) { $tally['errors']++; $log[] = "ERR create {$email}: " . $uid->get_error_message(); continue; }
					$tally['created']++;
				} else {
					$uid = $user->ID;
					$tally['matched']++;
				}

				// meta
				foreach ( $this->meta_map() as $src => $key ) {
					if ( isset( $r[ $src ] ) && $r[ $src ] !== '' && ! $this->set_user_meta_verified( $uid, $key, $r[ $src ] ) ) {
						$tally['errors']++;
						$log[] = "ERR storing {$key} for {$email}";
					}
				}
				if ( ! empty( $r['committee_preference'] ) ) {
					$committees = array_values( array_filter( array_map( 'trim', explode( ',', $r['committee_preference'] ) ) ) );
					if ( ! $this->set_user_meta_verified( $uid, 'aarepdc_committee_preference', $committees ) ) {
						$tally['errors']++;
						$log[] = "ERR storing committee preferences for {$email}";
					}
				}
				foreach ( array( 'billing_address', 'billing_city', 'billing_state', 'billing_postal_code' ) as $b ) {
					if ( ! empty( $r[ $b ] ) && ! $this->set_user_meta_verified( $uid, 'aarepdc_legacy_' . $b, $r[ $b ] ) ) {
						$tally['errors']++;
						$log[] = "ERR storing legacy {$b} for {$email}";
					}
				}
				if ( $tally['errors'] ) {
					continue;
				}
				// dates
				$startdate = $this->is_valid_date( $r['start_date'] ?? '' ) ? $r['start_date'] . ' 00:00:00' : current_time( 'mysql' );
				$term_end  = '';
				$grace     = function_exists( 'aarepdc_grace_days' ) ? aarepdc_grace_days() : 30;
				if ( $current ) {
					$term_end = $legacy_end; // preserve exactly what they paid for
					$enddate = function_exists( 'aarepdc_enddate_from_term_end' )
						? aarepdc_enddate_from_term_end( $term_end )
						: gmdate( 'Y-m-d 23:59:59', strtotime( $term_end . ' 23:59:59' ) + ( $grace * DAY_IN_SECONDS ) );
				} else {
					if ( $this->is_valid_date( $r['end_date'] ?? '' ) ) {
						$term_end = $r['end_date'];
						$enddate = function_exists( 'aarepdc_enddate_from_term_end' )
							? aarepdc_enddate_from_term_end( $term_end )
							: gmdate( 'Y-m-d 23:59:59', strtotime( $term_end . ' 23:59:59' ) + ( $grace * DAY_IN_SECONDS ) );
					} else {
						$enddate = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - DAY_IN_SECONDS );
					}
				}

				$custom = array(
					'user_id'         => $uid,
					'membership_id'   => $level_id,
					'code_id'         => 0,
					'initial_payment' => 0,
					'billing_amount'  => 0,
					'cycle_number'    => 0,
					'cycle_period'    => '',
					'billing_limit'   => 0,
					'trial_amount'    => 0,
					'trial_limit'     => 0,
					'startdate'       => $startdate,
					'enddate'         => $enddate,
				);
				$assigned = pmpro_changeMembershipLevel( $custom, $uid );
				if ( ! $assigned ) {
					$tally['errors']++;
					$log[] = "ERR assigning {$level_name} to {$email}; migration flag not set";
					continue;
				}

				if ( ! $current ) {
					// flip to expired so the WS5 gate routes them to /renew/
					$expired = pmpro_cancelMembershipLevel( $level_id, $uid, 'expired' );
					if ( ! $expired ) {
						$tally['errors']++;
						$log[] = "ERR expiring {$email}; migration flag not set";
						continue;
					}
					$tally['expired']++;
				} else {
					$tally['active']++;
				}

				// PMPro can return true even when an internal same-group deactivation failed.
				// Verify the committed shape directly before writing migration markers.
				$active_primary_rows = array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT membership_id FROM {$memberships_table} WHERE user_id = %d AND status = 'active' AND membership_id IN ({$primary_id_sql}) ORDER BY id", $uid ) ) );
				$latest_target = $wpdb->get_row( $wpdb->prepare( "SELECT status, enddate FROM {$memberships_table} WHERE user_id = %d AND membership_id = %d ORDER BY id DESC LIMIT 1", $uid, $level_id ) );
				$membership_ok = $latest_target
					&& ( $current
						? ( array( $level_id ) === $active_primary_rows && 'active' === $latest_target->status && $enddate === $latest_target->enddate )
						: ( empty( $active_primary_rows ) && 'expired' === $latest_target->status ) );
				if ( ! empty( $r['_had_board'] ) ) {
					$membership_ok = $membership_ok && (bool) $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM {$memberships_table} WHERE user_id = %d AND membership_id = %d AND status = 'active' LIMIT 1", $uid, $board_level_id ) );
				}
				if ( ! $membership_ok ) {
					$tally['errors']++;
					$log[] = "ERR membership readback for {$email}; migration flags not set";
					continue;
				}
				if ( '' !== $term_end && ! $this->set_user_meta_verified( $uid, 'aarepdc_term_end', $term_end ) ) {
					$tally['errors']++;
					$log[] = "ERR storing paid-through date for {$email}; migration flags not set";
					continue;
				}

				$migrated_at = gmdate( 'c' );
				$source      = $r['_sponsor'] ? 'sponsor' : 'member';
				$markers_ok  = $this->set_user_meta_verified( $uid, $scope_marker, $migrated_at )
					&& $this->set_user_meta_verified( $uid, 'aarepdc_migrated_from_legacy', $migrated_at )
					&& $this->set_user_meta_verified( $uid, 'aarepdc_legacy_source', $source );
				if ( ! $markers_ok ) {
					$tally['errors']++;
					$log[] = "ERR storing migration audit markers for {$email}";
				}
			}
			if ( $tally['errors'] ) {
				$wpdb->query( 'ROLLBACK' );
				wp_cache_flush();
				$tally['rolled_back'] = 1;
				$log[] = 'ROLLBACK: a runtime write failed; no planned migration rows were committed.';
			} elseif ( false === $wpdb->query( 'COMMIT' ) ) {
				$wpdb->query( 'ROLLBACK' );
				wp_cache_flush();
				$tally['errors']++;
				$tally['rolled_back'] = 1;
				$log[] = 'ROLLBACK: database commit failed.';
			}
		}

		$log_tail   = array_slice( $log, $logged_lines );
		$log_tail[] = 'SUMMARY ' . wp_json_encode( $tally );
		if ( false === file_put_contents( $logfile, implode( "\n", $log_tail ) . "\n", FILE_APPEND | LOCK_EX ) ) {
			WP_CLI::error( 'The final private audit log append failed. Stop and inspect the run before continuing.' );
		}

		WP_CLI::log( ( $dry ? '[DRY-RUN] ' : '' ) . 'Summary: ' . wp_json_encode( $tally ) );
		if ( $tally['errors'] > 0 ) {
			WP_CLI::error( sprintf( 'Migration rolled back after %d runtime error(s); see the private log.', $tally['errors'] ) );
		}
		WP_CLI::success( $dry ? 'Dry-run complete (no member data changes, no email; private audit log written).' : 'Migration complete with no unresolved records.' );
	}
}

WP_CLI::add_command( 'aarepdc-migrate run', array( 'AAREPDC_Migrate_Command', 'run' ) );
