<?php
/*
   Plugin Name: Event Registration System	
   Description: Event Registration System
   Version: 1.0
   Author: Apex Global Solutions
*/
if ( ! defined( 'ABSPATH' ) ) { 
	exit; // Exit if accessed directly
}
if ( ! defined('PP_PLUGIN_BASENAME') )
	define('PP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
if ( ! defined('PP_PLUGIN_NAME') )
	define( 'PP_PLUGIN_NAME', trim( dirname(PP_PLUGIN_BASENAME ), '/' ) );
if ( ! defined( 'PP_PLUGIN_DIR' ) )
	define( 'PP_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . PP_PLUGIN_NAME );
if ( ! defined( 'PP_PLUGIN_URL' ) )
	define( 'PP_PLUGIN_URL', WP_PLUGIN_URL . '/' . PP_PLUGIN_NAME );
	
require_once('ajax-request.php');
	
add_action( 'admin_menu', 'pp_menu_items');

function pp_menu_items() {

	//add_menu_page( "Employee Panel", "Employee Panel", 'manage_options', "employee-panel",'pp_employee');
	add_menu_page( "Event Registration", "Event Registration", 'manage_options', "event-registration",'event_list_panel');
	add_submenu_page("event-registration",'Add Details','Add Details','manage_options','add-event-details','ers_add_event');
	add_submenu_page("null",'Event Information','Event Information','manage_options','view_event_information','view_event_class_data');
	//add_submenu_page("event-registration",'User Credits','User Credits','manage_options','user-credits','pp_user_credit');
	//add_submenu_page("event-registration",'Employee','Employee','manage_options','employee-panel','pp_employee');
}
function event_list_panel(){
	global $wpdb;
	include 'event-registration-list.php';
}
function ers_add_event(){
	global $wpdb;
	include 'add-event-details.php';
}
function view_event_class_data(){
	global $wpdb;
	include 'view_event_data.php';
}
// function pp_user_credit(){
// 	global $wpdb;
// 	include 'user-credit-list.php';
// }

// function pp_employee(){
// 	global $wpdb;
// 	include 'register_employee.php';
// }
 
add_action("admin_init","ers_admin_enquque_scripts");
function ers_admin_enquque_scripts(){
    wp_enqueue_script('ui-sortable');
	wp_enqueue_style('ers_custom', PP_PLUGIN_URL . '/assets/css/custom.css', false,null);
	wp_enqueue_script('ers-common-js',PP_PLUGIN_URL.'/assets/js/common-js.js', array('jquery'));
	wp_localize_script( 'ers-common-js', 'ers_admin_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
}
?>