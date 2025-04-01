<?php
/**
 * Plugin Name: Shift Booking Manager
 * Description: A shift scheduling and booking system for service providers and their clients.
 * Version: 1.0.0
 * Author: Web Integrated Solutions
 * Author URI: https://webintegratedsolutions.com
 * License: GPL2+
 * Text Domain: shift-booking-manager
 */

defined('ABSPATH') || exit; // Exit if accessed directly

// Plugin Constants
define('SBM_VERSION', '1.0.0');
define('SBM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SBM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SBM_PLUGIN_FILE', __FILE__);

// Load Core Includes
require_once SBM_PLUGIN_DIR . 'includes/post-types.php';
require_once SBM_PLUGIN_DIR . 'includes/user-roles.php';
require_once SBM_PLUGIN_DIR . 'includes/booking-functions.php';
require_once SBM_PLUGIN_DIR . 'includes/email-functions.php';
require_once SBM_PLUGIN_DIR . 'includes/utilities.php';

// Admin Interfaces
if (is_admin()) {
    require_once SBM_PLUGIN_DIR . 'admin/settings-page.php';
    require_once SBM_PLUGIN_DIR . 'admin/provider-dashboard.php';
}

// Frontend Interfaces
// Require_once SBM_PLUGIN_DIR . 'frontend/calendar-display.php';
// Require_once SBM_PLUGIN_DIR . 'frontend/booking-form.php';
require_once SBM_PLUGIN_DIR . 'frontend/client-dashboard.php';
require_once SBM_PLUGIN_DIR . 'frontend/registration-form.php';

// Plugin Activation
function sbm_activate_plugin() {
    // Register custom post types, flush rewrite rules, add roles
    sbm_register_post_types();
    sbm_add_custom_roles();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'sbm_activate_plugin');

//Add Shortcode Wrapper for Booking Form
function sbm_booking_form_shortcode($atts) {
    ob_start();
    include SBM_PLUGIN_DIR . 'frontend/booking-form.php';
    return ob_get_clean();
}
add_shortcode('sbm_booking_form', 'sbm_booking_form_shortcode');

/**
 * Calendar Display Shortcode
 */
function sbm_calendar_shortcode($atts) {
    ob_start();
    include SBM_PLUGIN_DIR . 'frontend/calendar-display.php';
    return ob_get_clean();
}
add_shortcode('sbm_calendar', 'sbm_calendar_shortcode');

// Plugin Deactivation
function sbm_deactivate_plugin() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'sbm_deactivate_plugin');

// Enqueue Scripts and Styles
function sbm_enqueue_assets() {
    wp_enqueue_style(
        'sbm-styles',
        SBM_PLUGIN_URL . 'assets/css/style.css',
        [],
        SBM_VERSION
    );
}
add_action('wp_enqueue_scripts', 'sbm_enqueue_assets');

// Plugin Uninstall - defined in uninstall.php if needed
