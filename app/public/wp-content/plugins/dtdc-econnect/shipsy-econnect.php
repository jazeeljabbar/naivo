<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://shipsy.io/
 * @since             1.0.0
 * @package           Shipsy_Econnect
 *
 * @wordpress-plugin
 * Plugin Name:       DTDC Econnect
 * Plugin URI:        https://shipsy-public-assets.s3-us-west-2.amazonaws.com/plugins/woocommerce/dtdc-econnect.zip
 * Description:       DTDC provides a seamless fulfillment service to its customers and DTDC EConnect aims to make the fulfillment process all the more easier and efficient for DTDC Customers with Wordpress Stores.
 * Version:           1.0.17.7
 * Author:            shipsyplugins
 * Author URI:        https://shipsy.io/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       shipsy-econnect
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'SHIPSY_ECONNECT_VERSION', '1.0.3' );
define( 'SHIPSY_ECONNECT_URL', plugin_dir_url( __FILE__ ) );
define( 'SHIPSY_ECONNECT_PATH', plugin_dir_path( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-shipsy-econnect-activator.php
 */
function shipsy_econnect_activate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-shipsy-econnect-activator.php';
	$activator = new Shipsy_Econnect_Activator();
	$activator->activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-shipsy-econnect-deactivator.php
 */
function shipsy_econnect_deactivate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-shipsy-econnect-deactivator.php';
	$deactivator = new Shipsy_Econnect_Deactivator();
	$deactivator->deactivate();
}

register_activation_hook( __FILE__, 'shipsy_econnect_activate' );
register_deactivation_hook( __FILE__, 'shipsy_econnect_deactivate' );


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-shipsy-econnect.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function shipsy_econnect_run() {
	$plugin = new Shipsy_Econnect();
	$plugin->run();
}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
	shipsy_econnect_run();
}
