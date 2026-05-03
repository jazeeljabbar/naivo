<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Configuration for shortcode: btn
 */

$misc = us_config( 'elements_misc' );
$design_options_params = us_config( 'elements_design_options' );
$hover_options_params = us_config( 'elements_hover_options' );
$link_custom_values = us_get_elm_link_options();

/**
 * @return array
 */
return array(
	'title' => __( 'Button', 'us' ),
	'icon' => 'fas fa-hand-pointer',
	'category' => __( 'Basic', 'us' ),
	'admin_enqueue_js' => US_CORE_URI . '/plugins-support/js_composer/js/us_icon_view.js',
	'js_view' => 'ViewUsIcon', // used in WPBakery editor
	'params' => us_set_params_weight(

		// General section
		array(
			'label' => array(
				'title' => __( 'Button Label', 'us' ),
				'type' => 'text',
				'std' => __( 'Click Me', 'us' ),
				'holder' => 'button',
				'usb_preview' => array(
					'attr' => 'html',
					'elm' => '.w-btn-label',
				),
			),
			'link_type' => array(
				'title' => us_translate( 'Link' ),
				'type' => 'select',
				'options' => array_merge(
					array(
						'none' => us_translate( 'None' ),
						'post' => __( 'To a Post', 'us' ),
						'elm_value' => __( 'Clickable value (email, phone, website)', 'us' ),
						'onclick' => __( 'Onclick JavaScript action', 'us' ),
					),
					$link_custom_values,
					array( 'custom' => __( 'Custom', 'us' ) )
				),
				'std' => 'custom',
				'grid_std' => 'post',
				'usb_preview' => TRUE,
			),
			'link_new_tab' => array(
				'type' => 'switch',
				'switch_text' => us_translate( 'Open link in a new tab' ),
				'std' => 0,
				'classes' => 'for_above',
				'show_if' => array( 'link_type', '!=', array( 'none', 'onclick', 'custom' ) ),
				'usb_preview' => array(
					'toggle_atts' => array(
						'target' => '_blank',
						'rel' => 'noopener nofollow',
					),
					'elm' => '.w-btn',
				),
			),
			'link' => array(
				'placeholder' => us_translate( 'Enter the URL' ),
				'type' => 'link',
				'std' => array( 'url' => '#' ),
				'shortcode_std' => '#',
				'classes' => 'for_above desc_3',
				'show_if' => array( 'link_type', '=', 'custom' ),
				'usb_preview' => TRUE,
			),
			'onclick_code' => array(
				'type' => 'text',
				'std' => 'return false',
				'classes' => 'for_above',
				'show_if' => array( 'link_type', '=', 'onclick' ),
				'usb_preview' => array(
					'attr' => 'onclick',
					'elm' => '.w-btn',
				),
			),
			'style' => array(
				'title' => us_translate( 'Style' ),
				'description' => $misc['desc_btn_styles'],
				'type' => 'select',
				'options' => us_get_btn_styles(),
				'std' => '1',
				'usb_preview' => array(
					'mod' => 'us-btn-style',
					'elm' => '.w-btn',
				),
			),
			'align' => array(
				'title' => us_translate( 'Alignment' ),
				'type' => 'radio',
				'labels_as_icons' => 'fas fa-align-*',
				'options' => array(
					'none' => us_translate( 'Default' ),
					'left' => us_translate( 'Left' ),
					'center' => us_translate( 'Center' ),
					'right' => us_translate( 'Right' ),
					'justify' => __( 'Stretch to the full width', 'us' ),
				),
				'std' => 'none',
				'context' => array( 'shortcode' ),
				'is_responsive' => TRUE,
				'usb_preview' => TRUE,
			),
			'icon' => array(
				'title' => __( 'Icon', 'us' ),
				'type' => 'icon',
				'std' => '',
				'usb_preview' => TRUE,
			),
			'iconpos' => array(
				'title' => __( 'Icon Position', 'us' ),
				'type' => 'radio',
				'options' => array(
					'left' => __( 'Before text', 'us' ),
					'right' => __( 'After text', 'us' ),
				),
				'std' => 'left',
				'usb_preview' => TRUE,
			),
		),

		$design_options_params,
		$hover_options_params
	),
	'fallback_params' => array(
		'width_type',
	),
);
