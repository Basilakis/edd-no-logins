<?php

$email = isset( $_POST['eddnl_email'] ) ? $_POST['eddnl_email'] : '';

// See if valid email
if ( ! empty( $email ) && is_email( $email ) ) {
    global $wpdb;

    $customer_id = (int) $wpdb->get_var(
        $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}edd_customers WHERE email = %s", $email )
    );

    if ( $customer_id ) {

        // Generate the verification key
        $verify_key = wp_generate_password( 12, false );

        EDDNL()->set_verify_key( $customer_id, $email, $verify_key );

        // Get the purchase history URL
        $page_id = edd_get_option( 'purchase_history_page' );
        $page_url = get_permalink( $page_id );

        // Send the email
        $subject = __( 'Your access token', 'eddnl' );
        $message = "$page_url?eddnl=$verify_key";
        wp_mail( $email, $subject, $message );
    }
}

?>

<h3><?php _e( 'Access Account', 'eddnl' ); ?></h3>

<?php if ( empty( $email ) ) : ?>

<div class="eddnl-form">
    <form method="post" action="">
        <input type="email" name="eddnl_email" value="" placeholder="<?php _e( 'Your purchase email', 'eddnl' ); ?>" />
        <input type="submit" class="eddnl-submit" value="<?php _e( 'Email me the access token', 'eddnl' ); ?>" />
    </form>
</div>

<?php else : ?>

<div class="eddnl-confirm">
    <?php _e( 'Your access token has been emailed to you.', 'eddnl' ); ?>
</div>

<?php endif; ?>