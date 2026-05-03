<?php
/**
 * Shipsy internal API for softdata upload.
 *
 * @link       https://shipsy.io/
 * @since      1.0.3
 *
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/admin/apis
 */

/** Shipsy internal API for softdata upload. */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once SHIPSY_ECONNECT_PATH . 'admin/helper/helper.php';
require_once SHIPSY_ECONNECT_PATH . 'utils/request/shipsy-request-handler.php';

$request = shipsy_sanitize_array( $_REQUEST );  // phpcs:ignore
$request = $request['consignments'];

$result = shipsy_get_addresses();
$result = shipsy_sanitize_array( $result['data'] );
$result = shipsy_validate_customer_addresses( $result );

$consignments = array();
$order_ids    = array();

foreach ( $request as $idx => $consignment ) {

	$origin_w3w_id      = 'origin-w3w-number-' . $idx;
	$destination_w3w_id = 'destination-w3w-number-' . $idx;

	$consignment = shipsy_validate_consignment_addresses( $consignment );

	$order_ids[] = $consignment['customer-reference-number'];

	$same_piece = false;
	if ( array_key_exists( 'multiPieceCheck', shipsy_sanitize_array( $consignment ) ) ) {
		$same_piece = true;
	}
	$data_to_send_array = array(
		'customer_code'              => shipsy_get_cookie( 'cust_code' ),
		'consignment_type'           => $consignment['consignment-type'],
		'service_type_id'            => $consignment['service-type'],
		'reference_number'           => '',
		'load_type'                  => $consignment['courier-type'],
		'customer_reference_number'  => $consignment['customer-reference-number'],
		'commodity_name'             => 'Other',
		'num_pieces'                 => $consignment['num-pieces'],
		'origin_details'             => array(
			'name'            => $consignment['origin-name'],
			'phone'           => $consignment['origin-number'],
			'alternate_phone' => ( '' === $consignment['origin-alt-number'] ) ? $consignment['origin-number'] : $consignment['origin-alt-number'],
			'address_line_1'  => $consignment['origin-line-1'],
			'address_line_2'  => $consignment['origin-line-2'],
			'pincode'         => $consignment['origin-pincode'] ?? '',
			'w3w_code'        => shipsy_get_option( 'enable_what3words_code_option' ) === '1' ? $consignment[ $origin_w3w_id ] : '',
			'city'            => $consignment['origin-city'],
			'state'           => $consignment['origin-state'],
			'country'         => $consignment['origin-country'],
		),
		'destination_details'        => array(
			'name'            => $consignment['destination-name'],
			'phone'           => $consignment['destination-number'],
			'alternate_phone' => ( '' === $consignment['destination-alt-number'] ) ? $consignment['destination-number'] : $consignment['destination-alt-number'],
			'address_line_1'  => $consignment['destination-line-1'],
			'address_line_2'  => $consignment['destination-line-2'],
			'pincode'         => $consignment['destination-pincode'] ?? '',
			'w3w_code'        => shipsy_get_option( 'enable_what3words_code_option' ) === '1' ? $consignment[ $destination_w3w_id ] : '',
			'city'            => $consignment['destination-city'],
			'state'           => $consignment['destination-state'],
			'country'         => $consignment['destination-country'],
		),
		'same_pieces'                => $same_piece,
		'cod_favor_of'               => '',
		'pieces_detail'              => array(),
		'cod_collection_mode'        => ( 0.0 === (float) $consignment['cod-amount'] ? '' : $consignment['cod-collection-mode'] ),
		'cod_amount'                 => $consignment['cod-amount'],
		'return_details'             => array(
			'name'            => $result['reverseAddress']['name'],
			'phone'           => $result['reverseAddress']['phone'],
			'alternate_phone' => $result['reverseAddress']['alternate_phone'],
			'address_line_1'  => $result['reverseAddress']['address_line_1'],
			'address_line_2'  => $result['reverseAddress']['address_line_2'],
			'pincode'         => $result['reverseAddress']['pincode'],
			'w3w_code'        => shipsy_get_option( 'enable_what3words_code_option' ) === '1' ? $result['reverseAddress']['w3w_code'] : '',
			'city'            => $result['reverseAddress']['city'],
			'state'           => $result['reverseAddress']['state'],
		),
		'exceptional_return_details' => array(
			'name'            => $result['exceptionalReturnAddress']['name'],
			'phone'           => $result['exceptionalReturnAddress']['phone'],
			'alternate_phone' => $result['exceptionalReturnAddress']['alternate_phone'],
			'address_line_1'  => $result['exceptionalReturnAddress']['address_line_1'],
			'address_line_2'  => $result['exceptionalReturnAddress']['address_line_2'],
			'pincode'         => $result['exceptionalReturnAddress']['pincode'],
			'w3w_code'        => shipsy_get_option( 'enable_what3words_code_option' ) === '1' ? $result['exceptionalReturnAddress']['w3w_code'] : '',
			'city'            => $result['exceptionalReturnAddress']['city'],
			'state'           => $result['exceptionalReturnAddress']['state'],
		),
	);

	
	$data_to_send_array['rto_details'] = array(
		'name'            => $result['returnAddress']['name'],
		'phone'           => $result['returnAddress']['phone'],
		'alternate_phone' => $result['returnAddress']['alternate_phone'],
		'address_line_1'  => $result['returnAddress']['address_line_1'],
		'address_line_2'  => $result['returnAddress']['address_line_2'],
		'pincode'         => $result['returnAddress']['pincode'],
		'w3w_code'        => shipsy_get_option( 'enable_what3words_code_option' ) === '1' ? $result['returnAddress']['w3w_code'] : '',
		'city'            => $result['returnAddress']['city'],
		'state'           => $result['returnAddress']['state'],
	);


	$data_to_send_array['pieces_detail'] = array();
	for ( $index = 0; $index < $consignment['num-pieces']; $index++ ) {
		$temp_piece_details                    = array(
			'description'    => $consignment['description'][ $index ],
			'declared_value' => $consignment['declared-value'][ $index ],
			'weight'         => (float) $consignment['weight'][ $index ],
			'height'         => (float) $consignment['height'][ $index ],
			'length'         => (float) $consignment['length'][ $index ],
			'width'          => (float) $consignment['width'][ $index ],
		);
		$data_to_send_array['pieces_detail'][] = $temp_piece_details;
		if ( $same_piece ) {
			break;
		}
	}

	$data_to_send_array['notes'] = sanitize_text_field( $consignment['notes'] );

	$consignments[] = $data_to_send_array;
}

$data_to_send_json = wp_json_encode(
	array(
		'consignments' => $consignments,
	)
);

$headers = array(
	'Content-Type'    => 'application/json',
	'organisation-id' => shipsy_get_cookie( 'org_id' ),
	'shop-origin'     => 'wordpress',
	'shop-url'        => shipsy_get_shop_url(),
	'customer-id'     => shipsy_get_cookie( 'cust_id' ),
	'access-token'    => shipsy_get_cookie( 'access_token' ),
);
$args    = array(
	'body'        => $data_to_send_json,
	'timeout'     => '50',
	'redirection' => '50',
	'httpversion' => '1.0',
	'blocking'    => true,
	'headers'     => $headers,
);

$request_url = shipsy_get_endpoint( 'SOFTDATA_API' );
$request     = shipsy_get_request_handler();
$response    = $request->post( $request_url, $args );
$result      = wp_remote_retrieve_body( $response );

$array = json_decode( $result, true );

$notifications              = array();
$notifications['post_type'] = 'shop_order';

$success_synced_orders = array();
$failure_synced_orders = array();

$has_failed = false;

if ( array_key_exists( 'data', $array ) ) {
	foreach ( $array['data'] as $res ) {
		/*
		Order sync is successful iff,
			- we have data in response
			- there is a non empty value for `reference_number` key
		Please refer to https://shipsy.atlassian.net/browse/DTDCSPT-2057 for the
		issue that occurs when not doing so.
		*/
		if ( array_key_exists( 'reference_number', $res ) &&
			strlen( $res['reference_number'] ) > 0 ) {
			$success_synced_orders[] = array(
				'orderId' => $res['customer_reference_number'],
				'message' => 'Successfully synced',
			);

			// TODO: Maybe we can remove the if condition here.
			// for compatability with auto sync, i.e. manage double syncs.
			if ( shipsy_check_consignment_synced( $res['customer_reference_number'] ) ) {
				shipsy_update_synced_consignment(
					$res['customer_reference_number'],
					$res['reference_number'],
					'Sync Success',
					'Successfully synced.'
				);
			} else {
				shipsy_add_synced_consignment(
					$res['customer_reference_number'],
					$res['reference_number'],
					'Sync Success',
					'Successfully synced.'
				);
			}
		} else {
			$sync_comment = 'Something went wrong. Please try again later.';
			if ( array_key_exists( 'message', $res ) ) {
				$sync_comment = $res['message'];
			}

			// for compatability with auto sync, i.e. manage double syncs.
			if ( shipsy_check_consignment_synced( $res['customer_reference_number'] ) ) {
				if ( shipsy_get_ref_no( $res['customer_reference_number'] ) ) {
					shipsy_update_synced_consignment(
						$res['customer_reference_number'],
						$res['reference_number'],
						'Sync Success',
						'Successfully synced.'
					);
				} else {
					shipsy_update_synced_consignment(
						$res['customer_reference_number'],
						'',
						'Sync Failed',
						$sync_comment
					);
				}
			} else {
				shipsy_add_synced_consignment(
					$res['customer_reference_number'],
					'',
					'Sync Failed',
					$sync_comment
				);
			}

			$failure_synced_orders[] = array(
				'orderId' => $res['customer_reference_number'],
				'message' => $res['message'],
			);
		}
	}
} else {
	$has_failed               = true;
	$notifications['failure'] = $array['error']['message'];
}

if ( $has_failed ) {
	$response = array(
		'success'  => false,
		'status'   => 302,
		'redirect' => add_query_arg( $notifications, admin_url( 'edit.php' ) ),
	);
} else {
	$redirect_query['page'] = 'shipsy-sync-result';

	$response = array(
		'success'  => true,
		'status'   => 302,
		'redirect' => add_query_arg( $redirect_query, admin_url( 'admin.php' ) ),
		'data'     => array(
			'success' => $success_synced_orders,
			'failed'  => $failure_synced_orders,
		),
	);
}

wp_send_json( $response );
