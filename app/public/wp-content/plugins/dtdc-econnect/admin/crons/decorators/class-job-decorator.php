<?php
/**
 * The file for abstract Job Decorator class.
 *
 * @link       https://shipsy.io/
 * @since      1.0.3
 *
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/admin/crons/decorators
 */

namespace shipsy\cron\decorator;

use shipsy\cron\job\Job;

if ( ! defined( 'ABSPATH' ) ) exit;

require_once SHIPSY_ECONNECT_PATH . 'admin/crons/jobs/interface-job.php';

/**
 * An abstract decorator class, all the job decorators will extend this class.
 */
class Job_Decorator implements Job {
	/**
	 * The job to be executed is injected when creating concrete decorator instance.
	 *
	 * @var Job
	 */
	protected Job $job;

	/**
	 * Constructor of JobDecorator class.
	 *
	 * @param Job $job Concrete job that is to be run.
	 */
	public function __construct( Job $job ) {
		$this->job = $job;
	}

	/**
	 * An abstract implementation of execute that calls the execute function of job itself.
	 *
	 * @return mixed
	 */
	public function execute() {
		return $this->job->execute();
	}
}
