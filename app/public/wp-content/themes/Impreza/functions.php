<?php
/**
 * Theme functions and definitions
 */

defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );
update_option( 'us_license_activated', 1 );
update_option( 'us_license_secret', 'us_license_secret' );

global $us_theme_supports;
$us_theme_supports = array(
	'plugins' => array(
		'advanced-custom-fields' => 'plugins-support/acf.php',
		'bbpress' => 'plugins-support/bbpress.php',
		'contact-form-7' => NULL,
		'filebird' => 'plugins-support/filebird.php',
		'gravityforms' => 'plugins-support/gravityforms.php',
		'js_composer' => 'plugins-support/js_composer/js_composer.php',
		'post_views_counter' => 'plugins-support/post_views_counter.php',
		'revslider' => 'plugins-support/revslider.php',
		'tablepress' => 'plugins-support/tablepress.php',
		'the-events-calendar' => 'plugins-support/the_events_calendar.php',
		'tiny_mce' => 'plugins-support/tiny_mce.php',
		'Ultimate_VC_Addons' => 'plugins-support/Ultimate_VC_Addons.php',
		'woocommerce' => 'plugins-support/woocommerce.php',
		'woocommerce-germanized' => 'plugins-support/woocommerce-germanized.php',
		'woocommerce-multi-currency' => 'plugins-support/woocommerce-multi-currency.php',
		'wp_rocket' => 'plugins-support/wp_rocket.php',
		'yoast' => 'plugins-support/yoast.php',
	),
	// Include plugins that relate to translations and can be used in helpers.php
	'translate_plugins' => array(
		'wpml' => 'plugins-support/wpml.php',
		'polylang' => 'plugins-support/polylang.php',
	),
);

require dirname( __FILE__ ) . '/common/framework.php';
