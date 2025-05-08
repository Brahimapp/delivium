<?php
/**
 * The route optimization functionality of the plugin.
 *
 * @link       https://delivium.top
 * @since      1.0.0
 *
 * @package    Delivium
 * @subpackage Delivium/driver/includes
 */

class Delivium_Route_Optimizer {

    /**
     * Google Maps API key.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $api_key    The Google Maps API key.
     */
    private $api_key;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->api_key = get_option('delivium_google_maps_key');
    }

    /**
     * Get optimized route for a driver.
     *
     * @since    1.0.0
     * @param    int       $driver_id    The ID of the driver.
     * @return   array|false             The optimized route or false on failure.
     */
    public function get_optimized_route($driver_id) {
        // Get driver's current location
        $tracker = new Delivium_Location_Tracker();
        $current_location = $tracker->get_driver_location($driver_id);

        if (!$current_location) {
            return false;
        }

        // Get driver's assigned deliveries
        $deliveries = $this->get_assigned_deliveries($driver_id);

        if (empty($deliveries)) {
            return false;
        }

        // Build waypoints array
        $waypoints = array();
        foreach ($deliveries as $delivery) {
            $waypoints[] = array(
                'location' => array(
                    'lat' => floatval($delivery['latitude']),
                    'lng' => floatval($delivery['longitude'])
                ),
                'order_id' => $delivery['order_id'],
                'customer_name' => $delivery['customer_name'],
                'address' => $delivery['address'],
                'time_window' => $delivery['time_window']
            );
        }

        // Optimize route using Google Maps Directions API
        $optimized_route = $this->optimize_route_with_google(
            array(
                'lat' => floatval($current_location['latitude']),
                'lng' => floatval($current_location['longitude'])
            ),
            $waypoints
        );

        if (!$optimized_route) {
            return false;
        }

        // Cache the optimized route
        $this->cache_optimized_route($driver_id, $optimized_route);

        return $optimized_route;
    }

    /**
     * Get assigned deliveries for a driver.
     *
     * @since    1.0.0
     * @access   private
     * @param    int       $driver_id    The ID of the driver.
     * @return   array                   Array of delivery information.
     */
    private function get_assigned_deliveries($driver_id) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    p.ID as order_id,
                    pm1.meta_value as latitude,
                    pm2.meta_value as longitude,
                    CONCAT(pm3.meta_value, ' ', pm4.meta_value) as customer_name,
                    CONCAT(pm5.meta_value, ' ', pm6.meta_value, ', ', pm7.meta_value) as address,
                    pm8.meta_value as time_window
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_shipping_latitude'
                LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_shipping_longitude'
                LEFT JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_shipping_first_name'
                LEFT JOIN {$wpdb->postmeta} pm4 ON p.ID = pm4.post_id AND pm4.meta_key = '_shipping_last_name'
                LEFT JOIN {$wpdb->postmeta} pm5 ON p.ID = pm5.post_id AND pm5.meta_key = '_shipping_address_1'
                LEFT JOIN {$wpdb->postmeta} pm6 ON p.ID = pm6.post_id AND pm6.meta_key = '_shipping_city'
                LEFT JOIN {$wpdb->postmeta} pm7 ON p.ID = pm7.post_id AND pm7.meta_key = '_shipping_postcode'
                LEFT JOIN {$wpdb->postmeta} pm8 ON p.ID = pm8.post_id AND pm8.meta_key = '_delivium_time_window'
                WHERE p.post_type = 'shop_order'
                AND p.post_status IN ('wc-processing', 'wc-in-transit')
                AND EXISTS (
                    SELECT 1 FROM {$wpdb->postmeta}
                    WHERE post_id = p.ID
                    AND meta_key = '_delivium_assigned_driver'
                    AND meta_value = %d
                )",
                $driver_id
            ),
            ARRAY_A
        );
    }

    /**
     * Optimize route using Google Maps Directions API.
     *
     * @since    1.0.0
     * @access   private
     * @param    array    $origin       The starting point coordinates.
     * @param    array    $waypoints    Array of delivery waypoints.
     * @return   array|false            The optimized route or false on failure.
     */
    private function optimize_route_with_google($origin, $waypoints) {
        if (empty($this->api_key)) {
            return false;
        }

        // Prepare waypoints for API request
        $waypoint_str = '';
        foreach ($waypoints as $point) {
            $waypoint_str .= "optimize:true|{$point['location']['lat']},{$point['location']['lng']}|";
        }
        $waypoint_str = rtrim($waypoint_str, '|');

        // Build API URL
        $url = add_query_arg(
            array(
                'origin' => "{$origin['lat']},{$origin['lng']}",
                'destination' => "{$origin['lat']},{$origin['lng']}",
                'waypoints' => $waypoint_str,
                'key' => $this->api_key,
                'mode' => 'driving',
                'optimize' => 'true'
            ),
            'https://maps.googleapis.com/maps/api/directions/json'
        );

        // Make API request
        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($body['routes'][0])) {
            return false;
        }

        // Process response
        $route = $body['routes'][0];
        $waypoint_order = $route['waypoint_order'];

        // Reorder waypoints according to optimization
        $optimized_waypoints = array();
        foreach ($waypoint_order as $index) {
            $optimized_waypoints[] = $waypoints[$index];
        }

        return array(
            'waypoints' => $optimized_waypoints,
            'distance' => $route['legs'][0]['distance']['value'],
            'duration' => $route['legs'][0]['duration']['value'],
            'polyline' => $route['overview_polyline']['points']
        );
    }

    /**
     * Cache the optimized route.
     *
     * @since    1.0.0
     * @access   private
     * @param    int      $driver_id    The ID of the driver.
     * @param    array    $route        The optimized route data.
     */
    private function cache_optimized_route($driver_id, $route) {
        set_transient(
            'delivium_optimized_route_' . $driver_id,
            $route,
            HOUR_IN_SECONDS
        );
    }

    /**
     * Get cached route for a driver.
     *
     * @since    1.0.0
     * @param    int           $driver_id    The ID of the driver.
     * @return   array|false                 The cached route or false if not found.
     */
    public function get_cached_route($driver_id) {
        return get_transient('delivium_optimized_route_' . $driver_id);
    }

    /**
     * Clear cached route for a driver.
     *
     * @since    1.0.0
     * @param    int    $driver_id    The ID of the driver.
     */
    public function clear_cached_route($driver_id) {
        delete_transient('delivium_optimized_route_' . $driver_id);
    }
} 