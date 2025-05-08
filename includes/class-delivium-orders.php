<?php
/**
 * The orders functionality of the plugin.
 *
 * @link       https://delivium.top
 * @since      1.0.0
 *
 * @package    Delivium
 * @subpackage Delivium/includes
 */

/**
 * The orders functionality of the plugin.
 *
 * Defines the plugin name, version, and orders-related functionality.
 *
 * @package    Delivium
 * @subpackage Delivium/includes
 * @author     Delivium Team <support@delivium.top>
 */
class DELIVIUM_Orders {

    /**
     * Get orders count by status for a driver.
     *
     * @param int $driver_id Driver ID.
     * @return array
     */
    public function get_orders_count($driver_id) {
        global $wpdb;
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.post_status, COUNT(*) as orders
                FROM {$wpdb->posts} p
                JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE pm.meta_key = 'delivium_driverid'
                AND pm.meta_value = %d
                AND p.post_type = 'shop_order'
                GROUP BY p.post_status",
                $driver_id
            )
        );

        return $results;
    }

    /**
     * Get orders for a specific status.
     *
     * @param int    $driver_id Driver ID.
     * @param string $status    Order status.
     * @return array
     */
    public function get_orders_by_status($driver_id, $status) {
        global $wpdb;
        
        $orders = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.*
                FROM {$wpdb->posts} p
                JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE pm.meta_key = 'delivium_driverid'
                AND pm.meta_value = %d
                AND p.post_type = 'shop_order'
                AND p.post_status = %s",
                $driver_id,
                $status
            )
        );

        return $orders;
    }

    // ... rest of the class implementation ...
} 