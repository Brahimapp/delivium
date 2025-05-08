<?php
/**
 * Tracking page
 *
 * @package    Delivium
 */

if (!defined('ABSPATH')) {
    exit;
}

$order_id = isset($_GET['order']) ? sanitize_text_field(wp_unslash($_GET['order'])) : '';
$order = wc_get_order($order_id);

if (!$order) {
    wp_safe_redirect(home_url());
    exit;
}

$driver = new Delivium_Driver();
$order_status = $order->get_status();
$delivium_driver_id = $order->get_meta('delivium_driver_id');

if ('' === $delivium_driver_id) {
    wp_safe_redirect(home_url());
    exit;
}

if (get_option('delivium_out_for_delivery_status', '') !== 'wc-' . $order_status) {
    wp_safe_redirect(home_url());
    exit;
}

$driver_info = $driver->get_driver_info__premium_only($delivium_driver_id, 'html');
$vehicle_info = $driver->get_vehicle_info__premium_only($delivium_driver_id, 'html');

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <?php wp_head(); ?>
    <title><?php echo esc_js(__('Tracking', 'delivium')); ?></title>
</head>
<body>
    <div id="delivium_page" class="tracking">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="delivium-tracking-info">
                        <?php
                        if ('' !== $driver_info) {
                            echo $driver_info;
                        }
                        if ('' !== $vehicle_info) {
                            echo $vehicle_info;
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php wp_footer(); ?>
</body>
</html>
