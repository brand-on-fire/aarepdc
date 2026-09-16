<?php
/**
 * AAREP DC — Single Event template override (Phase 1.7)
 *
 * Wraps the default Tribe single-event template in a PMP membership check.
 * If the event has post meta `_aarepdc_members_only = 1` AND the visitor is
 * not a member, show a "Members only" message instead of the event content.
 *
 * Otherwise fall back to the parent theme/Tribe default rendering.
 *
 * @see https://docs.theeventscalendar.com/themes/single-event/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$tribe_ev_id     = get_the_ID();
$is_members_only = $tribe_ev_id ? (bool) get_post_meta( $tribe_ev_id, '_aarepdc_members_only', true ) : false;
$has_member      = is_user_logged_in() && function_exists( 'pmpro_hasMembershipLevel' ) && pmpro_hasMembershipLevel();
?>

<main id="main" class="site-main aarepdc-tribe-single-event">
	<?php while ( have_posts() ) : the_post(); ?>
		<?php if ( $is_members_only && ! $has_member ) : ?>
			<article class="aarepdc-event-locked" style="max-width:760px; margin:3em auto; padding:2em; border:1px solid #e0c97a; border-radius:8px; background:#fffbeb;">
				<h1 style="margin-top:0;"><?php the_title(); ?></h1>
				<p><strong>This event is for AAREP DC members only.</strong></p>
				<p>Please <a href="/member-login/">log in</a> to view event details, or <a href="/join/">join AAREP DC</a> to gain access.</p>
				<p style="font-size:.9em; color:#666;">If you believe you already have an active membership, please contact <a href="mailto:info@aarepdc.org">info@aarepdc.org</a>.</p>
			</article>
		<?php else : ?>
			<?php
			$tribe_template = tribe( 'events.templates' );
			if ( method_exists( $tribe_template, 'get_template_setting' ) ) {
				tribe_get_template_part( 'modules/meta', 'details' );
			}
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'aarepdc-event-public' ); ?>>
				<header class="aarepdc-event-header">
					<h1><?php the_title(); ?></h1>
					<?php if ( $is_members_only ) : ?>
						<p class="aarepdc-event-badge" style="display:inline-block; padding:.25em .75em; background:#b8860b; color:#fff; border-radius:4px; font-size:.85em; font-weight:600;">Members Only</p>
					<?php endif; ?>
					<?php if ( function_exists( 'tribe_events_event_schedule_details' ) ) : ?>
						<p class="aarepdc-event-when"><?php echo tribe_events_event_schedule_details( $tribe_ev_id ); ?></p>
					<?php endif; ?>
				</header>
				<div class="aarepdc-event-content">
					<?php the_content(); ?>
				</div>
			</article>
		<?php endif; ?>
	<?php endwhile; ?>
</main>

<?php
get_footer();
