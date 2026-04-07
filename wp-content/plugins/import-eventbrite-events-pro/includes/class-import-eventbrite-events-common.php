<?php
/**
 * Common functions class for Import Eventbrite Events Pro.
 *
 * @link       http://xylusthemes.com/
 * @since      1.5.0
 *
 * @package    Import_Eventbrite_Events_Pro
 * @subpackage Import_Eventbrite_Events_Pro/includes
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Import_Eventbrite_Events_Pro_Common {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {}

	/**
	 * Load License page.
	 *
	 * @since 1.5.0
	 * @return void
	 */
	function iee_licence_page_in_setting() {
		
		?>
		<div class="wrap iee_admin_panel">
			<h3 class="setting_bar"><?php esc_html_e( 'Import Eventbrite Events Pro License', 'import-eventbrite-events-pro' ); ?></h3>
		    <div id="poststuff">
		        <div id="post-body" class="metabox-holder columns-2">

		            <div id="postbox-container-1" class="postbox-container">
		            	<?php 
		            	// Sidebar here.
		            	?>
		            </div>
		            <div id="postbox-container-2" class="postbox-container">
		                <div class="import-eventbrite-events-page">

		                	<?php
		                	if( function_exists( 'iee_pro_license_page' ) ){
	                			iee_pro_license_page();
	                		}
			                ?>
		                	<div style="clear: both"></div>
		                </div>

		        </div>
		        
		    </div>
		</div>
		<?php
	}
}