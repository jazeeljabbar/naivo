<?php
/**
 * The file for concrete implementation of Auth Check Decorator class.
 *
 * @link       https://shipsy.io/
 * @since      1.0.3
 *
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/admin/crons/decorators
 */

namespace shipsy\cron\decorator\auth_check;

use shipsy\cron\decorator\Job_Decorator;

if ( ! defined( 'ABSPATH' ) ) exit;

require_once SHIPSY_ECONNECT_PATH . 'admin/crons/decorators/class-job-decorator.php';

/**
 * A concrete decorator class to check if the required credentials are available or not.
 * The decorator is run before running the job / nested decorator.
 */
class Auth_Check_Job_Decorator extends Job_Decorator {
	/**
	 * The execute function prepends the functionality to check presence of auth details.
	 *
	 * @return mixed|null
	 */
	public function execute() {
		require SHIPSY_ECONNECT_PATH . 'config/settings.php';

		$access_token = shipsy_get_setting( 'access_token' );
		$org_id       = shipsy_get_setting( 'org_id' );
		$cust_code    = shipsy_get_setting( 'cust_code' );

		if ( is_null( $access_token ) || is_null( $org_id ) || is_null( $cust_code ) ) {
			return null;
		}
		return parent::execute();
	}
}
