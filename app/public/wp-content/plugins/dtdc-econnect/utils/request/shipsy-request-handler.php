<?php
/**
 * The file to handle request object creation and handling.
 *
 * @link       https://shipsy.io/
 * @since      1.0.3
 *
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/admin/crons
 */

use shipsy\request\Request;
use shipsy\request\IRequest;

if ( ! defined( 'ABSPATH' ) ) exit;

require_once SHIPSY_ECONNECT_PATH . 'utils/request/request/class-request.php';
require_once SHIPSY_ECONNECT_PATH . 'utils/request/request/interface-request.php';
require_once SHIPSY_ECONNECT_PATH . 'utils/request/decorator/class-shipsy-request-log-decorator.php';


/**
 * The function to create request.
 *
 * @return IRequest
 */
function shipsy_get_request_handler(): IRequest {
	return new Shipsy_Request_Log_Decorator( new Request() );
}
