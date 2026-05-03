<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://kaizencoders.com
 * @since      1.2
 *
 * @package    UpdateURLS
 * @subpackage UpdateURLS/includes
 */

namespace KaizenCoders\UpdateURLS;

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.2
 * @package    UpdateURLS
 * @subpackage UpdateURLS/includes
 * @author     KaizenCoders <hello@kaizencoders.com>
 */
class Deactivator {

	/**
     * @param $network_wide
     *
     * @since 1.0.0
     */
    public static function deactivate( $network_wide ) {

        if ( is_multisite() && $network_wide ) {

            global $wpdb;

            $blog_ids = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM $wpdb->blogs WHERE deleted = %d", 0 ) );
            foreach ( $blog_ids as $blog_id ) {
                self::deactivate_on_blog( $blog_id );
            }

        } else {
            self::do_deactivation();
        }

    }

    /**
     * @param $blog_id
     *
     * @since 1.0.0
     */
    public static function deactivate_on_blog( $blog_id ) {
        switch_to_blog( $blog_id );
        self::do_deactivation();
        restore_current_blog();
    }

    public static function do_deactivation() {
        /**
         * Cleanup all plugin related code
         */
    }

}
