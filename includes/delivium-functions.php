<?php
/**
 * Helper functions for Delivium plugin
 *
 * @link       https://delivium.top
 * @since      1.0.0
 *
 * @package    Delivium
 * @subpackage Delivium/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Get total number of delivery orders
 *
 * @return int
 */
function delivium_get_total_orders() {
    $args = array(
        'post_type' => 'shop_order',
        'post_status' => array_keys(wc_get_order_statuses()),
        'meta_query' => array(
            array(
                'key' => '_delivium_delivery',
                'value' => '1',
                'compare' => '='
            )
        )
    );
    $query = new WP_Query($args);
    return $query->found_posts;
}

/**
 * Get number of active drivers
 *
 * @return int
 */
function delivium_get_active_drivers() {
    $args = array(
        'role' => 'delivium_driver',
        'meta_query' => array(
            array(
                'key' => 'delivium_driver_status',
                'value' => 'active',
                'compare' => '='
            )
        )
    );
    return count(get_users($args));
}

/**
 * Get number of orders out for delivery
 *
 * @return int
 */
function delivium_get_out_for_delivery_orders() {
    $status = get_option('delivium_out_for_delivery_status', 'out-for-delivery');
    $args = array(
        'post_type' => 'shop_order',
        'post_status' => $status,
        'posts_per_page' => -1
    );
    $query = new WP_Query($args);
    return $query->found_posts;
}

/**
 * Get number of completed orders today
 *
 * @return int
 */
function delivium_get_completed_orders_today() {
    $args = array(
        'post_type' => 'shop_order',
        'post_status' => 'wc-completed',
        'date_query' => array(
            array(
                'year' => date('Y'),
                'month' => date('m'),
                'day' => date('d'),
            ),
        ),
        'meta_query' => array(
            array(
                'key' => '_delivium_delivery',
                'value' => '1',
                'compare' => '='
            )
        )
    );
    $query = new WP_Query($args);
    return $query->found_posts;
}

/**
 * Display recent orders in dashboard
 */
function delivium_display_recent_orders() {
    $args = array(
        'post_type' => 'shop_order',
        'post_status' => array_keys(wc_get_order_statuses()),
        'posts_per_page' => 5,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => array(
            array(
                'key' => '_delivium_delivery',
                'value' => '1',
                'compare' => '='
            )
        )
    );
    $orders = wc_get_orders($args);
    
    if (!empty($orders)) {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . __('Order', 'delivium') . '</th>';
        echo '<th>' . __('Date', 'delivium') . '</th>';
        echo '<th>' . __('Status', 'delivium') . '</th>';
        echo '<th>' . __('Driver', 'delivium') . '</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($orders as $order) {
            $driver_id = $order->get_meta('delivium_driver_id');
            $driver = $driver_id ? get_userdata($driver_id) : null;
            
            echo '<tr>';
            echo '<td><a href="' . esc_url(get_edit_post_link($order->get_id())) . '">#' . $order->get_order_number() . '</a></td>';
            echo '<td>' . $order->get_date_created()->date_i18n(get_option('date_format')) . '</td>';
            echo '<td>' . wc_get_order_status_name($order->get_status()) . '</td>';
            echo '<td>' . ($driver ? esc_html($driver->display_name) : __('Unassigned', 'delivium')) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    } else {
        echo '<p>' . __('No recent delivery orders found.', 'delivium') . '</p>';
    }
}

/**
 * Display active drivers in dashboard
 */
function delivium_display_active_drivers() {
    $args = array(
        'role' => 'delivium_driver',
        'meta_query' => array(
            array(
                'key' => 'delivium_driver_status',
                'value' => 'active',
                'compare' => '='
            )
        )
    );
    $drivers = get_users($args);
    
    if (!empty($drivers)) {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . __('Driver', 'delivium') . '</th>';
        echo '<th>' . __('Phone', 'delivium') . '</th>';
        echo '<th>' . __('Current Orders', 'delivium') . '</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($drivers as $driver) {
            $current_orders = delivium_get_driver_current_orders($driver->ID);
            echo '<tr>';
            echo '<td><a href="' . esc_url(get_edit_user_link($driver->ID)) . '">' . esc_html($driver->display_name) . '</a></td>';
            echo '<td>' . esc_html(get_user_meta($driver->ID, 'phone', true)) . '</td>';
            echo '<td>' . $current_orders . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    } else {
        echo '<p>' . __('No active drivers found.', 'delivium') . '</p>';
    }
}

/**
 * Get current orders count for a driver
 *
 * @param int $driver_id Driver ID
 * @return int
 */
function delivium_get_driver_current_orders($driver_id) {
    $args = array(
        'post_type' => 'shop_order',
        'post_status' => array('driver-assigned', 'out-for-delivery'),
        'meta_query' => array(
            array(
                'key' => 'delivium_driver_id',
                'value' => $driver_id,
                'compare' => '='
            )
        )
    );
    $query = new WP_Query($args);
    return $query->found_posts;
}

/**
 * Get all delivery order statuses
 *
 * @return array
 */
function delivium_get_order_statuses() {
    return array(
        'pending' => __('Pending', 'delivium'),
        'driver-assigned' => __('Driver Assigned', 'delivium'),
        'out-for-delivery' => __('Out for Delivery', 'delivium'),
        'completed' => __('Completed', 'delivium'),
        'cancelled' => __('Cancelled', 'delivium')
    );
}

/**
 * Display orders list
 */
function delivium_display_orders_list() {
    $paged = max(1, get_query_var('paged'));
    $args = array(
        'post_type' => 'shop_order',
        'post_status' => array_keys(wc_get_order_statuses()),
        'posts_per_page' => 20,
        'paged' => $paged,
        'meta_query' => array(
            array(
                'key' => '_delivium_delivery',
                'value' => '1',
                'compare' => '='
            )
        )
    );
    
    // Add filters if set
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $args['post_status'] = sanitize_text_field($_GET['status']);
    }
    if (isset($_GET['driver']) && !empty($_GET['driver'])) {
        $args['meta_query'][] = array(
            'key' => 'delivium_driver_id',
            'value' => intval($_GET['driver']),
            'compare' => '='
        );
    }
    if (isset($_GET['date']) && !empty($_GET['date'])) {
        $date = sanitize_text_field($_GET['date']);
        $args['date_query'] = array(
            array(
                'year' => date('Y', strtotime($date)),
                'month' => date('m', strtotime($date)),
                'day' => date('d', strtotime($date)),
            ),
        );
    }
    
    $orders = wc_get_orders($args);
    
    if (!empty($orders)) {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . __('Order', 'delivium') . '</th>';
        echo '<th>' . __('Date', 'delivium') . '</th>';
        echo '<th>' . __('Status', 'delivium') . '</th>';
        echo '<th>' . __('Customer', 'delivium') . '</th>';
        echo '<th>' . __('Driver', 'delivium') . '</th>';
        echo '<th>' . __('Actions', 'delivium') . '</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($orders as $order) {
            $driver_id = $order->get_meta('delivium_driver_id');
            $driver = $driver_id ? get_userdata($driver_id) : null;
            
            echo '<tr>';
            echo '<td><a href="' . esc_url(get_edit_post_link($order->get_id())) . '">#' . $order->get_order_number() . '</a></td>';
            echo '<td>' . $order->get_date_created()->date_i18n(get_option('date_format')) . '</td>';
            echo '<td>' . wc_get_order_status_name($order->get_status()) . '</td>';
            echo '<td>' . esc_html($order->get_formatted_billing_full_name()) . '</td>';
            echo '<td>' . ($driver ? esc_html($driver->display_name) : __('Unassigned', 'delivium')) . '</td>';
            echo '<td>';
            echo '<a href="' . esc_url(admin_url('admin.php?page=delivium-orders&action=edit&order_id=' . $order->get_id())) . '" class="button">' . __('Edit', 'delivium') . '</a>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    } else {
        echo '<p>' . __('No orders found.', 'delivium') . '</p>';
    }
}

/**
 * Display drivers list
 */
function delivium_display_drivers_list() {
    $paged = max(1, get_query_var('paged'));
    $args = array(
        'role' => 'delivium_driver',
        'number' => 20,
        'paged' => $paged,
        'orderby' => 'display_name',
        'order' => 'ASC'
    );
    
    $drivers = get_users($args);
    
    if (!empty($drivers)) {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . __('Driver', 'delivium') . '</th>';
        echo '<th>' . __('Email', 'delivium') . '</th>';
        echo '<th>' . __('Phone', 'delivium') . '</th>';
        echo '<th>' . __('Status', 'delivium') . '</th>';
        echo '<th>' . __('Current Orders', 'delivium') . '</th>';
        echo '<th>' . __('Actions', 'delivium') . '</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($drivers as $driver) {
            $status = get_user_meta($driver->ID, 'delivium_driver_status', true);
            $current_orders = delivium_get_driver_current_orders($driver->ID);
            
            echo '<tr>';
            echo '<td>' . esc_html($driver->display_name) . '</td>';
            echo '<td>' . esc_html($driver->user_email) . '</td>';
            echo '<td>' . esc_html(get_user_meta($driver->ID, 'phone', true)) . '</td>';
            echo '<td>' . esc_html(ucfirst($status)) . '</td>';
            echo '<td>' . $current_orders . '</td>';
            echo '<td>';
            echo '<a href="' . esc_url(admin_url('admin.php?page=delivium-drivers&action=edit&driver_id=' . $driver->ID)) . '" class="button">' . __('Edit', 'delivium') . '</a>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    } else {
        echo '<p>' . __('No drivers found.', 'delivium') . '</p>';
    }
}

/**
 * Get total number of pages for drivers list
 *
 * @return int
 */
function delivium_get_drivers_total_pages() {
    $args = array(
        'role' => 'delivium_driver',
        'number' => 20,
        'count_total' => true
    );
    $users = new WP_User_Query($args);
    return ceil($users->get_total() / 20);
}

/**
 * Get total number of pages for orders list
 *
 * @return int
 */
function delivium_get_orders_total_pages() {
    $args = array(
        'post_type' => 'shop_order',
        'post_status' => array_keys(wc_get_order_statuses()),
        'posts_per_page' => 20,
        'meta_query' => array(
            array(
                'key' => '_delivium_delivery',
                'value' => '1',
                'compare' => '='
            )
        )
    );
    $query = new WP_Query($args);
    return $query->max_num_pages;
}

/**
 * Get total deliveries for a date range
 *
 * @param string $start_date Start date in Y-m-d format
 * @param string $end_date End date in Y-m-d format
 * @return int
 */
function delivium_get_total_deliveries($start_date, $end_date) {
    $args = array(
        'post_type' => 'shop_order',
        'post_status' => array('completed'),
        'date_query' => array(
            array(
                'after' => $start_date,
                'before' => $end_date,
                'inclusive' => true,
            ),
        ),
        'meta_query' => array(
            array(
                'key' => '_delivium_delivery',
                'value' => '1',
                'compare' => '='
            )
        )
    );
    $query = new WP_Query($args);
    return $query->found_posts;
}

/**
 * Get percentage of on-time deliveries
 *
 * @param string $start_date Start date in Y-m-d format
 * @param string $end_date End date in Y-m-d format
 * @return float
 */
function delivium_get_ontime_deliveries($start_date, $end_date) {
    $total = delivium_get_total_deliveries($start_date, $end_date);
    if ($total === 0) return 0;
    
    $args = array(
        'post_type' => 'shop_order',
        'post_status' => array('completed'),
        'date_query' => array(
            array(
                'after' => $start_date,
                'before' => $end_date,
                'inclusive' => true,
            ),
        ),
        'meta_query' => array(
            array(
                'key' => '_delivium_delivery',
                'value' => '1',
                'compare' => '='
            ),
            array(
                'key' => '_delivium_delivered_on_time',
                'value' => '1',
                'compare' => '='
            )
        )
    );
    $query = new WP_Query($args);
    return round(($query->found_posts / $total) * 100, 2);
}

/**
 * Get average delivery time
 *
 * @param string $start_date Start date in Y-m-d format
 * @param string $end_date End date in Y-m-d format
 * @return string
 */
function delivium_get_average_delivery_time($start_date, $end_date) {
    global $wpdb;
    
    $result = $wpdb->get_var($wpdb->prepare(
        "SELECT AVG(TIMESTAMPDIFF(MINUTE, 
            pm1.meta_value, 
            pm2.meta_value
        )) as avg_time
        FROM {$wpdb->posts} p
        JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id
        JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
        WHERE p.post_type = 'shop_order'
        AND p.post_status = 'completed'
        AND pm1.meta_key = '_delivium_pickup_time'
        AND pm2.meta_key = '_delivium_delivery_time'
        AND p.post_date BETWEEN %s AND %s",
        $start_date,
        $end_date
    ));
    
    if (!$result) return '0:00';
    
    $hours = floor($result / 60);
    $minutes = $result % 60;
    
    return sprintf('%d:%02d', $hours, $minutes);
}

/**
 * Get total delivery revenue
 *
 * @param string $start_date Start date in Y-m-d format
 * @param string $end_date End date in Y-m-d format
 * @return string
 */
function delivium_get_total_delivery_revenue($start_date, $end_date) {
    $args = array(
        'post_type' => 'shop_order',
        'post_status' => array('completed'),
        'date_query' => array(
            array(
                'after' => $start_date,
                'before' => $end_date,
                'inclusive' => true,
            ),
        ),
        'meta_query' => array(
            array(
                'key' => '_delivium_delivery',
                'value' => '1',
                'compare' => '='
            )
        )
    );
    
    $orders = wc_get_orders($args);
    $total = 0;
    
    foreach ($orders as $order) {
        $total += $order->get_shipping_total();
    }
    
    return wc_price($total);
}

/**
 * Display driver performance table
 *
 * @param string $start_date Start date in Y-m-d format
 * @param string $end_date End date in Y-m-d format
 */
function delivium_display_driver_performance($start_date, $end_date) {
    $args = array(
        'role' => 'delivium_driver',
        'orderby' => 'display_name',
        'order' => 'ASC'
    );
    
    $drivers = get_users($args);
    
    if (!empty($drivers)) {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . __('Driver', 'delivium') . '</th>';
        echo '<th>' . __('Completed Deliveries', 'delivium') . '</th>';
        echo '<th>' . __('On-Time Rate', 'delivium') . '</th>';
        echo '<th>' . __('Average Time', 'delivium') . '</th>';
        echo '<th>' . __('Revenue', 'delivium') . '</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($drivers as $driver) {
            $stats = delivium_get_driver_stats($driver->ID, $start_date, $end_date);
            
            echo '<tr>';
            echo '<td>' . esc_html($driver->display_name) . '</td>';
            echo '<td>' . $stats['completed'] . '</td>';
            echo '<td>' . $stats['ontime_rate'] . '%</td>';
            echo '<td>' . $stats['avg_time'] . '</td>';
            echo '<td>' . $stats['revenue'] . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    } else {
        echo '<p>' . __('No drivers found.', 'delivium') . '</p>';
    }
}

/**
 * Get driver statistics
 *
 * @param int $driver_id Driver ID
 * @param string $start_date Start date in Y-m-d format
 * @param string $end_date End date in Y-m-d format
 * @return array
 */
function delivium_get_driver_stats($driver_id, $start_date, $end_date) {
    $args = array(
        'post_type' => 'shop_order',
        'post_status' => array('completed'),
        'date_query' => array(
            array(
                'after' => $start_date,
                'before' => $end_date,
                'inclusive' => true,
            ),
        ),
        'meta_query' => array(
            array(
                'key' => 'delivium_driver_id',
                'value' => $driver_id,
                'compare' => '='
            )
        )
    );
    
    $orders = wc_get_orders($args);
    $total = count($orders);
    $ontime = 0;
    $total_time = 0;
    $revenue = 0;
    
    foreach ($orders as $order) {
        if ($order->get_meta('_delivium_delivered_on_time')) {
            $ontime++;
        }
        
        $pickup_time = strtotime($order->get_meta('_delivium_pickup_time'));
        $delivery_time = strtotime($order->get_meta('_delivium_delivery_time'));
        if ($pickup_time && $delivery_time) {
            $total_time += ($delivery_time - $pickup_time) / 60; // Convert to minutes
        }
        
        $revenue += $order->get_shipping_total();
    }
    
    return array(
        'completed' => $total,
        'ontime_rate' => $total ? round(($ontime / $total) * 100, 2) : 0,
        'avg_time' => $total ? sprintf('%d:%02d', floor($total_time / $total / 60), ($total_time / $total) % 60) : '0:00',
        'revenue' => wc_price($revenue)
    );
}

/**
 * Get all delivery drivers
 *
 * @since    1.0.0
 * @param    array    $args    Optional. Arguments to modify the query.
 * @return   array             Array of WP_User objects.
 */
function delivium_get_drivers($args = array()) {
    $defaults = array(
        'role' => 'delivium_driver',
        'orderby' => 'display_name',
        'order' => 'ASC'
    );

    $args = wp_parse_args($args, $defaults);
    
    return get_users($args);
}

/**
 * Get driver's current status
 *
 * @since    1.0.0
 * @param    int       $driver_id    The ID of the driver.
 * @return   string                  The driver's status (online/offline).
 */
function delivium_get_driver_status($driver_id) {
    $status = get_user_meta($driver_id, '_delivium_driver_status', true);
    return $status ? $status : 'offline';
}

/**
 * Get driver's current location
 *
 * @since    1.0.0
 * @param    int       $driver_id    The ID of the driver.
 * @return   array|false            The driver's location or false if not found.
 */
function delivium_get_driver_location($driver_id) {
    $location = get_user_meta($driver_id, '_delivium_last_location', true);
    return $location ? $location : false;
}

/**
 * Get driver's assigned orders
 *
 * @since    1.0.0
 * @param    int       $driver_id    The ID of the driver.
 * @param    string    $status       Optional. Filter by order status.
 * @return   array                   Array of WC_Order objects.
 */
function delivium_get_driver_orders($driver_id, $status = '') {
    $args = array(
        'meta_key' => '_delivium_assigned_driver',
        'meta_value' => $driver_id,
        'post_type' => 'shop_order',
        'posts_per_page' => -1
    );

    if ($status) {
        if (is_array($status)) {
            $args['post_status'] = array_map(function($s) {
                return 'wc-' . $s;
            }, $status);
        } else {
            $args['post_status'] = 'wc-' . $status;
        }
    }

    $orders = wc_get_orders($args);
    return $orders;
}

/**
 * Format delivery time window
 *
 * @since    1.0.0
 * @param    string    $time_window    The time window string.
 * @return   string                    Formatted time window.
 */
function delivium_format_time_window($time_window) {
    if (empty($time_window)) {
        return '';
    }

    $times = explode('-', $time_window);
    if (count($times) !== 2) {
        return $time_window;
    }

    $start = date('g:i A', strtotime(trim($times[0])));
    $end = date('g:i A', strtotime(trim($times[1])));

    return sprintf('%s - %s', $start, $end);
}

/**
 * Get order delivery status
 *
 * @since    1.0.0
 * @param    int       $order_id    The order ID.
 * @return   string                 The delivery status.
 */
function delivium_get_order_delivery_status($order_id) {
    $status = get_post_meta($order_id, '_delivium_delivery_status', true);
    return $status ? $status : 'pending';
}

/**
 * Get delivery status label
 *
 * @since    1.0.0
 * @param    string    $status    The status key.
 * @return   string              The status label.
 */
function delivium_get_delivery_status_label($status) {
    $statuses = array(
        'pending' => __('Pending', 'delivium'),
        'assigned' => __('Assigned', 'delivium'),
        'picked_up' => __('Picked Up', 'delivium'),
        'in_transit' => __('In Transit', 'delivium'),
        'delivered' => __('Delivered', 'delivium'),
        'failed' => __('Failed', 'delivium')
    );

    return isset($statuses[$status]) ? $statuses[$status] : $status;
}

/**
 * Check if an order is assigned to a driver
 *
 * @since    1.0.0
 * @param    int    $order_id     The order ID.
 * @param    int    $driver_id    The driver ID.
 * @return   bool                 Whether the order is assigned to the driver.
 */
function delivium_is_order_assigned_to_driver($order_id, $driver_id) {
    $assigned_driver = get_post_meta($order_id, '_delivium_assigned_driver', true);
    return $assigned_driver == $driver_id;
} 