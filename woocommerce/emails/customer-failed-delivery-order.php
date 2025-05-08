<?php
/**
 * Customer processing order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-processing-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates/Emails
 * @version 3.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php /* translators: %s: Customer first name */ ?>
<p><?php echo sprintf( esc_html__( 'Hi %s,', 'delivium' ), esc_html( $order->get_billing_first_name() ) ) . "\n\n"; ?></p>
<?php /* translators: %s: Order number */ ?>
<p><?php echo sprintf( esc_html__( 'Unfortunately, the delivery for your order #%s has failed.', 'delivium' ), esc_html( $order->get_id() ) ) . "\n\n"; ?></p>


<?php

	$delivium_driver_id = $order->get_meta('delivium_driverid');
	if ( '' !== $delivium_driver_id ) {
		$driver = new DELIVIUM_Driver();
		if ( '' !== $delivium_driver_id ) {
			echo $driver->get_driver_info__premium_only( $delivium_driver_id, 'html' );
			echo $driver->get_vehicle_info__premium_only( $delivium_driver_id, 'html' );
		}
	}

	/* driver note */
	$delivium_driver_note = $order->get_meta('delivium_driver_note');
	if ( '' !== $delivium_driver_note ){
		echo '<p><strong>' . esc_html( __( 'Driver note', 'delivium' ) ) . ':</strong> ' . $delivium_driver_note . '</p>';
	}

	// Signature
	$delivium_order_signature = $order->get_meta('delivium_order_last_signature');
	if ( '' !== $delivium_order_signature ) {
		echo '<p><strong>' . esc_html( __( 'Signature', 'delivium' ) ) . ':</strong> ';
		echo '<a href="' . esc_attr( $delivium_order_signature ) . '" target="_blank">' . $delivium_order_signature . '</a></p>';
	}

	// Photo
	$delivium_order_delivery_image = $order->get_meta('delivium_order_last_delivery_image');
	if ( '' !== $delivium_order_delivery_image ) {
		echo '<p><strong>' . esc_html( __( 'Delivery image', 'delivium' ) ) . ':</strong> ';
		echo '<a href="' . esc_attr( $delivium_order_delivery_image ) . '" target="_blank">' . $delivium_order_delivery_image . '</a></p>';
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
