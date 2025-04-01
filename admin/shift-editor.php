<?php
/**
 * Shift Booking Manager
 * 
 * This file contains the code for the Shift Booking Manager plugin.
 * It includes functionality for managing shifts, including creating, editing, and displaying shifts.
 * The plugin is designed to work with WordPress and provides a user-friendly interface for managing shifts.
 * 
 * @package Shift Booking Manager
 */

// Prevent direct access to the file
defined('ABSPATH') || exit;

/**
 * Register the Shift Details metabox
 */
function sbm_add_shift_meta_box() {
    add_meta_box(
        'sbm_shift_details',
        __('Shift Details', 'shift-booking-manager'),
        'sbm_render_shift_meta_box',
        'shift',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'sbm_add_shift_meta_box');

/**
 * Render the Shift Details metabox content
 */
function sbm_render_shift_meta_box($post) {
    $date = get_post_meta($post->ID, 'shift_date', true);
    $start = get_post_meta($post->ID, 'start_time', true);
    $end = get_post_meta($post->ID, 'end_time', true);
    $service = get_post_meta($post->ID, 'service', true);
    $client_id = get_post_meta($post->ID, 'client_id', true);
    $status = get_post_meta($post->ID, 'status', true);
    $rate = get_post_meta($post->ID, 'hourly_rate', true);
    $provider_id = get_post_meta($post->ID, 'provider_id', true);

    wp_nonce_field('sbm_save_shift_meta', 'sbm_shift_nonce');

    ?>
    <p>
        <label>Date:</label><br>
        <input type="date" name="shift_date" value="<?php echo esc_attr($date); ?>">
    </p>
    <p>
        <label>Start Time:</label><br>
        <input type="time" name="start_time" value="<?php echo esc_attr($start); ?>">
    </p>
    <p>
        <label>End Time:</label><br>
        <input type="time" name="end_time" value="<?php echo esc_attr($end); ?>">
    </p>
    <p>
        <label>Service:</label><br>
        <input type="text" name="service" value="<?php echo esc_attr($service); ?>">
    </p>
    <p>
        <label>Hourly Rate:</label><br>
        <input type="number" name="hourly_rate" value="<?php echo esc_attr($rate); ?>" step="0.01">
    </p>
    <p>
        <label>Status:</label><br>
        <select name="status">
            <option value="open" <?php selected($status, 'open'); ?>>Open</option>
            <option value="booked" <?php selected($status, 'booked'); ?>>Booked</option>
            <option value="completed" <?php selected($status, 'completed'); ?>>Completed</option>
            <option value="cancelled" <?php selected($status, 'cancelled'); ?>>Cancelled</option>
        </select>
    </p>

    <p>
    <label>Provider:</label><br>
    <select name="provider_id">
        <option value="">Select a provider</option>
        <?php
        $providers = get_users(['role' => 'editor']);
        foreach ($providers as $provider) {
            $selected = selected($provider_id, $provider->ID, false);
            echo "<option value='{$provider->ID}' {$selected}>{$provider->display_name} ({$provider->user_email})</option>";
        }
        ?>
    </select>
    </p>

    <p>
    <label>Client:</label><br>
    <select name="client_id">
        <option value="">Select a client</option>
        <?php
        $clients = get_users(['role' => 'contributor']);
        foreach ($clients as $client) {
            $selected = selected($client_id, $client->ID, false);
            echo "<option value='{$client->ID}' {$selected}>{$client->display_name} ({$client->user_email})</option>";
        }
        ?>
    </select>
    </p>

    <?php
}

/**
 * Save shift meta when the post is saved
 */
function sbm_save_shift_meta_box($post_id) {
    // Verify nonce
    if (!isset($_POST['sbm_shift_nonce']) || !wp_verify_nonce($_POST['sbm_shift_nonce'], 'sbm_save_shift_meta')) {
        return;
    }

    // Prevent autosave from overwriting
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    // Check user permission
    if (!current_user_can('edit_post', $post_id)) return;

    // Validation: if status is booked, both client and provider must be selected
    if (
        isset($_POST['status']) &&
        $_POST['status'] === 'booked' &&
        (empty($_POST['client_id']) || empty($_POST['provider_id']))
    ) {
        // If the status is booked but either client or provider is not selected, show an error message
        // and prevent the post from being saved.
        $edit_link = get_edit_post_link($post_id, ''); // Get link to return to the edit screen
        $message = __('You must select both a client and provider to mark this shift as booked.', 'shift-booking-manager');
        $message .= '<br><br><a href="' . esc_url($edit_link) . '" style="font-weight:bold;">&laquo; Go back to edit shift</a>';

        wp_die($message, __('Shift Booking Error', 'shift-booking-manager'), ['back_link' => false]);

    }

    // Define meta fields to update
    $fields = [
        'shift_date' => 'sanitize_text_field',
        'start_time' => 'sanitize_text_field',
        'end_time' => 'sanitize_text_field',
        'service' => 'sanitize_text_field',
        'hourly_rate' => 'floatval',
        'status' => 'sanitize_text_field',
        'provider_id' => 'intval',
        'client_id' => 'intval',
    ];

    foreach ($fields as $key => $sanitize_function) {
        if (isset($_POST[$key])) {
            $value = call_user_func($sanitize_function, $_POST[$key]);
            update_post_meta($post_id, $key, $value);
        }
    }
}
add_action('save_post_shift', 'sbm_save_shift_meta_box');


