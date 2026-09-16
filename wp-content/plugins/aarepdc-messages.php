<?php
/**
 * Plugin Name: AAREP DC — Member Messages
 * Description: Private member-to-member messaging inside the portal. Members contact each other without ever seeing a private email address; recipients get a branded "you have a new message" notification with a Log in to reply button. Two-pane conversation UI (Messages-for-Mac style). Built 2026-07-21 per client request (Alexis Pannell, AAREP DC).
 * Version: 1.0.1
 * Author: Brand on Fire
 *
 * Design notes:
 *   - ONE table. `thread_key` is the deterministic sorted user-id pair ("{min}-{max}"), so 1:1
 *     conversations group naturally with no separate threads table.
 *   - No email address is ever rendered in the UI or included in a notification.
 *   - Notifications are throttled: one per thread until the recipient reads it, so an active
 *     back-and-forth cannot spam an inbox.
 *   - Access requires a signed-in member who is active (or inside the renewal grace window).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AAREPDC_MSG_VERSION', '1.0.0' );

/** Table name. */
function aarepdc_msg_table() {
	global $wpdb;
	return $wpdb->prefix . 'aarepdc_messages';
}

/** Deterministic thread key for a pair of members. */
function aarepdc_msg_thread_key( $a, $b ) {
	$a = (int) $a;
	$b = (int) $b;
	return min( $a, $b ) . '-' . max( $a, $b );
}

/* ---------- Schema ---------- */

register_activation_hook( __FILE__, 'aarepdc_msg_install' );
add_action( 'init', 'aarepdc_msg_maybe_install' );

function aarepdc_msg_maybe_install() {
	if ( get_option( 'aarepdc_msg_db_version' ) === AAREPDC_MSG_VERSION ) {
		return;
	}
	aarepdc_msg_install();
}

function aarepdc_msg_install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$table   = aarepdc_msg_table();
	$charset = $wpdb->get_charset_collate();
	$sql = "CREATE TABLE {$table} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		thread_key VARCHAR(40) NOT NULL,
		from_user_id BIGINT(20) UNSIGNED NOT NULL,
		to_user_id BIGINT(20) UNSIGNED NOT NULL,
		body TEXT NOT NULL,
		created_at DATETIME NOT NULL,
		read_at DATETIME NULL DEFAULT NULL,
		notified_at DATETIME NULL DEFAULT NULL,
		PRIMARY KEY (id),
		KEY thread_key (thread_key),
		KEY to_user_id (to_user_id),
		KEY from_user_id (from_user_id)
	) {$charset};";
	dbDelta( $sql );
	update_option( 'aarepdc_msg_db_version', AAREPDC_MSG_VERSION );
}

/* ---------- Access control ---------- */

/** Can this user use Messages? Signed-in member, active or inside grace. */
function aarepdc_msg_user_can_message( $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();
	if ( ! $user_id ) {
		return false;
	}
	if ( user_can( $user_id, 'manage_options' ) ) {
		return true; // admins may use the inbox (they are NOT valid recipients — see below)
	}
	// Grace fully closed = lapsed, regardless of what PMPro's expiry cron has got round to.
	if ( function_exists( 'aarepdc_past_grace' ) && aarepdc_past_grace( $user_id ) ) {
		return false;
	}
	if ( function_exists( 'pmpro_getMembershipLevelForUser' ) && pmpro_getMembershipLevelForUser( $user_id ) ) {
		return true;
	}
	return function_exists( 'aarepdc_in_grace_period' ) && aarepdc_in_grace_period( $user_id );
}

/** May $sender message $recipient? Recipient must be a member who isn't hidden from the directory. */
function aarepdc_msg_can_contact( $sender_id, $recipient_id ) {
	$sender_id    = (int) $sender_id;
	$recipient_id = (int) $recipient_id;
	if ( ! $sender_id || ! $recipient_id || $sender_id === $recipient_id ) {
		return false;
	}
	if ( ! aarepdc_msg_user_can_message( $sender_id ) ) {
		return false;
	}
	if ( ! aarepdc_msg_user_can_message( $recipient_id ) ) {
		return false;
	}
	// Recipients must be real directory members. Admin/staff accounts are not listed in the
	// directory, so they must not be reachable by a hand-crafted ?with=<id> either.
	if ( user_can( $recipient_id, 'manage_options' ) ) {
		return false;
	}
	if ( '1' === (string) get_user_meta( $recipient_id, 'pmpromd_hide_directory', true ) ) {
		return false;
	}
	return true;
}

/** Messages page URL (the portal account page with our view). */
function aarepdc_msg_page_url( $thread_with = 0 ) {
	$account_id = (int) get_option( 'pmpro_account_page_id' );
	$base       = $account_id ? get_permalink( $account_id ) : home_url( '/member-account/' );
	$url        = add_query_arg( 'view', 'messages', $base );
	if ( $thread_with ) {
		$url = add_query_arg( 'with', (int) $thread_with, $url );
	}
	return $url;
}

/* ---------- Data ---------- */

/** Unread message count for a member. */
function aarepdc_msg_unread_count( $user_id ) {
	global $wpdb;
	$user_id = (int) $user_id;
	if ( ! $user_id ) {
		return 0;
	}
	return (int) $wpdb->get_var( $wpdb->prepare(
		'SELECT COUNT(*) FROM ' . aarepdc_msg_table() . ' WHERE to_user_id = %d AND read_at IS NULL',
		$user_id
	) );
}

/** Conversation list for a member: one row per counterpart, newest first. */
function aarepdc_msg_threads( $user_id ) {
	global $wpdb;
	$user_id = (int) $user_id;
	$table   = aarepdc_msg_table();
	$rows    = $wpdb->get_results( $wpdb->prepare(
		"SELECT m.*,
		        IF( m.from_user_id = %d, m.to_user_id, m.from_user_id ) AS other_id
		   FROM {$table} m
		   INNER JOIN (
		        SELECT thread_key, MAX(id) AS max_id
		          FROM {$table}
		         WHERE from_user_id = %d OR to_user_id = %d
		         GROUP BY thread_key
		   ) latest ON latest.max_id = m.id
		  ORDER BY m.created_at DESC",
		$user_id,
		$user_id,
		$user_id
	) );
	$out = array();
	foreach ( (array) $rows as $r ) {
		$other = (int) $r->other_id;
		$out[] = array(
			'other_id' => $other,
			'snippet'  => wp_trim_words( wp_strip_all_tags( $r->body ), 12, '…' ),
			'time'     => $r->created_at,
			'unread'   => (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE thread_key = %s AND to_user_id = %d AND read_at IS NULL",
				$r->thread_key,
				$user_id
			) ),
			'mine'     => ( (int) $r->from_user_id === $user_id ),
		);
	}
	return $out;
}

/** All messages between two members, oldest first. */
function aarepdc_msg_thread( $user_id, $other_id ) {
	global $wpdb;
	return (array) $wpdb->get_results( $wpdb->prepare(
		'SELECT * FROM ' . aarepdc_msg_table() . ' WHERE thread_key = %s ORDER BY created_at ASC, id ASC',
		aarepdc_msg_thread_key( $user_id, $other_id )
	) );
}

/** Mark everything the member received in this thread as read. */
function aarepdc_msg_mark_read( $user_id, $other_id ) {
	global $wpdb;
	$wpdb->query( $wpdb->prepare(
		'UPDATE ' . aarepdc_msg_table() . ' SET read_at = %s WHERE thread_key = %s AND to_user_id = %d AND read_at IS NULL',
		current_time( 'mysql' ),
		aarepdc_msg_thread_key( $user_id, $other_id ),
		(int) $user_id
	) );
}

/** Display name for a member (never an email address). */
function aarepdc_msg_name( $user_id ) {
	$u = get_userdata( (int) $user_id );
	if ( ! $u ) {
		return 'AAREP DC member';
	}
	// Keep staging-only Board identities visibly distinct from similarly named
	// real staging members in Messages and the Feed without changing real users.
	$name = '1' === get_user_meta( $u->ID, 'aarepdc_preview_only', true )
		? $u->display_name
		: trim( $u->first_name . ' ' . $u->last_name );
	if ( '' === $name ) {
		$name = $u->display_name;
	}
	// Never fall back to something that could be an email address.
	if ( '' === $name || is_email( $name ) ) {
		$name = 'AAREP DC member';
	}
	return $name;
}

/* ---------- Sending ---------- */

/**
 * Store a message and (throttled) notify the recipient.
 *
 * @return true|WP_Error
 */
function aarepdc_msg_send( $from_id, $to_id, $body ) {
	global $wpdb;
	$from_id = (int) $from_id;
	$to_id   = (int) $to_id;
	$body    = trim( wp_kses( (string) $body, array( 'br' => array(), 'p' => array(), 'strong' => array(), 'em' => array() ) ) );

	if ( '' === $body ) {
		return new WP_Error( 'empty', 'Please write a message first.' );
	}
	if ( mb_strlen( $body ) > 5000 ) {
		return new WP_Error( 'too_long', 'That message is too long (5,000 character limit).' );
	}
	if ( ! aarepdc_msg_can_contact( $from_id, $to_id ) ) {
		return new WP_Error( 'not_allowed', 'You can’t message that member.' );
	}
	// Simple per-user rate limit: 20 messages / hour.
	$bucket = 'aarepdc_msg_rate_' . $from_id;
	$sent   = (int) get_transient( $bucket );
	if ( $sent >= 20 ) {
		return new WP_Error( 'rate', 'You’ve sent a lot of messages in a short time — please try again later.' );
	}
	set_transient( $bucket, $sent + 1, HOUR_IN_SECONDS );

	$thread_key = aarepdc_msg_thread_key( $from_id, $to_id );
	$wpdb->insert(
		aarepdc_msg_table(),
		array(
			'thread_key'   => $thread_key,
			'from_user_id' => $from_id,
			'to_user_id'   => $to_id,
			'body'         => $body,
			'created_at'   => current_time( 'mysql' ),
		),
		array( '%s', '%d', '%d', '%s', '%s' )
	);

	aarepdc_msg_maybe_notify( $from_id, $to_id, $thread_key );
	return true;
}

/**
 * Notify the recipient — but only if they have no earlier UNREAD message in this thread that we
 * already notified about. Keeps an active conversation from generating an email per reply.
 */
function aarepdc_msg_maybe_notify( $from_id, $to_id, $thread_key ) {
	global $wpdb;
	$table = aarepdc_msg_table();

	$already = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$table} WHERE thread_key = %s AND to_user_id = %d AND read_at IS NULL AND notified_at IS NOT NULL",
		$thread_key,
		(int) $to_id
	) );
	if ( $already > 0 ) {
		return; // they already have an un-read notified message in this thread
	}

	$to = get_userdata( (int) $to_id );
	if ( ! $to || ! is_email( $to->user_email ) ) {
		return;
	}
	$sender_name = aarepdc_msg_name( $from_id );
	$reply_url   = aarepdc_msg_page_url( $from_id );
	$subject     = sprintf( 'New message from %s — AAREP DC', $sender_name );

	// NOTE: the message body is deliberately NOT included; members read it in the portal.
	$inner  = '<h1 style="margin:0 0 14px;color:#1e4480;font-size:22px;font-weight:bold;">You have a new message</h1>';
	$inner .= '<p style="margin:0 0 16px;color:#33384a;font-size:15px;line-height:1.65;"><strong>' . esc_html( $sender_name ) . '</strong> sent you a message through the AAREP DC member directory.</p>';
	$inner .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:6px auto 20px;"><tr><td style="background:#1e4480;border-radius:8px;">';
	$inner .= '<a href="' . esc_url( $reply_url ) . '" style="display:inline-block;padding:15px 44px;color:#ffffff;font-size:16px;font-weight:bold;text-decoration:none;">Log in to reply &rarr;</a>';
	$inner .= '</td></tr></table>';
	$inner .= '<p style="margin:0 0 12px;color:#6a7180;font-size:13px;line-height:1.55;text-align:center;">Your email address is never shared with other members.</p>';

	$html = function_exists( 'aarepdc_email_shell' ) ? aarepdc_email_shell( $inner ) : $inner;
	wp_mail( $to->user_email, $subject, $html, array( 'Content-Type: text/html; charset=UTF-8' ) );

	$wpdb->query( $wpdb->prepare(
		"UPDATE {$table} SET notified_at = %s WHERE thread_key = %s AND to_user_id = %d AND read_at IS NULL AND notified_at IS NULL",
		current_time( 'mysql' ),
		$thread_key,
		(int) $to_id
	) );
}

/* ---------- Form handling ---------- */

add_action( 'template_redirect', 'aarepdc_msg_handle_post', 5 );
function aarepdc_msg_handle_post() {
	if ( empty( $_POST['aarepdc_msg_send'] ) ) {
		return;
	}
	if ( ! is_user_logged_in() || ! isset( $_POST['aarepdc_msg_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aarepdc_msg_nonce'] ) ), 'aarepdc_msg_send' ) ) {
		wp_safe_redirect( aarepdc_msg_page_url() );
		exit;
	}
	$to   = isset( $_POST['aarepdc_msg_to'] ) ? (int) $_POST['aarepdc_msg_to'] : 0;
	$body = isset( $_POST['aarepdc_msg_body'] ) ? wp_unslash( $_POST['aarepdc_msg_body'] ) : '';
	$res  = aarepdc_msg_send( get_current_user_id(), $to, $body );

	$url = aarepdc_msg_page_url( $to );
	$url = add_query_arg( is_wp_error( $res ) ? array( 'msg_err' => rawurlencode( $res->get_error_message() ) ) : array( 'msg_ok' => 1 ), $url );
	wp_safe_redirect( $url );
	exit;
}

/* ---------- UI ---------- */

/** Renders the two-pane Messages view. Hooked into the account page by the portal filter. */
function aarepdc_msg_render_panel( $user_id, $account_url ) {
	$threads = aarepdc_msg_threads( $user_id );
	$with    = isset( $_GET['with'] ) ? (int) $_GET['with'] : 0;
	if ( $with && ! aarepdc_msg_can_contact( $user_id, $with ) ) {
		$with = 0;
	}
	if ( $with ) {
		aarepdc_msg_mark_read( $user_id, $with );
	}

	$out = '<div class="aarepdc-welcome aarepdc-messages">';
	$out .= '<p style="margin:0 0 14px;"><a class="aarepdc-btn aarepdc-btn-ghost" href="' . esc_url( $account_url ) . '">&larr; Back to Member Portal</a></p>';
	$out .= '<h2 class="aarepdc-welcome-title">Messages</h2>';

	if ( ! empty( $_GET['msg_err'] ) ) {
		$out .= '<div class="aarepdc-msg-alert aarepdc-msg-alert-err">' . esc_html( rawurldecode( sanitize_text_field( wp_unslash( $_GET['msg_err'] ) ) ) ) . '</div>';
	} elseif ( ! empty( $_GET['msg_ok'] ) ) {
		$out .= '<div class="aarepdc-msg-alert aarepdc-msg-alert-ok">Message sent.</div>';
	}

	$out .= '<div class="aarepdc-msg-wrap' . ( $with ? ' has-thread' : '' ) . '">';

	/* Left pane — conversations */
	$out .= '<aside class="aarepdc-msg-list">';
	if ( empty( $threads ) ) {
		$out .= '<p class="aarepdc-msg-empty">No conversations yet. Find a member in the <a href="' . esc_url( home_url( '/member-directory/' ) ) . '">directory</a> and choose <strong>Contact</strong>.</p>';
	}
	foreach ( $threads as $t ) {
		$active = ( $with === $t['other_id'] ) ? ' is-active' : '';
		$out .= '<a class="aarepdc-msg-item' . $active . ( $t['unread'] ? ' is-unread' : '' ) . '" href="' . esc_url( aarepdc_msg_page_url( $t['other_id'] ) ) . '">';
		$out .= '<span class="aarepdc-msg-avatar">' . get_avatar( $t['other_id'], 40 ) . '</span>';
		$out .= '<span class="aarepdc-msg-meta">';
		$out .= '<span class="aarepdc-msg-name">' . esc_html( aarepdc_msg_name( $t['other_id'] ) ) . '</span>';
		$out .= '<span class="aarepdc-msg-snippet">' . ( $t['mine'] ? 'You: ' : '' ) . esc_html( $t['snippet'] ) . '</span>';
		$out .= '</span>';
		$out .= '<span class="aarepdc-msg-when">' . esc_html( human_time_diff( strtotime( $t['time'] ), current_time( 'timestamp' ) ) ) . '</span>';
		if ( $t['unread'] ) {
			$out .= '<span class="aarepdc-msg-dot" aria-label="unread"></span>';
		}
		$out .= '</a>';
	}
	$out .= '</aside>';

	/* Right pane — thread */
	$out .= '<section class="aarepdc-msg-thread">';
	if ( ! $with ) {
		$out .= '<div class="aarepdc-msg-placeholder"><p>Select a conversation to read it here.</p></div>';
	} else {
		$out .= '<header class="aarepdc-msg-thread-head">';
		$out .= '<a class="aarepdc-msg-back" href="' . esc_url( aarepdc_msg_page_url() ) . '" aria-label="Back to conversations">&larr;</a>';
		$out .= '<span>' . esc_html( aarepdc_msg_name( $with ) ) . '</span>';
		$out .= '</header>';
		$out .= '<div class="aarepdc-msg-bubbles">';
		foreach ( aarepdc_msg_thread( $user_id, $with ) as $m ) {
			$mine = ( (int) $m->from_user_id === (int) $user_id );
			$out .= '<div class="aarepdc-msg-bubble' . ( $mine ? ' is-mine' : '' ) . '">';
			$out .= '<div class="aarepdc-msg-body">' . wpautop( wp_kses_post( $m->body ) ) . '</div>';
			$out .= '<time>' . esc_html( date_i18n( 'M j, g:i a', strtotime( $m->created_at ) ) ) . '</time>';
			$out .= '</div>';
		}
		$out .= '</div>';
		$out .= '<form class="aarepdc-msg-form" method="post" action="">';
		$out .= wp_nonce_field( 'aarepdc_msg_send', 'aarepdc_msg_nonce', true, false );
		$out .= '<input type="hidden" name="aarepdc_msg_to" value="' . (int) $with . '" />';
		$out .= '<textarea name="aarepdc_msg_body" rows="2" placeholder="Write a message…" required></textarea>';
		$out .= '<button type="submit" name="aarepdc_msg_send" value="1" class="aarepdc-btn">Send</button>';
		$out .= '</form>';
	}
	$out .= '</section>';
	$out .= '</div>';

	$out .= '<p class="aarepdc-msg-foot">Messages stay inside the member portal — email addresses are never shared. '
		. 'Need help or want to report a problem? Email <a href="mailto:info@aarepdc.org">info@aarepdc.org</a>.</p>';
	$out .= '</div>';
	return $out;
}

/* ---------- Directory / profile entry point ---------- */

add_filter( 'pmpromd_member_profile_action_links', 'aarepdc_msg_profile_action_link', 10, 2 );
function aarepdc_msg_profile_action_link( $links, $pu = null ) {
	if ( ( ! is_object( $pu ) || empty( $pu->ID ) ) && function_exists( 'pmpromd_get_user' ) ) {
		$pu = pmpromd_get_user();
	}
	$other = is_object( $pu ) && ! empty( $pu->ID ) ? (int) $pu->ID : 0;
	if ( ! $other || ! aarepdc_msg_can_contact( get_current_user_id(), $other ) ) {
		return $links;
	}
	$link = '<a class="aarepdc-btn aarepdc-msg-contact" href="' . esc_url( aarepdc_msg_page_url( $other ) ) . '">Contact ' . esc_html( aarepdc_msg_name( $other ) ) . '</a>';
	if ( is_array( $links ) ) {
		$links[] = $link;
		return $links;
	}
	return $links . ' ' . $link;
}
