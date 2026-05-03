<?php
/**
 * Concrete Request implementation.
 *
 * @link       https://shipsy.io/
 * @since      1.0.3
 *
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/admin/crons/job
 */

namespace shipsy\request;

use shipsy\request\IRequest;

if ( ! defined( 'ABSPATH' ) ) exit;

require_once SHIPSY_ECONNECT_PATH . 'utils/request/request/interface-request.php';

/**
 * Shipsy's Request.
 */
class Request implements IRequest {

	/**
	 * Function to send get request.
	 *
	 * @param string $url The url to send request to.
	 * @param mixed  $args The args to send.
	 *
	 * @return array|\WP_Error
	 */
	public function get( string $url, $args ) {
		return wp_remote_get( $url, $args );
	}

	/**
	 * Function to send post request.
	 *
	 * @param string $url The url to send request to.
	 * @param mixed  $args The args to send.
	 *
	 * @return array|\WP_Error
	 */
	public function post( string $url, $args ) {
		return wp_remote_post( $url, $args );
	}
}
