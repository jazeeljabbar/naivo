<?php
/**
 * The file for concrete implementation of Request Logging Decorator class.
 *
 * @link       https://shipsy.io/
 * @since      1.0.3
 *
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/admin/crons/decorators
 */

use shipsy\request\IRequest;
use \shipsy\request\decorator\Request_Decorator;

if ( ! defined( 'ABSPATH' ) ) exit;

require_once SHIPSY_ECONNECT_PATH . 'utils/request/request/interface-request.php';
require_once SHIPSY_ECONNECT_PATH . 'utils/request/decorator/class-request-decorator.php';


/**
 * A concrete decorator class to log requests and response.
 */
class Shipsy_Request_Log_Decorator extends Request_Decorator {
	/**
	 * The path of log file.
	 *
	 * @var string
	 */
	protected string $log;

	/**
	 * Constructor for decorator class.
	 *
	 * @param IRequest $request Request object.
	 */
	public function __construct( IRequest $request ) {
		parent::__construct( $request );

		require SHIPSY_ECONNECT_PATH . 'config/settings.php';
		$this->log = $LOG_FILE; // phpcs:ignore
	}

	/**
	 * Helper function to write logs to file.
	 *
	 * @param string $url The url to send request to.
	 * @param string $type Request / Response.
	 * @param string $content The arguments for request or data of response.
	 *
	 * @return void
	 */
	private function write_log( string $url, string $type, string $content ) {
		$url      = explode( '/', $url );
		$api      = $url[ count( $url ) - 1 ];
		$api      = strtoupper( $api ) . '_API';
		$datetime = gmdate( 'd-m-y h:i:s' );
		$content  = "[$datetime] <$type: $api> $content\n";

		// phpcs:disable
		$file = fopen( $this->log, 'a+' );
		fwrite( $file, $content );
		fclose( $file );
		// phpcs:enable
	}

	/**
	 * Function for get request.
	 *
	 * @param string $url The url to send request to.
	 * @param mixed  $args The arguments to send.
	 *
	 * @return mixed
	 */
	public function get( string $url, $args ) {
		$this->write_log( $url, 'Request', wp_json_encode( $args ) );
		$response      = parent::get( $url, $args );
		$response_data = wp_remote_retrieve_body( $response );
		$this->write_log( $url, 'Response', wp_json_encode( $response_data ) );
		return $response;
	}

	/**
	 * Function for post request.
	 *
	 * @param string $url The url to send request to.
	 * @param mixed  $args The arguments to send.
	 *
	 * @return mixed
	 */
	public function post( string $url, $args ) {
		$this->write_log( $url, 'Request', wp_json_encode( $args ) );
		$response      = parent::post( $url, $args );
		$response_data = wp_remote_retrieve_body( $response );
		$this->write_log( $url, 'Response', wp_json_encode( $response_data ) );
		return $response;
	}
}
