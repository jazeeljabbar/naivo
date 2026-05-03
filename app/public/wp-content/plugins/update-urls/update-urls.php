<?php

/**
 * Update URLs
 *
 * Quick and Easy way to search old links and replace them with new links in WordPress
 *
 * @package   UpdateURLS
 * @author    KaizenCoders <hello@kaizencoders.com>
 * @license   GPL-2.0+
 * @link      https://wordpress.org/plugins/update-urls
 * @copyright 2023 KaizenCoders
 *
 * @wordpress-plugin
 *
 * Plugin Name:       Update URLs
 * Plugin URI:        https://wordpress.org/plugins/update-urls
 * Description:       Quick and Easy way to search old links and replace them with new links in WordPress
 * Version:           1.2.9
 * Requires PHP:      5.6
 * Tested up to:      6.6.1
 * Author:            KaizenCoders
 * Author URI:        https://kaizencoders.com
 * Text Domain:       update-urls
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses
 * Domain Path:       /languages
 *
 * @fs_premium_only /pro/
 * @fs_ignore /vendor/, /lite/dist/styles/app.css, /lite/scripts/app.js
 *
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( ! defined( 'KC_UU_PLUGIN_VERSION' ) ) {
	define( 'KC_UU_PLUGIN_VERSION', '1.2.8' );
}

if ( function_exists( 'kc_uu_fs' ) ) {
	kc_uu_fs()->set_basename( true, __FILE__ );
} else {
	// Create a helper function for easy SDK access.
	function kc_uu_fs() {
		global $kc_uu_fs;

		if ( ! isset( $kc_uu_fs ) ) {

			require_once dirname( __FILE__ ) . '/libs/fs/start.php';

			$kc_uu_fs = fs_dynamic_init( array(
				'id'             => '13148',
				'slug'           => 'update-urls',
				'type'           => 'plugin',
				'public_key'     => 'pk_e7f60f62fb5346a24d64aeb9eea5e',
				'is_premium'     => false,
				'has_addons'     => false,
				'has_paid_plans' => false,
				'menu'           => array(
					'slug'       => 'update-urls',
					'first-path' => 'tools.php?page=update-urls',
					'parent'     => array(
						'slug' => 'tools.php',
					),
					'account'    => false,
					'contact'    => true,
					'support'    => true,
				),
			) );
		}

		return $kc_uu_fs;
	}

	kc_uu_fs();

	do_action( 'kc_uu_fs_loaded' );

	if ( ! function_exists( 'kc_uu_fail_php_version_notice' ) ) {

		/**
		 * Update URLs admin notice for minimum PHP version.
		 *
		 * Warning when the site doesn't have the minimum required PHP version.
		 *
		 * @return void
		 * @since 1.0.0
		 *
		 */
		function kc_uu_fail_php_version_notice() {
			/* translators: %s: PHP version */
			$message      = sprintf( esc_html__( 'Update URLs requires PHP version %s+, plugin is currently NOT RUNNING.', 'update-urls' ), '5.6' );
			$html_message = sprintf( '<div class="error">%s</div>', wpautop( $message ) );
			echo wp_kses_post( $html_message );
		}
	}

	if ( ! version_compare( PHP_VERSION, '5.6', '>=' ) ) {

		add_action( 'admin_notices', 'kc_uu_fail_php_version_notice' );

		return;
	}

	if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
		require_once dirname( __FILE__ ) . '/vendor/autoload.php';
	}

	if ( ! defined( 'KC_UU_PLUGIN_DIR' ) ) {
		/* @const KC_UU_PLUGIN_DIR */
		define( 'KC_UU_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
	}

	if ( ! defined( 'KC_UU_PLUGIN_BASE_NAME' ) ) {
		define( 'KC_UU_PLUGIN_BASE_NAME', plugin_basename( __FILE__ ) );
	}

	if ( ! defined( 'KC_UU_PLUGIN_FILE' ) ) {
		define( 'KC_UU_PLUGIN_FILE', __FILE__ );
	}

	if ( ! defined( 'KC_UU_PLUGIN_URL' ) ) {
		define( 'KC_UU_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
	}

	if ( ! defined( 'KC_UU_PLUGIN_ASSETS_DIR_URL' ) ) {
		define( 'KC_UU_PLUGIN_ASSETS_DIR_URL', KC_UU_PLUGIN_URL . 'lite/dist/assets' );
	}

	/**
	 * The code that runs during plugin activation.
	 * This action is documented in lib/Activator.php
	 */
	\register_activation_hook( __FILE__, '\KaizenCoders\UpdateURLS\Activator::activate' );

	/**
	 * The code that runs during plugin deactivation.
	 * This action is documented in lib/Deactivator.php
	 */
	\register_deactivation_hook( __FILE__, '\KaizenCoders\UpdateURLS\Deactivator::deactivate' );


	if ( ! function_exists( 'UU' ) ) {
		/**
		 *
		 * @since 1.0.0
		 */
		function UU() {
			return \KaizenCoders\UpdateURLS\Plugin::instance();
		}
	}

	UU()->run();
}