<?php
/**
 * Customer out for delivery order email (plain text)
 *
 * @package Delivium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: Customer first name */
echo sprintf( esc_html__( 'Hi %s,', 'delivium' ), esc_html( $order->get_billing_first_name() ) ) . "\n\n";
/* translators: %s: Order number */
echo sprintf( esc_html__( 'Your order #%s is out for delivery', 'delivium' ), esc_html( $order->get_order_number() ) ) . "\n\n";

$tracking_url = delivium_tracking_page_url( $order->get_id() );
if ( $tracking_url ) {
    echo esc_html__( 'Track your delivery:', 'delivium' ) . "\n";
    echo esc_url( $tracking_url ) . "\n\n";
}

echo esc_html__( 'Order Details', 'delivium' ) . "\n";
echo "=============\n\n";

echo esc_html__( 'Driver Information', 'delivium' ) . "\n";
echo "===================\n\n";

$delivium_driver_id = $order->get_meta('delivium_driverid');
if ( $delivium_driver_id ) {
    $driver = new DELIVIUM_Driver();
    echo wp_strip_all_tags( $driver->get_driver_info( $delivium_driver_id, 'plain' ) ) . "\n";
    echo wp_strip_all_tags( $driver->get_vehicle_info( $delivium_driver_id, 'plain' ) ) . "\n\n";
}

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
