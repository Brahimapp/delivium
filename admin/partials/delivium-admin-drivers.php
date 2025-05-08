<?php
/**
 * Admin drivers page
 *
 * @link       https://delivium.top
 * @since      1.0.0
 *
 * @package    Delivium
 * @subpackage Delivium/admin/partials
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}

// Redirect to add new user page with driver role pre-selected
if (isset($_GET['action']) && $_GET['action'] === 'add-new') {
    wp_safe_redirect(add_query_arg(
        array(
            'role' => 'delivium_driver',
            'wp_http_referer' => urlencode(admin_url('admin.php?page=delivium-drivers'))
        ),
        admin_url('user-new.php')
    ));
    exit;
}

// Handle driver status updates
if (isset($_POST['update_driver_status']) && isset($_POST['driver_id']) && isset($_POST['driver_status'])) {
    check_admin_referer('update_driver_status');
    $driver_id = intval($_POST['driver_id']);
    $status = sanitize_text_field($_POST['driver_status']);
    update_user_meta($driver_id, 'driver_status', $status);
    wp_safe_redirect(add_query_arg('status_updated', '1'));
    exit;
}

?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Delivery Drivers', 'delivium'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=delivium-drivers&action=add-new')); ?>" class="page-title-action"><?php esc_html_e('Add New Driver', 'delivium'); ?></a>
    <hr class="wp-header-end">

    <?php if (isset($_GET['status_updated'])): ?>
        <div class="notice notice-success">
            <p><?php esc_html_e('Driver status updated successfully.', 'delivium'); ?></p>
        </div>
    <?php endif; ?>

    <?php
    // List existing drivers
    $drivers_list = new WP_User_Query(array(
        'role' => 'delivium_driver',
        'orderby' => 'display_name'
    ));
    
    if (!empty($drivers_list->results)): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Driver Name', 'delivium'); ?></th>
                    <th><?php esc_html_e('Email', 'delivium'); ?></th>
                    <th><?php esc_html_e('Phone', 'delivium'); ?></th>
                    <th><?php esc_html_e('Status', 'delivium'); ?></th>
                    <th><?php esc_html_e('Actions', 'delivium'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($drivers_list->results as $driver): 
                    $current_status = get_user_meta($driver->ID, 'driver_status', true) ?: 'offline';
                ?>
                    <tr>
                        <td><?php echo esc_html($driver->display_name); ?></td>
                        <td><?php echo esc_html($driver->user_email); ?></td>
                        <td><?php echo esc_html(get_user_meta($driver->ID, 'phone', true)); ?></td>
                        <td>
                            <form method="post" style="display: inline;">
                                <?php wp_nonce_field('update_driver_status'); ?>
                                <input type="hidden" name="driver_id" value="<?php echo esc_attr($driver->ID); ?>">
                                <select name="driver_status" onchange="this.form.submit()">
                                    <option value="offline" <?php selected($current_status, 'offline'); ?>><?php esc_html_e('Offline', 'delivium'); ?></option>
                                    <option value="available" <?php selected($current_status, 'available'); ?>><?php esc_html_e('Available', 'delivium'); ?></option>
                                    <option value="busy" <?php selected($current_status, 'busy'); ?>><?php esc_html_e('Busy', 'delivium'); ?></option>
                                </select>
                                <input type="hidden" name="update_driver_status" value="1">
                            </form>
                        </td>
                        <td>
                            <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $driver->ID)); ?>" class="button button-small"><?php esc_html_e('Edit', 'delivium'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p><?php esc_html_e('No drivers found.', 'delivium'); ?></p>
    <?php endif; ?>
</div> 