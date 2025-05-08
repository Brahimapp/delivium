<?php
/**
 * Driver assigned order email
 *
 * @package Delivium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email );
$driver_id = $order->get_meta( 'delivium_driverid' );
$user_info = get_userdata( $driver_id );

?>

<?php /* translators: %s: Customer first name */ ?>
<p><?php printf( esc_html__( 'Hi %s,', 'delivium' ), esc_html( $order->get_billing_first_name() ) ); ?></p>
<?php /* translators: %s: Order number */ ?>
<p><?php printf( esc_html__( 'You have been assigned to deliver order #%s', 'delivium' ), esc_html( $order->get_order_number() ) ); ?></p>

<?php
$tracking_url = delivium_tracking_page_url( $order->get_id() );
if ( $tracking_url ) : ?>
	<p>
		<?php esc_html_e( 'Track this delivery:', 'delivium' ); ?><br>
		<a href="<?php echo esc_url( $tracking_url ); ?>"><?php echo esc_url( $tracking_url ); ?></a>
	</p>
<?php endif; ?>

<h2><?php esc_html_e( 'Order Details', 'delivium' ); ?></h2>

<?php if (defined('DELIVIUM_PREMIUM') && DELIVIUM_PREMIUM) : ?>
    <?php
    $route = $order->get_meta('delivium_order_route');
    if (!empty($route)) {
        $duration_text = $route['duration_text'];
        if (!empty($duration_text)) {
            echo '<p><strong>' . sprintf(esc_html__('Estimated delivery time: %s', 'delivium'), esc_html($duration_text)) . '</strong></p>';
        }
    }
    ?>
<?php endif; ?>

<?php
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
