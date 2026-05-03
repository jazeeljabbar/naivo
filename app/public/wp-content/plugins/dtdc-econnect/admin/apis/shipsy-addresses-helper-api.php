<?php
/**
 * Shipsy internal API to fetch addresses.
 *
 * @link       https://shipsy.io/
 * @since      1.0.3
 *
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/admin/apis
 */

/** Shipsy internal API to fetch addresses. */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once SHIPSY_ECONNECT_PATH . 'admin/helper/helper.php';

$addresses = shipsy_get_addresses();
$addresses = shipsy_sanitize_array( $addresses );

$result = array();

if ( array_key_exists( 'data', $addresses ) && ! is_null( $addresses['data'] ) ) {
	$all_addresses  = shipsy_validate_customer_addresses( $addresses['data'] );
	$result['data'] = array(
		'addresses' => $all_addresses,
		'success'   => true,
	);
} else {
	$result['error'] = array(
		'message' => 'Invalid Request please try after sometime',
		'success' => false,
	);
}
wp_send_json( $result );
