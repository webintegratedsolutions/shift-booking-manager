<?php
/*
 * Registration Form Template
 * This template is used to display the registration form for clients.
 *
 * @package Shift Booking Manager
 * @since 1.0.0
 */

// Prevent direct access to the file
defined('ABSPATH') || exit;

if (is_user_logged_in()) {
    echo '<p>You are already logged in.</p>';
    return;
}
?>

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="sbm-registration-form">
    <input type="hidden" name="action" value="sbm_register_client">
    <input type="hidden" name="sbm_register_nonce" value="<?php echo wp_create_nonce('sbm_register_client'); ?>">

    <label for="first_name"><?php _e('First Name', 'shift-booking-manager'); ?></label>
    <input type="text" name="first_name" id="first_name" required>

    <label for="last_name"><?php _e('Last Name', 'shift-booking-manager'); ?></label>
    <input type="text" name="last_name" id="last_name" required>

    <label for="email"><?php _e('Email Address', 'shift-booking-manager'); ?></label>
    <input type="email" name="email" id="email" required>

    <label for="phone"><?php _e('Phone Number', 'shift-booking-manager'); ?></label>
    <input type="text" name="phone" id="phone" required>

    <label for="address"><?php _e('Address (Optional)', 'shift-booking-manager'); ?></label>
    <textarea name="address" id="address"></textarea>

    <label for="notes"><?php _e('Notes (Optional)', 'shift-booking-manager'); ?></label>
    <textarea name="notes" id="notes"></textarea>

    <label for="password"><?php _e('Password', 'shift-booking-manager'); ?></label>
    <input type="password" name="password" id="password" required>

    <label for="confirm_password"><?php _e('Confirm Password', 'shift-booking-manager'); ?></label>
    <input type="password" name="confirm_password" id="confirm_password" required>

    <button type="submit"><?php _e('Register as a Client', 'shift-booking-manager'); ?></button>
</form>
