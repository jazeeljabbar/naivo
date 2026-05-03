<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Advanced Custom Fields
 *
 * @link https://www.advancedcustomfields.com/
 *
 * TODO: Globally replace the architecture of storing and working fields,
 * use an identifier instead of a name, since now there is a problem if fields in
 * different groups have the same name does not work correctly.
 */

if ( ! class_exists( 'ACF' ) ) {
	return;
}

// Register Google Maps API key
// https://www.advancedcustomfields.com/resources/google-map/
if ( ! function_exists( 'us_acf_google_map_api' ) ) {
	function us_acf_google_map_api( $api ) {
		// Get the Google Maps API key from the Theme Options
		$gmaps_api_key = trim( esc_attr( us_get_option('gmaps_api_key', '') ) );
		/*
		 * Set the API key for ACF only if it is not empty,
		 * to prevent possible erase of the same value set in other plugins
		 */
		if ( ! empty( $gmaps_api_key ) ) {
			$api['key'] = $gmaps_api_key;
		}

		return $api;
	}

	add_filter( 'acf/fields/google_map/api', 'us_acf_google_map_api' );
}

/**
 * Removing custom plugin message for ACF Pro
 */
if ( ! function_exists( 'us_acf_pro_remove_update_message' ) ) {
	function us_acf_pro_remove_update_message() {
		if (
			function_exists( 'acf_get_setting' )
			AND $acf_basename = acf_get_setting( 'basename' )
		) {
			// Since action for plugin is added via class member function,
			// removing not one specific action but all actions for the ACF Pro plugin update message
			remove_all_actions( 'in_plugin_update_message-' . $acf_basename );
		}
	}

	add_action( 'init', 'us_acf_pro_remove_update_message', 30 );
}

if ( ! function_exists( 'us_acf_get_fields' ) ) {
	/**
	 * Get a list of all fields.
	 *
	 * @param string|array $types The field types to get.
	 * @param bool $to_list If a list is given, the result will be [ group => [ key => value ] ].
	 * @param string $separator The separator for the "option" prefix, example: `option{separator}name`.
	 * @return array Returns a list of fields.
	 */
	function us_acf_get_fields( $types = array(), $to_list = FALSE, $separator = '|' ) {

		if ( ! is_array( $types ) AND ! empty( $types ) ) {
			$types = array( $types );
		}
		$result = array();

		// Bypass all field groups.
		foreach ( (array) acf_get_field_groups() as $group ) {

			/**
			 * Add the field prefix, if the group is used in ACF Options page.
			 * @link https://www.advancedcustomfields.com/resources/get-values-from-an-options-page/
			 */
			$options_prefix = '';
			if ( is_array( $group['location'] ) ) {
				foreach ( $group['location'] as $location_or ) {
					foreach ( $location_or as $location_and ) {
						if ( $location_and['param'] === 'options_page' AND $location_and['operator'] === '==' ) {
							$options_prefix = 'option' . $separator;
							break 2;
						}
					}
				}
			}

			// Get all the fields of the group and generating the result.
			$fields = array();
			foreach( (array) acf_get_fields( $group['ID'] ) as $field ) {

				// If types are given and the field does not correspond
				// to one type, then skip this field.
				if (
					! empty( $types )
					AND is_array( $types )
					AND ! in_array( $field['type'], $types )
				) {
					continue;
				}

				// If there is a prefix, then add all the field names.
				if ( $options_prefix ) {
					$field['name'] = $options_prefix . $field['name'];
				}

				// If the list format is specified, then we will form the list.
				if ( $to_list ) {
					$fields[ $field['name'] ] = $field['label'];
				} else {
					$fields[] = $field;
				}
			}

			if ( count( $fields ) ) {
				// This is the full name of the group that can be used for output in dropdowns or other controls
				$result[ $group['ID'] ] = array( '__group_label__' => $group['title'] );
				$result[ $group['ID'] ] += $fields;
			}
		}
		return $result;
	}
}

if ( ! function_exists( 'us_acf_get_field_object' ) ) {
	/**
	 * Returns an array containing all the field data for a given field_name
	 *
	 * @param string $selector The field name or key.
	 * @param mixed $post_id The post_id of which the value is saved against.
	 * @return bool|array
	 */
	function us_acf_get_field_object( $selector, $post_id = FALSE ) {
		if ( preg_match( '/^option(\/|\|)/', $selector, $matches ) ) {
			$selector = substr( $selector, strlen( $matches[0] ) );
			$post_id = 'option';
		}
		return get_field_object( $selector, $post_id );
	}
}
