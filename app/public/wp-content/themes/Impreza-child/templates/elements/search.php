<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Output search element
 *
 * @var $text           string Placeholder Text
 * @var $layout         string Layout: 'simple' / 'modern' / 'fulwidth' / 'fullscreen'
 * @var $width          int Field width
 * @var $design_options array
 * @var $product_search bool Whether to search for WooCommerce products only
 * @var $classes        string
 * @var $id             string
 */

$classes = isset( $classes ) ? $classes : '';
$classes .= ( ! empty( $el_class ) ) ? ( ' ' . $el_class ) : '';
$classes .= ' layout_' . $layout;
$classes .= ( us_get_option( 'ripple_effect', FALSE ) ) ? ' with_ripple' : '';

$output = '<div class="w-search' . $classes . '">';
if ( $layout == 'fullscreen' ) {
    $output .= '<div class="w-search-background"></div>';
}
$output .= '<a class="w-search-open" href="javascript:void(0);" aria-label="' . us_translate( 'Search' ) . '">';
if ( ! empty( $icon ) ) {
    $output .= us_prepare_icon_tag( $icon );
}
$output .= '</a>';
$output .= '<div class="w-search-form">';
$output .= '<div class="w-form-row for_text">';
$output .= '<div class="w-form-row-field">';

$output .= do_shortcode('[fibosearch]');

$output .= '</div>';


$output .= '<a class="w-search-close" href="javascript:void(0);" aria-label="' . us_translate( 'Close' ) . '"></a>';
$output .= '</div></div></div>';

echo $output;