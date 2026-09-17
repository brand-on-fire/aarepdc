<?php
/**
 * Plugin Name: AAREP DC — Bootstrap
 * Description: Idempotent setup of PMP levels, pages, user fields, demo events, and demo users for the AAREP DC membership build (Phase 1 MVP demo). Registers PMP user fields on init and provides WP-CLI commands: `wp aarepdc bootstrap-all`.
 * Version: 1.0.0
 * Author: Brand on Fire (wp-build agent)
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * Hard rules:
 * - Idempotent: re-running creates nothing new if names/slugs match.
 * - Touches PMP tables only; does NOT touch the legacy aal10_membership_master / aal10_sponsorship_master.
 * - Does NOT modify the legacy `membership-regisration-details` plugin in any way.
 * - User fields are registered on every page load via the `init` hook (priority 20, after PMP loads).
 *
 * Deploy note: lives in plugins/ rather than mu-plugins/ because WPE strips wp-content/mu-plugins/ on git deploy.
 * Activation: `wp plugin activate aarepdc-bootstrap` — keep this plugin active for the life of the project.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'aarepdc_private_storage_path' ) ) {
	/** Return a writable, web-inaccessible path for operational artifacts. */
	function aarepdc_private_storage_path( $filename ) {
		$dir = trailingslashit( ABSPATH ) . '_wpeprivate/aarepdc';
		if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
			return '';
		}
		@chmod( $dir, 0700 );
		if ( ! is_writable( $dir ) ) {
			return '';
		}
		return trailingslashit( $dir ) . sanitize_file_name( $filename );
	}
}

if ( ! function_exists( 'aarepdc_private_log_append' ) ) {
	function aarepdc_private_log_append( $filename, $line ) {
		$path = aarepdc_private_storage_path( $filename );
		if ( '' === $path ) {
			return false;
		}
		$written = file_put_contents( $path, $line, FILE_APPEND | LOCK_EX );
		if ( false !== $written ) {
			@chmod( $path, 0600 );
		}
		return false !== $written;
	}
}

/* ---------- Q5 Schema constants (sync with implementation/wp-build-notes/aarepdc-q5-schema.md) ---------- */
const AAREPDC_REAL_ESTATE_SECTORS = array(
	'Architecture',
	'Affordable housing',
	'Asset Management',
	'Brokerage',
	'Capital Markets',
	'Construction',
	'Development',
	'Engineering',
	'Finance',
	'Government',
	'Green Building',
	'Investments',
	'Law',
	'Marketing',
	'Property management',
	'Proptech',
);
const AAREPDC_PROFESSIONAL_LEVELS = array(
	'Entry Level',
	'Associate',
	'Mid-Senior Level',
	'Executive',
	'CEO/Founder',
	'Retired',
);
const AAREPDC_COMMITTEE_PREFERENCES = array(
	'Programming',
	'Community Engagement',
	'Gala',
	'Membership and Sponsorships',
	'Legislative / Advocacy',
	'Young Professionals',
	'N/A',
);
const AAREPDC_PHONE_TYPES = array( 'Work', 'Mobile' );
const AAREPDC_GRACE_DAYS = 30;

/* ---------- Level definitions (Phase 1.2) ---------- */
function aarepdc_level_definitions() {
	return array(
		array(
			'name'              => 'General Membership',
			'description'       => 'General membership is open to real estate professionals in the Washington, D.C. area. Membership runs through the calendar year and expires on December 31 of the current year, regardless of when activated.',
			'initial_payment'   => 300.00,
			'billing_amount'    => 0,
			'cycle_number'      => 0,
			'cycle_period'      => '',
			'billing_limit'     => 0,
			'trial_amount'      => 0,
			'trial_limit'       => 0,
			'expiration_number' => 0,
			'expiration_period' => '',
			'allow_signups'     => 1,
			'aarepdc_kind'      => 'individual',
		),
		array(
			'name'              => 'Government / Non-Profit',
			'description'       => 'Government membership is open to real estate professionals in the Washington, D.C. area that work for a Government agency. Proof of employment will be required. Membership runs through the calendar year and expires on December 31 of the current year, regardless of when activated.',
			'initial_payment'   => 150.00,
			'billing_amount'    => 0,
			'cycle_number'      => 0,
			'cycle_period'      => '',
			'billing_limit'     => 0,
			'trial_amount'      => 0,
			'trial_limit'       => 0,
			'expiration_number' => 0,
			'expiration_period' => '',
			'allow_signups'     => 1,
			'aarepdc_kind'      => 'individual',
		),
		array(
			'name'              => 'Young Professional / Student',
			'description'       => 'Young Professional / Student membership is open to real estate professionals in the Washington, D.C. area that are currently enrolled in an accredited educational program related to real estate and/or for those individuals 35 years and under. Proof of enrollment or age will be required. Membership runs through the calendar year and expires on December 31 of the current year, regardless of when activated.',
			'initial_payment'   => 150.00,
			'billing_amount'    => 0,
			'cycle_number'      => 0,
			'cycle_period'      => '',
			'billing_limit'     => 0,
			'trial_amount'      => 0,
			'trial_limit'       => 0,
			'expiration_number' => 0,
			'expiration_period' => '',
			'allow_signups'     => 1,
			'aarepdc_kind'      => 'individual_student',
		),
		array(
			'name'              => 'Platinum Sponsor',
			'description'       => 'Annual sponsorship — Platinum tier. Bundles 8 employee memberships.',
			'initial_payment'   => 15000.00,
			'billing_amount'    => 0,
			'cycle_number'      => 0,
			'cycle_period'      => '',
			'billing_limit'     => 0,
			'trial_amount'      => 0,
			'trial_limit'       => 0,
			'expiration_number' => 0,
			'expiration_period' => '',
			'allow_signups'     => 0,
			'aarepdc_kind'      => 'sponsor',
			'aarepdc_bundled'   => 8,
		),
		array(
			'name'              => 'Gold Sponsor',
			'description'       => 'Annual sponsorship — Gold tier. Bundles 5 employee memberships.',
			'initial_payment'   => 10000.00,
			'billing_amount'    => 0,
			'cycle_number'      => 0,
			'cycle_period'      => '',
			'billing_limit'     => 0,
			'trial_amount'      => 0,
			'trial_limit'       => 0,
			'expiration_number' => 0,
			'expiration_period' => '',
			'allow_signups'     => 0,
			'aarepdc_kind'      => 'sponsor',
			'aarepdc_bundled'   => 5,
		),
		array(
			'name'              => 'Silver Sponsor',
			'description'       => 'Annual sponsorship — Silver tier. Bundles 3 employee memberships.',
			'initial_payment'   => 7500.00,
			'billing_amount'    => 0,
			'cycle_number'      => 0,
			'cycle_period'      => '',
			'billing_limit'     => 0,
			'trial_amount'      => 0,
			'trial_limit'       => 0,
			'expiration_number' => 0,
			'expiration_period' => '',
			'allow_signups'     => 0,
			'aarepdc_kind'      => 'sponsor',
			'aarepdc_bundled'   => 3,
		),
		array(
			'name'              => 'Bronze Sponsor',
			'description'       => 'Annual sponsorship — Bronze tier. Bundles 2 employee memberships.',
			'initial_payment'   => 5000.00,
			'billing_amount'    => 0,
			'cycle_number'      => 0,
			'cycle_period'      => '',
			'billing_limit'     => 0,
			'trial_amount'      => 0,
			'trial_limit'       => 0,
			'expiration_number' => 0,
			'expiration_period' => '',
			'allow_signups'     => 0,
			'aarepdc_kind'      => 'sponsor',
			'aarepdc_bundled'   => 2,
		),
		array(
			'name'              => 'Sponsor Employee',
			'description'       => 'Bundled employee membership — managed by parent sponsor. Inherits parent sponsor expiration.',
			'initial_payment'   => 0,
			'billing_amount'    => 0,
			'cycle_number'      => 0,
			'cycle_period'      => '',
			'billing_limit'     => 0,
			'trial_amount'      => 0,
			'trial_limit'       => 0,
			'expiration_number' => 0,
			'expiration_period' => '',
			'allow_signups'     => 0,
			'aarepdc_kind'      => 'sponsor_employee',
		),
		array(
			// Client request 2026-07-30: unpaid Board Member level, invisible to the public.
			// allow_signups=0 natively hides it from [pmpro_levels] AND blocks direct
			// ?level=N checkout (same mechanism as Sponsor Employee above). AAREP staff
			// assign it in wp-admin (Users -> member -> Membership section).
			'name'              => 'Board Member',
			'description'       => 'AAREP DC Board of Directors — complimentary membership, assigned by AAREP DC staff. Not available for public signup.',
			'initial_payment'   => 0,
			'billing_amount'    => 0,
			'cycle_number'      => 0,
			'cycle_period'      => '',
			'billing_limit'     => 0,
			'trial_amount'      => 0,
			'trial_limit'       => 0,
			'expiration_number' => 0,
			'expiration_period' => '',
			'allow_signups'     => 0,
			'aarepdc_kind'      => 'board',
		),
	);
}

/** Level id of the Board Member level (0 if not created yet). */
function aarepdc_board_level_id() {
	static $id = null;
	if ( null === $id ) {
		$id = (int) aarepdc_find_level_id_by_name( 'Board Member' );
	}
	return $id;
}

/** True if the user holds the Board Member level. */
function aarepdc_is_board_member( $user_id ) {
	$board = aarepdc_board_level_id();
	if ( ! $board ) {
		return false;
	}
	// Board sits outside the paid-membership group so it can coexist with a General or Sponsor
	// level. Check the specific level rather than whichever active membership PMPro returns first.
	if ( function_exists( 'pmpro_hasMembershipLevel' ) ) {
		return (bool) pmpro_hasMembershipLevel( $board, (int) $user_id );
	}
	if ( ! function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
		return false;
	}
	$lvl = pmpro_getMembershipLevelForUser( (int) $user_id );
	return $lvl && (int) $lvl->id === $board;
}

/** Level id of the staff-managed Sponsor Employee level (0 if not created yet). */
function aarepdc_sponsor_employee_level_id() {
	static $id = null;
	if ( null === $id ) {
		$id = (int) aarepdc_find_level_id_by_name( 'Sponsor Employee' );
	}
	return $id;
}

/** True if the user currently holds the staff-managed Sponsor Employee level. */
function aarepdc_is_sponsor_employee( $user_id ) {
	$employee = aarepdc_sponsor_employee_level_id();
	if ( ! $employee || ! function_exists( 'pmpro_hasMembershipLevel' ) ) {
		return false;
	}
	return (bool) pmpro_hasMembershipLevel( $employee, (int) $user_id );
}

/* Board badge in the member directory + profile: a small brand-blue chip after the name.
 * (Closes the client's Jul-21 item 6 now that the Board level exists.) */
add_filter( 'pmpro_member_directory_display_name', 'aarepdc_board_badge_display_name', 10, 2 );
function aarepdc_board_badge_display_name( $display_name, $user ) {
	if ( is_object( $user ) && ! empty( $user->ID ) && aarepdc_is_board_member( $user->ID ) ) {
		$display_name .= ' <span class="aarepdc-board-chip">Board</span>';
	}
	return $display_name;
}

/* Board and Sponsor Employee memberships are staff-managed, so their account cards must not offer
 * public membership controls. Paid AAREP memberships are one-time calendar-year purchases; they
 * end automatically unless renewed, so an immediate-cancellation link would contradict the agreed
 * end-of-term behavior. Profile/password controls remain available. */
add_filter( 'pmpro_member_action_links', 'aarepdc_board_member_action_links', 20, 2 );
function aarepdc_board_member_action_links( $links, $level_id ) {
	if ( in_array( (int) $level_id, array( aarepdc_board_level_id(), aarepdc_sponsor_employee_level_id() ), true ) ) {
		unset( $links['change'], $links['cancel'] );
	} elseif ( function_exists( 'aarepdc_is_paid_level_id' ) && aarepdc_is_paid_level_id( $level_id ) && ! aarepdc_user_has_active_subscription( get_current_user_id() ) ) {
		unset( $links['cancel'] );
	}
	return $links;
}

/** Reversible cutover switch shared by the public membership UI and legacy handler. */
function aarepdc_membership_cutover_enabled() {
	return '1' === (string) get_option( 'aarepdc_membership_cutover_enabled', '0' );
}

/** Short launch-window freeze that closes only membership registration routes. */
function aarepdc_membership_freeze_enabled() {
	return '1' === (string) get_option( 'aarepdc_membership_freeze_enabled', '0' );
}

/** The exact PMPro Member Directory dependency required by Directory/Profile/Messages. */
function aarepdc_member_directory_dependency_ready() {
	return shortcode_exists( 'pmpro_member_directory' )
		&& shortcode_exists( 'pmpro_member_profile' )
		&& function_exists( 'pmpromd_get_user' );
}

/* Membership-only cutover boundary.
 * OFF: legacy /become-a-member/ remains authoritative and the new join/checkout pages are private.
 * ON:  /become-a-member/ redirects to /join/. The same option blocks only membership_online in
 *      the legacy handler before Stripe loads; sponsorship and event flows remain unchanged. */
// PMPro processes checkout on `wp` priority 2, so this must run at priority 1 to make
// the OFF state a true no-charge boundary even for a direct checkout POST.
add_action( 'wp', 'aarepdc_legacy_redirects', 1 );
function aarepdc_legacy_redirects() {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) : '';
	if ( ! $path ) {
		return;
	}
	$normalized       = '/' . trim( $path, '/' ) . '/';
	$is_legacy_signup = is_page( 'become-a-member' ) || '/become-a-member/' === $normalized;
	$is_portal_signup = is_page( array( 'join', 'membership-checkout' ) ) || in_array( $normalized, array( '/join/', '/membership-checkout/' ), true );
	if ( aarepdc_membership_freeze_enabled() && ( $is_legacy_signup || $is_portal_signup ) ) {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		nocache_headers();
		header( 'Retry-After: 600' );
		wp_die(
			esc_html__( 'Membership registration is briefly paused while AAREP DC completes the portal update. Please try again shortly.', 'aarepdc' ),
			esc_html__( 'Membership registration temporarily paused', 'aarepdc' ),
			array( 'response' => 503 )
		);
	}
	/* These two redirects flip direction the instant the cutover option changes, so they must
	 * never be cached. Without this, Varnish and the WP Engine edge hold a 302 for its 600s
	 * max-age and visitors bounce between /become-a-member/ and /join/ in a loop until it
	 * expires. The freeze branch above already does this; these did not. */
	if ( aarepdc_membership_cutover_enabled() && $is_legacy_signup ) {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		nocache_headers();
		wp_safe_redirect( home_url( '/join/' ), 302 );
		exit;
	}
	if ( ! aarepdc_membership_cutover_enabled() && $is_portal_signup ) {
		$method        = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) : 'GET';
		$admin_preview = current_user_can( 'manage_options' ) && 'GET' === $method;
		if ( ! $admin_preview ) {
			if ( ! defined( 'DONOTCACHEPAGE' ) ) {
				define( 'DONOTCACHEPAGE', true );
			}
			nocache_headers();
			wp_safe_redirect( home_url( '/become-a-member/' ), 302 );
			exit;
		}
	}
}

/* ---------- Body class: aarepdc-no-hero (audit-fix-3 2026-04-28) ----------
 * Only the new PMP/member pages we built in Phase 1 have no hero — they're plain pages where
 * the absolute-positioned white-text header overlays a white body, making the nav invisible.
 * Pages with hero banners or featured images (home, /membership/, /events/, /about-us/,
 * /become-a-member/, /become-a-sponsor/, etc.) must NOT get the aarepdc-no-hero class —
 * their transparent header overlays the hero correctly.
 *
 * Use an explicit slug allowlist of our newly-created PMP/member pages.
 */
add_filter( 'body_class', 'aarepdc_no_hero_body_class' );
function aarepdc_no_hero_body_class( $classes ) {
	if ( ! is_page() ) {
		return $classes;
	}
	$post = get_post();
	if ( ! $post || empty( $post->post_name ) ) {
		return $classes;
	}
	$no_hero_slugs = array(
		'member-login',
		'member-account',
		'member-directory',
		'members-resources',
		'join',
		'my-employees',
		'membership-checkout',
		'membership-confirmation',
		'membership-billing',
		'membership-cancel',
		'membership-invoice',
		'your-profile',
		'whats-new',
		'member-feed',
		'renew',
		'aarep-national-network',
	);
	if ( in_array( $post->post_name, $no_hero_slugs, true ) ) {
		$classes[] = 'aarepdc-no-hero';
	}
	return $classes;
}

/* ---------- Member-only event archive badging (Phase 1.7) ---------- */
add_filter( 'tribe_events_event_classes', 'aarepdc_event_classes' );
function aarepdc_event_classes( $classes ) {
	$post_id = get_the_ID();
	if ( $post_id && get_post_meta( $post_id, '_aarepdc_members_only', true ) ) {
		$classes[] = 'aarepdc-members-only-event';
	}
	return $classes;
}

/** Resolve the three public individual levels by checked-in names, never by portable-looking IDs. */
function aarepdc_individual_level_ids() {
	$ids = array();
	foreach ( aarepdc_level_definitions() as $definition ) {
		$kind = isset( $definition['aarepdc_kind'] ) ? $definition['aarepdc_kind'] : '';
		if ( ! in_array( $kind, array( 'individual', 'individual_student' ), true ) ) {
			continue;
		}
		$level_id = aarepdc_find_level_id_by_name( $definition['name'] );
		if ( $level_id ) {
			$ids[] = (int) $level_id;
		}
	}
	return 3 === count( array_unique( $ids ) ) ? array_values( array_unique( $ids ) ) : array();
}

/* ---------- Wrapper shortcode for /join: render only individual levels (audit-fix-3 2026-04-28) ----------
 * Avoid embedding [pmpro_levels levels="..."] directly in page content — wpautop / wptexturize
 * smart-quotes the attribute (`"` → `&rdquo;` / `&Prime;`), breaking shortcode parsing. This wrapper
 * runs do_shortcode internally so the attribute string is never exposed to texturize.
 */
add_shortcode( 'aarepdc_individual_levels', 'aarepdc_individual_levels_shortcode' );
function aarepdc_individual_levels_shortcode() {
	$ids = aarepdc_individual_level_ids();
	return $ids ? do_shortcode( '[pmpro_levels levels="' . implode( ',', $ids ) . '"]' ) : '';
}

/* ---------- /my-employees stub shortcode (Phase 1.11) ---------- */
add_shortcode( 'aarepdc_my_employees_stub', 'aarepdc_my_employees_stub_shortcode' );
function aarepdc_my_employees_stub_shortcode() {
	if ( ! is_user_logged_in() ) {
		return '<p>This page is for sponsor accounts only. Please <a href="/member-login/">log in</a>.</p>';
	}
	$user_id   = get_current_user_id();
	$is_sponsor = false;
	if ( function_exists( 'pmpro_hasMembershipLevel' ) ) {
		$sponsor_level_ids = aarepdc_sponsor_level_ids();
		if ( $sponsor_level_ids && pmpro_hasMembershipLevel( $sponsor_level_ids, $user_id ) ) {
			$is_sponsor = true;
		}
	}
	if ( ! $is_sponsor ) {
		return '<p>This page is for sponsor accounts only. <a href="/member-account/">Return to your account.</a></p>';
	}

	$bundled = 0;
	if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
		$lvl = pmpro_getMembershipLevelForUser( $user_id );
		if ( $lvl && function_exists( 'get_pmpro_membership_level_meta' ) ) {
			$meta_val = get_pmpro_membership_level_meta( $lvl->id, 'bundled_employee_count', true );
			$bundled  = (int) $meta_val;
		}
	}

	ob_start();
	?>
	<div class="aarepdc-my-employees-stub">
		<h2>My Employees</h2>
		<p>Welcome to your sponsor dashboard. As a sponsor, you can manage your bundled employee memberships here.</p>
		<?php if ( $bundled > 0 ) : ?>
			<p>Your sponsorship includes <strong><?php echo (int) $bundled; ?> employee membership<?php echo $bundled === 1 ? '' : 's'; ?></strong>.</p>
		<?php endif; ?>
		<div class="aarepdc-employee-list-empty" style="border:1px dashed #c8c8c8; padding:1.25em; border-radius:6px; background:#fafafa; margin:1em 0;">
			<p style="margin:0 0 .5em 0;"><strong>No employees added yet.</strong></p>
			<p style="margin:0; color:#555;">Self-service employee management is coming next. In the meantime, please email <a href="mailto:info@aarepdc.org">info@aarepdc.org</a> with the employees you want added to your sponsorship and we'll set them up for you.</p>
		</div>
		<p style="font-size:.9em; color:#666;"><em>Coming soon (Phase 3): add an employee, send their welcome email, manage their access — all from this page.</em></p>
	</div>
	<?php
	return ob_get_clean();
}

function aarepdc_sponsor_level_ids() {
	global $wpdb;
	$rows = $wpdb->get_col(
		"SELECT pml.id
		   FROM {$wpdb->prefix}pmpro_membership_levels pml
		   JOIN {$wpdb->prefix}pmpro_membership_levelmeta plm
		     ON plm.pmpro_membership_level_id = pml.id
		  WHERE plm.meta_key = 'aarepdc_kind' AND plm.meta_value = 'sponsor'"
	);
	if ( ! $rows ) {
		return array();
	}
	return array_map( 'intval', $rows );
}

/* ==========================================================================
 * WS1 - Member profile / account enhancements (2026-06-16)
 *   - Welcome banner + details card (first/last/Role/Company) on /member-account/
 *   - Profile preview on the edit-profile page (/your-profile/)
 *   - Log Out link in the account 'Member Links' section (nav-menu logout = child theme)
 * The card helper is shared so the welcome block and preview always match.
 * ========================================================================== */

/**
 * Render a member details card from saved profile data. Shared by the account
 * welcome block and the edit-profile preview so they stay visually identical.
 */
function aarepdc_render_member_card( $user_id ) {
	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return '';
	}
	// During a profile-update submission, prefer the just-submitted values so the preview is
	// correct in the SAME request — the server render runs before PMPro persists the save, so
	// without this the card would show the pre-save value until the page reloaded. (The live JS
	// also mirrors edits; this keeps the server output right with no flash.)
	$saving = ( isset( $_POST['action'], $_POST['user_id'] ) && 'update-profile' === $_POST['action'] && (int) $_POST['user_id'] === (int) $user_id );
	$pv = function ( $post_key, $fallback ) use ( $saving ) {
		return ( $saving && isset( $_POST[ $post_key ] ) ) ? sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) ) : $fallback;
	};
	$rows = array(
		'First Name'             => $pv( 'first_name', $user->first_name ),
		'Last Name'              => $pv( 'last_name', $user->last_name ),
		'Role / Job Title'       => $pv( 'aarepdc_role', get_user_meta( $user_id, 'aarepdc_role', true ) ),
		'Company / Organization' => $pv( 'aarepdc_company_name', get_user_meta( $user_id, 'aarepdc_company_name', true ) ),
	);
	$out  = '<div class="aarepdc-card-avatar">' . get_avatar( $user_id, 96 ) . '</div>';
	$out .= '<ul class="aarepdc-member-card-list">';
	foreach ( $rows as $label => $value ) {
		$value   = is_array( $value ) ? implode( ', ', $value ) : trim( (string) $value );
		$display = ( '' === $value ) ? '<em class="aarepdc-empty">Not set</em>' : esc_html( $value );
		$out .= '<li><span class="aarepdc-card-label">' . esc_html( $label ) . '</span><span class="aarepdc-card-value">' . $display . '</span></li>';
	}
	$out .= '</ul>';
	return $out;
}

/* Welcome banner + details card prepended to the member account page. */
add_filter( 'the_content', 'aarepdc_account_welcome_block' );
function aarepdc_account_welcome_block( $content ) {
	if ( is_admin() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$account_id = (int) get_option( 'pmpro_account_page_id' );
	if ( ! $account_id || ! is_page( $account_id ) || ! is_user_logged_in() ) {
		return $content;
	}
	$user_id     = get_current_user_id();
	$account_url = get_permalink( $account_id );

	// Payments / receipts view (focused panel; replaces the membership section).
	if ( isset( $_GET['view'] ) && 'payments' === $_GET['view'] ) {
		return aarepdc_render_payments_panel( $user_id, $account_url );
	}
	// Member Messages view (aarepdc-messages.php).
	if ( isset( $_GET['view'] ) && 'messages' === $_GET['view'] && function_exists( 'aarepdc_msg_render_panel' ) ) {
		return aarepdc_msg_render_panel( $user_id, $account_url );
	}

	$user    = wp_get_current_user();
	$first   = $user->first_name ? $user->first_name : $user->display_name;
	$edit_id  = (int) get_option( 'pmpro_member_profile_edit_page_id' );
	$edit_url = $edit_id ? get_permalink( $edit_id ) : '';
	$out  = '';
	$show_term_end_notice = false;
	if ( isset( $_GET['aarepdc_term_end'] ) && ! aarepdc_user_has_active_subscription( $user_id ) && function_exists( 'pmpro_hasMembershipLevel' ) ) {
		foreach ( aarepdc_paid_level_ids() as $paid_id ) {
			if ( pmpro_hasMembershipLevel( $paid_id, $user_id ) ) {
				$show_term_end_notice = true;
				break;
			}
		}
	}
	if ( $show_term_end_notice ) {
		$out .= '<div class="aarepdc-notice" style="margin:0 0 18px;padding:13px 16px;border:1px solid #c7d7ef;border-radius:8px;background:#f4f7fc;color:#1e4480;">Your membership is already set not to renew automatically. The term ends December 31, followed by the standard 30-day grace period.</div>';
	}
	if ( isset( $_GET['aarepdc_subscription_review'] ) && aarepdc_user_has_active_subscription( $user_id ) ) {
		$out .= '<div class="aarepdc-notice" style="margin:0 0 18px;padding:13px 16px;border:1px solid #f0d9a8;border-radius:8px;background:#fff7e6;color:#7a5b1a;">This account has an older payment subscription that must be reviewed by AAREP DC staff before cancellation. No membership or payment change was made.</div>';
	}
	$out .= '<div class="aarepdc-welcome">';
	$out .= '<h2 class="aarepdc-welcome-title">Welcome, ' . esc_html( $first ) . '!</h2>';
	$out .= '<div class="aarepdc-member-card">' . aarepdc_render_member_card( $user_id ) . '</div>';
	$out .= '<p class="aarepdc-account-email"><strong>Email:</strong> ' . esc_html( $user->user_email ) . '</p>';
	$out .= '<p class="aarepdc-card-actions">';
	if ( $edit_url ) {
		$out .= '<a class="aarepdc-btn" href="' . esc_url( $edit_url ) . '">Edit your profile</a> ';
		$out .= '<a class="aarepdc-btn aarepdc-btn-ghost" href="' . esc_url( add_query_arg( 'view', 'change-password', $edit_url ) ) . '">Change password</a> ';
	}
	$out .= '<a class="aarepdc-btn aarepdc-btn-ghost" href="' . esc_url( add_query_arg( 'view', 'payments', $account_url ) ) . '">Payments</a> ';
	if ( function_exists( 'aarepdc_msg_unread_count' ) ) {
		$unread = aarepdc_msg_unread_count( $user_id );
		$badge  = $unread ? ' <span class="aarepdc-msg-badge">' . (int) $unread . '</span>' : '';
		$out   .= '<a class="aarepdc-btn aarepdc-btn-ghost" href="' . esc_url( add_query_arg( 'view', 'messages', $account_url ) ) . '">Messages' . $badge . '</a> ';
	}
	$feed_page = get_page_by_path( 'member-feed' );
	if ( $feed_page ) {
		$out .= '<a class="aarepdc-btn aarepdc-btn-ghost" href="' . esc_url( get_permalink( $feed_page->ID ) ) . '">Member Feed</a> ';
	}
	$dir = get_page_by_path( 'member-directory' );
	if ( $dir ) {
		$out .= '<a class="aarepdc-btn aarepdc-btn-ghost" href="' . esc_url( get_permalink( $dir->ID ) ) . '">Member directory</a> ';
	}
	$resources = get_page_by_path( 'members-resources' );
	if ( $resources && 'publish' === $resources->post_status ) {
		$out .= '<a class="aarepdc-btn aarepdc-btn-ghost" href="' . esc_url( get_permalink( $resources->ID ) ) . '">Resources</a> ';
	}
	$events = get_page_by_path( 'whats-happening' );
	if ( $events && 'publish' === $events->post_status ) {
		$out .= '<a class="aarepdc-btn aarepdc-btn-ghost" href="' . esc_url( get_permalink( $events->ID ) . '#events_page_row' ) . '">Events</a> ';
	}
	// Log out always sits last in the action row.
	$out .= '<a class="aarepdc-btn aarepdc-btn-ghost" href="' . esc_url( wp_logout_url( home_url() ) ) . '">Log out</a>';
	$out .= '</p>';
	$out .= '</div>';
	$out .= aarepdc_help_card();
	return $out . $content;
}

/* "Questions?" support card — client request 2026-07-21. Points members at info@aarepdc.org for
 * site navigation, account access and membership questions. */
function aarepdc_help_card() {
	return '<div class="aarepdc-help-card" style="margin:22px 0 0;padding:16px 20px;background:#f4f7fc;border:1px solid #dbe4f2;border-radius:10px;">'
		. '<h3 style="margin:0 0 6px;color:#1e4480;font-size:16px;">'
		. '<span aria-hidden="true" style="display:inline-block;width:20px;height:20px;line-height:20px;text-align:center;margin-right:8px;border-radius:50%;background:#1e4480;color:#fff;font-size:13px;font-weight:bold;">?</span>'
		. 'Questions?</h3>'
		. '<p style="margin:0;color:#475467;font-size:14px;line-height:1.6;">Need help with the site, your account, or your membership? Email '
		. '<a href="mailto:info@aarepdc.org" style="color:#1e4480;font-weight:bold;">info@aarepdc.org</a> and we&rsquo;ll get right back to you.</p>'
		. '</div>';
}

/* Compact help line on the member directory. */
add_filter( 'the_content', 'aarepdc_directory_help_line', 20 );
function aarepdc_directory_help_line( $content ) {
	if ( is_admin() || ! in_the_loop() || ! is_main_query() || ! is_user_logged_in() ) {
		return $content;
	}
	$dir = get_page_by_path( 'member-directory' );
	if ( ! $dir || ! is_page( $dir->ID ) ) {
		return $content;
	}
	return $content . aarepdc_help_card();
}

/* Member-portal "Payments" view: a read-only list of the member's receipts (PMPro orders).
 * No Stripe login / membership management yet — just the receipts. A row appears whenever a
 * checkout or renewal creates an order. */
function aarepdc_render_payments_panel( $user_id, $account_url ) {
	global $wpdb;
	$orders = $wpdb->get_results( $wpdb->prepare(
		"SELECT code, total, status, membership_id, cardtype, accountnumber, timestamp, gateway_environment
		   FROM {$wpdb->prefix}pmpro_membership_orders
		  WHERE user_id = %d AND status NOT IN ( 'token', 'review', 'error' )
		  ORDER BY timestamp DESC LIMIT 50",
		$user_id
	) );
	// PMPro's setting is `pmpro_invoice_page_id` (singular). The plural key is always 0, which is
	// why the "View receipt" link never rendered. Fall back to the /membership-invoice/ page.
	$invoices_pid = (int) get_option( 'pmpro_invoice_page_id' );
	if ( ! $invoices_pid ) {
		$inv          = get_page_by_path( 'membership-invoice' );
		$invoices_pid = $inv ? (int) $inv->ID : 0;
	}
	$cell = 'padding:10px 12px;border-bottom:1px solid #eef1f6;font-size:14px;color:#33384a;text-align:left;';
	$out  = '<div class="aarepdc-welcome aarepdc-payments">';
	$out .= '<p style="margin:0 0 14px;"><a class="aarepdc-btn aarepdc-btn-ghost" href="' . esc_url( $account_url ) . '">&larr; Back to Member Portal</a></p>';
	$out .= '<h2 class="aarepdc-welcome-title">Payments &amp; receipts</h2>';
	$term = aarepdc_membership_term_label( $user_id );
	if ( '' !== $term ) {
		$out .= '<p style="margin:0 0 16px;color:#475467;font-size:14px;">Membership term: <strong>' . esc_html( $term ) . '</strong></p>';
	}
	if ( empty( $orders ) ) {
		$out .= '<p>You have no payments yet. When you join or renew, your receipts will appear here.</p></div>';
		return $out;
	}
	$out .= '<table style="width:100%;border-collapse:collapse;margin-top:8px;">';
	$out .= '<thead><tr>'
		. '<th style="' . $cell . 'font-weight:600;">Date</th>'
		. '<th style="' . $cell . 'font-weight:600;">Membership</th>'
		. '<th style="' . $cell . 'font-weight:600;">Amount</th>'
		. '<th style="' . $cell . 'font-weight:600;">Card</th>'
		. '<th style="' . $cell . 'font-weight:600;">Status</th>'
		. '<th style="' . $cell . 'font-weight:600;"></th>'
		. '</tr></thead><tbody>';
	foreach ( $orders as $o ) {
		$lvl    = pmpro_getLevel( (int) $o->membership_id );
		$date   = $o->timestamp ? date_i18n( get_option( 'date_format' ) . ' g:i a', strtotime( $o->timestamp ) ) : '';
		$amt    = '$' . number_format( (float) $o->total, 2 );
		// PMPro stores accountnumber already masked ("XXXXXXXXXXXX4242"), so use just the last 4
		// digits — otherwise the bullets double up as "••••XXXXXXXXXXXX4242".
		$digits = preg_replace( '/\D/', '', (string) $o->accountnumber );
		$last4  = ( '' !== $digits ) ? substr( $digits, -4 ) : '';
		$card   = $last4 ? esc_html( trim( $o->cardtype . ' ••••' . $last4 ) ) : '&mdash;';
		$test   = ( 'sandbox' === $o->gateway_environment ) ? ' <em style="color:#7a5b1a;">(test)</em>' : '';
		$status = esc_html( ucfirst( (string) $o->status ) );
		$view   = $invoices_pid ? '<a href="' . esc_url( add_query_arg( 'invoice', $o->code, get_permalink( $invoices_pid ) ) ) . '">View receipt</a>' : '';
		$out   .= '<tr>'
			. '<td style="' . $cell . '">' . esc_html( $date ) . '</td>'
			. '<td style="' . $cell . '">' . esc_html( $lvl ? $lvl->name : '—' ) . '</td>'
			. '<td style="' . $cell . '">' . esc_html( $amt ) . $test . '</td>'
			. '<td style="' . $cell . '">' . $card . '</td>'
			. '<td style="' . $cell . '">' . $status . '</td>'
			. '<td style="' . $cell . '">' . $view . '</td>'
			. '</tr>';
	}
	$out .= '</tbody></table></div>';
	return $out;
}

/* Test-mode helper: when Stripe runs in sandbox, show the test-card details on the checkout form
 * so reviewers can complete a real test payment. Hidden automatically once switched to live. */
add_action( 'pmpro_checkout_before_submit_button', 'aarepdc_checkout_test_card_notice' );
function aarepdc_checkout_test_card_notice() {
	if ( 'sandbox' !== get_option( 'pmpro_gateway_environment' ) ) {
		return;
	}
	echo '<div style="margin:14px 0;padding:12px 16px;background:#fff7e6;border:1px solid #f0d9a8;border-radius:8px;color:#7a5b1a;font-size:14px;line-height:1.55;">'
		. '<strong>Test mode &mdash; no real charge will be made.</strong><br>'
		. 'Use card <strong>4242&nbsp;4242&nbsp;4242&nbsp;4242</strong> &middot; any future expiry (e.g. 12&thinsp;/&thinsp;34) &middot; any CVC (e.g. 123) &middot; any ZIP (e.g. 20001).'
		. '</div>';
}

/* Exact staging-host helper shared by preview-only behavior. */
function aarepdc_is_staging_demo() {
	return 'aarepstg.wpengine.com' === wp_parse_url( home_url(), PHP_URL_HOST );
}

/* The former payment-reset demo is retired. Keep the cleanup hook so any stale scheduled event is
 * removed, but never cancel a membership, call a gateway, or delete payment evidence. */
add_action( 'init', 'aarepdc_schedule_renewal_demo_reset' );
function aarepdc_schedule_renewal_demo_reset() {
	if ( wp_next_scheduled( 'aarepdc_renewal_demo_reset' ) ) {
		wp_clear_scheduled_hook( 'aarepdc_renewal_demo_reset' );
	}
}

function aarepdc_reset_member_to_expired( $user_id, $force_level_id = 0 ) {
	return 0;
}

add_action( 'aarepdc_renewal_demo_reset', 'aarepdc_renewal_demo_reset_run' );
function aarepdc_renewal_demo_reset_run() {
	return;
}

/* STAGING: Bcc the AAREP review team + agency on member-facing PMPro emails (receipts, renewal
 * confirmations) so test payments can be confirmed. Only on staging; the mail guard still gates
 * the primary recipient (real members stay blocked) and these three are allowlisted. */
add_filter( 'pmpro_email_headers', 'aarepdc_bcc_demo_emails', 10, 2 );
function aarepdc_bcc_demo_emails( $headers, $email = null ) {
	if ( ! aarepdc_is_staging_demo() ) {
		return $headers;
	}
	$bcc = 'Bcc: heather@cubeddevelopment.com, executive@aarepdc.org, info@brandonfire.com';
	if ( is_array( $headers ) ) {
		$headers[] = $bcc;
	} else {
		$headers  = trim( (string) $headers );
		$headers .= ( '' === $headers ? '' : "\r\n" ) . $bcc;
	}
	return $headers;
}

/* Members shouldn't see the WordPress admin toolbar — and on logged-in member pages it shifts the
 * header down and crops the top of the content. Hide it for anyone who can't manage the site. */
add_filter( 'show_admin_bar', 'aarepdc_hide_admin_bar_for_members' );
function aarepdc_hide_admin_bar_for_members( $show ) {
	return current_user_can( 'manage_options' ) ? $show : false;
}

/* Profile preview prepended to the edit-profile page. */
add_filter( 'the_content', 'aarepdc_profile_preview_block' );
function aarepdc_profile_preview_block( $content ) {
	if ( is_admin() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$edit_id = (int) get_option( 'pmpro_member_profile_edit_page_id' );
	if ( ! $edit_id || ! is_page( $edit_id ) || ! is_user_logged_in() ) {
		return $content;
	}
	$user_id = get_current_user_id();
	$out  = '<div class="aarepdc-profile-preview">';
	$out .= '<h2 class="aarepdc-preview-title">Profile preview</h2>';
	$out .= '<p class="aarepdc-preview-note">This preview updates live as you edit your details below. Click <strong>Update Profile</strong> to save your changes.</p>';
	$out .= '<div class="aarepdc-member-card">' . aarepdc_render_member_card( $user_id ) . '</div>';
	$out .= '</div>';
	return $out . $content;
}

/* Live profile preview: mirror the edit-form fields into the preview card as the member types,
 * and once on load. The on-load sync also corrects the post-save render — the server builds the
 * preview at the_content priority 10, BEFORE PMPro processes the save during shortcode expansion,
 * so without this the preview would show the pre-save value for one request after saving. */
add_action( 'wp_footer', 'aarepdc_profile_preview_live_js' );
function aarepdc_profile_preview_live_js() {
	$edit_id = (int) get_option( 'pmpro_member_profile_edit_page_id' );
	if ( ! $edit_id || ! is_page( $edit_id ) || ! is_user_logged_in() ) {
		return;
	}
	?>
<script>
(function(){
	var MAP=[{f:'first_name',l:'First Name'},{f:'last_name',l:'Last Name'},{f:'aarepdc_role',l:'Role / Job Title'},{f:'aarepdc_company_name',l:'Company / Organization'}];
	function init(){
		var card=document.querySelector('.aarepdc-profile-preview .aarepdc-member-card-list');
		if(!card){return;}
		var vals={};
		card.querySelectorAll('li').forEach(function(li){var lab=li.querySelector('.aarepdc-card-label'),v=li.querySelector('.aarepdc-card-value');if(lab&&v){vals[lab.textContent.trim()]=v;}});
		function sync(){MAP.forEach(function(m){var inp=document.querySelector('[name="'+m.f+'"]'),el=vals[m.l];if(!inp||!el){return;}var t=(inp.value||'').trim();if(t===''){el.innerHTML='<em class="aarepdc-empty">Not set</em>';}else{el.textContent=t;}});}
		MAP.forEach(function(m){var inp=document.querySelector('[name="'+m.f+'"]');if(inp){inp.addEventListener('input',sync);inp.addEventListener('change',sync);}});
		sync();
	}
	if(document.readyState!=='loading'){init();}else{document.addEventListener('DOMContentLoaded',init);}
})();
</script>
	<?php
}

/* (Logout lives in the welcome-card actions + the nav; no separate Member Links section.) */

/* ---------- PMP user fields registration (Phase 1.4) ----------
 * IMPORTANT (audit-fix-2 2026-04-28): PMP's pmpro_add_user_field( $where, $field )
 * accepts a SINGLE PMPro_Field per call and silently fails if you pass an array.
 * Earlier code passed arrays — fields never registered, so the checkout form was
 * empty and the profile page showed no custom AAREP fields.
 *
 * Correct pattern: define a field group with pmpro_add_field_group(), then loop
 * and call pmpro_add_user_field( <group_name>, $single_field ) for each field.
 * Fields then appear on BOTH the checkout form (displayAtCheckout) and the
 * member profile/account page (displayInProfile) automatically. */
add_action( 'init', 'aarepdc_register_user_fields', 20 );
function aarepdc_register_user_fields() {
	if ( ! function_exists( 'pmpro_add_user_field' ) || ! function_exists( 'pmpro_add_field_group' ) || ! class_exists( 'PMPro_Field' ) ) {
		return;
	}

	$student_level_id = aarepdc_find_level_id_by_name( 'Young Professional / Student' );

	$contact_group = 'aarepdc_contact';
	pmpro_add_field_group( $contact_group, 'Contact Information', 'How AAREP DC can reach you.' );
	$base_group = 'aarepdc_professional';
	pmpro_add_field_group( $base_group, 'Professional Details', 'Tell us about your work in real estate.' );
	$privacy_group = 'aarepdc_privacy';
	pmpro_add_field_group( $privacy_group, 'Directory Visibility', 'Choose what other members can see in the member directory. Your details are never shown to the public — only to signed-in AAREP DC members.' );

	/* Directory visibility (client request 2026-07-21). Defaults are deliberate: LinkedIn shows,
	 * email + phone stay hidden until the member opts in — the 105 migrated members never chose,
	 * so we must not expose contact details on their behalf. Members are still reachable via the
	 * in-platform Messages feature, so hiding email costs them nothing. */
	$privacy_fields = array(
		new PMPro_Field( 'aarepdc_linkedin_url', 'text', array(
			'label'    => 'LinkedIn Profile',
			'required' => false,
			'profile'  => true,
			'hint'     => 'Optional. Paste your LinkedIn profile URL.',
			'html_attributes' => array( 'inputmode' => 'url', 'placeholder' => 'https://www.linkedin.com/in/…' ),
		) ),
		new PMPro_Field( 'aarepdc_show_linkedin', 'checkbox', array(
			'label'    => 'Show my LinkedIn in the member directory',
			'required' => false,
			'profile'  => true,
		) ),
		new PMPro_Field( 'aarepdc_show_email', 'checkbox', array(
			'label'    => 'Show my email address in the member directory',
			'required' => false,
			'profile'  => true,
			'hint'     => 'Off by default. Members can always reach you through Messages without seeing your address.',
		) ),
		new PMPro_Field( 'aarepdc_show_phone', 'checkbox', array(
			'label'    => 'Show my phone number in the member directory',
			'required' => false,
			'profile'  => true,
		) ),
	);
	foreach ( $privacy_fields as $f ) {
		pmpro_add_user_field( $privacy_group, $f );
	}

	/* audit-fix-3 (2026-04-28): removed aarepdc_address1/address2/city/state/postal_code/country
	 * to deduplicate against PMP's standard billing-address fields (bbillingaddress1, bbillingcity,
	 * bbillingstate, bbillingzip, bbillingcountry). The legacy demo-user data still lives in
	 * user_meta for migration / display purposes — it just doesn't render on the form anymore. */
	$base_fields = array(
		new PMPro_Field( 'aarepdc_profile_photo', 'file', array(
			'label'    => 'Profile Photo',
			'required' => false,
			'profile'  => true,
			'hint'     => 'Optional. A square headshot looks best in the member directory.',
		) ),
		new PMPro_Field( 'aarepdc_phone_number', 'text', array(
			'label'    => 'Phone Number',
			'required' => false,
			'profile'  => true,
			'html_attributes' => array( 'inputmode' => 'tel', 'autocomplete' => 'tel' ),
		) ),
		new PMPro_Field( 'aarepdc_phone_type', 'radio', array(
			'label'    => 'Phone Type',
			'options'  => array( 'Work' => 'Work', 'Mobile' => 'Mobile' ),
			'default'  => 'Mobile',
			'required' => false,
			'profile'  => true,
		) ),
		new PMPro_Field( 'aarepdc_company_name', 'text', array(
			'label'    => 'Company / Organization',
			'required' => false,
			'profile'  => true,
		) ),
		new PMPro_Field( 'aarepdc_role', 'text', array(
			'label'    => 'Role / Job Title',
			'required' => false,
			'profile'  => true,
			'html_attributes' => array( 'autocomplete' => 'organization-title' ),
		) ),
		new PMPro_Field( 'aarepdc_real_estate_sector', 'select', array(
			'label'    => 'Real Estate Sector',
			'options'  => aarepdc_options_assoc( AAREPDC_REAL_ESTATE_SECTORS ),
			'required' => false,
			'profile'  => true,
		) ),
		new PMPro_Field( 'aarepdc_professional_level', 'select', array(
			'label'    => 'Professional Level',
			'options'  => aarepdc_options_assoc( AAREPDC_PROFESSIONAL_LEVELS ),
			'required' => false,
			'profile'  => true,
		) ),
		new PMPro_Field( 'aarepdc_experience_real_estate', 'text', array(
			'label'    => 'Years of Experience in Real Estate',
			'required' => false,
			'profile'  => true,
		) ),
		new PMPro_Field( 'aarepdc_committee_preference', 'checkbox_grouped', array(
			'label'    => 'Committee Preference',
			'options'  => array_combine( AAREPDC_COMMITTEE_PREFERENCES, AAREPDC_COMMITTEE_PREFERENCES ),
			'required' => false,
			'profile'  => true,
		) ),
	);

	$contact_keys = array( 'aarepdc_profile_photo', 'aarepdc_phone_number', 'aarepdc_phone_type' );
	foreach ( $base_fields as $field ) {
		$grp = in_array( $field->name, $contact_keys, true ) ? $contact_group : $base_group;
		pmpro_add_user_field( $grp, $field );
	}

	if ( $student_level_id ) {
		$student_group = 'aarepdc_student_education';
		pmpro_add_field_group( $student_group, 'AAREP DC — Education (Young Professional / Student only)', 'Education details for student membership applicants.' );

		$student_fields = array(
			new PMPro_Field( 'aarepdc_college_university', 'text', array(
				'label'    => 'College / University',
				'required' => false,
				'profile'  => true,
				'levels'   => array( $student_level_id ),
			) ),
			new PMPro_Field( 'aarepdc_date_education', 'text', array(
				'label'    => 'Date of Education',
				'required' => false,
				'profile'  => true,
				'levels'   => array( $student_level_id ),
			) ),
			new PMPro_Field( 'aarepdc_degree_expected', 'text', array(
				'label'    => 'Degree Expected',
				'required' => false,
				'profile'  => true,
				'levels'   => array( $student_level_id ),
			) ),
			new PMPro_Field( 'aarepdc_credits_completed', 'text', array(
				'label'    => 'Credits Completed',
				'required' => false,
				'profile'  => true,
				'levels'   => array( $student_level_id ),
			) ),
			new PMPro_Field( 'aarepdc_relevant_experience', 'textarea', array(
				'label'    => 'Relevant Experience',
				'required' => false,
				'profile'  => true,
				'levels'   => array( $student_level_id ),
			) ),
		);
		foreach ( $student_fields as $field ) {
			pmpro_add_user_field( $student_group, $field );
		}
	}
}

function aarepdc_options_assoc( $list ) {
	$o = array( '' => '— Select —' );
	foreach ( $list as $v ) {
		$o[ $v ] = $v;
	}
	return $o;
}

function aarepdc_find_level_id_by_name( $name ) {
	global $wpdb;
	$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}pmpro_membership_levels WHERE name = %s LIMIT 1", $name ) );
	return $id ? (int) $id : 0;
}

/** Resolve the nine AAREP levels without assuming that database IDs are portable. */
function aarepdc_resolve_level_group_targets() {
	global $wpdb;
	$levels_table = $wpdb->prefix . 'pmpro_membership_levels';
	$primary_kinds = array( 'individual', 'individual_student', 'sponsor', 'sponsor_employee' );
	$primary_ids   = array();
	$board_ids     = array();

	foreach ( aarepdc_level_definitions() as $definition ) {
		$matches = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$levels_table} WHERE name = %s ORDER BY id", $definition['name'] ) );
		if ( 1 !== count( $matches ) ) {
			return new WP_Error( 'aarepdc_level_resolution', sprintf( 'Expected exactly one membership level named %s; found %d.', $definition['name'], count( $matches ) ) );
		}
		$level_id = (int) $matches[0];
		$kind     = isset( $definition['aarepdc_kind'] ) ? $definition['aarepdc_kind'] : '';
		if ( in_array( $kind, $primary_kinds, true ) ) {
			$primary_ids[] = $level_id;
		} elseif ( 'board' === $kind ) {
			$board_ids[] = $level_id;
		}
	}

	$primary_ids = array_values( array_unique( $primary_ids ) );
	$board_ids   = array_values( array_unique( $board_ids ) );
	if ( 8 !== count( $primary_ids ) || 1 !== count( $board_ids ) ) {
		return new WP_Error( 'aarepdc_level_topology', sprintf( 'Expected eight primary levels and one Board level; found %d and %d.', count( $primary_ids ), count( $board_ids ) ) );
	}

	return array( 'primary' => $primary_ids, 'board' => $board_ids[0] );
}

/** Read the current AAREP level-group topology without invoking PMPro's mutating group getter. */
function aarepdc_level_group_topology_state( $targets = null ) {
	global $wpdb;
	if ( null === $targets ) {
		$targets = aarepdc_resolve_level_group_targets();
	}
	if ( is_wp_error( $targets ) ) {
		return $targets;
	}

	$groups_table = $wpdb->prefix . 'pmpro_groups';
	$map_table    = $wpdb->prefix . 'pmpro_membership_levels_groups';
	foreach ( array( $groups_table, $map_table ) as $table ) {
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( $exists !== $table ) {
			return new WP_Error( 'aarepdc_group_table_missing', 'Paid Memberships Pro level-group tables are unavailable.' );
		}
	}

	$target_ids = array_merge( $targets['primary'], array( $targets['board'] ) );
	$id_sql     = implode( ',', array_map( 'intval', $target_ids ) );
	$map_rows   = $wpdb->get_results( "SELECT level, `group` FROM {$map_table} WHERE level IN ({$id_sql}) ORDER BY level, id" );
	$by_level   = array_fill_keys( $target_ids, array() );
	foreach ( (array) $map_rows as $row ) {
		$level_id = (int) $row->level;
		if ( isset( $by_level[ $level_id ] ) ) {
			$by_level[ $level_id ][] = (int) $row->group;
		}
	}

	$primary_group = 0;
	foreach ( $targets['primary'] as $level_id ) {
		if ( 1 !== count( $by_level[ $level_id ] ) ) {
			$primary_group = 0;
			break;
		}
		$mapped_group = (int) $by_level[ $level_id ][0];
		if ( ! $primary_group ) {
			$primary_group = $mapped_group;
		} elseif ( $primary_group !== $mapped_group ) {
			$primary_group = 0;
			break;
		}
	}

	$board_groups = $by_level[ $targets['board'] ];
	$board_group  = 1 === count( $board_groups ) ? (int) $board_groups[0] : 0;
	$primary_row  = $primary_group ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$groups_table} WHERE id = %d", $primary_group ) ) : null;
	$board_row    = $board_group ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$groups_table} WHERE id = %d", $board_group ) ) : null;
	$primary_members = $primary_group ? array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT level FROM {$map_table} WHERE `group` = %d ORDER BY level", $primary_group ) ) ) : array();
	$board_members   = $board_group ? array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT level FROM {$map_table} WHERE `group` = %d ORDER BY level", $board_group ) ) ) : array();
	$expected_primary = $targets['primary'];
	sort( $expected_primary );
	sort( $primary_members );
	sort( $board_members );

	$ready = $primary_group > 0
		&& $board_group > 0
		&& $primary_group !== $board_group
		&& $primary_row
		&& $board_row
		&& 0 === (int) $primary_row->allow_multiple_selections
		&& 0 === (int) $board_row->allow_multiple_selections
		&& $expected_primary === $primary_members
		&& array( (int) $targets['board'] ) === $board_members;

	return array(
		'ready'                 => $ready,
		'primary_group'         => $primary_group,
		'board_group'           => $board_group,
		'primary_group_name'    => $primary_row ? (string) $primary_row->name : '',
		'board_group_name'      => $board_row ? (string) $board_row->name : '',
		'primary_members'       => $primary_members,
		'board_members'         => $board_members,
		'primary_allow_multiple'=> $primary_row ? (int) $primary_row->allow_multiple_selections : null,
		'board_allow_multiple'  => $board_row ? (int) $board_row->allow_multiple_selections : null,
	);
}

/**
 * Keep paid/staff-managed levels in one single-selection group and Board in its own group.
 * PMPro moves orphaned levels into the first group when its Levels admin is opened, so Board
 * must be explicitly grouped elsewhere for paid + Board coexistence to remain stable.
 */
function aarepdc_reconcile_level_groups( $apply = false ) {
	global $wpdb;
	$targets = aarepdc_resolve_level_group_targets();
	if ( is_wp_error( $targets ) ) {
		return $targets;
	}
	$state = aarepdc_level_group_topology_state( $targets );
	if ( is_wp_error( $state ) || ! $apply || ! empty( $state['ready'] ) ) {
		return $state;
	}
	if ( ! function_exists( 'pmpro_create_level_group' ) || ! function_exists( 'pmpro_add_level_to_group' ) || ! function_exists( 'pmpro_get_group_id_for_level' ) ) {
		return new WP_Error( 'aarepdc_group_api_missing', 'Paid Memberships Pro level-group functions are unavailable.' );
	}

	$groups_table = $wpdb->prefix . 'pmpro_groups';
	$map_table    = $wpdb->prefix . 'pmpro_membership_levels_groups';
	$memberships_table = $wpdb->prefix . 'pmpro_memberships_users';
	$id_sql = implode( ',', array_map( 'intval', $targets['primary'] ) );
	$active_conflicts = (int) $wpdb->get_var( "SELECT COUNT(*) FROM (SELECT user_id FROM {$memberships_table} WHERE status = 'active' AND membership_id IN ({$id_sql}) GROUP BY user_id HAVING COUNT(DISTINCT membership_id) > 1) aarepdc_conflicts" );
	if ( $active_conflicts ) {
		return new WP_Error( 'aarepdc_group_membership_conflict', sprintf( '%d active user(s) hold multiple primary AAREP levels; group reconciliation stopped.', $active_conflicts ) );
	}

	$choose_safe_group = static function( $current_group, $canonical_name, $allowed_level_ids ) use ( $wpdb, $groups_table, $map_table ) {
		if ( $current_group ) {
			$members = array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT level FROM {$map_table} WHERE `group` = %d", $current_group ) ) );
			if ( ! array_diff( $members, $allowed_level_ids ) ) {
				return (int) $current_group;
			}
		}
		$named = array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$groups_table} WHERE name = %s ORDER BY id", $canonical_name ) ) );
		if ( count( $named ) > 1 ) {
			return new WP_Error( 'aarepdc_duplicate_group_name', sprintf( 'Multiple level groups are named %s.', $canonical_name ) );
		}
		if ( 1 === count( $named ) ) {
			$members = array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT level FROM {$map_table} WHERE `group` = %d", $named[0] ) ) );
			if ( array_diff( $members, $allowed_level_ids ) ) {
				return new WP_Error( 'aarepdc_unsafe_group', sprintf( 'The %s group contains unrelated levels.', $canonical_name ) );
			}
			return (int) $named[0];
		}
		return 0;
	};

	$primary_allowed = array_merge( $targets['primary'], array( $targets['board'] ) );
	$primary_group   = $choose_safe_group( $state['primary_group'], 'AAREP Membership', $primary_allowed );
	if ( is_wp_error( $primary_group ) ) {
		return $primary_group;
	}
	$board_group = $choose_safe_group( $state['board_group'] && $state['board_group'] !== $state['primary_group'] ? $state['board_group'] : 0, 'AAREP Board Access', array( $targets['board'] ) );
	if ( is_wp_error( $board_group ) ) {
		return $board_group;
	}

	$engines = $wpdb->get_results( $wpdb->prepare( "SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN (%s, %s)", $groups_table, $map_table ) );
	if ( 2 !== count( $engines ) || array_filter( $engines, static function( $row ) { return 'InnoDB' !== $row->ENGINE; } ) ) {
		return new WP_Error( 'aarepdc_group_transaction', 'Level-group tables are not both transactional InnoDB tables.' );
	}

	if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
		return new WP_Error( 'aarepdc_group_transaction_start', 'Unable to start the level-group transaction; no changes were made.' );
	}
	if ( ! $primary_group ) {
		$primary_group = (int) pmpro_create_level_group( 'AAREP Membership', false, 1 );
	}
	if ( ! $board_group ) {
		$board_group = (int) pmpro_create_level_group( 'AAREP Board Access', false, 2 );
	}
	if ( ! $primary_group || ! $board_group || $primary_group === $board_group ) {
		$wpdb->query( 'ROLLBACK' );
		return new WP_Error( 'aarepdc_group_create', 'Unable to create separate AAREP membership and Board groups.' );
	}
	if ( false === $wpdb->update( $groups_table, array( 'allow_multiple_selections' => 0 ), array( 'id' => $primary_group ), array( '%d' ), array( '%d' ) )
		|| false === $wpdb->update( $groups_table, array( 'allow_multiple_selections' => 0 ), array( 'id' => $board_group ), array( '%d' ), array( '%d' ) ) ) {
		$wpdb->query( 'ROLLBACK' );
		return new WP_Error( 'aarepdc_group_update', 'Unable to enforce single-selection AAREP level groups.' );
	}
	foreach ( $targets['primary'] as $level_id ) {
		if ( (int) pmpro_get_group_id_for_level( $level_id ) !== $primary_group ) {
			pmpro_add_level_to_group( $level_id, $primary_group );
		}
	}
	if ( (int) pmpro_get_group_id_for_level( $targets['board'] ) !== $board_group ) {
		pmpro_add_level_to_group( $targets['board'], $board_group );
	}

	$verified = aarepdc_level_group_topology_state( $targets );
	if ( is_wp_error( $verified ) || empty( $verified['ready'] ) || (int) $verified['primary_group'] !== $primary_group || (int) $verified['board_group'] !== $board_group ) {
		$wpdb->query( 'ROLLBACK' );
		return new WP_Error( 'aarepdc_group_readback', 'AAREP level-group readback failed; all group changes were rolled back.' );
	}
	if ( false === $wpdb->query( 'COMMIT' ) ) {
		$wpdb->query( 'ROLLBACK' );
		return new WP_Error( 'aarepdc_group_commit', 'Unable to commit AAREP level-group changes.' );
	}
	$verified['changed'] = true;
	return $verified;
}

/** Audit the public-signup boundary: individual levels public, sponsor/staff/Board levels private. */
function aarepdc_level_signup_visibility_state() {
	global $wpdb;
	$levels_table = $wpdb->prefix . 'pmpro_membership_levels';
	$expected     = array();
	$public_ids   = array();
	$private_ids  = array();

	foreach ( aarepdc_level_definitions() as $definition ) {
		$matches = $wpdb->get_results( $wpdb->prepare( "SELECT id, allow_signups FROM {$levels_table} WHERE name = %s ORDER BY id", $definition['name'] ) );
		if ( 1 !== count( $matches ) ) {
			return new WP_Error( 'aarepdc_signup_level_resolution', sprintf( 'Expected exactly one membership level named %s; found %d.', $definition['name'], count( $matches ) ) );
		}
		$level_id = (int) $matches[0]->id;
		$kind     = isset( $definition['aarepdc_kind'] ) ? $definition['aarepdc_kind'] : '';
		$is_public = in_array( $kind, array( 'individual', 'individual_student' ), true ) ? 1 : 0;
		$expected[ $level_id ] = $is_public;
		if ( $is_public ) {
			$public_ids[] = $level_id;
		} else {
			$private_ids[] = $level_id;
		}
	}

	if ( 3 !== count( $public_ids ) || 6 !== count( $private_ids ) ) {
		return new WP_Error( 'aarepdc_signup_level_count', 'Expected three public individual levels and six private sponsor/staff/Board levels.' );
	}
	$drift = array();
	foreach ( $expected as $level_id => $allow_signups ) {
		$current = (int) $wpdb->get_var( $wpdb->prepare( "SELECT allow_signups FROM {$levels_table} WHERE id = %d", $level_id ) );
		if ( $current !== $allow_signups ) {
			$drift[] = (int) $level_id;
		}
	}

	return array(
		'ready'       => empty( $drift ),
		'expected'    => $expected,
		'public_ids'  => $public_ids,
		'private_ids' => $private_ids,
		'drift_ids'   => $drift,
	);
}

/** Reconcile only allow_signups; prices, membership rows, groups, and provider data are untouched. */
function aarepdc_reconcile_signup_visibility( $apply = false ) {
	global $wpdb;
	$state = aarepdc_level_signup_visibility_state();
	if ( is_wp_error( $state ) || ! $apply || ! empty( $state['ready'] ) ) {
		return $state;
	}

	$levels_table = $wpdb->prefix . 'pmpro_membership_levels';
	$engine = $wpdb->get_var( $wpdb->prepare( 'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s', $levels_table ) );
	if ( 'InnoDB' !== $engine ) {
		return new WP_Error( 'aarepdc_signup_transaction', 'The membership-level table is not transactional InnoDB; no changes were made.' );
	}
	if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
		return new WP_Error( 'aarepdc_signup_transaction_start', 'Unable to start the signup-visibility transaction; no changes were made.' );
	}
	foreach ( $state['expected'] as $level_id => $allow_signups ) {
		if ( false === $wpdb->update( $levels_table, array( 'allow_signups' => $allow_signups ), array( 'id' => $level_id ), array( '%d' ), array( '%d' ) ) ) {
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'aarepdc_signup_update', 'Unable to reconcile membership signup visibility; all changes were rolled back.' );
		}
	}
	$verified = aarepdc_level_signup_visibility_state();
	if ( is_wp_error( $verified ) || empty( $verified['ready'] ) ) {
		$wpdb->query( 'ROLLBACK' );
		return new WP_Error( 'aarepdc_signup_readback', 'Signup-visibility readback failed; all changes were rolled back.' );
	}
	if ( false === $wpdb->query( 'COMMIT' ) ) {
		$wpdb->query( 'ROLLBACK' );
		return new WP_Error( 'aarepdc_signup_commit', 'Unable to commit signup visibility; all changes were rolled back.' );
	}
	$verified['changed'] = true;
	return $verified;
}

/**
	 * The seven paid AAREP levels. Public individual and private sponsor levels are both included;
	 * Board and Sponsor Employee are deliberately excluded.
 * Resolve by the checked-in names instead of assuming that database IDs are portable.
 */
function aarepdc_paid_level_ids() {
	static $ids = null;
	if ( null !== $ids ) {
		return $ids;
	}
	$ids = array();
	foreach ( aarepdc_level_definitions() as $definition ) {
		$kind = isset( $definition['aarepdc_kind'] ) ? $definition['aarepdc_kind'] : '';
		if ( ! in_array( $kind, array( 'individual', 'individual_student', 'sponsor' ), true ) ) {
			continue;
		}
		$id = aarepdc_find_level_id_by_name( $definition['name'] );
		if ( $id ) {
			$ids[] = (int) $id;
		}
	}
	return array_values( array_unique( $ids ) );
}

function aarepdc_is_paid_level_id( $level_id ) {
	return in_array( (int) $level_id, aarepdc_paid_level_ids(), true );
}

/** True when a resolved checkout level contains any automatic-renewal settings. */
function aarepdc_level_is_recurring( $level ) {
	if ( ! is_object( $level ) ) {
		return false;
	}
	return (float) ( isset( $level->billing_amount ) ? $level->billing_amount : 0 ) > 0
		|| (int) ( isset( $level->cycle_number ) ? $level->cycle_number : 0 ) > 0
		|| (float) ( isset( $level->trial_amount ) ? $level->trial_amount : 0 ) > 0
		|| (int) ( isset( $level->trial_limit ) ? $level->trial_limit : 0 ) > 0;
}

/* Alexis confirmed by email on April 21, 2026 that renewals are manual. Normalize the resolved
 * checkout object at the final level-filter priority so a stale base row or discount-code override
 * cannot silently create a subscription. The explicit WP-CLI reconciliation below also corrects
 * the stored configuration; this runtime guard is defense in depth. */
add_filter( 'pmpro_checkout_level', 'aarepdc_manual_renewal_checkout_level', 100 );
function aarepdc_manual_renewal_checkout_level( $level ) {
	if ( ! is_object( $level ) || empty( $level->id ) || ! aarepdc_is_paid_level_id( $level->id ) ) {
		return $level;
	}
	$level->billing_amount = 0;
	$level->cycle_number   = 0;
	$level->cycle_period   = '';
	$level->billing_limit  = 0;
	$level->trial_amount   = 0;
	$level->trial_limit    = 0;
	return $level;
}

/* Every successful paid checkout is a one-time purchase through December 31 of that checkout
 * year. PMPro stores the additional 30-day access window internally; member-facing copy continues
 * to show the authoritative December 31 term end. */
add_filter( 'pmpro_checkout_end_date', 'aarepdc_manual_renewal_checkout_end_date', 20, 4 );
function aarepdc_manual_renewal_checkout_end_date( $enddate, $user_id, $level, $startdate ) {
	if ( ! is_object( $level ) || empty( $level->id ) || ! aarepdc_is_paid_level_id( $level->id ) ) {
		return $enddate;
	}
	$term_end = current_time( 'Y' ) . '-12-31';
	$stored   = aarepdc_enddate_from_term_end( $term_end );
	return $stored ? $stored : $enddate;
}

/* Fail closed before account/order creation if a paid checkout somehow remains recurring, if the
 * account still has a provider subscription that requires manual review, or if an active paid
 * member attempts any same-tier or cross-tier purchase before the renewal window. AAREP has not
 * confirmed a mid-term level-change policy, so no price or access rule is invented here. No
 * gateway request is made by this check. */
add_filter( 'pmpro_checkout_checks', 'aarepdc_manual_renewal_checkout_checks', 5 );
function aarepdc_manual_renewal_checkout_checks( $okay ) {
	global $pmpro_level;
	if ( ! $okay || ! is_object( $pmpro_level ) || empty( $pmpro_level->id ) || ! aarepdc_is_paid_level_id( $pmpro_level->id ) ) {
		return $okay;
	}
	if ( aarepdc_level_is_recurring( $pmpro_level ) ) {
		pmpro_setMessage( 'Checkout is temporarily unavailable because this membership is not configured for manual renewal.', 'pmpro_error', true );
		return false;
	}
	$user_id = get_current_user_id();
	if ( $user_id && aarepdc_user_has_active_subscription( $user_id ) ) {
		pmpro_setMessage( 'Please contact AAREP DC before changing a membership that still has an active payment subscription.', 'pmpro_error', true );
		return false;
	}
	if ( $user_id && aarepdc_is_sponsor_employee( $user_id ) ) {
		pmpro_setMessage( 'This staff-managed membership cannot be changed through checkout. Please contact AAREP DC.', 'pmpro_error', true );
		return false;
	}
	if ( $user_id && function_exists( 'pmpro_hasMembershipLevel' ) && ! aarepdc_in_grace_period( $user_id ) && ! aarepdc_past_grace( $user_id ) ) {
		foreach ( aarepdc_paid_level_ids() as $active_paid_id ) {
			if ( ! pmpro_hasMembershipLevel( $active_paid_id, $user_id ) ) {
				continue;
			}
			$message = (int) $active_paid_id === (int) $pmpro_level->id
				? 'This membership is already active. Renewal becomes available when the current term ends.'
				: 'Membership level changes are unavailable until the current term ends. Please contact AAREP DC if a change is needed sooner.';
			pmpro_setMessage( $message, 'pmpro_error', true );
			return false;
		}
	}
	return true;
}

/* Provider-linked subscriptions are inventory for staff review. Prevent PMPro membership changes,
 * expiry jobs, and its canonical front-end cancel flow from calling the gateway automatically.
 * Production still requires a complete provider-reference audit before launch. */
add_filter( 'pmpro_cancel_previous_subscriptions', '__return_false', PHP_INT_MAX );
add_filter( 'pmpro_cancel_should_process', '__return_false', PHP_INT_MAX );

/* One-time memberships already end at the close of their paid term. Prevent PMPro's direct cancel
 * page from immediately removing access for those members. Any legacy provider-linked subscription
 * is also left unchanged and routed to staff review rather than touching the gateway. */
add_action( 'wp', 'aarepdc_manual_membership_cancel_gate', 1 );
function aarepdc_manual_membership_cancel_gate() {
	if ( is_admin() || ! is_user_logged_in() || wp_doing_ajax() ) {
		return;
	}
	$cancel_page = (int) get_option( 'pmpro_cancel_page_id' );
	if ( ! $cancel_page || ! is_page( $cancel_page ) ) {
		return;
	}
	$user_id = get_current_user_id();
	$account_page = (int) get_option( 'pmpro_account_page_id' );
	$account_url  = $account_page ? get_permalink( $account_page ) : home_url( '/member-account/' );
	if ( aarepdc_user_has_active_subscription( $user_id ) ) {
		wp_safe_redirect( add_query_arg( 'aarepdc_subscription_review', '1', $account_url ) );
		exit;
	}
	if ( aarepdc_is_board_member( $user_id ) || aarepdc_is_sponsor_employee( $user_id ) ) {
		wp_safe_redirect( $account_url );
		exit;
	}
	if ( ! function_exists( 'pmpro_hasMembershipLevel' ) ) {
		return;
	}
	foreach ( aarepdc_paid_level_ids() as $paid_id ) {
		if ( pmpro_hasMembershipLevel( $paid_id, $user_id ) ) {
			wp_safe_redirect( add_query_arg( 'aarepdc_term_end', '1', $account_url ) );
			exit;
		}
	}
}

/* ===== WS4 - Calendar-year cost text + confirmation term notice (2026-06-16).
 * Replaces PMPro's auto "Membership expires after 1 Year." with the org's
 * calendar-year language, and shows the term on the post-checkout confirmation.
 * Level descriptions are set verbatim from aarepdc.org in aarepdc_level_definitions(). ===== */

add_filter( 'pmpro_level_cost_text', 'aarepdc_calendar_year_cost_text', 20, 2 );
function aarepdc_calendar_year_cost_text( $text, $level ) {
	if ( ! is_object( $level ) || empty( $level->name ) ) {
		return $text;
	}
	if ( 'Sponsor Employee' === $level->name ) {
		return $text; // $0 bundled level - leave default
	}
	$price = function_exists( 'pmpro_formatPrice' ) ? pmpro_formatPrice( $level->initial_payment ) : ( '$' . number_format( (float) $level->initial_payment, 2 ) );
	return $price . ' for the calendar year. Your membership runs through December 31.';
}

add_filter( 'pmpro_confirmation_message', 'aarepdc_confirmation_term_notice', 15, 2 );
function aarepdc_confirmation_term_notice( $message, $invoice ) {
	$user_id = ( is_object( $invoice ) && ! empty( $invoice->user_id ) ) ? (int) $invoice->user_id : get_current_user_id();
	if ( ! $user_id || ! function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
		return $message;
	}
	$level = pmpro_getMembershipLevelForUser( $user_id );
	if ( ! $level || 'Sponsor Employee' === $level->name ) {
		return $message;
	}
	$tz    = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'America/New_York' );
	$now   = new DateTime( 'now', $tz );
	$year  = (int) $now->format( 'Y' );
	$dec31 = 'December 31, ' . $year;
	$message .= '<div class="aarepdc-confirm-term"><p><strong>Your AAREP DC membership is active through ' . esc_html( $dec31 ) . '.</strong></p></div>';
	return $message;
}

/* ===== WS2 - 3-column pricing table for the Membership page (2026-06-16).
 * Renders the individual levels as cards with verbatim descriptions + Join
 * buttons to PMPro checkout. Replaces the stacked [pmpro_levels] join page. ===== */
add_shortcode( 'aarepdc_pricing_table', 'aarepdc_pricing_table_shortcode' );
function aarepdc_pricing_table_shortcode( $atts ) {
	$ids = aarepdc_individual_level_ids();
	if ( ! function_exists( 'pmpro_getLevel' ) || ! $ids ) {
		return '';
	}
	/* banner="0" suppresses the join prompt where the table is embedded purely as a reference
	 * table rather than as a call to action. */
	$atts = shortcode_atts( array( 'banner' => '1' ), (array) $atts, 'aarepdc_pricing_table' );

	$checkout = (int) get_option( 'pmpro_checkout_page_id' );
	$out = '';
	if ( '0' !== (string) $atts['banner'] ) {
		$out .= '<div class="aarepdc-join-banner">';
		$out .= '<p class="aarepdc-join-banner-lead">Join today and make the rest of the year count.</p>';
		$out .= '<ul class="aarepdc-join-banner-list">';
		$out .= '<li>Member pricing on AAREP DC events and programs</li>';
		$out .= '<li>Listed in the members-only Member Directory</li>';
		$out .= '<li>Member-only communications, resources and opportunities</li>';
		$out .= '</ul>';
		$out .= '<p class="aarepdc-join-banner-note">Membership runs on the calendar year and ends December 31, so joining now carries you through the full season of programming ahead.</p>';
		$out .= '</div>';
	}
	$out .= '<div class="aarepdc-pricing">';
	foreach ( $ids as $id ) {
		$l = pmpro_getLevel( $id );
		if ( ! $l ) {
			continue;
		}
		$price = function_exists( 'pmpro_formatPrice' ) ? pmpro_formatPrice( $l->initial_payment ) : ( '$' . number_format( (float) $l->initial_payment, 0 ) );
		$url   = $checkout ? add_query_arg( 'level', $id, get_permalink( $checkout ) ) : ( home_url( '/membership-checkout/?level=' . $id ) );
		$out  .= '<div class="aarepdc-plan">';
		$out  .= '<h3 class="aarepdc-plan-name">' . esc_html( $l->name ) . '</h3>';
		$out  .= '<div class="aarepdc-plan-price">' . wp_kses_post( $price ) . '<span class="aarepdc-plan-per"> / calendar year</span></div>';
		$out  .= '<div class="aarepdc-plan-desc">' . wp_kses_post( wpautop( $l->description ) ) . '</div>';
		$out  .= '<a class="aarepdc-btn aarepdc-plan-btn" href="' . esc_url( $url ) . '">Join Now</a>';
		$out  .= '</div>';
	}
	$out .= '</div>';
	return $out;
}

/* ---------- AAREP National Network ----------
 * Chapter directory for /aarep-national-network/. Chapters live here rather than in page
 * content because this theme's shortcode optimizer rewrites post content on save; add or
 * reorder a chapter by editing this list (or the aarepdc_national_network_chapters filter).
 */
function aarepdc_national_network_chapters() {
	return (array) apply_filters(
		'aarepdc_national_network_chapters',
		array(
			array( 'name' => 'AAREP LA',           'region' => 'Los Angeles, CA',          'url' => 'https://www.aarepla.org/' ),
			array( 'name' => 'AAREP Philadelphia', 'region' => 'Philadelphia, PA',         'url' => 'https://www.aarepphl.org/' ),
			array( 'name' => 'AAREP DFW',          'region' => 'Dallas-Fort Worth, TX',    'url' => 'https://aarepdfw.org/' ),
			array( 'name' => 'AAREP Chicago',      'region' => 'Chicago, IL',              'url' => 'https://aarepchicago.org/' ),
			array( 'name' => 'AAREP Bay Area',     'region' => 'San Francisco Bay Area, CA', 'url' => 'https://www.aarepba.org/' ),
			array( 'name' => 'AAREP NYC',          'region' => 'New York, NY',             'url' => 'https://aarepny.com/' ),
			array( 'name' => 'AAREP Detroit',      'region' => 'Detroit, MI',              'url' => 'https://aarepdet.org/' ),
		)
	);
}

add_shortcode( 'aarepdc_national_network', 'aarepdc_national_network_shortcode' );
function aarepdc_national_network_shortcode() {
	$cards = '';
	foreach ( aarepdc_national_network_chapters() as $chapter ) {
		$url = esc_url( isset( $chapter['url'] ) ? $chapter['url'] : '' );
		if ( '' === $url || empty( $chapter['name'] ) ) {
			continue;
		}
		$host   = preg_replace( '/^www\./', '', (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$cards .= '<li class="aarepdc-network-card">'
			. '<a href="' . $url . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr( 'Visit ' . $chapter['name'] . ' (opens in a new tab)' ) . '">'
			. '<span class="aarepdc-network-name">' . esc_html( $chapter['name'] ) . '</span>'
			. ( ! empty( $chapter['region'] ) ? '<span class="aarepdc-network-region">' . esc_html( $chapter['region'] ) . '</span>' : '' )
			. '<span class="aarepdc-network-host">' . esc_html( $host ) . '</span>'
			. '<span class="aarepdc-network-cta">Visit chapter <span aria-hidden="true">&rarr;</span></span>'
			. '</a></li>';
	}
	if ( '' === $cards ) {
		return '';
	}

	return '<section class="aarepdc-network" aria-labelledby="aarepdc-network-title">'
		. '<span class="aarepdc-network-eyebrow">A national network</span>'
		. '<h1 id="aarepdc-network-title" class="aarepdc-network-title">AAREP National Network</h1>'
		. '<p class="aarepdc-network-intro">AAREP DC is one of several African American Real Estate Professionals chapters across the country. Connect with a chapter in your city.</p>'
		. '<ul class="aarepdc-network-grid">' . $cards . '</ul>'
		. '</section>';
}

/* Sponsor logo strip placeholder (real homepage banner to be confirmed by AAREP). */
add_shortcode( 'aarepdc_sponsor_logos', 'aarepdc_sponsor_logos_shortcode' );
function aarepdc_sponsor_logos_shortcode() {
	$ids   = array( 1257, 1546, 1598, 584, 1556, 963, 1428, 1030, 1619, 1046 );
	$logos = '';
	foreach ( $ids as $id ) {
		$img = wp_get_attachment_image( $id, 'medium', false, array( 'loading' => 'lazy', 'decoding' => 'async' ) );
		if ( $img ) {
			$logos .= '<div class="aarepdc-marquee-logo">' . $img . '</div>';
		}
	}
	if ( '' === $logos ) {
		return '';
	}
	// Full-colour CSS infinite-scroll marquee. Duplicated set => seamless -50% loop.
	$out  = '<div class="aarepdc-sponsors-section">';
	$out .= '<h3 class="aarepdc-sponsors-heading">Our Corporate Sponsors</h3>';
	$out .= '<div class="aarepdc-marquee"><div class="aarepdc-marquee-track">';
	$out .= $logos . $logos;
	$out .= '</div></div></div>';
	return $out;
}

/* ===== 30-day grace period (client request, 2026-07-30) =====
 * Members must not lose access at 00:00 on Jan 1 — they get 30 days to renew.
 *
 * Implementation: the stored PMPro `enddate` is the term end PLUS the grace days, so a
 * Jan 1 – Dec 31 2026 term is stored through `2027-01-30`. PMPro therefore keeps access, page
 * restrictions and member-directory inclusion working NATIVELY right through the grace window —
 * nothing has to grant access back, because access is never lost.
 *
 * The trade-off: the raw enddate is no longer the term end, so everything that DISPLAYS or
 * DERIVES the term must use aarepdc_member_term_end(); raw conversion is only its fallback and the
 * point where a new authoritative term is stamped. ===== */

/** Length of the post-term grace window, in days. */
function aarepdc_grace_days() {
	return AAREPDC_GRACE_DAYS;
}

/** Stored PMPro enddate for an authoritative term end plus the configured grace window. */
function aarepdc_enddate_from_term_end( $term_end ) {
	$term_end = trim( (string) $term_end );
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $term_end ) ) {
		return '';
	}
	$ts = strtotime( $term_end . ' 23:59:59' );
	return $ts ? gmdate( 'Y-m-d H:i:s', $ts + ( aarepdc_grace_days() * DAY_IN_SECONDS ) ) : '';
}

/**
 * The member's TRUE membership term end, derived from the stored enddate (enddate − grace).
 *
 * @param string $enddate_str PMPro enddate ('Y-m-d H:i:s').
 * @return string 'Y-m-d' term end, or '' if there is no real end date (non-expiring).
 */
function aarepdc_term_end_from_enddate( $enddate_str ) {
	$enddate_str = trim( (string) $enddate_str );
	if ( '' === $enddate_str || 0 === strpos( $enddate_str, '0000-00-00' ) ) {
		return '';
	}
	$ts = strtotime( substr( $enddate_str, 0, 10 ) );
	if ( ! $ts ) {
		return '';
	}
	return gmdate( 'Y-m-d', $ts - ( aarepdc_grace_days() * DAY_IN_SECONDS ) );
}

/**
 * The member's authoritative term end ('Y-m-d').
 *
 * We store this in our OWN user meta because PMPro rewrites `enddate` to the moment it expires a
 * membership — so deriving the term end from `enddate` alone would slide the grace window forward
 * whenever the expiry cron ran late. The stored value wins; the derivation is only a fallback for
 * members who haven't been stamped yet.
 */
function aarepdc_member_term_end( $user_id ) {
	$stored = get_user_meta( (int) $user_id, 'aarepdc_term_end', true );
	if ( $stored && preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $stored ) ) {
		return (string) $stored;
	}
	$sum = aarepdc_member_membership_summary( $user_id );
	return aarepdc_term_end_from_enddate( $sum['enddate_str'] );
}

/* PMPro's account card normally displays its raw enddate, which includes the internal grace
 * offset. On the front end, display the same authoritative term end used by the banner, receipts,
 * and renewal copy. The separate grace banner explains that access continues after this date. */
add_filter( 'pmpro_membership_expiration_text', 'aarepdc_membership_expiration_text', 20, 4 );
function aarepdc_membership_expiration_text( $text, $level, $user, $show_time ) {
	if ( is_admin() || ! ( $user instanceof WP_User ) ) {
		return $text;
	}
	$level_id = isset( $level->ID ) ? (int) $level->ID : ( isset( $level->id ) ? (int) $level->id : 0 );
	if ( $level_id && in_array( $level_id, array( aarepdc_board_level_id(), aarepdc_sponsor_employee_level_id() ), true ) ) {
		return $text;
	}
	$term_end = aarepdc_member_term_end( $user->ID );
	$term_ts  = $term_end ? strtotime( $term_end . ' 23:59:59' ) : false;
	return $term_ts ? date_i18n( get_option( 'date_format' ), $term_ts ) : $text;
}

/** Stamp the true term end whenever a membership with an end date is granted (checkout, renewal). */
add_action( 'pmpro_after_change_membership_level', 'aarepdc_stamp_term_end', 20, 2 );
function aarepdc_stamp_term_end( $level_id, $user_id ) {
	if ( ! (int) $level_id ) {
		return; // cancellation/expiry — keep the last known term end, don't recompute from a rewritten enddate
	}
	global $wpdb;
	$enddate = $wpdb->get_var( $wpdb->prepare(
		"SELECT enddate FROM {$wpdb->prefix}pmpro_memberships_users WHERE user_id = %d AND status = 'active' ORDER BY id DESC LIMIT 1",
		(int) $user_id
	) );
	$term = aarepdc_term_end_from_enddate( $enddate );
	if ( '' !== $term ) {
		update_user_meta( (int) $user_id, 'aarepdc_term_end', $term );
	}
}

/** Timestamp at which the grace window closes. */
function aarepdc_grace_ends_ts( $user_id ) {
	$term = aarepdc_member_term_end( $user_id );
	if ( '' === $term ) {
		return 0;
	}
	return strtotime( $term . ' 23:59:59' ) + ( aarepdc_grace_days() * DAY_IN_SECONDS );
}

/** True while the member's term has ended but their grace window is still open. */
function aarepdc_in_grace_period( $user_id ) {
	$term = aarepdc_member_term_end( $user_id );
	if ( '' === $term ) {
		return false;
	}
	$now = current_time( 'timestamp' );
	return $now > strtotime( $term . ' 23:59:59' ) && $now <= aarepdc_grace_ends_ts( $user_id );
}

/**
 * True once the grace window has fully closed. Enforced by our own gates so a member is routed to
 * /renew/ immediately after the configured grace window, even if PMPro's expiry cron hasn't
 * flipped their status yet.
 */
function aarepdc_past_grace( $user_id ) {
	$ends = aarepdc_grace_ends_ts( $user_id );
	return $ends && current_time( 'timestamp' ) > $ends;
}

/** Whole days remaining in the grace window (0 when not in grace). */
function aarepdc_grace_days_remaining( $user_id ) {
	if ( ! aarepdc_in_grace_period( $user_id ) ) {
		return 0;
	}
	return max( 0, (int) ceil( ( aarepdc_grace_ends_ts( $user_id ) - current_time( 'timestamp' ) ) / DAY_IN_SECONDS ) );
}

/* Grace banner — shown to members whose term has ended but who still have access. */
add_filter( 'the_content', 'aarepdc_grace_banner', 5 );
function aarepdc_grace_banner( $content ) {
	if ( is_admin() || ! in_the_loop() || ! is_main_query() || ! is_user_logged_in() ) {
		return $content;
	}
	$account_id = (int) get_option( 'pmpro_account_page_id' );
	$dir        = get_page_by_path( 'member-directory' );
	$on_member_page = ( $account_id && is_page( $account_id ) ) || ( $dir && is_page( $dir->ID ) );
	if ( ! $on_member_page ) {
		return $content;
	}
	$uid = get_current_user_id();
	if ( aarepdc_is_board_member( $uid ) || aarepdc_is_sponsor_employee( $uid ) ) {
		return $content;
	}
	if ( ! aarepdc_in_grace_period( $uid ) ) {
		return $content;
	}
	$left = aarepdc_grace_days_remaining( $uid );
	$term = aarepdc_member_term_end( $uid );
	$when = $term ? date_i18n( 'F j, Y', strtotime( $term ) ) : 'December 31';
	$out  = '<div style="margin:0 0 22px;padding:14px 18px;background:#fff7e6;border:1px solid #f0d9a8;border-radius:8px;color:#7a5b1a;font-size:15px;line-height:1.55;">'
		. '<strong>Your membership ended ' . esc_html( $when ) . '.</strong> '
		. 'You still have full access for ' . (int) $left . ' more day' . ( 1 === (int) $left ? '' : 's' ) . ' — '
		. '<a href="' . esc_url( home_url( '/renew/' ) ) . '" style="color:#1e4480;font-weight:bold;">renew now</a> to keep it.'
		. '</div>';
	return $out . $content;
}

/* ===== WS5 - Lapsed-member renewal gating (2026-06-16).
 * When a former member's one-time calendar-year term lapses without renewal,
 * they keep the ability to log in but are routed to /renew/ and blocked from other
 * restricted pages until they renew. No denial of login. ===== */
function aarepdc_get_expired_prior_level( $user_id ) {
	$user_id = (int) $user_id;
	if ( ! $user_id ) {
		return 0;
	}
	if ( aarepdc_is_board_member( $user_id ) || aarepdc_is_sponsor_employee( $user_id ) ) {
		return 0; // Staff-managed access remains independent if an older paid record also exists.
	}
	$paid_ids = aarepdc_paid_level_ids();
	if ( ! $paid_ids ) {
		return 0;
	}
	// A Board level may coexist with an older paid membership. Only an active paid level blocks
	// the lapsed lookup; Board and Sponsor Employee are never self-renewed here.
	if ( function_exists( 'pmpro_hasMembershipLevel' ) ) {
		foreach ( $paid_ids as $paid_id ) {
			if ( pmpro_hasMembershipLevel( $paid_id, $user_id ) ) {
				return 0;
			}
		}
	}
	global $wpdb;
	$id_sql = implode( ',', array_map( 'intval', $paid_ids ) );
	$row = $wpdb->get_row( $wpdb->prepare(
		"SELECT membership_id, status FROM {$wpdb->prefix}pmpro_memberships_users WHERE user_id = %d AND membership_id IN ({$id_sql}) ORDER BY id DESC LIMIT 1",
		$user_id
	) );
	if ( ! $row || (int) $row->membership_id <= 0 ) {
		return 0;
	}
	$lapsed = array( 'expired', 'cancelled', 'admin_cancelled', 'inactive', 'error' );
	$cron_lagged = ( 'active' === $row->status && aarepdc_past_grace( $user_id ) );
	if ( ! $cron_lagged && ! in_array( $row->status, $lapsed, true ) ) {
		return 0;
	}
	return (int) $row->membership_id;
}

/** Paid level that this user may renew now: during grace, after a cron-lagged grace, or lapsed. */
function aarepdc_get_renewal_level( $user_id ) {
	$user_id = (int) $user_id;
	if ( ! $user_id || aarepdc_is_board_member( $user_id ) || aarepdc_is_sponsor_employee( $user_id ) || aarepdc_user_has_active_subscription( $user_id ) ) {
		return 0;
	}
	if ( function_exists( 'pmpro_hasMembershipLevel' ) && ( aarepdc_in_grace_period( $user_id ) || aarepdc_past_grace( $user_id ) ) ) {
		foreach ( aarepdc_paid_level_ids() as $paid_id ) {
			if ( pmpro_hasMembershipLevel( $paid_id, $user_id ) ) {
				return (int) $paid_id;
			}
		}
	}
	$expired = aarepdc_get_expired_prior_level( $user_id );
	return aarepdc_is_paid_level_id( $expired ) ? (int) $expired : 0;
}

add_filter( 'login_redirect', 'aarepdc_lifecycle_login_redirect', 99, 3 );
function aarepdc_lifecycle_login_redirect( $redirect_to, $requested, $user ) {
	if ( ! ( $user instanceof WP_User ) || is_wp_error( $user ) ) {
		return $redirect_to;
	}
	if ( user_can( $user, 'manage_options' ) ) {
		return $redirect_to;
	}
	if ( aarepdc_get_renewal_level( $user->ID ) ) {
		return home_url( '/renew/' );
	}
	if ( function_exists( 'pmpro_getMembershipLevelForUser' ) && pmpro_getMembershipLevelForUser( $user->ID ) ) {
		return home_url( '/member-account/' );
	}
	return $redirect_to;
}

add_filter( 'pmpro_login_redirect_url', 'aarepdc_lifecycle_pmpro_login_redirect', 99 );
function aarepdc_lifecycle_pmpro_login_redirect( $url ) {
	$uid = get_current_user_id();
	if ( $uid && ! user_can( $uid, 'manage_options' ) && aarepdc_get_renewal_level( $uid ) ) {
		return home_url( '/renew/' );
	}
	return $url;
}

add_action( 'template_redirect', 'aarepdc_gate_lapsed_members', 1 );
function aarepdc_gate_lapsed_members() {
	if ( is_admin() || ! is_user_logged_in() || wp_doing_ajax() ) {
		return;
	}
	if ( current_user_can( 'manage_options' ) ) {
		return;
	}
	$uid = get_current_user_id();
	if ( aarepdc_is_board_member( $uid ) || aarepdc_is_sponsor_employee( $uid ) ) {
		return;
	}
	// Past the grace window = lapsed, full stop. We enforce this ourselves rather than waiting on
	// PMPro's expiry cron, so access ends immediately after the configured grace window even if that cron lags.
	if ( ! aarepdc_get_expired_prior_level( $uid ) && ! aarepdc_past_grace( $uid ) ) {
		return; // active members, members in grace & never-members pass through
	}
	// Explicit member-only content a lapsed member must renew to reach. Robust against
	// PMPro restriction-config changes (pmpro_setMembershipLevelsForPost was removed in 3.x).
	// 'your-profile' intentionally NOT blocked (client Q 2026-07-30): former members can still
	// log in and view/update their own information — only community surfaces need renewal.
	$member_only_slugs = array( 'member-directory', 'member-profile', 'members-resources', 'my-employees', 'member-feed' );
	$blocked_ids = array();
	foreach ( $member_only_slugs as $slug ) {
		$p = get_page_by_path( $slug );
		if ( $p ) {
			$blocked_ids[] = (int) $p->ID;
		}
	}
	$qid = get_queried_object_id();
	$is_member_event = ( $qid && get_post_meta( $qid, '_aarepdc_members_only', true ) );
	if ( ( $qid && in_array( $qid, $blocked_ids, true ) ) || $is_member_event ) {
		wp_safe_redirect( home_url( '/renew/' ) );
		exit;
	}
}

add_shortcode( 'aarepdc_renew', 'aarepdc_renew_shortcode' );
function aarepdc_renew_shortcode() {
	if ( ! is_user_logged_in() ) {
		return '<p>Please <a href="' . esc_url( wp_login_url( home_url( '/renew/' ) ) ) . '">log in</a> to renew your membership.</p>';
	}
	$uid   = get_current_user_id();
	$prior = aarepdc_get_renewal_level( $uid );
	$user  = wp_get_current_user();
	if ( ! $prior ) {
		return '<div class="aarepdc-renew"><p>Your membership is active. <a href="/member-account/">Go to your account.</a></p></div>';
	}
	$level    = pmpro_getLevel( $prior );
	$checkout = (int) get_option( 'pmpro_checkout_page_id' );
	$url      = $checkout ? add_query_arg( 'level', $prior, get_permalink( $checkout ) ) : ( home_url( '/membership-checkout/?level=' . $prior ) );
	$first    = $user->first_name ? $user->first_name : $user->display_name;
	$in_grace = aarepdc_in_grace_period( $uid );
	$out  = '<div class="aarepdc-renew">';
	$out .= '<h2>Welcome back, ' . esc_html( $first ) . '!</h2>';
	if ( $in_grace ) {
		$out .= '<p>Your <strong>' . esc_html( $level ? $level->name : 'AAREP DC' ) . '</strong> term has ended, and you are in the 30-day grace period. Renew now to keep your member access without a gap.</p>';
	} else {
		$out .= '<p>Your <strong>' . esc_html( $level ? $level->name : 'AAREP DC' ) . '</strong> membership has ended. Renew now to restore full access to member events, the directory, and resources.</p>';
	}
	$out .= '<p><a class="aarepdc-btn" href="' . esc_url( $url ) . '">Renew my membership</a></p>';
	$out .= '<p class="aarepdc-renew-note">Your membership runs on the calendar year through December 31.</p>';
	$out .= '</div>';
	return $out;
}

/* ===== Jan-1 renewal email (build + guarded send + log) =====
 * Migrated members carry no Stripe subscription, so they do not auto-renew: their term ends
 * Dec 31 and they must renew manually via /renew/. On Jan 1 we email each lapsed member a
 * "renew for {year}" nudge. A legacy active provider subscription is an exception requiring
 * manual review, so those accounts are skipped rather than changed or emailed automatically.
 *
 * SAFETY: nothing is sent to anyone unless BOTH (a) option `aarepdc_renewal_send_enabled` is
 * truthy AND (b) the recipient passes the Mail Guard allowlist. With the guard total-block
 * and the option unset (the default), this renders + logs intent and sends ZERO. Real sends
 * stay gated behind an explicit CEO green-light (set the option + open the guard). */

/**
 * Build the renewal email for a member. Pure renderer — sends nothing.
 *
 * @param WP_User|int $user           The member (or 0 with $first_override for a preview).
 * @param int         $year           The membership year being renewed INTO (e.g. 2027).
 * @param string      $first_override Optional first name for previews/drafts.
 * @return array{subject:string,html:string,text:string}
 */
/* The member's live membership summary (level + term end) straight from PMPro — used to wire the
 * renewal email to their actual status. Uses the active level, or the prior level if they lapsed. */
function aarepdc_member_membership_summary( $user_id ) {
	global $wpdb;
	$user_id = (int) $user_id;
	$summary = array( 'level_name' => '', 'enddate_str' => '' );
	if ( ! $user_id || ! function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
		return $summary;
	}
	$active = pmpro_getMembershipLevelForUser( $user_id );
	$lvl_id = $active ? (int) $active->id : (int) aarepdc_get_expired_prior_level( $user_id );
	$lvl    = $lvl_id ? pmpro_getLevel( $lvl_id ) : null;
	if ( $lvl ) {
		$summary['level_name'] = $lvl->name;
	}
	$enddate = $wpdb->get_var( $wpdb->prepare(
		"SELECT enddate FROM {$wpdb->prefix}pmpro_memberships_users WHERE user_id = %d ORDER BY id DESC LIMIT 1",
		$user_id
	) );
	if ( $enddate && '0000-00-00 00:00:00' !== $enddate ) {
		$summary['enddate_str'] = $enddate;
	}
	return $summary;
}

/**
 * Shared branded AAREP DC email wrapper — blue header, white card, standard footer.
 * Reused by the renewal email, payment receipts and member-inbox notifications so every
 * outbound message looks like it came from AAREP DC.
 *
 * @param string $body_html Inner HTML (already escaped) to place inside the card.
 * @return string Complete HTML email.
 */
function aarepdc_email_shell( $body_html ) {
	$blue  = '#1e4480';
	$html  = '<div style="background:#eef1f6;margin:0;padding:24px 12px;font-family:Arial,Helvetica,sans-serif;">';
	$html .= '<table role="presentation" width="600" align="center" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:10px;overflow:hidden;border:1px solid #e2e7f0;">';
	$html .= '<tr><td style="background:' . $blue . ';padding:26px 32px;text-align:center;">';
	$html .= '<div style="color:#ffffff;font-size:25px;font-weight:bold;letter-spacing:1px;">AAREP&nbsp;<span style="color:#cdd8ec;font-weight:normal;">DC</span></div>';
	$html .= '<div style="color:#aebfdc;font-size:11px;letter-spacing:1px;margin-top:5px;">African-American real estate professionals &middot; Washington, DC</div>';
	$html .= '</td></tr>';
	$html .= '<tr><td style="padding:32px 36px 6px;">' . $body_html . '</td></tr>';
	$html .= '<tr><td style="padding:16px 36px 28px;border-top:1px solid #eef1f6;">';
	$html .= '<p style="margin:0;color:#8a91a0;font-size:12px;line-height:1.6;">Questions? Just reply, or email <a href="mailto:info@aarepdc.org" style="color:' . $blue . ';">info@aarepdc.org</a>.<br>African-American Real Estate Professionals of Washington, DC</p>';
	$html .= '</td></tr>';
	$html .= '</table></div>';
	return $html;
}

/**
 * Human-readable membership term for a member: "January 1 – December 31, 2026".
 * Uses the authoritative term end, so receipts never show the internal grace end date.
 */
function aarepdc_membership_term_label( $user_id ) {
	$term_end = aarepdc_member_term_end( $user_id );
	if ( '' === $term_end ) {
		return '';
	}
	$year = (int) substr( $term_end, 0, 4 );
	return sprintf( 'January 1 – %s, %d', date_i18n( 'F j', strtotime( $term_end ) ), $year );
}

/* Brand PMPro's outgoing member emails (receipts/invoices included) with the AAREP shell and
 * state the membership term explicitly — client request, 2026-07-21. */
add_filter( 'pmpro_email_body', 'aarepdc_brand_pmpro_email', 20, 2 );
function aarepdc_brand_pmpro_email( $body, $email = null ) {
	// Already wrapped (our own shell) — don't double-wrap.
	if ( false !== strpos( (string) $body, 'AAREP&nbsp;<span' ) ) {
		return $body;
	}
	$template = ( is_object( $email ) && ! empty( $email->template ) ) ? (string) $email->template : '';
	$user_id  = 0;
	if ( is_object( $email ) && ! empty( $email->email ) ) {
		$u = get_user_by( 'email', $email->email );
		if ( $u ) {
			$user_id = (int) $u->ID;
		}
	}
	$inner = '<div style="color:#33384a;font-size:15px;line-height:1.65;">' . $body . '</div>';

	// Receipt-style emails carry the explicit membership term.
	$is_receipt = ( false !== stripos( $template, 'checkout' ) || false !== stripos( $template, 'invoice' ) || false !== stripos( $template, 'billing' ) );
	if ( $is_receipt && $user_id ) {
		$term = aarepdc_membership_term_label( $user_id );
		if ( '' !== $term ) {
			$inner .= '<p style="margin:18px 0 0;padding:12px 16px;background:#f4f7fc;border-radius:8px;color:#1e4480;font-size:14px;line-height:1.55;">'
				. '<strong>Membership term:</strong> ' . esc_html( $term ) . '</p>';
		}
	}
	return aarepdc_email_shell( $inner );
}

function aarepdc_render_renewal_email( $user, $year = 0, $first_override = '' ) {
	if ( ! ( $user instanceof WP_User ) && $user ) {
		$user = get_userdata( (int) $user );
	}
	$user_id = ( $user instanceof WP_User ) ? (int) $user->ID : 0;

	// Real member name (first name, falling back to display name).
	$first = trim( (string) $first_override );
	if ( '' === $first && $user instanceof WP_User ) {
		$first = $user->first_name ? $user->first_name : $user->display_name;
	}
	$first = trim( (string) $first );
	if ( '' === $first ) {
		$first = 'there';
	}

	// Wire to the member's actual PMPro membership. Derive the years from their real end date so
	// the email matches their record exactly (parse the year from the string to avoid TZ drift).
	$sum      = aarepdc_member_membership_summary( $user_id );
	$term_end = aarepdc_member_term_end( $user_id );
	if ( '' !== $term_end ) {
		// Use the authoritative TERM end, so the member sees the correct client-facing date rather
		// than the later grace end date stored internally by PMPro.
		$prior_year    = (int) substr( $term_end, 0, 4 );
		$year          = $prior_year + 1;
		$ended_display = date_i18n( 'F j, Y', strtotime( $term_end ) );
	} else {
		$year          = (int) $year ?: ( (int) gmdate( 'Y' ) + 1 );
		$prior_year    = $year - 1;
		$ended_display = 'December 31, ' . $prior_year;
	}
	$level_phrase = '' !== $sum['level_name'] ? esc_html( $sum['level_name'] ) : 'AAREP DC membership';
	$level_plain  = '' !== $sum['level_name'] ? $sum['level_name'] : 'AAREP DC membership';

	$renew_url = home_url( '/renew/' );
	$blue      = '#1e4480';
	$subject   = sprintf( 'Renew your AAREP DC membership for %d', $year );

	$benefits = array(
		'The members-only <strong>directory</strong> of DC real estate professionals',
		'<strong>Member pricing</strong> on programs, events, and the gala',
		'Committee participation and year-round networking',
		'Member communications and resources',
	);
	$benefits_html = '';
	foreach ( $benefits as $b ) {
		$benefits_html .= '<li style="margin-bottom:4px;">' . $b . '</li>';
	}

	$html  = '<h1 style="margin:0 0 14px;color:' . $blue . ';font-size:22px;font-weight:bold;">Happy new year, ' . esc_html( $first ) . '</h1>';
	$html .= '<p style="margin:0 0 15px;color:#33384a;font-size:15px;line-height:1.65;">Your <strong>' . $level_phrase . '</strong> for <strong>' . $prior_year . '</strong> ended on <strong>' . esc_html( $ended_display ) . '</strong>. Our memberships run on the calendar year, so now&rsquo;s the time to renew for <strong>' . $year . '</strong> and keep your benefits without a gap.</p>';
	$html .= '<p style="margin:0 0 8px;color:#33384a;font-size:15px;line-height:1.65;">As a ' . $year . ' member you keep:</p>';
	$html .= '<ul style="margin:0 0 22px;padding-left:20px;color:#33384a;font-size:15px;line-height:1.75;">' . $benefits_html . '</ul>';
	$html .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:4px auto 20px;"><tr><td style="background:' . $blue . ';border-radius:8px;">';
	$html .= '<a href="' . esc_url( $renew_url ) . '" style="display:inline-block;padding:15px 44px;color:#ffffff;font-size:16px;font-weight:bold;text-decoration:none;">Renew for ' . $year . ' &rarr;</a>';
	$html .= '</td></tr></table>';
	$html .= '<p style="margin:0 0 20px;color:#6a7180;font-size:13px;line-height:1.55;text-align:center;">Your renewed membership stays active through <strong>December 31, ' . $year . '</strong>.</p>';
	$html  = aarepdc_email_shell( $html );

	$text  = "Happy new year, {$first}\n\n";
	$text .= "Your {$level_plain} for {$prior_year} ended on {$ended_display}. Our memberships run on the calendar year, so now's the time to renew for {$year} and keep your benefits without a gap.\n\n";
	$text .= "As a {$year} member you keep:\n";
	$text .= "- The members-only directory of DC real estate professionals\n";
	$text .= "- Member pricing on programs, events, and the gala\n";
	$text .= "- Committee participation and year-round networking\n";
	$text .= "- Member communications and resources\n\n";
	$text .= "Renew for {$year}: {$renew_url}\n\n";
	$text .= "Your renewed membership stays active through December 31, {$year}.\n\n";
	$text .= "Questions? Just reply, or email info@aarepdc.org.\n";
	$text .= "African-American Real Estate Professionals of Washington, DC\n";

	return array( 'subject' => $subject, 'html' => $html, 'text' => $text );
}

/* Read-only admin preview of the Jan-1 renewal email. It is staging-only, sends nothing, and is
 * linked only from Tools > AAREP Preview. Priority 0 runs before the lapsed-member gate. */
add_action( 'template_redirect', 'aarepdc_renewal_preview_route', 0 );
function aarepdc_renewal_preview_route() {
	if ( empty( $_GET['aarepdc_renewal_preview'] ) ) {
		return;
	}
	if ( ! function_exists( 'aarepdc_preview_request_is_staging' ) || ! aarepdc_preview_request_is_staging() || '1' !== get_option( 'aarepdc_preview_tools_enabled', '0' ) || ! current_user_can( 'manage_options' ) ) {
		status_header( 404 );
		nocache_headers();
		exit;
	}
	$member = get_user_by( 'login', 'demo_renewal' );
	$msg    = $member ? aarepdc_render_renewal_email( $member ) : aarepdc_render_renewal_email( 0, (int) gmdate( 'Y' ) + 1 );
	nocache_headers();
	header( 'Content-Type: text/html; charset=utf-8' );
	header( 'X-Robots-Tag: noindex, nofollow', true );
	echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>AAREP DC &mdash; January 1 renewal email (preview)</title></head><body style="margin:0;background:#eef1f6;">';
	echo '<div style="max-width:600px;margin:0 auto;padding:14px 16px 0;font-family:Arial,Helvetica,sans-serif;color:#6a7180;font-size:12px;line-height:1.5;">Preview &mdash; this is the email members would receive on January&nbsp;1. <strong>Nothing has been sent.</strong></div>';
	echo $msg['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted builder output
	echo '</body></html>';
	exit;
}

/* The former public renewal-email test route is retained as a disabled tombstone so previously
 * shared URLs fail closed. Renewal delivery must be tested through an explicitly authorized,
 * non-public workflow before launch. */
add_action( 'template_redirect', 'aarepdc_renewal_webtest_route', 0 );
function aarepdc_renewal_webtest_route() {
	if ( empty( $_GET['aarepdc_renewal_send'] ) ) {
		return;
	}
	status_header( 403 );
	nocache_headers();
	header( 'Content-Type: text/plain; charset=utf-8' );
	echo 'Renewal email web testing is disabled.';
	exit;
}

/** True if the member still holds a legacy active provider subscription requiring manual review. */
function aarepdc_user_has_active_subscription( $user_id ) {
	global $wpdb;
	$user_id = (int) $user_id;
	if ( ! $user_id ) {
		return false;
	}
	// PMPro 3.x canonical source: the subscriptions table.
	$subs_table = $wpdb->prefix . 'pmpro_subscriptions';
	$subs_exist = ( $subs_table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $subs_table ) ) );
	if ( $subs_exist ) {
		$n = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$subs_table} WHERE user_id = %d AND status = 'active'",
			$user_id
		) );
		if ( $n > 0 ) {
			return true;
		}
	}
	// Also inspect order-only legacy IDs that have no canonical subscription row. A cancelled
	// canonical row deliberately suppresses its matching old order so it is not treated as active.
	$orders = $wpdb->prefix . 'pmpro_membership_orders';
	if ( $subs_exist ) {
		$n2 = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$orders} o LEFT JOIN {$subs_table} s ON s.user_id = o.user_id AND s.subscription_transaction_id = o.subscription_transaction_id WHERE o.user_id = %d AND o.subscription_transaction_id <> '' AND o.status IN ('success','active') AND s.id IS NULL",
			$user_id
		) );
	} else {
		$n2 = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$orders} WHERE user_id = %d AND subscription_transaction_id <> '' AND status IN ('success','active')",
			$user_id
		) );
	}
	return $n2 > 0;
}

/**
 * Members who need a {year} renewal nudge: their latest membership term ended during {year}-1,
 * they are not admins, not Sponsor Employees (gated by their sponsor), and have no active sub.
 * Targeting by enddate (not just "expired" status) is race-proof — it works whether or not
 * PMPro's expiration cron has already flipped them, and lets us preview before Jan 1.
 *
 * @return array<int,int> map of user_id => prior membership_id
 */
function aarepdc_collect_renewal_targets( $year ) {
	global $wpdb;
	$year = (int) $year;
	// Grace-aware window. The stored enddate is term-end + grace, so a member whose Dec 31 {year-1}
	// term just ended carries an enddate later in January of {year} and is still
	// 'active'. Target that window, padded either side of the configured grace length.
	$start = sprintf( '%d-01-01 00:00:00', $year );
	$end   = gmdate( 'Y-m-d 23:59:59', strtotime( sprintf( '%d-01-01', $year ) ) + ( ( aarepdc_grace_days() + 16 ) * DAY_IN_SECONDS ) );
	$rows  = $wpdb->get_results( $wpdb->prepare(
		"SELECT mu.user_id, mu.membership_id
		   FROM {$wpdb->prefix}pmpro_memberships_users mu
		   INNER JOIN (
		       SELECT user_id, MAX(id) AS max_id
		         FROM {$wpdb->prefix}pmpro_memberships_users
		        GROUP BY user_id
		   ) latest ON latest.max_id = mu.id
		  WHERE mu.enddate IS NOT NULL
		    AND mu.enddate BETWEEN %s AND %s",
		$start,
		$end
	) );
	$targets = array();
	foreach ( (array) $rows as $r ) {
		$uid = (int) $r->user_id;
		$level_id = (int) $r->membership_id;
		if ( ! $uid || ! aarepdc_is_paid_level_id( $level_id ) || user_can( $uid, 'manage_options' ) ) {
			continue;
		}
		$lvl = pmpro_getLevel( $level_id );
		if ( $lvl && 'Sponsor Employee' === $lvl->name ) {
			continue;
		}
		if ( aarepdc_user_has_active_subscription( $uid ) ) {
			continue;
		}
		$targets[ $uid ] = $level_id;
	}
	return $targets;
}

/**
 * Build + (guarded) send + log the Jan-1 renewal batch for a year. Callable from the cron and
 * directly (wp eval) for previews. Per-user idempotency via `aarepdc_renewal_sent_{year}` meta,
 * set ONLY on a real send — so suppressed members are picked up by a later run after green-light.
 */
function aarepdc_run_renewal_batch( $year ) {
	$year     = (int) $year;
	$send_on  = (bool) get_option( 'aarepdc_renewal_send_enabled' );
	$guard_on = function_exists( 'aarepdc_mailguard_enabled' ) ? aarepdc_mailguard_enabled() : true;
	$allow    = function_exists( 'aarepdc_mailguard_allowlist' ) ? aarepdc_mailguard_allowlist() : array();
	$targets  = aarepdc_collect_renewal_targets( $year );
	$counts   = array( 'targets' => 0, 'sent' => 0, 'suppressed' => 0, 'already' => 0, 'no_email' => 0 );
	$now      = function() {
		return gmdate( 'Y-m-d H:i:s' );
	};
	$log_started = aarepdc_private_log_append( 'aarepdc-renewal-jan1.log', sprintf(
		"[%s UTC] === Jan-1 renewal batch for %d === targets=%d send_enabled=%s guard_blocking=%s\n",
		$now(), $year, count( $targets ), $send_on ? 'yes' : 'no', $guard_on ? 'yes' : 'no'
	) );
	if ( $send_on && ! $log_started ) {
		$counts['targets']    = count( $targets );
		$counts['suppressed'] = count( $targets );
		error_log( 'AAREP renewal batch suppressed: private audit-log storage is unavailable.' );
		return $counts;
	}

	foreach ( $targets as $uid => $level_id ) {
		$counts['targets']++;
		$user = get_userdata( $uid );
		if ( ! $user || ! is_email( $user->user_email ) ) {
			$counts['no_email']++;
			aarepdc_private_log_append( 'aarepdc-renewal-jan1.log', sprintf( "[%s UTC]   user #%d SKIP (no valid email)\n", $now(), $uid ) );
			continue;
		}
		if ( get_user_meta( $uid, 'aarepdc_renewal_sent_' . $year, true ) ) {
			$counts['already']++;
			continue;
		}
		$email   = strtolower( $user->user_email );
		$allowed = $guard_on ? in_array( $email, $allow, true ) : true;
		$msg     = aarepdc_render_renewal_email( $user, $year );

		if ( $send_on && $allowed ) {
			$ok = wp_mail( $user->user_email, $msg['subject'], $msg['html'], array( 'Content-Type: text/html; charset=UTF-8' ) );
			if ( $ok ) {
				update_user_meta( $uid, 'aarepdc_renewal_sent_' . $year, time() );
				$counts['sent']++;
				aarepdc_private_log_append( 'aarepdc-renewal-jan1.log', sprintf( "[%s UTC]   user #%d SENT\n", $now(), $uid ) );
			} else {
				$counts['suppressed']++;
				aarepdc_private_log_append( 'aarepdc-renewal-jan1.log', sprintf( "[%s UTC]   user #%d SEND-FAILED\n", $now(), $uid ) );
			}
		} else {
			$counts['suppressed']++;
			$reason = ! $send_on ? 'send-disabled' : 'guard-blocked';
			aarepdc_private_log_append( 'aarepdc-renewal-jan1.log', sprintf( "[%s UTC]   user #%d SUPPRESSED (%s)\n", $now(), $uid, $reason ) );
		}
	}

	aarepdc_private_log_append( 'aarepdc-renewal-jan1.log', sprintf(
		"[%s UTC] --- batch done: targets=%d sent=%d suppressed=%d already=%d no_email=%d ---\n",
		$now(), $counts['targets'], $counts['sent'], $counts['suppressed'], $counts['already'], $counts['no_email']
	) );

	return $counts;
}

/* ===== Jan-1 renewal scheduler =====
 * The renewal batch fires at 12:00 noon America/New_York on January 1 — a deliberate 12h
 * delay past midnight so PMPro's expiration cron has flipped the Dec-31 lapses first and the
 * email lands during the day, not at 12:01am. Implemented as a precise, self-rescheduling
 * single event (WP Engine / Cloudways run real system cron, so it fires within ~a minute of
 * noon ET). Robustness:
 *   - the target YEAR is passed as the cron arg, never re-derived from a possibly-late clock;
 *   - a MySQL named lock serializes the run, so overlapping cron / a manual `wp cron event
 *     run` racing the scheduled fire cannot double-send (the lock auto-releases on crash);
 *   - true idempotency is the per-user aarepdc_renewal_sent_{year} meta (set only on a real
 *     send), so a crash mid-batch resumes cleanly with no per-year "done" marker to strand it.
 * Sending is still gated by aarepdc_renewal_send_enabled + the mail guard inside
 * aarepdc_run_renewal_batch() — this controls WHEN the batch runs, not whether it sends. */

/** Next Jan-1 12:00 noon (America/New_York): array( utc_timestamp, year ). */
function aarepdc_next_jan1_noon() {
	$tz   = new DateTimeZone( 'America/New_York' );
	$now  = new DateTime( 'now', $tz );
	$year = (int) $now->format( 'Y' );
	$noon = new DateTime( sprintf( '%d-01-01 12:00:00', $year ), $tz );
	if ( $noon <= $now ) {
		$year++;
		$noon = new DateTime( sprintf( '%d-01-01 12:00:00', $year ), $tz );
	}
	return array( $noon->getTimestamp(), $year );
}

/** Ensure the next Jan-1-noon event is scheduled, carrying its year as the cron arg. */
function aarepdc_arm_renewal_event() {
	list( $ts, $year ) = aarepdc_next_jan1_noon();
	if ( ! wp_next_scheduled( 'aarepdc_jan1_renewal_event', array( $year ) ) ) {
		// Clear ONLY the legacy arg-less instance from an earlier build. wp_clear_scheduled_hook()
		// keys on the args hash, so default (empty) args match only the arg-less event — any
		// arg-bearing event for another year (e.g. an overdue prior-year fire) is preserved so it
		// can still run late. (Do NOT switch this to wp_unschedule_hook(), which wipes all years.)
		wp_clear_scheduled_hook( 'aarepdc_jan1_renewal_event' );
		wp_schedule_single_event( $ts, 'aarepdc_jan1_renewal_event', array( $year ) );
	}
}

add_action( 'init', 'aarepdc_schedule_renewal_check' );
function aarepdc_schedule_renewal_check() {
	// Retire the old recurring 'daily' check from earlier builds (replaced by the noon event).
	if ( wp_next_scheduled( 'aarepdc_daily_renewal_check' ) ) {
		wp_clear_scheduled_hook( 'aarepdc_daily_renewal_check' );
	}
	aarepdc_arm_renewal_event();
}

add_action( 'aarepdc_jan1_renewal_event', 'aarepdc_fire_jan1_renewals' );
function aarepdc_fire_jan1_renewals( $year = 0 ) {
	global $wpdb;
	$year = (int) $year;
	if ( $year < 2020 ) {
		// Legacy arg-less fire — fall back to the current ET year.
		$year = (int) ( new DateTime( 'now', new DateTimeZone( 'America/New_York' ) ) )->format( 'Y' );
	}

	// Serialize concurrent runs with a MySQL named lock. GET_LOCK returns '1' acquired,
	// '0' contended, NULL if unsupported. Bail only when definitively contended (fail-open).
	$lock = 'aarepdc_renewal_' . $year;
	$got  = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 0)', $lock ) );
	if ( '0' === (string) $got ) {
		return; // another worker is already running this year's batch
	}
	aarepdc_run_renewal_batch( $year );
	if ( '1' === (string) $got ) {
		$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
	}

	// Re-arm next January 1.
	aarepdc_arm_renewal_event();
}

/* Restrict a post/page to the given PMPro level IDs. 3.8-safe direct write -
 * pmpro_setMembershipLevelsForPost() was REMOVED in PMPro 3.x, which silently
 * left all pages public. Writes the canonical {prefix}pmpro_memberships_pages rows. */
function aarepdc_restrict_post_to_levels( $post_id, $level_ids ) {
	global $wpdb;
	$post_id = (int) $post_id;
	$t = $wpdb->prefix . 'pmpro_memberships_pages';
	$wpdb->delete( $t, array( 'page_id' => $post_id ) );
	foreach ( array_unique( array_map( 'intval', (array) $level_ids ) ) as $lid ) {
		if ( $lid > 0 ) {
			$wpdb->insert( $t, array( 'membership_id' => $lid, 'page_id' => $post_id ) );
		}
	}
}

/* ===== Member profile photo -> avatar (directory, account, comments) =====
 * If a member uploaded an aarepdc_profile_photo (PMPro file field), use it as their
 * avatar everywhere get_avatar() runs; otherwise the default Gravatar is unchanged. */
add_filter( 'get_avatar', 'aarepdc_profile_photo_avatar', 20, 6 );
function aarepdc_profile_photo_avatar( $avatar, $id_or_email, $size, $default, $alt, $args ) {
	$user_id = 0;
	if ( is_numeric( $id_or_email ) ) {
		$user_id = (int) $id_or_email;
	} elseif ( $id_or_email instanceof WP_User ) {
		$user_id = (int) $id_or_email->ID;
	} elseif ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
		$u = get_user_by( 'email', $id_or_email );
		$user_id = $u ? (int) $u->ID : 0;
	} elseif ( is_object( $id_or_email ) && ! empty( $id_or_email->user_id ) ) {
		$user_id = (int) $id_or_email->user_id;
	}
	if ( ! $user_id ) {
		return $avatar;
	}
	$photo = get_user_meta( $user_id, 'aarepdc_profile_photo', true );
	$url   = '';
	if ( is_array( $photo ) ) {
		$url = ! empty( $photo['fullurl'] ) ? $photo['fullurl'] : ( ! empty( $photo['previewurl'] ) ? $photo['previewurl'] : '' );
	} elseif ( is_string( $photo ) && '' !== $photo ) {
		$url = $photo;
	}
	if ( ! $url ) {
		return $avatar;
	}
	$s = (int) $size;
	return '<img alt="' . esc_attr( $alt ) . '" src="' . esc_url( $url ) . '" class="avatar avatar-' . $s . ' photo aarepdc-photo" height="' . $s . '" width="' . $s . '" loading="lazy" style="object-fit:cover;" />';
}

/* ===== Directory visibility + privacy (client request, 2026-07-21) =====
 * Members choose what other members see. Defaults: LinkedIn visible, email + phone hidden.
 * Hidden values are blanked at the SOURCE (pmpromd_get_display_value) so they never reach the
 * HTML at all — not merely hidden with CSS. ===== */

/** Is this contact field visible in the directory for this member? */
function aarepdc_field_visible( $user_id, $key ) {
	$defaults = array(
		'aarepdc_show_linkedin' => '1', // LinkedIn shows by default
		'aarepdc_show_email'    => '',  // email + phone are opt-in
		'aarepdc_show_phone'    => '',
	);
	if ( ! isset( $defaults[ $key ] ) ) {
		return true;
	}
	$saved = get_user_meta( (int) $user_id, $key, true );
	// No saved preference (e.g. the 105 migrated members) => fall back to the default.
	if ( '' === $saved || null === $saved ) {
		return '1' === $defaults[ $key ];
	}
	return in_array( (string) $saved, array( '1', 'yes', 'true', 'on' ), true );
}

add_filter( 'pmpromd_get_display_value', 'aarepdc_directory_respect_visibility', 10, 3 );
function aarepdc_directory_respect_visibility( $value, $element, $pu ) {
	$uid = is_object( $pu ) && ! empty( $pu->ID ) ? (int) $pu->ID : 0;
	if ( ! $uid ) {
		return $value;
	}
	$map = array(
		'user_email'           => 'aarepdc_show_email',
		'email'                => 'aarepdc_show_email',
		'aarepdc_phone_number' => 'aarepdc_show_phone',
		'aarepdc_linkedin_url' => 'aarepdc_show_linkedin',
	);
	$el = (string) $element;
	if ( isset( $map[ $el ] ) ) {
		if ( ! aarepdc_field_visible( $uid, $map[ $el ] ) ) {
			return ''; // omitted entirely — never rendered (card layout skips empty values)
		}
		// Opted in: render as a useful link, not raw text (client Q3, 2026-07-30).
		$raw = trim( wp_strip_all_tags( (string) $value ) );
		if ( '' === $raw ) {
			return '';
		}
		if ( 'aarepdc_linkedin_url' === $el ) {
			$url = preg_match( '#^https?://#i', $raw ) ? $raw : 'https://' . ltrim( $raw, '/' );
			// Only ever link to LinkedIn — anything else renders as plain text.
			$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
			if ( $host && ( 'linkedin.com' === $host || '.linkedin.com' === substr( $host, -13 ) ) ) {
				return '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener nofollow">LinkedIn profile</a>';
			}
			return esc_html( $raw );
		}
		if ( 'aarepdc_phone_number' === $el ) {
			$tel = preg_replace( '/[^0-9+]/', '', $raw );
			return $tel ? '<a href="tel:' . esc_attr( $tel ) . '">' . esc_html( $raw ) . '</a>' : esc_html( $raw );
		}
		if ( is_email( $raw ) ) { // user_email / email
			return '<a href="mailto:' . esc_attr( $raw ) . '">' . esc_html( $raw ) . '</a>';
		}
		return esc_html( $raw );
	}
	return $value;
}

/* Hard privacy gate: the directory AND individual member profiles require a signed-in member.
 * (The add-on's own profile guard only checks that the PROFILE OWNER has a level, not that the
 * VIEWER is logged in.) Also keep both out of search indexes. */
add_action( 'template_redirect', 'aarepdc_directory_privacy_gate', 1 );
function aarepdc_directory_privacy_gate() {
	if ( is_admin() ) {
		return;
	}
	$dir     = get_page_by_path( 'member-directory' );
	$feed    = get_page_by_path( 'member-feed' );
	$profile = get_page_by_path( 'member-profile' );
	$on_dir  = ( $dir && is_page( $dir->ID ) ) || ( $feed && is_page( $feed->ID ) );
	$on_prof = ( $profile && is_page( $profile->ID ) ) || isset( $_GET['pu'] ) || isset( $_GET['pmpro_member_profile'] );
	if ( ! $on_dir && ! $on_prof ) {
		return;
	}
	add_action( 'wp_head', function () {
		echo '<meta name="robots" content="noindex,nofollow" />' . "\n";
	}, 1 );
	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( home_url( '/member-login/' ) );
		exit;
	}
	// Signed in, but must be a current member (active or inside grace) to view member data.
	$uid = get_current_user_id();
	if ( user_can( $uid, 'manage_options' ) ) {
		return;
	}
	if ( aarepdc_is_board_member( $uid ) || aarepdc_is_sponsor_employee( $uid ) ) {
		return;
	}
	$active = function_exists( 'pmpro_getMembershipLevelForUser' ) && pmpro_getMembershipLevelForUser( $uid );
	if ( aarepdc_past_grace( $uid ) || ( ! $active && ! aarepdc_in_grace_period( $uid ) ) ) {
		wp_safe_redirect( home_url( '/renew/' ) );
		exit;
	}
}

/* Member-directory gate (fixes the unregistered [aarepdc_directory_for_members] whose
 * closing tag was leaking on the front end; also restricts the directory to active members). */
add_shortcode( 'aarepdc_directory_for_members', 'aarepdc_directory_for_members_shortcode' );
function aarepdc_directory_for_members_shortcode( $atts, $content = '' ) {
	$active = is_user_logged_in() && function_exists( 'pmpro_getMembershipLevelForUser' ) && pmpro_getMembershipLevelForUser( get_current_user_id() );
	if ( $active ) {
		return do_shortcode( $content );
	}
	$msg = is_user_logged_in()
		? 'The member directory is available to active members. <a href="/renew/">Renew your membership</a> to view it.'
		: 'The member directory is available to AAREP DC members only. Please <a href="/member-login/">log in</a> to view.';
	return '<p class="aarepdc-members-only">' . $msg . '</p>';
}

/* ---------- WP-CLI commands ---------- */
if ( defined( 'WP_CLI' ) && WP_CLI ) {

	class AAREPDC_Bootstrap_Command {

		/**
		 * Run all bootstrap steps in order.
		 *
		 * ## OPTIONS
		 *
		 * [--with-demo-users]
		 * : Explicitly create/update the five internal demo users.
		 *
		 * [--with-demo-event]
		 * : Explicitly create/update the demo event.
		 *
		 * ## EXAMPLES
		 *
		 *     wp aarepdc bootstrap-all
		 *     wp aarepdc bootstrap-all --with-demo-users --with-demo-event
		 */
		public function bootstrap_all( $args, $assoc ) {
			$this->bootstrap_levels( array(), array() );
			$this->bootstrap_pages( array(), array() );
			$this->bootstrap_renewal_email( array(), array() );
			if ( ! empty( $assoc['with-demo-event'] ) ) {
				$this->bootstrap_event( array(), array() );
			}
			if ( ! empty( $assoc['with-demo-users'] ) ) {
				$this->bootstrap_users( array(), array() );
			}
			WP_CLI::success( 'AAREP DC bootstrap-all complete.' );
		}

		/**
		 * Create the 7 (+ Sponsor Employee) PMP membership levels (idempotent).
		 *
		 * @subcommand bootstrap-levels
		 */
		public function bootstrap_levels( $args, $assoc ) {
			global $wpdb;
			$created = 0;
			$skipped = 0;
			foreach ( aarepdc_level_definitions() as $level ) {
				$existing_id = aarepdc_find_level_id_by_name( $level['name'] );
				if ( $existing_id ) {
					WP_CLI::log( "skip {$level['name']} (id={$existing_id})" );
					$skipped++;
					$level_id = $existing_id;
				} else {
					$kind    = $level['aarepdc_kind'];
					$bundled = isset( $level['aarepdc_bundled'] ) ? (int) $level['aarepdc_bundled'] : 0;
					unset( $level['aarepdc_kind'], $level['aarepdc_bundled'] );

					$wpdb->insert( $wpdb->prefix . 'pmpro_membership_levels', $level );
					$level_id = (int) $wpdb->insert_id;
					WP_CLI::log( "create {$level['name']} (id={$level_id}, \${$level['initial_payment']})" );
					$created++;

					if ( function_exists( 'update_pmpro_membership_level_meta' ) ) {
						update_pmpro_membership_level_meta( $level_id, 'aarepdc_kind', $kind );
						if ( $bundled ) {
							update_pmpro_membership_level_meta( $level_id, 'bundled_employee_count', $bundled );
						}
					} else {
						$wpdb->insert( $wpdb->prefix . 'pmpro_membership_levelmeta', array(
							'pmpro_membership_level_id' => $level_id,
							'meta_key'                  => 'aarepdc_kind',
							'meta_value'                => $kind,
						) );
						if ( $bundled ) {
							$wpdb->insert( $wpdb->prefix . 'pmpro_membership_levelmeta', array(
								'pmpro_membership_level_id' => $level_id,
								'meta_key'                  => 'bundled_employee_count',
								'meta_value'                => (string) $bundled,
							) );
						}
					}
				}
			}
			$groups = aarepdc_reconcile_level_groups( true );
			if ( is_wp_error( $groups ) ) {
				WP_CLI::error( 'Level-group reconciliation failed: ' . $groups->get_error_message() );
			}
			$signup_visibility = aarepdc_reconcile_signup_visibility( true );
			if ( is_wp_error( $signup_visibility ) ) {
				WP_CLI::error( 'Signup-visibility reconciliation failed: ' . $signup_visibility->get_error_message() );
			}
			WP_CLI::log( sprintf( 'groups primary=%d board=%d', (int) $groups['primary_group'], (int) $groups['board_group'] ) );
			WP_CLI::log( sprintf( 'signup visibility public=%d private=%d', count( $signup_visibility['public_ids'] ), count( $signup_visibility['private_ids'] ) ) );
			WP_CLI::success( "Levels: created {$created}, skipped {$skipped}." );
		}

		/** Audit or repair the deterministic AAREP paid/Board level-group topology. */
		public function reconcile_level_groups( $args, $assoc ) {
			$apply = ! empty( $assoc['apply'] );
			$state = aarepdc_reconcile_level_groups( $apply );
			if ( is_wp_error( $state ) ) {
				WP_CLI::error( $state->get_error_message() );
			}
			if ( empty( $state['ready'] ) ) {
				WP_CLI::error( 'AAREP level groups need reconciliation. Re-run with --apply after reviewing the database backup.' );
			}
			WP_CLI::success( sprintf(
				'AAREP level groups ready: primary=%d (%s), Board=%d (%s).',
				(int) $state['primary_group'],
				$state['primary_group_name'],
				(int) $state['board_group'],
				$state['board_group_name']
			) );
		}

		/** Audit or repair membership-only public signup visibility. */
		public function reconcile_signup_visibility( $args, $assoc ) {
			$apply = ! empty( $assoc['apply'] );
			$state = aarepdc_reconcile_signup_visibility( $apply );
			if ( is_wp_error( $state ) ) {
				WP_CLI::error( $state->get_error_message() );
			}
			if ( empty( $state['ready'] ) ) {
				WP_CLI::error( 'AAREP signup visibility needs reconciliation. Re-run with --apply after reviewing the database backup.' );
			}
			WP_CLI::success( sprintf( 'AAREP signup visibility ready: public individual levels=%d; private sponsor/staff/Board levels=%d.', count( $state['public_ids'] ), count( $state['private_ids'] ) ) );
		}

		/** Audit, enable, or disable the reversible membership-only cutover switch. */
		public function membership_cutover( $args, $assoc ) {
			$enable  = ! empty( $assoc['enable'] );
			$disable = ! empty( $assoc['disable'] );
			if ( $enable && $disable ) {
				WP_CLI::error( 'Choose either --enable or --disable, not both.' );
			}
			if ( $enable ) {
				if ( '1' !== (string) get_option( 'pmpro_email_membership_expiring_disabled', '0' )
					|| '1' !== (string) get_option( 'pmpro_email_membership_expired_disabled', '0' ) ) {
					WP_CLI::error( 'PMPro lifecycle emails must be disabled with `wp aarepdc bootstrap-renewal-email` before membership cutover can be enabled.' );
				}
				if ( ! aarepdc_member_directory_dependency_ready() ) {
					WP_CLI::error( 'PMPro Member Directory is incomplete or inactive; Directory, Profile, and Messages must be available before membership cutover can be enabled.' );
				}
				$groups = aarepdc_level_group_topology_state();
				$signup = aarepdc_level_signup_visibility_state();
				if ( is_wp_error( $groups ) || empty( $groups['ready'] ) || is_wp_error( $signup ) || empty( $signup['ready'] ) ) {
					WP_CLI::error( 'Level groups and signup visibility must pass their audits before membership cutover can be enabled.' );
				}
				$required_pages = array();
				foreach ( array( 'join', 'membership-checkout' ) as $required_page ) {
					$required_pages[ $required_page ] = get_page_by_path( $required_page );
					if ( ! $required_pages[ $required_page ] || 'publish' !== $required_pages[ $required_page ]->post_status ) {
						WP_CLI::error( sprintf( 'Required published page /%s/ is unavailable; cutover remains off.', $required_page ) );
					}
				}
				if ( (int) get_option( 'pmpro_levels_page_id' ) !== (int) $required_pages['join']->ID
					|| (int) get_option( 'pmpro_checkout_page_id' ) !== (int) $required_pages['membership-checkout']->ID ) {
					WP_CLI::error( 'PMPro Levels and Checkout page settings must point to /join/ and /membership-checkout/ before cutover can be enabled.' );
				}
				foreach ( $signup['public_ids'] as $public_level_id ) {
					$level = function_exists( 'pmpro_getLevel' ) ? pmpro_getLevel( $public_level_id ) : null;
					if ( ! $level || aarepdc_level_is_recurring( $level ) || (float) $level->initial_payment <= 0 ) {
						WP_CLI::error( 'Each public individual level must be a positive-price, one-time purchase before cutover can be enabled.' );
					}
				}
				$handler_path   = trailingslashit( get_stylesheet_directory() ) . 'inc/operation.php';
				$handler_source = is_readable( $handler_path ) ? file_get_contents( $handler_path ) : '';
				$guard_position = strpos( $handler_source, "get_option( 'aarepdc_membership_cutover_enabled', '0' )" );
				$stripe_position = strpos( $handler_source, "require_once('stripe/init.php')" );
				if ( false === $guard_position || false === $stripe_position || $guard_position > $stripe_position ) {
					WP_CLI::error( 'The legacy membership handler does not contain the pre-Stripe cutover guard; cutover remains off.' );
				}
				update_option( 'aarepdc_membership_cutover_enabled', '1', false );
			} elseif ( $disable ) {
				update_option( 'aarepdc_membership_cutover_enabled', '0', false );
			}
			$expected = $enable ? '1' : ( $disable ? '0' : null );
			$current  = (string) get_option( 'aarepdc_membership_cutover_enabled', '0' );
			if ( null !== $expected && $current !== $expected ) {
				WP_CLI::error( 'Membership cutover option readback failed.' );
			}
			WP_CLI::success( 'Membership cutover is ' . ( '1' === $current ? 'ENABLED.' : 'DISABLED.' ) );
		}

		/** Audit, enable, or disable the short membership-only cutover freeze. */
		public function membership_freeze( $args, $assoc ) {
			$enable  = ! empty( $assoc['enable'] );
			$disable = ! empty( $assoc['disable'] );
			if ( $enable && $disable ) {
				WP_CLI::error( 'Choose either --enable or --disable, not both.' );
			}
			if ( $enable ) {
				$handler_path    = trailingslashit( get_stylesheet_directory() ) . 'inc/operation.php';
				$handler_source  = is_readable( $handler_path ) ? file_get_contents( $handler_path ) : '';
				$freeze_position = strpos( $handler_source, "get_option( 'aarepdc_membership_freeze_enabled', '0' )" );
				$stripe_position = strpos( $handler_source, "require_once('stripe/init.php')" );
				if ( false === $freeze_position || false === $stripe_position || $freeze_position > $stripe_position ) {
					WP_CLI::error( 'The legacy membership handler does not contain the pre-Stripe freeze guard; membership freeze remains off.' );
				}
				update_option( 'aarepdc_membership_freeze_enabled', '1', false );
			} elseif ( $disable ) {
				update_option( 'aarepdc_membership_freeze_enabled', '0', false );
			}
			$expected = $enable ? '1' : ( $disable ? '0' : null );
			$current  = (string) get_option( 'aarepdc_membership_freeze_enabled', '0' );
			if ( null !== $expected && $current !== $expected ) {
				WP_CLI::error( 'Membership freeze option readback failed.' );
			}
			WP_CLI::success( 'Membership freeze is ' . ( '1' === $current ? 'ENABLED.' : 'DISABLED.' ) );
		}

		/**
		 * Audit or explicitly apply Alexis's manual-renewal decision to the seven paid levels.
		 * Updates only recurrence fields; prices, IDs, descriptions, groups, history, Board, and
		 * Sponsor Employee remain untouched. The command never calls a payment provider.
		 *
		 * ## OPTIONS
		 *
		 * [--apply]
		 * : Apply the one-time configuration after the local subscription preflight passes.
		 *
		 * ## EXAMPLES
		 *
		 *     wp aarepdc enforce-manual-renewal
		 *     wp aarepdc enforce-manual-renewal --apply
		 *
		 * @subcommand enforce-manual-renewal
		 */
		public function enforce_manual_renewal( $args, $assoc ) {
			global $wpdb;
			$apply = ! empty( $assoc['apply'] );
			$ids   = aarepdc_paid_level_ids();
			if ( 7 !== count( $ids ) ) {
				WP_CLI::error( 'Expected exactly seven paid AAREP levels; no changes were made.' );
			}

			$id_sql          = implode( ',', array_map( 'intval', $ids ) );
			$levels_table    = $wpdb->prefix . 'pmpro_membership_levels';
			$discount_table  = $wpdb->prefix . 'pmpro_discount_codes_levels';
			$orders_table    = $wpdb->prefix . 'pmpro_membership_orders';
			$subs_table      = $wpdb->prefix . 'pmpro_subscriptions';
			$recurring_where = "(COALESCE(billing_amount,0) <> 0 OR COALESCE(cycle_number,0) <> 0 OR COALESCE(cycle_period,'') <> '' OR COALESCE(billing_limit,0) <> 0 OR COALESCE(trial_amount,0) <> 0 OR COALESCE(trial_limit,0) <> 0)";

			$base_drift = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$levels_table} WHERE id IN ({$id_sql}) AND {$recurring_where}" );
			$discount_rows = 0;
			$discount_drift = 0;
			if ( $discount_table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $discount_table ) ) ) {
				$discount_rows  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$discount_table} WHERE level_id IN ({$id_sql})" );
				$discount_drift = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$discount_table} WHERE level_id IN ({$id_sql}) AND {$recurring_where}" );
			}

			$subscription_refs = 0;
			if ( $subs_table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $subs_table ) ) ) {
				$subscription_refs = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$subs_table} WHERE membership_level_id IN ({$id_sql})" );
			}
			$order_refs = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$orders_table} WHERE membership_id IN ({$id_sql}) AND COALESCE(subscription_transaction_id,'') <> ''" );

			WP_CLI::log( "paid_levels=7 base_recurring_rows={$base_drift} discount_rows={$discount_rows} discount_recurring_rows={$discount_drift} subscription_rows={$subscription_refs} subscription_orders={$order_refs}" );
			if ( ! $apply ) {
				WP_CLI::success( 'Manual-renewal audit complete; no changes made. Re-run with --apply to reconcile.' );
				return;
			}
			if ( $subscription_refs || $order_refs ) {
				WP_CLI::error( 'Provider-linked subscription records exist. No changes were made; review them manually first.' );
			}

			$wpdb->query( 'START TRANSACTION' );
			$set_sql = "billing_amount = 0, cycle_number = 0, cycle_period = '', billing_limit = 0, trial_amount = 0, trial_limit = 0";
			$base_updated = $wpdb->query( "UPDATE {$levels_table} SET {$set_sql} WHERE id IN ({$id_sql})" );
			if ( false === $base_updated ) {
				$wpdb->query( 'ROLLBACK' );
				WP_CLI::error( 'Could not update the paid levels; transaction rolled back.' );
			}
			$discount_updated = 0;
			if ( $discount_rows ) {
				$discount_updated = $wpdb->query( "UPDATE {$discount_table} SET {$set_sql} WHERE level_id IN ({$id_sql})" );
				if ( false === $discount_updated ) {
					$wpdb->query( 'ROLLBACK' );
					WP_CLI::error( 'Could not update the paid discount rows; transaction rolled back.' );
				}
			}

			$remaining = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$levels_table} WHERE id IN ({$id_sql}) AND {$recurring_where}" );
			if ( $discount_rows ) {
				$remaining += (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$discount_table} WHERE level_id IN ({$id_sql}) AND {$recurring_where}" );
			}
			if ( $remaining ) {
				$wpdb->query( 'ROLLBACK' );
				WP_CLI::error( 'Manual-renewal readback failed; transaction rolled back.' );
			}
			if ( false === $wpdb->query( 'COMMIT' ) ) {
				$wpdb->query( 'ROLLBACK' );
				WP_CLI::error( 'Could not commit the manual-renewal configuration.' );
			}
			WP_CLI::success( "Manual renewal enforced: base_rows={$base_updated}, discount_rows={$discount_updated}." );
		}

		/**
		 * Create the required portal pages and wire PMP page settings (idempotent).
		 *
		 * @subcommand bootstrap-pages
		 */
		public function bootstrap_pages( $args, $assoc ) {
			if ( ! current_user_can( 'edit_users' ) ) {
				WP_CLI::error( 'Run bootstrap-pages with --user=<administrator ID> so protected shortcodes are saved intact.' );
			}
			if ( ! aarepdc_member_directory_dependency_ready() ) {
				WP_CLI::error( 'PMPro Member Directory is incomplete or inactive; no portal pages were changed.' );
			}

			$pages = array(
				'member-login'      => array(
					'title'   => 'Member Login',
					'content' => "<!-- wp:shortcode -->\n[pmpro_login]\n<!-- /wp:shortcode -->",
					'pmp_key' => 'login',
				),
				'member-account'    => array(
					'title'   => 'My Account',
					'content' => "<!-- wp:shortcode -->\n[pmpro_account]\n<!-- /wp:shortcode -->",
					'pmp_key' => 'account',
				),
				'renew'             => array(
					'title'              => 'Renew Your Membership',
					'content'            => '[aarepdc_renew]',
					'pmp_key'            => null,
					'required_shortcode' => 'aarepdc_renew',
				),
				'member-directory'  => array(
					'title'              => 'Member Directory',
					'content'            => "<h1 class=\"aarepdc-directory-title\" style=\"text-align:center;color:#1e4480;font-size:36px;margin:32px 0 8px;\">Member Directory</h1>\n<p style=\"text-align:center;color:#475467;font-size:16px;margin:0 0 32px;\">Connect with fellow AAREP DC professionals.</p>\n[aarepdc_directory_for_members]\n[pmpro_member_directory show_email=\"false\" show_search=\"true\" show_map=\"false\" show_startdate=\"false\" fields=\"Sector,aarepdc_real_estate_sector;Company,aarepdc_company_name;City,aarepdc_city;Title,aarepdc_professional_level;Email,user_email;Phone,aarepdc_phone_number;LinkedIn,aarepdc_linkedin_url\"]\n[/aarepdc_directory_for_members]",
					'pmp_key'            => 'directory',
					'required_shortcode' => 'pmpro_member_directory',
				),
				'member-profile'    => array(
					'title'   => 'Member Profile',
					'content' => "<!-- wp:shortcode -->\n[pmpro_member_profile elements=\"avatar|256;Membership,membership_name;Member Since,membership_startdate\" show_search=\"false\"]\n<!-- /wp:shortcode -->",
					'pmp_key' => 'profile',
					'force_update' => true,
				),
				'members-resources' => array(
					'title'   => 'Members Resources',
					'content' => '<div class="aarepdc-resource-page"><h1 class="aarepdc-directory-title">Member Resources</h1><p class="aarepdc-resource-intro">A private space for helpful information and materials from AAREP DC.</p><section class="aarepdc-resource-empty" aria-labelledby="aarepdc-resource-empty-title"><h2 id="aarepdc-resource-empty-title">Resources are coming soon</h2><p>AAREP DC is preparing resources for this area. Check back soon, or email <a href="mailto:info@aarepdc.org">info@aarepdc.org</a> if you are looking for something specific.</p></section></div>',
					'pmp_key' => null,
				),
				'member-feed'       => array(
					'title'              => 'Member Feed',
					'content'            => "<h1 class=\"aarepdc-directory-title\" style=\"text-align:center;color:#1e4480;font-size:36px;margin:32px 0 8px;\">Member Feed</h1>\n<p style=\"text-align:center;color:#475467;font-size:16px;margin:0 0 32px;\">Deals, opportunities, ideas, and requests from your fellow AAREP DC members.</p>\n[aarepdc_member_feed]",
					'pmp_key'            => null,
					'required_shortcode' => 'aarepdc_member_feed',
				),
				'join'              => array(
					'title'   => 'Join AAREP DC',
					'content' => "<!-- wp:paragraph --><p>Join the African American Real Estate Professionals of Washington DC. Choose your individual membership tier below.</p><!-- /wp:paragraph -->\n\n<!-- wp:shortcode -->\n[aarepdc_pricing_table]\n<!-- /wp:shortcode -->\n\n<!-- wp:paragraph --><p>Looking to support AAREP DC as a corporate sponsor? <a href=\"/become-a-sponsor/\">View our sponsorship tiers &rarr;</a></p><!-- /wp:paragraph -->",
					'pmp_key' => 'levels',
					'force_update' => true,
				),
				'my-employees'      => array(
					'title'   => 'My Employees',
					'content' => "<!-- wp:shortcode -->\n[aarepdc_my_employees_stub]\n<!-- /wp:shortcode -->",
					'pmp_key' => null,
				),
				/* PMP-required system pages (audit-fix-2 2026-04-28) — without these the Select buttons on /join/ lead to 404 */
				'membership-checkout' => array(
					'title'   => 'Membership Checkout',
					'content' => "<!-- wp:shortcode -->\n[pmpro_checkout]\n<!-- /wp:shortcode -->",
					'pmp_key' => 'checkout',
				),
				'membership-confirmation' => array(
					'title'   => 'Membership Confirmation',
					'content' => "<!-- wp:shortcode -->\n[pmpro_confirmation]\n<!-- /wp:shortcode -->",
					'pmp_key' => 'confirmation',
				),
				'membership-billing' => array(
					'title'   => 'Membership Billing',
					'content' => "<!-- wp:shortcode -->\n[pmpro_billing]\n<!-- /wp:shortcode -->",
					'pmp_key' => 'billing',
				),
				'membership-cancel' => array(
					'title'   => 'Membership Cancel',
					'content' => "<!-- wp:shortcode -->\n[pmpro_cancel]\n<!-- /wp:shortcode -->",
					'pmp_key' => 'cancel',
				),
				'membership-invoice' => array(
					'title'   => 'Membership Orders',
					'content' => "<!-- wp:shortcode -->\n[pmpro_invoice]\n<!-- /wp:shortcode -->",
					'pmp_key' => 'invoice',
				),
				'your-profile' => array(
					'title'   => 'Your Profile',
					'content' => "<!-- wp:shortcode -->\n[pmpro_member_profile_edit]\n<!-- /wp:shortcode -->",
					'pmp_key' => 'member_profile_edit',
				),
			);

			$created = 0; $updated = 0; $restricted_page_ids = array();
			foreach ( $pages as $slug => $cfg ) {
				$existing = get_page_by_path( $slug );
				if ( $existing ) {
					$page_id = $existing->ID;
					if ( ! empty( $cfg['force_update'] ) ) {
						$update_result = wp_update_post( array(
							'ID'           => $page_id,
							'post_title'   => $cfg['title'],
							'post_content' => $cfg['content'],
						), true );
						if ( is_wp_error( $update_result ) || ! $update_result ) {
							$message = is_wp_error( $update_result ) ? $update_result->get_error_message() : 'unknown write failure';
							WP_CLI::error( "Could not update /{$slug}/: {$message}" );
						}
						WP_CLI::log( "force-update /{$slug}/ content (id={$page_id})" );
					} else {
						WP_CLI::log( "skip /{$slug}/ (id={$page_id})" );
					}
					$updated++;
				} else {
					$page_id = wp_insert_post( array(
						'post_title'   => $cfg['title'],
						'post_name'    => $slug,
						'post_content' => $cfg['content'],
						'post_status'  => 'publish',
						'post_type'    => 'page',
					) );
					if ( is_wp_error( $page_id ) ) {
						WP_CLI::warning( "failed /{$slug}/: " . $page_id->get_error_message() );
						continue;
					}
					WP_CLI::log( "create /{$slug}/ (id={$page_id})" );
					$created++;
				}
				if ( 'member-profile' === $slug && get_post_field( 'post_content', $page_id ) !== $cfg['content'] ) {
					WP_CLI::error( 'Member Profile content readback failed; page wiring was not completed.' );
				}
				if ( ! empty( $cfg['required_shortcode'] ) && ! has_shortcode( get_post_field( 'post_content', $page_id ), $cfg['required_shortcode'] ) ) {
					WP_CLI::error( "Required shortcode [{$cfg['required_shortcode']}] is missing from /{$slug}/; page wiring was not completed." );
				}
				if ( $cfg['pmp_key'] ) {
					$current = (int) get_option( 'pmpro_' . $cfg['pmp_key'] . '_page_id' );
					if ( $current !== (int) $page_id ) {
						update_option( 'pmpro_' . $cfg['pmp_key'] . '_page_id', (int) $page_id );
						WP_CLI::log( "  → set PMP {$cfg['pmp_key']}_page_id = {$page_id}" );
					}
				}
				if ( in_array( $slug, array( 'member-directory', 'member-profile', 'members-resources', 'member-feed' ), true ) ) {
					$restricted_page_ids[ $slug ] = (int) $page_id;
				}
			}

			$any_active = aarepdc_active_individual_or_sponsor_level_ids();
			if ( $any_active ) {
				foreach ( $restricted_page_ids as $slug => $page_id ) {
					aarepdc_restrict_post_to_levels( $page_id, $any_active );
					WP_CLI::log( "  → restricted /{$slug}/ to active levels: " . implode( ',', $any_active ) );
				}
			}

			WP_CLI::success( "Pages: created {$created}, existing {$updated}." );
		}

		/**
		 * Create one demo Tribe event (Phase 1.7) — single-event template gating handled by child theme override.
		 *
		 * @subcommand bootstrap-event
		 */
		public function bootstrap_event( $args, $assoc ) {
			if ( ! post_type_exists( 'tribe_events' ) ) {
				WP_CLI::warning( 'The Events Calendar (tribe_events) is not active. Skipping demo event.' );
				return;
			}
			$slug    = 'aarep-members-only-networking-reception-demo';
			$existing = get_page_by_path( $slug, OBJECT, 'tribe_events' );
			if ( $existing ) {
				update_post_meta( $existing->ID, '_aarepdc_demo_event', 1 );
				$event_end = (string) get_post_meta( $existing->ID, '_EventEndDate', true );
				if ( ( ! $event_end || $event_end < current_time( 'mysql' ) ) && function_exists( 'tribe_events' ) ) {
					$start_datetime = current_datetime()->modify( '+30 days' )->setTime( 18, 0 );
					$end_datetime   = $start_datetime->modify( '+2 hours' );
					try {
						$saved = tribe_events()
							->where( 'id', $existing->ID )
							->set_args( array(
								'start_date' => $start_datetime,
								'end_date'   => $end_datetime,
								'timezone'   => wp_timezone_string(),
								'all_day'    => false,
							) )
							->save();
						if ( ! empty( $saved[ $existing->ID ] ) ) {
							WP_CLI::log( "  → refreshed past demo event to {$start_datetime->format( 'Y-m-d H:i:s' )}" );
						} else {
							WP_CLI::warning( 'The Events Calendar did not refresh the past demo event date.' );
						}
					} catch ( Throwable $error ) {
						WP_CLI::warning( 'Unable to refresh the past demo event date: ' . $error->getMessage() );
					}
				}
				$any_active = aarepdc_active_individual_or_sponsor_level_ids();
				if ( $any_active ) {
					aarepdc_restrict_post_to_levels( $existing->ID, $any_active );
					WP_CLI::log( "  → refreshed event restrictions for active levels: " . implode( ',', $any_active ) );
				}
				WP_CLI::log( "skip event /{$slug}/ (id={$existing->ID})" );
				return;
			}
			$start = date( 'Y-m-d H:i:s', strtotime( '+30 days 18:00' ) );
			$end   = date( 'Y-m-d H:i:s', strtotime( '+30 days 20:00' ) );
			$event_id = wp_insert_post( array(
				'post_type'    => 'tribe_events',
				'post_title'   => 'AAREP Members-Only Networking Reception (Demo)',
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_content' => 'Members, join us for an evening of networking with fellow AAREP DC professionals. This members-only reception features a guest speaker, refreshments, and curated introductions across our chapter. (Phase 1 demo event placeholder.)',
			) );
			if ( is_wp_error( $event_id ) ) {
				WP_CLI::warning( 'event creation failed: ' . $event_id->get_error_message() );
				return;
			}
			update_post_meta( $event_id, '_EventStartDate', $start );
			update_post_meta( $event_id, '_EventEndDate', $end );
			update_post_meta( $event_id, '_EventStartDateUTC', $start );
			update_post_meta( $event_id, '_EventEndDateUTC', $end );
			update_post_meta( $event_id, '_EventAllDay', 'no' );
			update_post_meta( $event_id, '_aarepdc_members_only', 1 );
			update_post_meta( $event_id, '_aarepdc_demo_event', 1 );
			WP_CLI::log( "create event /{$slug}/ (id={$event_id}, start={$start})" );

			$any_active = aarepdc_active_individual_or_sponsor_level_ids();
			if ( $any_active ) {
				aarepdc_restrict_post_to_levels( $event_id, $any_active );
				WP_CLI::log( "  → restricted event to active levels: " . implode( ',', $any_active ) );
			}
			WP_CLI::success( 'Demo event created.' );
		}

		/**
		 * Configure renewal email controls.
		 *
		 * @subcommand bootstrap-renewal-email
		 */
		public function bootstrap_renewal_email( $args, $assoc ) {
			// These legacy options were never consumed. The guarded January reminder is
			// rendered directly by aarepdc_render_renewal_email().
			delete_option( 'aarepdc_renewal_email_subject' );
			delete_option( 'aarepdc_renewal_email_body' );
			delete_option( 'aarepdc_renewal_email_schedule_days' );
			// The custom January reminder implements the agreed manual-renewal notice. PMPro's
			// defaults use the stored grace-end date and would add conflicting late-January messages.
			update_option( 'pmpro_email_membership_expiring_disabled', '1' );
			update_option( 'pmpro_email_membership_expired_disabled', '1' );
			if ( '1' !== (string) get_option( 'pmpro_email_membership_expiring_disabled', '0' )
				|| '1' !== (string) get_option( 'pmpro_email_membership_expired_disabled', '0' ) ) {
				WP_CLI::error( 'PMPro lifecycle-email suppression did not read back; renewal email setup is incomplete.' );
			}
			WP_CLI::success( 'Duplicate PMPro expiring/expired notices are disabled; the guarded January reminder remains code-defined. Mail-logger captures any custom send for review.' );
		}

		/**
		 * Create 5 demo users (Phase 1.9). Idempotent by username.
		 *
		 * @subcommand bootstrap-users
		 */
		public function bootstrap_users( $args, $assoc ) {
			$log_path = aarepdc_private_storage_path( 'aarepdc-demo-credentials.txt' );
			if ( '' === $log_path ) {
				WP_CLI::error( 'Private credential storage is unavailable; no users were changed.' );
			}
			$creds_for_log = array();
			$users = array(
				array(
					'login'  => 'demo_general',
					'email'  => 'demo_general@aarep-demo.test',
					'first'  => 'Riley',
					'last'   => 'Henderson',
					'level'  => 'General Membership',
					'meta'   => array(
						'aarepdc_company_name'           => 'Henderson Realty Advisors',
						'aarepdc_real_estate_sector'     => 'Brokerage',
						'aarepdc_professional_level'     => 'Mid-Senior Level',
						'aarepdc_phone_number'           => '202-555-0142',
						'aarepdc_phone_type'             => 'Mobile',
						'aarepdc_address1'               => '1100 Connecticut Ave NW',
						'aarepdc_city'                   => 'Washington',
						'aarepdc_state'                  => 'DC',
						'aarepdc_postal_code'            => '20036',
						'aarepdc_country'                => 'United States',
						'aarepdc_experience_real_estate' => '12',
						'aarepdc_committee_preference'   => array( 'Programming', 'Gala' ),
					),
				),
				array(
					'login'  => 'demo_government',
					'email'  => 'demo_government@aarep-demo.test',
					'first'  => 'Morgan',
					'last'   => 'Adelaide',
					'level'  => 'Government / Non-Profit',
					'meta'   => array(
						'aarepdc_company_name'           => 'DC Department of Housing & Community Development',
						'aarepdc_real_estate_sector'     => 'Government',
						'aarepdc_professional_level'     => 'Executive',
						'aarepdc_phone_number'           => '202-555-0167',
						'aarepdc_phone_type'             => 'Work',
						'aarepdc_address1'               => '1800 Martin Luther King Jr Ave SE',
						'aarepdc_city'                   => 'Washington',
						'aarepdc_state'                  => 'DC',
						'aarepdc_postal_code'            => '20020',
						'aarepdc_country'                => 'United States',
						'aarepdc_experience_real_estate' => '8',
						'aarepdc_committee_preference'   => array( 'Legislative / Advocacy', 'Community Engagement' ),
					),
				),
				array(
					'login'  => 'demo_student',
					'email'  => 'demo_student@aarep-demo.test',
					'first'  => 'Avery',
					'last'   => 'Tomlinson',
					'level'  => 'Young Professional / Student',
					'meta'   => array(
						'aarepdc_company_name'           => '',
						'aarepdc_real_estate_sector'     => 'Finance',
						'aarepdc_professional_level'     => 'Entry Level',
						'aarepdc_phone_number'           => '202-555-0188',
						'aarepdc_phone_type'             => 'Mobile',
						'aarepdc_address1'               => '37th & O Streets NW',
						'aarepdc_city'                   => 'Washington',
						'aarepdc_state'                  => 'DC',
						'aarepdc_postal_code'            => '20057',
						'aarepdc_country'                => 'United States',
						'aarepdc_experience_real_estate' => '1',
						'aarepdc_committee_preference'   => array( 'Young Professionals' ),
						'aarepdc_college_university'     => 'Georgetown University, McDonough School of Business',
						'aarepdc_date_education'         => '2024 - 2026',
						'aarepdc_degree_expected'        => 'MBA in Real Estate Finance',
						'aarepdc_credits_completed'      => '38 / 60',
						'aarepdc_relevant_experience'    => 'Summer associate at a regional commercial brokerage; capstone project on affordable housing finance in the DC market.',
					),
				),
				array(
					'login'  => 'demo_bronze',
					'email'  => 'demo_bronze@aarep-demo.test',
					'first'  => 'Casey',
					'last'   => 'Whitfield',
					'level'  => 'Bronze Sponsor',
					'meta'   => array(
						'aarepdc_company_name'           => 'Whitfield Capital Partners',
						'aarepdc_real_estate_sector'     => 'Capital Markets',
						'aarepdc_professional_level'     => 'CEO/Founder',
						'aarepdc_phone_number'           => '202-555-0124',
						'aarepdc_phone_type'             => 'Work',
						'aarepdc_address1'               => '1750 K Street NW Suite 800',
						'aarepdc_city'                   => 'Washington',
						'aarepdc_state'                  => 'DC',
						'aarepdc_postal_code'            => '20006',
						'aarepdc_country'                => 'United States',
						'aarepdc_experience_real_estate' => '22',
						'aarepdc_committee_preference'   => array( 'Membership and Sponsorships', 'Gala' ),
						'_company_name'                  => 'Whitfield Capital Partners',
						'_company_website'               => 'https://example.com/whitfield',
						'_company_tagline'               => 'Mid-cap CRE debt and equity in the DMV.',
					),
				),
				array(
					'login'         => 'demo_sponsor_employee',
					'email'         => 'demo_sponsor_employee@aarep-demo.test',
					'first'         => 'Jordan',
					'last'          => 'Patel',
					'level'         => 'Sponsor Employee',
					'parent_login'  => 'demo_bronze',
					'meta'          => array(
						'aarepdc_company_name'           => 'Whitfield Capital Partners',
						'aarepdc_real_estate_sector'     => 'Capital Markets',
						'aarepdc_professional_level'     => 'Associate',
						'aarepdc_phone_number'           => '202-555-0193',
						'aarepdc_phone_type'             => 'Work',
						'aarepdc_address1'               => '1750 K Street NW Suite 800',
						'aarepdc_city'                   => 'Washington',
						'aarepdc_state'                  => 'DC',
						'aarepdc_postal_code'            => '20006',
						'aarepdc_country'                => 'United States',
						'aarepdc_experience_real_estate' => '4',
						'aarepdc_committee_preference'   => array( 'Programming' ),
					),
				),
			);

			foreach ( $users as $u ) {
				$existing = get_user_by( 'login', $u['login'] );
				if ( $existing ) {
					WP_CLI::log( "skip user {$u['login']} (id={$existing->ID})" );
					$user_id = $existing->ID;
					$pwd     = '(unchanged — existing user)';
				} else {
					$pwd     = wp_generate_password( 14, false );
					$user_id = wp_insert_user( array(
						'user_login' => $u['login'],
						'user_pass'  => $pwd,
						'user_email' => $u['email'],
						'first_name' => $u['first'],
						'last_name'  => $u['last'],
						'display_name' => $u['first'] . ' ' . $u['last'],
						'role'       => 'subscriber',
					) );
					if ( is_wp_error( $user_id ) ) {
						WP_CLI::warning( "failed user {$u['login']}: " . $user_id->get_error_message() );
						continue;
					}
					WP_CLI::log( "create user {$u['login']} (id={$user_id})" );
				}

				foreach ( $u['meta'] as $k => $v ) {
					update_user_meta( $user_id, $k, $v );
				}

				$level_id = aarepdc_find_level_id_by_name( $u['level'] );
				if ( $level_id && function_exists( 'pmpro_changeMembershipLevel' ) ) {
					pmpro_changeMembershipLevel( $level_id, $user_id );
					WP_CLI::log( "  → assigned level '{$u['level']}' (id={$level_id})" );
				}

				if ( ! empty( $u['parent_login'] ) ) {
					$parent = get_user_by( 'login', $u['parent_login'] );
					if ( $parent ) {
						update_user_meta( $user_id, 'aarepdc_parent_sponsor_user_id', (int) $parent->ID );
						WP_CLI::log( "  → linked to parent sponsor {$u['parent_login']} (id={$parent->ID})" );
					}
				}

				$creds_for_log[] = sprintf( "%s | %s | %s | %s", $u['login'], $u['email'], $pwd, $u['level'] );
			}

			$log_body = "AAREP DC — Phase 1 Demo Credentials\n"
			          . "Generated: " . gmdate( 'Y-m-d H:i:s' ) . " UTC\n"
			          . "username | email | password | level\n"
			          . "------------------------------------------------------------\n"
			          . implode( "\n", $creds_for_log )
			          . "\n";
			if ( false === file_put_contents( $log_path, $log_body, LOCK_EX ) ) {
				WP_CLI::error( 'Unable to write the private credential file.' );
			}
			@chmod( $log_path, 0600 );
			WP_CLI::success( 'Users complete. Credentials written to: ' . $log_path );
		}
	}

	WP_CLI::add_command( 'aarepdc bootstrap-all', array( 'AAREPDC_Bootstrap_Command', 'bootstrap_all' ) );
	WP_CLI::add_command( 'aarepdc bootstrap-levels', array( 'AAREPDC_Bootstrap_Command', 'bootstrap_levels' ) );
	WP_CLI::add_command( 'aarepdc reconcile-level-groups', array( 'AAREPDC_Bootstrap_Command', 'reconcile_level_groups' ) );
	WP_CLI::add_command( 'aarepdc reconcile-signup-visibility', array( 'AAREPDC_Bootstrap_Command', 'reconcile_signup_visibility' ) );
	WP_CLI::add_command( 'aarepdc membership-cutover', array( 'AAREPDC_Bootstrap_Command', 'membership_cutover' ) );
	WP_CLI::add_command( 'aarepdc membership-freeze', array( 'AAREPDC_Bootstrap_Command', 'membership_freeze' ) );
	WP_CLI::add_command( 'aarepdc enforce-manual-renewal', array( 'AAREPDC_Bootstrap_Command', 'enforce_manual_renewal' ) );
	WP_CLI::add_command( 'aarepdc bootstrap-pages', array( 'AAREPDC_Bootstrap_Command', 'bootstrap_pages' ) );
	WP_CLI::add_command( 'aarepdc bootstrap-event', array( 'AAREPDC_Bootstrap_Command', 'bootstrap_event' ) );
	WP_CLI::add_command( 'aarepdc bootstrap-renewal-email', array( 'AAREPDC_Bootstrap_Command', 'bootstrap_renewal_email' ) );
	WP_CLI::add_command( 'aarepdc bootstrap-users', array( 'AAREPDC_Bootstrap_Command', 'bootstrap_users' ) );
}

function aarepdc_active_individual_or_sponsor_level_ids() {
	$targets = aarepdc_resolve_level_group_targets();
	if ( is_wp_error( $targets ) ) {
		return array();
	}
	// Public signup visibility is intentionally independent from existing account access:
	// private sponsor/staff/Board levels still retain directory, resource, event, Feed, and Message access.
	return array_values( array_unique( array_merge( $targets['primary'], array( (int) $targets['board'] ) ) ) );
}
