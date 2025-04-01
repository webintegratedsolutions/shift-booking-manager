<?php
/*
 * Shift Booking Manager
 * Calendar Display
 * Displays a calendar grid for booking shifts
 * Handles month/year navigation and shift status display
 * Detect the current year and month from the query string
 * Add "Prev" and "Next" buttons that adjust the month
 * Wrap it in a simple UI block above the calendar
 * 
 * @package Shift Booking Manager
 */

defined('ABSPATH') || exit;

// Get current month & year
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');

// Determine prev/next months
$current = mktime(0, 0, 0, $month, 1, $year);
$prev = strtotime("-1 month", $current);
$next = strtotime("+1 month", $current);

$prev_year = date('Y', $prev);
$prev_month = date('m', $prev);

$next_year = date('Y', $next);
$next_month = date('m', $next);

$calendar_url = get_permalink();

// Get shifts for month
$calendar_data = sbm_get_shifts_for_month($year, sprintf('%02d', $month));

// Setup calendar grid
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$first_day = date('w', strtotime("$year-$month-01")); // 0=Sunday

// Create calendar grid
echo '<div class="sbm-calendar-nav" style="margin-bottom: 1em;">';
echo '<a href="' . esc_url(add_query_arg(['month' => $prev_month, 'year' => $prev_year], $calendar_url)) . '" class="sbm-nav-button">&laquo; Previous</a>';
echo '<a href="' . esc_url(add_query_arg(['month' => $next_month, 'year' => $next_year], $calendar_url)) . '" class="sbm-nav-button" style="float:right;">Next &raquo;</a>';
echo '</div>';

// Display month/year header
echo "<h2>" . date('F Y', strtotime("$year-$month-01")) . "</h2>";
echo "<div class='sbm-calendar'>";
echo "<div class='sbm-calendar-grid'>";

// Days of week headers
$days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
foreach ($days as $day) {
    echo "<div class='sbm-calendar-cell sbm-calendar-header'>{$day}</div>";
}

// Blank cells before the first day of the month
for ($i = 0; $i < $first_day; $i++) {
    echo "<div class='sbm-calendar-cell empty'></div>";
}

// Calendar day cells
for ($day = 1; $day <= $days_in_month; $day++) {
    $date = "$year-" . sprintf('%02d', $month) . "-" . sprintf('%02d', $day);
    echo "<div class='sbm-calendar-cell'><strong>{$day}</strong>";

    if (!empty($calendar_data[$date])) {
        foreach ($calendar_data[$date] as $shift) {
            $status = $shift['status'];
            $class = $status === 'booked' ? 'booked' : 'open';
            $start_time = date('g:i A', strtotime($shift['start_time']));
            $end_time = date('g:i A', strtotime(get_post_meta($shift['id'], 'end_time', true)));
            $time_label = "{$start_time} – {$end_time}";
            $label = $status === 'booked' ? 'Booked' : 'Book';
            $link = $status === 'open' ? "/book-a-shift/?shift_id={$shift['id']}" : '#';
            echo "<div class='sbm-shift {$class}'><a href='{$link}'>{$time_label}</a></div>";
        }
    }

    echo "</div>";
}

echo "</div>"; // end grid
echo "</div>"; // end calendar
