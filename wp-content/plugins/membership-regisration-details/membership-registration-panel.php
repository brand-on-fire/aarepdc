<?php
/*
   Plugin Name: Membership Registration System	
   Description: Membership Registration System
   Version: 1.0
   Author: Apex Global Solutions
*/
if ( ! defined( 'ABSPATH' ) ) { 
	exit; // Exit if accessed directly
}
if ( ! defined('MEM_PLUGIN_BASENAME') )
	define('MEM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
if ( ! defined('MEM_PLUGIN_NAME') )
	define( 'MEM_PLUGIN_NAME', trim( dirname(MEM_PLUGIN_BASENAME ), '/' ) );
if ( ! defined( 'MEM_PLUGIN_DIR' ) )
	define( 'MEM_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . MEM_PLUGIN_NAME );
if ( ! defined( 'MEM_PLUGIN_URL' ) )
	define( 'MEM_PLUGIN_URL', WP_PLUGIN_URL . '/' . MEM_PLUGIN_NAME );
	
//require_once('ajax-request.php');
	
add_action( 'admin_menu', 'mem_menu_items');

function mem_menu_items() {

	//add_menu_page( "Employee Panel", "Employee Panel", 'manage_options', "employee-panel",'pp_employee');
	add_menu_page( "Membership Registration", "Membership Registration", 'manage_options', "membership-registration",'membership_list_panel');
	add_submenu_page("membership-registration",'Add Details','Add Details','manage_options','add-membership-details','ers_add_membership');
	add_submenu_page("null",'Membership Information','Membership Information','manage_options','view_membership_information','view_membership_class_data');
	//add_submenu_page("event-registration",'User Credits','User Credits','manage_options','user-credits','pp_user_credit');
	//add_submenu_page("event-registration",'Employee','Employee','manage_options','employee-panel','pp_employee');
}
function membership_list_panel(){
	global $wpdb;
	include 'membership-registration-list.php';
}
function ers_add_membership(){
	global $wpdb;
	include 'add-membership-details.php';
}
function view_membership_class_data(){
	global $wpdb;
	include 'view_membership_data.php';
}
// function pp_user_credit(){
// 	global $wpdb;
// 	include 'user-credit-list.php';
// }

// function pp_employee(){
// 	global $wpdb;
// 	include 'register_employee.php';
// }
 
add_action("admin_init","mem_admin_enquque_scripts");
function mem_admin_enquque_scripts(){
    wp_enqueue_script('ui-sortable');
	wp_enqueue_style('mem_custom', MEM_PLUGIN_URL . '/assets/css/custom.css', false,null);
	wp_enqueue_script('mem-common-js',MEM_PLUGIN_URL.'/assets/js/common-js.js', array('jquery'));
	wp_localize_script( 'mem-common-js', 'mem_admin_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
}
?>