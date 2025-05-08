<?php
/**
 * The public-facing template for the driver portal.
 *
 * @package    Delivium
 * @subpackage Delivium/public/partials
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check if user is logged in and is a delivery driver
if (!is_user_logged_in() || !current_user_can('delivery_driver')) {
    echo '<div class="delivium-error">' . __('You must be logged in as a delivery driver to access this page.', 'delivium') . '</div>';
    return;
}

$driver_id = get_current_user_id();

// Get available orders (not assigned to any driver)
$available_orders = wc_get_orders(array(
    'status' => 'processing',
    'meta_key' => '_delivium_delivery_driver_id',
    'meta_compare' => 'NOT EXISTS',
    'orderby' => 'date',
    'order' => 'DESC',
    'limit' => 50
));

// Get assigned orders (assigned to current driver)
$assigned_orders = wc_get_orders(array(
    'status' => 'driver-assigned',
    'meta_key' => '_delivium_delivery_driver_id',
    'meta_value' => $driver_id,
    'orderby' => 'date',
    'order' => 'DESC',
    'limit' => 50
));

// Get completed orders (completed by current driver)
$completed_orders = wc_get_orders(array(
    'status' => 'completed',
    'meta_key' => '_delivium_delivery_driver_id',
    'meta_value' => $driver_id,
    'orderby' => 'date',
    'order' => 'DESC',
    'limit' => 50
));
?>

<div class="delivium-driver-portal">
    <h1><?php echo __('Welcome to Your Driver Portal', 'delivium'); ?></h1>
    
    <div class="delivium-stats">
        <div class="stat-box">
            <h3><?php echo __('Available Orders', 'delivium'); ?></h3>
            <p class="stat-number"><?php echo count($available_orders); ?></p>
        </div>
        <div class="stat-box">
            <h3><?php echo __('Assigned Orders', 'delivium'); ?></h3>
            <p class="stat-number"><?php echo count($assigned_orders); ?></p>
        </div>
        <div class="stat-box">
            <h3><?php echo __('Completed Orders', 'delivium'); ?></h3>
            <p class="stat-number"><?php echo count($completed_orders); ?></p>
        </div>
    </div>

    <div class="delivium-orders-section">
        <h2><?php echo __('Available Orders', 'delivium'); ?></h2>
        <?php if (!empty($available_orders)) : ?>
            <table class="delivium-orders-table">
                <thead>
                    <tr>
                        <th><?php echo __('Order #', 'delivium'); ?></th>
                        <th><?php echo __('Date', 'delivium'); ?></th>
                        <th><?php echo __('Customer', 'delivium'); ?></th>
                        <th><?php echo __('Delivery Address', 'delivium'); ?></th>
                        <th><?php echo __('Actions', 'delivium'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($available_orders as $order) : ?>
                        <tr>
                            <td>#<?php echo $order->get_order_number(); ?></td>
                            <td><?php echo $order->get_date_created()->format('Y-m-d H:i'); ?></td>
                            <td><?php echo $order->get_formatted_billing_full_name(); ?></td>
                            <td><?php echo $order->get_formatted_shipping_address(); ?></td>
                            <td>
                                <button class="delivium-claim-order" data-order-id="<?php echo $order->get_id(); ?>">
                                    <?php echo __('Claim Order', 'delivium'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php echo __('No available orders at the moment.', 'delivium'); ?></p>
        <?php endif; ?>
    </div>

    <div class="delivium-orders-section">
        <h2><?php echo __('Your Assigned Orders', 'delivium'); ?></h2>
        <?php if (!empty($assigned_orders)) : ?>
            <table class="delivium-orders-table">
                <thead>
                    <tr>
                        <th><?php echo __('Order #', 'delivium'); ?></th>
                        <th><?php echo __('Date', 'delivium'); ?></th>
                        <th><?php echo __('Customer', 'delivium'); ?></th>
                        <th><?php echo __('Delivery Address', 'delivium'); ?></th>
                        <th><?php echo __('Status', 'delivium'); ?></th>
                        <th><?php echo __('Actions', 'delivium'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assigned_orders as $order) : ?>
                        <tr>
                            <td>#<?php echo $order->get_order_number(); ?></td>
                            <td><?php echo $order->get_date_created()->format('Y-m-d H:i'); ?></td>
                            <td><?php echo $order->get_formatted_billing_full_name(); ?></td>
                            <td><?php echo $order->get_formatted_shipping_address(); ?></td>
                            <td><?php echo wc_get_order_status_name($order->get_status()); ?></td>
                            <td>
                                <?php if ($order->get_status() === 'driver-assigned') : ?>
                                    <button class="delivium-start-delivery" data-order-id="<?php echo $order->get_id(); ?>">
                                        <?php echo __('Start Delivery', 'delivium'); ?>
                                    </button>
                                <?php elseif ($order->get_status() === 'out-for-delivery') : ?>
                                    <button class="delivium-complete-delivery" data-order-id="<?php echo $order->get_id(); ?>">
                                        <?php echo __('Complete Delivery', 'delivium'); ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php echo __('You have no assigned orders at the moment.', 'delivium'); ?></p>
        <?php endif; ?>
    </div>

    <div class="delivium-orders-section">
        <h2><?php echo __('Your Completed Orders', 'delivium'); ?></h2>
        <?php if (!empty($completed_orders)) : ?>
            <table class="delivium-orders-table">
                <thead>
                    <tr>
                        <th><?php echo __('Order #', 'delivium'); ?></th>
                        <th><?php echo __('Date', 'delivium'); ?></th>
                        <th><?php echo __('Customer', 'delivium'); ?></th>
                        <th><?php echo __('Delivery Address', 'delivium'); ?></th>
                        <th><?php echo __('Completed On', 'delivium'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($completed_orders as $order) : ?>
                        <tr>
                            <td>#<?php echo $order->get_order_number(); ?></td>
                            <td><?php echo $order->get_date_created()->format('Y-m-d H:i'); ?></td>
                            <td><?php echo $order->get_formatted_billing_full_name(); ?></td>
                            <td><?php echo $order->get_formatted_shipping_address(); ?></td>
                            <td><?php echo date('Y-m-d H:i', get_post_meta($order->get_id(), '_delivium_status_completed_time', true)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php echo __('You have not completed any orders yet.', 'delivium'); ?></p>
        <?php endif; ?>
    </div>
</div>

<style>
.delivium-driver-portal {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.delivium-stats {
    display: flex;
    justify-content: space-between;
    margin-bottom: 30px;
}

.stat-box {
    flex: 1;
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 5px;
    margin: 0 10px;
}

.stat-box:first-child {
    margin-left: 0;
}

.stat-box:last-child {
    margin-right: 0;
}

.stat-number {
    font-size: 24px;
    font-weight: bold;
    color: #007bff;
    margin: 10px 0 0;
}

.delivium-orders-section {
    margin-bottom: 40px;
}

.delivium-orders-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.delivium-orders-table th,
.delivium-orders-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
}

.delivium-orders-table th {
    background-color: #f8f9fa;
    font-weight: 600;
}

.delivium-claim-order,
.delivium-start-delivery,
.delivium-complete-delivery {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    transition: background-color 0.2s;
}

.delivium-claim-order {
    background-color: #28a745;
    color: white;
}

.delivium-start-delivery {
    background-color: #007bff;
    color: white;
}

.delivium-complete-delivery {
    background-color: #6c757d;
    color: white;
}

.delivium-claim-order:hover {
    background-color: #218838;
}

.delivium-start-delivery:hover {
    background-color: #0056b3;
}

.delivium-complete-delivery:hover {
    background-color: #5a6268;
}

.delivium-claim-order:disabled,
.delivium-start-delivery:disabled,
.delivium-complete-delivery:disabled {
    background-color: #6c757d;
    cursor: not-allowed;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Claim order
    $('.delivium-claim-order').on('click', function() {
        var orderId = $(this).data('order-id');
        var button = $(this);
        
        $.ajax({
            url: delivium_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'delivium_claim_order',
                order_id: orderId,
                nonce: delivium_ajax.nonce
            },
            beforeSend: function() {
                button.prop('disabled', true).text('<?php _e('Claiming...', 'delivium'); ?>');
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                    button.prop('disabled', false).text('<?php _e('Claim Order', 'delivium'); ?>');
                }
            }
        });
    });

    // Start delivery
    $('.delivium-start-delivery').on('click', function() {
        var orderId = $(this).data('order-id');
        var button = $(this);
        
        $.ajax({
            url: delivium_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'delivium_start_delivery',
                order_id: orderId,
                nonce: delivium_ajax.nonce
            },
            beforeSend: function() {
                button.prop('disabled', true).text('<?php _e('Starting...', 'delivium'); ?>');
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                    button.prop('disabled', false).text('<?php _e('Start Delivery', 'delivium'); ?>');
                }
            }
        });
    });

    // Complete delivery
    $('.delivium-complete-delivery').on('click', function() {
        var orderId = $(this).data('order-id');
        var button = $(this);
        
        $.ajax({
            url: delivium_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'delivium_complete_delivery',
                order_id: orderId,
                nonce: delivium_ajax.nonce
            },
            beforeSend: function() {
                button.prop('disabled', true).text('<?php _e('Completing...', 'delivium'); ?>');
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                    button.prop('disabled', false).text('<?php _e('Complete Delivery', 'delivium'); ?>');
                }
            }
        });
    });
});
</script> 