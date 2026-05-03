<?php
/**
 * The file contains sample settings to be used by the plugin.
 *
 * @link       https://shipsy.io/
 * @since      1.0.3
 *
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/admin
 */

//phpcs:disable

if ( ! defined( 'ABSPATH' ) ) exit;

require 'base.php';

$BASE_URL = 'https://dtdcapi.shipsy.io';

$ORGANISATION = 'DTDC';

// $SYNC_CONSIGNMENTS_LIMIT = 10; // override consignment sync limit;
$ORIGIN_COUNTRY = 'India';
$DOMESTIC = True; // <True/False>;

$VALID_CONSIGNMENT_STATUSES = array(
	'pickup_scheduled' => array(
		'status' => 'Pickup Scheduled',
		'comment' => 'Waiting for pickup',
		'style' => array(
			'background' => '#e5e5e5',
			'color'      => '#777',
		)
	),
	'out_for_pickup' => array(
		'status' => 'Out for Pickup',
		'comment' => 'Out for pickup',
		'style' => array(
			'background' => '#e5e5e5',
			'color'      => '#777',
		)
	),
	'reached_at_hub' => array(
		'status' => 'Reached at Hub',
		'comment' => 'Order reached at hub',
		'style' => array(
			'background' => '#e5e5e5',
			'color'      => '#777',
		)
	),
	'outfordelivery' => array(
		'status' => 'Out for Delivery',
		'comment' => 'Out for delivery',
		'style' => array(
			'background' => '#e5e5e5',
			'color'      => '#777',
		)
	),
	'attempted' => array(
		'status' => 'Attempted',
		'comment' => 'Delivery attempted',
		'style' => array(
			'background' => '#e5e5e5',
			'color'      => '#777',
		)
	),
	'delivered' => array(
		'status' => 'Delivered',
		'comment' => 'Order successfully delivered',
		'style' => array(
			'background' => '#c6e1c6',
			'color'      => '#5b841b',
		)
	),
	'cancelled' => array(
		'status' => 'Cancelled',
		'comment' => 'Order cancelled',
		'style' => array(
			'background' => '#e5adae',
			'color'      => '#6d4546',
		)
	)
);

/*
Unset the local variables after use, or else they will leak into the files where
we include this file
*/
unset( $API );
unset( $URL );
//phpcs:enable