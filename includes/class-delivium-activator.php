<?php
/**
 * Fired during plugin activation
 *
 * @link       https://delivium.top
 * @since      1.0.0
 *
 * @package    Delivium
 * @subpackage Delivium/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Delivium
 * @subpackage Delivium/includes
 * @author     Delivium Team <support@delivium.top>
 */
class Delivium_Activator {

	/**
	 * Activate the plugin.
	 *
	 * @since    1.0.0
	 * @param    bool $network_wide Whether to activate network-wide.
	 */
	public function activate($network_wide) {
		if (is_multisite() && $network_wide) {
			$sites = get_sites();
			foreach ($sites as $site) {
				switch_to_blog($site->blog_id);
				$this->activate_single();
				restore_current_blog();
			}
		} else {
			$this->activate_single();
		}
	}

	/**
	 * Activate for a single site.
	 *
	 * @since    1.0.0
	 */
	private function activate_single() {
		// Create delivery driver role
		add_role(
			'delivery_driver',
			__('Delivery Driver', 'delivium'),
			array(
				'read' => true,
				'edit_posts' => false,
				'delete_posts' => false,
				'publish_posts' => false,
				'upload_files' => true,
				'view_woocommerce_reports' => true,
				'edit_shop_orders' => true,
				'read_shop_orders' => true,
				'delivium_driver' => true
			)
		);

		// Create necessary pages
		$this->create_pages();

		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Create necessary pages.
	 *
	 * @since    1.0.0
	 */
	private function create_pages() {
		// Create Driver Portal page
		$driver_portal_page = array(
			'post_title' => __('Driver Portal', 'delivium'),
			'post_content' => '[delivium_driver_portal]',
			'post_status' => 'publish',
			'post_type' => 'page',
			'post_author' => 1,
		);

		$driver_portal_id = wp_insert_post($driver_portal_page);
		if ($driver_portal_id) {
			update_option('delivium_driver_portal_page_id', $driver_portal_id);
		}

		// Create Order Tracking page
		$order_tracking_page = array(
			'post_title' => __('Track Your Order', 'delivium'),
			'post_content' => '[delivium_order_tracking]',
			'post_status' => 'publish',
			'post_type' => 'page',
			'post_author' => 1,
		);

		$order_tracking_id = wp_insert_post($order_tracking_page);
		if ($order_tracking_id) {
			update_option('delivium_order_tracking_page_id', $order_tracking_id);
		}

		// Register shortcodes
		add_shortcode('delivium_driver_portal', array('Delivium_Public', 'render_driver_portal'));
		add_shortcode('delivium_order_tracking', array('Delivium_Public', 'render_order_tracking'));
	}
} 