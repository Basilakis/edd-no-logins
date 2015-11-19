<?php

$show_form = true;
$email = isset( $_POST['eddnl_email'] ) ? $_POST['eddnl_email'] : '';

if ( is_email( $email ) && wp_verify_nonce( $_POST['_wpnonce'], 'eddnl' ) ) {
    $customer_id = EDDNL()->get_customer_id( $email );

    if ( $customer_id ) {
        if ( EDDNL()->can_send_email( $customer_id ) ) {
            EDDNL()->send_email( $customer_id, $email );
            $show_form = false;
        }
    }
    else {
        EDDNL()->error = __( 'That purchase email does not exist', 'eddnl' );
    }
}

?>

<style>
.eddnl-error {
    padding: 3px 12px;
    border-left: 5px solid #ed1c24;
    margin-bottom: 10px;
}
</style>

<h3><?php _e( 'Access Your Account', 'eddnl' ); ?></h3>

<?php if ( ! empty( EDDNL()->error ) ) : ?>
<div class="eddnl-error"><?php echo EDDNL()->error; ?></div>
<?php endif; ?>

<?php if ( $show_form ) : ?>

    <div class="eddnl-form">
    <form method="post" action="">
        <input type="email" name="eddnl_email" value="" placeholder="<?php _e( 'Your purchase email', 'eddnl' ); ?>" />
        <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'eddnl' ); ?>" />
        <input type="submit" class="eddnl-submit" value="<?php _e( 'Email access token', 'eddnl' ); ?>" />
    </form>
</div>

<?php else : ?>

<div class="eddnl-confirm">
    <?php _e( 'An access token has been emailed to you.', 'eddnl' ); ?>
</div>

<?php endif; ?>