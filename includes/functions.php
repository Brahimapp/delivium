<?php
/**
 * Update post meta.
 *
 * @return void
 */
function delivium_update_post_meta( $order_id, $key, $value ) {
	$order = wc_get_order( $order_id );
	if ( $order ) {
		$order->update_meta_data( $key, $value );
		$order->save();
		delivium_update_sync_order( $order_id, $key, $value );
	}
}

/**
 * Delete post meta.
 *
 * @return void
 */
function delivium_delete_post_meta( $order_id, $key ) {
	$order = wc_get_order( $order_id );
	if ( $order ) {
		$order->delete_meta_data( $key );
		$order->save();
		delivium_update_sync_order( $order_id, $key, '0' );
	}
}

/**
 * Update a order row from sync table when a order is updated.
 *
 * @global object $wpdb
 * @param int $order_id Order ID
 * @param string $key Meta key
 * @param mixed $value Meta value
 */
function delivium_update_sync_order( $order_id, $key, $value ) {
	global $wpdb;

	$column = '';
	switch ( $key ) {
		case 'delivium_order_sort':
			$column = 'order_sort';
			break;
		case 'delivium_delivered_date':
			$column = 'delivered_date';
			break;
		case 'delivium_driverid':
			$column = 'driver_id';
			break;
		case 'delivium_driver_commission':
			$column = 'driver_commission';
			break;
		case 'order_refund_amount':
			$column = 'order_refund_amount';
			break;
	}

	if ( '' !== $column ) {
		if ( ! delivium_is_order_already_exists( $order_id ) ) {
			delivium_insert_orderid_to_sync_order( $order_id );
		}

		$table_name = 'delivium_orders';
		$wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . $table_name . '
			SET ' . $column . ' = %s
			WHERE order_id = %s',
				$value,
				$order_id
			)
		);
	}
}

/**
 * Update order row in sync table.
 *
 * @global object $wpdb
 * @param WC_Order $order Order object
 */
function delivium_update_all_sync_order( $order ) {
	global $wpdb;
	$table_name = 'delivium_orders';
	$store      = new DELIVIUM_Store();
	$seller_id  = $store->delivium_order_seller( $order );
	$city       = ( ! empty( $order->get_shipping_city() ) ) ? $order->get_shipping_city() : $order->get_billing_city();
	$refund     = $order->get_total_refunded();
	$wpdb->query(
		$wpdb->prepare(
			'UPDATE ' . $table_name . '
	 SET
			driver_id   = %d,
			seller_id   = %d,
			order_total = %f,
			driver_commission = %f,
			delivered_date = %s,
			order_sort = %d,
			order_refund_amount = %f,
			order_shipping_amount = %f,
			order_shipping_city = %s
	 WHERE order_id = %s',
			$order->get_meta( 'delivium_driverid' ),
			$seller_id,
			$order->get_total(),
			$order->get_meta( 'delivium_driver_commission' ),
			$order->get_meta( 'delivium_delivered_date' ),
			$order->get_meta( 'delivium_order_sort' ),
			$refund,
			$order->get_shipping_total(),
			$city,
			$order->get_id()
		)
	);
}

/**
 * Delete orders from delivium sync table when an order is deleted
 *
 * @param int $post_id Post ID
 */
function delivium_admin_on_delete_order( $post_id ) {
	$post = get_post( $post_id );

	if ( 'shop_order' == $post->post_type ) {
			delivium_delete_sync_order( $post_id );

			$sub_orders = get_children(
				array(
					'post_parent' => $post_id,
					'post_type'   => 'shop_order',
				)
			);
		if ( $sub_orders ) {
			foreach ( $sub_orders as $order_post ) {
					delivium_delete_sync_order( $order_post->ID );
			}
		}
	}
}

/**
 * Delete a order row from sync table when a order is deleted from WooCommerce.
 *
 * @global object $wpdb
 * @param int $order_id Order ID
 */
function delivium_delete_sync_order( $order_id ) {
	global $wpdb;
	$wpdb->delete( 'delivium_orders', array( 'order_id' => $order_id ) );
}

/**
 * Insert new order to sync table.
 *
 * @global object $wpdb
 * @param int $order_id Order ID
 */
function delivium_insert_sync_order_by_id( $order_id ) {
	global $wpdb;
	$order = wc_get_order( $order_id );

	if ( delivium_is_order_already_exists( $order_id ) ) {
		delivium_update_all_sync_order( $order );
		return;
	}

	delivium_insert_sync_order( $order );
}

/**
 * Check if an order with same id exists in database
 *
 * @param int $id Order ID
 * @return boolean
 */
function delivium_is_order_already_exists( $id ) {
	global $wpdb;

	if ( ! $id || ! is_numeric( $id ) ) {
		return false;
	}

	$order_id = $wpdb->get_var( $wpdb->prepare( "SELECT order_id FROM delivium_orders WHERE order_id=%d LIMIT 1", $id ) );

	return $order_id ? true : false;
}

/**
 * Insert a order row to sync table.
 *
 * @global object $wpdb
 * @param WC_Order $order WooCommerce order object
 */
function delivium_insert_sync_order( $order ) {
	global $wpdb;
	$table_name   = 'delivium_orders';
	$store        = new DELIVIUM_Store();
	$seller_id    = $store->delivium_order_seller( $order );
	$city         = ( ! empty( $order->get_shipping_city() ) ) ? $order->get_shipping_city() : $order->get_billing_city();
	$order_date   = ( ! empty( $order->get_date_created() ) ) ? $order->get_date_created()->format( 'Y-m-d H:i:s' ) : '';
	$order_status = $order->get_status();
	// Make sure order status contains "wc-" prefix.
	if ( stripos( $order_status, 'wc-' ) === false ) {
		$order_status = 'wc-' . $order_status;
	}

	// Delete duplicate orders.
	delivium_delete_sync_order( $order->get_id() );

	$wpdb->insert(
		$table_name,
		array(
			'order_id'              => $order->get_id(),
			'driver_id'             => $order->get_meta( 'delivium_driverid' ),
			'seller_id'             => $seller_id,
			'order_total'           => $order->get_total(),
			'driver_commission'     => $order->get_meta( 'delivium_driver_commission' ),
			'delivered_date'        => $order->get_meta( 'delivium_delivered_date' ),
			'order_sort'            => $order->get_meta( 'delivium_order_sort' ),
			'order_refund_amount'   => $order->get_total_refunded(),
			'order_shipping_amount' => $order->get_shipping_total(),
			'order_shipping_city'   => $city,
		),
		array(
			'%d',
			'%d',
			'%d',
			'%f',
			'%f',
			'%s',
			'%d',
			'%f',
			'%f',
			'%s',
		)
	);
}

/**
 * Create drivers panel page.
 *
 * @return void
 */
function delivium_create_drivers_panel_page() {
	if ( ! get_option( 'delivium_delivery_drivers_page', false ) ) {
		$array   = array(
			'post_title'     => 'Delivery Driver App',
			'post_type'      => 'page',
			'post_name'      => 'driver',
			'post_status'    => 'publish',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		);
		$page_id = wp_insert_post( $array );
		update_option( 'delivium_delivery_drivers_page', $page_id );
	}
}

/**
 * Create tracking page.
 */
function delivium_create_tracking_page() {
	$tracking_page = get_page_by_path('delivery-tracking');
	if (!$tracking_page) {
		$page_data = array(
			'post_title'    => __('Delivery Tracking', 'delivium'),
			'post_content'  => '[delivium_tracking]',
			'post_status'   => 'publish',
			'post_type'     => 'page',
			'post_name'     => 'delivery-tracking'
		);
		wp_insert_post($page_data);
	}
}

/**
 * Delete tracking table entries.
 */
function delivium_delete_from_tracking_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'delivium_tracking';
	$wpdb->query("DELETE FROM $table_name WHERE last_update < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
}

/**
 * Create tracking table.
 */
function delivium_create_tracking_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'delivium_tracking';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		driver_id bigint(20) NOT NULL,
		latitude decimal(10,8) NOT NULL,
		longitude decimal(11,8) NOT NULL,
		last_update datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY driver_id (driver_id),
		KEY last_update (last_update)
	) $charset_collate;";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
}

/**
 * Plugin activation hook.
 */
function delivium_activate() {
	delivium_create_tracking_table();
	delivium_create_tracking_page();
}

/**
 * Replace template tags.
 *
 * @param string $content Content with tags.
 * @param int    $order_id Order ID.
 * @param object $order Order object.
 * @param int    $driver_id Driver ID.
 * @return string
 */
function delivium_replace_tags($content, $order_id, $order, $driver_id) {
	$store = new Delivium_Store();
	$seller_id = $store->order_seller($order, false);
	$store_name = $store->store_name($order, $seller_id);
	$store_phone = $store->store_phone($order, $seller_id);
	$store_address = $store->store_address('text');

	$driver = get_userdata($driver_id);
	$driver_name = $driver ? $driver->display_name : '';
	$driver_phone = get_user_meta($driver_id, 'billing_phone', true);

	$tracking_url = delivium_tracking_page_url($order_id);
	$order_number = $order->get_order_number();
	$customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
	$delivery_address = $order->get_formatted_shipping_address();

	$tags = array(
		'{store_name}' => $store_name,
		'{store_phone}' => $store_phone,
		'{store_address}' => $store_address,
		'{driver_name}' => $driver_name,
		'{driver_phone}' => $driver_phone,
		'{tracking_url}' => $tracking_url,
		'{order_number}' => $order_number,
		'{customer_name}' => $customer_name,
		'{delivery_address}' => $delivery_address
	);

	return str_replace(array_keys($tags), array_values($tags), $content);
}

/**
 * Get tracking page URL.
 *
 * @param int $order_id Order ID.
 * @return string
 */
function delivium_tracking_page_url($order_id) {
	$tracking_page = get_page_by_path('delivery-tracking');
	if ($tracking_page) {
		$params = '';
		if ($order_id) {
			$order = wc_get_order($order_id);
			if ($order) {
				$order_key = $order->get_order_key();
				$order_key = str_replace('wc_order_', '', $order_key);
				$params = 'k=' . $order_key;
			}
		}

		$link = get_permalink($tracking_page->ID);
		if ($params) {
			$link .= (strpos($link, '?') !== false ? '&' : '?') . $params;
		}
		return $link;
	}
	return '';
}

/**
 * Create order sync table
 *
 * @return void
 */
function delivium_create_sync_table() {
	global $wpdb;
	include_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$sql = 'CREATE TABLE IF NOT EXISTS delivium_orders (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		order_id varchar(100) NOT NULL DEFAULT "0",
		driver_id bigint(20) DEFAULT 0,
		seller_id bigint(20) DEFAULT 0,
		order_total decimal(19,4) DEFAULT 0,
		order_refund_amount decimal(19,4) DEFAULT 0,
		order_sort bigint(20) DEFAULT 0,
		order_shipping_amount decimal(19,4) DEFAULT 0,
		order_shipping_city varchar(200) DEFAULT NULL,
		driver_commission decimal(19,4) DEFAULT 0,
		delivered_date varchar(50) DEFAULT NULL,
		PRIMARY KEY (id),
		KEY order_id (order_id),
		KEY driver_id (driver_id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
	dbDelta($sql);
}

/**
 * Check plugin db
 *
 * @return void
 */
function delivium_update_db_check() {
	if ( '2' !== get_option( 'delivium_sync_table', '' ) ) {
		delivium_create_sync_table();
		delivium_sync_table();
	}
}

/**
 * Sync table
 *
 * @return void
 */
function delivium_sync_table() {
	global $wpdb;

	// If plugin has been upgraded we sync table once.
	if ('2' !== get_option('delivium_sync_table', '')) {
		$wpdb->query("TRUNCATE TABLE delivium_orders");

		// Get all orders with drivers assigned
		$orders = wc_get_orders(array(
			'limit' => -1,
			'meta_key' => 'delivium_driverid',
			'meta_compare' => 'EXISTS'
		));

		foreach ($orders as $order) {
			delivium_insert_sync_order($order);
		}

		// Add option that sync table has been synced
		update_option('delivium_sync_table', '2');
	}
}

/**
 * Insert orderid to sync table.
 *
 * @param string $order_id The order ID
 * @return void
 */
function delivium_insert_orderid_to_sync_order($order_id) {
	global $wpdb;
	$table_name = 'delivium_orders';
	$wpdb->insert($table_name, array('order_id' => $order_id), array('%s'));
}

/**
 * Update refund in sync table.
 *
 * @param int $order_id Order ID
 * @param int $refund_id Refund ID
 * @return void
 */
function delivium_woocommerce_order_refunded( $order_id, $refund_id ) {
	if ( ! delivium_is_order_already_exists( $order_id ) ) {
		delivium_insert_orderid_to_sync_order( $order_id );
	}

	$order = wc_get_order( $order_id );
	delivium_update_all_sync_order( $order );
}

/**
 * Check if feature is premium.
 *
 * @param string $value Feature value
 * @return string
 */
function delivium_admin_premium_feature($value) {
	if (defined('DELIVIUM_PREMIUM') && DELIVIUM_PREMIUM) {
		return $value;
	}
	return '';
}

/**
 * Format international phone number
 *
 * @param string $country_code Country code
 * @param string $phone Phone number
 * @return string
 */
function delivium_get_international_phone_number( $country_code, $phone ) {
	$phone = preg_replace( '/[^0-9+]*/', '', $phone );

	// if phone number diesnt include + we format the number by country calling code.
	if ( strpos( $phone, '+' ) === false && '' !== $country_code ) {
			$calling_code      = WC()->countries->get_country_calling_code( $country_code );
			$calling_code      = is_array( $calling_code ) ? $calling_code[0] : $calling_code;
			$preg_calling_code = str_replace( '+', '', $calling_code );
			$preg              = '/^(?:\+?' . $preg_calling_code . '|0)?/';
			$phone             = preg_replace( $preg, $calling_code, $phone );
			$phone             = str_replace( $calling_code . '0', $calling_code, $phone );
	}
		 return $phone;
}

/**
 * Get allowed HTML tags
 *
 * @return array
 */
function delivium_allowed_html() {
	return array(
		'abbr'       => array(),
		'b'          => array(),
		'blockquote' => array(),
		'cite'       => array(),
		'code'       => array(),
		'del'        => array(),
		'dd'         => array(),
		'div'        => array(),
		'dl'         => array(),
		'dt'         => array(),
		'em'         => array(),
		'h1'         => array(),
		'h2'         => array(),
		'h3'         => array(),
		'h4'         => array(),
		'h5'         => array(),
		'h6'         => array(),
		'i'          => array(),
		'img'        => array(
			'alt'    => array(),
			'class'  => array(),
			'height' => array(),
			'src'    => array(),
			'width'  => array(),
		),
		'li'         => array(),
		'ol'         => array(),
		'p'          => array(),
		'q'          => array(),
		'span'       => array(),
		'strike'     => array(),
		'strong'     => array(),
		'ul'         => array(),
	);
}

/**
 * Get driver app mode.
 *
 * @param string $driver_id Driver ID
 * @return string
 */
function delivium_get_app_mode($driver_id) {
	if (!defined('DELIVIUM_PREMIUM') || !DELIVIUM_PREMIUM) {
		return '';
	}

	$app_mode = '';
	if ($driver_id) {
		// Get user app mode.
		$app_mode = get_user_meta($driver_id, 'delivium_driver_app_mode', true);
	}
	// If empty get admin setting app mode.
	return $app_mode ? $app_mode : get_option('delivium_app_mode', '');
}

/**
 * Get map language.
 *
 * @return string
 */
function delivium_get_map_language() {
	$language = get_locale();
	if (strlen($language) > 0) {
		$language = explode('_', $language)[0];
	} else {
		$language = 'en';
	}
	return $language;
}

/**
 * Get map center coordinates.
 *
 * @param int $order_id Order ID
 * @param int $driver_id Driver ID
 * @return string
 */
function delivium_get_map_center($order_id, $driver_id) {
	$result = '';
	$latitude = get_option('delivium_store_address_latitude');
	$longitude = get_option('delivium_store_address_longitude');
	if ($longitude && $latitude && $longitude !== '0' && $latitude !== '0') {
		$result = $latitude . ',' . $longitude;
	}
	return $result;
}

/**
 * Convert seconds to human readable time.
 *
 * @param int $seconds Number of seconds
 * @return string
 */
function delivium_convert_seconds_to_words($seconds) {
	$hours = ($seconds / 60 / 60);
	$rhours = floor($hours);
	$minutes = ($hours - $rhours) * 60;
	$rminutes = floor($minutes);
	$result = '';

	if ((int)$rhours > 1) {
		$result = $rhours . ' ' . esc_html__('hours', 'delivium') . ' ';
	}
	if ((int)$rhours === 1) {
		$result = $rhours . ' ' . esc_html__('hour', 'delivium') . ' ';
	}
	if ((int)$rminutes > 0) {
		$result .= $rminutes . ' ' . esc_html__('mins', 'delivium');
	}
	return $result;
}


