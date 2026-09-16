<?php
/**
 * Plugin Name: AAREP DC — Member Feed
 * Description: A members-only community feed where members post deals, opportunities, ideas, requests, and updates. Includes an approval queue (default ON): new posts are held as Pending until AAREP staff approve them in wp-admin. Built 2026-07-30 per client request (Alexis Pannell, AAREP DC).
 * Version: 1.0.0
 * Author: Brand on Fire
 *
 * Design notes:
 *   - Posts are a custom post type (`aarepdc_feed`) with show_ui=true, which gives AAREP staff a
 *     free moderation queue in wp-admin (Pending filter, edit, trash) with zero custom admin UI.
 *   - Moderation: option `aarepdc_feed_moderation` ('1' default) => new posts save as 'pending'.
 *     The author sees their own pending posts with an "Awaiting approval" chip; nobody else does.
 *     Set the option to '0' for instant publishing (staff can still remove anything).
 *   - Members only (active or in the renewal grace window); rate-limited; kses-sanitized;
 *     no email addresses ever rendered.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AAREPDC_FEED_CPT', 'aarepdc_feed' );

/** Feed categories: key => [label, css-class]. */
function aarepdc_feed_categories() {
	return array(
		'deal'        => 'Deal',
		'opportunity' => 'Opportunity',
		'idea'        => 'Idea',
		'request'     => 'Request',
		'update'      => 'Update',
	);
}

/** Is the approval queue on? (default yes) */
function aarepdc_feed_moderation_on() {
	return '0' !== (string) get_option( 'aarepdc_feed_moderation', '1' );
}

/* ---------- CPT (front-end invisible; wp-admin = the moderation queue) ---------- */

add_action( 'init', 'aarepdc_feed_register_cpt' );
function aarepdc_feed_register_cpt() {
	register_post_type( AAREPDC_FEED_CPT, array(
		'labels' => array(
			'name'          => 'Member Feed',
			'singular_name' => 'Feed Post',
			'menu_name'     => 'Member Feed',
			'edit_item'     => 'Edit Feed Post',
			'all_items'     => 'All Feed Posts',
		),
		'public'              => false,
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_rest'        => false,
		'menu_icon'           => 'dashicons-megaphone',
		'supports'            => array( 'editor', 'author' ),
		'capability_type'     => 'post',
		'has_archive'         => false,
		'rewrite'             => false,
	) );
}

/* wp-admin list: show category + status at a glance for the approval queue. */
add_filter( 'manage_' . AAREPDC_FEED_CPT . '_posts_columns', function ( $cols ) {
	$cols['aarepdc_cat'] = 'Category';
	return $cols;
} );
add_action( 'manage_' . AAREPDC_FEED_CPT . '_posts_custom_column', function ( $col, $post_id ) {
	if ( 'aarepdc_cat' === $col ) {
		$cats = aarepdc_feed_categories();
		$key  = (string) get_post_meta( $post_id, 'aarepdc_feed_category', true );
		echo esc_html( isset( $cats[ $key ] ) ? $cats[ $key ] : '—' );
	}
}, 10, 2 );

/* ---------- Access ---------- */

/** Can this user read/post to the feed? Signed-in member, active or inside grace (admins yes). */
function aarepdc_feed_user_can_participate( $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();
	if ( ! $user_id ) {
		return false;
	}
	if ( user_can( $user_id, 'manage_options' ) ) {
		return true;
	}
	if ( function_exists( 'aarepdc_past_grace' ) && aarepdc_past_grace( $user_id ) ) {
		return false;
	}
	if ( function_exists( 'pmpro_getMembershipLevelForUser' ) && pmpro_getMembershipLevelForUser( $user_id ) ) {
		return true;
	}
	return function_exists( 'aarepdc_in_grace_period' ) && aarepdc_in_grace_period( $user_id );
}

/** Display name that can never be an email address (reuses the messages helper when present). */
function aarepdc_feed_author_name( $user_id ) {
	if ( function_exists( 'aarepdc_msg_name' ) ) {
		return aarepdc_msg_name( $user_id );
	}
	$u = get_userdata( (int) $user_id );
	$n = $u ? trim( $u->first_name . ' ' . $u->last_name ) : '';
	if ( '' === $n && $u ) {
		$n = $u->display_name;
	}
	return ( '' === $n || is_email( $n ) ) ? 'AAREP DC member' : $n;
}

/* ---------- Posting / deleting (real form POSTs, nonce'd) ---------- */

add_action( 'template_redirect', 'aarepdc_feed_handle_post', 5 );
function aarepdc_feed_handle_post() {
	if ( empty( $_POST['aarepdc_feed_action'] ) ) {
		return;
	}
	$feed_page = get_page_by_path( 'member-feed' );
	$back      = $feed_page ? get_permalink( $feed_page->ID ) : home_url( '/member-feed/' );
	$uid       = get_current_user_id();
	$action    = sanitize_key( wp_unslash( $_POST['aarepdc_feed_action'] ) );

	if ( ! $uid || ! isset( $_POST['aarepdc_feed_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aarepdc_feed_nonce'] ) ), 'aarepdc_feed' ) ) {
		wp_safe_redirect( $back );
		exit;
	}

	if ( 'delete' === $action ) {
		$pid  = isset( $_POST['aarepdc_feed_id'] ) ? (int) $_POST['aarepdc_feed_id'] : 0;
		$post = $pid ? get_post( $pid ) : null;
		if ( $post && AAREPDC_FEED_CPT === $post->post_type && ( (int) $post->post_author === $uid || current_user_can( 'manage_options' ) ) ) {
			wp_trash_post( $pid );
			wp_safe_redirect( add_query_arg( 'feed_ok', 'deleted', $back ) );
			exit;
		}
		wp_safe_redirect( $back );
		exit;
	}

	if ( 'create' !== $action ) {
		wp_safe_redirect( $back );
		exit;
	}
	if ( ! aarepdc_feed_user_can_participate( $uid ) ) {
		wp_safe_redirect( add_query_arg( 'feed_err', rawurlencode( 'An active membership is required to post.' ), $back ) );
		exit;
	}

	$cats = aarepdc_feed_categories();
	$cat  = isset( $_POST['aarepdc_feed_category'] ) ? sanitize_key( wp_unslash( $_POST['aarepdc_feed_category'] ) ) : '';
	if ( ! isset( $cats[ $cat ] ) ) {
		$cat = 'update';
	}
	$body = trim( wp_kses( (string) wp_unslash( $_POST['aarepdc_feed_body'] ?? '' ), array( 'br' => array(), 'p' => array(), 'strong' => array(), 'em' => array() ) ) );
	if ( '' === $body ) {
		wp_safe_redirect( add_query_arg( 'feed_err', rawurlencode( 'Please write something first.' ), $back ) );
		exit;
	}
	if ( mb_strlen( $body ) > 2000 ) {
		wp_safe_redirect( add_query_arg( 'feed_err', rawurlencode( 'That post is too long (2,000 character limit).' ), $back ) );
		exit;
	}
	// Rate limit: 10 posts/hour/member.
	$bucket = 'aarepdc_feed_rate_' . $uid;
	$n      = (int) get_transient( $bucket );
	if ( $n >= 10 ) {
		wp_safe_redirect( add_query_arg( 'feed_err', rawurlencode( 'You’ve posted a lot in the last hour — please try again later.' ), $back ) );
		exit;
	}
	set_transient( $bucket, $n + 1, HOUR_IN_SECONDS );

	$status = aarepdc_feed_moderation_on() && ! current_user_can( 'manage_options' ) ? 'pending' : 'publish';
	$pid    = wp_insert_post( array(
		'post_type'    => AAREPDC_FEED_CPT,
		'post_status'  => $status,
		'post_author'  => $uid,
		'post_title'   => wp_trim_words( wp_strip_all_tags( $body ), 8, '…' ),
		'post_content' => $body,
	), true );
	if ( is_wp_error( $pid ) ) {
		wp_safe_redirect( add_query_arg( 'feed_err', rawurlencode( 'Something went wrong — please try again.' ), $back ) );
		exit;
	}
	update_post_meta( $pid, 'aarepdc_feed_category', $cat );

	// Tell AAREP staff a post awaits approval (mail-guard-gated on staging).
	if ( 'pending' === $status ) {
		$inner = '<h1 style="margin:0 0 14px;color:#1e4480;font-size:22px;font-weight:bold;">Feed post awaiting approval</h1>'
			. '<p style="margin:0 0 16px;color:#33384a;font-size:15px;line-height:1.65;"><strong>' . esc_html( aarepdc_feed_author_name( $uid ) ) . '</strong> submitted a new '
			. esc_html( strtolower( aarepdc_feed_categories()[ $cat ] ) ) . ' post to the Member Feed. Review it in the dashboard under <strong>Member Feed &rarr; Pending</strong>.</p>';
		$html  = function_exists( 'aarepdc_email_shell' ) ? aarepdc_email_shell( $inner ) : $inner;
		wp_mail( 'info@aarepdc.org', 'AAREP DC — feed post awaiting approval', $html, array( 'Content-Type: text/html; charset=UTF-8' ) );
	}

	wp_safe_redirect( add_query_arg( 'feed_ok', ( 'pending' === $status ? 'pending' : 'posted' ), $back ) );
	exit;
}

/* ---------- Render ---------- */

add_shortcode( 'aarepdc_member_feed', 'aarepdc_feed_shortcode' );
function aarepdc_feed_shortcode() {
	$uid = get_current_user_id();
	if ( ! $uid ) {
		return '<p class="aarepdc-members-only">The Member Feed is available to AAREP DC members only. Please <a href="/member-login/">log in</a> to view.</p>';
	}
	if ( ! aarepdc_feed_user_can_participate( $uid ) ) {
		return '<p class="aarepdc-members-only">The Member Feed is available to active members. <a href="/renew/">Renew your membership</a> to rejoin the conversation.</p>';
	}

	$cats = aarepdc_feed_categories();
	$out  = '<div class="aarepdc-feed">';

	if ( ! empty( $_GET['feed_err'] ) ) {
		$out .= '<div class="aarepdc-msg-alert aarepdc-msg-alert-err">' . esc_html( rawurldecode( sanitize_text_field( wp_unslash( $_GET['feed_err'] ) ) ) ) . '</div>';
	} elseif ( ! empty( $_GET['feed_ok'] ) ) {
		$ok   = sanitize_key( wp_unslash( $_GET['feed_ok'] ) );
		$msgs = array(
			'posted'  => 'Posted to the feed.',
			'pending' => 'Thanks! Your post was submitted and will appear once AAREP DC approves it.',
			'deleted' => 'Post removed.',
		);
		$out .= '<div class="aarepdc-msg-alert aarepdc-msg-alert-ok">' . esc_html( $msgs[ $ok ] ?? 'Done.' ) . '</div>';
	}

	/* Composer */
	$out .= '<form class="aarepdc-feed-composer" method="post" action="">';
	$out .= wp_nonce_field( 'aarepdc_feed', 'aarepdc_feed_nonce', true, false );
	$out .= '<input type="hidden" name="aarepdc_feed_action" value="create" />';
	$out .= '<div class="aarepdc-feed-composer-head">' . get_avatar( $uid, 40 );
	$out .= '<select name="aarepdc_feed_category" aria-label="Post category">';
	foreach ( $cats as $k => $label ) {
		$out .= '<option value="' . esc_attr( $k ) . '">' . esc_html( $label ) . '</option>';
	}
	$out .= '</select></div>';
	$out .= '<textarea name="aarepdc_feed_body" rows="3" maxlength="2000" placeholder="Share a deal, opportunity, idea, or request with the membership…" required></textarea>';
	$out .= '<div class="aarepdc-feed-composer-foot">';
	$out .= aarepdc_feed_moderation_on() && ! current_user_can( 'manage_options' )
		? '<span class="aarepdc-feed-note">Posts appear after a quick review by AAREP DC.</span>'
		: '<span class="aarepdc-feed-note">Posts appear immediately.</span>';
	$out .= '<button type="submit" class="aarepdc-btn">Post</button>';
	$out .= '</div></form>';

	/* Feed: published posts + the viewer's own pending posts (pinned on top with a chip). */
	$paged = isset( $_GET['fpg'] ) ? max( 1, (int) $_GET['fpg'] ) : 1;

	$mine_pending = array();
	if ( 1 === $paged ) {
		$mine_pending = get_posts( array(
			'post_type'      => AAREPDC_FEED_CPT,
			'post_status'    => 'pending',
			'author'         => $uid,
			'posts_per_page' => 10,
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );
	}
	$q = new WP_Query( array(
		'post_type'      => AAREPDC_FEED_CPT,
		'post_status'    => 'publish',
		'posts_per_page' => 20,
		'paged'          => $paged,
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );

	$out .= '<div class="aarepdc-feed-list">';
	if ( empty( $mine_pending ) && ! $q->have_posts() ) {
		$out .= '<p class="aarepdc-feed-empty">No posts yet — be the first to share something with the membership.</p>';
	}
	foreach ( $mine_pending as $p ) {
		$out .= aarepdc_feed_render_item( $p, $uid, true );
	}
	while ( $q->have_posts() ) {
		$q->the_post();
		$out .= aarepdc_feed_render_item( get_post(), $uid, false );
	}
	wp_reset_postdata();
	$out .= '</div>';

	if ( $q->max_num_pages > $paged ) {
		$feed_page = get_page_by_path( 'member-feed' );
		$base      = $feed_page ? get_permalink( $feed_page->ID ) : home_url( '/member-feed/' );
		$out      .= '<p class="aarepdc-feed-more"><a class="aarepdc-btn aarepdc-btn-ghost" href="' . esc_url( add_query_arg( 'fpg', $paged + 1, $base ) ) . '">Load more</a></p>';
	}

	$out .= '<p class="aarepdc-msg-foot">Be professional and generous — this feed is visible to all AAREP DC members. '
		. 'See something off? <a href="mailto:info@aarepdc.org?subject=Member%20Feed%20report">Report it</a>.</p>';
	$out .= '</div>';
	return $out;
}

/** One feed card. */
function aarepdc_feed_render_item( $post, $viewer_id, $is_pending ) {
	$cats  = aarepdc_feed_categories();
	$key   = (string) get_post_meta( $post->ID, 'aarepdc_feed_category', true );
	$label = isset( $cats[ $key ] ) ? $cats[ $key ] : 'Update';
	$aid   = (int) $post->post_author;
	$name  = esc_html( aarepdc_feed_author_name( $aid ) );
	if ( function_exists( 'aarepdc_is_board_member' ) && aarepdc_is_board_member( $aid ) ) {
		$name .= ' <span class="aarepdc-board-chip">Board</span>';
	}

	$out  = '<article class="aarepdc-feed-item' . ( $is_pending ? ' is-pending' : '' ) . '">';
	$out .= '<header><span class="aarepdc-feed-avatar">' . get_avatar( $aid, 44 ) . '</span>';
	$out .= '<span class="aarepdc-feed-who"><span class="aarepdc-feed-name">' . $name . '</span>';
	$out .= '<time>' . esc_html( human_time_diff( get_post_timestamp( $post ), time() ) ) . ' ago</time></span>';
	$out .= '<span class="aarepdc-feed-chips"><span class="aarepdc-feed-chip aarepdc-feed-chip-' . esc_attr( $key ?: 'update' ) . '">' . esc_html( $label ) . '</span>';
	if ( $is_pending ) {
		$out .= ' <span class="aarepdc-feed-chip aarepdc-feed-chip-pending">Awaiting approval</span>';
	}
	$out .= '</span></header>';
	$out .= '<div class="aarepdc-feed-body">' . wpautop( wp_kses( $post->post_content, array( 'br' => array(), 'p' => array(), 'strong' => array(), 'em' => array() ) ) ) . '</div>';
	if ( (int) $viewer_id === $aid || current_user_can( 'manage_options' ) ) {
		$out .= '<form method="post" action="" class="aarepdc-feed-del">'
			. wp_nonce_field( 'aarepdc_feed', 'aarepdc_feed_nonce', true, false )
			. '<input type="hidden" name="aarepdc_feed_action" value="delete" />'
			. '<input type="hidden" name="aarepdc_feed_id" value="' . (int) $post->ID . '" />'
			. '<button type="submit" aria-label="Delete this post">Delete</button></form>';
	}
	$out .= '</article>';
	return $out;
}
