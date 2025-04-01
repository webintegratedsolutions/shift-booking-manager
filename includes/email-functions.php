<?php
/**
 * Email functions for Shift Booking Manager
 * Booking confirmation email to both parties
 * Cancellation notice with reason
 * Simple invoice email on shift completion
 * Prepared for customization & templating
 */

defined('ABSPATH') || exit;

/**
 * Send booking confirmation email to client and provider.
 */
function sbm_send_booking_confirmation_email($shift_id) {
    $client_email = get_post_meta($shift_id, 'client_email', true);
    $provider_id = get_post_meta($shift_id, 'provider_id', true);
    $provider_user = get_userdata($provider_id);

    $shift_date = get_post_meta($shift_id, 'shift_date', true);
    $start_time = get_post_meta($shift_id, 'start_time', true);
    $service = get_post_meta($shift_id, 'service', true);
    $rate = get_post_meta($shift_id, 'hourly_rate', true);

    $subject = __('Shift Booking Confirmed', 'shift-booking-manager');
    $headers = ['Content-Type: text/html; charset=UTF-8'];

    $message = "
        <h2>Booking Confirmation</h2>
        <p>Your booking has been confirmed.</p>
        <p><strong>Service:</strong> {$service}<br>
        <strong>Date:</strong> {$shift_date}<br>
        <strong>Time:</strong> {$start_time}<br>
        <strong>Rate:</strong> \${$rate}/hr</p>
    ";

    // Send to client
    wp_mail($client_email, $subject, $message, $headers);

    // Send to provider
    if ($provider_user && !empty($provider_user->user_email)) {
        wp_mail($provider_user->user_email, $subject, $message, $headers);
    }
}

/**
 * Send cancellation notification email to both parties.
 */
function sbm_send_cancellation_email($shift_id, $cancelled_by = 'client') {
    $client_email = get_post_meta($shift_id, 'client_email', true);
    $provider_id = get_post_meta($shift_id, 'provider_id', true);
    $provider_user = get_userdata($provider_id);

    $shift_date = get_post_meta($shift_id, 'shift_date', true);
    $start_time = get_post_meta($shift_id, 'start_time', true);
    $service = get_post_meta($shift_id, 'service', true);

    $subject = __('Shift Booking Canceled', 'shift-booking-manager');
    $headers = ['Content-Type: text/html; charset=UTF-8'];

    $message = "
        <h2>Shift Cancellation</h2>
        <p>The following shift has been canceled.</p>
        <p><strong>Service:</strong> {$service}<br>
        <strong>Date:</strong> {$shift_date}<br>
        <strong>Time:</strong> {$start_time}</p>
        <p><em>Cancelled by: {$cancelled_by}</em></p>
    ";

    if ($client_email) wp_mail($client_email, $subject, $message, $headers);
    if ($provider_user && !empty($provider_user->user_email)) {
        wp_mail($provider_user->user_email, $subject, $message, $headers);
    }
}

/**
 * Send invoice email after shift completion.
 */
function sbm_send_invoice_email($shift_id) {
    $client_email = get_post_meta($shift_id, 'client_email', true);
    $provider_id = get_post_meta($shift_id, 'provider_id', true);
    $provider_email = get_user_meta($provider_id, 'invoice_payment_email', true);
    $payment_method = get_user_meta($provider_id, 'payment_method', true);

    $shift_date = get_post_meta($shift_id, 'shift_date', true);
    $start_time = get_post_meta($shift_id, 'start_time', true);
    $service = get_post_meta($shift_id, 'service', true);
    $rate = get_post_meta($shift_id, 'hourly_rate', true);

    $total = $rate; // For now; you can multiply by duration if needed

    $subject = __('Invoice for Completed Service', 'shift-booking-manager');
    $headers = ['Content-Type: text/html; charset=UTF-8'];

    $message = "
        <h2>Invoice</h2>
        <p>Thank you for your recent appointment. Below are your invoice details:</p>
        <p><strong>Service:</strong> {$service}<br>
        <strong>Date:</strong> {$shift_date}<br>
        <strong>Time:</strong> {$start_time}<br>
        <strong>Total Due:</strong> \${$total}</p>
        <p>Please submit payment via <strong>{$payment_method}</strong> to:</p>
        <p><strong>{$provider_email}</strong></p>
    ";

    wp_mail($client_email, $subject, $message, $headers);

    // Optional: flag shift as "invoice_sent"
    update_post_meta($shift_id, 'invoice_sent', 'yes');
    update_post_meta($shift_id, 'invoice_sent_date', current_time('mysql'));
}
