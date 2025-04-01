<?php
defined('ABSPATH') || exit;

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

$shifts = get_posts($args);

if (empty($shifts)) {
    echo '<p>No booked shifts yet.</p>';
    return;
}

echo '<h2>Your Booked Shifts</h2>';
echo '<table class="sbm-client-booking-table">';
echo '<thead><tr><th>Date</th><th>Time</th><th>Service</th><th>Status</th><th>Action</th></tr></thead><tbody>';

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
