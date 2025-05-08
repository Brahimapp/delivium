<?php
/**
 * The template for adding a new driver.
 *
 * @package    Delivium
 * @subpackage Delivium/admin/partials
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get error message if any
$error = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';
$error_messages = array(
    'required_fields' => __('Please fill in all required fields.', 'delivium'),
    'username_exists' => __('Username already exists.', 'delivium'),
    'email_exists' => __('Email address already exists.', 'delivium'),
    'create_user_failed' => __('Failed to create user. Please try again.', 'delivium')
);
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php if ($error && isset($error_messages[$error])) : ?>
        <div class="notice notice-error">
            <p><?php echo esc_html($error_messages[$error]); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="delivium-add-driver-form">
        <input type="hidden" name="action" value="add_delivium_driver">
        <?php wp_nonce_field('delivium_add_driver'); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="username"><?php _e('Username', 'delivium'); ?> <span class="description"><?php _e('(required)', 'delivium'); ?></span></label>
                </th>
                <td>
                    <input name="username" type="text" id="username" class="regular-text" required>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="email"><?php _e('Email', 'delivium'); ?> <span class="description"><?php _e('(required)', 'delivium'); ?></span></label>
                </th>
                <td>
                    <input name="email" type="email" id="email" class="regular-text" required>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="password"><?php _e('Password', 'delivium'); ?> <span class="description"><?php _e('(required)', 'delivium'); ?></span></label>
                </th>
                <td>
                    <input name="password" type="password" id="password" class="regular-text" required>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="first_name"><?php _e('First Name', 'delivium'); ?></label>
                </th>
                <td>
                    <input name="first_name" type="text" id="first_name" class="regular-text">
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="last_name"><?php _e('Last Name', 'delivium'); ?></label>
                </th>
                <td>
                    <input name="last_name" type="text" id="last_name" class="regular-text">
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="phone"><?php _e('Phone Number', 'delivium'); ?></label>
                </th>
                <td>
                    <input name="phone" type="tel" id="phone" class="regular-text">
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Add New Driver', 'delivium'); ?>">
            <a href="<?php echo esc_url(admin_url('admin.php?page=delivium-drivers')); ?>" class="button"><?php _e('Cancel', 'delivium'); ?></a>
        </p>
    </form>
</div>

<style>
.delivium-add-driver-form {
    max-width: 600px;
    margin-top: 20px;
}

.delivium-add-driver-form .form-table th {
    width: 200px;
}

.delivium-add-driver-form .regular-text {
    width: 100%;
    max-width: 400px;
}

.delivium-add-driver-form .submit {
    margin-top: 20px;
}

.delivium-add-driver-form .description {
    color: #666;
    font-style: italic;
}
</style> 