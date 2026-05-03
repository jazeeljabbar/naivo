<?php
/**
 * Shipsy internal API to get download single shipping label.
 *
 * @link       https://shipsy.io/
 * @since      1.0.3
 *
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/admin/apis
 */

/** Shipsy internal API to download single shipping label. */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once SHIPSY_ECONNECT_PATH . 'admin/helper/helper.php';
require_once SHIPSY_ECONNECT_PATH . 'utils/request/shipsy-request-handler.php';

$reference_no = isset( $_REQUEST['ref_no'] ) ? sanitize_text_field( $_REQUEST['ref_no'] ) : ''; // phpcs:ignore

$headers     = array(
	'Content-Type'    => 'application/json',
	'organisation-id' => shipsy_get_cookie( 'org_id' ),
	'shop-origin'     => 'wordpress',
	'shop-url'        => shipsy_get_shop_url(),
	'customer-id'     => shipsy_get_cookie( 'cust_id' ),
	'access-token'    => shipsy_get_cookie( 'access_token' ),
);
$args        = array(
	'timeout'     => '10',
	'redirection' => '10',
	'httpversion' => '1.0',
	'blocking'    => true,
	'headers'     => $headers,
);
$request_url = add_query_arg(
	array( 'reference_number' => $reference_no ),
	shipsy_get_endpoint( 'SHIPPING_LABEL_API' ) . '/link'
);
$request     = shipsy_get_request_handler();
$response    = $request->get( $request_url, $args );
$result      = wp_remote_retrieve_body( $response );
$array2      = json_decode( $result, true );

$notifications = array();

if ( array_key_exists( 'data', $array2 ) ) {
	$url = $array2['data']['url'];
	try {

		$filename = "shipping-label-$reference_no.pdf";
		header( 'Content-type: application/x-file-to-save' );
		header( 'Content-Disposition: attachment; filename=' . basename( $filename ) );
		readfile( $url ); // phpcs:ignore
		$notifications['success'] = "Successfully downloaded label for $reference_no";

	} catch ( Exception $ex ) {
		$notifications['failure'] = 'Something went wrong please try again later';
	}
} else {
	$notifications['failure'] = $array2['error']['message'];
}

wp_send_json( $notifications );
