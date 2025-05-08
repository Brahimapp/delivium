<?php
/**
 * Fired during plugin deactivation
 *
 * @link       https://delivium.top
 * @since      1.0.0
 *
 * @package    Delivium
 * @subpackage Delivium/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @link       https://delivium.top
 * @since      1.0.0
 *
 * @package    Delivium
 * @subpackage Delivium/includes
 * @author     Delivium Team <support@delivium.top>
 */
class Delivium_Deactivator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Remove driver role.
        remove_role('delivium_driver');

        // Clear scheduled hooks.
        wp_clear_scheduled_hook('delivium_daily_event');

        // Delete plugin options.
        delete_option('delivium_delivery_drivers_page');
        delete_option('delivium_sync_table');
        delete_option('delivium_tracking_table');
        delete_option('delivium_out_for_delivery_status');
        delete_option('delivium_delivered_status');
        delete_option('delivium_failed_attempt_status');
        delete_option('delivium_driver_assigned_status');
        delete_option('delivium_processing_status');
        delete_option('delivium_sms_assign_to_driver_template');
        delete_option('delivium_sms_out_for_delivery_template');
        delete_option('delivium_sms_start_delivery_template');
        delete_option('delivium_whatsapp_assign_to_driver_template');
        delete_option('delivium_whatsapp_out_for_delivery_template');
        delete_option('delivium_whatsapp_start_delivery_template');
        delete_option('delivium_failed_delivery_reason_1');
        delete_option('delivium_failed_delivery_reason_2');
        delete_option('delivium_failed_delivery_reason_3');
        delete_option('delivium_failed_delivery_reason_4');
        delete_option('delivium_failed_delivery_reason_5');
        delete_option('delivium_delivery_dropoff_1');
        delete_option('delivium_delivery_dropoff_2');
        delete_option('delivium_delivery_dropoff_3');

        // Handle premium options
        if (defined('DELIVIUM_PREMIUM') && DELIVIUM_PREMIUM) {
            delete_option('delivium_self_assign_delivery_drivers');
            delete_option('delivium_auto_assign_delivery_drivers');
            delete_option('delivium_sms_provider');
            delete_option('delivium_whatsapp_provider');
            delete_option('delivium_driver_photo_permission');
            delete_option('delivium_driver_name_permission');
            delete_option('delivium_driver_phone_permission');
            delete_option('delivium_driver_prices_permission');
            delete_option('delivium_driver_products_permission');
            delete_option('delivium_driver_commission_permission');
            delete_option('delivium_driver_billing_permission');
        }

        // Drop custom tables.
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS delivium_orders");
        $wpdb->query("DROP TABLE IF EXISTS delivium_tracking");
        $wpdb->query("DROP TABLE IF EXISTS delivium_deliveries");
        $wpdb->query("DROP TABLE IF EXISTS delivium_ratings");
    }
} 