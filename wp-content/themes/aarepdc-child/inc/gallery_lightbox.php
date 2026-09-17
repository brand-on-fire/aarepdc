<?php
/**
 * Standard lightbox for WordPress core galleries.
 *
 * Core galleries link each thumbnail to its attachment page by default, which drops visitors
 * onto a bare single-image page. Point the links at the image file instead and open them in a
 * small, dependency-free lightbox (js/aarepdc-lightbox.js). Without JavaScript the link still
 * opens the full image rather than the attachment page.
 *
 * Only core [gallery] output is affected. A Photonic-rendered gallery (for example the SmugMug
 * feed, once an API key is configured) uses its own markup and lightbox and is left alone.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'shortcode_atts_gallery', 'aarepdc_gallery_lightbox_atts', 10, 3 );
function aarepdc_gallery_lightbox_atts( $out, $pairs, $atts ) {
	unset( $pairs );
	$atts = (array) $atts;

	// Respect an explicit link="none"; otherwise open the file, not the attachment page.
	if ( ! isset( $atts['link'] ) || 'none' !== $atts['link'] ) {
		$out['link'] = 'file';
	}
	// Tiles are wider than a 150px thumbnail, so use a sharper source unless one was chosen.
	if ( empty( $atts['size'] ) ) {
		$out['size'] = 'medium_large';
	}

	return $out;
}

add_action( 'wp_enqueue_scripts', 'aarepdc_gallery_lightbox_assets', 20 );
function aarepdc_gallery_lightbox_assets() {
	if ( ! is_singular() ) {
		return;
	}
	$post = get_post();
	if ( ! $post || false === strpos( (string) $post->post_content, '[gallery' ) ) {
		return;
	}

	$path = get_stylesheet_directory() . '/js/aarepdc-lightbox.js';
	wp_enqueue_script(
		'aarepdc-lightbox',
		get_stylesheet_directory_uri() . '/js/aarepdc-lightbox.js',
		array(),
		is_readable( $path ) ? (string) filemtime( $path ) : '1',
		true
	);
}
