<?php

$email = isset( $_POST['eddnl_email'] ) ? $_POST['eddnl_email'] : '';

// See if valid email
if ( ! empty( $email ) && is_email( $email ) ) {
    global $wpdb;

    $email_exists = (int) $wpdb->get_var(
        $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}edd_customers WHERE email = %s", $email )
    );

    if ( $email_exists ) {
        $token = EDDNL()->generate_token();
        EDDNL()->set_token( $token, $email );

        $subject = 'Your access token';
        $message = "Your access token: " . $token;
        wp_mail( $email, $subject, $message );
    }
}

?>

<h3><?php _e( 'Access Account', 'eddnl' ); ?></h3>

<?php if ( empty( $email ) ) : ?>

<div class="eddnl-form">
    <form method="post" action="">
        <input type="email" name="eddnl_email" value="" placeholder="Your purchase email" />
        <input type="submit" class="eddnl-submit" value="Email me the access token" />
    </form>
</div>

<?php else : ?>

<div class="eddnl-confirm">
    <?php _e( 'Thank you! Your access token has been sent.' ); ?>
    <form method="get" action="">
        <input type="text" name="edd_nl" value="" placeholder="Access token" />
        <input type="submit" class="eddnl-submit" value="Login" />
    </form>
</div>

<?php endif; ?>