<?php

$show_form = true;
$email = isset( $_POST['eddnl_email'] ) ? $_POST['eddnl_email'] : '';

// Get the purchase history URL
$page_id = edd_get_option( 'purchase_history_page' );
$page_url = get_permalink( $page_id );

// Form submission
if ( is_email( $email ) && wp_verify_nonce( $_POST['_wpnonce'], 'eddnl' ) ) {

    // Use reCAPTCHA
    if ( defined( 'RECAPTCHA_KEY' ) ) {

        $args = array(
            'secret'    => RECAPTCHA_SECRET,
            'response'  => $_POST['g-recaptcha-response'],
            'remoteip'  => $_POST['eddnl_ip']
        );

        if ( ! empty( $args['response'] ) ) {
            $request = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', array( 'body' => $args ) );
            if ( ! is_wp_error( $request ) || 200 == wp_remote_retrieve_response_code( $request ) ) {
                $response = json_decode( $request['body'], true );

                // reCAPTCHA fail
                if ( ! $response['success'] ) {
                    EDDNL()->error = __( 'reCAPTCHA test failed', 'eddnl' );
                }
            }
            else {
                EDDNL()->error = __( 'Unable to connect to reCAPTCHA server', 'eddnl' );
            }
        }
        // reCAPTCHA empty
        else {
            EDDNL()->error = __( 'reCAPTCHA test failed', 'eddnl' );
        }
    }

    if ( empty( EDDNL()->error ) ) {
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
}

?>

<style>
.eddnl-error {
    padding: 3px 12px;
    border-left: 5px solid #ed1c24;
    margin-bottom: 10px;
}

.g-recaptcha {
    padding: 10px 0;
}
</style>

<script>
(function($) {
    $(function() {
        $.getJSON("https://api.ipify.org?format=jsonp&callback=?", function(json) {
            $('.eddnl_ip').val(json.ip);
        });
    });
})(jQuery);
</script>

<h3><?php _e( 'Access Your Account', 'eddnl' ); ?></h3>

<?php if ( ! empty( EDDNL()->error ) ) : ?>
<div class="eddnl-error"><?php echo EDDNL()->error; ?></div>
<?php endif; ?>

<?php if ( $show_form ) : ?>

    <script src='https://www.google.com/recaptcha/api.js'></script>
    <div class="eddnl-form">
    <form method="post" action="<?php echo $page_url; ?>">
        <input type="email" name="eddnl_email" value="" placeholder="<?php _e( 'Your purchase email', 'eddnl' ); ?>" />
        <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'eddnl' ); ?>" />

        <?php if ( defined( 'RECAPTCHA_KEY' ) ) : ?>
        <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_KEY; ?>"></div>
        <input type="hidden" name="eddnl_ip" class="eddnl_ip" value="" />
        <?php endif; ?>

        <input type="submit" class="eddnl-submit" value="<?php _e( 'Email access token', 'eddnl' ); ?>" />
    </form>
</div>

<?php else : ?>

<div class="eddnl-confirm">
    <?php _e( 'An access token has been emailed to you.', 'eddnl' ); ?>
</div>

<?php endif; ?>