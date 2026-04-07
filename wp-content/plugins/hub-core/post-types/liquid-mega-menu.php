<?php

/**
 * Post Type: Mega Menu
 * Register Custom Post Type
 */

$labels = array(
	'name'                  => esc_html_x( 'Mega Menus', 'Post Type General Name', 'landinghub-core' ),
	'singular_name'         => esc_html_x( 'Mega Menu', 'Post Type Singular Name', 'landinghub-core' ),
	'menu_name'             => esc_html__( 'Mega Menus', 'landinghub-core' ),
	'name_admin_bar'        => esc_html__( 'Mega Menus', 'landinghub-core' ),
	'archives'              => esc_html__( 'Item Archives', 'landinghub-core' ),
	'parent_item_colon'     => esc_html__( 'Parent Item:', 'landinghub-core' ),
	'all_items'             => esc_html__( 'All Items', 'landinghub-core' ),
	'add_new_item'          => esc_html__( 'Add New Mega Menu', 'landinghub-core' ),
	'add_new'               => esc_html__( 'Add New', 'landinghub-core' ),
	'new_item'              => esc_html__( 'New Mega Menu', 'landinghub-core' ),
	'edit_item'             => esc_html__( 'Edit Mega Menu', 'landinghub-core' ),
	'update_item'           => esc_html__( 'Update Mega Menu', 'landinghub-core' ),
	'view_item'             => esc_html__( 'View Mega Menu', 'landinghub-core' ),
	'search_items'          => esc_html__( 'Search Mega Menu', 'landinghub-core' ),
	'not_found'             => esc_html__( 'Not found', 'landinghub-core' ),
	'not_found_in_trash'    => esc_html__( 'Not found in Trash', 'landinghub-core' ),
	'featured_image'        => esc_html__( 'Featured Image', 'landinghub-core' ),
	'set_featured_image'    => esc_html__( 'Set featured image', 'landinghub-core' ),
	'remove_featured_image' => esc_html__( 'Remove featured image', 'landinghub-core' ),
	'use_featured_image'    => esc_html__( 'Use as featured image', 'landinghub-core' ),
	'insert_into_item'      => esc_html__( 'Insert into item', 'landinghub-core' ),
	'uploaded_to_this_item' => esc_html__( 'Uploaded to this item', 'landinghub-core' ),
	'items_list'            => esc_html__( 'Items list', 'landinghub-core' ),
	'items_list_navigation' => esc_html__( 'Items list navigation', 'landinghub-core' ),
	'filter_items_list'     => esc_html__( 'Filter items list', 'landinghub-core' ),
);
$args = array(
	'label'                 => esc_html__( 'Mega Menu', 'landinghub-core' ),
	'labels'                => $labels,
	'supports'              => array( 'title', 'editor', 'revisions', ),
	'hierarchical'          => false,
	'public'                => true,
	'show_ui'               => true,
	'show_in_menu'          => true,
	'menu_position'         => 25,
	'menu_icon'             => 'dashicons-align-center',
	'show_in_admin_bar'     => true,
	'show_in_nav_menus'     => false,
	'can_export'            => true,
	'has_archive'           => false,
	'exclude_from_search'   => true,
	'publicly_queryable'    => true,
	'rewrite'               => false,
	'capability_type'       => 'page',
);
register_post_type( 'liquid-mega-menu', $args );
