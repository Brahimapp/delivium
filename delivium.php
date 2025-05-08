<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://delivium.top
 * @since             1.0.0
 * @package           Delivium
 *
 * @wordpress-plugin
 * Plugin Name:       Delivium - Delivery Management System
 * Plugin URI:        https://delivium.top
 * Description:       A comprehensive delivery management system for WordPress and WooCommerce.
 * Version:           1.0.0
 * Author:            Delivium Team
 * Author URI:        https://delivium.top/team
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       delivium
 * Domain Path:       /languages
 * WC requires at least: 3.0.0
 * WC tested up to: 8.0.0
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

$delivium_plugin_basename = plugin_basename(__FILE__);
$delivium_plugin_basename_array = explode('/', $delivium_plugin_basename);
$delivium_plugin_folder = $delivium_plugin_basename_array[0];
$delivium_delivery_drivers_page = get_option('delivium_delivery_drivers_page', '');

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 */
define('DELIVIUM_VERSION', '1.0.0');

/**
 * Define if premium version is active
 */
define('DELIVIUM_PREMIUM', false);

/**
 * Load helper functions
 */
require_once plugin_dir_path(__FILE__) . 'includes/delivium-functions.php';

/**
 * Load SMS functionality
 */
require_once plugin_dir_path(__FILE__) . 'includes/class-delivium-sms.php';

/**
 * Load WooCommerce integration
 */
require_once plugin_dir_path(__FILE__) . 'woocommerce/class-delivium-order-meta.php';

/**
 * Declare HPOS compatibility.
 */
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

/**
 * Define delivery driver page id.
 */
define('DELIVIUM_PAGE_ID', $delivium_delivery_drivers_page);

/**
 * Define plugin folder name.
 */
define('DELIVIUM_FOLDER', $delivium_plugin_folder);

/**
 * Define plugin dir.
 */
define('DELIVIUM_DIR', __DIR__);

/**
 * Define supported plugins.
 */
$delivium_plugins = array();
$delivium_multivendor = '';

if (is_plugin_active('woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php')) {
    // Brazil checkout fields.
    $delivium_plugins[] = 'woocommerce-extra-checkout-fields-for-brazil';
}

if (is_plugin_active('comunas-de-chile-para-woocommerce/woocoomerce-comunas.php')) {
    // Chile states.
    $delivium_plugins[] = 'comunas-de-chile-para-woocommerce';
}

if (is_plugin_active('wc-frontend-manager/wc_frontend_manager.php')) {
    // WCFM.
    $delivium_plugins[] = 'wcfm';
    $delivium_multivendor = 'wcfm';
}

if (is_plugin_active('dc-woocommerce-multi-vendor/dc_product_vendor.php')) {
    // WC Marketplace.
    $delivium_plugins[] = 'wcmp';
    $delivium_multivendor = 'wcmp';
}

if (is_plugin_active('dokan-lite/dokan.php')) {
    // Dokan.
    $delivium_plugins[] = 'dokan';
    $delivium_multivendor = 'dokan';
}

define('DELIVIUM_PLUGINS', $delivium_plugins);

/**
 * Define multivendor plugin.
 */
define('DELIVIUM_MULTIVENDOR', (in_array($delivium_multivendor, DELIVIUM_PLUGINS, true) ? $delivium_multivendor : ''));

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-delivium-activator.php
 *
 * @param array $network_wide network wide.
 */
function delivium_activate($network_wide)
{
    include_once plugin_dir_path(__FILE__) . 'includes/class-delivium-activator.php';
    $activator = new Delivium_Activator();
    $activator->activate($network_wide);
}

/**
 * Get order custom fields.
 *
 * @since 1.1.2
 * @param int   $orderid order id.
 * @param array $posts custom fields array.
 * @return string
 */
function delivium_order_custom_fields($orderid, $posts)
{
    $html = '';
    $counter = 0;
    $post_has_content = false;
    foreach ($posts as $post) {
        $meta = get_post_meta($post->ID, '', true);
        $meta = array_map(function ($n) {
            return $n[0];
        }, $meta);

        if (0 < $counter && $post_has_content) {
            $html .= '<br>';
            $post_has_content = false;
        }

        $field_value = '';
        foreach ($meta as $key => $value) {
            $value = preg_replace('/\\s+/', ' ', $value);

            if ('_' !== substr($key, 0, 1)) {
                $post_meta = get_post_meta($orderid, $key, true);
                if ('' !== $post_meta) {
                    if ('' !== $value) {
                        $field_value .= $value . $post_meta . ' ';
                    } else {
                        $field_value .= $post_meta . ' ';
                    }
                    $post_has_content = true;
                }
            }
        }

        if ('' !== $field_value) {
            $html .= '<strong>' . esc_html($post->post_title) . '</strong><br>' . esc_html($field_value);
        }

        $counter++;
    }

    return $html;
}

/**
 * Get currency symbol.
 *
 * @return string
 */
function delivium_currency_symbol()
{
    return get_woocommerce_currency_symbol();
}

/**
 * Format price.
 *
 * @param string $permission permission.
 * @param float  $price price.
 * @return string
 */
function delivium_price($permission, $price)
{
    if ('1' === $permission) {
        return wc_price($price);
    }
    return '';
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-delivium-deactivator.php
 */
function delivium_deactivate()
{
    include_once plugin_dir_path(__FILE__) . 'includes/class-delivium-deactivator.php';
    $deactivator = new Delivium_Deactivator();
    $deactivator->deactivate();
}

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function delivium_run()
{
    include_once plugin_dir_path(__FILE__) . 'includes/class-delivium.php';
    $plugin = new Delivium();
    $plugin->run();

    // Debug hook for meta boxes
    if (defined('WP_DEBUG') && WP_DEBUG) {
        add_action('all', function($hook) {
            if (strpos($hook, 'meta_box') !== false || strpos($hook, 'delivium') !== false) {
                error_log("Delivium Hook fired: $hook");
            }
        });
    }
}

/**
 * Get drivers page url.
 *
 * @param array $params parameters.
 * @return string
 */
function delivium_drivers_page_url($params)
{
    $url = get_permalink(DELIVIUM_PAGE_ID);
    if (!empty($params)) {
        $url = add_query_arg($params, $url);
    }
    return $url;
}

/**
 * Register query vars.
 *
 * @param array $vars variables.
 * @return array
 */
function delivium_register_query_vars($vars)
{
    $vars[] = 'k';
    $vars[] = 'screen';
    $vars[] = 'status';
    $vars[] = 'orderid';
    $vars[] = 'action';
    $vars[] = 'driverid';
    $vars[] = 'date';
    return $vars;
}

/**
 * Get date format.
 *
 * @param string $type type.
 * @return string
 */
function delivium_date_format($type)
{
    $format = '';
    switch ($type) {
        case 'date':
            $format = get_option('date_format');
            break;
        case 'time':
            $format = get_option('time_format');
            break;
        case 'datetime':
            $format = get_option('date_format') . ' ' . get_option('time_format');
            break;
    }
    return $format;
}

/**
 * Format address.
 *
 * @param string $format format.
 * @param array  $array array.
 * @return string
 */
function delivium_format_address($format, $array)
{
    $format = str_replace(array_keys($array), array_values($array), $format);
    $format = preg_replace('/\\s+/', ' ', trim($format));
    $format = preg_replace('/{|}/', '', $format);
    return $format;
}

/**
 * Delete cache.
 *
 * @param string $type type.
 * @param int    $driver_id driver id.
 */
function delivium_delete_cache($type, $driver_id)
{
    switch ($type) {
        case 'driver_orders':
            delete_transient('delivium_driver_orders_' . $driver_id);
            break;
        case 'driver_orders_count':
            delete_transient('delivium_driver_orders_count_' . $driver_id);
            break;
        case 'driver_orders_total':
            delete_transient('delivium_driver_orders_total_' . $driver_id);
            break;
    }
}

/**
 * Hook SMS notifications
 */
function delivium_init_notifications() {
    $sms = new Delivium_SMS();

    // Driver notifications
    add_action('woocommerce_order_status_processing', array($sms, 'notify_driver_new_order'));
    add_action('delivium_order_assigned_to_driver', array($sms, 'notify_driver_assigned'), 10, 2);
    
    // Customer notifications
    add_action('delivium_order_assigned_to_driver', array($sms, 'notify_customer_driver_assigned'), 10, 1);
    add_action('woocommerce_order_status_out-for-delivery', array($sms, 'notify_customer_out_delivery'), 10, 1);
    add_action('woocommerce_order_status_completed', array($sms, 'notify_customer_delivered'), 10, 1);
}
add_action('init', 'delivium_init_notifications');

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'delivium_activate');
register_deactivation_hook(__FILE__, 'delivium_deactivate');

// Initialize the plugin only after all plugins are loaded
add_action('plugins_loaded', 'delivium_run');

add_filter('query_vars', 'delivium_register_query_vars');