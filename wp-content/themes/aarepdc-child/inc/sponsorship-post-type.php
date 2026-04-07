<?php
/* Sponsorship Custom Post Type Start */

add_action('init', 'sponsor_post_type');
function sponsor_post_type() {
	$labels = array(
		'name'               => _x( 'Sponsorship', 'aarepdc' ),
		'singular_name'      => _x( 'Sponsorship', 'aarepdc' ),
		'menu_name'          => _x( 'Sponsorship', 'aarepdc' ),
		'name_admin_bar'     => _x( 'Sponsorship', 'aarepdc' ),
		'add_new'            => _x( 'Add Sponsorship', 'aarepdc' ),
		'add_new_item'       => __( 'Add New Sponsorship', 'aarepdc' ),
		'new_item'           => __( 'New Sponsorship', 'aarepdc' ),
		'edit_item'          => __( 'Edit Sponsorship', 'aarepdc' ),
		'view_item'          => __( 'View Sponsorship', 'aarepdc' ),
		'all_items'          => __( 'All Sponsorship', 'aarepdc' ),
		'search_items'       => __( 'Search Sponsorship', 'aarepdc' ),
		'parent_item_colon'  => __( 'Parent Sponsorship:', 'aarepdc' ),
		'not_found'          => __( 'No Sponsorship found.', 'aarepdc' ),
		'not_found_in_trash' => __( 'No Sponsorship found in Trash.', 'aarepdc' )
	);

	$args = array(
		'labels'             => $labels,
        'description'        => __( 'Description.', 'aarepdc' ),
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'sponsorships' ),
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => null,
		'supports'           => array( 'title', 'editor' )
	);
	register_post_type( 'sponsorship', $args );

// Add Custom Taxonomy for Board Member
$labels = array(
	'name'                       => _x( 'Categories', 'aarepdc' ),
	'singular_name'              => _x( 'Categories', 'aarepdc' ),
	'search_items'               => __( 'Search Categories', 'aarepdc' ),
	'popular_items'              => __( 'Popular Categories', 'aarepdc' ),
	'all_items'                  => __( 'All Categories', 'aarepdc' ),
	'parent_item'       		 => __( 'Parent Categories', 'textdomain' ),
	'parent_item_colon' 		 => __( 'Parent Categories:', 'textdomain' ),
	'edit_item'                  => __( 'Edit Categories', 'aarepdc' ),
	'update_item'                => __( 'Update Categories', 'aarepdc' ),
	'add_new_item'               => __( 'Add New Categories', 'aarepdc' ),
	'new_item_name'              => __( 'New Categories Name', 'aarepdc' ),
	'separate_items_with_commas' => __( 'Separate Categories with commas', 'aarepdc' ),
	'add_or_remove_items'        => __( 'Add or remove Categories', 'aarepdc' ),
	'choose_from_most_used'      => __( 'Choose from the most used Categories', 'aarepdc' ),
	'not_found'                  => __( 'No Categories found.', 'aarepdc' ),
	'menu_name'                  => __( 'Categories', 'aarepdc' ),
);	

$args = array(
	'hierarchical'      => true,
	'labels'            => $labels,
	'show_ui'           => true,
	'show_admin_column' => true,
	'query_var'         => true,
	'rewrite'           => array( 'slug' => 'sponsorships-category' ),
);

register_taxonomy( 'sponsorships-category', 'sponsorship', $args );
}