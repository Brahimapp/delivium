<?php
/**
 * The assigned order email vendor functionality of the plugin.
 *
 * @link       https://delivium.top
 * @since      1.0.0
 *
 * @package    Delivium
 * @subpackage Delivium/includes
 * @author     Delivium Team <support@delivium.top>
 */

/**
 * Assigned order email vendor.
 *
 * @link       https://delivium.top
 * @since      1.0.0
 *
 * @package    Delivium
 * @subpackage Delivium/includes
 * @author     Delivium Team <support@delivium.top>
 */
class Delivium_Assigned_Order_Email_Vendor extends WC_Email {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id             = 'delivium_assigned_order_email_vendor';
        $this->title          = __( 'Delivium - Driver has been Assigned to Order', 'delivium' );
        $this->description    = __( 'Assigned order emails are sent to chosen recipient(s) when a driver has been assigned to order.', 'delivium' );
        $this->template_html  = 'emails/delivium-assigned-order-vendor.php';
        $this->template_plain = 'emails/plain/delivium-assigned-order-vendor.php';
        $this->placeholders   = array(
            '{site_title}'   => $this->get_blogname(),
            '{order_date}'   => '',
            '{order_number}' => '',
        );

        // Call parent constructor.
        parent::__construct();

        // Other settings.
        $this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
    }

    /**
     * Get email subject.
     *
     * @since  3.1.0
     * @return string
     */
    public function get_default_subject() {
        return __( 'Your {site_title} order has been assigned to driver!', 'delivium' );
    }

    /**
     * Get email heading.
     *
     * @since  3.1.0
     * @return string
     */
    public function get_default_heading() {
        return __( 'Order #{order_number}', 'delivium' );
    }

    /**
     * Trigger the sending of this email.
     *
     * @param int            $order_id The order ID.
     * @param WC_Order|false $order Order object.
     * @param int            $seller_id Seller ID.
     */
    public function trigger( $order_id, $order = false, $seller_id = '' ) {
        $this->setup_locale();

        if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
            $order = wc_get_order( $order_id );
        }

        if ( is_a( $order, 'WC_Order' ) ) {
            $this->object                         = $order;
            $store                                = new Delivium_Store();
            $seller_email                         = $store->store_email__premium_only( $seller_id );
            $this->recipient                      = $seller_email;
            $this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
            $this->placeholders['{order_number}'] = $this->object->get_order_number();
        }

        if ( $this->is_enabled() && $this->get_recipient() ) {
            $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        }

        $this->restore_locale();
    }

    /**
     * Get content html.
     *
     * @return string
     */
    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html,
            array(
                'order'         => $this->object,
                'email_heading' => $this->get_heading(),
                'sent_to_admin' => true,
                'plain_text'    => false,
                'email'         => $this,
            )
        );
    }

    /**
     * Get content plain.
     *
     * @return string
     */
    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain,
            array(
                'order'         => $this->object,
                'email_heading' => $this->get_heading(),
                'sent_to_admin' => true,
                'plain_text'    => true,
                'email'         => $this,
            )
        );
    }

    /**
     * Default content to show below main email content.
     *
     * @since 1.0.0
     * @return string
     */
    public function get_default_additional_content() {
        return __( 'Thanks for using {site_address}!', 'delivium' );
    }
} 