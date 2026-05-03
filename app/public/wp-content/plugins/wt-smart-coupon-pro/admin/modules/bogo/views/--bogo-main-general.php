<?php
/**
 * BOGO general settings
 *
 * @package    Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$placeholders = array(
	'wbte_sc_bogo_general_discount_apply_message'        => array(
		'{bogo_title}',
	),
	'wbte_sc_bogo_general_product_added_message'         => array(
		'{bogo_title}',
	),
	'wbte_sc_bogo_general_cheap_exp_added_message'       => array(
		'{bogo_title}',
		'{qty}',
	),
	'wbte_sc_bogo_general_discount_under_product_msg'    => array(
		'{bogo_title}',
	),
	'wbte_sc_bogo_general_apply_choose_product_title'    => array(
		'{bogo_title}',
	),
	'wbte_sc_bogo_general_select_any_from_store'         => array(
		'{bogo_title}',
		'{qty}',
		'{shop_link $text$}', // since 3.1.0    User can pass custom text between $.
	),
	'wbte_sc_bogo_general_select_from_specific_category' => array(
		'{bogo_title}',
		'{category_name}',
		'{qty}',
	),
);
?>

<div id="wbte_sc_bogo_general_settings" class="wbte_sc_bogo_general_settings">     
	
	<div class="wbte_sc_bogo_general_settings_head">
		<h3><?php esc_html_e( 'General settings', 'wt-smart-coupons-for-woocommerce-pro' ); ?></h3>
		<p class="wbte_sc_bogo_general_settings_close">&times;</p>
	</div>
	<div class="wbte_sc_bogo_general_settings_body">
		<form id="wbte_sc_bogo_general_settings_form" action="POST">

			<p class="wbte_sc_bogo_input_title" style="margin:30px 0 10px 0;"><?php esc_html_e( 'Apply tax on', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
			<?php
			echo $ds_obj->get_component(
				'radio-group multi-line',
				array(
					'values' => array(
						'name'  => 'wbte_sc_bogo_apply_tax_on',
						'items' => array(
							array(
								'label'      => sprintf(
									// Translators: 1: Tooltip.
									esc_html__( 'Discounted price %s', 'wt-smart-coupons-for-woocommerce-pro' ),
									wp_kses_post( wc_help_tip( __( 'Tax is calculated based on the price after the offer is applied', 'wt-smart-coupons-for-woocommerce-pro' ) ) )
								),
								'value'      => 'wbte_sc_bogo_apply_tax_on_discount',
								'is_checked' => 'wbte_sc_bogo_apply_tax_on_discount' === self::get_general_settings_value( 'wbte_sc_bogo_apply_tax_on' ),
							),
							array(
								'label'       => sprintf(
									// Translators: 1: Premium icon, 2: Tooltip.
									esc_html__( 'Original price %s', 'wt-smart-coupons-for-woocommerce-pro' ),
									wp_kses_post( wc_help_tip( __( 'Tax is calculated on the original price before the offer is applied.', 'wt-smart-coupons-for-woocommerce-pro' ) ) )
								),
								'value'       => 'wbte_sc_bogo_apply_tax_on_original',
								'is_checked'  => 'wbte_sc_bogo_apply_tax_on_original' === self::get_general_settings_value( 'wbte_sc_bogo_apply_tax_on' ),
							),
						),
					),
				)
			);
			?>
			<p class="wbte_sc_bogo_input_title" style="margin:30px 0 10px 0;"><?php esc_html_e( 'Auto add products for Buy X Get X/Y giveaways', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
			<?php
			echo $ds_obj->get_component(
				'radio-group multi-line',
				array(
					'values' => array(
						'name'  => 'wbte_sc_bogo_auto_add_giveaway',
						'items' => array(
							array(
								'label'      => esc_html__( 'Add only free products to cart', 'wt-smart-coupons-for-woocommerce-pro' ),
								'value'      => 'wbte_sc_bogo_auto_add_full_giveaway',
								'is_checked' => 'wbte_sc_bogo_auto_add_full_giveaway' === self::get_general_settings_value( 'wbte_sc_bogo_auto_add_giveaway' ),
							),
							array(
								'label'       => esc_html__( 'Add all discounted products to cart', 'wt-smart-coupons-for-woocommerce-pro' ),
								'value'       => 'wbte_sc_bogo_auto_add_all_giveaway',
								'is_checked'  => 'wbte_sc_bogo_auto_add_all_giveaway' === self::get_general_settings_value( 'wbte_sc_bogo_auto_add_giveaway' ),
							),
						),
					),
				)
			);
			?>

			<label for="wbte_sc_bogo_general_discount_apply_message" class="wbte_sc_bogo_input_title wbte_sc_bogo_general_label" style="margin-top:30px;"><?php esc_html_e( 'Offer applied message', 'wt-smart-coupons-for-woocommerce-pro' ); ?></label>
			<input type="text" id="wbte_sc_bogo_general_discount_apply_message" name="wbte_sc_bogo_general_discount_apply_message" class="wbte_sc_bogo_text_input" placeholder="<?php esc_attr_e( 'Apply discount', 'wt-smart-coupons-for-woocommerce-pro' ); ?>" value="<?php echo esc_attr( self::get_general_settings_value( 'wbte_sc_bogo_general_discount_apply_message' ) ); ?>">
			<div class="wbte_sc_bogo_help_text">
				<?php
				esc_html_e(
					'Available placeholders: ',
					'wt-smart-coupons-for-woocommerce-pro'
				);
				foreach ( $placeholders['wbte_sc_bogo_general_discount_apply_message'] as $placeholder ) {
					echo "<span class='wbte_sc_bogo_placeholder' id='" . esc_attr( $placeholder ) . "' data-parent-input='wbte_sc_bogo_general_discount_apply_message'>" . esc_html( $placeholder ) . '</span>&nbsp;';
				}
				?>
			</div>

			<label for="wbte_sc_bogo_general_product_added_message" class="wbte_sc_bogo_input_title wbte_sc_bogo_general_label"><?php esc_html_e( 'Product added message for Buy X Get X/Y offers', 'wt-smart-coupons-for-woocommerce-pro' ); ?></label>
			<input type="text" id="wbte_sc_bogo_general_product_added_message" name="wbte_sc_bogo_general_product_added_message" class="wbte_sc_bogo_text_input" placeholder="<?php esc_attr_e( 'Product added...', 'wt-smart-coupons-for-woocommerce-pro' ); ?>" value="<?php echo esc_attr( self::get_general_settings_value( 'wbte_sc_bogo_general_product_added_message' ) ); ?>">
			<div class="wbte_sc_bogo_help_text">
				<?php
				esc_html_e(
					'Available placeholders: ',
					'wt-smart-coupons-for-woocommerce-pro'
				);
				foreach ( $placeholders['wbte_sc_bogo_general_product_added_message'] as $placeholder ) {
					echo "<span class='wbte_sc_bogo_placeholder' id='" . esc_attr( $placeholder ) . "' data-parent-input='wbte_sc_bogo_general_product_added_message'>" . esc_html( $placeholder ) . '</span>&nbsp;';
				}
				?>
			</div>

			<label for="wbte_sc_bogo_general_cheap_exp_added_message" class="wbte_sc_bogo_input_title wbte_sc_bogo_general_label"><?php esc_html_e( 'Discount applied message for Cheapest/Most expensive offers', 'wt-smart-coupons-for-woocommerce-pro' ); ?></label>

			<textarea type="text" id="wbte_sc_bogo_general_cheap_exp_added_message" name="wbte_sc_bogo_general_cheap_exp_added_message" class="wbte_sc_bogo_text_input" placeholder="<?php esc_attr_e( 'Product added...', 'wt-smart-coupons-for-woocommerce-pro' ); ?>"><?php echo esc_html( self::get_general_settings_value( 'wbte_sc_bogo_general_cheap_exp_added_message' ) ); ?></textarea>
			
			<div class="wbte_sc_bogo_help_text">
				<?php
				esc_html_e(
					'Available placeholders: ',
					'wt-smart-coupons-for-woocommerce-pro'
				);
				foreach ( $placeholders['wbte_sc_bogo_general_cheap_exp_added_message'] as $placeholder ) {
					echo "<span class='wbte_sc_bogo_placeholder' id='" . esc_attr( $placeholder ) . "' data-parent-input='wbte_sc_bogo_general_cheap_exp_added_message'>" . esc_html( $placeholder ) . '</span>&nbsp;';
				}
				?>
			</div>

			<label for="wbte_sc_bogo_general_discount_under_product_msg" class="wbte_sc_bogo_input_title wbte_sc_bogo_general_label"><?php esc_html_e( 'Discount info under each item in cart', 'wt-smart-coupons-for-woocommerce-pro' ); ?></label>
			<input type="text" id="wbte_sc_bogo_general_discount_under_product_msg" name="wbte_sc_bogo_general_discount_under_product_msg" class="wbte_sc_bogo_text_input" placeholder="<?php esc_attr_e( 'Discount...', 'wt-smart-coupons-for-woocommerce-pro' ); ?>" value="<?php echo esc_attr( self::get_general_settings_value( 'wbte_sc_bogo_general_discount_under_product_msg' ) ); ?>">
			<div class="wbte_sc_bogo_help_text">
				<?php
				esc_html_e(
					'Available placeholders: ',
					'wt-smart-coupons-for-woocommerce-pro'
				);
				foreach ( $placeholders['wbte_sc_bogo_general_discount_under_product_msg'] as $placeholder ) {
					echo "<span class='wbte_sc_bogo_placeholder' id='" . esc_attr( $placeholder ) . "' data-parent-input='wbte_sc_bogo_general_discount_under_product_msg'>" . esc_html( $placeholder ) . '</span>&nbsp;';
				}
				?>
			</div>

			<label for="wbte_sc_bogo_general_apply_choose_product_title" class="wbte_sc_bogo_input_title wbte_sc_bogo_general_label"><?php esc_html_e( '“Choose product” title', 'wt-smart-coupons-for-woocommerce-pro' ); ?></label>
			<input type="text" id="wbte_sc_bogo_general_apply_choose_product_title" name="wbte_sc_bogo_general_apply_choose_product_title" class="wbte_sc_bogo_text_input" placeholder="<?php esc_attr_e( 'Choose product', 'wt-smart-coupons-for-woocommerce-pro' ); ?>" value="<?php echo esc_attr( self::get_general_settings_value( 'wbte_sc_bogo_general_apply_choose_product_title' ) ); ?>">
			<div class="wbte_sc_bogo_help_text">
				<?php
				esc_html_e(
					'Available placeholders: ',
					'wt-smart-coupons-for-woocommerce-pro'
				);
				foreach ( $placeholders['wbte_sc_bogo_general_apply_choose_product_title'] as $placeholder ) {
					echo "<span class='wbte_sc_bogo_placeholder' id='" . esc_attr( $placeholder ) . "' data-parent-input='wbte_sc_bogo_general_apply_choose_product_title'>" . esc_html( $placeholder ) . '</span>&nbsp;';
				}
				?>
			</div>

			<label for="wbte_sc_bogo_general_select_any_from_store" class="wbte_sc_bogo_input_title wbte_sc_bogo_general_label"><?php esc_html_e( 'Message for `Select any product from store`', 'wt-smart-coupons-for-woocommerce-pro' ); ?></label>
			<textarea type="text" id="wbte_sc_bogo_general_select_any_from_store" name="wbte_sc_bogo_general_select_any_from_store" class="wbte_sc_bogo_text_input" placeholder="<?php esc_attr_e( 'eg., Next product added to the cart is on us!', 'wt-smart-coupons-for-woocommerce-pro' ); ?>"><?php echo esc_html( self::get_general_settings_value( 'wbte_sc_bogo_general_select_any_from_store' ) ); ?></textarea>
			<div class="wbte_sc_bogo_help_text">
				<?php
				esc_html_e(
					'Available placeholders: ',
					'wt-smart-coupons-for-woocommerce-pro'
				);
				foreach ( $placeholders['wbte_sc_bogo_general_select_any_from_store'] as $placeholder ) {
					echo "<span class='wbte_sc_bogo_placeholder' id='" . esc_attr( $placeholder ) . "' data-parent-input='wbte_sc_bogo_general_select_any_from_store'>" . esc_html( $placeholder ) . '</span>&nbsp;';
				}
				?>
			</div>

			<label for="wbte_sc_bogo_general_select_from_specific_category" class="wbte_sc_bogo_input_title wbte_sc_bogo_general_label"><?php esc_html_e( 'Message for "Select Any product from specific category”', 'wt-smart-coupons-for-woocommerce-pro' ); ?></label>
			<textarea type="text" id="wbte_sc_bogo_general_select_from_specific_category" name="wbte_sc_bogo_general_select_from_specific_category" class="wbte_sc_bogo_text_input" placeholder="<?php esc_attr_e( 'eg., Add any product from the {category_name} to your cart, and it’s on us!', 'wt-smart-coupons-for-woocommerce-pro' ); ?>"><?php echo esc_html( self::get_general_settings_value( 'wbte_sc_bogo_general_select_from_specific_category' ) ); ?></textarea>
			<div class="wbte_sc_bogo_help_text">
				<?php
				esc_html_e(
					'Available placeholders: ',
					'wt-smart-coupons-for-woocommerce-pro'
				);
				foreach ( $placeholders['wbte_sc_bogo_general_select_from_specific_category'] as $placeholder ) {
					echo "<span class='wbte_sc_bogo_placeholder' id='" . esc_attr( $placeholder ) . "' data-parent-input='wbte_sc_bogo_general_select_from_specific_category'>" . esc_html( $placeholder ) . '</span>&nbsp;';
				}
				?>
			</div>

			<div class="wbte_sc_bogo_general_settings_btn_div">
				<?php
				echo $ds_obj->get_component(
					'button filled medium',
					array(
						'values' => array(
							'button_title' => esc_html__( 'Update settings', 'wt-smart-coupons-for-woocommerce-pro' ),
						),
						'class'  => array( 'wbte_sc_bogo_update_general_settings' ),
					)
				);
				?>
			</div>
		</form>
	</div>
</div>