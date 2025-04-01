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
 * Remove title input field for shift post type
 */
function sbm_hide_shift_title_field() {
    $screen = get_current_screen();
    if ($screen->post_type === 'shift') {
        echo '<style>#titlediv { display: none; }</style>';
    }
}
add_action('admin_head', 'sbm_hide_shift_title_field');


/**
 * Save shift meta when the post is saved
 */
function sbm_save_shift_meta_box($post_id) {
    // Verify nonce
    if (!isset($_POST['sbm_shift_nonce']) || !wp_verify_nonce($_POST['sbm_shift_nonce'], 'sbm_save_shift_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $edit_link = get_edit_post_link($post_id, '');

    // Gather submitted values
    $shift_date = sanitize_text_field($_POST['shift_date'] ?? '');
    $start_time = sanitize_text_field($_POST['start_time'] ?? '');
    $end_time = sanitize_text_field($_POST['end_time'] ?? '');
    $service = sanitize_text_field($_POST['service'] ?? '');
    $rate = floatval($_POST['hourly_rate'] ?? 0);
    $status = sanitize_text_field($_POST['status'] ?? 'open');
    $provider_id = intval($_POST['provider_id'] ?? 0);
    $client_id = intval($_POST['client_id'] ?? 0);

    // Get existing shift data (before update)
    $is_existing_shift = get_post_status($post_id) !== false;
    $original_status = get_post_meta($post_id, 'status', true);

    // === VALIDATION RULES ===

    // Mandatory fields when adding or updating shift
    if (empty($shift_date) || empty($start_time) || empty($end_time) || empty($service) || $rate <= 0) {
        $message = __('Date, Start Time, End Time, Service, and Hourly Rate are required fields.', 'shift-booking-manager');
        $message .= '<br><br><a href="' . esc_url($edit_link) . '">&laquo; Go back to edit shift</a>';
        wp_die($message, __('Missing Required Fields', 'shift-booking-manager'), ['back_link' => false]);
    }

    // Validate time: Start must be at least 1 hour in the future
    $now = current_time('timestamp'); // current WP time
    $start_timestamp = strtotime("$shift_date $start_time");

    if ($start_timestamp < $now + HOUR_IN_SECONDS) {
        $message = __('Start time must be at least 1 hour in the future.', 'shift-booking-manager');
        $message .= '<br><br><a href="' . esc_url($edit_link) . '">&laquo; Go back to edit shift</a>';
        wp_die($message, __('Invalid Shift Time', 'shift-booking-manager'), ['back_link' => false]);
    }

    // Can't mark as "booked" without client and provider
    if (
        $status === 'booked' &&
        (empty($client_id) || empty($provider_id))
    ) {
        $message = __('You must select both a client and provider to mark this shift as booked.', 'shift-booking-manager');
        $message .= '<br><br><a href="' . esc_url($edit_link) . '">&laquo; Go back to edit shift</a>';
        wp_die($message, __('Shift Booking Error', 'shift-booking-manager'), ['back_link' => false]);
    }

    // Can't set status back to open if client & provider are selected
    if (
        $status === 'open' &&
        !empty($client_id) &&
        !empty($provider_id)
    ) {
        $message = __('A shift cannot remain open if both a client and provider are already selected.', 'shift-booking-manager');
        $message .= '<br><br><a href="' . esc_url($edit_link) . '">&laquo; Go back to edit shift</a>';
        wp_die($message, __('Shift Status Conflict', 'shift-booking-manager'), ['back_link' => false]);
    }

    // If the shift is already booked/completed and not being cancelled, lock it
    $locked_statuses = ['booked', 'completed'];
    if (
        $is_existing_shift &&
        in_array($original_status, $locked_statuses) &&
        $status !== 'cancelled'
    ) {
        // Prevent editing once booked or completed, unless cancelling
        $locked_fields = [
            'shift_date', 'start_time', 'end_time', 'service', 'hourly_rate', 'provider_id', 'client_id'
        ];

        foreach ($locked_fields as $field) {
            if (!empty($_POST[$field])) {
                $submitted = sanitize_text_field($_POST[$field]);
                $existing = get_post_meta($post_id, $field, true);
                if ((string)$submitted !== (string)$existing) {
                    $message = __('You cannot modify a booked or completed shift. It can only be cancelled.', 'shift-booking-manager');
                    $message .= '<br><br><a href="' . esc_url($edit_link) . '">&laquo; Go back to edit shift</a>';
                    wp_die($message, __('Shift Locked', 'shift-booking-manager'), ['back_link' => false]);
                }
            }
        }
    }

    // Enqueue admin script for validation
    // Only enqueue if the user is on the edit screen for the shift post type
    function sbm_enqueue_admin_shift_script($hook) {
        $screen = get_current_screen();
        if ($screen->post_type === 'shift') {
            wp_enqueue_script(
                'sbm-shift-validation',
                SBM_PLUGIN_URL . 'assets/js/admin-shift-validation.js',
                [],
                SBM_VERSION,
                true
            );
        }
    }
    add_action('admin_enqueue_scripts', 'sbm_enqueue_admin_shift_script');

    // === SAVE FIELDS ===
    $fields = [
        'shift_date' => $shift_date,
        'start_time' => $start_time,
        'end_time' => $end_time,
        'service' => $service,
        'hourly_rate' => $rate,
        'status' => $status,
        'provider_id' => $provider_id,
        'client_id' => $client_id,
    ];

    foreach ($fields as $key => $value) {
        update_post_meta($post_id, $key, $value);
    }

    // Auto-generate post title from date and time
    if (!empty($shift_date) && !empty($start_time) && !empty($end_time)) {
        $formatted_date = date('F jS', strtotime($shift_date));
        $formatted_start = date('g:i A', strtotime($start_time));
        $formatted_end = date('g:i A', strtotime($end_time));
        $auto_title = "{$formatted_date} @ {$formatted_start} to {$formatted_end}";

        wp_update_post([
            'ID' => $post_id,
            'post_title' => $auto_title,
        ]);

    }
}
add_action('save_post_shift', 'sbm_save_shift_meta_box');

