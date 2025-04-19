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
<?php $min_date = date('Y-m-d'); ?>
<p><label>Date:</label><br>
<?php
$today = (new DateTime('now', new DateTimeZone('America/Toronto')))->format('Y-m-d');
?>
<?php error_log("Shift date min: $today"); ?>
<input type="date" id="shift_date" name="shift_date" min="<?php echo $today; ?>" value="<?php echo esc_attr($date); ?>">
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
    if (!empty($end_time)) {
        $end_timestamp = strtotime("$shift_date $end_time");

        if ($end_timestamp <= $start_timestamp) {
            update_post_meta($post_id, '_sbm_shift_validation_failed', '1');
            $message = __('End time must be after the start time.', 'shift-booking-manager');
            $message .= '<br><br><a href="' . esc_url($edit_link) . '">&laquo; Go back to edit shift</a>';
            wp_die($message, __('Invalid End Time', 'shift-booking-manager'), ['back_link' => false]);
        }
    
// Allow shifts to end at midnight (23:59) if they are at least 45 minutes long
$ends_at_midnight = (date('H:i', $end_timestamp) === '23:59');
$min_duration = $ends_at_midnight ? 45 * MINUTE_IN_SECONDS : HOUR_IN_SECONDS;

if (($end_timestamp - $start_timestamp) < $min_duration) {
    update_post_meta($post_id, '_sbm_shift_validation_failed', '1');

    $message = $ends_at_midnight
        ? __('The shift must be at least 45 minutes long if it ends at Midnight.', 'shift-booking-manager')
        : __('The shift must be at least 1 hour long.', 'shift-booking-manager');

    $message .= '<br><br><a href="' . esc_url($edit_link) . '">&laquo; Go back to edit shift</a>';
    wp_die($message, __('Shift Too Short', 'shift-booking-manager'), ['back_link' => false]);
}

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
// Only update the title if the post is being published or updated, not when it's being created as a draft
if (!empty($shift_date) && !empty($start_time) && !empty($end_time)) {
    $formatted_date = date('F jS', strtotime($shift_date));
    $formatted_start = date('g:i A', strtotime($start_time));

    // Replace 11:59 PM with "Midnight"
    $end_raw = date('H:i', strtotime($end_time));
    $formatted_end = ($end_raw === '23:59') ? 'Midnight' : date('g:i A', strtotime($end_time));

    $auto_title = "{$formatted_date} @ {$formatted_start} to {$formatted_end}";

    remove_action('save_post_shift', 'sbm_save_shift_meta_box');
    if (get_post_status($post_id) === 'publish') {
        wp_update_post([
            'ID' => $post_id,
            'post_title' => $auto_title,
        ]);
    }
    add_action('save_post_shift', 'sbm_save_shift_meta_box');
}

    delete_post_meta($post_id, '_sbm_shift_validation_failed');
}
add_action('save_post_shift', 'sbm_save_shift_meta_box');

function sbm_exclude_auto_drafts_from_admin($query) {
    if (!is_admin() || !$query->is_main_query()) return;

    // Only apply to 'shift' post type in main "All" admin screen
    global $pagenow;
    if ($pagenow === 'edit.php' &&
        $query->get('post_type') === 'shift' &&
        $query->get('post_status') === ''  // This ensures we don't override 'trash', 'draft', etc.
    ) {
        $query->set('post_status', ['publish', 'draft', 'pending', 'future']);
    }
}
add_action('pre_get_posts', 'sbm_exclude_auto_drafts_from_admin');

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
    global $typenow;
    if ($typenow === 'shift') {
        wp_enqueue_script(
            'sbm-admin-shift-validation',
            plugin_dir_url(__FILE__) . '../assets/js/admin-shift-validation.js',
            [],
            time(),
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
