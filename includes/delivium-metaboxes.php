<?php
/**
 * The metaboxes functionality of the plugin.
 *
 * @link       https://delivium.top
 * @since      1.0.0
 *
 * @package    Delivium
 * @subpackage Delivium/includes
 * @author     Delivium Team <support@delivium.top>
 */

/**
 * Add driver assignment metabox to order page
 */
function delivium_add_order_meta_boxes() {
	add_meta_box(
		'delivium_driver_assignment',
		__('Driver Assignment', 'delivium'),
		'delivium_driver_assignment_meta_box',
		'shop_order',
		'side',
		'default'
	);
}
add_action('add_meta_boxes', 'delivium_add_order_meta_boxes');

/**
 * Display driver assignment metabox content
 */
function delivium_driver_assignment_meta_box($post) {
	$order = wc_get_order($post->ID);
	$current_driver_id = $order->get_meta('_delivium_assigned_driver');
	$commission = $order->get_meta('_delivium_driver_commission');
	$note = $order->get_meta('_delivium_delivery_note_to_driver');
	
	// Get all drivers
	$drivers = get_users(array(
		'role' => 'delivium_driver',
		'orderby' => 'display_name'
	));
	
	wp_nonce_field('delivium_save_driver_assignment', 'delivium_driver_nonce');
	?>
	<div class="delivium-driver-assignment">
		<p>
			<label for="delivium_assigned_driver"><?php _e('Select Driver', 'delivium'); ?></label>
			<select name="delivium_assigned_driver" id="delivium_assigned_driver" class="widefat">
				<option value=""><?php _e('Select Driver', 'delivium'); ?></option>
				<?php 
				$available_drivers = array();
				$unavailable_drivers = array();
				
				foreach ($drivers as $driver) {
					$status = get_user_meta($driver->ID, '_delivium_status', true) ?: 'offline';
					$driver_data = array(
						'id' => $driver->ID,
						'name' => $driver->display_name,
						'selected' => selected($current_driver_id, $driver->ID, false)
					);
					
					if ($status === 'online') {
						$available_drivers[] = $driver_data;
					} else {
						$unavailable_drivers[] = $driver_data;
					}
				}
				
				if (!empty($available_drivers)) : ?>
					<optgroup label="<?php _e('Available Drivers', 'delivium'); ?>">
						<?php foreach ($available_drivers as $driver) : ?>
							<option value="<?php echo esc_attr($driver['id']); ?>" <?php echo $driver['selected']; ?>>
								<?php echo esc_html($driver['name']); ?>
							</option>
						<?php endforeach; ?>
					</optgroup>
				<?php endif;
				
				if (!empty($unavailable_drivers)) : ?>
					<optgroup label="<?php _e('Unavailable Drivers', 'delivium'); ?>">
						<?php foreach ($unavailable_drivers as $driver) : ?>
							<option value="<?php echo esc_attr($driver['id']); ?>" <?php echo $driver['selected']; ?>>
								<?php echo esc_html($driver['name']); ?>
							</option>
						<?php endforeach; ?>
					</optgroup>
				<?php endif; ?>
			</select>
		</p>

		<?php if (defined('DELIVIUM_PREMIUM') && DELIVIUM_PREMIUM) : ?>
			<p>
				<label for="delivium_driver_commission"><?php _e('Driver Commission', 'delivium'); ?></label>
				<input type="number" 
					name="delivium_driver_commission" 
					id="delivium_driver_commission" 
					class="widefat"
					step="0.01"
					min="0"
					value="<?php echo esc_attr($commission); ?>"
				>
			</p>
			
			<p>
				<label for="delivium_delivery_note"><?php _e('Note to Driver', 'delivium'); ?></label>
				<textarea name="delivium_delivery_note" 
					id="delivium_delivery_note" 
					class="widefat"
					rows="3"
					placeholder="<?php esc_attr_e('Add special instructions for the driver', 'delivium'); ?>"
				><?php echo esc_textarea($note); ?></textarea>
			</p>
		<?php endif; ?>

		<?php if ($current_driver_id) : 
			$driver = get_userdata($current_driver_id);
			$status = get_user_meta($current_driver_id, '_delivium_status', true) ?: 'offline';
			?>
			<div class="delivium-current-driver">
				<h4><?php _e('Current Driver', 'delivium'); ?></h4>
				<p>
					<strong><?php echo esc_html($driver->display_name); ?></strong><br>
					<span class="status-<?php echo esc_attr($status); ?>">
						<?php echo esc_html(ucfirst($status)); ?>
					</span>
				</p>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Save driver assignment
 */
function delivium_save_driver_assignment($post_id) {
	if (!isset($_POST['delivium_driver_nonce']) || 
		!wp_verify_nonce($_POST['delivium_driver_nonce'], 'delivium_save_driver_assignment')) {
		return;
	}
	
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}
	
	if (!current_user_can('edit_post', $post_id)) {
		return;
	}
	
	$order = wc_get_order($post_id);
	if (!$order) {
		return;
	}
	
	// Handle driver assignment
	$new_driver_id = isset($_POST['delivium_assigned_driver']) ? 
		sanitize_text_field($_POST['delivium_assigned_driver']) : '';
	$current_driver_id = $order->get_meta('_delivium_assigned_driver');
	
	if ($new_driver_id !== $current_driver_id) {
		$order->update_meta_data('_delivium_assigned_driver', $new_driver_id);
		
		if ($new_driver_id) {
			// Update order status to assigned
			$order->update_status('assigned', __('Order assigned to driver.', 'delivium'));
			do_action('delivium_order_assigned_to_driver', $post_id, $new_driver_id);
		}
	}
	
	// Handle premium features
	if (defined('DELIVIUM_PREMIUM') && DELIVIUM_PREMIUM) {
		// Save commission
		if (isset($_POST['delivium_driver_commission'])) {
			$commission = sanitize_text_field($_POST['delivium_driver_commission']);
			$order->update_meta_data('_delivium_driver_commission', $commission);
		}
		
		// Save note
		if (isset($_POST['delivium_delivery_note'])) {
			$note = sanitize_textarea_field($_POST['delivium_delivery_note']);
			$order->update_meta_data('_delivium_delivery_note_to_driver', $note);
		}
	}
	
	$order->save();
}
add_action('save_post', 'delivium_save_driver_assignment');