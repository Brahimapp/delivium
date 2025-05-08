<?php
/**
 * The SMS functionality of the plugin.
 *
 * @link       https://delivium.top
 * @since      1.0.0
 *
 * @package    Delivium
 * @subpackage Delivium/includes
 * @author     Delivium Team <support@delivium.top>
 */

/**
 * Plugin SMS.
 *
 * All the SMS functions.
 *
 * @package    Delivium
 * @subpackage Delivium/includes
 * @author     Delivium Team <support@delivium.top>
 */

/**
 * Plugin SMS.
 *
 * All the SMS functions.
 *
 * @package    Delivium
 * @subpackage Delivium/includes
 * @author     Delivium Team <support@delivium.top>
 */
class Delivium_SMS {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Check sms
	 *
	 * @param string $to_number sms number.
	 * @param string $sms_text sms content.
	 * @return array
	 */
	public function check_sms( $to_number, $sms_text ) {
		$sms_provider = get_option( 'delivium_sms_provider', '' );
		if ( '' === $sms_provider ) {
			return array( 0, __( 'Failed to send SMS, the SMS provider is missing.', 'delivium' ) );
		}
		if ( 'twilio' !== $sms_provider && 'nexmo' !== $sms_provider ) {
			return array( 0, __( 'Failed to send SMS, the SMS provider is not supported.', 'delivium' ) );
		}

		if ( 'twilio' === $sms_provider ) {
			$sid = get_option( 'delivium_twilio_account_sid', '' );
			if ( '' === $sid ) {
				return array( 0, __( 'Failed to send SMS, the SID is missing.', 'delivium' ) );
			}

			$auth_token = get_option( 'delivium_twilio_auth_token', '' );
			if ( '' === $auth_token ) {
				return array( 0, __( 'Failed to send SMS, the auth token is missing.', 'delivium' ) );
			}

			$from_number = get_option( 'delivium_twilio_from_number', '' );
			if ( '' === $from_number ) {
				return array( 0, __( 'Failed to send SMS, the SMS phone number is missing.', 'delivium' ) );
			}
		} else {
			$api_key = get_option( 'delivium_nexmo_api_key', '' );
			if ( '' === $api_key ) {
				return array( 0, __( 'Failed to send SMS, the API key is missing.', 'delivium' ) );
			}

			$api_secret = get_option( 'delivium_nexmo_api_secret', '' );
			if ( '' === $api_secret ) {
				return array( 0, __( 'Failed to send SMS, the API secret is missing.', 'delivium' ) );
			}

			$from_number = get_option( 'delivium_nexmo_from_number', '' );
			if ( '' === $from_number ) {
				return array( 0, __( 'Failed to send SMS, the SMS phone number is missing.', 'delivium' ) );
			}
		}

		if ( '' === $to_number ) {
			return array( 0, __( 'Failed to send SMS, the phone number is missing.', 'delivium' ) );
		}
		if ( '' === $sms_text ) {
			return array( 0, __( 'Failed to send SMS, the SMS text is missing.', 'delivium' ) );
		}

		return array( 1, 'ok', 'delivium' );
	}

	/**
	 * Send sms to customer
	 *
	 * @param int    $order_id order number.
	 * @param object $order order object.
	 * @param int    $order_status order status.
	 * @return array
	 */
	public function send_sms_to_customer( $order_id, $order, $order_status ) {
		$driver_id             = $order->get_meta( 'delivium_driverid' );
		$country_code          = $order->get_billing_country();
		$customer_phone_number = $order->get_billing_phone();

		$sms_text = '';
		if ( get_option( 'delivium_out_for_delivery_status', '' ) === 'wc-' . $order_status ) {
			$sms_text = get_option( 'delivium_sms_out_for_delivery_template', '' );
		}

		if ( 'start_delivery' === $order_status ) {
			$sms_text = get_option( 'delivium_sms_start_delivery_template', '' );
		}

		$result = $this->check_sms( $customer_phone_number, $sms_text );
		if ( 0 === $result[0] ) {
			return $result;
		}

		$customer_phone_number = delivium_get_international_phone_number( $country_code, $customer_phone_number );

		$sms_text = delivium_replace_tags( $sms_text, $order_id, $order, $driver_id );

		return $this->send_sms( $sms_text, $customer_phone_number );
	}

	/**
	 * Send sms to driver
	 *
	 * @param int    $order_id order number.
	 * @param object $order order object.
	 * @param int    $driver_id user id number.
	 * @return array
	 */
	public function send_sms_to_driver( $order_id, $order, $driver_id ) {
		$country_code        = get_user_meta( $driver_id, 'billing_country', true );
		$driver_phone_number = get_user_meta( $driver_id, 'billing_phone', true );
		$sms_text            = get_option( 'delivium_sms_assign_to_driver_template', '' );

		$result = $this->check_sms( $driver_phone_number, $sms_text );
		if ( 0 === $result[0] ) {
			return $result;
		}

		$driver_phone_number = delivium_get_international_phone_number( $country_code, $driver_phone_number );
		$sms_text            = delivium_replace_tags( $sms_text, $order_id, $order, $driver_id );
		return $this->send_sms( $sms_text, $driver_phone_number );
	}

	/**
	 * Send SMS
	 *
	 * @param string $to recipient phone number.
	 * @param string $message message content.
	 * @return bool
	 */
	public function send_sms($to, $message) {
		$provider = get_option('delivium_sms_provider', '');
		$settings = get_option('delivium_sms_settings', array());

		if (empty($provider) || empty($settings)) {
			return false;
		}

		switch ($provider) {
			case 'twilio':
				return $this->send_twilio_sms($to, $message, $settings);
			case 'messagebird':
				return $this->send_messagebird_sms($to, $message, $settings);
			case 'nexmo':
				return $this->send_nexmo_sms($to, $message, $settings);
			default:
				return false;
		}
	}

	/**
	 * Send Twilio SMS
	 *
	 * @param string $to recipient phone number.
	 * @param string $message message content.
	 * @param array $settings Provider settings.
	 * @return bool
	 */
	private function send_twilio_sms($to, $message, $settings) {
		if (empty($settings['account_sid']) || empty($settings['auth_token']) || empty($settings['from_number'])) {
			return false;
		}

		$url = "https://api.twilio.com/2010-04-01/Accounts/{$settings['account_sid']}/Messages.json";
		
		$args = array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode($settings['account_sid'] . ':' . $settings['auth_token'])
			),
			'body' => array(
				'To' => $to,
				'From' => $settings['from_number'],
				'Body' => $message
			)
		);

		$response = wp_remote_post($url, $args);

		return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 201;
	}

	/**
	 * Send MessageBird SMS
	 *
	 * @param string $to recipient phone number.
	 * @param string $message message content.
	 * @param array $settings Provider settings.
	 * @return bool
	 */
	private function send_messagebird_sms($to, $message, $settings) {
		if (empty($settings['api_key']) || empty($settings['originator'])) {
			return false;
		}

		$url = 'https://rest.messagebird.com/messages';
		
		$args = array(
			'headers' => array(
				'Authorization' => 'AccessKey ' . $settings['api_key'],
				'Content-Type' => 'application/json'
			),
			'body' => json_encode(array(
				'recipients' => array($to),
				'originator' => $settings['originator'],
				'body' => $message
			))
		);

		$response = wp_remote_post($url, $args);

		return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 201;
	}

	/**
	 * Send Nexmo SMS
	 *
	 * @param string $to recipient phone number.
	 * @param string $message message content.
	 * @param array $settings Provider settings.
	 * @return bool
	 */
	private function send_nexmo_sms($to, $message, $settings) {
		if (empty($settings['api_key']) || empty($settings['api_secret']) || empty($settings['from'])) {
			return false;
		}

		$url = 'https://rest.nexmo.com/sms/json';
		
		$args = array(
			'body' => array(
				'api_key' => $settings['api_key'],
				'api_secret' => $settings['api_secret'],
				'to' => $to,
				'from' => $settings['from'],
				'text' => $message
			)
		);

		$response = wp_remote_post($url, $args);

		if (is_wp_error($response)) {
			return false;
		}

		$body = json_decode(wp_remote_retrieve_body($response), true);
		return isset($body['messages'][0]['status']) && $body['messages'][0]['status'] === '0';
	}

	/**
	 * Get SMS settings
	 *
	 * @return array
	 */
	public function get_sms_settings() {
		$settings = array(
			'provider' => get_option( 'delivium_sms_provider', '' ),
			'twilio' => array(
				'account_sid' => get_option( 'delivium_twilio_account_sid', '' ),
				'auth_token' => get_option( 'delivium_twilio_auth_token', '' ),
				'from_number' => get_option( 'delivium_twilio_from_number', '' ),
			),
			'nexmo' => array(
				'api_key' => get_option( 'delivium_nexmo_api_key', '' ),
				'api_secret' => get_option( 'delivium_nexmo_api_secret', '' ),
				'from_number' => get_option( 'delivium_nexmo_from_number', '' ),
			),
		);

		return $settings;
	}

	/**
	 * Update SMS settings
	 *
	 * @param array $settings settings.
	 * @return bool
	 */
	public function update_sms_settings( $settings ) {
		if ( isset( $settings['provider'] ) ) {
			update_option( 'delivium_sms_provider', $settings['provider'] );
		}

		if ( isset( $settings['twilio'] ) ) {
			if ( isset( $settings['twilio']['account_sid'] ) ) {
				update_option( 'delivium_twilio_account_sid', $settings['twilio']['account_sid'] );
			}
			if ( isset( $settings['twilio']['auth_token'] ) ) {
				update_option( 'delivium_twilio_auth_token', $settings['twilio']['auth_token'] );
			}
			if ( isset( $settings['twilio']['from_number'] ) ) {
				update_option( 'delivium_twilio_from_number', $settings['twilio']['from_number'] );
			}
		}

		if ( isset( $settings['nexmo'] ) ) {
			if ( isset( $settings['nexmo']['api_key'] ) ) {
				update_option( 'delivium_nexmo_api_key', $settings['nexmo']['api_key'] );
			}
			if ( isset( $settings['nexmo']['api_secret'] ) ) {
				update_option( 'delivium_nexmo_api_secret', $settings['nexmo']['api_secret'] );
			}
			if ( isset( $settings['nexmo']['from_number'] ) ) {
				update_option( 'delivium_nexmo_from_number', $settings['nexmo']['from_number'] );
			}
		}

		return true;
	}

	/**
	 * Send notification to driver about new order.
	 *
	 * @param int $order_id Order ID.
	 * @param int $driver_id Driver ID.
	 * @return bool
	 */
	public function notify_driver_new_order($order_id, $driver_id) {
		$notification_settings = get_option('delivium_notification_settings', array());
		if (empty($notification_settings['notify_driver_new_order'])) {
			return false;
		}

		$driver_phone = get_user_meta($driver_id, 'phone', true);
		if (empty($driver_phone)) {
			return false;
		}

		$message = sprintf(
			__('New order #%s is available for delivery. Log in to your driver portal to claim it.', 'delivium'),
			$order_id
		);

		return $this->send_sms($driver_phone, $message);
	}

	/**
	 * Send notification to driver about assigned order.
	 *
	 * @param int $order_id Order ID.
	 * @param int $driver_id Driver ID.
	 * @return bool
	 */
	public function notify_driver_assigned($order_id, $driver_id) {
		$notification_settings = get_option('delivium_notification_settings', array());
		if (empty($notification_settings['notify_driver_assigned'])) {
			return false;
		}

		$driver_phone = get_user_meta($driver_id, 'phone', true);
		if (empty($driver_phone)) {
			return false;
		}

		$message = sprintf(
			__('Order #%s has been assigned to you. Please check your driver portal for details.', 'delivium'),
			$order_id
		);

		return $this->send_sms($driver_phone, $message);
	}

	/**
	 * Send notification to customer about driver assignment.
	 *
	 * @param int $order_id Order ID.
	 * @return bool
	 */
	public function notify_customer_driver_assigned($order_id) {
		$notification_settings = get_option('delivium_notification_settings', array());
		if (empty($notification_settings['notify_customer_driver_assigned'])) {
			return false;
		}

		$order = wc_get_order($order_id);
		if (!$order) {
			return false;
		}

		$phone = $order->get_billing_phone();
		if (empty($phone)) {
			return false;
		}

		$driver_id = get_post_meta($order_id, '_delivium_driver_id', true);
		$driver = get_userdata($driver_id);
		
		$message = sprintf(
			__('Your order #%s has been assigned to %s. Track your delivery at: %s', 'delivium'),
			$order_id,
			$driver->display_name,
			get_permalink(get_option('delivium_tracking_page_id'))
		);

		return $this->send_sms($phone, $message);
	}
}
