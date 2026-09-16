<?php
/**
 * Plugin Name: AAREP DC — Mail Guard (staging safety)
 * Description: MASTER OUTBOUND EMAIL CONTROL for the AAREP DC staging site. Blocks ALL outbound email by default; only addresses on an explicit allowlist may receive mail. Every send attempt is logged. Members receive NOTHING until an administrator changes the allowlist. Built 2026-06-16 per CEO directive ("nothing to any members until I give the green light; master control to the byte").
 * Version: 1.0.0
 * Author: Brand on Fire
 *
 * Control surface:
 *   - Option `aarepdc_mailguard_enabled`    : '1' (DEFAULT) guard on/blocking; '0' guard off (normal mail).
 *   - Option `aarepdc_mailguard_allowlist`  : comma-separated allowlist (DEFAULT EMPTY = block everyone).
 *   - Constant AAREPDC_MAILGUARD_HARD_BLOCK : if defined truthy in wp-config, blocks EVERYTHING incl. the
 *                                             allowlist (ultimate kill-switch — survives DB option changes).
 *   - Log: _wpeprivate/aarepdc/aarepdc-mailguard.log (every attempt, ALLOWED or BLOCKED, with counts only; no PII).
 *   - WP-CLI: wp aarepdc-mail status | allow "<csv>" | add <email> | block_all | enable | disable | test <email> | log
 *
 * NOTE: while enabled with an empty allowlist, even admin password-reset emails are blocked (intentional).
 * Add specific admin addresses to the allowlist to receive test email.
 *
 * Scope limit: this intercepts wp_mail() (PMPro, Contact Form 7, WP core, WP Job Manager, etc.). It does NOT
 * intercept senders that bypass wp_mail() via their own API/SMTP (e.g. Mailchimp). Those integrations must be
 * disconnected on staging separately. See the admin notice.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function aarepdc_mailguard_log_path() {
	if ( function_exists( 'aarepdc_private_storage_path' ) ) {
		return aarepdc_private_storage_path( 'aarepdc-mailguard.log' );
	}

	$dir = trailingslashit( ABSPATH ) . '_wpeprivate/aarepdc';
	if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
		return '';
	}
	@chmod( $dir, 0700 );
	return is_writable( $dir ) ? trailingslashit( $dir ) . 'aarepdc-mailguard.log' : '';
}

function aarepdc_mailguard_enabled() {
	if ( defined( 'AAREPDC_MAILGUARD_HARD_BLOCK' ) && AAREPDC_MAILGUARD_HARD_BLOCK ) {
		return true;
	}
	return '1' === get_option( 'aarepdc_mailguard_enabled', '1' );
}

function aarepdc_mailguard_allowlist() {
	if ( defined( 'AAREPDC_MAILGUARD_HARD_BLOCK' ) && AAREPDC_MAILGUARD_HARD_BLOCK ) {
		return array();
	}
	$raw = (string) get_option( 'aarepdc_mailguard_allowlist', '' );
	$out = array();
	foreach ( explode( ',', $raw ) as $e ) {
		$e = strtolower( trim( $e ) );
		if ( '' !== $e && is_email( $e ) ) {
			$out[] = $e;
		}
	}
	return array_values( array_unique( $out ) );
}

function aarepdc_mailguard_extract_email( $addr ) {
	$addr = trim( (string) $addr );
	if ( preg_match( '/<([^>]+)>/', $addr, $m ) ) {
		$addr = trim( $m[1] );
	}
	return strtolower( $addr );
}

/**
 * Extract every address from a wp_mail() recipient value. If a non-empty
 * value cannot be parsed, preserve it so the allowlist check fails closed.
 */
function aarepdc_mailguard_parse_recipients( $value ) {
	$out = array();

	foreach ( (array) $value as $entry ) {
		if ( ! is_scalar( $entry ) ) {
			$out[] = '(unparseable recipient)';
			continue;
		}

		$entry = trim( (string) $entry );
		if ( '' === $entry ) {
			continue;
		}

		$matches = array();
		preg_match_all( '/[a-z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[a-z0-9.-]+\.[a-z]{2,}/i', $entry, $matches );

		if ( empty( $matches[0] ) ) {
			$out[] = aarepdc_mailguard_extract_email( $entry );
			continue;
		}

		foreach ( $matches[0] as $email ) {
			$out[] = strtolower( $email );
		}
	}

	return $out;
}

/**
 * Return Cc/Bcc recipients from the string, numeric-array, or associative
 * header formats accepted by wp_mail(). Folded continuation lines are also
 * included so hidden recipients cannot bypass the allowlist.
 */
function aarepdc_mailguard_header_recipients( $headers ) {
	$out = array(
		'cc'  => array(),
		'bcc' => array(),
	);

	if ( empty( $headers ) ) {
		return $out;
	}

	$lines = is_array( $headers ) ? $headers : preg_split( '/\r\n|\r|\n/', (string) $headers );
	$current_recipient_header = '';

	foreach ( (array) $lines as $key => $line ) {
		if ( is_string( $key ) && in_array( strtolower( trim( $key ) ), array( 'cc', 'bcc' ), true ) ) {
			$header_name = strtolower( trim( $key ) );
			$out[ $header_name ] = array_merge( $out[ $header_name ], aarepdc_mailguard_parse_recipients( $line ) );
			$current_recipient_header = $header_name;
			continue;
		}

		if ( ! is_scalar( $line ) ) {
			if ( '' !== $current_recipient_header ) {
				$out[ $current_recipient_header ][] = '(unparseable recipient)';
			}
			continue;
		}

		$line = (string) $line;
		if ( preg_match( '/^\s*(cc|bcc)\s*:\s*(.*)$/i', $line, $matches ) ) {
			$current_recipient_header = strtolower( $matches[1] );
			$out[ $current_recipient_header ] = array_merge(
				$out[ $current_recipient_header ],
				aarepdc_mailguard_parse_recipients( $matches[2] )
			);
			continue;
		}

		if ( '' !== $current_recipient_header && preg_match( '/^\s+(.+)$/', $line, $matches ) ) {
			$out[ $current_recipient_header ] = array_merge(
				$out[ $current_recipient_header ],
				aarepdc_mailguard_parse_recipients( $matches[1] )
			);
			continue;
		}

		$current_recipient_header = '';
	}

	return $out;
}

function aarepdc_mailguard_log( $line ) {
	$file  = aarepdc_mailguard_log_path();
	if ( '' === $file ) {
		return;
	}
	$stamp = gmdate( 'Y-m-d H:i:s' ) . ' UTC';
	$written = file_put_contents( $file, "[{$stamp}] {$line}\n", FILE_APPEND | LOCK_EX );
	if ( false !== $written ) {
		@chmod( $file, 0600 );
	}
}

/**
 * The interceptor. pre_wp_mail returns: non-null => short-circuit (BLOCK, treated as the wp_mail result),
 * null => proceed to normal sending (ALLOW).
 */
add_filter( 'pre_wp_mail', 'aarepdc_mailguard_intercept', 1, 2 );
function aarepdc_mailguard_intercept( $short_circuit, $atts ) {
	if ( ! aarepdc_mailguard_enabled() ) {
		return $short_circuit; // guard off — normal behavior
	}

	$allow   = aarepdc_mailguard_allowlist();
	$headers = aarepdc_mailguard_header_recipients( isset( $atts['headers'] ) ? $atts['headers'] : array() );

	$recipients_by_type = array(
		'to'  => aarepdc_mailguard_parse_recipients( isset( $atts['to'] ) ? $atts['to'] : array() ),
		'cc'  => $headers['cc'],
		'bcc' => $headers['bcc'],
	);
	$recip_emails = array_values( array_unique( array_merge(
		$recipients_by_type['to'],
		$recipients_by_type['cc'],
		$recipients_by_type['bcc']
	) ) );
	if ( empty( $recip_emails ) ) {
		aarepdc_mailguard_log( 'BLOCKED allowed_count=0 blocked_count=0 reason=no_parseable_recipient' );
		return true;
	}

	$blocked = array();
	foreach ( $recip_emails as $e ) {
		if ( ! in_array( $e, $allow, true ) ) {
			$blocked[] = $e;
		}
	}

	if ( empty( $blocked ) ) {
		aarepdc_mailguard_log( 'ALLOWED allowed_count=' . count( $recip_emails ) . ' blocked_count=0' );
		return $short_circuit; // proceed — every recipient is on the allowlist
	}

	aarepdc_mailguard_log( 'BLOCKED allowed_count=' . ( count( $recip_emails ) - count( $blocked ) ) . ' blocked_count=' . count( $blocked ) );
	return true; // short-circuit; nothing sent; wp_mail() returns true (no errors for callers)
}

/* Make the guard state unmistakable in wp-admin. */
add_action( 'admin_notices', 'aarepdc_mailguard_admin_notice' );
function aarepdc_mailguard_admin_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! aarepdc_mailguard_enabled() ) {
		echo '<div class="notice notice-warning"><p><strong>AAREP Mail Guard is DISABLED</strong> — outbound email will send normally. Re-enable with <code>wp aarepdc-mail enable</code>.</p></div>';
		return;
	}
	$allow = aarepdc_mailguard_allowlist();
	$who   = $allow ? esc_html( implode( ', ', $allow ) ) : 'NOBODY (all outbound email is blocked)';
	echo '<div class="notice notice-error"><p><strong>AAREP Mail Guard ACTIVE</strong> — outbound email is blocked except to: <strong>' . $who . '</strong>. Members receive nothing. Reminder: API senders like Mailchimp bypass this and must be disconnected separately. The counts-only log is stored privately.</p></div>';
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {

	class AAREPDC_MailGuard_CLI {

		/** Show current guard status. */
		public function status() {
			WP_CLI::log( 'enabled (blocking): ' . ( aarepdc_mailguard_enabled() ? 'YES' : 'no' ) );
			WP_CLI::log( 'hard-block constant: ' . ( defined( 'AAREPDC_MAILGUARD_HARD_BLOCK' ) && AAREPDC_MAILGUARD_HARD_BLOCK ? 'ON' : 'off' ) );
			$allow = aarepdc_mailguard_allowlist();
			WP_CLI::log( 'allowlist: ' . ( $allow ? implode( ', ', $allow ) : '(empty — everything blocked)' ) );
		}

		/** Replace the allowlist. Usage: wp aarepdc-mail allow "a@x.com,b@y.com" */
		public function allow( $args ) {
			update_option( 'aarepdc_mailguard_allowlist', isset( $args[0] ) ? $args[0] : '' );
			$this->status();
			WP_CLI::success( 'allowlist set' );
		}

		/** Add one address. Usage: wp aarepdc-mail add admin@example.com */
		public function add( $args ) {
			$email = isset( $args[0] ) ? strtolower( trim( $args[0] ) ) : '';
			if ( ! is_email( $email ) ) {
				WP_CLI::error( 'not a valid email' );
			}
			$cur   = aarepdc_mailguard_allowlist();
			$cur[] = $email;
			update_option( 'aarepdc_mailguard_allowlist', implode( ',', array_unique( $cur ) ) );
			$this->status();
		}

		/** Block everything (clears allowlist + enables guard). Usage: wp aarepdc-mail block_all */
		public function block_all() {
			update_option( 'aarepdc_mailguard_allowlist', '' );
			update_option( 'aarepdc_mailguard_enabled', '1' );
			WP_CLI::success( 'ALL outbound email blocked.' );
		}

		public function enable() {
			update_option( 'aarepdc_mailguard_enabled', '1' );
			WP_CLI::success( 'guard enabled (blocking)' );
		}

		public function disable() {
			update_option( 'aarepdc_mailguard_enabled', '0' );
			WP_CLI::warning( 'guard DISABLED — mail will send normally' );
		}

		/** Send a test email (only delivers if the recipient is on the allowlist). Usage: wp aarepdc-mail test admin@example.com */
		public function test( $args ) {
			$to = isset( $args[0] ) ? $args[0] : '';
			if ( ! is_email( $to ) ) {
				WP_CLI::error( 'usage: wp aarepdc-mail test <email>' );
			}
			$ok = wp_mail( $to, 'AAREP Mail Guard test ' . gmdate( 'Y-m-d H:i:s' ), 'If you received this, the allowlist is working. Sent from the AAREP DC STAGING site. Members never receive this guard test.' );
			WP_CLI::log( 'wp_mail() returned: ' . var_export( $ok, true ) . ' — check the private mail-guard log for ALLOWED/BLOCKED.' );
		}

		/** Tail the guard log. */
		public function log() {
			$f = aarepdc_mailguard_log_path();
			if ( ! file_exists( $f ) ) {
				WP_CLI::log( '(no log yet)' );
				return;
			}
			$lines = file( $f, FILE_IGNORE_NEW_LINES );
			WP_CLI::log( implode( "\n", array_slice( $lines, -40 ) ) );
		}
	}

	WP_CLI::add_command( 'aarepdc-mail', 'AAREPDC_MailGuard_CLI' );
}
