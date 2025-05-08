<?php
/**
 * The shortcode functionality of the plugin.
 *
 * @link       https://delivium.top
 * @since      1.0.0
 *
 * @package    Delivium
 * @subpackage Delivium/includes
 */

/**
 * The shortcode functionality of the plugin.
 *
 * Defines the plugin name, version, and registers the shortcodes.
 *
 * @package    Delivium
 * @subpackage Delivium/includes
 * @author     Delivium Team <support@delivium.top>
 */
class Delivium_Shortcodes {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_shortcode('delivium_driver_portal', array($this, 'driver_portal_shortcode'));
        add_shortcode('delivium_order_tracking', array($this, 'order_tracking_shortcode'));
    }

    /**
     * Driver portal shortcode
     */
    public function driver_portal_shortcode($atts) {
        if (!is_user_logged_in()) {
            return $this->get_login_form();
        }

        $user = wp_get_current_user();
        if (!in_array('delivium_driver', $user->roles)) {
            return '<p>' . __('Access denied. This area is for delivery drivers only.', 'delivium') . '</p>';
        }

        // Get current view
        $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'dashboard';
        
        ob_start();
        ?>
        <div class="delivium-driver-portal">
            <nav class="delivium-nav">
                <ul>
                    <li class="<?php echo $view === 'dashboard' ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(add_query_arg('view', 'dashboard')); ?>">
                            <?php _e('Dashboard', 'delivium'); ?>
                        </a>
                    </li>
                    <li class="<?php echo $view === 'orders' ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(add_query_arg('view', 'orders')); ?>">
                            <?php _e('Orders', 'delivium'); ?>
                        </a>
                    </li>
                    <li class="<?php echo $view === 'earnings' ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(add_query_arg('view', 'earnings')); ?>">
                            <?php _e('Earnings', 'delivium'); ?>
                        </a>
                    </li>
                    <li class="<?php echo $view === 'profile' ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(add_query_arg('view', 'profile')); ?>">
                            <?php _e('Profile', 'delivium'); ?>
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="delivium-content">
                <?php
                switch ($view) {
                    case 'dashboard':
                        $this->display_driver_dashboard();
                        break;
                    case 'orders':
                        $this->display_driver_orders();
                        break;
                    case 'earnings':
                        $this->display_driver_earnings();
                        break;
                    case 'profile':
                        $this->display_driver_profile();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Display driver dashboard
     */
    private function display_driver_dashboard() {
        $user_id = get_current_user_id();
        $status = get_user_meta($user_id, '_delivium_status', true) ?: 'offline';
        $today_orders = $this->get_driver_orders_count($user_id, 'today');
        $total_earnings = $this->get_driver_earnings($user_id, 'total');
        ?>
        <div class="delivium-dashboard">
            <div class="status-toggle">
                <label>
                    <input type="checkbox" id="driver-status" 
                        <?php checked($status, 'online'); ?>>
                    <?php _e('Online', 'delivium'); ?>
                </label>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?php _e('Today\'s Orders', 'delivium'); ?></h3>
                    <div class="stat-value"><?php echo esc_html($today_orders); ?></div>
                </div>
                
                <div class="stat-card">
                    <h3><?php _e('Total Earnings', 'delivium'); ?></h3>
                    <div class="stat-value">
                        <?php echo wc_price($total_earnings); ?>
                    </div>
                </div>
            </div>

            <div class="recent-orders">
                <h3><?php _e('Recent Orders', 'delivium'); ?></h3>
                <?php $this->display_driver_orders(5); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Display driver orders
     */
    private function display_driver_orders($limit = -1) {
        $user_id = get_current_user_id();
        $orders = $this->get_driver_orders($user_id, $limit);

        if (empty($orders)) {
            echo '<p>' . __('No orders found.', 'delivium') . '</p>';
            return;
        }
        ?>
        <table class="delivium-orders">
            <thead>
                <tr>
                    <th><?php _e('Order', 'delivium'); ?></th>
                    <th><?php _e('Customer', 'delivium'); ?></th>
                    <th><?php _e('Address', 'delivium'); ?></th>
                    <th><?php _e('Status', 'delivium'); ?></th>
                    <th><?php _e('Actions', 'delivium'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order) : ?>
                    <tr>
                        <td>#<?php echo $order->get_id(); ?></td>
                        <td><?php echo esc_html($order->get_formatted_billing_full_name()); ?></td>
                        <td><?php echo esc_html($order->get_formatted_shipping_address()); ?></td>
                        <td><?php echo esc_html(wc_get_order_status_name($order->get_status())); ?></td>
                        <td>
                            <?php if ($order->has_status('assigned')) : ?>
                                <button class="button start-delivery" 
                                    data-order="<?php echo esc_attr($order->get_id()); ?>">
                                    <?php _e('Start Delivery', 'delivium'); ?>
                                </button>
                            <?php elseif ($order->has_status('out-for-delivery')) : ?>
                                <button class="button complete-delivery" 
                                    data-order="<?php echo esc_attr($order->get_id()); ?>">
                                    <?php _e('Complete Delivery', 'delivium'); ?>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Display driver earnings
     */
    private function display_driver_earnings() {
        $user_id = get_current_user_id();
        $earnings = $this->get_driver_earnings($user_id);
        ?>
        <div class="delivium-earnings">
            <div class="earnings-summary">
                <div class="stat-card">
                    <h3><?php _e('Today', 'delivium'); ?></h3>
                    <div class="stat-value">
                        <?php echo wc_price($earnings['today']); ?>
                    </div>
                </div>
                
                <div class="stat-card">
                    <h3><?php _e('This Week', 'delivium'); ?></h3>
                    <div class="stat-value">
                        <?php echo wc_price($earnings['week']); ?>
                    </div>
                </div>
                
                <div class="stat-card">
                    <h3><?php _e('This Month', 'delivium'); ?></h3>
                    <div class="stat-value">
                        <?php echo wc_price($earnings['month']); ?>
                    </div>
                </div>
            </div>

            <div class="earnings-history">
                <h3><?php _e('Earnings History', 'delivium'); ?></h3>
                <?php $this->display_earnings_table(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Display driver profile
     */
    private function display_driver_profile() {
        $user = wp_get_current_user();
        $phone = get_user_meta($user->ID, 'billing_phone', true);
        ?>
        <div class="delivium-profile">
            <form method="post" class="profile-form">
                <?php wp_nonce_field('delivium_update_profile', 'profile_nonce'); ?>
                
                <div class="form-group">
                    <label><?php _e('Name', 'delivium'); ?></label>
                    <input type="text" name="display_name" 
                        value="<?php echo esc_attr($user->display_name); ?>" required>
                </div>
                
                <div class="form-group">
                    <label><?php _e('Email', 'delivium'); ?></label>
                    <input type="email" name="email" 
                        value="<?php echo esc_attr($user->user_email); ?>" required>
                </div>
                
                <div class="form-group">
                    <label><?php _e('Phone', 'delivium'); ?></label>
                    <input type="tel" name="phone" 
                        value="<?php echo esc_attr($phone); ?>" required>
                </div>
                
                <div class="form-group">
                    <label><?php _e('New Password', 'delivium'); ?></label>
                    <input type="password" name="password">
                    <p class="description">
                        <?php _e('Leave blank to keep current password', 'delivium'); ?>
                    </p>
                </div>
                
                <button type="submit" class="button button-primary">
                    <?php _e('Update Profile', 'delivium'); ?>
                </button>
            </form>
        </div>
        <?php
    }

    /**
     * Get driver orders
     */
    private function get_driver_orders($driver_id, $limit = -1) {
        $args = array(
            'meta_key' => '_delivium_assigned_driver',
            'meta_value' => $driver_id,
            'post_type' => 'shop_order',
            'post_status' => array('wc-assigned', 'wc-out-for-delivery'),
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC'
        );

        $orders = wc_get_orders($args);
        return $orders;
    }

    /**
     * Get driver orders count
     */
    private function get_driver_orders_count($driver_id, $period = 'today') {
        $args = array(
            'meta_key' => '_delivium_assigned_driver',
            'meta_value' => $driver_id,
            'post_type' => 'shop_order',
            'post_status' => 'wc-completed',
            'posts_per_page' => -1
        );

        switch ($period) {
            case 'today':
                $args['date_query'] = array(
                    array(
                        'after' => '1 day ago'
                    )
                );
                break;
        }

        $orders = wc_get_orders($args);
        return count($orders);
    }

    /**
     * Get driver earnings
     */
    private function get_driver_earnings($driver_id, $period = 'all') {
        $earnings = array(
            'today' => 0,
            'week' => 0,
            'month' => 0,
            'total' => 0
        );

        $args = array(
            'meta_key' => '_delivium_assigned_driver',
            'meta_value' => $driver_id,
            'post_type' => 'shop_order',
            'post_status' => 'wc-completed',
            'posts_per_page' => -1
        );

        $orders = wc_get_orders($args);

        foreach ($orders as $order) {
            $commission = $order->get_meta('_delivium_driver_commission') ?: 0;
            $order_date = $order->get_date_created()->getTimestamp();
            
            $earnings['total'] += $commission;
            
            if ($order_date > strtotime('-1 day')) {
                $earnings['today'] += $commission;
            }
            
            if ($order_date > strtotime('-1 week')) {
                $earnings['week'] += $commission;
            }
            
            if ($order_date > strtotime('-1 month')) {
                $earnings['month'] += $commission;
            }
        }

        return $period === 'all' ? $earnings : $earnings[$period];
    }

    /**
     * Display earnings table
     */
    private function display_earnings_table() {
        $user_id = get_current_user_id();
        $orders = $this->get_driver_orders($user_id);
        ?>
        <table class="delivium-earnings-table">
            <thead>
                <tr>
                    <th><?php _e('Order', 'delivium'); ?></th>
                    <th><?php _e('Date', 'delivium'); ?></th>
                    <th><?php _e('Commission', 'delivium'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order) : 
                    $commission = $order->get_meta('_delivium_driver_commission') ?: 0;
                    ?>
                    <tr>
                        <td>#<?php echo $order->get_id(); ?></td>
                        <td><?php echo $order->get_date_completed()->date_i18n(get_option('date_format')); ?></td>
                        <td><?php echo wc_price($commission); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Get login form
     */
    private function get_login_form() {
        ob_start();
        ?>
        <div class="delivium-login">
            <h2><?php _e('Driver Login', 'delivium'); ?></h2>
            <?php wp_login_form(array(
                'redirect' => get_permalink()
            )); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Order tracking shortcode
     */
    public function order_tracking_shortcode($atts) {
        ob_start();
        ?>
        <div class="delivium-order-tracking">
            <form method="post" class="tracking-form">
                <div class="form-group">
                    <label><?php _e('Order ID', 'delivium'); ?></label>
                    <input type="text" name="order_id" required>
                </div>
                
                <div class="form-group">
                    <label><?php _e('Email', 'delivium'); ?></label>
                    <input type="email" name="order_email" required>
                </div>
                
                <button type="submit" class="button">
                    <?php _e('Track Order', 'delivium'); ?>
                </button>
            </form>

            <?php if (isset($_POST['order_id']) && isset($_POST['order_email'])) : 
                $order_id = absint($_POST['order_id']);
                $order_email = sanitize_email($_POST['order_email']);
                $order = wc_get_order($order_id);

                if ($order && $order->get_billing_email() === $order_email) :
                    $status = $order->get_status();
                    $driver_id = $order->get_meta('_delivium_assigned_driver');
                    $driver = $driver_id ? get_userdata($driver_id) : null;
                    ?>
                    <div class="tracking-result">
                        <h3><?php _e('Order Status', 'delivium'); ?></h3>
                        <div class="status">
                            <?php echo wc_get_order_status_name($status); ?>
                        </div>

                        <?php if ($driver) : ?>
                            <div class="driver-info">
                                <h4><?php _e('Delivery Driver', 'delivium'); ?></h4>
                                <p><?php echo esc_html($driver->display_name); ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="delivery-address">
                            <h4><?php _e('Delivery Address', 'delivium'); ?></h4>
                            <p><?php echo $order->get_formatted_shipping_address(); ?></p>
                        </div>
                    </div>
                <?php else : ?>
                    <p class="error">
                        <?php _e('Order not found or email does not match.', 'delivium'); ?>
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
} 