<?php
/**
 * WooCommerce Order Meta Boxes
 *
 * @package    Delivium
 * @subpackage Delivium/woocommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class Delivium_Order_Meta {
    /**
     * Initialize the class
     */
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_delivery_driver_meta_box'));
        add_action('save_post', array($this, 'save_delivery_driver_meta_box'));
        add_action('woocommerce_admin_order_data_after_order_details', array($this, 'display_delivery_driver_in_order'));
    }

    /**
     * Add delivery driver meta box to order edit screen
     */
    public function add_delivery_driver_meta_box() {
        add_meta_box(
            'delivium_delivery_driver',
            __('Delivery Driver', 'delivium'),
            array($this, 'render_delivery_driver_meta_box'),
            'shop_order',
            'side',
            'high'
        );
    }

    /**
     * Render delivery driver meta box content
     */
    public function render_delivery_driver_meta_box($post) {
        $order = wc_get_order($post->ID);
        $current_driver_id = get_post_meta($post->ID, '_delivium_delivery_driver_id', true);
        
        // Get all delivery drivers
        $drivers = get_users(array(
            'role' => 'delivery_driver',
            'orderby' => 'display_name',
            'order' => 'ASC'
        ));
        
        wp_nonce_field('delivium_save_delivery_driver', 'delivium_delivery_driver_nonce');
        ?>
        <p>
            <label for="delivium_delivery_driver"><?php _e('Assign Delivery Driver:', 'delivium'); ?></label>
            <select name="delivium_delivery_driver" id="delivium_delivery_driver" class="widefat">
                <option value=""><?php _e('Select a driver', 'delivium'); ?></option>
                <?php foreach ($drivers as $driver) : ?>
                    <option value="<?php echo esc_attr($driver->ID); ?>" <?php selected($current_driver_id, $driver->ID); ?>>
                        <?php echo esc_html($driver->display_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php
    }

    /**
     * Save delivery driver meta box data
     */
    public function save_delivery_driver_meta_box($post_id) {
        if (!isset($_POST['delivium_delivery_driver_nonce']) || 
            !wp_verify_nonce($_POST['delivium_delivery_driver_nonce'], 'delivium_save_delivery_driver')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['delivium_delivery_driver'])) {
            $old_driver_id = get_post_meta($post_id, '_delivium_delivery_driver_id', true);
            $new_driver_id = sanitize_text_field($_POST['delivium_delivery_driver']);
            
            update_post_meta($post_id, '_delivium_delivery_driver_id', $new_driver_id);
            
            // If driver changed, trigger notification
            if ($old_driver_id != $new_driver_id && !empty($new_driver_id)) {
                do_action('delivium_driver_assigned', $post_id, $new_driver_id);
            }
        }
    }

    /**
     * Display delivery driver information in order details
     */
    public function display_delivery_driver_in_order($order) {
        $driver_id = get_post_meta($order->get_id(), '_delivium_delivery_driver_id', true);
        if ($driver_id) {
            $driver = get_user_by('ID', $driver_id);
            if ($driver) {
                ?>
                <p class="form-field form-field-wide">
                    <label><?php _e('Delivery Driver:', 'delivium'); ?></label>
                    <?php echo esc_html($driver->display_name); ?>
                </p>
                <?php
            }
        }
    }
}

// Initialize the class
new Delivium_Order_Meta(); 