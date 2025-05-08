<?php
/**
 * Admin delivery zones page template
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

// Get current action
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Delivery Zones', 'delivium'); ?></h1>
    <a href="<?php echo esc_url(add_query_arg('action', 'add')); ?>" class="page-title-action"><?php _e('Add New', 'delivium'); ?></a>
    <hr class="wp-header-end">

    <?php if ($action === 'list'): ?>
        <div class="delivium-zones-list">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Zone Name', 'delivium'); ?></th>
                        <th><?php _e('Coverage Area', 'delivium'); ?></th>
                        <th><?php _e('Delivery Fee', 'delivium'); ?></th>
                        <th><?php _e('Minimum Order', 'delivium'); ?></th>
                        <th><?php _e('Status', 'delivium'); ?></th>
                        <th><?php _e('Actions', 'delivium'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $zones = get_option('delivium_delivery_zones', array());
                    if (!empty($zones)):
                        foreach ($zones as $zone_id => $zone):
                    ?>
                        <tr>
                            <td><?php echo esc_html($zone['name']); ?></td>
                            <td><?php echo esc_html($zone['coverage']); ?></td>
                            <td><?php echo wc_price($zone['delivery_fee']); ?></td>
                            <td><?php echo wc_price($zone['min_order']); ?></td>
                            <td><?php echo $zone['active'] ? __('Active', 'delivium') : __('Inactive', 'delivium'); ?></td>
                            <td>
                                <a href="<?php echo esc_url(add_query_arg(array('action' => 'edit', 'zone_id' => $zone_id))); ?>"><?php _e('Edit', 'delivium'); ?></a> |
                                <a href="<?php echo esc_url(add_query_arg(array('action' => 'delete', 'zone_id' => $zone_id))); ?>" class="delete"><?php _e('Delete', 'delivium'); ?></a>
                            </td>
                        </tr>
                    <?php
                        endforeach;
                    else:
                    ?>
                        <tr>
                            <td colspan="6"><?php _e('No delivery zones found.', 'delivium'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="delivium-zone-form">
            <form method="post" action="">
                <?php wp_nonce_field('delivium_zone_action', 'delivium_zone_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="zone_name"><?php _e('Zone Name', 'delivium'); ?></label></th>
                        <td>
                            <input type="text" id="zone_name" name="zone_name" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="zone_coverage"><?php _e('Coverage Area', 'delivium'); ?></label></th>
                        <td>
                            <div id="zone_map" style="height: 400px; margin-bottom: 10px;"></div>
                            <textarea id="zone_coverage" name="zone_coverage" class="large-text" rows="3" required></textarea>
                            <p class="description"><?php _e('Draw the delivery zone on the map or enter coordinates manually.', 'delivium'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="delivery_fee"><?php _e('Delivery Fee', 'delivium'); ?></label></th>
                        <td>
                            <input type="number" id="delivery_fee" name="delivery_fee" class="regular-text" step="0.01" min="0" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="min_order"><?php _e('Minimum Order', 'delivium'); ?></label></th>
                        <td>
                            <input type="number" id="min_order" name="min_order" class="regular-text" step="0.01" min="0" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Status', 'delivium'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="zone_active" value="1">
                                <?php _e('Active', 'delivium'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
    <?php endif; ?>
</div> 