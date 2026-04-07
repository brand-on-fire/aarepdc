<?php
/**
 * Plugin Name: Photonic Gallery & Lightbox for Flickr, SmugMug & Others
 * Plugin URI: https://aquoid.com/plugins/photonic/
 * Description: Extends the native gallery to support Flickr, SmugMug and Zenfolio. JS libraries like BaguetteBox, BigPicture, Gie Lightbox, LightGallery, PhotoSwipe, Spotlight, Swipebox, Fancybox, Magnific, Colorbox, PrettyPhoto, Image Lightbox, Featherlight and Lightcase are supported. Photos are displayed in vanilla grids of thumbnails, or more fancy slideshows, or justified or masonry or random mosaic layouts. The plugin also extends all layout options to a regular WP gallery.
 * Version: 3.24
 * Requires at least: 6.2
 * Requires PHP: 7.3
 * Author: Sayontan Sinha
 * Author URI: https://mynethome.net/
 * License: GPLv3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: photonic
 *
 * Copyright (c) 2011 - 2026 Sayontan Sinha. All rights reserved.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Photonic_Plugin;

use Photonic_Plugin\Core\Photonic;

class Photonic_Plugin {
	public function __construct() {
		if (!defined('PHOTONIC_VERSION')) {
			define('PHOTONIC_VERSION', '3.24');
		}

		define('PHOTONIC_PATH', __DIR__);

		if (!defined('PHOTONIC_URL')) {
			define('PHOTONIC_URL', plugin_dir_url(__FILE__));
		}

		$photonic_wp_upload_dir = wp_upload_dir();
		if (!defined('PHOTONIC_UPLOAD_DIR')) {
			define('PHOTONIC_UPLOAD_DIR', trailingslashit($photonic_wp_upload_dir['basedir']) . 'photonic');
		}

		if (!defined('PHOTONIC_UPLOAD_URL')) {
			define('PHOTONIC_UPLOAD_URL', trailingslashit($photonic_wp_upload_dir['baseurl']) . 'photonic');
		}

		require_once PHOTONIC_PATH . '/Core/Photonic.php';
	}
}

new Photonic_Plugin();

add_action('admin_init', '\Photonic_Plugin\photonic_utilities_init');
add_action('init', '\Photonic_Plugin\photonic_init', 0); // Delaying the start from 10 to 100 so that CPTs can be picked up

/**
 * Main plugin initiation
 */
function photonic_init() {
	global $photonic;
	$photonic = new Photonic();
}

/**
 * Loads up the utilities file
 */
function photonic_utilities_init() {
	require_once PHOTONIC_PATH . '/Core/Utilities.php';
}
