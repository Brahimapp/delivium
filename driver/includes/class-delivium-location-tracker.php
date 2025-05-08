<?php
/**
 * The location tracking functionality of the plugin.
 *
 * @link       https://delivium.top
 * @since      1.0.0
 *
 * @package    Delivium
 * @subpackage Delivium/driver/includes
 */

class Delivium_Location_Tracker {

    /**
     * The table name for location data.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $table_name    The name of the database table.
     */
    private $table_name;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'delivium_driver_locations';
        $this->maybe_create_table();
    }

    /**
     * Create the database table if it doesn't exist.
     *
     * @since    1.0.0
     * @access   private
     */
    private function maybe_create_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            driver_id bigint(20) NOT NULL,
            latitude decimal(10,8) NOT NULL,
            longitude decimal(11,8) NOT NULL,
            accuracy float,
            speed float,
            heading int,
            timestamp datetime NOT NULL,
            battery_level int,
            connection_type varchar(20),
            PRIMARY KEY  (id),
            KEY driver_id (driver_id),
            KEY timestamp (timestamp)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Update driver's location.
     *
     * @since    1.0.0
     * @param    int      $driver_id    The ID of the driver.
     * @param    array    $location     The location data.
     * @return   bool                   Whether the update was successful.
     */
    public function update_location($driver_id, $location) {
        global $wpdb;

        // Validate location data
        if (!isset($location['lat']) || !isset($location['lng'])) {
            return false;
        }

        // Prepare data for insertion
        $data = array(
            'driver_id' => $driver_id,
            'latitude' => $location['lat'],
            'longitude' => $location['lng'],
            'timestamp' => current_time('mysql'),
            'accuracy' => isset($location['accuracy']) ? $location['accuracy'] : null,
            'speed' => isset($location['speed']) ? $location['speed'] : null,
            'heading' => isset($location['heading']) ? $location['heading'] : null,
            'battery_level' => isset($location['battery']) ? $location['battery'] : null,
            'connection_type' => isset($location['connection']) ? $location['connection'] : null
        );

        // Insert location data
        $result = $wpdb->insert(
            $this->table_name,
            $data,
            array(
                '%d', // driver_id
                '%f', // latitude
                '%f', // longitude
                '%s', // timestamp
                '%f', // accuracy
                '%f', // speed
                '%d', // heading
                '%d', // battery_level
                '%s'  // connection_type
            )
        );

        if ($result === false) {
            return false;
        }

        // Update driver's last known location in user meta
        update_user_meta($driver_id, '_delivium_last_location', array(
            'lat' => $location['lat'],
            'lng' => $location['lng'],
            'timestamp' => current_time('mysql')
        ));

        // Clean up old location data
        $this->cleanup_old_locations();

        return true;
    }

    /**
     * Get driver's current location.
     *
     * @since    1.0.0
     * @param    int      $driver_id    The ID of the driver.
     * @return   array|false            The location data or false if not found.
     */
    public function get_driver_location($driver_id) {
        global $wpdb;

        $location = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT latitude, longitude, accuracy, speed, heading, timestamp
                FROM {$this->table_name}
                WHERE driver_id = %d
                ORDER BY timestamp DESC
                LIMIT 1",
                $driver_id
            ),
            ARRAY_A
        );

        return $location ? $location : false;
    }

    /**
     * Get all active drivers' locations.
     *
     * @since    1.0.0
     * @return   array    Array of driver locations.
     */
    public function get_active_drivers_locations() {
        global $wpdb;

        // Get locations for drivers who have updated within the last 5 minutes
        $cutoff_time = date('Y-m-d H:i:s', strtotime('-5 minutes'));

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DISTINCT l.driver_id, l.latitude, l.longitude, l.timestamp,
                    u.display_name as driver_name,
                    COUNT(o.ID) as active_deliveries
                FROM {$this->table_name} l
                JOIN {$wpdb->users} u ON l.driver_id = u.ID
                LEFT JOIN {$wpdb->posts} o ON o.post_author = l.driver_id
                    AND o.post_type = 'shop_order'
                    AND o.post_status IN ('wc-processing', 'wc-in-transit')
                WHERE l.timestamp > %s
                GROUP BY l.driver_id
                ORDER BY l.timestamp DESC",
                $cutoff_time
            ),
            ARRAY_A
        );
    }

    /**
     * Clean up old location data.
     *
     * @since    1.0.0
     * @access   private
     */
    private function cleanup_old_locations() {
        global $wpdb;

        // Delete locations older than 24 hours
        $cutoff_time = date('Y-m-d H:i:s', strtotime('-24 hours'));

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name}
                WHERE timestamp < %s",
                $cutoff_time
            )
        );
    }

    /**
     * Get driver's location history.
     *
     * @since    1.0.0
     * @param    int       $driver_id    The ID of the driver.
     * @param    string    $start_date   Start date for the history.
     * @param    string    $end_date     End date for the history.
     * @return   array                   Array of location history.
     */
    public function get_location_history($driver_id, $start_date, $end_date) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT latitude, longitude, accuracy, speed, heading, timestamp
                FROM {$this->table_name}
                WHERE driver_id = %d
                AND timestamp BETWEEN %s AND %s
                ORDER BY timestamp ASC",
                $driver_id,
                $start_date,
                $end_date
            ),
            ARRAY_A
        );
    }
} 