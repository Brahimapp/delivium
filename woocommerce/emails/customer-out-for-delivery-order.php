<?php
/**
 * Customer out for delivery order email
 *
 * @package Delivium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php /* translators: %s: Customer first name */ ?>
<p><?php printf( esc_html__( 'Hi %s,', 'delivium' ), esc_html( $order->get_billing_first_name() ) ); ?></p>
<?php /* translators: %s: Order number */ ?>
<p><?php printf( esc_html__( 'Your order #%s is out for delivery', 'delivium' ), esc_html( $order->get_order_number() ) ); ?></p>

<?php
$tracking_url = delivium_tracking_page_url( $order->get_id() );
if ( $tracking_url ) : ?>
	<p>
		<?php esc_html_e( 'Track your delivery:', 'delivium' ); ?><br>
		<a href="<?php echo esc_url( $tracking_url ); ?>"><?php echo esc_url( $tracking_url ); ?></a>
	</p>
<?php endif; ?>

<?php
// ETA.
$route = $order->get_meta('delivium_order_route');
if ( ! empty ( $route ) ) {
    $duration_text = $route['duration_text'];
    if ( '' !== $duration_text ) {
        echo '<p><strong>' . sprintf( esc_html__( 'Estimated time of arrival: %s', 'delivium' ), esc_html( $duration_text ) ) . '</strong></p>';
    }
}

$delivium_driver_id = $order->get_meta('delivium_driverid');
if ( '' !== $delivium_driver_id ) {
    $driver = new DELIVIUM_Driver();
    if ( '' !== $delivium_driver_id ) {
        echo $driver->get_driver_info( $delivium_driver_id, 'html' );
        echo $driver->get_vehicle_info( $delivium_driver_id, 'html' );
    }
}

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
