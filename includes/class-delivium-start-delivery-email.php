<?php
/**
 * The start delivery email functionality of the plugin.
 *
 * @link       https://delivium.top
 * @since      1.0.0
 *
 * @package    Delivium
 * @subpackage Delivium/includes
 */

/**
 * The start delivery email functionality of the plugin.
 *
 * Handles sending email notifications when a delivery starts.
 *
 * @package    Delivium
 * @subpackage Delivium/includes
 * @author     Delivium Team <support@delivium.top>
 */
class Delivium_Start_Delivery_Email {

    /**
     * Send start delivery notification email.
     *
     * @param int $order_id Order ID.
     * @return bool
     */
    public function send_email($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }

        $driver_id = $order->get_meta('delivium_driverid');
        if (!$driver_id) {
            return false;
        }

        $driver = get_userdata($driver_id);
        if (!$driver) {
            return false;
        }

        $to = $order->get_billing_email();
        $subject = sprintf(
            __('Your order #%s delivery has started', 'delivium'),
            $order->get_order_number()
        );

        ob_start();
        include DELIVIUM_PLUGIN_PATH . 'templates/emails/start-delivery.php';
        $message = ob_get_clean();

        $headers = array('Content-Type: text/html; charset=UTF-8');

        return wp_mail($to, $subject, $message, $headers);
    }

    /**
     * Get email template variables.
     *
     * @param WC_Order $order Order object.
     * @param WP_User  $driver Driver user object.
     * @return array
     */
    private function get_template_vars($order, $driver) {
        return array(
            'order_id' => $order->get_order_number(),
            'customer_name' => $order->get_formatted_billing_full_name(),
            'driver_name' => $driver->display_name,
            'driver_phone' => get_user_meta($driver->ID, 'delivium_driver_phone', true),
            'estimated_delivery' => $this->get_estimated_delivery_time($order),
            'tracking_url' => $this->get_tracking_url($order),
        );
    }

    /**
     * Get estimated delivery time.
     *
     * @param WC_Order $order Order object.
     * @return string
     */
    private function get_estimated_delivery_time($order) {
        $eta = $order->get_meta('delivium_estimated_delivery_time');
        if (!$eta) {
            return __('Not available', 'delivium');
        }
        return date_i18n(get_option('time_format'), strtotime($eta));
    }

    /**
     * Get tracking URL.
     *
     * @param WC_Order $order Order object.
     * @return string
     */
    private function get_tracking_url($order) {
        $tracking_page = get_option('delivium_tracking_page');
        if (!$tracking_page) {
            return '';
        }

        return add_query_arg('order_id', $order->get_id(), get_permalink($tracking_page));
    }
} 