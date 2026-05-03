<?php
/**
 * The file concrete Job implementation, here, Auto Consignment Status Update Job.
 *
 * @link       https://shipsy.io/
 * @since      1.0.3
 *
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/admin/crons/job
 */

namespace shipsy\cron\job\auto_consignment_status;

use Exception;
use shipsy\cron\job\Job;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Shipsy's Job to update Consignment status automatically.
 */
class Shipsy_Auto_Update_Consignment_Status_Job implements Job {
	/**
	 * The task to auto update consignment status.
	 *
	 * @return void
	 */
	public function execute() {
		$this->shipsy_auto_update_consignment_status();
	}

	/**
	 * Auto sync action hook callback function.
	 *
	 * @return void
	 */
	private function shipsy_auto_update_consignment_status() {
		require SHIPSY_ECONNECT_PATH . 'config/settings.php';
		require_once SHIPSY_ECONNECT_PATH . 'admin/helper/helper.php';

		$orders = shipsy_get_successfully_synced_consignments();

		$order_id = array();
		foreach ( $orders as $order ) {
			$order_id[] = $order->orderId;  // phpcs:ignore
		}

		if ( count( $order_id ) > 0 ) {
			$this->shipsy_update_consignment_status( $order_id );
		}
	}

	/**
	 * Internal function for Softdata upload for auto consignment sync.
	 *
	 * @param array $consignments Consignment Ids for status update.
	 * @return void
	 */
	private function shipsy_update_consignment_status( array $consignments ) {
		require SHIPSY_ECONNECT_PATH . 'config/settings.php';
		require_once SHIPSY_ECONNECT_PATH . 'admin/helper/helper.php';

		$data_to_send_json = wp_json_encode(
			array(
				'customerReferenceNumberList' => $consignments,
			)
		);

		$headers     = array(
			'Content-Type'    => 'application/json',
			'organisation-id' => shipsy_get_setting( 'org_id' ),
			'shop-origin'     => 'wordpress',
			'shop-url'        => shipsy_get_shop_url(),
			'customer-id'     => shipsy_get_setting( 'cust_id' ),
			'access-token'    => shipsy_get_setting( 'access_token' ),
		);
		$args        = array(
			'body'        => $data_to_send_json,
			'timeout'     => '50',
			'redirection' => '50',
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => $headers,
		);
		$request_url = shipsy_get_endpoint( 'AWB_NUMBER_API', shipsy_get_setting( 'org_id' ) );
		$response    = wp_remote_post( $request_url, $args );
		$result      = wp_remote_retrieve_body( $response );

		$array = json_decode( $result, true );

		if ( array_key_exists( 'data', $array ) ) {
			// phpcs:ignore
			foreach ( $array['data'] as $orderId => $res ) {
				$res = $res[0];

				if ( array_key_exists( 'reference_number', $res ) &&
					strlen( $res['reference_number'] ) > 0 ) {

					// phpcs:disable
					if( array_key_exists( $res['status'], $VALID_CONSIGNMENT_STATUSES ) ) {
						error_log(print_r($res['status'], true));
						shipsy_update_synced_consignment(
							$orderId,
							$res['reference_number'],
							$VALID_CONSIGNMENT_STATUSES[$res['status']]['status'],
							$VALID_CONSIGNMENT_STATUSES[$res['status']]['comment'],
						);
					}
					// phpcs:enable
				}
			}
		}
	}
}
