<?php
/**
 * Template for displaying delivery tracking information
 *
 * @link       https://delivium.top
 * @since      1.0.0
 *
 * @package    Delivium
 * @subpackage Delivium/public/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

$order_id = absint($atts['order_id']);
if (!$order_id) {
    return;
}

$order = wc_get_order($order_id);
if (!$order) {
    return;
}

// Get delivery information
global $wpdb;
$delivery = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}delivium_deliveries WHERE order_id = %d",
    $order_id
));

if (!$delivery) {
    return;
}

$show_map = isset($atts['show_map']) ? $atts['show_map'] === 'yes' : true;
?>

<div class="delivium-tracking-container" data-order-id="<?php echo esc_attr($order_id); ?>" data-show-map="<?php echo $show_map ? 'yes' : 'no'; ?>">
    <div class="delivium-tracking-header">
        <h2><?php esc_html_e('Delivery Tracking', 'delivium'); ?></h2>
        <p><?php printf(esc_html__('Order #%s', 'delivium'), $order->get_order_number()); ?></p>
    </div>

    <div class="delivium-status-container">
        <div class="delivium-status"><?php echo esc_html(ucfirst($delivery->status)); ?></div>
        <?php if ($delivery->estimated_delivery_time): ?>
            <div class="delivium-estimated-time">
                <?php printf(
                    esc_html__('Estimated delivery: %s', 'delivium'),
                    date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($delivery->estimated_delivery_time))
                ); ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($delivery->driver_id): ?>
        <?php
        $driver = get_userdata($delivery->driver_id);
        if ($driver):
            $driver_phone = get_user_meta($driver->ID, 'delivium_phone', true);
        ?>
            <div class="delivium-driver-info">
                <h3><?php esc_html_e('Driver Information', 'delivium'); ?></h3>
                <dl class="driver-details">
                    <dt><?php esc_html_e('Name:', 'delivium'); ?></dt>
                    <dd class="driver-name"><?php echo esc_html($driver->display_name); ?></dd>
                    <?php if ($driver_phone): ?>
                        <dt><?php esc_html_e('Phone:', 'delivium'); ?></dt>
                        <dd class="driver-phone"><?php echo esc_html($driver_phone); ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($show_map): ?>
        <div class="delivium-map"></div>
    <?php endif; ?>

    <?php if ($delivery->status === 'delivered' && defined('DELIVIUM_PREMIUM') && DELIVIUM_PREMIUM): ?>
        <div class="delivium-rating-form" data-delivery-id="<?php echo esc_attr($delivery->id); ?>">
            <h3><?php esc_html_e('Rate Your Delivery', 'delivium'); ?></h3>
            <form>
                <div class="rating-stars">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" name="rating" value="<?php echo esc_attr($i); ?>" id="star<?php echo esc_attr($i); ?>">
                        <label for="star<?php echo esc_attr($i); ?>">★</label>
                    <?php endfor; ?>
                </div>
                <div class="rating-comment">
                    <textarea name="comment" rows="3" placeholder="<?php esc_attr_e('Leave a comment (optional)', 'delivium'); ?>"></textarea>
                </div>
                <button type="submit" class="delivium-button">
                    <?php esc_html_e('Submit Rating', 'delivium'); ?>
                </button>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($delivery->status === 'out_for_delivery' && current_user_can('delivium_driver')): ?>
        <div class="delivium-actions">
            <button class="delivium-button delivium-complete-delivery" data-order-id="<?php echo esc_attr($order_id); ?>">
                <?php esc_html_e('Mark as Delivered', 'delivium'); ?>
            </button>
        </div>
    <?php endif; ?>
</div> 