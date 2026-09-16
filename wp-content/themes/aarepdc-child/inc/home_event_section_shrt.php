<?php
add_shortcode( 'home_event_section_shrt', 'home_event_section_shrt_section' );

/** Render the next genuine event on the homepage, or the Eventbrite fallback. */
function home_event_section_shrt_section( $atts ) {
	unset( $atts );
	ob_start();

	$events = aarepdc_upcoming_event_query( 1 );
	if ( ! $events->have_posts() ) {
		echo aarepdc_events_coming_soon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		wp_reset_postdata();
		return ob_get_clean();
	}

	while ( $events->have_posts() ) :
		$events->the_post();
		$event_id      = get_the_ID();
		$thumbnail_url = wp_get_attachment_image_url( get_post_thumbnail_id( $event_id ), 'full' );
		$venue_details = tribe_get_venue_details( $event_id );
		$member_fee    = get_post_meta( $event_id, 'member_price', true );
		$event_cost    = tribe_get_cost( $event_id );
		$is_restricted = (bool) get_post_meta( $event_id, '_aarepdc_members_only', true );
		?>
		<div class="hes_box vc_row">
			<div class="hes_box_left vc_col-md-6"<?php echo $thumbnail_url ? ' style="background-image:url(' . esc_url( $thumbnail_url ) . ');"' : ''; ?>></div>
			<div class="hes_box_right vc_col-md-6">
				<h1 style="margin-bottom:15px!important;padding-bottom:0!important;color:rgb(30,68,128);">Upcoming Events</h1>
				<?php if ( $is_restricted ) : ?>
					<span class="aarepdc-event-badge">Members Only</span>
				<?php endif; ?>
				<div class="hes_box_right_contnt" style="margin-bottom:35px;">
					<p class="event_big_text1"><a href="<?php echo esc_url( get_permalink( $event_id ) ); ?>"><?php echo esc_html( get_the_title( $event_id ) ); ?></a></p>
					<?php if ( $is_restricted && ! is_user_logged_in() ) : ?>
						<p>Available to current AAREP DC members.</p>
					<?php else : ?>
						<?php echo wp_kses_post( wpautop( get_the_excerpt( $event_id ) ) ); ?>
					<?php endif; ?>
				</div>

				<div class="hes_event_date" style="margin-bottom:35px;">
					<div class="event_icons_wapp"><i class="fa fa-calendar-check-o" aria-hidden="true"></i></div>
					<p class="event_big_text1"><?php echo esc_html( tribe_get_start_date( $event_id, false, 'l, F j, Y' ) ); ?></p>
					<p><?php echo esc_html( tribe_get_start_time( $event_id ) . ' - ' . tribe_get_end_time( $event_id ) ); ?></p>
				</div>

				<?php if ( tribe_has_venue( $event_id ) ) : ?>
					<div class="hes_event_location" style="margin-bottom:35px;">
						<div class="event_icons_wapp"><i class="fa fa-map-marker" aria-hidden="true"></i></div>
						<p class="event_big_text1"><?php echo wp_kses_post( $venue_details['linked_name'] ); ?></p>
						<p><?php echo esc_html( $venue_details['address'] ); ?></p>
					</div>
				<?php endif; ?>

				<?php if ( '' !== (string) $event_cost ) : ?>
					<div class="hes_event_members_info" style="margin-bottom:35px;">
						<?php if ( '' === (string) $member_fee || 0.0 === (float) $member_fee ) : ?>
							<p class="event_big_text1">Free for Members | <?php echo esc_html( $event_cost ); ?> for Non-Members</p>
						<?php else : ?>
							<p class="event_big_text1">$<?php echo esc_html( number_format_i18n( (float) $member_fee, 2 ) ); ?> for Members | <?php echo esc_html( $event_cost ); ?> for Non-Members</p>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php echo aarepdc_event_actions( $event_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
		<?php
	endwhile;

	wp_reset_postdata();
	return ob_get_clean();
}
