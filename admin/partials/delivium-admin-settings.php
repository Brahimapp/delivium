<?php
/**
 * Admin settings page template
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

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

// Define tabs
$tabs = array(
    'general' => __('General', 'delivium'),
    'delivery' => __('Delivery', 'delivium'),
    'drivers' => __('Drivers', 'delivium'),
    'notifications' => __('Notifications', 'delivium'),
    'maps' => __('Maps', 'delivium'),
    'payment' => __('Payment', 'delivium'),
    'templates' => __('Templates', 'delivium'),
);

if (defined('DELIVIUM_PREMIUM') && DELIVIUM_PREMIUM) {
    $tabs['premium'] = __('Premium Features', 'delivium');
}

// Save settings if form is submitted
if (isset($_POST['delivium_save_settings']) && check_admin_referer('delivium_settings')) {
    $sms_provider = sanitize_text_field($_POST['sms_provider']);
    $sms_settings = array();
    
    // Sanitize and save provider-specific settings
    switch ($sms_provider) {
        case 'twilio':
            $sms_settings['account_sid'] = sanitize_text_field($_POST['twilio_account_sid']);
            $sms_settings['auth_token'] = sanitize_text_field($_POST['twilio_auth_token']);
            $sms_settings['from_number'] = sanitize_text_field($_POST['twilio_from_number']);
            break;
            
        case 'messagebird':
            $sms_settings['api_key'] = sanitize_text_field($_POST['messagebird_api_key']);
            $sms_settings['originator'] = sanitize_text_field($_POST['messagebird_originator']);
            break;
            
        case 'nexmo':
            $sms_settings['api_key'] = sanitize_text_field($_POST['nexmo_api_key']);
            $sms_settings['api_secret'] = sanitize_text_field($_POST['nexmo_api_secret']);
            $sms_settings['from'] = sanitize_text_field($_POST['nexmo_from']);
            break;
    }
    
    // Save notification settings
    $notification_settings = array(
        'notify_driver_new_order' => isset($_POST['notify_driver_new_order']),
        'notify_driver_assigned' => isset($_POST['notify_driver_assigned']),
        'notify_customer_driver_assigned' => isset($_POST['notify_customer_driver_assigned']),
        'notify_customer_out_delivery' => isset($_POST['notify_customer_out_delivery']),
        'notify_customer_delivered' => isset($_POST['notify_customer_delivered'])
    );
    
    // Save all settings
    update_option('delivium_sms_provider', $sms_provider);
    update_option('delivium_sms_settings', $sms_settings);
    update_option('delivium_notification_settings', $notification_settings);
    
    // Show success message
    add_settings_error('delivium_messages', 'delivium_message', __('Settings Saved', 'delivium'), 'updated');
}

// Get current settings
$current_provider = get_option('delivium_sms_provider', '');
$current_settings = get_option('delivium_sms_settings', array());
$notification_settings = get_option('delivium_notification_settings', array());

// Show settings errors
settings_errors('delivium_messages');
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="delivium-settings-page">
        <!-- Settings Tabs -->
        <nav class="nav-tab-wrapper">
            <?php
            foreach ($tabs as $tab => $name) {
                $active = ($current_tab === $tab) ? ' nav-tab-active' : '';
                echo sprintf(
                    '<a href="%s" class="nav-tab%s">%s</a>',
                    esc_url(add_query_arg('tab', $tab)),
                    esc_attr($active),
                    esc_html($name)
                );
            }
            ?>
        </nav>

        <form method="post" action="options.php">
            <?php
            switch ($current_tab) {
                case 'delivery':
                    settings_fields('delivium_delivery_settings');
                    do_settings_sections('delivium_delivery_settings');
                    ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Delivery Time Slots', 'delivium'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="delivium_enable_timeslots" value="1" 
                                        <?php checked(get_option('delivium_enable_timeslots'), 1); ?>>
                                    <?php _e('Enable delivery time slots', 'delivium'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Slot Duration', 'delivium'); ?></th>
                            <td>
                                <select name="delivium_slot_duration">
                                    <option value="30" <?php selected(get_option('delivium_slot_duration'), '30'); ?>>
                                        <?php _e('30 minutes', 'delivium'); ?>
                                    </option>
                                    <option value="60" <?php selected(get_option('delivium_slot_duration'), '60'); ?>>
                                        <?php _e('1 hour', 'delivium'); ?>
                                    </option>
                                    <option value="120" <?php selected(get_option('delivium_slot_duration'), '120'); ?>>
                                        <?php _e('2 hours', 'delivium'); ?>
                                    </option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Maximum Orders per Slot', 'delivium'); ?></th>
                            <td>
                                <input type="number" name="delivium_max_orders_per_slot" min="1" 
                                    value="<?php echo esc_attr(get_option('delivium_max_orders_per_slot', '5')); ?>">
                            </td>
                        </tr>
                    </table>
                    <?php
                    break;

                case 'drivers':
                    settings_fields('delivium_drivers_settings');
                    do_settings_sections('delivium_drivers_settings');
                    ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Driver Roles', 'delivium'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="delivium_enable_senior_drivers" value="1" 
                                            <?php checked(get_option('delivium_enable_senior_drivers'), 1); ?>>
                                        <?php _e('Enable senior driver role', 'delivium'); ?>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="delivium_enable_part_time_drivers" value="1" 
                                            <?php checked(get_option('delivium_enable_part_time_drivers'), 1); ?>>
                                        <?php _e('Enable part-time driver role', 'delivium'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Driver App Settings', 'delivium'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="delivium_driver_app_photo_required" value="1" 
                                            <?php checked(get_option('delivium_driver_app_photo_required'), 1); ?>>
                                        <?php _e('Require delivery photo', 'delivium'); ?>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="delivium_driver_app_signature_required" value="1" 
                                            <?php checked(get_option('delivium_driver_app_signature_required'), 1); ?>>
                                        <?php _e('Require customer signature', 'delivium'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                    <?php
                    break;

                case 'payment':
                    settings_fields('delivium_payment_settings');
                    do_settings_sections('delivium_payment_settings');
                    ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Cash on Delivery', 'delivium'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="delivium_enable_cod" value="1" 
                                            <?php checked(get_option('delivium_enable_cod'), 1); ?>>
                                        <?php _e('Enable Cash on Delivery', 'delivium'); ?>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="delivium_cod_exact_amount" value="1" 
                                            <?php checked(get_option('delivium_cod_exact_amount'), 1); ?>>
                                        <?php _e('Require exact amount', 'delivium'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Driver Payment Collection', 'delivium'); ?></th>
                            <td>
                                <select name="delivium_driver_payment_method">
                                    <option value="cash" <?php selected(get_option('delivium_driver_payment_method'), 'cash'); ?>>
                                        <?php _e('Cash Only', 'delivium'); ?>
                                    </option>
                                    <option value="card" <?php selected(get_option('delivium_driver_payment_method'), 'card'); ?>>
                                        <?php _e('Card Only', 'delivium'); ?>
                                    </option>
                                    <option value="both" <?php selected(get_option('delivium_driver_payment_method'), 'both'); ?>>
                                        <?php _e('Both Cash and Card', 'delivium'); ?>
                                    </option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <?php
                    break;

                case 'templates':
                    settings_fields('delivium_template_settings');
                    do_settings_sections('delivium_template_settings');
                    ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Order Confirmation', 'delivium'); ?></th>
                            <td>
                                <textarea name="delivium_order_confirmation_template" rows="5" class="large-text"><?php 
                                    echo esc_textarea(get_option('delivium_order_confirmation_template')); 
                                ?></textarea>
                                <p class="description">
                                    <?php _e('Available variables: {order_id}, {customer_name}, {delivery_address}, {estimated_time}', 'delivium'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Driver Assignment', 'delivium'); ?></th>
                            <td>
                                <textarea name="delivium_driver_assignment_template" rows="5" class="large-text"><?php 
                                    echo esc_textarea(get_option('delivium_driver_assignment_template')); 
                                ?></textarea>
                                <p class="description">
                                    <?php _e('Available variables: {order_id}, {driver_name}, {pickup_address}, {delivery_address}', 'delivium'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    <?php
                    break;

                case 'notifications':
                    settings_fields('delivium_notification_settings');
                    do_settings_sections('delivium_notification_settings');
                    ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('SMS Provider', 'delivium'); ?></th>
                            <td>
                                <select name="delivium_sms_provider" id="delivium_sms_provider">
                                    <option value=""><?php _e('Select Provider', 'delivium'); ?></option>
                                    <option value="twilio" <?php selected($current_provider, 'twilio'); ?>><?php _e('Twilio', 'delivium'); ?></option>
                                    <option value="messagebird" <?php selected($current_provider, 'messagebird'); ?>><?php _e('MessageBird', 'delivium'); ?></option>
                                    <option value="nexmo" <?php selected($current_provider, 'nexmo'); ?>><?php _e('Nexmo', 'delivium'); ?></option>
                                </select>
                            </td>
                        </tr>
                        
                        <!-- Twilio Settings -->
                        <tr class="provider-settings twilio-settings" <?php echo $current_provider !== 'twilio' ? 'style="display:none;"' : ''; ?>>
                            <th scope="row"><?php _e('Twilio Settings', 'delivium'); ?></th>
                            <td>
                                <input type="text" name="delivium_sms_settings[twilio][account_sid]" id="twilio_account_sid" 
                                    placeholder="<?php _e('Account SID', 'delivium'); ?>"
                                    value="<?php echo esc_attr($current_settings['twilio']['account_sid'] ?? ''); ?>"><br>
                                <input type="text" name="delivium_sms_settings[twilio][auth_token]" id="twilio_auth_token" 
                                    placeholder="<?php _e('Auth Token', 'delivium'); ?>"
                                    value="<?php echo esc_attr($current_settings['twilio']['auth_token'] ?? ''); ?>"><br>
                                <input type="text" name="delivium_sms_settings[twilio][from_number]" id="twilio_from_number" 
                                    placeholder="<?php _e('From Number', 'delivium'); ?>"
                                    value="<?php echo esc_attr($current_settings['twilio']['from_number'] ?? ''); ?>">
                            </td>
                        </tr>
                        
                        <!-- MessageBird Settings -->
                        <tr class="provider-settings messagebird-settings" <?php echo $current_provider !== 'messagebird' ? 'style="display:none;"' : ''; ?>>
                            <th scope="row"><?php _e('MessageBird Settings', 'delivium'); ?></th>
                            <td>
                                <input type="text" name="delivium_sms_settings[messagebird][api_key]" id="messagebird_api_key" 
                                    placeholder="<?php _e('API Key', 'delivium'); ?>"
                                    value="<?php echo esc_attr($current_settings['messagebird']['api_key'] ?? ''); ?>"><br>
                                <input type="text" name="delivium_sms_settings[messagebird][originator]" id="messagebird_originator" 
                                    placeholder="<?php _e('Originator', 'delivium'); ?>"
                                    value="<?php echo esc_attr($current_settings['messagebird']['originator'] ?? ''); ?>">
                            </td>
                        </tr>
                        
                        <!-- Nexmo Settings -->
                        <tr class="provider-settings nexmo-settings" <?php echo $current_provider !== 'nexmo' ? 'style="display:none;"' : ''; ?>>
                            <th scope="row"><?php _e('Nexmo Settings', 'delivium'); ?></th>
                            <td>
                                <input type="text" name="delivium_sms_settings[nexmo][api_key]" id="nexmo_api_key" 
                                    placeholder="<?php _e('API Key', 'delivium'); ?>"
                                    value="<?php echo esc_attr($current_settings['nexmo']['api_key'] ?? ''); ?>"><br>
                                <input type="text" name="delivium_sms_settings[nexmo][api_secret]" id="nexmo_api_secret" 
                                    placeholder="<?php _e('API Secret', 'delivium'); ?>"
                                    value="<?php echo esc_attr($current_settings['nexmo']['api_secret'] ?? ''); ?>"><br>
                                <input type="text" name="delivium_sms_settings[nexmo][from]" id="nexmo_from" 
                                    placeholder="<?php _e('From', 'delivium'); ?>"
                                    value="<?php echo esc_attr($current_settings['nexmo']['from'] ?? ''); ?>">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Notification Events', 'delivium'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="delivium_notification_settings[notify_driver_new_order]" value="1" 
                                            <?php checked(isset($notification_settings['notify_driver_new_order'])); ?>>
                                        <?php _e('Notify driver when new order is received', 'delivium'); ?>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="delivium_notification_settings[notify_driver_assigned]" value="1" 
                                            <?php checked(isset($notification_settings['notify_driver_assigned'])); ?>>
                                        <?php _e('Notify driver when assigned to an order', 'delivium'); ?>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="delivium_notification_settings[notify_customer_driver_assigned]" value="1" 
                                            <?php checked(isset($notification_settings['notify_customer_driver_assigned'])); ?>>
                                        <?php _e('Notify customer when driver is assigned', 'delivium'); ?>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="delivium_notification_settings[notify_customer_out_delivery]" value="1" 
                                            <?php checked(isset($notification_settings['notify_customer_out_delivery'])); ?>>
                                        <?php _e('Notify customer when order is out for delivery', 'delivium'); ?>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="delivium_notification_settings[notify_customer_delivered]" value="1" 
                                            <?php checked(isset($notification_settings['notify_customer_delivered'])); ?>>
                                        <?php _e('Notify customer when order is delivered', 'delivium'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                    <?php
                    break;

                case 'maps':
                    settings_fields('delivium_maps_settings');
                    do_settings_sections('delivium_maps_settings');
                    ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Maps Provider', 'delivium'); ?></th>
                            <td>
                                <select name="delivium_maps_provider">
                                    <option value="google" <?php selected(get_option('delivium_maps_provider'), 'google'); ?>>
                                        <?php _e('Google Maps', 'delivium'); ?>
                                    </option>
                                    <option value="mapbox" <?php selected(get_option('delivium_maps_provider'), 'mapbox'); ?>>
                                        <?php _e('Mapbox', 'delivium'); ?>
                                    </option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('API Key', 'delivium'); ?></th>
                            <td>
                                <input type="text" class="regular-text" name="delivium_maps_api_key" 
                                    value="<?php echo esc_attr(get_option('delivium_maps_api_key')); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Default Map Center', 'delivium'); ?></th>
                            <td>
                                <input type="text" class="regular-text" name="delivium_default_latitude" 
                                    placeholder="<?php esc_attr_e('Latitude', 'delivium'); ?>"
                                    value="<?php echo esc_attr(get_option('delivium_default_latitude')); ?>">
                                <input type="text" class="regular-text" name="delivium_default_longitude" 
                                    placeholder="<?php esc_attr_e('Longitude', 'delivium'); ?>"
                                    value="<?php echo esc_attr(get_option('delivium_default_longitude')); ?>">
                            </td>
                        </tr>
                    </table>
                    <?php
                    break;

                case 'premium':
                    if (defined('DELIVIUM_PREMIUM') && DELIVIUM_PREMIUM):
                        settings_fields('delivium_premium_settings');
                        do_settings_sections('delivium_premium_settings');
                        ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Route Optimization', 'delivium'); ?></th>
                                <td>
                                    <select name="delivium_route_algorithm">
                                        <option value="basic" <?php selected(get_option('delivium_route_algorithm'), 'basic'); ?>>
                                            <?php _e('Basic', 'delivium'); ?>
                                        </option>
                                        <option value="advanced" <?php selected(get_option('delivium_route_algorithm'), 'advanced'); ?>>
                                            <?php _e('Advanced', 'delivium'); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Real-time Tracking', 'delivium'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="delivium_realtime_tracking" value="1" 
                                            <?php checked(get_option('delivium_realtime_tracking'), 1); ?>>
                                        <?php _e('Enable real-time driver tracking', 'delivium'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Update Interval', 'delivium'); ?></th>
                                <td>
                                    <select name="delivium_tracking_interval">
                                        <option value="30" <?php selected(get_option('delivium_tracking_interval'), '30'); ?>>
                                            <?php _e('30 seconds', 'delivium'); ?>
                                        </option>
                                        <option value="60" <?php selected(get_option('delivium_tracking_interval'), '60'); ?>>
                                            <?php _e('1 minute', 'delivium'); ?>
                                        </option>
                                        <option value="300" <?php selected(get_option('delivium_tracking_interval'), '300'); ?>>
                                            <?php _e('5 minutes', 'delivium'); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Analytics', 'delivium'); ?></th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" name="delivium_enable_analytics" value="1" 
                                                <?php checked(get_option('delivium_enable_analytics'), 1); ?>>
                                            <?php _e('Enable advanced analytics', 'delivium'); ?>
                                        </label><br>
                                        <label>
                                            <input type="checkbox" name="delivium_enable_heatmaps" value="1" 
                                                <?php checked(get_option('delivium_enable_heatmaps'), 1); ?>>
                                            <?php _e('Enable delivery heatmaps', 'delivium'); ?>
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>
                        </table>
                        <?php
                    endif;
                    break;

                default: // General Settings
                    settings_fields('delivium_general_settings');
                    do_settings_sections('delivium_general_settings');
                    ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Order Statuses', 'delivium'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="text" class="regular-text" name="delivium_driver_assigned_status" 
                                            value="<?php echo esc_attr(get_option('delivium_driver_assigned_status', 'driver-assigned')); ?>">
                                        <?php _e('Driver Assigned Status', 'delivium'); ?>
                                    </label><br>
                                    <label>
                                        <input type="text" class="regular-text" name="delivium_out_for_delivery_status" 
                                            value="<?php echo esc_attr(get_option('delivium_out_for_delivery_status', 'out-for-delivery')); ?>">
                                        <?php _e('Out for Delivery Status', 'delivium'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Distance Unit', 'delivium'); ?></th>
                            <td>
                                <select name="delivium_distance_unit">
                                    <option value="km" <?php selected(get_option('delivium_distance_unit'), 'km'); ?>>
                                        <?php _e('Kilometers', 'delivium'); ?>
                                    </option>
                                    <option value="mi" <?php selected(get_option('delivium_distance_unit'), 'mi'); ?>>
                                        <?php _e('Miles', 'delivium'); ?>
                                    </option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Store Hours', 'delivium'); ?></th>
                            <td>
                                <fieldset>
                                    <?php
                                    $days = array(
                                        'monday' => __('Monday', 'delivium'),
                                        'tuesday' => __('Tuesday', 'delivium'),
                                        'wednesday' => __('Wednesday', 'delivium'),
                                        'thursday' => __('Thursday', 'delivium'),
                                        'friday' => __('Friday', 'delivium'),
                                        'saturday' => __('Saturday', 'delivium'),
                                        'sunday' => __('Sunday', 'delivium')
                                    );
                                    foreach ($days as $day_key => $day_name): ?>
                                        <div class="store-hours-row">
                                            <label>
                                                <input type="checkbox" name="delivium_store_days[]" value="<?php echo esc_attr($day_key); ?>"
                                                    <?php checked(in_array($day_key, (array) get_option('delivium_store_days', array()))); ?>>
                                                <?php echo esc_html($day_name); ?>
                                            </label>
                                            <input type="time" name="delivium_store_hours[<?php echo esc_attr($day_key); ?>][open]"
                                                value="<?php echo esc_attr(get_option("delivium_store_hours_{$day_key}_open", '09:00')); ?>">
                                            <span>-</span>
                                            <input type="time" name="delivium_store_hours[<?php echo esc_attr($day_key); ?>][close]"
                                                value="<?php echo esc_attr(get_option("delivium_store_hours_{$day_key}_close", '17:00')); ?>">
                                        </div>
                                    <?php endforeach; ?>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                    <?php
                    break;
            }
            ?>
            
            <?php submit_button(); ?>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#delivium_sms_provider').on('change', function() {
        $('.provider-settings').hide();
        $('.' + $(this).val() + '-settings').show();
    });
});
</script>

<style>
.provider-settings {
    display: none;
}
</style> 