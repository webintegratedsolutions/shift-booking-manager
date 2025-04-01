<?php
/*
 * Booking Form Template
 * This template is used to display the booking form for a shift.
 *
 * @package Shift Booking Manager
 * 
 * Be secure (nonce + user checks)
 * Pre-fill client info if logged in
 * Allow booking an individual shift
 */

// Prevent direct access to the file
defined('ABSPATH') || exit;

// Ensure a shift ID is passed
$shift_id = absint($_GET['shift_id'] ?? 0);
if (!$shift_id || get_post_type($shift_id) !== 'shift') {
    echo '<p>' . esc_html__('Invalid shift.', 'shift-booking-manager') . '</p>';
    return;
}

// Check if already booked
$status = get_post_meta($shift_id, 'status', true);
if ($status !== 'open') {
    echo '<p>' . esc_html__('This shift is no longer available.', 'shift-booking-manager') . '</p>';
    return;
}

// Get logged-in client info
$current_user = wp_get_current_user();
$client_name  = trim($current_user->first_name . ' ' . $current_user->last_name);
$client_email = $current_user->user_email;
$client_phone = get_user_meta($current_user->ID, 'phone_number', true);

// Output the form
?>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="sbm-booking-form">
    <input type="hidden" name="action" value="sbm_book_shift">
    <input type="hidden" name="sbm_booking_nonce" value="<?php echo wp_create_nonce('sbm_book_shift'); ?>">
    <input type="hidden" name="shift_id" value="<?php echo esc_attr($shift_id); ?>">

    <label for="client_name"><?php _e('Your Name', 'shift-booking-manager'); ?></label>
    <input type="text" name="client_name" id="client_name" value="<?php echo esc_attr($client_name); ?>" required>

    <label for="client_email"><?php _e('Email Address', 'shift-booking-manager'); ?></label>
    <input type="email" name="client_email" id="client_email" value="<?php echo esc_attr($client_email); ?>" required>

    <label for="client_phone"><?php _e('Phone Number', 'shift-booking-manager'); ?></label>
    <input type="text" name="client_phone" id="client_phone" value="<?php echo esc_attr($client_phone); ?>" required>

    <label for="client_message"><?php _e('Additional Notes (Optional)', 'shift-booking-manager'); ?></label>
    <textarea name="client_message" id="client_message" rows="4"></textarea>

    <button type="submit"><?php _e('Confirm Booking', 'shift-booking-manager'); ?></button>
</form>
