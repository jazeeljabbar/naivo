<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Shortcode: us_btn
 *
 * Dev note: if you want to change some of the default values or acceptable attributes, overload the shortcodes config.
 *
 * @var $shortcode      string Current shortcode name
 * @var $shortcode_base string The original called shortcode name (differs if called an alias)
 * @var $content        string Shortcode's inner content
 * @var $classes        string Extend class names
 *
 * @var $link           string Video link
 * @var $ratio          string Ratio: '16x9' / '4x3' / '3x2' / '1x1'
 * @var $max_width      string Max width in pixels
 * @var $align          string Video alignment: 'left' / 'center' / 'right'
 * @var $css            string Extra css
 * @var $el_id          string element ID
 * @var $el_class       string Extra class name
 * @var $source         string Iframe from custom field
 */

// Check if ACF field is chosen
if (
	isset( $source )
	AND $source !== 'custom'
	AND function_exists( 'us_acf_get_field_object' )
) {
	// If $source contains a "option|" prefix then $_id will be ignored.
	$_id = get_queried_object();
	$acf_field = (array) us_acf_get_field_object( $source, $_id );
	$value = us_arr_path( $acf_field, 'value', '' );

	preg_match_all( '/src="([^"]+)"+/i', $value, $result );

	// If ACF field returns an iframe, get link from value and replace with current
	if ( ! empty( $result[1][0] ) ) {
		$url = explode( '?', $result[1][0] );
		if ( ! empty( $url[0] ) AND filter_var( $url[0], FILTER_VALIDATE_URL ) !== FALSE ) {
			$link = $url[0];
		}

		// in case the ACF field is not iframe and not empty
	} elseif ( $value !== '' ) {
		$link = $value;

		// in other cases do not output the element
	} else {
		return;
	}
}

$_atts['class'] = 'w-video';
$_atts['class'] .= isset( $classes ) ? $classes : '';
$_atts['class'] .= ' align_' . $align;

if ( ! empty( $ratio ) ) {
	$_atts['class'] .= ' ratio_' . $ratio;
}

// When some values are set in Design options, add the specific classes
if ( us_design_options_has_property( $css, 'border-radius' ) ) {
	$_atts['class'] .= ' has_border_radius';
}

if ( ! empty( $el_id ) ) {
	$_atts['id'] = $el_id;
}

// Image Overlay
if ( $overlay_image AND ! us_amp() ) {
	$overlay_image_src = wp_get_attachment_image_url( $overlay_image, 'full' );

	if ( empty( $overlay_image_src ) ) {
		$overlay_image_src = us_get_img_placeholder( 'full', TRUE );
	}
	$_atts['class'] .= ' with_overlay';
	$_atts['style'] = 'background-image:url(' . $overlay_image_src . ');';
}

// Empty embed by default because video can be loaded with JS
$embed_html = '';

// Apply filter
$link = strip_tags( us_replace_dynamic_value( $link ) );

// Check providers
if ( ! empty( $link ) ) {
	foreach ( us_config( 'embeds' ) as $provider => $embed ) {

		// If there is no video ID then skip iteration.
		if (
			! isset( $embed['get_video_id'] )
			OR ! is_callable( $embed['get_video_id'] )
			OR ! $video_id = call_user_func( $embed['get_video_id'], $link )
		) {
			continue;
		}

		// Get a unique ID for an video player.
		$player_id = $provider . '-' . us_uniqid();

		// Get HTML/JS code to init the player.
		$player_html = us_arr_path( $embed, 'player_html', '' );

		if ( ! $overlay_image ) {
			// Get raw iframe markup to show as is
			$embed_html = us_arr_path( $embed, 'iframe_html', '' );
		}

		// Get player vars.
		$player_vars = us_arr_path( $embed, 'player_vars', array() );

		// Apply settings.
		switch ( $provider ) {
			case 'youtube':
				$player_vars = array(
					'origin' => get_site_url(),
					'controls' => (int) ! $hide_controls,
				);
				break;
			case 'vimeo':
				$player_vars = array(
					'byline' => (int) ! $hide_video_title,
					'title' => (int) ! $hide_video_title,
				);
				break;
		}

		// If an overlay is used, then set autoplay.
		if ( $overlay_image AND ! us_amp() ) {
			$player_vars['autoplay'] = 1;
		}

		// Set a playlist for YouTube.
		if ( $provider == 'youtube' AND $overlay_image ) {
			$player_vars['playlist'] = $video_id;
		}

		// Set value to <variable>
		$variables = array(
			'video_id' => $video_id,
			'player_id' => $player_id,
			'player_vars' => json_encode( $player_vars ),
			'player_url_params' => build_query( $player_vars ),
		);

		foreach ( $variables as $variable => $value ) {
			$player_html = str_replace( "<{$variable}>", $value, $player_html );
			$embed_html = str_replace( "<{$variable}>", $value, $embed_html );
		}

		// Export data to JS
		$js_data = array(
			'player_id' => $player_id,
			'player_api' => us_arr_path( $embed, 'player_api', '' ),
			'player_html' => $player_html,
		);

		$_atts['onclick'] = us_pass_data_to_js( $js_data, /* onclicks */ FALSE );

		// One successful iteration is enough.
		break;
	}
}

if ( empty( $_atts['onclick'] ) ) {
	if ( preg_match( '/^.*\.(mp4|m4v|webm|ogv|flv)$/i', $link ) ) {
		$embed_html = '<video controls preload="auto">';
		$video_ext = 'mp4'; //use mp4 as default extension
		$file_path_info = pathinfo( $link );
		if ( isset( $file_path_info['extension'] ) ) {
			if ( in_array( $file_path_info['extension'], array( 'ogg', 'ogv' ) ) ) {
				$video_ext = 'ogg';
			} elseif ( $file_path_info['extension'] == 'webm' ) {
				$video_ext = 'webm';
			}
		}
		$embed_html .= '<source type="video/' . $video_ext . '" src="' . $link . '" />';
		$embed_html .= '</video>';

		$js_data = array(
			'player_html' => $embed_html,
		);
	} else {
		global $wp_embed;
		// Using the default WordPress way
		$embed_html = do_shortcode( $wp_embed->run_shortcode( '[embed]' . $link . '[/embed]' ) );
		$js_data = array(
			'player_html' => $embed_html,
		);
	}

	$_atts['onclick'] = us_pass_data_to_js( $js_data, FALSE );
}

if ( ! $overlay_image ) {
	unset( $_atts['onclick'] );
}

$output = '<div' . us_implode_atts( $_atts ) . '>';
$output .= '<div class="w-video-h">' . $embed_html . '</div>';

// Add play icon in output
if ( $overlay_icon AND ! us_amp() ) {
	$tag_style = '';
	if ( ! empty( $overlay_icon_size ) ) {
		$tag_style .= 'font-size:' . esc_attr( $overlay_icon_size ) . ';';
	}
	if ( ! empty( $overlay_icon_bg_color ) ) {
		$tag_style .= 'background:' . us_get_color( $overlay_icon_bg_color, /* Gradient */ TRUE ) . ';';
	}
	if ( ! empty( $overlay_icon_text_color ) ) {
		$tag_style .= 'color:' . us_get_color( $overlay_icon_text_color );
	}
	$output .= '<div class="w-video-icon" style="' . $tag_style . '"></div>';
}
$output .= '</div>';

echo $output;
