<?php
/**
 * The password functionality of the plugin.
 *
 * @link       https://delivium.top
 * @since      1.0.0
 *
 * @package    Delivium
 * @subpackage Delivium/includes
 */

/**
 * The password functionality of the plugin.
 *
 * Defines the plugin name, version, and password-related functionality.
 *
 * @package    Delivium
 * @subpackage Delivium/includes
 * @author     Delivium Team <support@delivium.top>
 */
class DELIVIUM_Password {

    /**
     * Generate a password reset key.
     *
     * @param int $user_id User ID.
     * @return string
     */
    public function generate_reset_key($user_id) {
        $key = wp_generate_password(20, false);
        update_user_meta($user_id, 'delivium_reset_key', $key);
        update_user_meta($user_id, 'delivium_reset_time', time());
        return $key;
    }

    /**
     * Validate a password reset key.
     *
     * @param int    $user_id User ID.
     * @param string $key Reset key.
     * @return bool
     */
    public function validate_reset_key($user_id, $key) {
        $stored_key = get_user_meta($user_id, 'delivium_reset_key', true);
        $reset_time = get_user_meta($user_id, 'delivium_reset_time', true);

        // Key expires after 24 hours
        if (time() - $reset_time > 86400) {
            return false;
        }

        return $stored_key === $key;
    }

    /**
     * Reset a user's password.
     *
     * @param int    $user_id User ID.
     * @param string $new_password New password.
     * @return bool
     */
    public function reset_password($user_id, $new_password) {
        wp_set_password($new_password, $user_id);
        delete_user_meta($user_id, 'delivium_reset_key');
        delete_user_meta($user_id, 'delivium_reset_time');
        return true;
    }

    /**
     * Send password reset email.
     *
     * @param string $user_login User login or email.
     * @return bool
     */
    public function send_reset_email($user_login) {
        $user = get_user_by('email', $user_login);
        if (!$user) {
            $user = get_user_by('login', $user_login);
        }

        if (!$user) {
            return false;
        }

        $reset_key = $this->generate_reset_key($user->ID);
        $reset_url = add_query_arg(array(
            'delivium_reset_key' => $reset_key,
            'delivium_reset_login' => rawurlencode($user->user_login)
        ), home_url());

        $message = sprintf(
            __('Someone has requested a password reset for the following account: %s', 'delivium'),
            $user->user_email
        );
        $message .= "\r\n\r\n";
        $message .= __('If this was a mistake, just ignore this email and nothing will happen.', 'delivium');
        $message .= "\r\n\r\n";
        $message .= __('To reset your password, visit the following address:', 'delivium');
        $message .= "\r\n\r\n";
        $message .= $reset_url;

        return wp_mail(
            $user->user_email,
            __('Password Reset Request', 'delivium'),
            $message
        );
    }

    // ... rest of the class implementation ...
} 