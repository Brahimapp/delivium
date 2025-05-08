<?php
/**
 * Template for the driver's mobile app interface
 *
 * @link       https://delivium.top
 * @since      1.0.0
 *
 * @package    Delivium
 * @subpackage Delivium/driver/templates
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get current driver
$current_user = wp_get_current_user();
if (!in_array('delivium_driver', $current_user->roles)) {
    wp_die(__('You do not have permission to access this page.', 'delivium'));
}

// Get driver's current deliveries
$optimizer = new Delivium_Route_Optimizer();
$route = $optimizer->get_optimized_route($current_user->ID);

// Get driver's status
$status = get_user_meta($current_user->ID, '_delivium_driver_status', true);
if (!$status) {
    $status = 'offline';
}

// Remove default admin bar and set viewport for mobile
show_admin_bar(false);
add_action('wp_head', function() {
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">';
});

get_header('driver');
?>

<div class="delivium-driver-app" data-driver-id="<?php echo esc_attr($current_user->ID); ?>">
    <!-- Status Bar -->
    <div class="status-bar">
        <div class="driver-info">
            <img src="<?php echo esc_url(get_avatar_url($current_user->ID)); ?>" alt="<?php echo esc_attr($current_user->display_name); ?>" class="driver-avatar">
            <span class="driver-name"><?php echo esc_html($current_user->display_name); ?></span>
        </div>
        <div class="status-toggle">
            <label class="switch">
                <input type="checkbox" id="status-toggle" <?php checked($status, 'online'); ?>>
                <span class="slider"></span>
            </label>
            <span class="status-label"><?php echo $status === 'online' ? esc_html__('Online', 'delivium') : esc_html__('Offline', 'delivium'); ?></span>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content">
        <!-- Map View -->
        <div id="driver-map"></div>

        <!-- Deliveries Panel -->
        <div class="deliveries-panel">
            <div class="panel-header">
                <h2 class="panel-title">
                    <?php esc_html_e('Today\'s Deliveries', 'delivium'); ?>
                    <?php if (!empty($route['waypoints'])): ?>
                        <span class="delivery-count">(<?php echo count($route['waypoints']); ?>)</span>
                    <?php endif; ?>
                </h2>
            </div>

            <div class="delivery-list">
                <?php if (!empty($route['waypoints'])): ?>
                    <?php foreach ($route['waypoints'] as $index => $delivery): ?>
                        <div class="delivery-item" data-order-id="<?php echo esc_attr($delivery['order_id']); ?>">
                            <div class="order-header">
                                <span class="order-number"><?php echo sprintf(esc_html__('Order #%s', 'delivium'), $delivery['order_id']); ?></span>
                                <?php if (!empty($delivery['time_window'])): ?>
                                    <span class="time-window"><?php echo esc_html($delivery['time_window']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="customer-info">
                                <div class="customer-name">
                                    <i class="fas fa-user"></i>
                                    <?php echo esc_html($delivery['customer_name']); ?>
                                </div>
                                <div class="customer-address">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo esc_html($delivery['address']); ?>
                                </div>
                            </div>
                            <div class="delivery-actions">
                                <button class="btn btn-navigate" data-lat="<?php echo esc_attr($delivery['location']['lat']); ?>" data-lng="<?php echo esc_attr($delivery['location']['lng']); ?>">
                                    <i class="fas fa-directions"></i>
                                    <?php esc_html_e('Navigate', 'delivium'); ?>
                                </button>
                                <button class="btn btn-update-status">
                                    <i class="fas fa-clock"></i>
                                    <?php esc_html_e('Update Status', 'delivium'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-deliveries">
                        <i class="fas fa-check-circle"></i>
                        <p><?php esc_html_e('No deliveries assigned for today.', 'delivium'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="status-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><?php esc_html_e('Update Delivery Status', 'delivium'); ?></h3>
            </div>
            <form id="status-update-form">
                <input type="hidden" name="order_id" id="status-order-id">
                <div class="form-group">
                    <label for="delivery-status"><?php esc_html_e('Status', 'delivium'); ?></label>
                    <select name="status" id="delivery-status" class="form-control" required>
                        <option value="picked_up"><?php esc_html_e('Picked Up', 'delivium'); ?></option>
                        <option value="in_transit"><?php esc_html_e('In Transit', 'delivium'); ?></option>
                        <option value="delivered"><?php esc_html_e('Delivered', 'delivium'); ?></option>
                        <option value="failed"><?php esc_html_e('Failed', 'delivium'); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status-note"><?php esc_html_e('Note', 'delivium'); ?></label>
                    <textarea name="note" id="status-note" class="form-control" rows="3"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-cancel"><?php esc_html_e('Cancel', 'delivium'); ?></button>
                    <button type="submit" class="btn btn-update-status"><?php esc_html_e('Update', 'delivium'); ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay">
        <div class="spinner"></div>
        <p><?php esc_html_e('Updating...', 'delivium'); ?></p>
    </div>
</div>

<?php
// Enqueue Font Awesome
wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', array(), '5.15.4');

// Enqueue Google Maps with API key
wp_enqueue_script(
    'google-maps',
    'https://maps.googleapis.com/maps/api/js?key=' . esc_attr(get_option('delivium_google_maps_key')) . '&libraries=geometry',
    array(),
    null,
    true
);

// Localize script data
wp_localize_script(
    $this->plugin_name . '-driver',
    'deliviumDriver',
    array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('delivium_driver_nonce'),
        'markerIcon' => plugins_url('images/delivery-marker.png', dirname(__FILE__)),
        'driverIcon' => plugins_url('images/driver-marker.png', dirname(__FILE__)),
        'i18n' => array(
            'online' => __('Online', 'delivium'),
            'offline' => __('Offline', 'delivium'),
            'locationError' => __('Unable to get your current location.', 'delivium'),
            'routeError' => __('Unable to calculate route.', 'delivium'),
            'updateError' => __('Failed to update delivery status.', 'delivium')
        )
    )
);

get_footer('driver');
?> 