<?php
/**
 * Admin dashboard page template
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
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="delivium-dashboard">
        <div class="delivium-dashboard-header">
            <div class="delivium-stats-grid">
                <div class="delivium-stat-box">
                    <h3><?php _e('Total Orders', 'delivium'); ?></h3>
                    <p class="stat-number"><?php echo esc_html(delivium_get_total_orders()); ?></p>
                </div>
                
                <div class="delivium-stat-box">
                    <h3><?php _e('Active Drivers', 'delivium'); ?></h3>
                    <p class="stat-number"><?php echo esc_html(delivium_get_active_drivers()); ?></p>
                </div>
                
                <div class="delivium-stat-box">
                    <h3><?php _e('Orders Out for Delivery', 'delivium'); ?></h3>
                    <p class="stat-number"><?php echo esc_html(delivium_get_out_for_delivery_orders()); ?></p>
                </div>
                
                <div class="delivium-stat-box">
                    <h3><?php _e('Completed Today', 'delivium'); ?></h3>
                    <p class="stat-number"><?php echo esc_html(delivium_get_completed_orders_today()); ?></p>
                </div>
            </div>
        </div>
        
        <div class="delivium-dashboard-content">
            <div class="delivium-section">
                <h2><?php _e('Recent Orders', 'delivium'); ?></h2>
                <?php delivium_display_recent_orders(); ?>
            </div>
            
            <div class="delivium-section">
                <h2><?php _e('Active Drivers', 'delivium'); ?></h2>
                <?php delivium_display_active_drivers(); ?>
            </div>
            
            <?php if (defined('DELIVIUM_PREMIUM') && DELIVIUM_PREMIUM): ?>
            <div class="delivium-section">
                <h2><?php _e('Live Tracking', 'delivium'); ?></h2>
                <div id="delivium-tracking-map"></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div> 