<?php
/**
 * Shared display helpers for the launch-safe Eventbrite event workflow.
 *
 * The Events Calendar remains the source of event details. Registration is
 * external: staff place the event's Eventbrite URL in the standard Event
 * Website field (`_EventURL`). If that field is not ready yet, visitors are
 * sent to AAREP DC's organizer profile instead of the retired local checkout.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'AAREPDC_EVENTBRITE_ORGANIZER_URL' ) ) {
	define( 'AAREPDC_EVENTBRITE_ORGANIZER_URL', 'https://www.eventbrite.com/o/african-american-real-estate-professionals-dc-3245186396' );
}

/** Return the canonical AAREP DC Eventbrite organizer profile. */
function aarepdc_eventbrite_organizer_url() {
	return (string) apply_filters( 'aarepdc_eventbrite_organizer_url', AAREPDC_EVENTBRITE_ORGANIZER_URL );
}

/** Accept only Eventbrite HTTP(S) links for the launch registration workflow. */
function aarepdc_valid_eventbrite_url( $url ) {
	$url = esc_url_raw( trim( (string) $url ), array( 'http', 'https' ) );
	if ( '' === $url ) {
		return '';
	}

	$host              = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	$eventbrite_suffix = '.eventbrite.com';
	if ( 'eventbrite.com' !== $host && $eventbrite_suffix !== substr( $host, -strlen( $eventbrite_suffix ) ) ) {
		return '';
	}

	return $url;
}

/** Return an event-specific Eventbrite URL, or an empty string if none is ready. */
function aarepdc_eventbrite_event_url( $event_id ) {
	$event_id = (int) $event_id;
	if ( $event_id < 1 ) {
		return '';
	}

	$candidates = array(
		get_post_meta( $event_id, '_EventURL', true ),
		get_post_meta( $event_id, 'event_registration_link', true ),
	);

	if ( function_exists( 'get_field' ) ) {
		$candidates[] = get_field( 'event_registration_link', $event_id );
	}

	foreach ( array_unique( array_filter( $candidates ) ) as $candidate ) {
		$url = aarepdc_valid_eventbrite_url( $candidate );
		if ( '' !== $url ) {
			return $url;
		}
	}

	return '';
}

/**
 * Resolve the honest registration destination and label for an event.
 *
 * Staff can set `_aarepdc_registration_status` on an event:
 *   coming_soon  no link yet, shows "Registration coming soon"
 *   open         registration hosted elsewhere (a partner site), uses `_aarepdc_registration_url`
 * With no status the original Eventbrite behaviour applies unchanged.
 */
function aarepdc_event_registration_cta( $event_id ) {
	$event_id = (int) $event_id;
	$status   = (string) get_post_meta( $event_id, '_aarepdc_registration_status', true );

	if ( 'coming_soon' === $status ) {
		return array(
			'state' => 'coming_soon',
			'url'   => '',
			'label' => 'Registration coming soon',
		);
	}

	if ( 'open' === $status ) {
		$url = esc_url_raw( trim( (string) get_post_meta( $event_id, '_aarepdc_registration_url', true ) ), array( 'https', 'http' ) );
		if ( '' !== $url ) {
			return array(
				'state' => 'external',
				'url'   => $url,
				'label' => 'Register now',
			);
		}
	}

	$event_url = aarepdc_eventbrite_event_url( $event_id );
	if ( '' !== $event_url ) {
		return array(
			'state' => 'eventbrite',
			'url'   => $event_url,
			'label' => 'Register on Eventbrite',
		);
	}

	return array(
		'state' => 'eventbrite',
		'url'   => aarepdc_eventbrite_organizer_url(),
		'label' => 'View AAREP DC on Eventbrite',
	);
}

/** IDs of internal QA events that must never appear as real launch events. */
function aarepdc_demo_event_ids() {
	$ids = get_posts(
		array(
			'post_type'      => 'tribe_events',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_key'       => '_aarepdc_demo_event',
			'meta_value'     => '1',
		)
	);

	$known_demo = get_page_by_path( 'aarep-members-only-networking-reception-demo', OBJECT, 'tribe_events' );
	if ( $known_demo ) {
		$ids[] = (int) $known_demo->ID;
	}

	return array_values( array_unique( array_map( 'intval', $ids ) ) );
}

/** Build a consistent upcoming-events query for the homepage and Events page. */
function aarepdc_upcoming_event_query( $limit = -1 ) {
	return new WP_Query(
		array(
			'post_type'           => 'tribe_events',
			'post_status'         => 'publish',
			'posts_per_page'      => (int) $limit,
			'post__not_in'        => aarepdc_demo_event_ids(),
			'meta_key'            => '_EventStartDate',
			'orderby'             => 'meta_value',
			'order'               => 'ASC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'meta_query'          => array(
				array(
					'key'     => '_EventEndDate',
					'value'   => current_time( 'mysql' ),
					'compare' => '>=',
					'type'    => 'DATETIME',
				),
			),
		)
	);
}

/** Render the external registration button for an upcoming event. */
function aarepdc_eventbrite_button( $event_id, $class = 'site_button' ) {
	$cta   = aarepdc_event_registration_cta( $event_id );
	$title = get_the_title( $event_id );

	if ( 'coming_soon' === $cta['state'] ) {
		return '<span class="aarepdc-registration-soon">' . esc_html( $cta['label'] ) . '</span>';
	}

	return sprintf(
		'<a class="%1$s aarepdc-eventbrite-button" href="%2$s" target="_blank" rel="noopener noreferrer" aria-label="%3$s">%4$s <span aria-hidden="true">&rarr;</span></a>',
		esc_attr( $class ),
		esc_url( $cta['url'] ),
		esc_attr( $cta['label'] . ': ' . $title . ' (opens in a new tab)' ),
		esc_html( $cta['label'] )
	);
}

/**
 * Plain-text venue address, e.g. "1640 Columbia Road NW, Washington, DC 20009".
 *
 * tribe_get_venue_details()['address'] returns formatted markup, which printed as raw
 * <span> text when escaped. Build the line from the individual fields instead.
 */
function aarepdc_event_venue_address( $event_id ) {
	$event_id = (int) $event_id;
	if ( ! function_exists( 'tribe_get_venue_id' ) || ! tribe_has_venue( $event_id ) ) {
		return '';
	}
	$venue_id = tribe_get_venue_id( $event_id );
	$street   = trim( (string) tribe_get_address( $venue_id ) );
	$city     = trim( (string) tribe_get_city( $venue_id ) );
	$region   = trim( (string) tribe_get_stateprovince( $venue_id ) );
	$zip      = trim( (string) tribe_get_zip( $venue_id ) );
	$locality = trim( $city . ( '' !== $city && '' !== $region ? ', ' : '' ) . $region . ( '' !== $zip ? ' ' . $zip : '' ) );

	return implode( ', ', array_filter( array( $street, $locality ) ) );
}

/** Render a correctly populated Add to Calendar control for an event. */
function aarepdc_add_to_calendar_button( $event_id ) {
	$event_id       = (int) $event_id;
	$start_date     = tribe_get_start_date( $event_id, false, 'Y-m-d' );
	$end_date       = tribe_get_end_date( $event_id, false, 'Y-m-d' );
	$start_time     = tribe_get_start_date( $event_id, true, 'H:i' );
	$end_time       = tribe_get_end_date( $event_id, true, 'H:i' );
	$event_timezone = get_post_meta( $event_id, '_EventTimezone', true );
	$event_timezone = $event_timezone ? $event_timezone : wp_timezone_string();
	$event_location = trim(
		implode(
			', ',
			array_filter(
				array(
					tribe_get_venue( $event_id ),
					aarepdc_event_venue_address( $event_id ),
				)
			)
		)
	);

	return sprintf(
		'<div id="aarepdc-calendar-%1$d" class="calender_btn_css aarepdc-calendar-button"><add-to-calendar-button name="%2$s" startDate="%3$s" endDate="%4$s" startTime="%5$s" endTime="%6$s" timeZone="%7$s" location="%8$s" options="\'Apple\',\'Google\',\'iCal\',\'Outlook.com\',\'Yahoo\'" lightMode="bodyScheme"></add-to-calendar-button></div>',
		$event_id,
		esc_attr( '[Reminder] ' . get_the_title( $event_id ) ),
		esc_attr( $start_date ),
		esc_attr( $end_date ),
		esc_attr( $start_time ),
		esc_attr( $end_time ),
		esc_attr( $event_timezone ),
		esc_attr( $event_location )
	);
}

/** Render an "Event details" link when staff have set `_aarepdc_details_url`. */
function aarepdc_event_details_link( $event_id ) {
	$url = esc_url_raw( trim( (string) get_post_meta( (int) $event_id, '_aarepdc_details_url', true ) ), array( 'https', 'http' ) );
	if ( '' === $url ) {
		return '';
	}

	return sprintf(
		'<a class="aarepdc-event-details-link" href="%1$s" target="_blank" rel="noopener noreferrer" aria-label="%2$s">Event details <span aria-hidden="true">&rarr;</span></a>',
		esc_url( $url ),
		esc_attr( 'Event details: ' . get_the_title( $event_id ) . ' (opens in a new tab)' )
	);
}

/** Render the registration, details and calendar actions together. */
function aarepdc_event_actions( $event_id ) {
	$cta     = aarepdc_event_registration_cta( $event_id );
	$partner = trim( (string) get_post_meta( (int) $event_id, '_aarepdc_registration_partner', true ) );

	if ( 'coming_soon' === $cta['state'] ) {
		$note = 'Registration details will be posted here soon.';
	} elseif ( 'external' === $cta['state'] ) {
		$note = '' !== $partner ? 'Registration is hosted by ' . $partner . '.' : 'Registration is hosted by our event partner.';
	} else {
		$note = 'Registration is handled on Eventbrite.';
	}

	return '<div class="aarepdc-event-actions">'
		. '<div class="event_block_btn_wrapp">' . aarepdc_eventbrite_button( $event_id ) . '</div>'
		. aarepdc_event_details_link( $event_id )
		. aarepdc_add_to_calendar_button( $event_id )
		. '</div><p class="aarepdc-event-registration-note">' . esc_html( $note ) . '</p>';
}

/** Add the same launch actions to genuine upcoming event detail pages. */
add_action( 'tribe_events_single_event_after_the_content', 'aarepdc_single_event_actions', 8 );
function aarepdc_single_event_actions() {
	$event_id = get_the_ID();
	if ( ! $event_id || 'tribe_events' !== get_post_type( $event_id ) || in_array( $event_id, aarepdc_demo_event_ids(), true ) ) {
		return;
	}

	$event_end = (string) get_post_meta( $event_id, '_EventEndDate', true );
	if ( '' !== $event_end && $event_end < current_time( 'mysql' ) ) {
		return;
	}

	$is_members_only = (bool) get_post_meta( $event_id, '_aarepdc_members_only', true );
	$has_membership  = is_user_logged_in() && function_exists( 'pmpro_hasMembershipLevel' ) && pmpro_hasMembershipLevel();
	if ( $is_members_only && ! $has_membership ) {
		return;
	}

	echo aarepdc_event_actions( $event_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/** Render the launch fallback when there are no genuine upcoming events. */
function aarepdc_events_coming_soon() {
	return '<section class="aarepdc-events-empty" aria-labelledby="aarepdc-events-empty-title">'
		. '<span class="aarepdc-events-eyebrow">Upcoming events</span>'
		. '<h2 id="aarepdc-events-empty-title">Events coming soon</h2>'
		. '<p>We are preparing the next AAREP DC program. Follow AAREP DC on Eventbrite for the latest event and registration announcements.</p>'
		. '<a class="site_button aarepdc-eventbrite-button" href="' . esc_url( aarepdc_eventbrite_organizer_url() ) . '" target="_blank" rel="noopener noreferrer">View AAREP DC on Eventbrite <span aria-hidden="true">&rarr;</span></a>'
		. '</section>';
}

/** The retired local checkout must not remain reachable from an old bookmark.
 *
 * Default OFF. Event registration is a separate, still-live flow on production
 * (aal10_event_registration_master was written to as recently as 2026-09-12), and this
 * membership launch is membership-only. Retiring the local event checkout is a distinct
 * decision for AAREP to make once events have genuinely moved to Eventbrite, so it is
 * gated exactly like the membership cutover rather than riding along with it.
 */
add_action( 'template_redirect', 'aarepdc_redirect_legacy_event_registration', 5 );
function aarepdc_redirect_legacy_event_registration() {
	if ( is_admin() || ! is_page( array( 'events-registration', 'event-registration' ) ) ) {
		return;
	}
	if ( '1' !== (string) get_option( 'aarepdc_retire_local_event_checkout', '0' ) ) {
		return;
	}

	wp_safe_redirect( home_url( '/whats-happening/#events_page_row' ), 302 );
	exit;
}
