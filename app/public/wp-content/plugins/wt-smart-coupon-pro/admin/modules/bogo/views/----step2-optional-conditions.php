<?php
/**
 * Optional conditions for BOGO in step 2
 *
 * @since 3.2.0 Moved from step2.php to this file
 * @package    Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$purchase_history = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_purchase_history' );
$on_sale_non_sale = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_on_sale_non_sale' );
?>

<table class="wbte_sc_bogo_edit_table wbte_sc_bogo_additional_fields_table">
	<thead>
		<tr>
			<th colspan="2">
				<div class="wbte_sc_bogo_edit_custom_drop_down_head">
					<p><?php esc_html_e( 'Optional conditions', 'wt-smart-coupons-for-woocommerce-pro' ); ?>
						<span class="wbte_sc_bogo_edit_add_button wbte_sc_bogo_edit_addition_conditions"><?php esc_html_e( '+ Add', 'wt-smart-coupons-for-woocommerce-pro' ); ?></span>
					</p>
					<div class="wbte_sc_bogo_edit_additional_condition_select wbte_sc_bogo_edit_custom_drop_down wbte_sc_bogo_multi_dropdown">

						<!-- Cart condition -->
						<div class="wbte_sc_bogo_dropdown_menu_item">
							<p class="wbte_sc_bogo_dropdown_menu_item_head">
                                <?php esc_html_e( 'Cart condition', 'wt-smart-coupons-for-woocommerce-pro' ); ?>
                                <img src="<?php echo esc_url( "{$admin_img_path}right-arrow.svg" ); ?>" alt="arrow icon">
                            </p>
							<div class="wbte_sc_bogo_submenu">
								<p data-row="wbte_sc_bogo_qty_row" data-group="wbte_sc_qty"><?php esc_html_e( 'Total quantity', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
								<p data-row="wbte_sc_bogo_each_qty_row" data-group="wbte_sc_qty"><?php esc_html_e( 'Quantity of each product', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
								<p data-row="wbte_sc_bogo_adtl_subtotal_row"><?php esc_html_e( 'Subtotal', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
								<p data-row="wbte_sc_bogo_on_sale_item_row"><?php esc_html_e( 'On-sale items', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
							</div>
						</div>

						<!-- User restriction -->
						<div class="wbte_sc_bogo_dropdown_menu_item">
							<p class="wbte_sc_bogo_dropdown_menu_item_head">
                                <?php esc_html_e( 'User restriction', 'wt-smart-coupons-for-woocommerce-pro' ); ?>
                                <img src="<?php echo esc_url( "{$admin_img_path}right-arrow.svg" ); ?>" alt="arrow icon">
                            </p>
							<div class="wbte_sc_bogo_submenu">
								<p data-row="wbte_sc_bogo_user_role_row"><?php esc_html_e( 'User role', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
								<p data-row="wbte_sc_bogo_email_row"><?php esc_html_e( 'Allowed emails', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
							</div>
						</div>

						<!-- Usage limit -->
						<div class="wbte_sc_bogo_dropdown_menu_item">
							<p class="wbte_sc_bogo_dropdown_menu_item_head">
                                <?php esc_html_e( 'Usage limit', 'wt-smart-coupons-for-woocommerce-pro' ); ?>
                                <img src="<?php echo esc_url( "{$admin_img_path}right-arrow.svg" ); ?>" alt="arrow icon">
                            </p>
							<div class="wbte_sc_bogo_submenu">
								<p data-row="wbte_sc_bogo_per_coupon_row"><?php esc_html_e( 'Usage limit per offer', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
								<p data-row="wbte_sc_bogo_per_user_row"><?php esc_html_e( 'Usage limit per user', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
								<p data-row="wbte_sc_bogo_combine_offer_row"><?php esc_html_e( 'Combining offers', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
							</div>
						</div>

						<!-- Checkout -->
						<div class="wbte_sc_bogo_dropdown_menu_item">
							<p class="wbte_sc_bogo_dropdown_menu_item_head">
                                <?php esc_html_e( 'Checkout', 'wt-smart-coupons-for-woocommerce-pro' ); ?>
                                <img src="<?php echo esc_url( "{$admin_img_path}right-arrow.svg" ); ?>" alt="arrow icon">
                            </p>
							<div class="wbte_sc_bogo_submenu">
								<p data-row="wbte_sc_bogo_payment_mtd_row"><?php esc_html_e( 'Payment method', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
								<p data-row="wbte_sc_bogo_shipping_mtd_row"><?php esc_html_e( 'Shipping method', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
								<p data-row="wbte_sc_bogo_location_row"><?php esc_html_e( 'Location', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
							</div>
						</div>

						<!-- Purchase history -->
						<p data-row="wbte_sc_bogo_purchase_history_row"><?php esc_html_e( 'Purchase history', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
						
					</div>
				</div>
			</th>
		</tr>
	</thead>
	<tbody class="wbte_sc_bogo_additional_fields_contents">
		<!-- Cart condition -->
		<tr class="<?php echo ( 0 >= self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_min_qty_add' ) && empty( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_max_qty_add' ) ) ) ? 'wbte_sc_bogo_conditional_hidden ' : ' '; ?>" data-row="wbte_sc_bogo_qty_row">
			<td colspan="2">
				<div class="wbte_sc_bogo_additional_fields">
					<?php echo wp_kses_post( $trash_icon ); ?>
					<div class="wbte_sc_bogo_additional_flex">
						<p class="wbte_sc_bogo_add_fields_p"><?php esc_html_e( 'Minimum quantity', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
						<input type="text" name="_wbte_sc_bogo_min_qty_add" id="_wbte_sc_bogo_min_qty_add" class="wbte_sc_bogo_edit_input wbte_sc_bogo_input_only_number" value="<?php echo esc_attr( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_min_qty_add' ) ); ?>">
					</div>
					<br>
					<div class="wbte_sc_bogo_additional_flex">
						<p class="wbte_sc_bogo_add_fields_p"><?php esc_html_e( 'Maximum quantity', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
						<input type="text" name="_wbte_sc_bogo_max_qty_add" id="_wbte_sc_bogo_max_qty_add" placeholder="<?php esc_attr_e( 'Optional', 'wt-smart-coupons-for-woocommerce-pro' ); ?>" class="wbte_sc_bogo_edit_input wbte_sc_bogo_input_only_number" value="<?php echo esc_attr( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_max_qty_add' ) ); ?>">
					</div>
				</div>
			</td>
		</tr>
		<tr class="<?php echo empty( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_min_qty_each' ) ) && empty( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_max_qty_each' ) ) ? 'wbte_sc_bogo_conditional_hidden ' : ' '; ?>" data-row="wbte_sc_bogo_each_qty_row">
			<td colspan="2">
				<div class="wbte_sc_bogo_additional_fields">
					<?php echo wp_kses_post( $trash_icon ); ?>
					<div class="wbte_sc_bogo_additional_flex">
						<p class="wbte_sc_bogo_add_fields_p"><?php esc_html_e( 'Min quantity of each item', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
						<input type="text" name="_wbte_sc_min_qty_each" id="_wbte_sc_min_qty_each" class="wbte_sc_bogo_edit_input wbte_sc_bogo_input_only_number" value="<?php echo esc_attr( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_min_qty_each' ) ); ?>">
					</div>
					<br>
					<div class="wbte_sc_bogo_additional_flex">
						<p class="wbte_sc_bogo_add_fields_p"><?php esc_html_e( 'Max quantity of each item', 'wt-smart-coupons-for-woocommerce-pro' ); ?><br><span><?php esc_html_e( '(Optional)', 'wt-smart-coupons-for-woocommerce-pro' ); ?></span></p>
						<input type="text" name="_wbte_sc_max_qty_each" id="_wbte_sc_max_qty_each" placeholder=<?php esc_attr_e( 'Optional', 'wt-smart-coupons-for-woocommerce-pro' ); ?> class="wbte_sc_bogo_edit_input wbte_sc_bogo_input_only_number" value="<?php echo esc_attr( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_max_qty_each' ) ); ?>">
					</div>
				</div>
			</td>
		</tr>
		<tr class="<?php echo ( 0 >= self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_min_amount_adtl' ) && empty( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_max_amount_adtl' ) ) ) ? 'wbte_sc_bogo_conditional_hidden ' : ' '; ?>" data-row="wbte_sc_bogo_adtl_subtotal_row">
			<td colspan="2">
				<div class="wbte_sc_bogo_additional_fields">
					<?php echo wp_kses_post( $trash_icon ); ?>
					<div class="wbte_sc_bogo_additional_flex">
						<p class="wbte_sc_bogo_add_fields_p"><?php esc_html_e( 'Minimum subtotal', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
						<div class="wbte_sc_bogo_icon_input">
							<input type="text" id="_wbte_sc_bogo_min_amount_adtl" name="_wbte_sc_bogo_min_amount_adtl" class="wbte_sc_bogo_input_only_numbers_with_decimal" value="<?php echo esc_attr( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_min_amount_adtl' ) ); ?>">
							<div class="wbte_sc_bogo_icon_input_symbol">
								<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>
							</div>
						</div>
					</div>
					<br>
					<div class="wbte_sc_bogo_additional_flex">
						<p class="wbte_sc_bogo_add_fields_p"><?php esc_html_e( 'Max subtotal', 'wt-smart-coupons-for-woocommerce-pro' ); ?><br><span><?php esc_html_e( '(Optional)', 'wt-smart-coupons-for-woocommerce-pro' ); ?></span></p>
						<div class="wbte_sc_bogo_icon_input">
							<input type="text" id="_wbte_sc_bogo_max_amount_adtl" name="_wbte_sc_bogo_max_amount_adtl" class="wbte_sc_bogo_input_only_numbers_with_decimal" value="<?php echo esc_attr( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_max_amount_adtl' ) ); ?>">
							<div class="wbte_sc_bogo_icon_input_symbol">
								<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>
							</div>
						</div>
					</div>
					<br>
					<div class="wbte_sc_bogo_additional_flex radio">
						<p class="wbte_sc_bogo_add_fields_p"><?php esc_html_e( 'Calculate subtotal from', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>

						<?php
						$adtl_subtotal_from = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_adtl_subtotal_from' );
						$adtl_subtotal_from = ! empty( $adtl_subtotal_from ) ? $adtl_subtotal_from : 'selected_products';
						echo $ds_obj->get_component(
							'radio-group multi-line',
							array(
								'values' => array(
									'name'  => 'wbte_sc_bogo_adtl_subtotal_from',
									'items' => array(
										array(
											'label'      => esc_html__( 'Entire cart', 'wt-smart-coupons-for-woocommerce-pro' ),
											'value'      => 'entire_cart',
											'is_checked' => 'entire_cart' === esc_attr( $adtl_subtotal_from ),
										),
										array(
											'label'      => esc_html__( "Products selected in 'Customer buys' (if any)", 'wt-smart-coupons-for-woocommerce-pro' ),
											'value'      => 'selected_products',
											'is_checked' => 'selected_products' === esc_attr( $adtl_subtotal_from ),
										),
									),
								),
								'class'  => array( 'wbte_sc_bogo_radio_remove_val_if_hidden' ),
							)
						);
						?>
					</div>
				</div>
			</td>
		</tr>
		<tr class="<?php echo empty( $on_sale_non_sale ) ? 'wbte_sc_bogo_conditional_hidden' : ''; ?>" data-row="wbte_sc_bogo_on_sale_item_row">
			<td colspan="2">
				<div class="wbte_sc_bogo_additional_fields">
					<?php echo wp_kses_post( $trash_icon ); ?>
					<div class="wbte_sc_bogo_additional_flex">
						<p class="wbte_sc_bogo_add_fields_p" id="wbte_sc_bogo_on_sale_non_sale_label"><?php esc_html_e( 'Apply offer only to', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>

						<?php
						$on_sale_non_sale = ! empty( $on_sale_non_sale ) ? $on_sale_non_sale : 'wbte_sc_bogo_on_sale';
						echo $ds_obj->get_component(
							'radio-group',
							array(
								'values' => array(
									'name'  => 'wbte_sc_bogo_on_sale_non_sale',
									'items' => array(
										array(
											'label'      => esc_html__( 'On-sale products', 'wt-smart-coupons-for-woocommerce-pro' ),
											'value'      => 'wbte_sc_bogo_on_sale',
											'is_checked' => 'wbte_sc_bogo_on_sale' === esc_attr( $on_sale_non_sale ),
										),
										array(
											'label'      => esc_html__( 'Products not on sale', 'wt-smart-coupons-for-woocommerce-pro' ),
											'value'      => 'wbte_sc_bogo_on_non_sale',
											'is_checked' => 'wbte_sc_bogo_on_non_sale' === esc_attr( $on_sale_non_sale ),
										),
									),
								),
								'class'  => array( 'wbte_sc_bogo_radio_remove_val_if_hidden' ),
							)
						);
						?>
					</div>
				</div>
			</td>
		</tr>

		<!-- User restriction -->
		<tr class="<?php echo empty( get_post_meta( $coupon_id, '_wt_sc_user_roles', true ) ) ? 'wbte_sc_bogo_conditional_hidden ' : ' '; ?>" data-row="wbte_sc_bogo_user_role_row">
			<td colspan="2">
				<div class="wbte_sc_bogo_additional_fields">
					<?php echo wp_kses_post( $trash_icon ); ?>
					<div class="wbte_sc_bogo_additional_flex">
						<p class="wbte_sc_bogo_add_fields_p"><?php esc_html_e( 'Eligible user roles for offer', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
						<?php echo wp_kses_post( wc_help_tip( __( 'Select one or more user roles to restrict the offer.', 'wt-smart-coupons-for-woocommerce-pro' ) ) ); ?>
						<div style="flex-grow: 1;">
							<select id="_wt_sc_user_roles" name="_wt_sc_user_roles[]" style="width:90%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Any role', 'wt-smart-coupons-for-woocommerce-pro' ); ?>">
							<?php
							$available_roles = wp_roles()->roles;
							$available_roles['wbte_sc_guest'] = array(
								'name' => __('Guest', 'wt-smart-coupons-for-woocommerce-pro')
							);
							if ( ! empty( $available_roles ) ) {
								$selected_user_roles = is_array( self::get_coupon_meta_value( $coupon_id, '_wt_sc_user_roles' ) ) ? self::get_coupon_meta_value( $coupon_id, '_wt_sc_user_roles' ) : explode( ',', self::get_coupon_meta_value( $coupon_id, '_wt_sc_user_roles' ) );

								foreach ( $available_roles as $role_id => $user_role ) {
									echo '<option value="' . esc_attr( $role_id ) . '"' . selected( in_array( $role_id, $selected_user_roles, true ), true, false ) . '>' . esc_html( translate_user_role( $user_role['name'] ) ) . '</option>';
								}
							}
							?>
							</select> 
						</div>
					</div>
				</div>
			</td>
		</tr>
		<tr class="<?php echo empty( $coupon->get_email_restrictions( 'edit' ) ) ? 'wbte_sc_bogo_conditional_hidden ' : ' '; ?>" data-row="wbte_sc_bogo_email_row">
			<td colspan="2">
				<div class="wbte_sc_bogo_additional_fields wbte_sc_bogo_email_flex">
					<?php echo wp_kses_post( $trash_icon ); ?>
					<label class="wbte_sc_bogo_add_fields_p" for="customer_email"><?php esc_html_e( 'Allowed emails', 'wt-smart-coupons-for-woocommerce-pro' ); ?></label>
					<?php echo wp_kses_post( wc_help_tip( __( 'The BOGO deal is only valid for recipients of the selected emails.', 'wt-smart-coupons-for-woocommerce-pro' ) ) ); ?>
					<div>
						<select style="width: 333px; height: 55px;" name="wbte_sc_bogo_emails[]" id="wbte_sc_bogo_emails" multiple="multiple" class="wbte_sc_bogo_email_search" data-placeholder="<?php echo esc_attr( 'mail@example.com' ); ?>">
						<?php
							$emails = $coupon->get_email_restrictions( 'edit' );
						foreach ( $emails as $email ) {
							echo '<option value="' . esc_attr( $email ) . '" selected="selected">' . esc_html( $email ) . '</option>';
						}
						?>
						</select>
						<p class="wbte_sc_bogo_email_field_caption"><?php echo wp_kses_post( __( 'Offer won’t be auto-applied for guest users when email restriction is enabled.', 'wt-smart-coupons-for-woocommerce-pro' ) ); ?></p>
					</div>
				</div>
			</td>
		</tr>

		<!-- Usage limit -->
		<tr class="<?php echo empty( self::get_coupon_meta_value( $coupon_id, 'usage_limit' ) ) ? 'wbte_sc_bogo_conditional_hidden ' : ' '; ?>" data-row="wbte_sc_bogo_per_coupon_row">
			<td colspan="2">
				<div class="wbte_sc_bogo_additional_fields">
					<?php echo wp_kses_post( $trash_icon ); ?>
					<div class="wbte_sc_bogo_additional_flex">
						<p class="wbte_sc_bogo_add_fields_p"><?php esc_html_e( 'Usage limit per offer', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
						<?php echo wp_kses_post( wc_help_tip( __( 'The total number of times this offer can be used in the store, including multiple redemptions by the same user', 'wt-smart-coupons-for-woocommerce-pro' ) ) ); ?>
						<input type="text" name="usage_limit" id="usage_limit" class="wbte_sc_bogo_edit_input wbte_sc_bogo_input_only_number" value="<?php echo esc_attr( self::get_coupon_meta_value( $coupon_id, 'usage_limit' ) ); ?>">
					</div>
				</div>
			</td>
		</tr>
		<tr class="<?php echo empty( self::get_coupon_meta_value( $coupon_id, 'usage_limit_per_user' ) ) ? 'wbte_sc_bogo_conditional_hidden ' : ' '; ?>" data-row="wbte_sc_bogo_per_user_row">
			<td colspan="2">
				<div class="wbte_sc_bogo_additional_fields">
					<?php echo wp_kses_post( $trash_icon ); ?>
					<div class="wbte_sc_bogo_additional_flex">
						<p class="wbte_sc_bogo_add_fields_p"><?php esc_html_e( 'Usage limit per user', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
						<?php echo wp_kses_post( wc_help_tip( __( 'The maximum number of times a single user can redeem this offer. It must be less than the overall usage limit per offer', 'wt-smart-coupons-for-woocommerce-pro' ) ) ); ?>
						<input type="text" name="usage_limit_per_user" id="usage_limit_per_user" class="wbte_sc_bogo_edit_input wbte_sc_bogo_input_only_number" value="<?php echo esc_attr( self::get_coupon_meta_value( $coupon_id, 'usage_limit_per_user' ) ); ?>">
					</div>
				</div>
			</td>
		</tr>
		<tr class="<?php echo 'no' === self::get_coupon_meta_value( $coupon_id, 'individual_use' ) ? 'wbte_sc_bogo_conditional_hidden ' : ' '; ?>" data-row="wbte_sc_bogo_combine_offer_row">
			<td colspan="2">
				<div class="wbte_sc_bogo_additional_fields">
					<?php echo wp_kses_post( $trash_icon ); ?>
					<div class="wbte_sc_bogo_additional_flex">
						<p class="wbte_sc_bogo_add_fields_p"><?php esc_html_e( 'Combining offers', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
						<?php
						echo $ds_obj->get_component(
							'checkbox normal',
							array(
								'values' => array(
									'name'       => 'individual_use',
									'id'         => 'individual_use',
									'value'      => 'yes',
									'is_checked' => 'yes' === esc_attr( self::get_coupon_meta_value( $coupon_id, 'individual_use' ) ),
									'label'      => esc_html__( 'Do not combine with other coupons', 'wt-smart-coupons-for-woocommerce-pro' ),
								),
							)
						);
						?>
					</div>
				</div>
			</td>
		</tr>

		<!-- Checkout -->
		<!-- Payment methods -->
		<tr class="<?php echo empty( get_post_meta( $coupon_id, '_wt_sc_payment_methods', true ) ) ? 'wbte_sc_bogo_conditional_hidden ' : ' '; ?>" data-row="wbte_sc_bogo_payment_mtd_row">
			<td colspan="2">
				<div class="wbte_sc_bogo_additional_fields">
					<?php echo wp_kses_post( $trash_icon ); ?>
					<div class="wbte_sc_bogo_additional_flex">
						<p class="wbte_sc_bogo_add_fields_p"><?php esc_html_e( 'Eligible payment methods for offer', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
						<?php echo wp_kses_post( wc_help_tip( __( 'Coupon will be applicable if any of these payment methods are selected.', 'wt-smart-coupons-for-woocommerce-pro' ) ) ); ?>
						<div style="flex-grow: 1;">
							<select id="_wt_sc_payment_methods" name="_wt_sc_payment_methods[]" style="width:90%;" class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Any payment method', 'wt-smart-coupons-for-woocommerce-pro' ); ?>">
							<?php
							$payment_methods = WC()->payment_gateways->payment_gateways();

							if ( ! empty( $payment_methods ) ) {
								$payment_method_ids = is_array( self::get_coupon_meta_value( $coupon_id, '_wt_sc_payment_methods' ) ) ? self::get_coupon_meta_value( $coupon_id, '_wt_sc_payment_methods' ) : explode( ',', self::get_coupon_meta_value( $coupon_id, '_wt_sc_payment_methods' ) );

								foreach ( $payment_methods as $payment_method ) {
									if ( wc_string_to_bool( $payment_method->enabled ) ) {
										echo '<option value="' . esc_attr( $payment_method->id ) . '" ' . selected( in_array( $payment_method->id, $payment_method_ids, true ), true, false ) . '>' . esc_html( $payment_method->title ) . '</option>';
									}
								}
							}
							?>
							</select>
						</div>
					</div>
				</div>
			</td>
		</tr>
		<!-- Shipping methods -->
		<tr class="<?php echo empty( get_post_meta( $coupon_id, '_wt_sc_shipping_methods', true ) ) ? 'wbte_sc_bogo_conditional_hidden ' : ' '; ?>" data-row="wbte_sc_bogo_shipping_mtd_row">
			<td colspan="2">
				<div class="wbte_sc_bogo_additional_fields">
					<?php echo wp_kses_post( $trash_icon ); ?>
					<div class="wbte_sc_bogo_additional_flex">
						<p class="wbte_sc_bogo_add_fields_p"><?php esc_html_e( 'Eligible Shipping methods for offer', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
						<?php echo wp_kses_post( wc_help_tip( __( 'Coupon will be applicable if any of these shipping methods are selected.', 'wt-smart-coupons-for-woocommerce-pro' ) ) ); ?>
						<div style="flex-grow: 1;">
							<select id="_wt_sc_shipping_methods" name="_wt_sc_shipping_methods[]" style="width:90%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Any shipping method', 'wt-smart-coupons-for-woocommerce-pro' ); ?>">
							<?php
							$shipping_methods = WC()->shipping->load_shipping_methods();

							if ( ! empty( $shipping_methods ) ) {

								$shipping_method_ids = is_array( self::get_coupon_meta_value( $coupon_id, '_wt_sc_shipping_methods' ) ) ? self::get_coupon_meta_value( $coupon_id, '_wt_sc_shipping_methods' ) : explode( ',', self::get_coupon_meta_value( $coupon_id, '_wt_sc_shipping_methods' ) );
								foreach ( $shipping_methods as $shipping_method ) {

									// Skip disabled items.
									if ( 'yes' !== $shipping_method->enabled ) {
										continue;
									}

									$method_title = $shipping_method->method_title;
									$method_id    = $shipping_method->id;

									if ( 'pickup_location' === $method_id ) {
										$method_title .= __( ' (Only for block checkout)', 'wt-smart-coupons-for-woocommerce-pro' );
									}

									?>
									<option value="<?php echo esc_attr( $method_id ); ?>"
										<?php
										echo selected( in_array( $method_id, $shipping_method_ids, true ), true, false );
										?>
									>
										<?php echo esc_html( $method_title ); ?>
									</option>
									<?php
								}
							}
							?>
							</select>
						</div>
					</div>
				</div>
			</td>
		</tr>
		<!-- Location -->
		<tr class="<?php echo empty( get_post_meta( $coupon_id, '_wt_coupon_available_location', true ) ) ? 'wbte_sc_bogo_conditional_hidden ' : ' '; ?>" data-row="wbte_sc_bogo_location_row">
			<td colspan="2">
				<div class="wbte_sc_bogo_additional_fields">
					<?php echo wp_kses_post( $trash_icon ); ?>
					<div class="wbte_sc_bogo_additional_flex">
						<p class="wbte_sc_bogo_add_fields_p"><?php esc_html_e( 'Locations', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>

						<div class="wbte_sc_bogo_edit_custom_drop_down_head">
							<div class="wbte_sc_bogo_edit_custom_drop_down_btn">
								<p><?php 'include' === self::get_coupon_meta_value( $coupon_id, '_wt_coupon_available_location_inc_exc' ) ? esc_html_e( 'Eligible for offer', 'wt-smart-coupons-for-woocommerce-pro' ) : esc_html_e( 'Not eligible for offer', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
								<span class="dashicons dashicons-arrow-down-alt2"></span>
							</div>
							<div class="wbte_sc_bogo_edit_custom_drop_down">
								<p class="wbte_sc_bogo_edit_custom_drop_down_sub_btn" data-val="include"><?php esc_html_e( 'Eligible for offer', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
								<p class="wbte_sc_bogo_edit_custom_drop_down_sub_btn" data-val="exclude"><?php esc_html_e( 'Not eligible for offer', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
							</div>
							<input type="hidden" name="_wt_coupon_available_location_inc_exc" id="_wt_coupon_available_location_inc_exc" class="wbte_sc_bogo_avoid_if_hidden" value="<?php echo esc_attr( self::get_coupon_meta_value( $coupon_id, '_wt_coupon_available_location_inc_exc' ) ); ?>">
						</div>

						<select id="_wt_coupon_available_location" name="_wt_coupon_available_location[]" style="width:35%;"  class="wc-enhanced-select wbte_sc_bogo_avoid_if_hidden" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Any Location', 'wt-smart-coupons-for-woocommerce-pro' ); ?>">
							<?php
							$countries = WC()->countries->get_countries();

							if ( ! empty( $countries ) ) {
								$available_locations = is_array( self::get_coupon_meta_value( $coupon_id, '_wt_coupon_available_location' ) ) ? self::get_coupon_meta_value( $coupon_id, '_wt_coupon_available_location' ) : explode( ',', self::get_coupon_meta_value( $coupon_id, '_wt_coupon_available_location' ) );

								foreach ( $countries as $country_code => $country ) {
									echo '<option value="' . esc_attr( $country_code ) . '" ' . selected( in_array( $country_code, $available_locations, true ), true, false ) . '>' . esc_html( $country ) . '</option>';

									$states = WC()->countries->get_states( $country_code );
									if ( $states ) {
										echo '<optgroup label="' . esc_attr( $country ) . '">';

										foreach ( $states as $state_code => $state ) {
											$option_value = "{$country_code}:{$state_code}";
											echo '<option value="' . esc_attr( $option_value ) . '" ' . selected( in_array( $option_value, $available_locations, true ), true, false ) . '>' . esc_html( $country ) . ' &mdash; ' . esc_html( $state ) . '</option>';
										}

										echo '</optgroup>';

									}
								}
							}
							?>
						</select>
					</div>
					<br>
					<div class="wbte_sc_bogo_additional_flex">
						<p class="wbte_sc_bogo_add_fields_p"><?php esc_html_e( 'Use location from', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>

						<?php
						echo $ds_obj->get_component(
							'radio-group',
							array(
								'values' => array(
									'name'  => '_wt_need_check_location_in',
									'items' => array(
										array(
											'label'      => esc_html__( 'Billing address', 'wt-smart-coupons-for-woocommerce-pro' ),
											'value'      => 'billing',
											'is_checked' => 'billing' === esc_attr( self::get_coupon_meta_value( $coupon_id, '_wt_need_check_location_in' ) ),
										),
										array(
											'label'      => esc_html__( 'Shipping address', 'wt-smart-coupons-for-woocommerce-pro' ),
											'value'      => 'shipping',
											'is_checked' => 'shipping' === esc_attr( self::get_coupon_meta_value( $coupon_id, '_wt_need_check_location_in' ) ),
										),
									),
								),
							)
						);
						?>
					</div>
				</div>
			</td>
		</tr>
		
		<!-- Purchase history -->
		<tr class="<?php echo empty( $purchase_history ) ? 'wbte_sc_bogo_conditional_hidden' : ''; ?>" data-row="wbte_sc_bogo_purchase_history_row">
			<td colspan="2">
				<div class="wbte_sc_bogo_additional_fields wbte_sc_bogo_purchase_history_container">
					<?php
					$purchase_history = ! empty( $purchase_history ) ? $purchase_history : 'wbte_sc_bogo_puchase_history_first_time';

					$nth_cond_arr = array(
						'please_select'      => esc_html__( '- Select -', 'wt-smart-coupons-for-woocommerce-pro' ),
						'greater_or_equal'   => esc_html__( 'Greater than / equal to', 'wt-smart-coupons-for-woocommerce-pro' ),
						'equals'             => esc_html__( 'Equal to', 'wt-smart-coupons-for-woocommerce-pro' ),
						'less_than_or_equal' => esc_html__( 'Less than / equal to', 'wt-smart-coupons-for-woocommerce-pro' ),
					);

					$nth_coupon_condition = self::get_coupon_meta_value( $coupon_id, 'nth_coupon_no_of_coupon_condition' );
					$nth_coupon_condition = ! empty( $nth_coupon_condition ) ? $nth_coupon_condition : 'greater_or_equal';

					$date_pattern = apply_filters( 'woocommerce_date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' );

					$date_from     = self::get_coupon_meta_value( $coupon_id, '_wt_sc_nth_order_date_from' );
					$date_to       = self::get_coupon_meta_value( $coupon_id, '_wt_sc_nth_order_date_to' );
					$purchase_days = self::get_coupon_meta_value( $coupon_id, '_wt_sc_nth_order_within_days' );

					$nth_order_status_selected = get_post_meta( $coupon_id, 'wt_order_Status_need_to_count', true );

					$nth_order_products = get_post_meta( $coupon_id, '_wt_sc_nth_order_products', true );

					$date_or_days = self::get_coupon_meta_value( $coupon_id, '_nth_coupon_order_date_or_days' );

					?>
					<?php echo wp_kses_post( $trash_icon ); ?>
					<div class="wbte_sc_bogo_purchase_history_container_inner radio wbte_sc_parent_div">
						<p class="wbte_sc_bogo_add_fields_p"><?php esc_html_e( 'Apply offer to', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>

						<?php
						echo $ds_obj->get_component(
							'radio-group multi-line',
							array(
								'values' => array(
									'name'  => 'wbte_sc_bogo_purchase_history',
									'items' => array(
										array(
											'label'      => esc_html__( 'First time buyers', 'wt-smart-coupons-for-woocommerce-pro' ),
											'value'      => 'wbte_sc_bogo_puchase_history_first_time',
											'is_checked' => 'wbte_sc_bogo_puchase_history_first_time' === esc_attr( $purchase_history ),
										),
										array(
											'label'      => esc_html__( 'Returning customers', 'wt-smart-coupons-for-woocommerce-pro' ),
											'value'      => 'wbte_sc_bogo_puchase_history_returning',
											'is_checked' => 'wbte_sc_bogo_puchase_history_returning' === esc_attr( $purchase_history ),
										),
									),
								),
								'class'  => array( 'wbte_sc_bogo_radio_remove_val_if_hidden' ),
							)
						);
						?>
					</div>
					<div class="wbte_sc_bogo_purchase_history_returning_div wbte_sc_parent_div <?php echo 'wbte_sc_bogo_puchase_history_returning' === $purchase_history ? '' : 'wbte_sc_bogo_conditional_hidden'; ?>">
						<div class="wbte_sc_bogo_purchase_history_container_inner">
							<p class="wbte_sc_bogo_add_fields_p"><?php esc_html_e( 'No of orders', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>

							<div class="wbte_sc_bogo_edit_custom_drop_down_head">
								<div class="wbte_sc_bogo_custom_drop_btn wbte_sc_bogo_edit_custom_drop_down_btn wbte_sc_nth_order_condition_selected">
									<p><?php echo esc_html( $nth_cond_arr[ $nth_coupon_condition ] ); ?></p>
									<span class="dashicons dashicons-arrow-down-alt2"></span>
								</div>
								<div class="wbte_sc_bogo_edit_custom_drop_down">
									<?php

									foreach ( $nth_cond_arr as $key => $value ) {
										echo '<p data-val="' . esc_attr( $key ) . '" class="wbte_sc_bogo_edit_custom_drop_down_sub_btn wbte_sc_nth_coup_condition">' . esc_html( $value ) . '</p>';
									}
									?>
								</div>
								<input type="hidden" name="nth_coupon_no_of_coupon_condition" id="nth_coupon_no_of_coupon_condition" class="wbte_sc_bogo_avoid_if_hidden" value="<?php echo esc_attr( $nth_coupon_condition ); ?>">
							</div>
							<input type="text" id="wt_nth_order_no_of_orders" name="wt_nth_order_no_of_orders" class="wbte_sc_bogo_edit_input wbte_sc_bogo_input_only_number" value="<?php echo esc_attr( self::get_coupon_meta_value( $coupon_id, 'wt_nth_order_no_of_orders' ) ); ?>" style="width: 42px;">
						</div>
						<div class="wbte_sc_bogo_purchase_history_container_inner">
							<p class="wbte_sc_bogo_add_fields_p"><?php esc_html_e( 'With a minimum subtotal of ', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
							<div class="wbte_sc_bogo_icon_input">
								<input type="text" id="wt_nth_order_order_total" name="wt_nth_order_order_total" class="wbte_sc_bogo_input_only_numbers_with_decimal" value="<?php echo esc_attr( self::get_coupon_meta_value( $coupon_id, 'wt_nth_order_order_total' ) ); ?>">
								<div class="wbte_sc_bogo_icon_input_symbol">
									<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>
								</div>
							</div>
						</div>
						<div class="wbte_sc_bogo_purchase_history_container_inner wbte_sc_parent_div radio <?php echo ( empty( $date_from ) && empty( $date_to ) && empty( $purchase_days ) ) ? 'wbte_sc_bogo_conditional_hidden' : ''; ?>" data-row="date_range">    
							<p class="wbte_sc_bogo_edit_hide_row_btn wbte_sc_bogo_add_fields_p">
								<img src="<?php echo esc_url( $admin_img_path . 'remove_row.svg' ); ?>" alt="<?php esc_html_e( 'Remove', 'wt-smart-coupons-for-woocommerce-pro' ); ?>">
								<?php esc_html_e( 'Date range', 'wt-smart-coupons-for-woocommerce-pro' ); ?>
							</p>
							<div>
								<div class="wbte_sc_bogo_pch_date_days_div">
									<label class="wbte_sc_ctm_radio_container">
										<?php esc_html_e( 'Ordered in the last', 'wt-smart-coupons-for-woocommerce-pro' ); ?>
										<input type="radio" name="_nth_coupon_order_date_or_days" value="days" <?php checked( 'days', $date_or_days ); ?>>
										<span class="wbte_sc_radio_checkmark"></span>
									</label>
									<input type="text" id="_wt_sc_nth_order_within_days" name="_wt_sc_nth_order_within_days" class="wbte_sc_bogo_edit_input wbte_sc_bogo_input_only_number" value="<?php echo esc_attr( $purchase_days ); ?>" style="width: 42px; margin:0 5px;" <?php echo 'date' === $date_or_days ? ' disabled' : ''; ?>/>
									<?php esc_html_e( 'days', 'wt-smart-coupons-for-woocommerce-pro' ); ?>
								</div>
								<label class="wbte_sc_ctm_radio_container">
									<?php esc_html_e( 'Ordered between' ); ?>
									<input type="radio" name="_nth_coupon_order_date_or_days" value="date" <?php checked( 'date', $date_or_days ); ?>>
									<span class="wbte_sc_radio_checkmark"></span>
								</label>
								<div class="<?php echo 'days' === $date_or_days ? 'wbte_sc_bogo_conditional_hidden' : ''; ?>" id="wbte_sc_bogo_purchase_history_date_range">
									<div class="wbte_sc_bogo_purchase_history_date_range_from">
										<!-- From -->
										<label for="_wt_sc_nth_order_date_from">
											<p><?php esc_html_e( 'From', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
										</label>
										<input type="date" class="wbte_sc_bogo_date_picker" id="_wt_sc_nth_order_date_from" name="_wt_sc_nth_order_date_from"  value="<?php echo esc_attr( $date_from ); ?>" pattern="<?php echo esc_attr( $date_pattern ); ?>" >
									</div>
									<div class="wbte_sc_bogo_purchase_history_date_range_to">
										<!-- To -->
										<label for="_wt_sc_nth_order_date_to">
										<p><?php esc_html_e( 'To', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
										</label>
										<input type="date" class="wbte_sc_bogo_date_picker" id="_wt_sc_nth_order_date_to" name="_wt_sc_nth_order_date_to"  value="<?php echo esc_attr( $date_to ); ?>" pattern="<?php echo esc_attr( $date_pattern ); ?>" >
									</div>
								</div>
							</div>
						</div>
						<div class="wbte_sc_bogo_purchase_history_container_inner wbte_sc_parent_div <?php echo empty( $nth_order_status_selected ) ? 'wbte_sc_bogo_conditional_hidden' : ''; ?>" data-row="order_status">    
							<p class="wbte_sc_bogo_edit_hide_row_btn wbte_sc_bogo_add_fields_p">
								<img src="<?php echo esc_url( $admin_img_path . 'remove_row.svg' ); ?>" alt="<?php esc_html_e( 'Remove', 'wt-smart-coupons-for-woocommerce-pro' ); ?>">
								<?php esc_html_e( 'Order status', 'wt-smart-coupons-for-woocommerce-pro' ); ?>
							</p>
							<div style="flex-grow: 1;">
								<select id="wt_order_Status_need_to_count" name="wt_order_Status_need_to_count[]" multiple="multiple" style="width:90%;"  class="wc-enhanced-select" data-placeholder="<?php esc_attr_e( 'Please select', 'wt-smart-coupons-for-woocommerce-pro' ); ?>">
								<?php
									$nth_order_statuses = wc_get_order_statuses();

								foreach ( $nth_order_statuses  as $nth_order_status => $nth_order_status_text ) {
									$selected = '';
									if ( is_array( $nth_order_status_selected ) && in_array( $nth_order_status, $nth_order_status_selected, true ) ) {
										$selected = ' selected="selected"';
									}

									echo '<option value="' . esc_attr( $nth_order_status ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $nth_order_status_text ) . '</option>';
								}
								?>
								</select>
							</div>
						</div>
						<div class="wbte_sc_bogo_purchase_history_container_inner wbte_sc_parent_div <?php echo empty( $nth_order_products ) ? 'wbte_sc_bogo_conditional_hidden' : ''; ?>" data-row="products_purchased">    
							<p class="wbte_sc_bogo_edit_hide_row_btn wbte_sc_bogo_add_fields_p">
								<img src="<?php echo esc_url( $admin_img_path . 'remove_row.svg' ); ?>" alt="<?php esc_html_e( 'Remove', 'wt-smart-coupons-for-woocommerce-pro' ); ?>">
								<?php esc_html_e( 'Products purchased', 'wt-smart-coupons-for-woocommerce-pro' ); ?>
							</p>
							<div style="flex-grow: 1;">
								<select id="_wt_sc_nth_order_products" name="_wt_sc_nth_order_products[]" multiple="multiple" style="width: 90%;"  class="wc-product-search" data-placeholder="<?php esc_attr_e( 'No product selected', 'wt-smart-coupons-for-woocommerce-pro' ); ?>">
								<?php
								$nth_order_products = get_post_meta( $coupon_id, '_wt_sc_nth_order_products', true );
								if ( ! empty( $nth_order_products ) ) {
									foreach ( $nth_order_products as $product_id ) {
										$product = wc_get_product( $product_id );

										if ( is_object( $product ) ) {
											echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $product->get_formatted_name() ) . '</option>';
										}
									}
								}
								?>
								</select>
							</div>
						</div>
						<div class="wbte_sc_bogo_edit_filter_span_setion">
							<p style="color: #6E7681;"><?php esc_html_e( 'Filter by:', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
							<p class="wbte_sc_bogo_edit_filter_btn" data-row="date_range"><?php esc_html_e( '+ Date range', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
							<p class="wbte_sc_bogo_edit_filter_btn" data-row="order_status"><?php esc_html_e( '+ Order status', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
							<p class="wbte_sc_bogo_edit_filter_btn" data-row="products_purchased"><?php esc_html_e( '+ Products purchased', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
						</div>
					</div>
					
				</div>
			</td>
		</tr>
	</tbody>
</table>
