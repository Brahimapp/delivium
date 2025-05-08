<?php
/**
 * The driver-specific functionality of the plugin.
 *
 * @link       https://delivium.top
 * @since      1.0.0
 *
 * @package    Delivium
 * @subpackage Delivium/driver
 */

class Delivium_Driver {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name    The name of this plugin.
     * @param    string    $version        The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        $this->load_dependencies();
        $this->define_hooks();
    }

    /**
     * Load the required dependencies for the driver functionality.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'driver/api/class-delivium-driver-api.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'driver/includes/class-delivium-location-tracker.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'driver/includes/class-delivium-route-optimizer.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'driver/includes/class-delivium-marker-generator.php';

        // Ensure marker images exist
        Delivium_Marker_Generator::ensure_markers_exist();
    }

    /**
     * Register all of the hooks related to the driver functionality.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_hooks() {
        add_action('rest_api_init', array($this, 'register_driver_api_routes'));
        add_action('wp_ajax_delivium_update_location', array($this, 'update_driver_location'));
        add_action('wp_ajax_delivium_update_delivery_status', array($this, 'update_delivery_status'));
        add_action('wp_ajax_delivium_get_route', array($this, 'get_optimized_route'));
    }

    /**
     * Register REST API routes for the driver app.
     *
     * @since    1.0.0
     */
    public function register_driver_api_routes() {
        $driver_api = new Delivium_Driver_API();
        $driver_api->register_routes();
    }

    /**
     * Update driver's current location.
     *
     * @since    1.0.0
     */
    public function update_driver_location() {
        check_ajax_referer('delivium_driver_nonce', 'nonce');

        if (!current_user_can('delivium_driver')) {
            wp_send_json_error('Unauthorized access');
        }

        $location = array(
            'lat' => sanitize_text_field($_POST['lat']),
            'lng' => sanitize_text_field($_POST['lng']),
            'timestamp' => current_time('mysql')
        );

        $tracker = new Delivium_Location_Tracker();
        $result = $tracker->update_location(get_current_user_id(), $location);

        if ($result) {
            wp_send_json_success('Location updated');
        } else {
            wp_send_json_error('Failed to update location');
        }
    }

    /**
     * Update delivery status.
     *
     * @since    1.0.0
     */
    public function update_delivery_status() {
        check_ajax_referer('delivium_driver_nonce', 'nonce');

        if (!current_user_can('delivium_driver')) {
            wp_send_json_error('Unauthorized access');
        }

        $order_id = intval($_POST['order_id']);
        $status = sanitize_text_field($_POST['status']);
        $note = sanitize_textarea_field($_POST['note']);

        // Validate status
        $valid_statuses = array('picked_up', 'in_transit', 'delivered', 'failed');
        if (!in_array($status, $valid_statuses)) {
            wp_send_json_error('Invalid status');
        }

        // Update order status
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error('Order not found');
        }

        // Check if this driver is assigned to this order
        $assigned_driver = get_post_meta($order_id, '_delivium_assigned_driver', true);
        if ($assigned_driver != get_current_user_id()) {
            wp_send_json_error('Not authorized to update this order');
        }

        $order->update_status('wc-' . $status);
        $order->add_order_note($note, false, true);

        // Update delivery meta
        update_post_meta($order_id, '_delivium_delivery_status', $status);
        update_post_meta($order_id, '_delivium_status_timestamp', current_time('mysql'));

        // Trigger webhooks
        do_action('delivium_delivery_status_updated', $order_id, $status, array(
            'driver_id' => get_current_user_id(),
            'timestamp' => current_time('mysql'),
            'note' => $note
        ));

        wp_send_json_success('Status updated');
    }

    /**
     * Get optimized route for deliveries.
     *
     * @since    1.0.0
     */
    public function get_optimized_route() {
        check_ajax_referer('delivium_driver_nonce', 'nonce');

        if (!current_user_can('delivium_driver')) {
            wp_send_json_error('Unauthorized access');
        }

        $optimizer = new Delivium_Route_Optimizer();
        $route = $optimizer->get_optimized_route(get_current_user_id());

        if ($route) {
            wp_send_json_success($route);
        } else {
            wp_send_json_error('Failed to generate route');
        }
    }

    /**
     * Register the stylesheets for the driver area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name . '-driver',
            plugin_dir_url(__FILE__) . 'css/delivium-driver.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the driver area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name . '-driver',
            plugin_dir_url(__FILE__) . 'js/delivium-driver.js',
            array('jquery'),
            $this->version,
            false
        );

        wp_localize_script(
            $this->plugin_name . '-driver',
            'deliviumDriver',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('delivium_driver_nonce'),
                'api_url' => rest_url('delivium/v1/driver/'),
                'google_maps_key' => get_option('delivium_google_maps_key'),
                'update_interval' => get_option('delivium_location_update_interval', 30),
                'strings' => array(
                    'confirm_delivery' => __('Are you sure you want to mark this order as delivered?', 'delivium'),
                    'confirm_pickup' => __('Are you sure you want to mark this order as picked up?', 'delivium'),
                    'location_error' => __('Unable to get your current location.', 'delivium'),
                    'network_error' => __('Network error. Please try again.', 'delivium')
                )
            )
        );
    }
} 