<?php
/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://shipsy.io/
 * @since      1.0.0
 *
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/includes
 * @author     shipsyplugins <pradeep.mishra@shipsy.co.in>
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Shipsy_Econnect_I18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'shipsy-econnect',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
