<?php
/**
 * Setup and manage user roles and capabilities.
 * Ensuring the Contributor role is used for Clients
 * Confirming or modifying the Editor role for Service Providers
 * Optionally adding custom capabilities if needed later
 */

defined('ABSPATH') || exit;

/**
 * Add or modify roles and capabilities for the plugin.
 */
function sbm_add_custom_roles() {
    // Ensure Contributor role is enabled for clients
    $contributor = get_role('contributor');
    if ($contributor) {
        // Add custom capabilities if needed
        // $contributor->add_cap('read_shift');
    }

    // Service providers will use the Editor role
    $editor = get_role('editor');
    if ($editor) {
        // Add custom capabilities if needed
        // $editor->add_cap('manage_shifts');
        // $editor->add_cap('view_own_shift_calendar');
    }

    // Optional: Add a custom role for future use
    // add_role('service_provider', 'Service Provider', [
    //     'read' => true,
    //     'edit_posts' => false,
    //     'upload_files' => true,
    // ]);
}
add_action('init', 'sbm_add_custom_roles');

/**
 * Remove custom roles or caps on uninstall if needed.
 */
function sbm_remove_custom_roles() {
    // remove_role('service_provider'); // If custom role added
    // $contributor = get_role('contributor');
    // $contributor->remove_cap('read_shift');
}
