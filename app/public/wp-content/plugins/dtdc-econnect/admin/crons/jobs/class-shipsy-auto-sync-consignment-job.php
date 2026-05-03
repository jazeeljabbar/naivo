<?php
/**
 * The file concrete Job implementation, here, Auto Sync Consignment Job.
 *
 * @link       https://shipsy.io/
 * @since      1.0.3
 *
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/admin/crons/job
 */

namespace shipsy\cron\job\auto_sync;

use Exception;
use shipsy\cron\job\Job;

if ( ! defined( 'ABSPATH' ) ) exit;

require_once SHIPSY_ECONNECT_PATH . 'admin/crons/jobs/interface-job.php';
require_once SHIPSY_ECONNECT_PATH . 'admin/helper/helper.php';
require_once SHIPSY_ECONNECT_PATH . 'utils/request/shipsy-request-handler.php';

/**
 * Shipsy's Job to Sync Consignments automatically.
 */
class Shipsy_Auto_Sync_Consignment_Job implements Job {
	/**
	 * Default constructor.
	 */
	public function __construct() {
	}

	/**
	 * The task to auto sync consignments.
	 *
	 * @return void
	 */
	public function execute() {
		$this->shipsy_auto_sync_consignments();
	}

	/**
	 * Auto sync action hook callback function.
	 *
	 * @return void
	 */
	private function shipsy_auto_sync_consignments() {
		require SHIPSY_ECONNECT_PATH . 'config/settings.php';

		$orders    = shipsy_get_consignments_to_sync();
		$order_ids = array();
		foreach ( $orders as $order ) {
			$order_ids[] = $order->orderId; // phpcs:ignore
		}

		$consignments_to_sync = 0;
		$consignment_id       = array();
		foreach ( $order_ids as $order_id ) {
			// phpcs:ignore
			if ( $consignments_to_sync >= $SYNC_CONSIGNMENTS_LIMIT ) {
				break;
			}

			$consignment_id[] = $order_id;
			shipsy_add_consignment_to_sync( $order_id );
			++$consignments_to_sync;
		}

		if ( count( $consignment_id ) > 0 ) {
			try {
				$consignments = $this->shipsy_get_consignment_details_for_auto_sync( $consignment_id );
				$this->shipsy_auto_softdata_upload( $consignments );
			} catch ( Exception $exception ) {
				foreach ( $consignment_id as $cons_id ) {
					if ( is_null( shipsy_get_ref_no( $cons_id ) ) ) {
						shipsy_rollback_pending_consignment( $cons_id );
					}
				}
			}
		}
	}

	/**
	 * Internal function to prepare consignment details.
	 *
	 * @param array $order_ids Array of order ids to get details.
	 * @return array
	 */
	private function shipsy_get_consignment_details_for_auto_sync( array $order_ids ): array {
		$response                   = shipsy_get_addresses();
		$all_addresses              = shipsy_validate_customer_addresses( $response['data'] );
		$valid_service_types        = $all_addresses['serviceTypes'];
		$forward_address            = $all_addresses['forwardAddress'];
		$reverse_address            = $all_addresses['reverseAddress'];
		$return_address             = $all_addresses['returnAddress'];
		$exceptional_return_address = $all_addresses['exceptionalReturnAddress'];

		$consignments      = array();
		$sync_service_type = ( shipsy_get_option( 'auto_sync_service_type' ) ?
			shipsy_get_option( 'auto_sync_service_type' ) :
			$valid_service_types[0]['id']
		);

		foreach ( $order_ids as $order_id ) {
			$order            = wc_get_order( $order_id );
			$customer_notes   = $order->get_customer_note();
			$shipping_address = $order->get_address( 'shipping' );

			$total_cash_retrieval = 0.0;
			foreach ( $order->get_items() as $key => $item ) {
				if ( ! empty( $item['is_deposit'] ) ) {
					if ( 'cod' === $order->get_payment_method() ) {
						$total_cash_retrieval += (float) $item['_deposit_full_amount_ex_tax'] + $item['total_tax'];
					} else {
						$total_cash_retrieval += max(
							0.0,
							( (float) $item['_deposit_full_amount_ex_tax'] -
							(float) $item['_deposit_deposit_amount_ex_tax'] +
							(float) $item['total_tax'] )
						);
					}
				} elseif ( 'cod' === $order->get_payment_method() ) {
					$total_cash_retrieval += $item['total'] + $item['total_tax'];
				}
			}

			$shipping_address = shipsy_validate_consignment_addresses( $shipping_address );

			$data_to_send_array = array(
				'customer_code'              => shipsy_get_setting( 'cust_code' ),
				'consignment_type'           => 'forward',
				'service_type_id'            => $sync_service_type,
				'reference_number'           => '',
				'load_type'                  => 'NON-DOCUMENT',
				'customer_reference_number'  => $order_id,
				'commodity_name'             => 'Other',
				'num_pieces'                 => '1',
				'origin_details'             => array(
					'name'            => $forward_address['name'],
					'phone'           => $forward_address['phone'],
					'alternate_phone' => $forward_address['alternate_phone'],
					'address_line_1'  => $forward_address['address_line_1'],
					'address_line_2'  => $forward_address['address_line_2'],
					'pincode'         => $forward_address['pincode'] ?? '',
					'city'            => $forward_address['city'],
					'state'           => $forward_address['state'],
					'country'         => $forward_address['country'],
				),
				'destination_details'        => array(
					'name'            => "{$shipping_address['first_name']} {$shipping_address['last_name']}",
					'phone'           => $order->get_billing_phone(),
					'alternate_phone' => $order->get_billing_phone(),
					'address_line_1'  => $shipping_address['address_1'],
					'address_line_2'  => $shipping_address['address_2'],
					'pincode'         => $shipping_address['postcode'] ?? '',
					'city'            => $shipping_address['city'],
					'state'           => $shipping_address['state'],
					'country'         => $shipping_address['country'],
				),
				'same_pieces'                => false,
				'cod_favor_of'               => '',
				'pieces_detail'              => array(),
				'cod_collection_mode'        => ( 0.0 === (float) $total_cash_retrieval ? '' : 'CASH' ),
				'cod_amount'                 => $total_cash_retrieval,
				'return_details'             => array(
					'name'            => $reverse_address['name'],
					'phone'           => $reverse_address['phone'],
					'alternate_phone' => $reverse_address['alternate_phone'],
					'address_line_1'  => $reverse_address['address_line_1'],
					'address_line_2'  => $reverse_address['address_line_2'],
					'pincode'         => $reverse_address['pincode'],
					'city'            => $reverse_address['city'],
					'state'           => $reverse_address['state'],
				),
				'exceptional_return_details' => array(
					'name'            => $exceptional_return_address['name'],
					'phone'           => $exceptional_return_address['phone'],
					'alternate_phone' => $exceptional_return_address['alternate_phone'],
					'address_line_1'  => $exceptional_return_address['address_line_1'],
					'address_line_2'  => $exceptional_return_address['address_line_2'],
					'pincode'         => $exceptional_return_address['pincode'],
					'city'            => $exceptional_return_address['city'],
					'state'           => $exceptional_return_address['state'],
				),
			);

			
			$data_to_send_array['rto_details'] = array(
				'name'            => $return_address['name'],
				'phone'           => $return_address['phone'],
				'alternate_phone' => $return_address['alternate_phone'],
				'address_line_1'  => $return_address['address_line_1'],
				'address_line_2'  => $return_address['address_line_2'],
				'pincode'         => $return_address['pincode'],
				'city'            => $return_address['city'],
				'state'           => $return_address['state'],
			);
			

			$order_items = $order->get_items();
			foreach ( $order_items as $key => $item ) {
				$description    = sanitize_text_field( (int) $item['quantity'] . ' ' . $item['name'] );
				$declared_value = sanitize_text_field( $item['total'] + $item['total_tax'] );
				$product        = $item->get_product();

				$data_to_send_array['pieces_detail'][] = array(
					'description'    => sanitize_text_field( $description ),
					'declared_value' => sanitize_text_field( $declared_value ),
					'weight'         => $product->get_weight() ? (float) ($product->get_weight() * $item['quantity']) : 1,
					'height'         => $product->get_height() ? $product->get_height() : 1,
					'length'         => $product->get_length() ? $product->get_length() : 1,
					'width'          => $product->get_width() ? $product->get_width() : 1,
				);
			}

			$data_to_send_array['notes'] = sanitize_text_field( $customer_notes );
			$consignments[]              = $data_to_send_array;
		}
		return $consignments;
	}

	/**
	 * Internal function for Softdata upload for auto consignment sync.
	 *
	 * @param array $consignments Consignment details for softdata upload.
	 * @return void
	 */
	private function shipsy_auto_softdata_upload( array $consignments ) {
		$data_to_send_json = wp_json_encode(
			array(
				'consignments' => $consignments,
			)
		);

		$headers = array(
			'Content-Type'    => 'application/json',
			'organisation-id' => shipsy_get_setting( 'org_id' ),
			'shop-origin'     => 'wordpress',
			'shop-url'        => shipsy_get_shop_url(),
			'customer-id'     => shipsy_get_setting( 'cust_id' ),
			'access-token'    => shipsy_get_setting( 'access_token' ),
		);
		$args    = array(
			'body'        => $data_to_send_json,
			'timeout'     => '50',
			'redirection' => '50',
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => $headers,
		);

		$request_url = shipsy_get_endpoint( 'SOFTDATA_API', shipsy_get_setting( 'org_id' ) );
		$request     = shipsy_get_request_handler();
		$response    = $request->post( $request_url, $args );
		$result      = wp_remote_retrieve_body( $response );

		$array = json_decode( $result, true );

		if ( array_key_exists( 'data', $array ) ) {
			foreach ( $array['data'] as $res ) {
				/*
				Order sync is successful iff,
					- we have data in response
					- the data has success set as true
					- there is a non empty value for `reference_number` key
				Please refer to https://shipsy.atlassian.net/browse/DTDCSPT-2057 for the
				issue that occurs when not doing so.
				*/

				if ( array_key_exists( 'reference_number', $res ) &&
					strlen( $res['reference_number'] ) > 0 ) {

					shipsy_update_synced_consignment(
						$res['customer_reference_number'],
						$res['reference_number'],
						'Sync Success',
						'Successfully synced.'
					);
				} else {
					$sync_comment = 'Something went wrong. Please try again later.';
					if ( array_key_exists( 'message', $res ) ) {
						$sync_comment = $res['message'];
					}

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
				}
			}
		}
	}
}

