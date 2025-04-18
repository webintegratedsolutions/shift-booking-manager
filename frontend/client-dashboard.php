<?php
// Template Name: Client Dashboard
// Description: A template for the client dashboard to view booked shifts and cancel them.
// This template is used to display the client dashboard for viewing and managing booked shifts.
// It includes functionality to cancel booked shifts and view upcoming and past shifts.
// It is designed to be used within a WordPress theme and requires the WordPress environment to function properly.
// It is important to ensure that this template is only accessible to logged-in users with the appropriate permissions.

// Prevent direct access to the file
defined('ABSPATH') || exit;

// Check if the user is logged in and has the 'contributor' role
if (!is_user_logged_in() || !current_user_can('contributor')) {
    echo '<p>You must be logged in as a client to view this page.</p>';
    return;
}

$current_user_id = get_current_user_id();

// Get upcoming and past shifts
$today = date('Y-m-d');
$args = [
    'post_type' => 'shift',
    'posts_per_page' => -1,
    'meta_query' => [
        [
            'key' => 'client_id',
            'value' => $current_user_id,
            'compare' => '='
        ]
    ],
    'orderby' => 'meta_value',
    'meta_key' => 'shift_date',
    'order' => 'ASC'
];

// Check if a shift cancellation is requested
$shifts = get_posts($args);

if (empty($shifts)) {
    echo '<p>No booked shifts yet.</p>';
    return;
}

// Handle cancellation of booked shifts
echo '<h2>Your Booked Shifts</h2>';
echo '<table class="sbm-client-booking-table">';
echo '<thead><tr><th>Date</th><th>Time</th><th>Service</th><th>Status</th><th>Action</th></tr></thead><tbody>';

// Loop through booked shifts and display them
foreach ($shifts as $shift) {
    $date = get_post_meta($shift->ID, 'shift_date', true);
    $start = get_post_meta($shift->ID, 'start_time', true);
    $end = get_post_meta($shift->ID, 'end_time', true);
    $service = get_post_meta($shift->ID, 'service', true);
    $status = get_post_meta($shift->ID, 'status', true);

    $is_upcoming = ($date >= $today && $status === 'booked');

    echo '<tr>';
    echo '<td>' . esc_html(date('F j, Y', strtotime($date))) . '</td>';
    echo '<td>' . esc_html(date('g:i A', strtotime($start)) . ' - ' . date('g:i A', strtotime($end))) . '</td>';
    echo '<td>' . esc_html($service) . '</td>';
    echo '<td>' . ucfirst($status) . '</td>';

    if ($is_upcoming) {
        echo '<td><a href="' . esc_url(add_query_arg(['cancel_shift' => $shift->ID])) . '" class="sbm-cancel-button">Cancel</a></td>';
    } else {
        echo '<td>—</td>';
    }

    echo '</tr>';
}

echo '</tbody></table>';
