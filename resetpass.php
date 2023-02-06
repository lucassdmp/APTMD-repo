<?php
/*
Template Name: Reset Password
*/

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">

        <?php
        if (!is_user_logged_in()) {

            // check if the reset password form has been submitted
            if (isset($_POST['reset-password-submit'])) {
                $errors = reset_password();

                if (is_wp_error($errors)) {
                    // display errors if any
                    echo '<p class="error">' . $errors->get_error_message() . '</p>';
                } else {
                    // display success message
                    echo '<p class="success">Password reset successful! Please check your email for the new password.</p>';
                }
            }

            // display the reset password form
        ?>
            <form id="reset-password-form" method="post">
                <p>
                    <label for="user_login">Username or Email:</label>
                    <input type="text" name="user_login" id="user_login" required>
                </p>

                <p class="submit">
                    <input type="submit" name="reset-password-submit" value="Reset Password">
                </p>
            </form>
        <?php } else {
            // display message if user is already logged in
            echo '<p>You are already logged in.</p>';
        } ?>

    </main><!-- #main -->
</div><!-- #primary -->

<?php
get_footer();

/**
 * Reset password function
 */
function reset_password()
{
    if (empty($_POST['user_login'])) {
        return new WP_Error('field', 'Please enter your username or email address.');
    } elseif (strpos($_POST['user_login'], '@')) {
        $user_data = get_user_by('email', trim($_POST['user_login']));
        if (empty($user_data)) {
            return new WP_Error('invalid_email', 'There is no user registered with that email address.');
        }
    } else {
        $login = trim($_POST['user_login']);
        $user_data = get_user_by('login', $login);
    }

    do_action('lostpassword_post');

    if (!$user_data) {
        return new WP_Error('invalidcombo', 'Invalid username or email.');
    }

    // Redefining user_login ensures we return the right case in the email.
    $user_login = $user_data->user_login;
    $user_email = $user_data->user_email;
    $key = get_password_reset_key($user_data);

    if (is_wp_error($key)) {
        return $key;
    }

    $message = __('Someone requested that the password be reset for the following account:') . "\r\n\r\n";

    $message .= network_home_url('/') . "\r\n\r\n";
    $message .= sprintf(__('Username: %s'), $user_login) . "\r\n\r\n";
    $message .= __('If this was a mistake, just ignore this email and nothing will happen.') . "\r\n\r\n";
    $message .= __('To reset your password, visit the following address:') . "\r\n\r\n";
    $message .= network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login') . "\r\n";

    $title = __('Password Reset');

    $title = apply_filters('retrieve_password_title', $title, $user_login, $user_data);
    $message = apply_filters('retrieve_password_message', $message, $key, $user_login, $user_data);

    if ($message && !wp_mail($user_email, wp_specialchars_decode($title), $message)) {
        wp_die(__('The email could not be sent.') . "<br />\n" . __('Possible reason: your host may have disabled the mail() function.'));
    }

    return true;
}
