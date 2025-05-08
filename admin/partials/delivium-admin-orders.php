<?php
/**
 * Admin orders page template
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

// Get the current view (default to 'list')
$current_view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'list';
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="delivium-orders-page">
        <div class="delivium-orders-filters">
            <form method="get" action="">
                <input type="hidden" name="page" value="delivium-orders">
                
                <select name="status" id="filter-by-status">
                    <option value=""><?php _e('All Statuses', 'delivium'); ?></option>
                    <?php
                    $statuses = delivium_get_order_statuses();
                    foreach ($statuses as $status => $label) {
                        $selected = isset($_GET['status']) && $_GET['status'] === $status ? 'selected' : '';
                        echo '<option value="' . esc_attr($status) . '" ' . $selected . '>' . esc_html($label) . '</option>';
                    }
                    ?>
                </select>
                
                <select name="driver" id="filter-by-driver">
                    <option value=""><?php _e('All Drivers', 'delivium'); ?></option>
                    <?php
                    $drivers = delivium_get_drivers();
                    foreach ($drivers as $driver) {
                        $selected = isset($_GET['driver']) && $_GET['driver'] === $driver->ID ? 'selected' : '';
                        echo '<option value="' . esc_attr($driver->ID) . '" ' . $selected . '>' . esc_html($driver->display_name) . '</option>';
                    }
                    ?>
                </select>
                
                <input type="date" name="date" value="<?php echo isset($_GET['date']) ? esc_attr($_GET['date']) : ''; ?>" placeholder="<?php _e('Filter by date', 'delivium'); ?>">
                
                <?php submit_button(__('Filter', 'delivium'), 'secondary', 'filter', false); ?>
            </form>
        </div>
        
        <div class="delivium-orders-list">
            <?php
            // Display orders list or grid based on current view
            if ($current_view === 'grid') {
                delivium_display_orders_grid();
            } else {
                delivium_display_orders_list();
            }
            ?>
        </div>
        
        <?php
        // Display pagination
        $total_pages = delivium_get_orders_total_pages();
        if ($total_pages > 1) {
            echo '<div class="delivium-pagination">';
            echo paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => __('&laquo;'),
                'next_text' => __('&raquo;'),
                'total' => $total_pages,
                'current' => max(1, get_query_var('paged'))
            ));
            echo '</div>';
        }
        ?>
    </div>
</div> 