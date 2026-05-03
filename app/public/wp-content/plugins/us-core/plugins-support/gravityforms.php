<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Gravity Forms support
 *
 * @link http://www.gravityforms.com/
 */

if ( ! class_exists( 'GFForms' ) ) {
	return;
}

// Add theme styling
if ( defined( 'US_DEV' ) OR ! us_get_option( 'optimize_assets', 0 ) ) {
	add_action( 'wp_enqueue_scripts', 'us_gforms_add_styles', 14 );
	function us_gforms_add_styles( $styles ) {
		global $us_template_directory_uri;
		$min_ext = defined( 'US_DEV' ) ? '' : '.min';
		wp_enqueue_style( 'us-gravityforms', $us_template_directory_uri . '/common/css/plugins/gravityforms' . $min_ext . '.css', array(), US_THEMEVERSION, 'all' );
	}
}

// Remove plugin's datepicker CSS
if ( ! function_exists( 'us_gforms_remove_styles' ) ) {
	add_action( 'wp_enqueue_scripts', 'us_gforms_remove_styles', 15 );
	function us_gforms_remove_styles() {
		wp_dequeue_style( 'gforms_datepicker_css' );
		wp_deregister_style( 'gforms_datepicker_css' );
	}
}

if ( ! function_exists( 'us_gform_add_design_css_class' ) ) {
	add_filter( 'do_shortcode_tag', 'us_gform_add_design_css_class', 501, 3 );
	/**
	 * Add a custom class to the gform from the design options
	 *
	 * @param string $output The shortcode output
	 * @param string $tag The shortcode tag name
	 * @param array $atts The shortcode attributes array or empty string
	 * @return string Returns a string with the added css class to the first html element of the output
	 */
	function us_gform_add_design_css_class( $output, $tag, $atts ) {
		if ( $tag !== 'gravityform' ) {
			return $output;
		}

		// Get a list of specific classes based on shortcode settings
		if ( $css_classes = (string) us_get_specific_classes_by_shortcode( $atts ) ) {
			return preg_replace( '/(<\S+.*?class=[\"|\'].*?)([\"|\'].*?>)/', '$1 '. $css_classes .'$2', $output, /* limit */1 );
		}
		return $output;
	}
}
