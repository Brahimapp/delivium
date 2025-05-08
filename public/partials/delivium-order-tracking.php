<?php
/**
 * The public-facing template for order tracking.
 *
 * @package    Delivium
 * @subpackage Delivium/public/partials
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get order ID from URL
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if (!$order_id) {
    echo '<div class="delivium-error">' . __('Order ID is required.', 'delivium') . '</div>';
    return;
}

// Get order
$order = wc_get_order($order_id);

if (!$order) {
    echo '<div class="delivium-error">' . __('Order not found.', 'delivium') . '</div>';
    return;
}

// Get delivery driver information
$driver_id = get_post_meta($order_id, '_delivium_delivery_driver_id', true);
$driver = $driver_id ? get_userdata($driver_id) : null;

// Get delivery status timestamps
$assigned_time = get_post_meta($order_id, '_delivium_status_driver-assigned_time', true);
$out_for_delivery_time = get_post_meta($order_id, '_delivium_status_out-for-delivery_time', true);
$completed_time = get_post_meta($order_id, '_delivium_status_completed_time', true);
?>

<div class="delivium-order-tracking">
    <h1><?php echo sprintf(__('Order #%s', 'delivium'), $order->get_order_number()); ?></h1>
    
    <div class="delivium-order-status">
        <h2><?php echo __('Order Status', 'delivium'); ?></h2>
        <p class="status-badge status-<?php echo $order->get_status(); ?>">
            <?php echo wc_get_order_status_name($order->get_status()); ?>
        </p>
    </div>

    <?php if ($driver) : ?>
        <div class="delivium-driver-info">
            <h2><?php echo __('Delivery Driver', 'delivium'); ?></h2>
            <div class="driver-details">
                <p><strong><?php echo __('Name:', 'delivium'); ?></strong> <?php echo esc_html($driver->display_name); ?></p>
                <?php if ($driver->phone) : ?>
                    <p><strong><?php echo __('Phone:', 'delivium'); ?></strong> <?php echo esc_html($driver->phone); ?></p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="delivium-delivery-timeline">
        <h2><?php echo __('Delivery Timeline', 'delivium'); ?></h2>
        <div class="timeline">
            <div class="timeline-item <?php echo $order->get_status() === 'processing' ? 'active' : 'completed'; ?>">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <h3><?php echo __('Order Received', 'delivium'); ?></h3>
                    <p><?php echo $order->get_date_created()->format('Y-m-d H:i'); ?></p>
                </div>
            </div>

            <?php if ($assigned_time) : ?>
                <div class="timeline-item <?php echo $order->get_status() === 'driver-assigned' ? 'active' : 'completed'; ?>">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <h3><?php echo __('Driver Assigned', 'delivium'); ?></h3>
                        <p><?php echo date('Y-m-d H:i', $assigned_time); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($out_for_delivery_time) : ?>
                <div class="timeline-item <?php echo $order->get_status() === 'out-for-delivery' ? 'active' : 'completed'; ?>">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <h3><?php echo __('Out for Delivery', 'delivium'); ?></h3>
                        <p><?php echo date('Y-m-d H:i', $out_for_delivery_time); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($completed_time) : ?>
                <div class="timeline-item completed">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <h3><?php echo __('Delivery Completed', 'delivium'); ?></h3>
                        <p><?php echo date('Y-m-d H:i', $completed_time); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="delivium-order-details">
        <h2><?php echo __('Order Details', 'delivium'); ?></h2>
        <div class="order-items">
            <?php foreach ($order->get_items() as $item) : ?>
                <div class="order-item">
                    <div class="item-name"><?php echo $item->get_name(); ?></div>
                    <div class="item-quantity"><?php echo $item->get_quantity(); ?></div>
                    <div class="item-total"><?php echo $order->get_formatted_line_subtotal($item); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="order-total">
            <strong><?php echo __('Total:', 'delivium'); ?></strong>
            <?php echo $order->get_formatted_order_total(); ?>
        </div>
    </div>

    <div class="delivium-delivery-address">
        <h2><?php echo __('Delivery Address', 'delivium'); ?></h2>
        <div class="address-details">
            <?php echo $order->get_formatted_shipping_address(); ?>
        </div>
    </div>
</div>

<style>
.delivium-order-tracking {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.delivium-order-status {
    margin-bottom: 30px;
}

.status-badge {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 4px;
    font-weight: 500;
    color: white;
}

.status-processing {
    background-color: #ffc107;
}

.status-driver-assigned {
    background-color: #17a2b8;
}

.status-out-for-delivery {
    background-color: #007bff;
}

.status-completed {
    background-color: #28a745;
}

.delivium-driver-info {
    margin-bottom: 30px;
    padding: 20px;
    background-color: #f8f9fa;
    border-radius: 5px;
}

.driver-details p {
    margin: 5px 0;
}

.delivium-delivery-timeline {
    margin-bottom: 30px;
}

.timeline {
    position: relative;
    padding: 20px 0;
}

.timeline::before {
    content: '';
    position: absolute;
    top: 0;
    left: 20px;
    height: 100%;
    width: 2px;
    background-color: #dee2e6;
}

.timeline-item {
    position: relative;
    padding-left: 50px;
    margin-bottom: 30px;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-dot {
    position: absolute;
    left: 15px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background-color: #dee2e6;
    border: 2px solid white;
}

.timeline-item.active .timeline-dot {
    background-color: #007bff;
}

.timeline-item.completed .timeline-dot {
    background-color: #28a745;
}

.timeline-content {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
}

.timeline-content h3 {
    margin: 0 0 5px;
    font-size: 16px;
}

.timeline-content p {
    margin: 0;
    color: #6c757d;
    font-size: 14px;
}

.delivium-order-details {
    margin-bottom: 30px;
}

.order-items {
    margin-bottom: 15px;
}

.order-item {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #dee2e6;
}

.order-item:last-child {
    border-bottom: none;
}

.item-name {
    flex: 2;
}

.item-quantity {
    flex: 1;
    text-align: center;
}

.item-total {
    flex: 1;
    text-align: right;
}

.order-total {
    text-align: right;
    font-size: 18px;
    padding-top: 15px;
    border-top: 2px solid #dee2e6;
}

.delivium-delivery-address {
    padding: 20px;
    background-color: #f8f9fa;
    border-radius: 5px;
}

.address-details {
    margin-top: 10px;
    white-space: pre-line;
}
</style> 