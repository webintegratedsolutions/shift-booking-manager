<?php
/*
 * Shift Booking Manager
 * Calendar Display
 * Displays a calendar grid for booking shifts
 * Handles month/year navigation and shift status display
 * 
 * @package Shift Booking Manager
 */

defined('ABSPATH') || exit;

// Get current month & year
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');

// Get shifts for month
$calendar_data = sbm_get_shifts_for_month($year, sprintf('%02d', $month));

// Setup calendar grid
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$first_day = date('w', strtotime("$year-$month-01")); // 0=Sunday

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
            $label = $status === 'booked' ? 'Booked' : 'Book';
            $link = $status === 'open' ? "/book-a-shift/?shift_id={$shift['id']}" : '#';
            echo "<div class='sbm-shift {$class}'><a href='{$link}'>{$shift['start_time']} - {$label}</a></div>";
        }
    }

    echo "</div>";
}

echo "</div>"; // end grid
echo "</div>"; // end calendar
