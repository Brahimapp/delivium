<?php
/**
 * Order meta boxes
 *
 * @link       https://delivium.top
 * @since      1.0.0
 *
 * @package    Delivium
 * @subpackage Delivium/admin/includes
 */

class Delivium_Order_Meta_Boxes {

    /**
     * Initialize the class
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Add meta boxes for both classic and HPOS order screens
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('add_meta_boxes_woocommerce_page_wc-orders', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'), 10, 2);
        add_action('woocommerce_process_shop_order_meta', array($this, 'save_meta_boxes'), 10, 2);
    }

    /**
     * Add meta boxes to order page
     *
     * @since    1.0.0
     */
    public function add_meta_boxes() {
        $screen = get_current_screen();
        $post_type = $screen ? $screen->post_type : 'shop_order';

        add_meta_box(
            'delivium_driver_assignment',
            __('Delivery Driver Assignment', 'delivium'),
            array($this, 'driver_assignment_meta_box'),
            $post_type,
            'side',
            'default'
        );

        add_meta_box(
            'delivium_delivery_details',
            __('Delivery Details', 'delivium'),
            array($this, 'delivery_details_meta_box'),
            $post_type,
            'normal',
            'high'
        );
    }

    /**
     * Driver assignment meta box
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function driver_assignment_meta_box($post) {
        $order = wc_get_order($post->ID);
        $assigned_driver = $order->get_meta('_delivium_driver_id');
        
        // Get only available drivers
        $drivers = get_users(array(
            'role' => 'delivery_driver',
            'meta_key' => 'delivium_driver_status',
            'meta_value' => 'available',
            'orderby' => 'display_name'
        ));

        wp_nonce_field('delivium_save_data', 'delivium_meta_nonce');

        echo '<select name="delivium_driver_id" style="width:100%">';
        echo '<option value="">' . esc_html__('Select a driver', 'delivium') . '</option>';
        
        foreach ($drivers as $driver) {
            echo '<option value="' . esc_attr($driver->ID) . '" ' . selected($assigned_driver, $driver->ID, false) . '>';
            echo esc_html($driver->display_name);
            echo '</option>';
        }
        echo '</select>';

        if ($assigned_driver) {
            $status = $order->get_meta('_delivium_delivery_status');
            echo '<p class="description">' . esc_html__('Current Status: ', 'delivium');
            echo '<strong>' . esc_html($status ?: 'Not assigned') . '</strong></p>';
        }
    }

    /**
     * Delivery details meta box
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function delivery_details_meta_box($post) {
        $order = wc_get_order($post->ID);
        $time_window = get_post_meta($post->ID, '_delivium_time_window', true);
        $delivery_notes = get_post_meta($post->ID, '_delivium_delivery_notes', true);
        $delivery_date = get_post_meta($post->ID, '_delivium_delivery_date', true);

        // Delivery date
        echo '<p class="form-field">';
        echo '<label for="delivium_delivery_date">' . esc_html__('Delivery Date', 'delivium') . '</label>';
        echo '<input type="date" id="delivium_delivery_date" name="delivium_delivery_date" value="' . esc_attr($delivery_date) . '">';
        echo '</p>';

        // Time window
        echo '<p class="form-field">';
        echo '<label for="delivium_time_window">' . esc_html__('Time Window', 'delivium') . '</label>';
        echo '<input type="text" id="delivium_time_window" name="delivium_time_window" value="' . esc_attr($time_window) . '" placeholder="e.g., 14:00-16:00">';
        echo '</p>';

        // Delivery notes
        echo '<p class="form-field">';
        echo '<label for="delivium_delivery_notes">' . esc_html__('Delivery Notes', 'delivium') . '</label>';
        echo '<textarea id="delivium_delivery_notes" name="delivium_delivery_notes" rows="4" style="width:100%">' . esc_textarea($delivery_notes) . '</textarea>';
        echo '</p>';

        // Shipping address map
        $address = $order->get_formatted_shipping_address();
        if ($address) {
            echo '<div class="delivium-map-container" style="height:300px;margin-top:20px;">';
            echo '<div id="delivium-order-map" style="height:100%"></div>';
            echo '</div>';

            // Add Google Maps initialization
            wp_enqueue_script('google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . esc_attr(get_option('delivium_google_maps_key')));
            
            $lat = get_post_meta($post->ID, '_shipping_latitude', true);
            $lng = get_post_meta($post->ID, '_shipping_longitude', true);
            
            if ($lat && $lng) {
                echo '<script>
                    jQuery(document).ready(function($) {
                        var map = new google.maps.Map(document.getElementById("delivium-order-map"), {
                            zoom: 15,
                            center: { lat: ' . floatval($lat) . ', lng: ' . floatval($lng) . ' }
                        });
                        
                        new google.maps.Marker({
                            position: { lat: ' . floatval($lat) . ', lng: ' . floatval($lng) . ' },
                            map: map,
                            title: "' . esc_js($address) . '"
                        });
                    });
                </script>';
            }
        }
    }

    /**
     * Save meta box data
     *
     * @since    1.0.0
     * @param    int       $post_id    The post ID.
     * @param    WP_Post   $post       The post object.
     */
    public function save_meta_boxes($post_id, $post) {
        // Check if our nonce is set
        if (!isset($_POST['delivium_meta_nonce'])) {
            return;
        }

        // Verify that the nonce is valid
        if (!wp_verify_nonce($_POST['delivium_meta_nonce'], 'delivium_save_data')) {
            return;
        }

        // If this is an autosave, our form has not been submitted, so we don't want to do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check the user's permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save driver assignment
        if (isset($_POST['delivium_driver_id'])) {
            $driver_id = sanitize_text_field($_POST['delivium_driver_id']);
            $order = wc_get_order($post_id);
            
            if ($order) {
                $old_driver_id = $order->get_meta('_delivium_driver_id');
                
                if ($driver_id !== $old_driver_id) {
                    $order->update_meta_data('_delivium_driver_id', $driver_id);
                    
                    if (!empty($driver_id)) {
                        // Update order status to driver assigned
                        $order->update_status('driver-assigned', __('Order assigned to driver.', 'delivium'));
                        $order->update_meta_data('_delivium_delivery_status', 'driver-assigned');
                        
                        // Trigger notification
                        do_action('delivium_order_assigned_to_driver', $post_id, $driver_id);
                    } else {
                        $order->update_meta_data('_delivium_delivery_status', 'pending');
                    }
                    
                    $order->save();
                }
            }
        }

        // Save delivery details
        if (isset($_POST['delivium_delivery_date'])) {
            update_post_meta($post_id, '_delivium_delivery_date', sanitize_text_field($_POST['delivium_delivery_date']));
        }

        if (isset($_POST['delivium_time_window'])) {
            update_post_meta($post_id, '_delivium_time_window', sanitize_text_field($_POST['delivium_time_window']));
        }

        if (isset($_POST['delivium_delivery_notes'])) {
            update_post_meta($post_id, '_delivium_delivery_notes', sanitize_textarea_field($_POST['delivium_delivery_notes']));
        }
    }
} 