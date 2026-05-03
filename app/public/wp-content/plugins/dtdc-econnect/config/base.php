<?php
/**
 * The file contains base settings to be used by the plugin, these can be overridden in settings.php.
 *
 * @link       https://shipsy.io/
 * @since      1.0.3
 *
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/admin
 */

//phpcs:disable
// This setting file contains all the routes to the backend APIs.

if ( ! defined( 'ABSPATH' ) ) exit;

$ENDPOINTS = array(
	'AWB_NUMBER_API'         => '/api/ecommerce/getawbnumber',
	'VSERIES_API'            => '/api/ecommerce/getSeries',
	'SHOP_DATA_API'          => '/api/ecommerce/getshopdata',
	'REGISTER_SHOP_API'      => '/api/ecommerce/registershop',
	'UPDATE_ADDRESS_API'     => '/api/ecommerce/updateaddress',
	'TRACKING_API'           => '/api/ecommerce/gettracking',
	'SOFTDATA_API'           => '/api/ecommerce/softdata',
	'SHIPPING_LABEL_API'     => '/api/ecommerce/shippinglabel',
	'CANCEL_CONSIGNMENT_API' => '/api/ecommerce/cancelconsignment',
	'BULK_LABEL_API'         => '/api/ecommerce/generateconsignmentlabelStream',
);

// Set cookie time to live for 30 days
$COOKIE_TTL = 2592000;

// Set max consignment sync limit
$SYNC_CONSIGNMENTS_LIMIT = 10;

// Default log file.
$LOG_FILE = SHIPSY_ECONNECT_PATH . 'debug.log';
// phpcs:enable