<?php
/**
 * Shift Booking Manager - Shift Editor
 * 
 * Admin metabox and save logic for the Shift post type.
 */

defined('ABSPATH') || exit;

/**
 * Render time options in 15-minute increments
 */
function sbm_render_time_options($selected = '') {
    error_log('--- Rendering Shift Metabox ---');
    error_log('Rendering time selects...');

    $output = '';
    $start = strtotime('00:00');
    $end = strtotime('23:45');

    for ($time = $start; $time <= $end; $time += 900) {
        $value = date('H:i', $time);
        $label = date('g:i A', $time);
        $is_selected = selected($selected, $value, false);
        $output .= "<option value='{$value}' {$is_selected}>{$label}</option>";
    }

    error_log('--- sbm_render_time_options finished ---');
    return $output;
}

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
    <p><label>Date:</label><br>
        <input type="date" name="shift_date" value="<?php echo esc_attr($date); ?>">
    </p>
    <p><label>Start Time:</label><br>
    <select name="start_time">
        <option value="">-- Select Start Time --</option>
        <?php echo sbm_render_time_options($start); ?>
    </select>
    </p>
    <p><label>End Time:</label><br>
    <select name="end_time">
        <option value="">-- Select End Time --</option>
        <?php echo sbm_render_time_options($end); ?>
    </select>
    </p>
    <p><label>Service:</label><br>
        <input type="text" name="service" value="<?php echo esc_attr($service); ?>">
    </p>
    <p><label>Hourly Rate:</label><br>
        <input type="number" name="hourly_rate" value="<?php echo esc_attr($rate); ?>" step="0.01">
    </p>
    <p><label>Status:</label><br>
        <select name="status">
            <option value="open" <?php selected($status, 'open'); ?>>Open</option>
            <option value="booked" <?php selected($status, 'booked'); ?>>Booked</option>
            <option value="completed" <?php selected($status, 'completed'); ?>>Completed</option>
            <option value="cancelled" <?php selected($status, 'cancelled'); ?>>Cancelled</option>
        </select>
    </p>
    <p><label>Provider:</label><br>
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
    <p><label>Client:</label><br>
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
    global $sbm_shift_validation_error;
    $sbm_shift_validation_error = false;

    if (!isset($_POST['sbm_shift_nonce']) || !wp_verify_nonce($_POST['sbm_shift_nonce'], 'sbm_save_shift_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $edit_link = get_edit_post_link($post_id, '');

    $shift_date = sanitize_text_field($_POST['shift_date'] ?? '');
    $start_time = sanitize_text_field($_POST['start_time'] ?? '');
    $end_time = sanitize_text_field($_POST['end_time'] ?? '');
    $service = sanitize_text_field($_POST['service'] ?? '');
    $rate = floatval($_POST['hourly_rate'] ?? 0);
    $status = sanitize_text_field($_POST['status'] ?? 'open');
    $provider_id = intval($_POST['provider_id'] ?? 0);
    $client_id = intval($_POST['client_id'] ?? 0);

    $is_existing_shift = get_post_status($post_id) !== false;
    $original_status = get_post_meta($post_id, 'status', true);

    // === VALIDATION RULES ===

    // Required fields
    if (empty($shift_date) || empty($start_time) || empty($end_time) || empty($service) || $rate <= 0) {
        update_post_meta($post_id, '_sbm_shift_validation_failed', '1');
        $message = __('Date, Start Time, End Time, Service, and Hourly Rate are required fields.', 'shift-booking-manager');
        $message .= '<br><br><a href="' . esc_url($edit_link) . '">&laquo; Go back to edit shift</a>';
        wp_die($message, __('Missing Required Fields', 'shift-booking-manager'), ['back_link' => false]);
    }

    $now = current_time('timestamp');
    $start_timestamp = strtotime("$shift_date $start_time");

    if ($start_timestamp < $now + HOUR_IN_SECONDS) {
        update_post_meta($post_id, '_sbm_shift_validation_failed', '1');
        $message = __('Start time must be at least 1 hour from now.', 'shift-booking-manager');
        $message .= '<br><br><a href="' . esc_url($edit_link) . '">&laquo; Go back to edit shift</a>';
        wp_die($message, __('Invalid Shift Time', 'shift-booking-manager'), ['back_link' => false]);
    }

    // NEW: Validate End Time > Start Time
    $end_timestamp = strtotime("$shift_date $end_time");

    if ($end_timestamp <= $start_timestamp) {
        update_post_meta($post_id, '_sbm_shift_validation_failed', '1');
        $message = __('End time must be after the start time.', 'shift-booking-manager');
        $message .= '<br><br><a href="' . esc_url($edit_link) . '">&laquo; Go back to edit shift</a>';
        wp_die($message, __('Invalid End Time', 'shift-booking-manager'), ['back_link' => false]);
    }

    // NEW: Minimum 1 hour shift
    if (($end_timestamp - $start_timestamp) < HOUR_IN_SECONDS) {
        update_post_meta($post_id, '_sbm_shift_validation_failed', '1');
        $message = __('The shift must be at least 1 hour long.', 'shift-booking-manager');
        $message .= '<br><br><a href="' . esc_url($edit_link) . '">&laquo; Go back to edit shift</a>';
        wp_die($message, __('Shift Too Short', 'shift-booking-manager'), ['back_link' => false]);
    }

    // Can't be "booked" without both client and provider
    if ($status === 'booked' && (empty($client_id) || empty($provider_id))) {
        update_post_meta($post_id, '_sbm_shift_validation_failed', '1');
        $message = __('You must select both a client and provider to mark this shift as booked.', 'shift-booking-manager');
        $message .= '<br><br><a href="' . esc_url($edit_link) . '">&laquo; Go back to edit shift</a>';
        wp_die($message, __('Shift Booking Error', 'shift-booking-manager'), ['back_link' => false]);
    }

    // Can't set back to open if both provider and client are set
    if ($status === 'open' && !empty($client_id) && !empty($provider_id)) {
        update_post_meta($post_id, '_sbm_shift_validation_failed', '1');
        $message = __('A shift cannot remain open if both a client and provider are already selected.', 'shift-booking-manager');
        $message .= '<br><br><a href="' . esc_url($edit_link) . '">&laquo; Go back to edit shift</a>';
        wp_die($message, __('Shift Status Conflict', 'shift-booking-manager'), ['back_link' => false]);
    }

    // Prevent edits to booked/completed unless cancelling
    $locked_statuses = ['booked', 'completed'];
    if ($is_existing_shift && in_array($original_status, $locked_statuses) && $status !== 'cancelled') {
        $locked_fields = ['shift_date', 'start_time', 'end_time', 'service', 'hourly_rate', 'provider_id', 'client_id'];
        foreach ($locked_fields as $field) {
            if (!empty($_POST[$field])) {
                $submitted = sanitize_text_field($_POST[$field]);
                $existing = get_post_meta($post_id, $field, true);
                if ((string)$submitted !== (string)$existing) {
                    update_post_meta($post_id, '_sbm_shift_validation_failed', '1');
                    $message = __('You cannot modify a booked or completed shift. It can only be cancelled.', 'shift-booking-manager');
                    $message .= '<br><br><a href="' . esc_url($edit_link) . '">&laquo; Go back to edit shift</a>';
                    wp_die($message, __('Shift Locked', 'shift-booking-manager'), ['back_link' => false]);
                }
            }
        }
    }

    // === SAVE META FIELDS ===
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

    // === AUTO-GENERATE TITLE ===
    if (!empty($shift_date) && !empty($start_time) && !empty($end_time)) {
        $formatted_date = date('F jS', strtotime($shift_date));
        $formatted_start = date('g:i A', strtotime($start_time));
        $formatted_end = date('g:i A', strtotime($end_time));
        $auto_title = "{$formatted_date} @ {$formatted_start} to {$formatted_end}";

        remove_action('save_post_shift', 'sbm_save_shift_meta_box');
        wp_update_post([
            'ID' => $post_id,
            'post_title' => $auto_title,
        ]);
        add_action('save_post_shift', 'sbm_save_shift_meta_box');
    }

    delete_post_meta($post_id, '_sbm_shift_validation_failed');
}
add_action('save_post_shift', 'sbm_save_shift_meta_box');

/**
 * Pre-validate shift before insert (prevents invalid post save)
 */
function sbm_validate_shift_before_insert($data, $postarr) {

    if (
        $postarr['post_type'] !== 'shift' ||
        defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ||
        !isset($_POST['shift_date']) // User didn't click "Publish/Update"
    ) {
        return $data;
    }

    $errors = [];

    $shift_date = sanitize_text_field($postarr['shift_date'] ?? '');
    $start_time = sanitize_text_field($postarr['start_time'] ?? '');
    $end_time = sanitize_text_field($postarr['end_time'] ?? '');
    $service = sanitize_text_field($postarr['service'] ?? '');
    $rate = floatval($postarr['hourly_rate'] ?? 0);
    $status = sanitize_text_field($postarr['status'] ?? 'open');
    $provider_id = intval($postarr['provider_id'] ?? 0);
    $client_id = intval($postarr['client_id'] ?? 0);

    if (empty($shift_date)) $errors[] = 'Date is required.';
    if (empty($start_time)) $errors[] = 'Start Time is required.';
    if (empty($end_time)) $errors[] = 'End Time is required.';
    if (empty($service)) $errors[] = 'Service is required.';
    if ($rate <= 0) $errors[] = 'Hourly rate must be greater than 0.';

    if (!empty($shift_date) && !empty($start_time)) {
        $now = current_time('timestamp');
        $start_timestamp = strtotime("$shift_date $start_time");
        if ($start_timestamp < $now + HOUR_IN_SECONDS) {
            $errors[] = 'Start time must be at least 1 hour from now.';
        }
    }

    if ($status === 'booked' && (empty($provider_id) || empty($client_id))) {
        $errors[] = 'You must select both a provider and client to mark as booked.';
    }

    if ($status === 'open' && !empty($provider_id) && !empty($client_id)) {
        $errors[] = 'A shift cannot remain open with both a client and provider.';
    }

    if (!empty($errors)) {
        add_filter('redirect_post_location', function ($location) use ($errors) {
            return add_query_arg('sbm_shift_errors', urlencode(implode('|', $errors)), $location);
        });
        $data['post_status'] = 'draft';
        $data['post_title'] = '';
    }

    return $data;
}
//add_filter('wp_insert_post_data', 'sbm_validate_shift_before_insert', 10, 2);

/**
 * Show error notices in the admin
 */
function sbm_show_shift_admin_errors() {
    if (
        get_current_screen()->post_type !== 'shift' ||
        !isset($_GET['sbm_shift_errors'])
    ) return;

    $errors = explode('|', urldecode($_GET['sbm_shift_errors']));

    echo '<div class="notice notice-error is-dismissible"><ul>';
    foreach ($errors as $error) {
        echo '<li>' . esc_html($error) . '</li>';
    }
    echo '</ul></div>';
}
add_action('admin_notices', 'sbm_show_shift_admin_errors');

/**
 * Enqueue JS for admin validation (optional enhancement)
 */
function sbm_enqueue_admin_shift_scripts($hook) {
    $screen = get_current_screen();
    if ($screen->post_type === 'shift') {
        wp_enqueue_script(
            'sbm-admin-shift-validation',
            plugin_dir_url(__FILE__) . '../assets/js/admin-shift-validation.js',
            [],
            '1.0',
            true
        );
    }
}
add_action('admin_enqueue_scripts', 'sbm_enqueue_admin_shift_scripts');

/**
 * Disable autosave script for the shift post type to prevent empty drafts
 */
function sbm_disable_autosave_for_shift() {
    global $post_type;
    if ('shift' === $post_type) {
        wp_deregister_script('autosave');
    }
}
add_action('admin_print_scripts-post-new.php', 'sbm_disable_autosave_for_shift');
add_action('admin_print_scripts-post.php', 'sbm_disable_autosave_for_shift');
