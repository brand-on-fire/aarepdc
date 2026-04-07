<?php
/**
 * Template for displaying eventbrite events widget style 1
 */
$event_source_url = get_permalink();
if ( $is_direct_link ) { 
	$eventbrite_event_id = get_post_meta(get_the_ID(), 'iee_event_id', true);
    $event_source_url = "https://www.eventbrite.com/e/". $eventbrite_event_id;
}
?>
<div class="iee_widget_style1 iee_widget" >
	<div class="event_details" style="height: auto;">
		<?php if( has_post_thumbnail() && $is_display_image ){
			$picture_url = get_the_post_thumbnail_url( get_the_ID(), 'thumbnail' );
			?>
			<div class="event_picture">
				<a href="<?php echo esc_url( $event_source_url ); ?>" <?php if( $is_new_window ){ _e( 'target="_blank"', 'import-eventbrite-events-pro' ); } ?> >
				<img src="<?php echo esc_url( $picture_url ); ?>" title="<?php echo esc_attr( get_the_title() ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" >
				</a>
			</div>
			<?php
		} else {
			?>
			<div class="event_date">	
				<span class="month"><?php echo esc_attr( date_i18n('M', $event_start_str ) ); ?></span>
				<span class="date"> <?php echo esc_attr( date_i18n('d', $event_start_str ) ); ?> </span>
			</div>
			<?php
		} ?>					
		
		<div class="event_desc">
			<div class="event_name">
				<a href="<?php echo esc_url( $event_source_url ); ?>" rel="bookmark" <?php if( $is_new_window ){ _e( 'target="_blank"', 'import-eventbrite-events-pro' ); } ?> >
					<?php echo esc_attr( get_the_title() ); ?>
				</a>
			</div>
			<?php 
			if( $event_date != '' ){
				?><div class="event_dates"><i class="fa fa-calendar"></i> <?php echo esc_attr( $event_date ); ?></div><?php
			}

			if( $event_address != '' && $is_display_location ){ ?>
				<div class="event_address"><i class="fa fa-map-marker"></i> <?php echo esc_attr( $event_address ); ?></div>
			<?php }	?>

			<?php
			if( $is_display_desc ){ 
				$description = get_the_content();
				if( $description != '' ){
					?>
					<p class="description" >
						<?php echo esc_attr( substr( wp_strip_all_tags($description), 0, 100 ) . '...' ); ?>
					</p>
					<?php	
				}
			}	?>
		</div>
		<div style="clear: both"></div>
	</div>
</div>
