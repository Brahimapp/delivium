<?php
/**
 * Admin analytics page template
 *
 * @link       https://delivium.top
 * @since      1.0.0
 *
 * @package    Delivium
 * @subpackage Delivium/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get date range
$end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');
$start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));

// Get analytics data
$total_deliveries = get_option('delivium_total_deliveries_' . date('Y-m'), 0);
$successful_deliveries = get_option('delivium_successful_deliveries_' . date('Y-m'), 0);
$failed_deliveries = get_option('delivium_failed_deliveries_' . date('Y-m'), 0);
$average_delivery_time = get_option('delivium_average_delivery_time_' . date('Y-m'), 0);
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Delivery Analytics', 'delivium'); ?></h1>
    <hr class="wp-header-end">

    <!-- Date Range Filter -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get">
                <input type="hidden" name="page" value="delivium-analytics">
                <label for="start_date"><?php _e('From:', 'delivium'); ?></label>
                <input type="date" id="start_date" name="start_date" value="<?php echo esc_attr($start_date); ?>">
                <label for="end_date"><?php _e('To:', 'delivium'); ?></label>
                <input type="date" id="end_date" name="end_date" value="<?php echo esc_attr($end_date); ?>">
                <input type="submit" class="button" value="<?php _e('Filter', 'delivium'); ?>">
            </form>
        </div>
    </div>

    <!-- Overview Cards -->
    <div class="delivium-analytics-cards">
        <div class="card">
            <h3><?php _e('Total Deliveries', 'delivium'); ?></h3>
            <div class="card-value"><?php echo esc_html($total_deliveries); ?></div>
        </div>
        <div class="card">
            <h3><?php _e('Successful Deliveries', 'delivium'); ?></h3>
            <div class="card-value success"><?php echo esc_html($successful_deliveries); ?></div>
            <div class="card-percentage">
                <?php 
                $success_rate = $total_deliveries > 0 ? round(($successful_deliveries / $total_deliveries) * 100, 1) : 0;
                echo esc_html($success_rate . '%');
                ?>
            </div>
        </div>
        <div class="card">
            <h3><?php _e('Failed Deliveries', 'delivium'); ?></h3>
            <div class="card-value failure"><?php echo esc_html($failed_deliveries); ?></div>
            <div class="card-percentage">
                <?php 
                $failure_rate = $total_deliveries > 0 ? round(($failed_deliveries / $total_deliveries) * 100, 1) : 0;
                echo esc_html($failure_rate . '%');
                ?>
            </div>
        </div>
        <div class="card">
            <h3><?php _e('Average Delivery Time', 'delivium'); ?></h3>
            <div class="card-value"><?php echo esc_html(round($average_delivery_time, 1)); ?> <?php _e('minutes', 'delivium'); ?></div>
        </div>
    </div>

    <!-- Charts -->
    <div class="delivium-analytics-charts">
        <!-- Delivery Volume Chart -->
        <div class="chart-container">
            <h2><?php _e('Delivery Volume', 'delivium'); ?></h2>
            <canvas id="deliveryVolumeChart"></canvas>
        </div>

        <!-- Delivery Performance Chart -->
        <div class="chart-container">
            <h2><?php _e('Delivery Performance', 'delivium'); ?></h2>
            <canvas id="deliveryPerformanceChart"></canvas>
        </div>
    </div>

    <!-- Detailed Statistics -->
    <div class="delivium-analytics-details">
        <h2><?php _e('Detailed Statistics', 'delivium'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Driver', 'delivium'); ?></th>
                    <th><?php _e('Total Deliveries', 'delivium'); ?></th>
                    <th><?php _e('Success Rate', 'delivium'); ?></th>
                    <th><?php _e('Average Time', 'delivium'); ?></th>
                    <th><?php _e('Customer Rating', 'delivium'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Get all drivers
                $drivers = get_users(array('role' => 'delivium_driver'));
                foreach ($drivers as $driver) {
                    $driver_stats = get_user_meta($driver->ID, 'delivium_driver_stats_' . date('Y-m'), true);
                    if (!$driver_stats) {
                        $driver_stats = array(
                            'total' => 0,
                            'success_rate' => 0,
                            'avg_time' => 0,
                            'rating' => 0
                        );
                    }
                    ?>
                    <tr>
                        <td><?php echo esc_html($driver->display_name); ?></td>
                        <td><?php echo esc_html($driver_stats['total']); ?></td>
                        <td><?php echo esc_html($driver_stats['success_rate'] . '%'); ?></td>
                        <td><?php echo esc_html(round($driver_stats['avg_time'], 1) . ' ' . __('minutes', 'delivium')); ?></td>
                        <td>
                            <?php
                            $rating = round($driver_stats['rating'], 1);
                            echo str_repeat('★', floor($rating));
                            if ($rating - floor($rating) >= 0.5) {
                                echo '½';
                            }
                            echo str_repeat('☆', 5 - ceil($rating));
                            echo ' (' . $rating . ')';
                            ?>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Delivery Volume Chart
    var volumeCtx = document.getElementById('deliveryVolumeChart').getContext('2d');
    new Chart(volumeCtx, {
        type: 'line',
        data: {
            labels: <?php 
                $labels = array();
                $current = strtotime($start_date);
                while ($current <= strtotime($end_date)) {
                    $labels[] = date('M j', $current);
                    $current = strtotime('+1 day', $current);
                }
                echo json_encode($labels);
            ?>,
            datasets: [{
                label: '<?php _e('Daily Deliveries', 'delivium'); ?>',
                data: <?php
                    $data = array();
                    $current = strtotime($start_date);
                    while ($current <= strtotime($end_date)) {
                        $data[] = get_option('delivium_daily_deliveries_' . date('Y-m-d', $current), 0);
                        $current = strtotime('+1 day', $current);
                    }
                    echo json_encode($data);
                ?>,
                borderColor: '#2271b1',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Delivery Performance Chart
    var performanceCtx = document.getElementById('deliveryPerformanceChart').getContext('2d');
    new Chart(performanceCtx, {
        type: 'doughnut',
        data: {
            labels: ['<?php _e('On Time', 'delivium'); ?>', '<?php _e('Delayed', 'delivium'); ?>', '<?php _e('Failed', 'delivium'); ?>'],
            datasets: [{
                data: [
                    <?php echo get_option('delivium_ontime_deliveries_' . date('Y-m'), 0); ?>,
                    <?php echo get_option('delivium_delayed_deliveries_' . date('Y-m'), 0); ?>,
                    <?php echo $failed_deliveries; ?>
                ],
                backgroundColor: ['#00a32a', '#dba617', '#d63638']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
});
</script> 