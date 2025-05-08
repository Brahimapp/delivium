<?php
/**
 * The driver functionality of the plugin.
 *
 * @link       https://delivium.top
 * @since      1.0.0
 *
 * @package    Delivium
 * @subpackage Delivium/includes
 */

/**
 * The driver functionality of the plugin.
 *
 * Defines the plugin name, version, and driver-related functionality.
 *
 * @package    Delivium
 * @subpackage Delivium/includes
 * @author     Delivium Team <support@delivium.top>
 */
class DELIVIUM_Driver {

    /**
     * Get driver details.
     *
     * @param int $driver_id Driver ID.
     * @return array
     */
    public function get_driver_details($driver_id) {
        if (!defined('DELIVIUM_PREMIUM') || !DELIVIUM_PREMIUM) {
            return array();
        }

        $driver = get_userdata($driver_id);
        if ($driver) {
            return array(
                'id' => $driver_id,
                'name' => $driver->display_name,
                'phone' => get_user_meta($driver_id, 'delivium_driver_phone', true),
                'availability' => get_user_meta($driver_id, 'delivium_driver_availability', true),
                'account_status' => get_user_meta($driver_id, 'delivium_driver_account', true),
            );
        }
        return array();
    }

    /**
     * Update driver availability.
     *
     * @param int    $driver_id Driver ID.
     * @param string $status    Availability status.
     * @return bool
     */
    public function update_availability($driver_id, $status) {
        if (!defined('DELIVIUM_PREMIUM') || !DELIVIUM_PREMIUM) {
            return false;
        }
        return update_user_meta($driver_id, 'delivium_driver_availability', $status);
    }

    /**
     * Get driver's current orders.
     *
     * @param int $driver_id Driver ID.
     * @return array
     */
    public function get_current_orders($driver_id) {
        if (!defined('DELIVIUM_PREMIUM') || !DELIVIUM_PREMIUM) {
            return array();
        }

        global $wpdb;
        
        $orders = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.ID, p.post_status
                FROM {$wpdb->posts} p
                JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE pm.meta_key = 'delivium_driverid'
                AND pm.meta_value = %d
                AND p.post_type = 'shop_order'
                AND p.post_status IN ('wc-processing', %s, %s, %s)",
                $driver_id,
                get_option('delivium_driver_assigned_status'),
                get_option('delivium_out_for_delivery_status'),
                get_option('delivium_failed_attempt_status')
            )
        );

        return $orders;
    }

    /**
     * Get driver information.
     *
     * @param int    $driver_id Driver ID.
     * @param string $format    Output format (html/text).
     * @param bool   $with_link Include link to driver profile.
     * @return string
     */
    public function get_driver_info($driver_id, $format = 'html', $with_link = false) {
        $driver = get_userdata($driver_id);
        if (!$driver) {
            return '';
        }

        $driver_name = $driver->display_name;
        $driver_phone = get_user_meta($driver_id, 'billing_phone', true);
        
        if ($format === 'html') {
            $info = '<p><strong>' . __('Driver Name:', 'delivium') . '</strong> ';
            if ($with_link && defined('DELIVIUM_PREMIUM') && DELIVIUM_PREMIUM) {
                $info .= '<a href="' . esc_url(get_edit_user_link($driver_id)) . '">' . esc_html($driver_name) . '</a>';
            } else {
                $info .= esc_html($driver_name);
            }
            $info .= '</p>';
            if ($driver_phone) {
                $info .= '<p><strong>' . __('Driver Phone:', 'delivium') . '</strong> ' . esc_html($driver_phone) . '</p>';
            }
            return $info;
        } else {
            $info = __('Driver Name:', 'delivium') . ' ' . $driver_name . "\n";
            if ($driver_phone) {
                $info .= __('Driver Phone:', 'delivium') . ' ' . $driver_phone . "\n";
            }
            return $info;
        }
    }

    /**
     * Get vehicle information.
     *
     * @param int    $driver_id Driver ID.
     * @param string $format    Output format (html/text).
     * @return string
     */
    public function get_vehicle_info($driver_id, $format = 'html') {
        if (!defined('DELIVIUM_PREMIUM') || !DELIVIUM_PREMIUM) {
            return '';
        }

        $vehicle_type = get_user_meta($driver_id, 'delivium_vehicle_type', true);
        $vehicle_model = get_user_meta($driver_id, 'delivium_vehicle_model', true);
        $vehicle_color = get_user_meta($driver_id, 'delivium_vehicle_color', true);
        $vehicle_plate = get_user_meta($driver_id, 'delivium_vehicle_plate', true);

        if (!$vehicle_type && !$vehicle_model && !$vehicle_color && !$vehicle_plate) {
            return '';
        }

        if ($format === 'html') {
            $info = '<div class="vehicle-info">';
            if ($vehicle_type) {
                $info .= '<p><strong>' . __('Vehicle Type:', 'delivium') . '</strong> ' . esc_html($vehicle_type) . '</p>';
            }
            if ($vehicle_model) {
                $info .= '<p><strong>' . __('Vehicle Model:', 'delivium') . '</strong> ' . esc_html($vehicle_model) . '</p>';
            }
            if ($vehicle_color) {
                $info .= '<p><strong>' . __('Vehicle Color:', 'delivium') . '</strong> ' . esc_html($vehicle_color) . '</p>';
            }
            if ($vehicle_plate) {
                $info .= '<p><strong>' . __('License Plate:', 'delivium') . '</strong> ' . esc_html($vehicle_plate) . '</p>';
            }
            $info .= '</div>';
            return $info;
        } else {
            $info = '';
            if ($vehicle_type) {
                $info .= __('Vehicle Type:', 'delivium') . ' ' . $vehicle_type . "\n";
            }
            if ($vehicle_model) {
                $info .= __('Vehicle Model:', 'delivium') . ' ' . $vehicle_model . "\n";
            }
            if ($vehicle_color) {
                $info .= __('Vehicle Color:', 'delivium') . ' ' . $vehicle_color . "\n";
            }
            if ($vehicle_plate) {
                $info .= __('License Plate:', 'delivium') . ' ' . $vehicle_plate . "\n";
            }
            return $info;
        }
    }

    // ... rest of the class implementation ...
} 