<?php
/**
 * Booking logic for Shift Booking Manager
 * Handles the core logic of booking a shift, including:
 * Validating form submissions
 * Saving client data to the shift post
 * Updating the shift status to booked
 * Preventing double bookings
 * Triggering email notifications
 */

defined('ABSPATH') || exit;

/**
 * Handles booking form submissions
 */
function sbm_handle_shift_booking() {
    if (
        !isset($_POST['sbm_booking_nonce']) ||
        !wp_verify_nonce($_POST['sbm_booking_nonce'], 'sbm_book_shift')
    ) {
        return;
    }

    if (!is_user_logged_in() || !current_user_can('contributor')) {
        return;
    }

    $user_id = get_current_user_id();
    $shift_id = absint($_POST['shift_id']);

    // Get shift data
    $status = get_post_meta($shift_id, 'status', true);
    if ($status !== 'open') {
        wp_die(__('This shift is no longer available.', 'shift-booking-manager'));
    }

    // Sanitize user input
    $client_name = sanitize_text_field($_POST['client_name'] ?? '');
    $client_email = sanitize_email($_POST['client_email'] ?? '');
    $client_phone = sanitize_text_field($_POST['client_phone'] ?? '');
    $client_message = sanitize_textarea_field($_POST['client_message'] ?? '');

    // Update shift with client data
    update_post_meta($shift_id, 'client_id', $user_id);
    update_post_meta($shift_id, 'client_name', $client_name);
    update_post_meta($shift_id, 'client_email', $client_email);
    update_post_meta($shift_id, 'client_phone', $client_phone);
    update_post_meta($shift_id, 'client_message', $client_message);
    update_post_meta($shift_id, 'status', 'booked');
    update_post_meta($shift_id, 'booking_timestamp', current_time('mysql'));

    // Optional: update the post title (if helpful for listing)
    $title = get_the_title($shift_id);
    wp_update_post([
        'ID' => $shift_id,
        'post_title' => $title . ' - Booked by ' . $client_name,
    ]);

    // Trigger booking email
    do_action('sbm_shift_booked', $shift_id);

    // Redirect or show message (can be handled with JS)
    wp_redirect(add_query_arg('booking', 'success', wp_get_referer()));
    exit;
}
add_action('admin_post_sbm_book_shift', 'sbm_handle_shift_booking');
add_action('admin_post_nopriv_sbm_book_shift', 'sbm_handle_shift_booking');
