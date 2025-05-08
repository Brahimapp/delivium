<?php
/**
 * Delivered email admin
 *
 * @link       https://delivium.top
 * @since      1.0.0
 *
 * @package    Delivium
 * @subpackage Delivium/includes
 * @author     Delivium Team <support@delivium.top>
 */

/**
 * The delivered email admin functionality of the plugin.
 *
 * @link       https://delivium.top
 * @since      1.0.0
 *
 * @package    Delivium
 * @subpackage Delivium/includes
 * @author     Delivium Team <support@delivium.top>
 */
class Delivium_Delivered_Email_Admin extends WC_Email {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id             = 'delivium_delivered_email_admin';
        $this->title          = __( 'Delivium - Order Delivered', 'delivium' );
        $this->description    = __( 'Delivered emails are sent to chosen recipient(s) when order status is changed to delivered.', 'delivium' );
        $this->template_html  = 'emails/delivium-delivered-admin.php';
        $this->template_plain = 'emails/plain/delivium-delivered-admin.php';
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
        return __( 'Your {site_title} order has been delivered!', 'delivium' );
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
     */
    public function trigger( $order_id, $order = false ) {
        $this->setup_locale();

        if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
            $order = wc_get_order( $order_id );
        }

        if ( is_a( $order, 'WC_Order' ) ) {
            $this->object                         = $order;
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