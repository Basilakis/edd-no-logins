<?php

class EDDNL_Upgrade
{
    function __construct() {
        $this->version = EDDNL_VERSION;
        $this->last_version = get_option( 'eddnl_version' );

        if ( version_compare( $this->last_version, $this->version, '<' ) ) {
            if ( version_compare( $this->last_version, '0.1', '<' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $this->clean_install();
            }
            else {
                $this->run_upgrade();
            }

            update_option( 'eddnl_version', $this->version );
        }
    }


    private function clean_install() {
        global $wpdb;

        $sql = "
        CREATE TABLE {$wpdb->prefix}eddnl_tokens (
            id BIGINT unsigned not null auto_increment,
            customer_id BIGINT unsigned default '0',
            email VARCHAR(255),
            token VARCHAR(255),
            verify_key VARCHAR(255),
            PRIMARY KEY (id)
        ) DEFAULT CHARSET=utf8";
        dbDelta( $sql );
    }


    private function run_upgrade() {
    }
}

new EDDNL_Upgrade();
