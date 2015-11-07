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
    public $token = false;
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

        // Setup the DB table
        include( EDDNL_DIR . '/includes/class-upgrade.php' );

        $this->check_for_token();

        if ( $this->token_exists ) {
            add_filter( 'edd_can_view_receipt', '__return_true' );
            add_filter( 'edd_user_pending_verification', '__return_false' );
            add_filter( 'edd_get_success_page_uri', array( $this, 'edd_success_page_uri' ) );
            add_filter( 'edd_get_users_purchases_args', array( $this, 'users_purchases_args' ) );
        }
        else {
            add_action( 'get_template_part_history', array( $this, 'login' ), 10, 2 );
        }
    }


    /**
     * See if "eddnl" URL variable exists
     */
    function check_for_token() {
        $token = isset( $_GET['eddnl'] ) ? $_GET['eddnl'] : '';

        if ( ! empty( $token ) ) {

            // Not a valid token
            if ( ! $this->is_valid_token( $token ) ) {

                // Resetting the token (incorrect verification)
                if ( ! $this->is_valid_verify_key( $token ) ) {
                    return;
                }
            }

            $this->token_exists = true;

            // Simulate a user login
            $user = get_user_by( 'login', 'eddnl' );

            if ( $user ) {
                $user_id = $user->ID;
            }
            else {
                $user_id = wp_create_user( 'eddnl', wp_generate_password( 20 ), 'eddnl@facetwp.com' );
                update_user_meta( $user_id, 'show_admin_bar_front', false );
                update_user_meta( $user_id, 'wp_capabilities', '' );
                update_user_meta( $user_id, 'wp_user_level', 0 );
            }

            wp_set_current_user( $user_id );
        }
    }


    /**
     * Append token to "View Details and Downloads" links
     */
    function edd_success_page_uri( $uri ) {
        if ( $this->token_exists ) {
            return add_query_arg( array( 'eddnl' => $this->token ), $uri );
        }
    }


    /**
     * Validate token
     */
    function is_valid_token( $token ) {
        global $wpdb;

        $email = $wpdb->get_var(
            $wpdb->prepare( "SELECT email FROM {$wpdb->prefix}eddnl_tokens WHERE token = %s LIMIT 1", $token )
        );

        if ( ! empty( $email ) ) {
            $this->token_email = $email;
            $this->token = $token;
            return true;
        }

        return false;
    }


    /**
     * Determine whether to reset the token
     */
    function is_valid_verify_key( $token ) {
        global $wpdb;

        // See if the verify_key exists
        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT id, email FROM {$wpdb->prefix}eddnl_tokens WHERE verify_key = %s LIMIT 1", $token )
        );

        // Set token
        if ( ! empty( $row ) ) {
            $wpdb->query(
                $wpdb->prepare( "UPDATE {$wpdb->prefix}eddnl_tokens SET verify_key = '', token = %s WHERE id = %d LIMIT 1", $token, $row->id )
            );

            $this->token_email = $row->email;
            $this->token = $token;
            return true;
        }

        return false;
    }


    /**
     * Only reset the token after email verification
     */
    function set_verify_key( $customer_id, $email, $verify_key ) {
        global $wpdb;

        // Insert or update?
        $row_id = (int) $wpdb->get_var(
            $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}eddnl_tokens WHERE customer_id = %d LIMIT 1", $customer_id )
        );

        // Update
        if ( ! empty( $row_id ) ) {
            $wpdb->query(
                $wpdb->prepare( "UPDATE {$wpdb->prefix}eddnl_tokens SET verify_key = %s WHERE id = %d LIMIT 1", $verify_key, $row_id )
            );
        }
        // Insert
        else {
            $wpdb->query(
                $wpdb->prepare( "INSERT INTO {$wpdb->prefix}eddnl_tokens (customer_id, email, verify_key) VALUES (%d, %s, %s)", $customer_id, $email, $verify_key )
            );
        }
    }


    /**
     * Token request form
     */
    function login( $slug = 'history', $name = 'purchases' ) {
        include( EDDNL_DIR . '/templates/login-form.php' );
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
