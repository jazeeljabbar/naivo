<?php
/**
 * The functions used all over the place in this plugin.
 *
 * @link       https://shipsy.io/
 * @since      1.0.3
 *
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/admin
 */

/**
 * TODO: Find a neat way of importing the endpoints
 *      instead of just calling a function again and again.
 *      Although we are using `require_once` but still.
 *
 * TODO: Cache db calls everywhere.
 */

 if ( ! defined( 'ABSPATH' ) ) exit;
 
require_once SHIPSY_ECONNECT_PATH . 'utils/request/shipsy-request-handler.php';

/**
 * Function to get the endpoint url
 *
 * @param string      $api THe name of the api for which to get the endpoint.
 * @param string|null $org_id The organisation for which to fetch url.
 * @return string
 */
function shipsy_get_endpoint( string $api, string $org_id = null ): string {
	require SHIPSY_ECONNECT_PATH . 'config/settings.php';
	// TODO: Why is it not working when we use `require_once`?

	// For backward compatibility, and to prevent changes where calling this function.
	if ( is_null( $org_id ) ) {
		$org_id = shipsy_get_cookie( 'org_id' );
	}

	$integration_url  = shipsy_get_base_url($org_id);   // phpcs:ignore
	// phpcs:ignore
	if ( isset($PROJECTX_INTEGRATION_CONFIG) && ! is_null( $PROJECTX_INTEGRATION_CONFIG ) && array_key_exists( $org_id, $PROJECTX_INTEGRATION_CONFIG ) ) {
		$integration_url = $PROJECTX_INTEGRATION_CONFIG[ $org_id ]; // phpcs:ignore
	}

	return $integration_url . $ENDPOINTS[ $api ]; // phpcs:ignore
}

/**
 * Function to get TTL for cookies.
 *
 * @return int
 */
function shipsy_get_cookie_ttl(): int {
	require SHIPSY_ECONNECT_PATH . 'config/settings.php';

	// Ignore all caps casing.
	return time() + $COOKIE_TTL;    // phpcs:ignore
}

/**
 * Function to sanitize arrays.
 *
 * @param array $input The input to sanitize.
 * @return array
 */
function shipsy_sanitize_array( array $input ): array {
	// Initialize the new array that will hold the sanitized values.
	$new_input = array();

	// Loop through the input and recursively sanitize each of the values.
	foreach ( $input as $key => $val ) {
		if ( is_array( $val ) ) {
			$new_input[ $key ] = shipsy_sanitize_array( $val );
		} else {
			$new_input[ $key ] = sanitize_text_field( $val );
		}
	}
	return $new_input;
}

/**
 * Function to parse error response.
 *
 * @param array $error Array containing error message.
 * @return mixed|string
 */
function shipsy_parse_response_error( array $error ) {
	if ( 401 === $error['statusCode'] ) {
		return 'Authentication error! Please log in again.';
	}
	return $error['message'];
}

/**
 * Function to validate consignment address (i.e, during syncing).
 *
 * @param array $consignment The consignment to sync.
 * @return array
 */
function shipsy_validate_consignment_addresses( array $consignment ): array {
	$ends        = array( 'origin', 'destination' );
	$end_details = array( 'name', 'number', 'alt-number', 'line-1', 'line-2', 'pincode', 'city', 'state', 'country' );

	foreach ( $ends as $end ) {
		foreach ( $end_details as $end_detail ) {
			$key = $end . '-' . $end_detail;
			if ( ! isset( $consignment[ $key ] ) ) {
				$consignment[ $key ] = '';
			}
		}
	}

	return $consignment;
}

/**
 * Function to validate addresses.
 *
 * @param array $addresses The addresses of customer.
 * @return array
 */
function shipsy_validate_customer_addresses( array $addresses ): array {
	$address_types   = array( 'forwardAddress', 'reverseAddress', 'exceptionalReturnAddress', 'returnAddress' );
	$address_details = array( 'name', 'phone', 'alternate_phone', 'address_line_1', 'address_line_2', 'pincode', 'city', 'state' );

	foreach ( $address_types as $address_type ) {
		if ( ! isset( $addresses[ $address_type ] ) ) {
			$addresses[ $address_type ] = array();
		}

		foreach ( $address_details as $address_detail ) {
			if ( ! isset( $addresses[ $address_type ][ $address_detail ] ) ) {
				$addresses[ $address_type ][ $address_detail ] = '';
			}
		}
	}
	return $addresses;
}

/**
 * Function to get AWB number.
 *
 * @param array $synced_orders Array of order ids if synced orders.
 * @return mixed
 */
function shipsy_get_awb_number( array $synced_orders ) {
	$headers = array(
		'Content-Type'    => 'application/json',
		'organisation-id' => shipsy_get_cookie( 'org_id' ),
		'shop-origin'     => 'wordpress',
		'shop-url'        => shipsy_get_shop_url(),
		'customer-id'     => shipsy_get_cookie( 'cust_id' ),
		'access-token'    => shipsy_get_cookie( 'access_token' ),
	);

	$data_to_send_json = wp_json_encode( array( 'customerReferenceNumberList' => $synced_orders ) );
	$args              = array(
		'body'        => $data_to_send_json,
		'timeout'     => '10',
		'redirection' => '10',
		'httpversion' => '1.0',
		'blocking'    => true,
		'headers'     => $headers,
	);
	$request_url       = shipsy_get_endpoint( 'AWB_NUMBER_API' );
	$request           = shipsy_get_request_handler();
	$response          = $request->post( $request_url, $args );
	$result            = wp_remote_retrieve_body( $response );

	return json_decode( $result, true );
}


/**
 * Function to get Virtual Series.
 *
 * @return mixed
 */
function shipsy_get_virtual_series() {
	$headers     = array(
		'Content-Type'    => 'application/json',
		'organisation-id' => shipsy_get_cookie( 'org_id' ),
		'shop-origin'     => 'wordpress',
		'shop-url'        => shipsy_get_shop_url(),
		'customer-id'     => shipsy_get_cookie( 'cust_id' ),
		'access-token'    => shipsy_get_cookie( 'access_token' ),
	);
	$args        = array(
		'headers' => $headers,
	);
	$request_url = shipsy_get_endpoint( 'VSERIES_API' );
	$request     = shipsy_get_request_handler();
	$response    = $request->get( $request_url, $args );
	$result      = wp_remote_retrieve_body( $response );

	return json_decode( $result, true );
}

/**
 * Function to get customer addresses.
 *
 * @return mixed
 */
function shipsy_get_addresses() {
	$headers     = array(
		'Content-Type'    => 'application/json',
		'organisation-id' => shipsy_get_setting( 'org_id' ),
		'shop-origin'     => 'wordpress',
		'shop-url'        => shipsy_get_shop_url(),
		'customer-id'     => shipsy_get_setting( 'cust_id' ),
		'access-token'    => shipsy_get_setting( 'access_token' ),
	);
	$args        = array(
		'timeout'     => '10',
		'redirection' => '10',
		'httpversion' => '1.0',
		'blocking'    => true,
		'headers'     => $headers,
	);
	$request_url = shipsy_get_endpoint( 'SHOP_DATA_API', shipsy_get_setting( 'org_id' ) );
	$request     = shipsy_get_request_handler();
	$response    = $request->post( $request_url, $args );
	$result      = wp_remote_retrieve_body( $response );

	return json_decode( $result, true );
}

/**
 * Function to configure plugin (i.e, user login with settings).
 *
 * @param array $post_request_params Form values.
 * @return void
 */
function shipsy_config( array $post_request_params ) {
	$post_request_params['org_id'] = strtolower( $post_request_params['org_id'] );

	$headers            = array(
		'Content-Type'    => 'application/json',
		'organisation-id' => $post_request_params['org_id'],
		'shop-origin'     => 'wordpress',
		'shop-url'        => shipsy_get_shop_url(),
	);
	$data_to_send_array = array(
		'username' => $post_request_params['user-name'],
		'password' => $post_request_params['password'],
	);
	$data_to_send_json  = wp_json_encode( $data_to_send_array );
	$args               = array(
		'body'        => $data_to_send_json,
		'timeout'     => '10',
		'redirection' => '10',
		'httpversion' => '1.0',
		'blocking'    => true,
		'headers'     => $headers,
	);
	$request_url        = shipsy_get_endpoint( 'REGISTER_SHOP_API', $post_request_params['org_id'] );

	$request               = shipsy_get_request_handler();
	$response              = $request->post( $request_url, $args );
	$result                = wp_remote_retrieve_body( $response );
	$result_data           = json_decode( $result, true );
	$notifications         = array();
	$notifications['page'] = 'shipsy-configuration';

	if ( array_key_exists( 'data', $result_data ) ) {
		if ( array_key_exists( 'access_token', $result_data['data'] ) ) {
			$access_token             = $result_data['data']['access_token'];
			$notifications['success'] = 'Configuration is successful';
			shipsy_set_setting( 'access_token', $access_token );

			// if registration is successful store the org-id in cookies.
			shipsy_set_setting( 'org_id', $post_request_params['org_id'] );
		}
		if ( array_key_exists( 'customer', $result_data['data'] ) &&
			array_key_exists( 'id', $result_data['data']['customer'] ) &&
			array_key_exists( 'code', $result_data['data']['customer'] ) ) {
			$customer_id   = $result_data['data']['customer']['id'];
			$customer_code = $result_data['data']['customer']['code'];
			shipsy_set_setting( 'cust_id', $customer_id );
			shipsy_set_setting( 'cust_code', $customer_code );
		}
	} else {
		// remove cookies if already set from previous config.
		shipsy_remove_setting( 'org_id' );
		shipsy_remove_setting( 'cust_id' );
		shipsy_remove_setting( 'cust_code' );
		shipsy_remove_setting( 'access_token' );

		// set org_id even if auth failed so that if user tries to access other pages,
		// request is sent to correct PX instance.
		shipsy_set_cookie( 'org_id', $post_request_params['org_id'], shipsy_get_cookie_ttl() );

		$notifications['failure'] = $result_data['error']['message'];
	}
	shipsy_remove_option( 'auto_sync_service_type' );

	wp_safe_redirect( add_query_arg( $notifications, admin_url( 'admin.php' ) ) );
}

/**
 * Function to store plugin settings.
 *
 * @param array $post_request_params The parameters for settings.
 * @return void
 */
function shipsy_settings( array $post_request_params ) {
	$notifications         = array();
	$notifications['page'] = 'shipsy-configuration';
	try {
		shipsy_set_option(
			'download_label_option',
			( array_key_exists( 'download_label_option', $post_request_params ) &&
			$post_request_params['download_label_option'] ) ? 1 : 0
		);

		shipsy_set_option(
			'enable_what3words_code_option',
			( array_key_exists( 'enable_what3words_code_option', $post_request_params ) &&
			$post_request_params['enable_what3words_code_option'] ) ? 1 : 0
		);

		shipsy_set_option(
			'club_multi_pieces_into_single_option',
			( array_key_exists( 'club_multi_pieces_into_single_option', $post_request_params ) &&
			$post_request_params['club_multi_pieces_into_single_option'] ) ? 1 : 0
		);

		shipsy_set_option(
			'enable_multipiece_edit_option',
			( array_key_exists( 'enable_multipiece_edit_option', $post_request_params ) &&
			$post_request_params['enable_multipiece_edit_option'] ) ? 1 : 0
		);

		shipsy_set_option(
			'enable_auto_sync_option',
			( array_key_exists( 'enable_auto_sync_option', $post_request_params ) &&
			$post_request_params['enable_auto_sync_option'] ) ? 1 : 0
		);

		shipsy_set_option(
			'enable_customer_order_edit_option',
			( array_key_exists( 'enable_customer_order_edit_option', $post_request_params ) &&
			$post_request_params['enable_customer_order_edit_option'] ) ? 1 : 0
		);

		shipsy_set_option(
			'enable_auto_status_update_option',
			( array_key_exists( 'enable_auto_status_update_option', $post_request_params ) &&
			$post_request_params['enable_auto_status_update_option'] ) ? 1 : 0
		);

		if ( (int) shipsy_get_option( 'enable_auto_sync_option' ) ) {
			shipsy_set_option(
				'auto_sync_service_type',
				$post_request_params['auto_sync_service_type']
			);
		}

		$notifications['success'] = 'Settings saved successfully!';
	} catch ( Exception $exc ) {
		$notifications['failure'] = 'Failed to save settings. Please contact the service provider to get it resolved.';
	}
	wp_safe_redirect( add_query_arg( $notifications, admin_url( 'admin.php' ) ) );

}

/**
 * Function to update address.
 *
 * @param array $post_request_params Form values.
 * @return void
 */
function shipsy_update_addresses( array $post_request_params ) {
	$headers = array(
		'Content-Type'    => 'application/json',
		'organisation-id' => shipsy_get_cookie( 'org_id' ),
		'shop-origin'     => 'wordpress',
		'shop-url'        => shipsy_get_shop_url(),
		'customer-id'     => shipsy_get_cookie( 'cust_id' ),
		'access-token'    => shipsy_get_cookie( 'access_token' ),
	);

	if ( isset( $post_request_params['useForwardCheck'] ) && 'true' === $post_request_params['useForwardCheck'] ) {
		$use_forward_address = true;
		$reverse_address     = array(
			'name'            => $post_request_params['forward-name'],
			'phone'           => $post_request_params['forward-phone'],
			'alternate_phone' => $post_request_params['forward-alt-phone'] ?? '',
			'address_line_1'  => $post_request_params['forward-line-1'],
			'address_line_2'  => $post_request_params['forward-line-2'],
			'pincode'         => $post_request_params['forward-pincode'],
			'w3w_code'        => $post_request_params['forward-w3w-number'],
			'city'            => $post_request_params['forward-city'],
			'state'           => $post_request_params['forward-state'],
			'country'         => $post_request_params['forward-country'],
		);
	} else {
		$use_forward_address = false;
		$reverse_address     = array(
			'name'            => $post_request_params['reverse-name'],
			'phone'           => $post_request_params['reverse-phone'],
			'alternate_phone' => $post_request_params['reverse-alt-phone'] ?? '',
			'address_line_1'  => $post_request_params['reverse-line-1'],
			'address_line_2'  => $post_request_params['reverse-line-2'],
			'pincode'         => $post_request_params['reverse-pincode'],
			'w3w_code'        => $post_request_params['reverse-w3w-number'],
			'city'            => $post_request_params['reverse-city'],
			'state'           => $post_request_params['reverse-state'],
			'country'         => $post_request_params['reverse-country'],
		);
	}
	$data_to_send_array = array(
		'forwardAddress'           => array(
			'name'            => $post_request_params['forward-name'],
			'phone'           => $post_request_params['forward-phone'],
			'alternate_phone' => $post_request_params['forward-alt-phone'] ?? '',
			'address_line_1'  => $post_request_params['forward-line-1'],
			'address_line_2'  => $post_request_params['forward-line-2'],
			'pincode'         => $post_request_params['forward-pincode'],
			'w3w_code'        => $post_request_params['forward-w3w-number'],
			'city'            => $post_request_params['forward-city'],
			'state'           => $post_request_params['forward-state'],
			'country'         => $post_request_params['forward-country'],
		),
		'reverseAddress'           => $reverse_address,
		'useForwardAddress'        => $use_forward_address,
		'exceptionalReturnAddress' => array(
			'name'            => $post_request_params['exp-return-name'],
			'phone'           => $post_request_params['exp-return-phone'],
			'alternate_phone' => $post_request_params['exp-return-alt-phone'] ?? '',
			'address_line_1'  => $post_request_params['exp-return-line-1'],
			'address_line_2'  => $post_request_params['exp-return-line-2'],
			'pincode'         => $post_request_params['exp-return-pincode'],
			'w3w_code'        => $post_request_params['exp-return-w3w-number'],
			'city'            => $post_request_params['exp-return-city'],
			'state'           => $post_request_params['exp-return-state'],
			'country'         => $post_request_params['exp-return-country'],
		),
	);

	
	$data_to_send_array['returnAddress'] = array(
		'name'            => $post_request_params['return-name'],
		'phone'           => $post_request_params['return-phone'],
		'alternate_phone' => $post_request_params['return-alt-phone'] ?? '',
		'address_line_1'  => $post_request_params['return-line-1'],
		'address_line_2'  => $post_request_params['return-line-2'],
		'pincode'         => $post_request_params['return-pincode'],
		'w3w_code'        => $post_request_params['return-w3w-number'],
		'city'            => $post_request_params['return-city'],
		'state'           => $post_request_params['return-state'],
		'country'         => $post_request_params['return-country'],
	);
	

	$data_to_send_json = wp_json_encode( $data_to_send_array );

	$args        = array(
		'body'        => $data_to_send_json,
		'timeout'     => '10',
		'redirection' => '10',
		'httpversion' => '1.0',
		'blocking'    => true,
		'headers'     => $headers,
	);
	$request_url = shipsy_get_endpoint( 'UPDATE_ADDRESS_API' );
	$request     = shipsy_get_request_handler();
	$response    = $request->post( $request_url, $args );
	$result      = wp_remote_retrieve_body( $response );
	$array2      = json_decode( $result, true );

	$notifications         = array();
	$notifications['page'] = 'shipsy-setup';
	if ( is_array( $array2 ) ) {
		if ( array_key_exists( 'success', $array2 ) ) {
			if ( $array2['success'] ) {
				$notifications['success'] = 'Setup is Successful';
			}
		} else {
			$notifications['failure'] = $array2['error']['message'];
		}
	}
	wp_safe_redirect( add_query_arg( $notifications, admin_url( 'admin.php' ) ) );

}

/**
 * Function to save/update option.
 *
 * @param string $option_name Name of option to save/update.
 * @param mixed  $option_value Value of option to save/update.
 * @return void
 */
function shipsy_set_option( string $option_name, $option_value ) {
	global $wpdb;
	$exists     = ! shipsy_check_option_exists( $option_name );
	$table_name = $wpdb->prefix . 'options';

	// phpcs:disable
	$option_name = 'shipsy_' . $option_name;
	if ( $exists ) {
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO `$table_name` (option_name, option_value) VALUES (%s, %s)",
				array( $option_name, $option_value )
			)
		);

	} else {
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE `$table_name` SET option_value=%s WHERE option_name=%s",
				array( $option_value, $option_name )
			)
		);
	}
	// phpcs:enable
}

/**
 * Function to fetch option value.
 *
 * @param string $option_name Name of option to fetch value.
 * @return string|null
 */
function shipsy_get_option( string $option_name ): ?string {
	global $wpdb;
	$table_name  = $wpdb->prefix . 'options';
	$option_name = 'shipsy_' . $option_name;

	// phpcs:disable
	return $wpdb->get_var(
		$wpdb->prepare(
			"SELECT `option_value` FROM `$table_name` WHERE option_name=%s",
			$option_name
		)
	);
	// phpcs:enable
}

/**
 * Function to check if option exists.
 *
 * @param string $option_name Name of option to fetch value.
 * @return boolean
 */
function shipsy_check_option_exists( string $option_name ): ?string {
	global $wpdb;
	$table_name  = $wpdb->prefix . 'options';
	$option_name = 'shipsy_' . $option_name;

	// phpcs:disable
	$name = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT `option_name` FROM `$table_name` WHERE option_name=%s",
			$option_name
		)
	);

	return ! is_null( $name );
	// phpcs:enable
}

/**
 * Function to delete option.
 *
 * @param string $option_name Name of option to remove.
 * @return bool
 */
function shipsy_remove_option( string $option_name ): bool {
	global $wpdb;
	$table_name = $wpdb->prefix . 'options';

	if ( shipsy_get_option( $option_name ) ) {
		$option_name = 'shipsy_' . $option_name;

		// phpcs:disable
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM `$table_name` WHERE option_name=%s",
				$option_name
			)
		);
		// phpcs:enable
		return true;
	}
	return false;
}

/**
 * Function to save cookie.
 *
 * @param string $cookie_name Name of cookie.
 * @param mixed  $cookie_value Value of cookie.
 * @param mixed  $ttl TTL for cookie.
 *
 * @return void
 */
function shipsy_set_cookie( string $cookie_name, $cookie_value, $ttl ) {
	setcookie( $cookie_name, $cookie_value, $ttl );
}

/**
 * Function to fetch cookie.
 *
 * @param string $cookie_name Name of the cookie whose value to find.
 * @return string|null
 */
function shipsy_get_cookie( string $cookie_name ): ?string {
	return isset( $_COOKIE[ $cookie_name ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) ) : null;
}

/**
 * Function to remove cookie.
 *
 * @param string $key The key for which to remove cookie.
 * @return boolean
 */
function shipsy_remove_cookie( string $key ): bool {
	if ( isset( $_COOKIE[ $key ] ) ) {
		unset( $_COOKIE[ $key ] );
		setcookie( $key, null );
		return true;
	}
	return false;
}

/**
 * Function to set cookie and option.
 *
 * @param string $setting_name Name of cookie and option (without prefix).
 * @param mixed  $setting_value Value to store.
 * @return void
 */
function shipsy_set_setting( string $setting_name, $setting_value ) {
	shipsy_set_cookie( $setting_name, $setting_value, shipsy_get_cookie_ttl() );
	shipsy_set_option( $setting_name, $setting_value );
}

/**
 * Function to get value from cookie or option.
 *
 * @param string $setting_name Name of setting.
 * @return string|null
 */
function shipsy_get_setting( string $setting_name ): ?string {
	$setting_value = shipsy_get_cookie( $setting_name );
	if ( is_null( $setting_value ) ) {
		$setting_value = shipsy_get_option( $setting_name );
	}
	return $setting_value;
}

/**
 * Function to delete setting from cookie and option.
 *
 * @param string $key Name of setting to delete.
 * @return void
 */
function shipsy_remove_setting( string $key ) {
	if ( shipsy_get_cookie( $key ) ) {
		shipsy_remove_cookie( $key );
	}
	if ( shipsy_get_option( $key ) ) {
		shipsy_remove_option( $key );
	}
}

/**
 * Function to get shop url.
 *
 * @return string|void
 */
function shipsy_get_shop_url() {
	return get_bloginfo( 'wpurl' );
}

/**
 * Function to save synced order details to DB.
 *
 * @param array $data The order details to write in db.
 * @return void
 */
function shipsy_add_sync_track( array $data ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'sync_track_order';

	// phpcs:disable
	$wpdb->query(
		$wpdb->prepare(
			"INSERT INTO `$table_name` (orderId, shipsy_refno) VALUES (%s, %s)",
			array( $data['orderId'], $data['shipsy_refno'] )
		)
	);
	// phpcs:enable
}

/**
 * Function to get reference number from DB.
 *
 * @param string $order_id The order id for which to fetch details from db.
 * @return string|null
 */
function shipsy_get_ref_no( string $order_id ): ?string {
	global $wpdb;
	$table_name = $wpdb->prefix . 'sync_track_order';

	// phpcs:disable
	return $wpdb->get_var(
		$wpdb->prepare(
			"SELECT `shipsy_refno` FROM `$table_name` WHERE orderId=%s",
			$order_id
		)
	);
	// phpcs:enable
}

/**
 * Function to get tracking url stored in db.
 *
 * @param string $order_id The order id for which to fetch tracking url from db.
 * @return string|null
 */
function shipsy_get_tracking_url( string $order_id ): ?string {
	global $wpdb;
	$table_name = $wpdb->prefix . 'sync_track_order';

	// phpcs:disable
	return $wpdb->get_var(
		$wpdb->prepare(
			"SELECT `track_url` FROM `$table_name` WHERE orderId=%s",
			$order_id
		)
	);
}

/**
 * Function to check if consignment was synced or not.
 *
 * @param string $order_id The order id for which to check.
 * @return string|null
 */
function shipsy_check_consignment_synced( string $order_id ): ?string {
	global $wpdb;
	$table_name = $wpdb->prefix . 'sync_track_order';

	// phpcs:disable
	return $wpdb->get_var(
		$wpdb->prepare(
			"SELECT `orderId` FROM `$table_name` WHERE `orderId`=%s",
			$order_id
		),
	);
	// phpcs:enable
}

/**
 * Function to get synced consignment details.
 *
 * @param string $order_id The order id for which to check.
 * @return object|null
 */
function shipsy_get_synced_consignment( string $order_id ): ?object {
	global $wpdb;
	$table_name = $wpdb->prefix . 'sync_track_order';

	// phpcs:disable
	return $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM `$table_name` WHERE `orderId`=%s",
			$order_id
		),
	);
	// phpcs:enable
}

/**
 * Function to get consignments to sync.
 *
 * @return array|object|stdClass[]|null
 */
function shipsy_get_consignments_to_sync() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'sync_track_order';

	// phpcs:disable
	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT `orderId` FROM `$table_name` WHERE `status`=%s",
			'Softdata Upload'
		),
	);
	// phpcs:enable
}

/**
 * Function to add consignments to sync.
 *
 * @param string $order_id The order id for which to add.
 * @return string|null
 */
function shipsy_add_consignment_to_sync( string $order_id ): ?string {
	global $wpdb;
	$table_name = $wpdb->prefix . 'sync_track_order';

	if ( ! shipsy_check_consignment_synced( $order_id ) ) {
		// phpcs:disable
		return $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO `$table_name` (orderId, status) VALUES (%s, %s)",
				array( $order_id, 'Softdata Upload' )
			)
		);
	}
	else if( 'Sync Failed' === shipsy_get_synced_consignment( $order_id )->status ) {
		return $wpdb->query(
			$wpdb->prepare(
				"UPDATE `$table_name` SET status=%s WHERE orderId=%s",
				array( 'Softdata Upload', $order_id )
			)
		);
		// phpcs: enable
	}
	return false;
}

/**
 * Function to update synced consignment.
 *
 * @param string $order_id The order id for which to update.
 * @param string $ref_no The reference no for that consignment.
 * @param string $sync_status The status of synced consignment.
 * @param string $sync_comment The message got in sync response.
 *
 * @return string|null
 */
function shipsy_update_synced_consignment( string $order_id, string $ref_no, string $sync_status, string $sync_comment ): ?string {
	global $wpdb;
	$table_name = $wpdb->prefix . 'sync_track_order';

	if ( shipsy_check_consignment_synced( $order_id ) ) {
		if ( strlen( $ref_no ) === 0 ) {
			if ( ! is_null( shipsy_get_ref_no( $order_id ) ) ) {
				$ref_no = shipsy_get_ref_no( $order_id );
			} else {
				$ref_no = '';
			}
		}

		// phpcs:disable
		return $wpdb->query(
			$wpdb->prepare(
				"UPDATE `$table_name` SET shipsy_refno=%s, status=%s, comment=%s WHERE orderId=%s",
				array( $ref_no, $sync_status, $sync_comment, $order_id )
			)
		);
		// phpcs:enable
	}
	return false;
}

/**
 * Function to add synced consignment.
 *
 * @param string $order_id The order id for which to update.
 * @param string $ref_no The reference no for that consignment.
 * @param string $sync_status The status of synced consignment.
 * @param string $sync_comment The message got in sync response.
 *
 * @return string|null
 */
function shipsy_add_synced_consignment( string $order_id, string $ref_no, string $sync_status, string $sync_comment = '' ): ?string {
	global $wpdb;
	$table_name = $wpdb->prefix . 'sync_track_order';

	// phpcs:disable
	return $wpdb->query(
		$wpdb->prepare(
			"INSERT INTO `$table_name` (orderId, shipsy_refno, status, comment) VALUES (%s, %s, %s, %s)",
			array( $order_id, $ref_no, $sync_status, $sync_comment )
		)
	);
	// phpcs:enable
}

/**
 * Function to remove added consignment if exception.
 *
 * @param string $order_id The order id for which to rollback.
 * @return string|null
 */
function shipsy_rollback_pending_consignment( string $order_id ): ?string {
	global $wpdb;
	$table_name = $wpdb->prefix . 'sync_track_order';

	if ( ! shipsy_check_consignment_synced( $order_id ) ) {
		// phpcs:disable
		return $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM `$table_name` where orderId=%s",
				$order_id
			)
		);
		// phpcs:enable
	}
	return false;
}

/**
 * Function to get successfully synced orders.
 *
 * @return array|object|stdClass[]|null
 */
function shipsy_get_successfully_synced_consignments(): ?array {
	global $wpdb;
	$table_name = $wpdb->prefix . 'sync_track_order';

	$invalid_consignment_status = array( 'Softdata Upload', 'Not Synced', 'Sync Failed', 'Cancelled', 'Delivered' );
	$placeholders               = substr( str_repeat( '%s,', count( $invalid_consignment_status ) ), 0, -1 );

	// phpcs:disable
	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT `orderId` from `$table_name` WHERE status NOT IN ( $placeholders )",
			$invalid_consignment_status
		)
	);
	// phpcs:enable
}

/**
 * Function to add tracking url for an order in db.
 *
 * @param string $order_id The order id for which to add tracking url.
 * @return bool
 */
function shipsy_add_tracking_url( string $order_id ): bool {
	global $wpdb;
	$headers            = array(
		'Content-Type'    => 'application/json',
		'organisation-id' => shipsy_get_cookie( 'org_id' ),
		'shop-origin'     => 'wordpress',
		'shop-url'        => shipsy_get_shop_url(),
		'customer-id'     => shipsy_get_cookie( 'cust_id' ),
		'access-token'    => shipsy_get_cookie( 'access_token' ),
	);
	$data['cust_refno'] = $order_id;
	$data_to_send_json  = wp_json_encode( array( 'customerReferenceNumberList' => array( $data['cust_refno'] ) ) );
	$args               = array(
		'body'        => $data_to_send_json,
		'timeout'     => '10',
		'redirection' => '10',
		'httpversion' => '1.0',
		'blocking'    => true,
		'headers'     => $headers,
	);
	$request            = shipsy_get_request_handler();
	$request_url        = shipsy_get_endpoint( 'TRACKING_API' );
	$response           = $request->post( $request_url, $args );
	$result             = wp_remote_retrieve_body( $response );
	$array2             = json_decode( $result, true );
	if ( ! empty( $array2['data'] ) && $array2['success'] ) {
		$table_name = $wpdb->prefix . 'sync_track_order';

		$track_url = $array2['data'][ $order_id ];
		// phpcs:disable
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE `$table_name` SET track_url=%s WHERE orderId=%s",
				array( $track_url, $order_id )
			)
		);
		// phpcs:enable
		return true;
	} else {
		return false;
	}
}

/**
 * Function for bulk label download.
 *
 * @param array $order_ids The order ids for which to download labels.
 * @return array|void
 */
function shipsy_bulk_label_download( array $order_ids ) {
	// TODO: Can we turn this function into an internal API?
	require SHIPSY_ECONNECT_PATH . 'config/settings.php';

	$order_ids = shipsy_clean_order_ids( $order_ids );

	$ref_nos = array();

	foreach ( $order_ids as $order_id ) {
		$ref_nos[] = shipsy_get_ref_no( sanitize_text_field( $order_id ) );
	}

	$headers = array(
		'Content-Type'    => 'application/json',
		'organisation-id' => shipsy_get_cookie( 'org_id' ),
		'shop-origin'     => 'wordpress',
		'shop-url'        => shipsy_get_shop_url(),
		'customer-id'     => shipsy_get_cookie( 'cust_id' ),
		'access-token'    => shipsy_get_cookie( 'access_token' ),
	);

	$data_to_send_json = wp_json_encode(
		array(
			'consignmentIds'    => $ref_nos,
			'isReferenceNumber' => true,
		)
	);
	$args              = array(
		'body'        => $data_to_send_json,
		'timeout'     => '50',
		'redirection' => '50',
		'httpversion' => '1.0',
		'blocking'    => true,
		'headers'     => $headers,
	);

	// Ignore all caps casing.
	$request_url = shipsy_get_endpoint('BULK_LABEL_API');    // phpcs:ignore
	$request     = shipsy_get_request_handler();
	$response    = $request->post( $request_url, $args );
	$result      = json_decode( $response['body'], true );

	$notifications              = array();
	$notifications['post_type'] = 'shop_order';

	if ( $result && array_key_exists( 'error', $result ) ) {
		if ( array_key_exists( 'message', $result['error'] ) ) {
			$notifications['failure'] = $result['error']['message'];
		} else {
			$notifications['failure'] = 'Cannot fetch labels, please try again later';
		}
	} else {
		try {
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
			$wpfs = new WP_Filesystem_Direct( null );

			$upload_dir = wp_upload_dir();
			$dir_path   = $upload_dir['basedir'] . '/shipsy-labels-dir';

			if ( ! empty( $upload_dir['basedir'] ) ) {
				if ( ! file_exists( $dir_path ) ) {
					wp_mkdir_p( $dir_path );
				}
			}

			$pdf_path = $dir_path . '/shipsy-labels.pdf';
			$wpfs->delete( $pdf_path, false, 'f' );
			$wpfs->put_contents( $pdf_path, $response['body'], 0777 );

			$notifications['success'] = 'Successfully fetched labels for orders: ' . implode( ', ', $order_ids );
			$url                      = SHIPSY_ECONNECT_URL . 'assets/pdf/shipsy-labels.pdf';
			$redirect                 = add_query_arg( $notifications, admin_url( 'edit.php' ) );

			/*
			 * TODO: Can there be some better way to handle this. That is can we open download in new tab and also
			 *	     redirect the current page with the respective message
			 */

			header( 'Content-type: application/x-file-to-save' );
			header( 'Content-Disposition: attachment; filename=' . basename( $url ) );
			readfile( $pdf_path ); // phpcs:ignore
			die;

		} catch ( Exception $ex ) {
			$notifications['failure'] = $ex->getMessage();
		}
	}
	return $notifications;
}

/**
 * Function to convert string of order_ids to array( order_id ).
 *
 * @param mixed $order_id String of order id(s).
 * @return array
 */
function shipsy_clean_order_ids( $order_id ): array {

	if ( is_array( $order_id ) ) {
		$orders = array();
		foreach ( $order_id as $id ) {
			$orders[] = sanitize_text_field( wp_unslash( $id ) );
		}
		$order_id = $orders;
	} else {
		$order_id = array( sanitize_text_field( wp_unslash( $order_id ) ) );
	}

	return $order_id;
}

/**
 * Function to get BASE_URL
 * @return string
 */
function shipsy_get_base_url($org_id)
{
		require SHIPSY_ECONNECT_PATH . 'config/settings.php';
        $headers = array(
                'organisation-pretty-name' => $org_id
        );
        $args = array(
                'timeout' => '10',
                'redirection' => '10',
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => $headers,
        );
        $request = shipsy_get_request_handler();
        $request_url = 'https://centraldbrepo-ap-south-1.shipsy.io/deployment-urls/v1/fetch';
        $response = $request->get($request_url, $args);
        $result = wp_remote_retrieve_body($response);
        $array = json_decode($result, true);
        $base_url = isset($array['data']) && isset($array['data']['px_api_url']) ? $array['data']['px_api_url'] : $BASE_URL;
        return $base_url;

}


/**
 * Slugify a string.
 *
 * @param string $text String to slugify.
 * @return string
 */
function shipsy_slugify( string $text ): string {
	// Strip html tags.
	$text = wp_strip_all_tags( $text );
	// Replace non letter or digits by -.
	$text = preg_replace( '~[^\pL\d]+~u', '-', $text );
	// Transliterate.
	setlocale( LC_ALL, 'en_US.utf8' );
	$text = iconv( 'utf-8', 'us-ascii//TRANSLIT', $text );
	// Remove unwanted characters.
	$text = preg_replace( '~[^-\w]+~', '', $text );
	// Trim.
	$text = trim( $text, '-' );
	// Remove duplicate -.
	$text = preg_replace( '~-+~', '-', $text );
	// Lowercase.
	$text = strtolower( $text );
	// Check if it is empty.
	if ( empty( $text ) ) {
		return 'n-a';
	}
	// Return result.
	return $text;
}



