<?php
/**
 * Shipsy internal API to get shipping address.
 *
 * @link       https://shipsy.io/
 * @since      1.0.3
 *
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/admin/apis
 */

/** Shipsy internal API to get shipping address. */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once SHIPSY_ECONNECT_PATH . 'admin/helper/helper.php';

$order_id = isset( $_REQUEST['order_id'] ) ? sanitize_text_field( $_REQUEST['order_id'] ) : ''; // phpcs:ignore

$result = array();

if ( strlen( $order_id ) > 0 ) {
	$curr_order = wc_get_order( sanitize_text_field( $order_id ) );

	if ( ! is_null( $curr_order ) ) {
		$shipping_address              = array();
		$shipping_address['name']      = $curr_order->get_formatted_shipping_full_name();
		$shipping_address['state']     = $curr_order->get_shipping_state();
		$shipping_address['country']   = $curr_order->get_shipping_country();
		$shipping_address['city']      = $curr_order->get_shipping_city();
		$shipping_address['pincode']   = $curr_order->get_shipping_postcode();
		$shipping_address['address_1'] = $curr_order->get_shipping_address_1();
		$shipping_address['address_2'] = $curr_order->get_shipping_address_2();
		$shipping_address['phone']     = $curr_order->get_billing_phone();

		$shipping_address = shipsy_sanitize_array( $shipping_address );

		$result['data'] = array(
			'shipping_address' => $shipping_address,
			'success'          => true,
		);
	} else {
		$result['error'] = array(
			'message' => 'Invalid Order Id requested',
			'success' => false,
		);
	}
} else {
	$result['error'] = array(
		'message' => 'Invalid Order Id requested',
		'success' => false,
	);
}

wp_send_json( $result );
