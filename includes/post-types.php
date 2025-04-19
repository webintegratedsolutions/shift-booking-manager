<?php
/**
 * Register Custom Post Types
 * 
 * This Includes:
 * Labels for admin use (if needed)
 * supports: title (minimal—most shift data stored in post meta)
 * Fully private (not queryable publicly or shown in menus by default)
 * Can be surfaced in WP Admin later if desired
 */

defined('ABSPATH') || exit;

function sbm_register_post_types() {
    $labels = [
        'name'                  => _x('Shifts', 'Post Type General Name', 'shift-booking-manager'),
        'singular_name'         => _x('Shift', 'Post Type Singular Name', 'shift-booking-manager'),
        'menu_name'             => __('Shifts', 'shift-booking-manager'),
        'name_admin_bar'        => __('Shift', 'shift-booking-manager'),
        'add_new'               => __('Add New', 'shift-booking-manager'),
        'add_new_item'          => __('Add New Shift', 'shift-booking-manager'),
        'edit_item'             => __('Edit Shift', 'shift-booking-manager'),
        'new_item'              => __('New Shift', 'shift-booking-manager'),
        'view_item'             => __('View Shift', 'shift-booking-manager'),
        'search_items'          => __('Search Shifts', 'shift-booking-manager'),
        'not_found'             => __('No shifts found', 'shift-booking-manager'),
        'not_found_in_trash'    => __('No shifts found in Trash', 'shift-booking-manager'),
    ];

    $args = [
        'label'                 => __('Shifts', 'shift-booking-manager'),
        'description'           => __('Service provider shift availability', 'shift-booking-manager'),
        'labels'                => $labels,
        'supports'              => ['title', 'editor', 'custom-fields'], // Additional fields managed via post_meta
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true, // Admin only, hidden if using front-end only
        'show_in_menu'          => true, // Can be exposed optionally
        'menu_icon'             => 'dashicons-calendar-alt',
        'menu_position'         => 20,
        'show_in_admin_bar'     => false,
        'show_in_nav_menus'     => false,
        'can_export'            => true,
        'has_archive'           => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
        'map_meta_cap'          => true,

    ];

    register_post_type('shift', $args);
}
add_action('init', 'sbm_register_post_types');
