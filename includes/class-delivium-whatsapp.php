<?php
/**
 * The WhatsApp functionality of the plugin.
 *
 * @link       https://delivium.top
 * @since      1.0.0
 *
 * @package    Delivium
 * @subpackage Delivium/includes
 * @author     Delivium Team <support@delivium.top>
 */

/**
 * The WhatsApp functionality of the plugin.
 *
 * @since      1.0.0
 * @package    Delivium
 * @subpackage Delivium/includes
 * @author     Delivium Team <support@delivium.top>
 */
class Delivium_WhatsApp {

	/**
	 * Check WhatsApp configuration.
	 *
	 * @param string $to_number WhatsApp number.
	 * @param string $message WhatsApp message.
	 * @return array
	 */
	public function check_whatsapp($to_number, $message) {
		if (!defined('DELIVIUM_PREMIUM') || !DELIVIUM_PREMIUM) {
			return array(0, __('WhatsApp functionality is only available in the premium version.', 'delivium'));
		}

		if (empty($to_number)) {
			return array(0, __('Failed to send WhatsApp, the phone number is missing.', 'delivium'));
		}
		if (empty($message)) {
			return array(0, __('Failed to send WhatsApp, the message is missing.', 'delivium'));
		}

		$provider = get_option('delivium_whatsapp_provider', '');
		if (empty($provider)) {
			return array(0, __('Failed to send WhatsApp, the provider is missing.', 'delivium'));
		}

		return array(1, 'ok');
	}

	/**
	 * Send WhatsApp to customer.
	 *
	 * @param int $order_id Order ID.
	 * @param object $order Order object.
	 * @param string $order_status Order status.
	 * @return array
	 */
	public function send_whatsapp_to_customer($order_id, $order, $order_status) {
		if (!defined('DELIVIUM_PREMIUM') || !DELIVIUM_PREMIUM) {
			return array(0, __('WhatsApp functionality is only available in the premium version.', 'delivium'));
		}

		$country_code = $order->get_billing_country();
		$customer_phone = $order->get_billing_phone();

		$message = '';
		if (get_option('delivium_out_for_delivery_status', '') === 'wc-' . $order_status) {
			$message = get_option('delivium_whatsapp_out_for_delivery_template', '');
		} elseif ('start_delivery' === $order_status) {
			$message = get_option('delivium_whatsapp_start_delivery_template', '');
		}

		$result = $this->check_whatsapp($customer_phone, $message);
		if ($result[0] === 0) {
			return $result;
		}

		$customer_phone = delivium_get_international_phone_number($country_code, $customer_phone);
		$message = delivium_replace_tags($message, $order_id, $order, $order->get_meta('delivium_driverid'));

		return $this->send_whatsapp($message, $customer_phone);
	}

	/**
	 * Send WhatsApp to driver.
	 *
	 * @param int $order_id Order ID.
	 * @param object $order Order object.
	 * @param int $driver_id Driver ID.
	 * @return array
	 */
	public function send_whatsapp_to_driver($order_id, $order, $driver_id) {
		$country_code = get_user_meta($driver_id, 'billing_country', true);
		$driver_phone = get_user_meta($driver_id, 'billing_phone', true);
		$message = get_option('delivium_whatsapp_assign_to_driver_template', '');

		$result = $this->check_whatsapp($driver_phone, $message);
		if ($result[0] === 0) {
			return $result;
		}

		$driver_phone = delivium_get_international_phone_number($country_code, $driver_phone);
		$message = delivium_replace_tags($message, $order_id, $order, $driver_id);

		return $this->send_whatsapp($message, $driver_phone);
	}

	/**
	 * Send WhatsApp message.
	 *
	 * @param string $message Message content.
	 * @param string $to_number Recipient number.
	 * @return array
	 */
	public function send_whatsapp($message, $to_number) {
		$provider = get_option('delivium_whatsapp_provider', '');
		
		if ($provider === 'twilio') {
			$account_sid = get_option('delivium_whatsapp_twilio_sid', '');
			$auth_token = get_option('delivium_whatsapp_twilio_token', '');
			$from_number = get_option('delivium_whatsapp_twilio_from', '');
			
			return $this->send_whatsapp_twilio($message, $from_number, $to_number, $account_sid, $auth_token);
		}

		return array(0, __('Unsupported WhatsApp provider.', 'delivium'));
	}

	/**
	 * Send WhatsApp message via Twilio.
	 *
	 * @param string $message Message content.
	 * @param string $from_number Sender number.
	 * @param string $to_number Recipient number.
	 * @param string $account_sid Twilio Account SID.
	 * @param string $auth_token Twilio Auth Token.
	 * @return array
	 */
	private function send_whatsapp_twilio($message, $from_number, $to_number, $account_sid, $auth_token) {
		$url = "https://api.twilio.com/2010-04-01/Accounts/{$account_sid}/Messages.json";
		
		$args = array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode($account_sid . ':' . $auth_token)
			),
			'body' => array(
				'To' => "whatsapp:{$to_number}",
				'From' => "whatsapp:{$from_number}",
				'Body' => $message
			)
		);

		$response = wp_remote_post($url, $args);

		if (is_wp_error($response)) {
			return array(0, $response->get_error_message());
		}

		$body = json_decode(wp_remote_retrieve_body($response), true);
		
		if (isset($body['sid'])) {
			return array(1, sprintf(__('WhatsApp message sent successfully to %s', 'delivium'), $to_number));
		} else {
			return array(0, sprintf(__('Failed to send WhatsApp message to %s', 'delivium'), $to_number));
		}
	}
}
