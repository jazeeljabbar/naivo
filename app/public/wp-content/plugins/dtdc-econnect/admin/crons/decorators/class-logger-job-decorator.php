<?php
/**
 * The file for concrete implementation of Logger Decorator class.
 *
 * @link       https://shipsy.io/
 * @since      1.0.3
 *
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/admin/crons/decorators
 */

namespace shipsy\cron\decorator\logger;

use shipsy\cron\decorator\Job_Decorator;

if ( ! defined( 'ABSPATH' ) ) exit;

require_once SHIPSY_ECONNECT_PATH . 'admin/crons/decorators/class-job-decorator.php';

/**
 * A concrete decorator class to do some logging for development purpose only.
 * The decorator is run before & after running the job / nested decorator.
 */
class Logger_Job_Decorator extends Job_Decorator {
	/**
	 * The execute function prepends the functionality to check presence of auth details.
	 *
	 * @return mixed|null
	 */
	public function execute() {
		error_log('Syncing orders...'); // phpcs:ignore
		$result = parent::execute();
		error_log('Synced orders...');  // phpcs:ignore
		return $result;
	}
}
