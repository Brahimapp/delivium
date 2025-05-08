<?php
/**
 * The route functionality of the plugin.
 *
 * @link       https://delivium.top
 * @since      1.0.0
 *
 * @package    Delivium
 * @subpackage Delivium/includes
 */

/**
 * The route functionality of the plugin.
 *
 * Defines the plugin name, version, and route-related functionality.
 *
 * @package    Delivium
 * @subpackage Delivium/includes
 * @author     Delivium Team <support@delivium.top>
 */
class DELIVIUM_Route {

    /**
     * Get route waypoints for a driver.
     *
     * @param int $driver_id Driver ID.
     * @return array
     */
    public function get_route_waypoints($driver_id) {
        global $wpdb;
        
        $orders = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.ID, pm_lat.meta_value as latitude, pm_lng.meta_value as longitude
                FROM {$wpdb->posts} p
                JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                JOIN {$wpdb->postmeta} pm_lat ON p.ID = pm_lat.post_id
                JOIN {$wpdb->postmeta} pm_lng ON p.ID = pm_lng.post_id
                WHERE pm.meta_key = 'delivium_driverid'
                AND pm.meta_value = %d
                AND pm_lat.meta_key = 'delivium_latitude'
                AND pm_lng.meta_key = 'delivium_longitude'
                AND p.post_type = 'shop_order'
                AND p.post_status = %s",
                $driver_id,
                get_option('delivium_out_for_delivery_status')
            )
        );

        return $orders;
    }

    /**
     * Optimize route order.
     *
     * @param array $waypoints Array of waypoints.
     * @return array
     */
    public function optimize_route($waypoints) {
        if (empty($waypoints)) {
            return array();
        }

        // Sort waypoints by distance from previous point
        $optimized = array($waypoints[0]);
        unset($waypoints[0]);

        while (!empty($waypoints)) {
            $last = end($optimized);
            $shortest_distance = PHP_FLOAT_MAX;
            $next_point = null;
            $next_key = null;

            foreach ($waypoints as $key => $point) {
                $distance = $this->calculate_distance(
                    $last->latitude,
                    $last->longitude,
                    $point->latitude,
                    $point->longitude
                );

                if ($distance < $shortest_distance) {
                    $shortest_distance = $distance;
                    $next_point = $point;
                    $next_key = $key;
                }
            }

            if ($next_point) {
                $optimized[] = $next_point;
                unset($waypoints[$next_key]);
            }
        }

        return $optimized;
    }

    /**
     * Calculate distance between two points.
     *
     * @param float $lat1 First point latitude.
     * @param float $lon1 First point longitude.
     * @param float $lat2 Second point latitude.
     * @param float $lon2 Second point longitude.
     * @return float
     */
    private function calculate_distance($lat1, $lon1, $lat2, $lon2) {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        return $miles;
    }

    // ... rest of the class implementation ...
} 