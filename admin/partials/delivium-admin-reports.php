<?php
/**
 * Admin reports page template
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

// Get date range filters
$start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="delivium-reports-page">
        <!-- Date Range Filter -->
        <div class="delivium-date-filter">
            <form method="get" action="">
                <input type="hidden" name="page" value="delivium-reports">
                
                <label for="start_date"><?php _e('Start Date:', 'delivium'); ?></label>
                <input type="date" id="start_date" name="start_date" value="<?php echo esc_attr($start_date); ?>">
                
                <label for="end_date"><?php _e('End Date:', 'delivium'); ?></label>
                <input type="date" id="end_date" name="end_date" value="<?php echo esc_attr($end_date); ?>">
                
                <?php submit_button(__('Apply Filter', 'delivium'), 'secondary', 'filter', false); ?>
            </form>
        </div>

        <!-- Overview Stats -->
        <div class="delivium-stats-grid">
            <div class="delivium-stat-box">
                <h3><?php _e('Total Deliveries', 'delivium'); ?></h3>
                <p class="stat-number"><?php echo esc_html(delivium_get_total_deliveries($start_date, $end_date)); ?></p>
            </div>
            
            <div class="delivium-stat-box">
                <h3><?php _e('On-Time Deliveries', 'delivium'); ?></h3>
                <p class="stat-number"><?php echo esc_html(delivium_get_ontime_deliveries($start_date, $end_date)); ?>%</p>
            </div>
            
            <div class="delivium-stat-box">
                <h3><?php _e('Average Delivery Time', 'delivium'); ?></h3>
                <p class="stat-number"><?php echo esc_html(delivium_get_average_delivery_time($start_date, $end_date)); ?></p>
            </div>
            
            <div class="delivium-stat-box">
                <h3><?php _e('Total Revenue', 'delivium'); ?></h3>
                <p class="stat-number"><?php echo esc_html(delivium_get_total_delivery_revenue($start_date, $end_date)); ?></p>
            </div>
        </div>

        <!-- Detailed Reports -->
        <div class="delivium-reports-content">
            <!-- Delivery Performance Chart -->
            <div class="delivium-report-section">
                <h2><?php _e('Delivery Performance', 'delivium'); ?></h2>
                <div id="delivium-performance-chart" class="delivium-chart"></div>
            </div>

            <!-- Driver Performance -->
            <div class="delivium-report-section">
                <h2><?php _e('Driver Performance', 'delivium'); ?></h2>
                <?php delivium_display_driver_performance($start_date, $end_date); ?>
            </div>

            <?php if (defined('DELIVIUM_PREMIUM') && DELIVIUM_PREMIUM): ?>
            <!-- Premium Reports -->
            <div class="delivium-report-section">
                <h2><?php _e('Advanced Analytics', 'delivium'); ?></h2>
                <div class="delivium-premium-reports">
                    <!-- Customer Satisfaction -->
                    <div class="delivium-report-box">
                        <h3><?php _e('Customer Satisfaction', 'delivium'); ?></h3>
                        <?php delivium_display_customer_satisfaction($start_date, $end_date); ?>
                    </div>

                    <!-- Route Efficiency -->
                    <div class="delivium-report-box">
                        <h3><?php _e('Route Efficiency', 'delivium'); ?></h3>
                        <?php delivium_display_route_efficiency($start_date, $end_date); ?>
                    </div>

                    <!-- Delivery Heatmap -->
                    <div class="delivium-report-box">
                        <h3><?php _e('Delivery Heatmap', 'delivium'); ?></h3>
                        <div id="delivium-heatmap"></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Export Options -->
            <div class="delivium-export-section">
                <h2><?php _e('Export Reports', 'delivium'); ?></h2>
                <div class="delivium-export-buttons">
                    <form method="post" action="">
                        <?php wp_nonce_field('delivium_export_reports', 'delivium_export_nonce'); ?>
                        <button type="submit" name="export_csv" class="button">
                            <?php _e('Export to CSV', 'delivium'); ?>
                        </button>
                        <?php if (defined('DELIVIUM_PREMIUM') && DELIVIUM_PREMIUM): ?>
                        <button type="submit" name="export_pdf" class="button">
                            <?php _e('Export to PDF', 'delivium'); ?>
                        </button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div> 