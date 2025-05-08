<?php
/**
 * The screens functionality of the plugin.
 *
 * @link       https://delivium.top
 * @since      1.0.0
 *
 * @package    Delivium
 * @subpackage Delivium/includes
 */

/**
 * The screens functionality of the plugin.
 *
 * Defines the plugin name, version, and screens-related functionality.
 *
 * @package    Delivium
 * @subpackage Delivium/includes
 * @author     Delivium Team <support@delivium.top>
 */
class DELIVIUM_Screens {

    /**
     * Generate the home screen content.
     *
     * @return string
     */
    public function delivium_home() {
        ob_start();
        include_once DELIVIUM_PLUGIN_PATH . 'public/partials/screens/home.php';
        return ob_get_clean();
    }

    /**
     * Generate the dashboard screen content.
     *
     * @param int $driver_id Driver ID.
     * @return string
     */
    public function delivium_dashboard_screen($driver_id) {
        ob_start();
        include_once DELIVIUM_PLUGIN_PATH . 'public/partials/screens/dashboard.php';
        return ob_get_clean();
    }

    /**
     * Generate the orders screen content.
     *
     * @param int $driver_id Driver ID.
     * @return string
     */
    public function delivium_orders_screen($driver_id) {
        ob_start();
        include_once DELIVIUM_PLUGIN_PATH . 'public/partials/screens/orders.php';
        return ob_get_clean();
    }

    /**
     * Generate the order details screen content.
     *
     * @param int $driver_id Driver ID.
     * @return string
     */
    public function delivium_order_screen($driver_id) {
        global $delivium_order_id;
        ob_start();
        include_once DELIVIUM_PLUGIN_PATH . 'public/partials/screens/order.php';
        return ob_get_clean();
    }

    /**
     * Generate the out for delivery screen content.
     *
     * @param int $driver_id Driver ID.
     * @return string
     */
    public function delivium_out_for_delivery_screen($driver_id) {
        ob_start();
        include_once DELIVIUM_PLUGIN_PATH . 'public/partials/screens/out-for-delivery.php';
        return ob_get_clean();
    }

    /**
     * Generate the failed delivery screen content.
     *
     * @param int $driver_id Driver ID.
     * @return string
     */
    public function delivium_failed_delivery_screen($driver_id) {
        ob_start();
        include_once DELIVIUM_PLUGIN_PATH . 'public/partials/screens/failed-delivery.php';
        return ob_get_clean();
    }

    /**
     * Generate the delivered screen content.
     *
     * @param int $driver_id Driver ID.
     * @return string
     */
    public function delivium_delivered_screen($driver_id) {
        ob_start();
        include_once DELIVIUM_PLUGIN_PATH . 'public/partials/screens/delivered.php';
        return ob_get_clean();
    }

    // ... rest of the class implementation ...
} 