<?php
/*
   Plugin Name: Sponsorship Registration System	
   Description: Sponsorship Registration System
   Version: 1.0
   Author: Apex Global Solutions
*/
if ( ! defined( 'ABSPATH' ) ) { 
	exit; // Exit if accessed directly
}
if ( ! defined('SP_PLUGIN_BASENAME') )
	define('SP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
if ( ! defined('SP_PLUGIN_NAME') )
	define( 'SP_PLUGIN_NAME', trim( dirname(SP_PLUGIN_BASENAME ), '/' ) );
if ( ! defined( 'SP_PLUGIN_DIR' ) )
	define( 'SP_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . SP_PLUGIN_NAME );
if ( ! defined( 'SP_PLUGIN_URL' ) )
	define( 'SP_PLUGIN_URL', WP_PLUGIN_URL . '/' . SP_PLUGIN_NAME );
	
//require_once('ajax-request.php');
	
add_action( 'admin_menu', 'sp_menu_items');

function sp_menu_items() {

	//add_menu_page( "Employee Panel", "Employee Panel", 'manage_options', "employee-panel",'pp_employee');
	add_menu_page( "Sponsorship Registration", "Sponsorship Registration", 'manage_options', "sponsorship-registration",'sponsorship_list_panel');
	add_submenu_page("sponsorship-registration",'Add Details','Add Details','manage_options','add-sponsorship-details','ers_add_sponsorship');
	add_submenu_page("null",'Sponsorship Information','Sponsorship Information','manage_options','view_sponsorship_information','view_sponsorship_class_data');
	//add_submenu_page("event-registration",'User Credits','User Credits','manage_options','user-credits','pp_user_credit');
	//add_submenu_page("event-registration",'Employee','Employee','manage_options','employee-panel','pp_employee');
}
function sponsorship_list_panel(){
	global $wpdb;
	include 'sponsorship-registration-list.php';
}
function ers_add_sponsorship(){
	global $wpdb;
	include 'add-sponsorship-details.php';
}
function view_sponsorship_class_data(){
	global $wpdb;
	include 'view_sponsorship_data.php';
}
// function pp_user_credit(){
// 	global $wpdb;
// 	include 'user-credit-list.php';
// }

// function pp_employee(){
// 	global $wpdb;
// 	include 'register_employee.php';
// }
 
add_action("admin_init","sp_admin_enquque_scripts");
function sp_admin_enquque_scripts(){
    wp_enqueue_script('ui-sortable');
	wp_enqueue_style('sp_custom', SP_PLUGIN_URL . '/assets/css/custom.css', false,null);
	wp_enqueue_script('sp-common-js',SP_PLUGIN_URL.'/assets/js/common-js.js', array('jquery'));
	wp_localize_script( 'sp-common-js', 'sp_admin_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
}
?>