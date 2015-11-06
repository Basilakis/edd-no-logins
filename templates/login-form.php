<?php

$email = isset( $_POST['eddnl_email'] ) ? $_POST['eddnl_email'] : '';

// See if valid email
if ( ! empty( $email ) && is_email( $email ) ) {
    global $wpdb;

    $email_exists = (int) $wpdb->get_var(
        $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}edd_customers WHERE email = %s", $email )
    );

    // @TODO: get proper URLs
    // @TODO: add token auth to prevent passwords from getting reset anonymously
    // edd_settings['purchase_history_page'] = post ID, then get the permalink URL and append ?edd_nl to it
    if ( $email_exists ) {
        $token = wp_generate_password();
        EDDNL()->set_token( $token, $email );

        $subject = 'Your access token';
        $message = "Your access token: http://wp.dev/checkout/purchase-history/?edd_nl=" . $token;
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
</div>

<?php endif; ?>