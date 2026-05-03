<?php
/**
 * The file for cron set up and teardown.
 *
 * @link       https://shipsy.io/
 * @since      1.0.3
 *
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/admin/crons
 */

use shipsy\cron\job\auto_sync\Shipsy_Auto_Sync_Consignment_Job;
use shipsy\cron\job\auto_consignment_status\Shipsy_Auto_Update_Consignment_Status_Job;

use shipsy\cron\decorator\auth_check\Auth_Check_Job_Decorator;
use shipsy\cron\decorator\logger\Logger_Job_Decorator;

if ( ! defined( 'ABSPATH' ) ) exit;

require_once SHIPSY_ECONNECT_PATH . 'admin/crons/decorators/class-logger-job-decorator.php';
require_once SHIPSY_ECONNECT_PATH . 'admin/crons/decorators/class-auth-check-job-decorator.php';
require_once SHIPSY_ECONNECT_PATH . 'admin/crons/jobs/class-shipsy-auto-sync-consignment-job.php';
require_once SHIPSY_ECONNECT_PATH . 'admin/crons/jobs/class-shipsy-auto-update-consignment-status-job.php';


/**
 * Function to set up cron jobs.
 *
 * @return void
 */
function shipsy_setup_cron() {
	shipsy_auto_sync_cron();
	shipsy_auto_status_update_cron();
}

/**
 * Function to schedule auto sync job.
 * Shipsy_Auto_Update_Consignment_Status_Job
 *
 * @return void
 */
function shipsy_auto_sync_cron() {
	if ( (int) shipsy_get_option( 'enable_auto_sync_option' ) ) {
		$auto_sync_job = new Shipsy_Auto_Sync_Consignment_Job();
		$auto_sync_job = new Logger_Job_Decorator( new Auth_Check_Job_Decorator( $auto_sync_job ) );
		add_action( 'shipsy_auto_sync_consignments_cron_hook', array( $auto_sync_job, 'execute' ) );

		if ( ! wp_next_scheduled( 'shipsy_auto_sync_consignments_cron_hook' ) ) {
			wp_schedule_event( time(), 'fifteen_minutes', 'shipsy_auto_sync_consignments_cron_hook' );
		}
	} else {
		wp_clear_scheduled_hook( 'shipsy_auto_sync_consignments_cron_hook' );
	}
}

/**
 * Function to schedule auto status update job.
 *
 * @return void
 */
function shipsy_auto_status_update_cron() {
	if ( (int) shipsy_get_option( 'enable_auto_status_update_option' ) ) {
		$auto_status_update_job = new Shipsy_Auto_Update_Consignment_Status_Job();
		$auto_status_update_job = new Auth_Check_Job_Decorator( $auto_status_update_job );
		add_action( 'shipsy_auto_update_consignment_status_cron_hook', array( $auto_status_update_job, 'execute' ) );

		if ( ! wp_next_scheduled( 'shipsy_auto_update_consignment_status_cron_hook' ) ) {
			wp_schedule_event( time(), 'fifteen_minutes', 'shipsy_auto_update_consignment_status_cron_hook' );
		}
	} else {
		wp_clear_scheduled_hook( 'shipsy_auto_update_consignment_status_cron_hook' );
	}
}

/**
 * Remove all recurring scheduled jobs.
 *
 * @return void
 */
function shipsy_teardown_cron() {
	wp_clear_scheduled_hook( 'shipsy_auto_sync_consignments_cron_hook' );
	wp_clear_scheduled_hook( 'shipsy_auto_update_consignment_status_cron_hook' );
}
