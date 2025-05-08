<?php
/**
 * Admin order meta box
 *
 * @link       https://delivium.top
 * @since      1.0.0
 *
 * @package    Delivium
 * @subpackage Delivium/admin/partials
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}

/**
 * Add the driver assignment meta box
 */
function delivium_add_order_meta_boxes() {
    add_meta_box(
        'delivium_driver_assignment',
        __('Delivery Driver Assignment', 'delivium'),
        'delivium_driver_assignment_meta_box',
        'shop_order',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'delivium_add_order_meta_boxes');

/**
 * Render the driver assignment meta box
 */
function delivium_driver_assignment_meta_box($post) {
    $order = wc_get_order($post->ID);
    $current_driver_id = get_post_meta($post->ID, '_delivium_driver_id', true);
    
    // Get all drivers
    $drivers = get_users(array('role' => 'delivium_driver'));
    
    wp_nonce_field('delivium_save_driver_assignment', 'delivium_driver_nonce');
    ?>
    <div class="delivium-driver-assignment">
        <p>
            <label for="delivium_driver"><?php esc_html_e('Assign Driver:', 'delivium'); ?></label>
            <select name="delivium_driver" id="delivium_driver" class="widefat">
                <option value=""><?php esc_html_e('Select a driver', 'delivium'); ?></option>
                <?php foreach ($drivers as $driver): ?>
                    <option value="<?php echo esc_attr($driver->ID); ?>" 
                            <?php selected($current_driver_id, $driver->ID); ?>>
                        <?php echo esc_html($driver->display_name); ?>
                        (<?php echo esc_html(get_user_meta($driver->ID, 'driver_status', true) ?: 'offline'); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        
        <?php if ($current_driver_id): ?>
            <p class="driver-info">
                <?php
                $driver = get_userdata($current_driver_id);
                $status = get_user_meta($current_driver_id, 'driver_status', true) ?: 'offline';
                echo sprintf(
                    esc_html__('Currently assigned to: %s (Status: %s)', 'delivium'),
                    esc_html($driver->display_name),
                    esc_html($status)
                );
                ?>
            </p>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Save the driver assignment
 */
function delivium_save_driver_assignment($post_id) {
    if (!isset($_POST['delivium_driver_nonce']) || 
        !wp_verify_nonce($_POST['delivium_driver_nonce'], 'delivium_save_driver_assignment')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['delivium_driver'])) {
        $driver_id = sanitize_text_field($_POST['delivium_driver']);
        if (!empty($driver_id)) {
            update_post_meta($post_id, '_delivium_driver_id', $driver_id);
            
            // Update order status to assigned if not already
            $order = wc_get_order($post_id);
            if ($order && $order->get_status() === 'processing') {
                $order->update_status('assigned', __('Order assigned to driver.', 'delivium'));
            }
            
            // Notify the driver
            do_action('delivium_order_assigned_to_driver', $post_id, $driver_id);
        } else {
            delete_post_meta($post_id, '_delivium_driver_id');
        }
    }
} 