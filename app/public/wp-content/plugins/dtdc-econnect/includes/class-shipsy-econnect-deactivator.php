<?php
/**
 * Fired during plugin deactivation
 *
 * @link       https://shipsy.io/
 * @since      1.0.0
 *
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/includes
 * @author     shipsyplugins <pradeep.mishra@shipsy.co.in>
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Shipsy_Econnect_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	// phpcs:disable
	// private $table_activator;
	 public function __construct(){
//	    $this->table_activator = $activator;

	 }

	// public function deactivate() {
	// global $wpdb;
	// dropping tables on  plugin uninstall
	// $wpdb->query("DROP TABLE IF EXISTS ". $this->table_activator->wp_sync_track_order());
	// }
	// phpcs:enable

	/**
	 * Function to run when deactivating.
	 *
	 * @return void
	 */
	public function deactivate() {
		$this->remove_cron_jobs();
	}

	/**
	 * Remove scheduled cron jobs.
	 *
	 * @return void
	 */
	public function remove_cron_jobs() {
		require_once SHIPSY_ECONNECT_PATH . 'admin/crons/shipsy-cron-handler.php';
		shipsy_teardown_cron();
	}
}
