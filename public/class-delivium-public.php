<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://delivium.top
 * @since      1.0.0
 *
 * @package    Delivium
 * @subpackage Delivium/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for the public-facing side
 * including the public-facing stylesheet and JavaScript.
 *
 * @package    Delivium
 * @subpackage Delivium/public
 * @author     Delivium Team <support@delivium.top>
 */
class Delivium_Public {

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
	 * @param    string    $plugin_name    The name of the plugin.
	 * @param    string    $version        The version of this plugin.
	 */
	public function __construct($plugin_name, $version) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// Register AJAX handlers
		add_action('wp_ajax_delivium_claim_order', array($this, 'handle_claim_order'));
		add_action('wp_ajax_delivium_start_delivery', array($this, 'handle_start_delivery'));
		add_action('wp_ajax_delivium_complete_delivery', array($this, 'handle_complete_delivery'));

		// Register shortcodes
		add_shortcode('delivium_driver_portal', array($this, 'render_driver_portal'));
		add_shortcode('delivium_order_tracking', array($this, 'render_order_tracking'));
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			$this->plugin_name,
			plugin_dir_url(__FILE__) . 'css/delivium-public.css',
			array(),
			$this->version,
			'all'
		);

		// Enqueue premium styles if available
		if (defined('DELIVIUM_PREMIUM') && DELIVIUM_PREMIUM) {
			wp_enqueue_style(
				$this->plugin_name . '-premium',
				plugin_dir_url(__FILE__) . 'css/delivium-public-premium.css',
				array($this->plugin_name),
				$this->version,
				'all'
			);
		}
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url(__FILE__) . 'js/delivium-public.js',
			array('jquery'),
			$this->version,
			false
		);

		wp_localize_script($this->plugin_name, 'delivium_ajax', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('delivium_ajax_nonce')
		));

		// Localize the script with new data
		$script_data = array(
			'tracking_interval' => get_option('delivium_tracking_interval', 60),
			'map_zoom' => get_option('delivium_map_zoom', 13),
			'is_premium' => defined('DELIVIUM_PREMIUM') && DELIVIUM_PREMIUM,
			'tracking_enabled' => get_option('delivium_enable_tracking', 'yes') === 'yes',
			'i18n' => array(
				'tracking_error' => __('Unable to update tracking information.', 'delivium'),
				'location_error' => __('Could not get current location.', 'delivium'),
				'delivery_complete' => __('Delivery completed successfully!', 'delivium'),
				'confirm_delivery' => __('Are you sure you want to mark this delivery as complete?', 'delivium')
			)
		);

		wp_localize_script($this->plugin_name, 'deliviumPublic', $script_data);

		// Enqueue premium scripts if available
		if (defined('DELIVIUM_PREMIUM') && DELIVIUM_PREMIUM) {
			wp_enqueue_script(
				$this->plugin_name . '-premium',
				plugin_dir_url(__FILE__) . 'js/delivium-public-premium.js',
				array($this->plugin_name),
				$this->version,
				true
			);
		}
	}

	/**
	 * Register shortcodes for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function register_shortcodes() {
		add_shortcode('delivium_tracking', array($this, 'tracking_shortcode'));
		
		if (defined('DELIVIUM_PREMIUM') && DELIVIUM_PREMIUM) {
			add_shortcode('delivium_driver_dashboard', array($this, 'driver_dashboard_shortcode'));
			add_shortcode('delivium_delivery_rating', array($this, 'delivery_rating_shortcode'));
		}
	}

	/**
	 * Tracking shortcode callback.
	 *
	 * @since    1.0.0
	 * @param    array    $atts    Shortcode attributes.
	 * @return   string            Shortcode output.
	 */
	public function tracking_shortcode($atts) {
		$atts = shortcode_atts(array(
			'order_id' => 0,
			'show_map' => 'yes'
		), $atts, 'delivium_tracking');

		ob_start();
		include plugin_dir_path(__FILE__) . 'partials/delivium-public-tracking.php';
		return ob_get_clean();
	}

	/**
	 * Driver dashboard shortcode callback (Premium only).
	 *
	 * @since    1.0.0
	 * @param    array    $atts    Shortcode attributes.
	 * @return   string            Shortcode output.
	 */
	public function driver_dashboard_shortcode($atts) {
		if (!defined('DELIVIUM_PREMIUM') || !DELIVIUM_PREMIUM) {
			return '<p>' . __('This feature requires Delivium Premium.', 'delivium') . '</p>';
		}

		$atts = shortcode_atts(array(
			'show_map' => 'yes',
			'show_stats' => 'yes'
		), $atts, 'delivium_driver_dashboard');

		ob_start();
		include plugin_dir_path(__FILE__) . 'partials/delivium-public-driver-dashboard.php';
		return ob_get_clean();
	}

	/**
	 * Delivery rating shortcode callback (Premium only).
	 *
	 * @since    1.0.0
	 * @param    array    $atts    Shortcode attributes.
	 * @return   string            Shortcode output.
	 */
	public function delivery_rating_shortcode($atts) {
		if (!defined('DELIVIUM_PREMIUM') || !DELIVIUM_PREMIUM) {
			return '<p>' . __('This feature requires Delivium Premium.', 'delivium') . '</p>';
		}

		$atts = shortcode_atts(array(
			'delivery_id' => 0,
			'show_comments' => 'yes'
		), $atts, 'delivium_delivery_rating');

		ob_start();
		include plugin_dir_path(__FILE__) . 'partials/delivium-public-rating.php';
		return ob_get_clean();
	}

	/**
	 * Register REST API endpoints.
	 *
	 * @since    1.0.0
	 */
	public function register_rest_endpoints() {
		register_rest_route('delivium/v1', '/tracking/(?P<order_id>\d+)', array(
			'methods' => 'GET',
			'callback' => array($this, 'get_tracking_data'),
			'permission_callback' => array($this, 'check_tracking_permissions'),
			'args' => array(
				'order_id' => array(
					'validate_callback' => function($param) {
						return is_numeric($param);
					}
				)
			)
		));

		if (defined('DELIVIUM_PREMIUM') && DELIVIUM_PREMIUM) {
			register_rest_route('delivium/v1', '/tracking/update', array(
				'methods' => 'POST',
				'callback' => array($this, 'update_tracking_data'),
				'permission_callback' => array($this, 'check_driver_permissions')
			));
		}
	}

	/**
	 * Check tracking permissions for REST API.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    Full details about the request.
	 * @return   bool|WP_Error                  True if the request has access, WP_Error object otherwise.
	 */
	public function check_tracking_permissions($request) {
		$order_id = $request->get_param('order_id');
		$order = wc_get_order($order_id);

		if (!$order) {
			return new WP_Error(
				'delivium_invalid_order',
				__('Invalid order ID.', 'delivium'),
				array('status' => 404)
			);
		}

		// Allow if user owns the order or is an administrator
		return current_user_can('administrator') || 
			   $order->get_customer_id() === get_current_user_id();
	}

	/**
	 * Check driver permissions for REST API.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request    $request    Full details about the request.
	 * @return   bool|WP_Error                  True if the request has access, WP_Error object otherwise.
	 */
	public function check_driver_permissions($request) {
		if (!is_user_logged_in()) {
			return new WP_Error(
				'delivium_not_logged_in',
				__('You must be logged in to perform this action.', 'delivium'),
				array('status' => 401)
			);
		}

		// Check if user has driver role
		$user = wp_get_current_user();
		if (!in_array('delivium_driver', (array) $user->roles)) {
			return new WP_Error(
				'delivium_not_driver',
				__('You must be a delivery driver to perform this action.', 'delivium'),
				array('status' => 403)
			);
		}

		return true;
	}

	/**
	 * Handle order claim request.
	 *
	 * @since    1.0.0
	 */
	public function handle_claim_order() {
		check_ajax_referer('delivium_ajax_nonce', 'nonce');

		if (!current_user_can('delivery_driver')) {
			wp_send_json_error(array('message' => __('You do not have permission to claim orders.', 'delivium')));
		}

		$order_id = intval($_POST['order_id']);
		$driver_id = get_current_user_id();

		// Check if order exists and is not already assigned
		$order = wc_get_order($order_id);
		if (!$order || $order->get_meta('_delivery_driver_id')) {
			wp_send_json_error(array('message' => __('Invalid order or order already assigned.', 'delivium')));
		}

		// Assign order to driver
		$order->update_meta_data('_delivery_driver_id', $driver_id);
		$order->update_status('driver-assigned');
		$order->save();

		wp_send_json_success(array('message' => __('Order claimed successfully.', 'delivium')));
	}

	/**
	 * Handle start delivery request.
	 *
	 * @since    1.0.0
	 */
	public function handle_start_delivery() {
		check_ajax_referer('delivium_ajax_nonce', 'nonce');

		if (!current_user_can('delivery_driver')) {
			wp_send_json_error(array('message' => __('You do not have permission to start deliveries.', 'delivium')));
		}

		$order_id = intval($_POST['order_id']);
		$driver_id = get_current_user_id();

		// Check if order exists and is assigned to current driver
		$order = wc_get_order($order_id);
		if (!$order || $order->get_meta('_delivery_driver_id') != $driver_id) {
			wp_send_json_error(array('message' => __('Invalid order or not assigned to you.', 'delivium')));
		}

		// Update order status
		$order->update_status('out-for-delivery');
		$order->save();

		wp_send_json_success(array('message' => __('Delivery started successfully.', 'delivium')));
	}

	/**
	 * Handle complete delivery request.
	 *
	 * @since    1.0.0
	 */
	public function handle_complete_delivery() {
		check_ajax_referer('delivium_ajax_nonce', 'nonce');

		if (!current_user_can('delivery_driver')) {
			wp_send_json_error(array('message' => __('You do not have permission to complete deliveries.', 'delivium')));
		}

		$order_id = intval($_POST['order_id']);
		$driver_id = get_current_user_id();

		// Check if order exists and is assigned to current driver
		$order = wc_get_order($order_id);
		if (!$order || $order->get_meta('_delivery_driver_id') != $driver_id) {
			wp_send_json_error(array('message' => __('Invalid order or not assigned to you.', 'delivium')));
		}

		// Update order status
		$order->update_status('completed');
		$order->save();

		wp_send_json_success(array('message' => __('Delivery completed successfully.', 'delivium')));
	}

	public function render_driver_portal() {
		if (!is_user_logged_in() || !current_user_can('delivery_driver')) {
			return '<p>' . __('You must be logged in as a delivery driver to access this page.', 'delivium') . '</p>';
		}

		ob_start();
		include plugin_dir_path(__FILE__) . 'partials/delivium-driver-portal.php';
		return ob_get_clean();
	}

	public function render_order_tracking() {
		ob_start();
		include plugin_dir_path(__FILE__) . 'partials/delivium-order-tracking.php';
		return ob_get_clean();
	}
} 