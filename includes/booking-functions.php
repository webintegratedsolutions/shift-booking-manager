<?php
/**
 * Booking logic for Shift Booking Manager
 * Handles the core logic of booking a shift, including:
 * Validating form submissions
 * Saving client data to the shift post
 * Updating the shift status to booked
 * Preventing double bookings
 * Triggering email notifications via do_action()
 * Role check and nonce verification
 * Saves all client info securely
 * Redirects on success
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

/// Function to get shifts for a specific month and year
/**
 * Retrieves shifts for a given month and year
 *
 * @param int $year The year to retrieve shifts for
 * @param int $month The month to retrieve shifts for
 * @param int|null $provider_id Optional provider ID to filter by
 * @return array Array of shifts for the specified month and year
 */
function sbm_get_shifts_for_month($year, $month, $provider_id = null) {
    $start_date = "$year-$month-01";
    $end_date = date('Y-m-t', strtotime($start_date)); // last day of month

    $query_args = [
        'post_type' => 'shift',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => 'shift_date',
                'value' => [$start_date, $end_date],
                'compare' => 'BETWEEN',
                'type' => 'DATE',
            ],
        ],
    ];

    if ($provider_id) {
        $query_args['meta_query'][] = [
            'key' => 'provider_id',
            'value' => $provider_id,
            'compare' => '=',
        ];
    }

    $shifts = get_posts($query_args);

    $calendar_data = [];

    foreach ($shifts as $shift) {
        $date = get_post_meta($shift->ID, 'shift_date', true);
        $calendar_data[$date][] = [
            'id' => $shift->ID,
            'start_time' => get_post_meta($shift->ID, 'start_time', true),
            'status' => get_post_meta($shift->ID, 'status', true),
            'service' => get_post_meta($shift->ID, 'service', true),
        ];
    }

    return $calendar_data;
}

