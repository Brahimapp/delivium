<?php
/**
 * Admin time slots page template
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

// Get current view
$current_view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'calendar';
$current_date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : date('Y-m-d');
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Delivery Time Slots', 'delivium'); ?></h1>
    <a href="<?php echo esc_url(add_query_arg('view', 'settings')); ?>" class="page-title-action"><?php _e('Settings', 'delivium'); ?></a>
    <hr class="wp-header-end">

    <!-- View Navigation -->
    <ul class="subsubsub">
        <li>
            <a href="<?php echo esc_url(add_query_arg('view', 'calendar')); ?>" <?php echo $current_view === 'calendar' ? 'class="current"' : ''; ?>>
                <?php _e('Calendar View', 'delivium'); ?>
            </a> |
        </li>
        <li>
            <a href="<?php echo esc_url(add_query_arg('view', 'list')); ?>" <?php echo $current_view === 'list' ? 'class="current"' : ''; ?>>
                <?php _e('List View', 'delivium'); ?>
            </a>
        </li>
    </ul>

    <?php if ($current_view === 'calendar'): ?>
        <div class="delivium-calendar-view">
            <!-- Calendar Navigation -->
            <div class="tablenav top">
                <div class="alignleft actions">
                    <a href="<?php echo esc_url(add_query_arg('date', date('Y-m-d', strtotime('-1 month', strtotime($current_date))))); ?>" class="button">&laquo; <?php _e('Previous Month', 'delivium'); ?></a>
                    <a href="<?php echo esc_url(add_query_arg('date', date('Y-m-d'))); ?>" class="button"><?php _e('Today', 'delivium'); ?></a>
                    <a href="<?php echo esc_url(add_query_arg('date', date('Y-m-d', strtotime('+1 month', strtotime($current_date))))); ?>" class="button"><?php _e('Next Month', 'delivium'); ?> &raquo;</a>
                </div>
            </div>

            <!-- Calendar Grid -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <?php
                        $weekdays = array(
                            __('Sunday', 'delivium'),
                            __('Monday', 'delivium'),
                            __('Tuesday', 'delivium'),
                            __('Wednesday', 'delivium'),
                            __('Thursday', 'delivium'),
                            __('Friday', 'delivium'),
                            __('Saturday', 'delivium')
                        );
                        foreach ($weekdays as $day) {
                            echo '<th>' . esc_html($day) . '</th>';
                        }
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $timestamp = strtotime($current_date);
                    $days_in_month = date('t', $timestamp);
                    $first_day = date('w', strtotime(date('Y-m-01', $timestamp)));
                    $last_day = date('w', strtotime(date('Y-m-' . $days_in_month, $timestamp)));
                    
                    $calendar_days = array();
                    for ($i = 0; $i < $first_day; $i++) {
                        $calendar_days[] = '';
                    }
                    for ($i = 1; $i <= $days_in_month; $i++) {
                        $calendar_days[] = $i;
                    }
                    for ($i = $last_day; $i < 6; $i++) {
                        $calendar_days[] = '';
                    }

                    $weeks = array_chunk($calendar_days, 7);
                    foreach ($weeks as $week) {
                        echo '<tr>';
                        foreach ($week as $day) {
                            echo '<td>';
                            if ($day !== '') {
                                echo '<div class="calendar-day">';
                                echo '<span class="day-number">' . esc_html($day) . '</span>';
                                // Add time slots for this day
                                $date = date('Y-m-d', strtotime(date('Y-m-' . sprintf('%02d', $day), $timestamp)));
                                $slots = get_option('delivium_timeslots_' . $date, array());
                                if (!empty($slots)) {
                                    echo '<div class="time-slots">';
                                    foreach ($slots as $slot) {
                                        echo '<div class="time-slot">';
                                        echo esc_html($slot['start_time'] . ' - ' . $slot['end_time']);
                                        echo '<span class="slot-capacity">(' . esc_html($slot['available']) . '/' . esc_html($slot['capacity']) . ')</span>';
                                        echo '</div>';
                                    }
                                    echo '</div>';
                                }
                                echo '</div>';
                            }
                            echo '</td>';
                        }
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    <?php elseif ($current_view === 'list'): ?>
        <div class="delivium-list-view">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Date', 'delivium'); ?></th>
                        <th><?php _e('Time Slot', 'delivium'); ?></th>
                        <th><?php _e('Capacity', 'delivium'); ?></th>
                        <th><?php _e('Available', 'delivium'); ?></th>
                        <th><?php _e('Status', 'delivium'); ?></th>
                        <th><?php _e('Actions', 'delivium'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Get time slots for the next 7 days
                    $start_date = date('Y-m-d');
                    for ($i = 0; $i < 7; $i++) {
                        $date = date('Y-m-d', strtotime("+$i days"));
                        $slots = get_option('delivium_timeslots_' . $date, array());
                        if (!empty($slots)) {
                            foreach ($slots as $slot_id => $slot) {
                                ?>
                                <tr>
                                    <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($date))); ?></td>
                                    <td><?php echo esc_html($slot['start_time'] . ' - ' . $slot['end_time']); ?></td>
                                    <td><?php echo esc_html($slot['capacity']); ?></td>
                                    <td><?php echo esc_html($slot['available']); ?></td>
                                    <td><?php echo $slot['available'] > 0 ? __('Available', 'delivium') : __('Full', 'delivium'); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url(add_query_arg(array('action' => 'edit', 'date' => $date, 'slot_id' => $slot_id))); ?>"><?php _e('Edit', 'delivium'); ?></a> |
                                        <a href="<?php echo esc_url(add_query_arg(array('action' => 'delete', 'date' => $date, 'slot_id' => $slot_id))); ?>" class="delete"><?php _e('Delete', 'delivium'); ?></a>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="delivium-timeslot-settings">
            <form method="post" action="">
                <?php wp_nonce_field('delivium_timeslot_settings', 'delivium_timeslot_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Default Time Slots', 'delivium'); ?></th>
                        <td>
                            <div id="default-slots">
                                <div class="slot-row">
                                    <input type="time" name="slot_start[]" required>
                                    <span>-</span>
                                    <input type="time" name="slot_end[]" required>
                                    <input type="number" name="slot_capacity[]" min="1" value="5" required>
                                    <button type="button" class="button remove-slot"><?php _e('Remove', 'delivium'); ?></button>
                                </div>
                            </div>
                            <button type="button" class="button add-slot"><?php _e('Add Time Slot', 'delivium'); ?></button>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Advance Booking', 'delivium'); ?></th>
                        <td>
                            <input type="number" name="advance_booking_days" min="1" value="<?php echo esc_attr(get_option('delivium_advance_booking_days', '7')); ?>">
                            <p class="description"><?php _e('Number of days in advance customers can book deliveries.', 'delivium'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Cut-off Time', 'delivium'); ?></th>
                        <td>
                            <input type="number" name="cutoff_hours" min="0" max="24" value="<?php echo esc_attr(get_option('delivium_cutoff_hours', '2')); ?>">
                            <p class="description"><?php _e('Hours before delivery time to stop accepting orders.', 'delivium'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Save Settings', 'delivium')); ?>
            </form>
        </div>
    <?php endif; ?>
</div> 