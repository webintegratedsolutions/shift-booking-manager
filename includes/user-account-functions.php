<?php
/**
 * Shift Booking Manager - Client Registration and Account Managment Handler
 * @package Shift Booking Manager
 * @version 1.0.0
 * User account functions: registration, profile updates, login helpers
 */

defined('ABSPATH') || exit;

/**
 * Handle client registration form submission
 */
function sbm_handle_client_registration() {
    if (
        !isset($_POST['sbm_register_nonce']) ||
        !wp_verify_nonce($_POST['sbm_register_nonce'], 'sbm_register_client')
    ) {
        wp_die(__('Security check failed', 'shift-booking-manager'));
    }

    // Sanitize input
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);
    $email = sanitize_email($_POST['email']);
    $phone = sanitize_text_field($_POST['phone']);
    $address = sanitize_textarea_field($_POST['address']);
    $notes = sanitize_textarea_field($_POST['notes']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        wp_die(__('Passwords do not match.', 'shift-booking-manager'));
    }

    if (email_exists($email) || username_exists($email)) {
        wp_die(__('A user with this email already exists.', 'shift-booking-manager'));
    }

    $user_id = wp_create_user($email, $password, $email);
    if (is_wp_error($user_id)) {
        wp_die(__('User registration failed.', 'shift-booking-manager'));
    }

    // Set role and update user meta
    wp_update_user([
        'ID' => $user_id,
        'first_name' => $first_name,
        'last_name'  => $last_name,
        'display_name' => $first_name . ' ' . $last_name,
        'role' => 'contributor',
    ]);

    update_user_meta($user_id, 'phone_number', $phone);
    update_user_meta($user_id, 'address', $address);
    update_user_meta($user_id, 'notes', $notes);

    // Auto-login the new user
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);

    // Redirect to calendar or dashboard
    wp_redirect(home_url('/shift-calendar/?registered=1'));
    exit;
}
add_action('admin_post_nopriv_sbm_register_client', 'sbm_handle_client_registration');
