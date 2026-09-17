<?php
add_shortcode( 'events_page_shrt', 'events_page_shrt_sction' );

/** Render genuine upcoming events; internal QA events are excluded by the shared query. */
function events_page_shrt_sction( $atts ) {
	unset( $atts );
	ob_start();

	$events = aarepdc_upcoming_event_query( -1 );
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
		?>
		<div class="vc_row event_block H11188">
			<?php if ( $thumbnail_url ) : ?>
			<div class="vc_col-sm-4">
				<div class="event_block_image" style="background-image:url(<?php echo esc_url( $thumbnail_url ); ?>);"></div>
			</div>
			<?php endif; ?>
			<div class="<?php echo $thumbnail_url ? 'vc_col-sm-8' : 'vc_col-sm-12'; ?>">
				<div class="event_block_title"><a href="<?php echo esc_url( get_permalink( $event_id ) ); ?>"><?php echo esc_html( get_the_title( $event_id ) ); ?></a></div>
				<div class="hes_event_date" style="margin-bottom:35px;">
					<div class="event_icons_wapp"><i class="fa fa-calendar-check-o" aria-hidden="true"></i></div>
					<p class="event_big_text1"><?php echo esc_html( tribe_get_start_date( $event_id, false, 'l, F j, Y' ) ); ?></p>
					<p><?php echo esc_html( tribe_get_start_time( $event_id ) . ' - ' . tribe_get_end_time( $event_id ) ); ?></p>
				</div>
				<?php if ( tribe_has_venue( $event_id ) ) : ?>
					<div class="hes_event_location" style="margin-bottom:35px;">
						<div class="event_icons_wapp"><i class="fa fa-map-marker" aria-hidden="true"></i></div>
						<p class="event_big_text1"><?php echo wp_kses_post( $venue_details['linked_name'] ); ?></p>
						<?php $aarepdc_address = aarepdc_event_venue_address( $event_id ); ?>
						<?php if ( '' !== $aarepdc_address ) : ?>
							<p><?php echo esc_html( $aarepdc_address ); ?></p>
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
