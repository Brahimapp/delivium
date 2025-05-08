<?php
/**
 * Admin branding page template
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

// Check if premium is active
if (!defined('DELIVIUM_PREMIUM') || !DELIVIUM_PREMIUM) {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <div class="notice notice-error">
            <p><?php _e('Branding customization is a premium feature. Please upgrade to access these settings.', 'delivium'); ?></p>
        </div>
    </div>
    <?php
    return;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="delivium-branding-page">
        <form method="post" action="options.php" enctype="multipart/form-data">
            <?php
            settings_fields('delivium_branding_settings');
            do_settings_sections('delivium_branding_settings');
            ?>
            
            <!-- Color Scheme -->
            <h2><?php _e('Color Scheme', 'delivium'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Primary Color', 'delivium'); ?></th>
                    <td>
                        <input type="text" name="delivium_primary_color" 
                            value="<?php echo esc_attr(get_option('delivium_primary_color', '#0073aa')); ?>" 
                            class="delivium-color-picker">
                        <p class="description"><?php _e('Main color for buttons and highlights', 'delivium'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Secondary Color', 'delivium'); ?></th>
                    <td>
                        <input type="text" name="delivium_secondary_color" 
                            value="<?php echo esc_attr(get_option('delivium_secondary_color', '#23282d')); ?>" 
                            class="delivium-color-picker">
                        <p class="description"><?php _e('Color for secondary elements and accents', 'delivium'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Text Color', 'delivium'); ?></th>
                    <td>
                        <input type="text" name="delivium_text_color" 
                            value="<?php echo esc_attr(get_option('delivium_text_color', '#444444')); ?>" 
                            class="delivium-color-picker">
                        <p class="description"><?php _e('Main text color throughout the interface', 'delivium'); ?></p>
                    </td>
                </tr>
            </table>

            <!-- Logo Settings -->
            <h2><?php _e('Logo & Branding', 'delivium'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Company Logo', 'delivium'); ?></th>
                    <td>
                        <?php
                        $logo_url = get_option('delivium_company_logo');
                        if ($logo_url) {
                            echo '<div class="delivium-logo-preview">';
                            echo '<img src="' . esc_url($logo_url) . '" alt="Company Logo">';
                            echo '</div>';
                        }
                        ?>
                        <input type="file" name="delivium_company_logo" accept="image/*">
                        <p class="description"><?php _e('Recommended size: 300x100px', 'delivium'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Favicon', 'delivium'); ?></th>
                    <td>
                        <?php
                        $favicon_url = get_option('delivium_favicon');
                        if ($favicon_url) {
                            echo '<div class="delivium-favicon-preview">';
                            echo '<img src="' . esc_url($favicon_url) . '" alt="Favicon">';
                            echo '</div>';
                        }
                        ?>
                        <input type="file" name="delivium_favicon" accept="image/x-icon,image/png">
                        <p class="description"><?php _e('Recommended size: 32x32px', 'delivium'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Company Name', 'delivium'); ?></th>
                    <td>
                        <input type="text" name="delivium_company_name" class="regular-text" 
                            value="<?php echo esc_attr(get_option('delivium_company_name')); ?>">
                        <p class="description"><?php _e('Used in emails and customer communications', 'delivium'); ?></p>
                    </td>
                </tr>
            </table>

            <!-- Custom Text -->
            <h2><?php _e('Custom Text', 'delivium'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Email Footer Text', 'delivium'); ?></th>
                    <td>
                        <textarea name="delivium_email_footer" rows="3" class="large-text"><?php 
                            echo esc_textarea(get_option('delivium_email_footer')); 
                        ?></textarea>
                        <p class="description"><?php _e('Custom text to appear at the bottom of all delivery emails', 'delivium'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('SMS Signature', 'delivium'); ?></th>
                    <td>
                        <input type="text" name="delivium_sms_signature" class="regular-text" 
                            value="<?php echo esc_attr(get_option('delivium_sms_signature')); ?>">
                        <p class="description"><?php _e('Added to the end of SMS notifications', 'delivium'); ?></p>
                    </td>
                </tr>
            </table>

            <!-- Driver App Branding -->
            <h2><?php _e('Driver App Branding', 'delivium'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('App Theme', 'delivium'); ?></th>
                    <td>
                        <select name="delivium_app_theme">
                            <option value="light" <?php selected(get_option('delivium_app_theme'), 'light'); ?>>
                                <?php _e('Light Theme', 'delivium'); ?>
                            </option>
                            <option value="dark" <?php selected(get_option('delivium_app_theme'), 'dark'); ?>>
                                <?php _e('Dark Theme', 'delivium'); ?>
                            </option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Custom CSS', 'delivium'); ?></th>
                    <td>
                        <textarea name="delivium_custom_css" rows="5" class="large-text code"><?php 
                            echo esc_textarea(get_option('delivium_custom_css')); 
                        ?></textarea>
                        <p class="description"><?php _e('Additional CSS for fine-tuning the driver app interface', 'delivium'); ?></p>
                    </td>
                </tr>
            </table>

            <?php submit_button(__('Save Branding Settings', 'delivium')); ?>
        </form>

        <!-- Preview Section -->
        <div class="delivium-branding-preview">
            <h2><?php _e('Live Preview', 'delivium'); ?></h2>
            <div class="delivium-preview-container">
                <div id="delivium-preview-header">
                    <div class="preview-logo"></div>
                    <div class="preview-nav"></div>
                </div>
                <div id="delivium-preview-content">
                    <div class="preview-button"></div>
                    <div class="preview-text"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // Initialize color pickers
        $('.delivium-color-picker').wpColorPicker({
            change: function(event, ui) {
                updatePreview();
            }
        });

        // Live preview updates
        function updatePreview() {
            var primaryColor = $('input[name="delivium_primary_color"]').val();
            var secondaryColor = $('input[name="delivium_secondary_color"]').val();
            var textColor = $('input[name="delivium_text_color"]').val();

            $('.preview-button').css('background-color', primaryColor);
            $('.preview-nav').css('background-color', secondaryColor);
            $('.preview-text').css('color', textColor);
        }

        // Initial preview
        updatePreview();
    });
    </script>
</div> 