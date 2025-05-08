<?php

/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       https://delivium.top
 * @since      1.0.0
 *
 * @package    Delivium
 * @subpackage Delivium/public/partials
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="delivium-container">
    <?php if ( isset( $delivery_status ) ) : ?>
        <div class="delivium-tracking-section">
            <h2><?php esc_html_e( 'Delivery Status', 'delivium' ); ?></h2>
            <div class="delivium-delivery-status">
                <?php echo esc_html( $delivery_status ); ?>
            </div>
            <?php if ( isset( $estimated_time ) ) : ?>
                <div class="delivium-estimated-time">
                    <?php 
                    printf(
                        /* translators: %s: estimated delivery time */
                        esc_html__( 'Estimated delivery time: %s', 'delivium' ),
                        esc_html( $estimated_time )
                    );
                    ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ( isset( $show_map ) && $show_map ) : ?>
            <div id="delivium-tracking-map" 
                 data-lat="<?php echo esc_attr( $delivery_lat ); ?>"
                 data-lng="<?php echo esc_attr( $delivery_lng ); ?>">
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ( isset( $show_rating_form ) && $show_rating_form ) : ?>
        <div class="delivium-rating-section">
            <h3><?php esc_html_e( 'Rate Your Delivery', 'delivium' ); ?></h3>
            <form class="delivium-rating-form" method="post">
                <div class="form-group">
                    <label for="delivium-rating">
                        <?php esc_html_e( 'Rating', 'delivium' ); ?>
                    </label>
                    <select name="rating" id="delivium-rating" required>
                        <option value=""><?php esc_html_e( 'Select Rating', 'delivium' ); ?></option>
                        <?php for ( $i = 5; $i >= 1; $i-- ) : ?>
                            <option value="<?php echo esc_attr( $i ); ?>">
                                <?php echo esc_html( $i . ' ' . _n( 'Star', 'Stars', $i, 'delivium' ) ); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="delivium-feedback">
                        <?php esc_html_e( 'Feedback', 'delivium' ); ?>
                    </label>
                    <textarea name="feedback" id="delivium-feedback" rows="4"></textarea>
                </div>
                <div class="delivium-message" style="display: none;"></div>
                <button type="submit" class="delivium-submit-rating">
                    <?php esc_html_e( 'Submit Rating', 'delivium' ); ?>
                </button>
                <?php wp_nonce_field( 'delivium_rating_nonce', 'delivium_rating_nonce' ); ?>
            </form>
        </div>
    <?php endif; ?>

    <?php if ( isset( $show_address_form ) && $show_address_form ) : ?>
        <div class="delivium-address-section">
            <h3><?php esc_html_e( 'Delivery Address', 'delivium' ); ?></h3>
            <form class="delivium-address-form" method="post">
                <div class="form-group">
                    <label for="delivium-address">
                        <?php esc_html_e( 'Address', 'delivium' ); ?>
                    </label>
                    <input type="text" 
                           name="address" 
                           id="delivium-address" 
                           class="delivium-address-field" 
                           required 
                           placeholder="<?php esc_attr_e( 'Enter your delivery address', 'delivium' ); ?>"
                    >
                    <input type="hidden" name="lat" class="delivium-lat">
                    <input type="hidden" name="lng" class="delivium-lng">
                </div>
                <div class="form-group">
                    <label for="delivium-instructions">
                        <?php esc_html_e( 'Delivery Instructions', 'delivium' ); ?>
                    </label>
                    <textarea name="instructions" 
                              id="delivium-instructions" 
                              rows="3"
                              placeholder="<?php esc_attr_e( 'Any special instructions for the delivery?', 'delivium' ); ?>"
                    ></textarea>
                </div>
                <div class="delivium-message" style="display: none;"></div>
                <button type="submit" class="delivium-submit-address">
                    <?php esc_html_e( 'Save Address', 'delivium' ); ?>
                </button>
                <?php wp_nonce_field( 'delivium_address_nonce', 'delivium_address_nonce' ); ?>
            </form>
        </div>
    <?php endif; ?>
</div> 