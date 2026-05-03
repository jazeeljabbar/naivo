<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Output Button element
 */

global $us_grid_object_type;

// Check existence of Button Style, if not, set the default
$style = us_maybe_get_button_style( $style );

$_atts['class'] = 'w-btn us-btn-style_' . $style;
$_atts['class'] .= isset( $classes ) ? $classes : '';

if ( ! empty( $el_id ) ) {
	$_atts['id'] = $el_id;
}

$wrapper_class = '';
if ( $us_elm_context == 'shortcode' ) {

	// Fallback for versions below 8.11
	if ( ! empty( $width_type ) ) {
		$align = 'justify';
	}

	// Responsive alignment
	if ( $_alignments = (array) us_get_responsive_values( $align ) ) {
		foreach ( $_alignments as $state => $align ) {
			$wrapper_class .= sprintf( ' %s_align_%s', $state, $align );
		}
		
		// Standard alignment
	} else {
		$wrapper_class .= ' align_' . $align;
	}

	// Moving classes `hide_on_*` from the button to the wrapper
	$hide_on_prefix = 'hide_on_';
	if ( strpos( $_atts['class'], $hide_on_prefix ) !== FALSE ) {
		$classes = &$_atts['class'];
		foreach ( (array) us_get_responsive_states( /* only keys */TRUE ) as $state ) {
			$hide_classname = $hide_on_prefix . $state;
			if ( strpos( $classes, $hide_classname ) !== FALSE ) {
				$wrapper_class .= ' ' . $hide_classname;
				$classes = preg_replace( '/\s?' . $hide_classname . '/', '', $classes );
			}
		}
		unset( $classes );
	}
}

// Icon
$icon_html = '';
if ( ! empty( $icon ) ) {
	$icon_html = us_prepare_icon_tag( $icon );
	$_atts['class'] .= ' icon_at' . $iconpos;
}

// Apply filters to button label
$label = us_replace_dynamic_value( $label, $us_elm_context );
$label = trim( strip_tags( $label, '<br>' ) );
$label = wptexturize( $label );

if ( $label === '' ) {
	$_atts['class'] .= ' text_none';
	$_atts['aria-label'] = us_translate( 'Button' );
}

$tag = 'a';
$link_atts = array();

// Link
if ( $link_type === 'none' ) {
	$tag = 'button';

} elseif ( $link_type === 'post' ) {

	// Terms of selected taxonomy in Grid
	if ( $us_elm_context == 'grid' AND $us_grid_object_type == 'term' ) {
		global $us_grid_term;
		$link_atts['href'] = get_term_link( $us_grid_term );
	} else {
		$link_atts['href'] = apply_filters( 'the_permalink', get_permalink() );

		// Force opening in a new tab for "Link" post format
		if ( get_post_format() == 'link' ) {
			$link_atts['target'] = '_blank';
			$link_atts['rel'] = 'noopener';
		}
	}

} elseif ( $link_type === 'elm_value' AND ! empty( $label ) ) {
	if ( is_email( $label ) ) {
		$link_atts['href'] = 'mailto:' . $label;
	} elseif ( strpos( $label, '.' ) === FALSE ) {
		$link_atts['href'] = 'tel:' . $label;
	} else {
		$link_atts['href'] = esc_url( $label );
	}

} elseif ( $link_type === 'custom' ) {
	$link_atts = us_generate_link_atts( $link );

} elseif ( $link_type === 'onclick' ) {
	if ( ! empty( $onclick_code ) ) {
		// If there are errors in custom JS, an error message will be displayed
		// in the console, and this will not break the work of the site.
		$onclick_code = 'try{' . trim( $onclick_code ) . '}catch(e){console.error(e)}';
	} else {
		$onclick_code = 'return false'; // Default value
	}
	// NOTE: On the output, the value is filtered using `esc_attr()`,
	// and there is no need for additional filtering `esc_js()`.
	$link_atts['onclick'] = $onclick_code;
	$link_atts['href'] = '#';

} else {
	$link_atts = us_generate_link_atts( 'url:{{' . $link_type . '}}|||' );
}

// Don't show the button if it has no link
if (
	$link_type !== 'none'
	AND empty( $link_atts['href'] )
	AND ! usb_is_preview_page()
) {
	return;

	// Force "Open in a new tab" attributes
} elseif ( empty( $link_atts['target'] ) AND $link_new_tab ) {
	$link_atts['target'] = '_blank';
	$link_atts['rel'] = 'noopener nofollow';
}

$_atts = $_atts + $link_atts;
$_atts['class'] = trim( $_atts['class'] );

// Output the element
$output = '';

if ( $us_elm_context == 'shortcode' ) {
	$output .= '<div class="w-btn-wrapper' . $wrapper_class . '">';
}

$output .= '<' . $tag . us_implode_atts( $_atts ) . '>';
if ( $iconpos == 'left' ) {
	$output .= $icon_html;
}
if ( $label !== '' ) {
	$output .= '<span class="w-btn-label">' . $label . '</span>';
}
if ( $iconpos == 'right' ) {
	$output .= $icon_html;
}
$output .= '</' . $tag . '>';

if ( $us_elm_context == 'shortcode' ) {
	$output .= '</div>';
}

echo $output;
