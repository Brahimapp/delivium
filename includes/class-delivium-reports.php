<?php
/**
 * The reports functionality of the plugin.
 *
 * @link       https://delivium.top
 * @since      1.0.0
 *
 * @package    Delivium
 * @subpackage Delivium/includes
 */

/**
 * The reports functionality of the plugin.
 *
 * Defines the plugin name, version, and reports-related functionality.
 *
 * @package    Delivium
 * @subpackage Delivium/includes
 * @author     Delivium Team <support@delivium.top>
 */
class DELIVIUM_Reports {

    /**
     * Get delivery statistics for a driver.
     *
     * @param int    $driver_id Driver ID.
     * @param string $start_date Start date in Y-m-d format.
     * @param string $end_date End date in Y-m-d format.
     * @return array
     */
    public function get_driver_stats($driver_id, $start_date = '', $end_date = '') {
        global $wpdb;

        $where = array(
            "pm.meta_key = 'delivium_driverid'",
            "pm.meta_value = %d",
            "p.post_type = 'shop_order'"
        );

        if ($start_date) {
            $where[] = "p.post_date >= %s";
        }
        if ($end_date) {
            $where[] = "p.post_date <= %s";
        }

        $where_clause = implode(' AND ', $where);
        $query = "SELECT p.post_status, COUNT(*) as count
                 FROM {$wpdb->posts} p
                 JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                 WHERE $where_clause
                 GROUP BY p.post_status";

        $query_args = array($driver_id);
        if ($start_date) {
            $query_args[] = $start_date;
        }
        if ($end_date) {
            $query_args[] = $end_date;
        }

        $results = $wpdb->get_results($wpdb->prepare($query, $query_args));

        return $this->format_stats_results($results);
    }

    /**
     * Get delivery times for a driver.
     *
     * @param int    $driver_id Driver ID.
     * @param string $date Date in Y-m-d format.
     * @return array
     */
    public function get_driver_times($driver_id, $date) {
        global $wpdb;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT *
                FROM {$wpdb->prefix}delivium_driver_times
                WHERE driver_id = %d
                AND date = %s
                ORDER BY created DESC",
                $driver_id,
                $date
            )
        );

        return $results;
    }

    /**
     * Format statistics results.
     *
     * @param array $results Raw statistics results.
     * @return array
     */
    private function format_stats_results($results) {
        $stats = array(
            'total' => 0,
            'delivered' => 0,
            'failed' => 0,
            'out_for_delivery' => 0,
            'assigned' => 0
        );

        foreach ($results as $row) {
            $stats['total'] += $row->count;
            switch ($row->post_status) {
                case get_option('delivium_delivered_status'):
                    $stats['delivered'] = $row->count;
                    break;
                case get_option('delivium_failed_attempt_status'):
                    $stats['failed'] = $row->count;
                    break;
                case get_option('delivium_out_for_delivery_status'):
                    $stats['out_for_delivery'] = $row->count;
                    break;
                case get_option('delivium_driver_assigned_status'):
                    $stats['assigned'] = $row->count;
                    break;
            }
        }

        return $stats;
    }

    // ... rest of the class implementation ...
} 