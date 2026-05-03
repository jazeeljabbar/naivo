<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Output one term from Grid listing
 */

global $us_grid_listing_post_atts, $us_grid_term;
$columns = $us_grid_listing_post_atts['columns'];
$type = $us_grid_listing_post_atts['type'];
$grid_layout_settings = $us_grid_listing_post_atts['grid_layout_settings'];
$load_animation = $us_grid_listing_post_atts['load_animation'];
$overriding_link = $us_grid_listing_post_atts['overriding_link'];

$term_atts = array(
	'class' => 'w-grid-item type_term term-' . $us_grid_term->term_id . ' term-' . $us_grid_term->slug,
);

// Add items appearance animation on loading
if ( $load_animation !== 'none' AND ! us_amp() ) {
	$term_atts['class'] .= ' us_animate_' . $load_animation;

	// We need to hide CSS animation before isotope.js initialization
	if ( $type === 'masonry' AND $columns > 1 ) {
		$post_atts['class'] .= ' off_autostart';
	}

	// Set "animation-delay" for every doubled amount of columns
	if ( $columns > 1 ) {
		global $us_grid_item_counter;
		$term_atts['style'] = sprintf( 'animation-delay:%ss', 0.1 * $us_grid_item_counter );

		// Calcualte columns factor for better population on single screen
		if ( $columns >= 7 ) {
			$columns_factor = 4;
		} elseif ( $columns >= 5 ) {
			$columns_factor = 3;
		} else {
			$columns_factor = 2;
		}

		if ( ( $us_grid_item_counter + 1 ) < $columns * $columns_factor ) {
			$us_grid_item_counter++;
		} else {
			$us_grid_item_counter = 0;
		}
	}
}

// Aspect ratio class
if ( us_arr_path( $grid_layout_settings, 'default.options.ratio' ) ) {
	$term_atts['class'] .= ' ratio_' . us_arr_path( $grid_layout_settings, 'default.options.ratio' );
}

// Generate background property based on image and color
$bg_img_source = us_arr_path( $grid_layout_settings, 'default.options.bg_img_source' );

// Check if image source is set and it's not from Media Library (cause it's set in listing-start.php)
if ( ! in_array( $bg_img_source, array( 'none', 'media' ) ) ) {

	$bg_file_size = us_arr_path( $grid_layout_settings, 'default.options.bg_file_size', 'full' );

	// Get Product Category thumbnail
	if ( $bg_img_source == 'featured' ) {
		if (
			$us_grid_term->taxonomy == 'product_cat'
			AND $term_thumbnail_id = get_term_meta( $us_grid_term->term_id, 'thumbnail_id', TRUE )
		) {
			$bg_img_url = wp_get_attachment_image_url( $term_thumbnail_id, $bg_file_size );
		}

		// Custom Field image source
	} elseif ( $postID = get_the_ID() ) {
		$bg_img_url = wp_get_attachment_image_url( get_term_meta( $us_grid_term->term_id, $bg_img_source, TRUE ), $bg_file_size );
	}

	// ACF Options image source
	if (
		empty( $bg_img_url )
		AND function_exists( 'us_acf_get_field_object' )
		AND $acf_field = us_acf_get_field_object( $bg_img_source )
	) {
		if ( us_arr_path( $acf_field, 'return_format', 'id' ) === 'array' ) {
			$_attachment_id = us_arr_path( $acf_field, 'value.ID', '' );
		} else {
			$_attachment_id = us_arr_path( $acf_field, 'value', '' );
		}
		if ( is_numeric( $_attachment_id ) ) {
			$bg_img_url = wp_get_attachment_image_url( $_attachment_id, $bg_file_size );

			// for "image url" return format
		} else {
			$bg_img_url = (string) $_attachment_id;
		}
	}

	// If the image exists, combine it with other background properties
	if ( ! empty( $bg_img_url ) ) {
		$background_value = 'url(' . $bg_img_url . ') ';
		$background_value .= us_arr_path( $grid_layout_settings, 'default.options.bg_img_position' );
		$background_value .= '/';
		$background_value .= us_arr_path( $grid_layout_settings, 'default.options.bg_img_size' );
		$background_value .= ' ';
		$background_value .= us_arr_path( $grid_layout_settings, 'default.options.bg_img_repeat' );

		$bg_color = usof_meta( 'us_tile_bg_color' )
			? usof_meta( 'us_tile_bg_color' )
			: us_arr_path( $grid_layout_settings, 'default.options.color_bg' );

		$bg_color = us_get_color( $bg_color, /* Gradient */ TRUE );

		// If the color value contains gradient, add comma for correct appearance
		if ( strpos( $bg_color, 'gradient' ) !== FALSE ) {
			$background_value .= ',';
		}
		$background_value .= ' ' . $bg_color;
	}
}

if ( empty( $background_value ) ) {
	$background_value = usof_meta( 'us_tile_bg_color' );
	$background_value = us_get_color( $background_value, /* Gradient */ TRUE );
}

$color_value = usof_meta( 'us_tile_text_color' );

// Custom colors for Portfolio Pages
$inline_css = us_prepare_inline_css(
	array(
		'background' => $background_value,
		'color' => us_get_color( $color_value ),
	)
);

// Generate Overriding Link semantics to the whole grid item
$link_url = $link_atts = '';
$link_title = FALSE;

if ( $overriding_link == 'post' OR $overriding_link == 'popup_post' ) {

	$link_url = apply_filters( 'the_permalink', get_term_link( $us_grid_term ) );
	$link_atts .= ' rel="bookmark"';

} elseif ( $overriding_link == 'popup_post_image' ) {

	$tnail_id = get_term_meta( $us_grid_term->term_id, 'thumbnail_id', TRUE );
	$link_url = wp_get_attachment_image_url( $tnail_id, 'full' );
	if ( $link_url ) {
		// Use the Caption as a Title
		$attachment = get_post( $tnail_id );
		$img_title = trim( strip_tags( $attachment->post_excerpt ) );
		if ( empty( $img_title ) ) {
			// If not, Use the Alt
			$img_title = trim( strip_tags( get_post_meta( $attachment->ID, '_wp_attachment_image_alt', TRUE ) ) );
		}
		if ( empty( $img_title ) ) {
			// If no Alt, use the Term name
			$img_title = $us_grid_term->name;
		}
		$link_atts .= ' ref="magnificPopupGrid" title="' . esc_attr( $img_title ) . '"';
		$link_title = TRUE;
	}
}

// Add aria-label if "title" attribute is absent for accessibility support
if ( ! $link_title ) {
	$link_atts .= ' aria-label="' . esc_attr( strip_tags( $us_grid_term->name ) ) . '"';
}

// Apply theme filter
$term_atts['class'] = apply_filters( 'us_grid_item_classes', $term_atts['class'], $us_grid_term->term_id );

ob_start();
?>
	<div<?= us_implode_atts( $term_atts ) ?>>
		<div class="w-grid-item-h"<?= $inline_css ?>>
			<?php if ( $link_url ): ?>
				<a class="w-grid-item-anchor" href="<?= esc_url( $link_url ) ?>"<?= $link_atts ?>></a>
			<?php endif; ?>
			<?php us_output_builder_elms( $grid_layout_settings, 'default', 'middle_center', 'grid', 'term' ); ?>
		</div>
	</div>
<?php
echo apply_filters( 'us_grid_listing_term', ob_get_clean() );
