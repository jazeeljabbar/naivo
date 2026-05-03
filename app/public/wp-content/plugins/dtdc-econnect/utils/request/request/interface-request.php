<?php
/**
 * The file for Request interface.
 *
 * @link       https://shipsy.io/
 * @since      1.0.3
 *
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/admin/crons/job
 */

namespace shipsy\request;

/**
 * Interface that will be used by all of Shipsy's Request.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

interface IRequest {
	/**
	 * Function to send post request.
	 *
	 * @param string $url The url to send request to.
	 * @param mixed  $args The args to send.
	 *
	 * @return array|\WP_Error
	 */
	public function post( string $url, $args );

	/**
	 * Function to send get request.
	 *
	 * @param string $url The url to send request to.
	 * @param mixed  $args The args to send.
	 *
	 * @return array|\WP_Error
	 */
	public function get( string $url, $args );
}
