<?php
/**
 * The file for Job interface.
 *
 * @link       https://shipsy.io/
 * @since      1.0.3
 *
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/admin/crons/job
 */

namespace shipsy\cron\job;

/**
 * Interface that will be used by all of Shipsy's Jobs.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

interface Job {
	/**
	 * All jobs that extend this interface will perform certain task, which will be achieved via this function.
	 *
	 * @return mixed
	 */
	public function execute();
}
