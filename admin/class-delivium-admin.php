<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://delivium.top
 * @since      1.0.0
 *
 * @package    Delivium
 * @subpackage Delivium/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Delivium
 * @subpackage Delivium/admin
 * @author     Delivium Team <support@delivium.top>
 */
class Delivium_Admin {
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
	 * @param    string $plugin_name       The name of this plugin.
	 * @param    string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// Register settings
		add_action('admin_init', array($this, 'register_settings'));
		
		// Register admin menu
		add_action('admin_menu', array($this, 'register_admin_menu'));

		// Load dependencies
		$this->load_dependencies();

		// Add new menu item for adding drivers and its handler
		add_action('admin_menu', array($this, 'add_plugin_admin_menu'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
		add_action('admin_init', array($this, 'register_settings'));
		add_action('admin_post_add_delivium_driver', array($this, 'handle_add_driver'));
	}

	/**
	 * Register the admin menu items.
	 *
	 * @since    1.0.0
	 */
	public function register_admin_menu() {
		// Main menu
		add_menu_page(
			__('Delivium', 'delivium'),
			__('Delivium', 'delivium'),
			'manage_options',
			'delivium',
			array($this, 'display_dashboard_page'),
			'dashicons-truck',
			30
		);

		// Submenu items
		add_submenu_page(
			'delivium',
			__('Dashboard', 'delivium'),
			__('Dashboard', 'delivium'),
			'manage_options',
			'delivium',
			array($this, 'display_dashboard_page')
		);

		add_submenu_page(
			'delivium',
			__('Orders', 'delivium'),
			__('Orders', 'delivium'),
			'manage_woocommerce',
			'delivium-orders',
			array($this, 'display_orders_page')
		);

		add_submenu_page(
			'delivium',
			__('Drivers', 'delivium'),
			__('Drivers', 'delivium'),
			'manage_options',
			'delivium-drivers',
			array($this, 'display_drivers_page')
		);

		add_submenu_page(
			'delivium',
			__('Add New Driver', 'delivium'),
			__('Add New Driver', 'delivium'),
			'manage_options',
			'delivium-add-driver',
			array($this, 'display_add_driver_page')
		);

		add_submenu_page(
			'delivium',
			__('Settings', 'delivium'),
			__('Settings', 'delivium'),
			'manage_options',
			'delivium-settings',
			array($this, 'display_settings_page')
		);
	}

	/**
	 * Display the dashboard page.
	 *
	 * @since    1.0.0
	 */
	public function display_dashboard_page() {
		include_once 'partials/delivium-admin-dashboard.php';
	}

	/**
	 * Display the orders page.
	 *
	 * @since    1.0.0
	 */
	public function display_orders_page() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/delivium-admin-orders.php';
	}

	/**
	 * Display the drivers page.
	 *
	 * @since    1.0.0
	 */
	public function display_drivers_page() {
		include_once 'partials/delivium-admin-drivers.php';
	}

	/**
	 * Display the zones page.
	 *
	 * @since    1.0.0
	 */
	public function display_zones_page() {
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/delivium-admin-zones.php';
	}

	/**
	 * Display the time slots page.
	 *
	 * @since    1.0.0
	 */
	public function display_time_slots_page() {
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/delivium-admin-timeslots.php';
	}

	/**
	 * Display the reports page.
	 *
	 * @since    1.0.0
	 */
	public function display_reports_page() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/delivium-admin-reports.php';
	}

	/**
	 * Display the settings page.
	 *
	 * @since    1.0.0
	 */
	public function display_settings_page() {
		include_once 'partials/delivium-admin-settings.php';
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		$page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
		
		if ( 'delivium-reports' === $page ) {
			wp_enqueue_style( 'delivium-jquery-ui', plugin_dir_url( __FILE__ ) . 'css/jquery-ui.css', array(), $this->version, 'all' );
		}
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/delivium-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		$page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
		$tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : '';
		
		$screen = get_current_screen();
		
		// Load color picker for branding settings
		if ( 'user-edit' === $screen->base || 'delivium-branding' === $tab ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );
		}

		// Load datepicker for reports
		if ( 'delivium-reports' === $page ) {
			wp_enqueue_script( 'jquery-ui-datepicker' );
		}

		$script_array = array( 'jquery' );
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/delivium-admin.js', $script_array, $this->version, false );
		wp_localize_script( $this->plugin_name, 'delivium_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		wp_localize_script( $this->plugin_name, 'delivium_nonce', array( 'nonce' => wp_create_nonce( 'delivium-nonce' ) ) );
	}

	/**
	 * Handle out for delivery service
	 *
	 * @param int $driver_id The driver ID.
	 * @return string
	 */
	public function out_for_delivery_service( $driver_id ) {
		$result = array( 'error' => 1 );
		$error  = __( 'An error occurred.', 'delivium' );

		// Verify nonce
		if ( isset( $_POST['delivium_wpnonce'] ) && 
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['delivium_wpnonce'] ) ), 'delivium-nonce' ) ) {
			
			$orders_list = isset( $_POST['orders_list'] ) ? sanitize_text_field( wp_unslash( $_POST['orders_list'] ) ) : '';
			
			if ( !empty( $orders_list ) ) {
				$orders_array = explode( ',', $orders_list );
				foreach ( $orders_array as $order_id ) {
					$order = wc_get_order( $order_id );
					if ( $order ) {
						$order_driver_id = $order->get_meta( 'delivium_driver_id' );
						$out_for_delivery_status = get_option( 'delivium_out_for_delivery_status', 'out-for-delivery' );
						$driver_assigned_status = get_option( 'delivium_driver_assigned_status', 'driver-assigned' );

						if ( $driver_id === intval( $order_driver_id ) && 
							$driver_assigned_status === $order->get_status() ) {
							
							// Update order status to out for delivery
							$order->update_status( 
								$out_for_delivery_status, 
								__( 'The delivery driver changed the order status.', 'delivium' )
							);

							$result['error'] = 0;
							$error = sprintf(
								'<div class="alert alert-success alert-dismissible fade show">%s<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div><a id="view_out_of_delivery_orders_button" href="%s" class="btn btn-lg btn-block btn-primary">%s</a>',
								__( 'Orders successfully marked as out for delivery.', 'delivium' ),
								esc_url( add_query_arg( 'screen', 'out_for_delivery', admin_url( 'admin.php?page=delivium-orders' ) ) ),
								__( 'View out for delivery orders', 'delivium' )
							);
						}
					}
				}
			} else {
				$error = __( 'Please choose the orders.', 'delivium' );
			}
		}

		if ( ! $this->is_delivery_driver( $driver_id ) ) {
			$error = __( 'User is not a delivery driver', 'delivium' );
		}

		$result['message'] = $error;
		return wp_json_encode( $result );
	}

	/**
	 * Check if user is a delivery driver
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	private function is_delivery_driver( $user_id ) {
		$user = get_userdata( $user_id );
		return $user && in_array( 'delivium_driver', (array) $user->roles, true );
	}

	/**
	 * Handle AJAX requests
	 */
	public function handle_ajax() {
		// Get parameters
		$data_type = isset( $_POST['data_type'] ) ? sanitize_text_field( wp_unslash( $_POST['data_type'] ) ) : '';
		$obj_id = isset( $_POST['obj_id'] ) ? sanitize_text_field( wp_unslash( $_POST['obj_id'] ) ) : '';
		$service = isset( $_POST['service'] ) ? sanitize_text_field( wp_unslash( $_POST['service'] ) ) : '';
		$driver_id = isset( $_POST['driver_id'] ) ? sanitize_text_field( wp_unslash( $_POST['driver_id'] ) ) : '';

		// Verify nonce
		if ( ! isset( $_POST['delivium_wpnonce'] ) || 
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['delivium_wpnonce'] ) ), 'delivium-nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', 'delivium' ) );
			wp_die();
		}

		// Handle different services
		switch ( $service ) {
			case 'out_for_delivery':
				echo $this->out_for_delivery_service( $driver_id );
				break;

			case 'edit_driver':
				$driver = new Delivium_Driver();
				echo $driver->edit_driver_service();
				break;

			case 'start_delivery':
				$order_id = isset( $_POST['order_id'] ) ? sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) : '';
				$route = new Delivium_Route();
				echo $route->start_delivery( $driver_id, $order_id );
				break;

			case 'update_location':
				$latitude = isset( $_POST['latitude'] ) ? sanitize_text_field( wp_unslash( $_POST['latitude'] ) ) : '';
				$longitude = isset( $_POST['longitude'] ) ? sanitize_text_field( wp_unslash( $_POST['longitude'] ) ) : '';
				$tracking = new Delivium_Tracking();
				echo $tracking->update_driver_location( $driver_id, $latitude, $longitude );
				break;

			default:
				wp_send_json_error( __( 'Invalid service requested.', 'delivium' ) );
				break;
		}

		wp_die();
	}

	/**
	 * Load the required dependencies for this plugin's admin functionality.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/includes/class-delivium-order-meta-boxes.php';
		new Delivium_Order_Meta_Boxes();
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		// Register SMS settings
		register_setting('delivium_sms_settings', 'delivium_sms_provider');
		register_setting('delivium_sms_settings', 'delivium_sms_settings');
		register_setting('delivium_sms_settings', 'delivium_notification_settings');
		
		// SMS Templates
		register_setting('delivium_sms_templates', 'delivium_sms_templates', array(
			'sanitize_callback' => array($this, 'sanitize_sms_templates')
		));
		
		// Add settings sections
		add_settings_section(
			'delivium_sms_provider_section',
			__('SMS Provider Settings', 'delivium'),
			array($this, 'render_sms_provider_section'),
			'delivium_sms_settings'
		);
		
		add_settings_section(
			'delivium_notification_section',
			__('Notification Settings', 'delivium'),
			array($this, 'render_notification_section'),
			'delivium_sms_settings'
		);
		
		add_settings_section(
			'delivium_sms_templates_section',
			__('SMS Templates', 'delivium'),
			array($this, 'render_sms_templates_section'),
			'delivium_sms_templates'
		);
		
		// Add settings fields
		add_settings_field(
			'delivium_sms_provider',
			__('SMS Provider', 'delivium'),
			array($this, 'render_sms_provider_field'),
			'delivium_sms_settings',
			'delivium_sms_provider_section'
		);
		
		add_settings_field(
			'delivium_notification_events',
			__('Notification Events', 'delivium'),
			array($this, 'render_notification_events_field'),
			'delivium_sms_settings',
			'delivium_notification_section'
		);
	}

	/**
	 * Render SMS provider section
	 */
	public function render_sms_provider_section() {
		echo '<p>' . __('Configure your SMS provider settings below.', 'delivium') . '</p>';
	}

	/**
	 * Render notification section
	 */
	public function render_notification_section() {
		echo '<p>' . __('Choose which events should trigger SMS notifications.', 'delivium') . '</p>';
	}

	/**
	 * Render SMS templates section
	 */
	public function render_sms_templates_section() {
		echo '<p>' . __('Customize your SMS notification templates. Available variables: {order_id}, {customer_name}, {driver_name}, {delivery_address}', 'delivium') . '</p>';
	}

	/**
	 * Render SMS provider field
	 */
	public function render_sms_provider_field() {
		$provider = get_option('delivium_sms_provider', '');
		$settings = get_option('delivium_sms_settings', array());
		?>
		<select name="delivium_sms_provider" id="delivium_sms_provider">
			<option value=""><?php _e('Select Provider', 'delivium'); ?></option>
			<option value="twilio" <?php selected($provider, 'twilio'); ?>><?php _e('Twilio', 'delivium'); ?></option>
			<option value="messagebird" <?php selected($provider, 'messagebird'); ?>><?php _e('MessageBird', 'delivium'); ?></option>
			<option value="nexmo" <?php selected($provider, 'nexmo'); ?>><?php _e('Nexmo', 'delivium'); ?></option>
		</select>
		
		<div id="twilio_settings" class="provider-settings" <?php echo $provider !== 'twilio' ? 'style="display:none;"' : ''; ?>>
			<p>
				<label><?php _e('Account SID', 'delivium'); ?></label>
				<input type="text" name="delivium_sms_settings[twilio][account_sid]" 
					value="<?php echo esc_attr($settings['twilio']['account_sid'] ?? ''); ?>">
			</p>
			<p>
				<label><?php _e('Auth Token', 'delivium'); ?></label>
				<input type="password" name="delivium_sms_settings[twilio][auth_token]" 
					value="<?php echo esc_attr($settings['twilio']['auth_token'] ?? ''); ?>">
			</p>
			<p>
				<label><?php _e('From Number', 'delivium'); ?></label>
				<input type="text" name="delivium_sms_settings[twilio][from_number]" 
					value="<?php echo esc_attr($settings['twilio']['from_number'] ?? ''); ?>">
			</p>
		</div>
		
		<div id="messagebird_settings" class="provider-settings" <?php echo $provider !== 'messagebird' ? 'style="display:none;"' : ''; ?>>
			<p>
				<label><?php _e('API Key', 'delivium'); ?></label>
				<input type="text" name="delivium_sms_settings[messagebird][api_key]" 
					value="<?php echo esc_attr($settings['messagebird']['api_key'] ?? ''); ?>">
			</p>
			<p>
				<label><?php _e('Originator', 'delivium'); ?></label>
				<input type="text" name="delivium_sms_settings[messagebird][originator]" 
					value="<?php echo esc_attr($settings['messagebird']['originator'] ?? ''); ?>">
			</p>
		</div>
		
		<div id="nexmo_settings" class="provider-settings" <?php echo $provider !== 'nexmo' ? 'style="display:none;"' : ''; ?>>
			<p>
				<label><?php _e('API Key', 'delivium'); ?></label>
				<input type="text" name="delivium_sms_settings[nexmo][api_key]" 
					value="<?php echo esc_attr($settings['nexmo']['api_key'] ?? ''); ?>">
			</p>
			<p>
				<label><?php _e('API Secret', 'delivium'); ?></label>
				<input type="password" name="delivium_sms_settings[nexmo][api_secret]" 
					value="<?php echo esc_attr($settings['nexmo']['api_secret'] ?? ''); ?>">
			</p>
			<p>
				<label><?php _e('From', 'delivium'); ?></label>
				<input type="text" name="delivium_sms_settings[nexmo][from]" 
					value="<?php echo esc_attr($settings['nexmo']['from'] ?? ''); ?>">
			</p>
		</div>
		<?php
	}

	/**
	 * Render notification events field
	 */
	public function render_notification_events_field() {
		$settings = get_option('delivium_notification_settings', array());
		?>
		<fieldset>
			<label>
				<input type="checkbox" name="delivium_notification_settings[notify_driver_new_order]" value="1" 
					<?php checked(isset($settings['notify_driver_new_order'])); ?>>
				<?php _e('Notify driver of new order', 'delivium'); ?>
			</label><br>
			
			<label>
				<input type="checkbox" name="delivium_notification_settings[notify_driver_assigned]" value="1" 
					<?php checked(isset($settings['notify_driver_assigned'])); ?>>
				<?php _e('Notify driver when assigned to order', 'delivium'); ?>
			</label><br>
			
			<label>
				<input type="checkbox" name="delivium_notification_settings[notify_customer_driver_assigned]" value="1" 
					<?php checked(isset($settings['notify_customer_driver_assigned'])); ?>>
				<?php _e('Notify customer when driver is assigned', 'delivium'); ?>
			</label><br>
			
			<label>
				<input type="checkbox" name="delivium_notification_settings[notify_customer_out_delivery]" value="1" 
					<?php checked(isset($settings['notify_customer_out_delivery'])); ?>>
				<?php _e('Notify customer when order is out for delivery', 'delivium'); ?>
			</label><br>
			
			<label>
				<input type="checkbox" name="delivium_notification_settings[notify_customer_delivered]" value="1" 
					<?php checked(isset($settings['notify_customer_delivered'])); ?>>
				<?php _e('Notify customer when order is delivered', 'delivium'); ?>
			</label>
		</fieldset>
		<?php
	}

	/**
	 * Sanitize SMS templates
	 */
	public function sanitize_sms_templates($templates) {
		if (!is_array($templates)) {
			return array();
		}
		
		foreach ($templates as $key => $template) {
			$templates[$key] = wp_kses_post($template);
		}
		
		return $templates;
	}

	/**
	 * Register the administration menu for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {
		add_menu_page(
			__('Delivium', 'delivium'),
			__('Delivium', 'delivium'),
			'manage_options',
			'delivium',
			array($this, 'display_plugin_dashboard_page'),
			'dashicons-truck',
			30
		);

		add_submenu_page(
			'delivium',
			__('Dashboard', 'delivium'),
			__('Dashboard', 'delivium'),
			'manage_options',
			'delivium',
			array($this, 'display_plugin_dashboard_page')
		);

		add_submenu_page(
			'delivium',
			__('Orders', 'delivium'),
			__('Orders', 'delivium'),
			'manage_options',
			'delivium-orders',
			array($this, 'display_plugin_orders_page')
		);

		add_submenu_page(
			'delivium',
			__('Drivers', 'delivium'),
			__('Drivers', 'delivium'),
			'manage_options',
			'delivium-drivers',
			array($this, 'display_plugin_drivers_page')
		);

		add_submenu_page(
			'delivium',
			__('Add New Driver', 'delivium'),
			__('Add New Driver', 'delivium'),
			'manage_options',
			'delivium-add-driver',
			array($this, 'display_add_driver_page')
		);

		add_submenu_page(
			'delivium',
			__('Delivery Zones', 'delivium'),
			__('Delivery Zones', 'delivium'),
			'manage_options',
			'delivium-zones',
			array($this, 'display_plugin_zones_page')
		);

		add_submenu_page(
			'delivium',
			__('Time Slots', 'delivium'),
			__('Time Slots', 'delivium'),
			'manage_options',
			'delivium-time-slots',
			array($this, 'display_plugin_time_slots_page')
		);

		add_submenu_page(
			'delivium',
			__('Reports', 'delivium'),
			__('Reports', 'delivium'),
			'manage_options',
			'delivium-reports',
			array($this, 'display_plugin_reports_page')
		);

		add_submenu_page(
			'delivium',
			__('Settings', 'delivium'),
			__('Settings', 'delivium'),
			'manage_options',
			'delivium-settings',
			array($this, 'display_plugin_settings_page')
		);
	}

	/**
	 * Display the add driver page.
	 *
	 * @since    1.0.0
	 */
	public function display_add_driver_page() {
		if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delivium_add_driver_nonce'])) {
			if (wp_verify_nonce($_POST['delivium_add_driver_nonce'], 'delivium_add_driver')) {
				$this->handle_add_driver();
			}
		}
		include_once 'partials/delivium-admin-add-driver.php';
	}

	/**
	 * Handle the add driver form submission.
	 *
	 * @since    1.0.0
	 */
	public function handle_add_driver() {
		// Validate input
		$username = sanitize_user($_POST['username']);
		$email = sanitize_email($_POST['email']);
		$password = $_POST['password'];
		$first_name = sanitize_text_field($_POST['first_name']);
		$last_name = sanitize_text_field($_POST['last_name']);
		$phone = sanitize_text_field($_POST['phone']);

		// Check if username or email already exists
		if (username_exists($username)) {
			wp_redirect(add_query_arg('error', 'username_exists', admin_url('admin.php?page=delivium-add-driver')));
			exit;
		}

		if (email_exists($email)) {
			wp_redirect(add_query_arg('error', 'email_exists', admin_url('admin.php?page=delivium-add-driver')));
			exit;
		}

		// Create new user
		$user_id = wp_create_user($username, $password, $email);

		if (is_wp_error($user_id)) {
			wp_redirect(add_query_arg('error', 'create_failed', admin_url('admin.php?page=delivium-add-driver')));
			exit;
		}

		// Set user role
		$user = new WP_User($user_id);
		$user->set_role('delivery_driver');

		// Update user meta
		update_user_meta($user_id, 'first_name', $first_name);
		update_user_meta($user_id, 'last_name', $last_name);
		update_user_meta($user_id, 'phone', $phone);
		
		// Set driver status as available by default
		update_user_meta($user_id, 'delivium_driver_status', 'available');
		
		// Set driver capabilities
		$user->add_cap('delivium_driver');
		$user->add_cap('read');
		$user->add_cap('edit_shop_orders');
		$user->add_cap('read_shop_orders');

		// Redirect to drivers list with success message
		wp_redirect(add_query_arg('success', 'driver_added', admin_url('admin.php?page=delivium-drivers')));
		exit;
	}

	/**
	 * Add meta box to WooCommerce orders page
	 */
	public function add_order_meta_box() {
		add_meta_box(
			'delivium_driver_assignment',
			__('Delivery Driver Assignment', 'delivium'),
			array($this, 'render_order_meta_box'),
			'shop_order',
			'side',
			'high'
		);
	}

	/**
	 * Render the driver assignment meta box
	 */
	public function render_order_meta_box($post) {
		// Get current driver if assigned
		$current_driver_id = get_post_meta($post->ID, '_delivium_driver_id', true);
		
		// Get all delivery drivers
		$drivers = get_users(array(
			'role' => 'delivery_driver',
			'orderby' => 'display_name'
		));
		
		// Add nonce field
		wp_nonce_field('delivium_driver_assignment', 'delivium_driver_assignment_nonce');
		
		echo '<select name="delivium_driver_id" id="delivium_driver_id" class="widefat">';
		echo '<option value="">' . __('Select a driver', 'delivium') . '</option>';
		
		foreach ($drivers as $driver) {
			$selected = selected($current_driver_id, $driver->ID, false);
			echo '<option value="' . esc_attr($driver->ID) . '" ' . $selected . '>';
			echo esc_html($driver->display_name);
			echo '</option>';
		}
		
		echo '</select>';
	}

	/**
	 * Save the driver assignment
	 */
	public function save_order_meta_box($post_id) {
		// Check if our nonce is set
		if (!isset($_POST['delivium_driver_assignment_nonce'])) {
			return;
		}

		// Verify nonce
		if (!wp_verify_nonce($_POST['delivium_driver_assignment_nonce'], 'delivium_driver_assignment')) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}

		// Check user permissions
		if (!current_user_can('edit_post', $post_id)) {
			return;
		}

		// Get the driver ID
		$driver_id = isset($_POST['delivium_driver_id']) ? intval($_POST['delivium_driver_id']) : '';

		// Update the meta field
		if ($driver_id) {
			update_post_meta($post_id, '_delivium_driver_id', $driver_id);
			
			// Update order status to driver assigned
			$order = wc_get_order($post_id);
			if ($order) {
				$order->update_status('driver-assigned');
			}
		} else {
			delete_post_meta($post_id, '_delivium_driver_id');
		}
	}
} 