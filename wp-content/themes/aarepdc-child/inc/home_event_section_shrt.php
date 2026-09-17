<?php
add_shortcode( 'home_event_section_shrt', 'home_event_section_shrt_section' );

/**
 * Homepage events block, intentionally empty.
 *
 * AAREP DC lists upcoming events on the Events page only. The shortcode stays registered
 * so the existing homepage row prints nothing rather than the raw shortcode, and the row
 * itself (#home_new_event_section) is hidden in aarepdc-membership.css.
 */
function home_event_section_shrt_section( $atts ) {
	unset( $atts );
	return '';
}
