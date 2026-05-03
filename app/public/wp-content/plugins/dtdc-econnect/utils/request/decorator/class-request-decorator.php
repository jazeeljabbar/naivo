<?php
/**
 * The file for abstract Request Decorator class.
 *
 * @link       https://shipsy.io/
 * @since      1.0.3
 *
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/admin/crons/decorators
 */

namespace shipsy\request\decorator;

use shipsy\request\IRequest;

if ( ! defined( 'ABSPATH' ) ) exit;

require_once SHIPSY_ECONNECT_PATH . 'utils/request/request/interface-request.php';

/**
 * An abstract decorator class, all the request decorators will extend this class.
 */
class Request_Decorator implements IRequest {
	/**
	 * Request object.
	 *
	 * @var IRequest
	 */
	protected IRequest $request;

	/**
	 * Construction for decorator class.
	 *
	 * @param IRequest $request The request object to decorate.
	 */
	public function __construct( IRequest $request ) {
		$this->request = $request;
	}

	/**
	 * Function for get request.
	 *
	 * @param string $url The url to make request to.
	 * @param mixed  $args The arguments to send.
	 *
	 * @return mixed
	 */
	public function get( string $url, $args ) {
		return $this->request->get( $url, $args );
	}

	/**
	 * Function for post request.
	 *
	 * @param string $url The url to make request to.
	 * @param mixed  $args The arguments to send.
	 *
	 * @return mixed
	 */
	public function post( string $url, $args ) {
		return $this->request->post( $url, $args );
	}
}
