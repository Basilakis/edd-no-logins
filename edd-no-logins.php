<?php
/*
Plugin Name: EDD - No Logins
Plugin URI: https://facetwp.com/
Description: Allow users to access their purchase information without logging in
Version: 0.1
Author: Matt Gibbs

Copyright 2015 Matt Gibbs

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, see <http://www.gnu.org/licenses/>.
*/

defined( 'ABSPATH' ) or exit;

class EDD_No_Logins
{

    public $token_exists = false;
    public $token_email = false;
    private static $instance;


    function __construct() {

        // setup variables
        define( 'EDDNL_VERSION', '0.1' );
        define( 'EDDNL_DIR', dirname( __FILE__ ) );
        define( 'EDDNL_URL', plugins_url( basename( EDDNL_DIR ) ) );
        define( 'EDDNL_BASENAME', plugin_basename( __FILE__ ) );

        // get the gears turning
        add_action( 'init', array( $this, 'init' ) );
    }


    function init() {
        if ( is_user_logged_in() ) {
            return;
        }

        add_action( 'get_template_part_history', array( $this, 'login' ) );
        add_filter( 'edd_user_pending_verification', array( $this, 'override_pending' ) );
        add_filter( 'edd_get_users_purchases_args', array( $this, 'users_purchases_args' ) );
        add_filter( 'edd_can_view_receipt', '__return_true' );

        $this->check_for_token();
    }


    function login() {
        if ( ! $this->token_exists ) {
            include( EDDNL_DIR . '/templates/login-form.php' );
        }
    }


    /**
     * See if "edd_nl" URL variable exists
     */
    function check_for_token() {
        if ( isset( $_GET['edd_nl'] ) ) {

            // Not a valid token
            if ( ! $this->is_valid_token( $_GET['edd_nl'] ) ) {
                return;
            }

            $this->token_exists = true;

            // Simulate a user login
            $user = get_user_by( 'login', 'edd_nl' );

            if ( $user ) {
                $user_id = $user->ID;
            }
            else {
                $user_id = wp_create_user( 'edd_nl', wp_generate_password(), 'eddnl@facetwp.com' );
                update_user_meta( $user_id, 'show_admin_bar_front', false );
                update_user_meta( $user_id, 'wp_capabilities', '' );
                update_user_meta( $user_id, 'wp_user_level', 0 );
            }

            wp_set_current_user( $user_id );
        }
    }


    /**
     * Generate a new token
     */
    function generate_token() {
        $bucket = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr( str_shuffle( $bucket ), 0, 12 );
    }


    /**
     * Get tokens
     */
    function get_tokens() {
        $tokens = get_option( 'eddnl_tokens' );
        return empty ( $tokens ) ? array() : json_decode( $tokens, true );
    }


    /**
     * Set a token value
     */
    function set_token( $token, $email ) {
        $tokens = $this->get_tokens();
        $tokens[ $email ] = $token;

        // Set option
        update_option( 'eddnl_tokens', json_encode( $tokens ) );
    }


    /**
     * Validate token
     */
    function is_valid_token( $token ) {
        $this->token_email = array_search( $token, $this->get_tokens() );
        return ( false !== $this->token_email );
    }


    /**
     * Bypass edd_user_pending_verification() when using shortcodes
     *
     * [purchase_history]
     * [download_history]
     */
    function override_pending( $pending ) {
        if ( $this->token_exists ) {
            $pending = false;
        }
        return $pending;
    }


    /**
     * Get purchases by email instead of user ID
     */
    function users_purchases_args( $args ) {
        $args['user'] = $this->token_email;
        return $args;
    }


    /**
     * Singleton
     */
    public static function instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self;
        }
        return self::$instance;
    }
}


function EDDNL() {
    return EDD_No_Logins::instance();
}


EDDNL();
