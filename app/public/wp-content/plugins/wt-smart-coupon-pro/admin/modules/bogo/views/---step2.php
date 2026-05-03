<?php
/**
 * BOGO edit page step 2
 *
 * @since 3.0.0
 * @package    Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<style type="text/css">
.wbte_sc_bogo_email_select_inner span.invalid::after{ content:"<?php esc_html_e( 'Invalid email address', 'wt-smart-coupons-for-woocommerce-pro' ); ?>"; }
</style>

<div class="wbte_sc_bogo_edit_step">
	<div class="wbte_sc_bogo_edit_step_head">
		<p class="wbte_sc_bogo_edit_step_title"><?php esc_html_e( 'Step 2', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
		<p><?php esc_html_e( 'Trigger', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
		<span class="wbte_sc_bogo_step_arrow dashicons"></span>
	</div>
	<div class="wbte_sc_bogo_edit_step_content">
		<div class="wbte_sc_bogo_step_opened">
			<table class="wbte_sc_bogo_edit_table">
				<tbody>
					<tr valign="top" class="<?php echo 'wbte_sc_bogo_cheap_expensive' === $selected_discount_type ? 'wbte_sc_hide_bxgx_fields' : ''; ?>">
						<th>
							<p><?php esc_html_e( 'Customer', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
						</th>
						<td class="wbte_sc_bogo_edit_radio_fields" >
						<?php
							echo $ds_obj->get_component(
								'radio-group multi-line',
								array(
									'values' => array(
										'name'  => 'wbte_sc_bogo_triggers_when',
										'items' => array(
											array(
												'label' => esc_html__( 'Buys quantities of', 'wt-smart-coupons-for-woocommerce-pro' ),
												'value' => 'wbte_sc_bogo_triggers_qty',
												'is_checked' => esc_attr( 'wbte_sc_bogo_triggers_qty' === $selected_triggers_when ),
											),
											array(
												'label' => esc_html__( 'Spends subtotal of', 'wt-smart-coupons-for-woocommerce-pro' ),
												'value' => 'wbte_sc_bogo_triggers_subtotal',
												'is_checked' => esc_attr( 'wbte_sc_bogo_triggers_subtotal' === $selected_triggers_when ),
											),
										),
									),
								)
							);
							?>
						</td>
					</tr>
					<tr class="<?php echo 'wbte_sc_bogo_triggers_subtotal' === $selected_triggers_when ? '' : 'wbte_sc_bogo_conditional_hidden '; ?> wbte_sc_bogo_edit_minmax_amount">
						<th>
							<label for="_wbte_sc_bogo_min_amount"><?php esc_html_e( 'Min amount', 'wt-smart-coupons-for-woocommerce-pro' ); ?></label>
						</th>
						<td>
							<div class="wbte_sc_bogo_icon_input">
								<input type="text" id="_wbte_sc_bogo_min_amount" name="_wbte_sc_bogo_min_amount" class="wbte_sc_bogo_input_only_numbers_with_decimal" value="<?php echo esc_attr( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_min_amount' ) ); ?>">
								<div class="wbte_sc_bogo_icon_input_symbol">
									<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>
								</div>
							</div>
						</td>
					</tr>
					<tr class="<?php echo 'wbte_sc_bogo_triggers_subtotal' === $selected_triggers_when ? '' : 'wbte_sc_bogo_conditional_hidden '; ?> wbte_sc_bogo_edit_minmax_amount">
						<th>
							<label for="_wbte_sc_bogo_max_amount" class="wbte_sc_bogo_edit_flex_label"><?php esc_html_e( 'Max amount', 'wt-smart-coupons-for-woocommerce-pro' ); ?></label><span><?php esc_html_e( '(optional)', 'wt-smart-coupons-for-woocommerce-pro' ); ?></span>
						</th>
						<td>
							<div class="wbte_sc_bogo_icon_input">
								<input type="text" id="_wbte_sc_bogo_max_amount" name="_wbte_sc_bogo_max_amount" class="wbte_sc_bogo_input_only_numbers_with_decimal" value="<?php echo esc_attr( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_max_amount' ) ); ?>">
								<div class="wbte_sc_bogo_icon_input_symbol">
									<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>
								</div>
							</div>
						</td>
					</tr>
					<?php
					if ( 'wbte_sc_bogo_bxgx' === $selected_discount_type ) {
						?>
					<tr class="<?php echo 'wbte_sc_bogo_triggers_qty' === $selected_triggers_when ? '' : 'wbte_sc_bogo_conditional_hidden '; ?> wbte_sc_bogo_edit_minmax_qty">
						<th>
							<label for="_wbte_sc_bogo_min_qty"><?php esc_html_e( 'Min quantity', 'wt-smart-coupons-for-woocommerce-pro' ); ?></label>
						</th>
						<td>
							<input type="text" id="_wbte_sc_bogo_min_qty" name="_wbte_sc_bogo_min_qty" class="wbte_sc_bogo_edit_input wbte_sc_bogo_input_only_number" value="<?php echo esc_attr( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_min_qty' ) ); ?>">
						</td>
					</tr>
					<tr class="<?php echo 'wbte_sc_bogo_triggers_qty' === $selected_triggers_when ? '' : 'wbte_sc_bogo_conditional_hidden '; ?> wbte_sc_bogo_edit_minmax_qty">
						<th>
							<label for="_wbte_sc_bogo_max_qty" class="wbte_sc_bogo_edit_flex_label"><?php esc_html_e( 'Max quantity', 'wt-smart-coupons-for-woocommerce-pro' ); ?></label><span><?php esc_html_e( '(optional)', 'wt-smart-coupons-for-woocommerce-pro' ); ?></span>
						</th>
						<td>
							<input type="text" id="_wbte_sc_bogo_max_qty" name="_wbte_sc_bogo_max_qty" class="wbte_sc_bogo_edit_input wbte_sc_bogo_input_only_number" value="<?php echo esc_attr( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_max_qty' ) ); ?>">
						</td>
					</tr>
						<?php
					} else {
						?>
					<tr class="wbte_sc_bogo_edit_minmax_qty wbte_sc_bogo_cheap_exp_qty_tr">
						<th>
							<label for="_wbte_sc_bogo_min_qty"><?php esc_html_e( 'Cart contains quantity of min.', 'wt-smart-coupons-for-woocommerce-pro' ); ?></label>
							<input type="text" id="_wbte_sc_bogo_min_qty" name="_wbte_sc_bogo_min_qty" class="wbte_sc_bogo_edit_input wbte_sc_bogo_input_only_number" value="<?php echo esc_attr( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_min_qty' ) ); ?>">
							<label for="_wbte_sc_bogo_max_qty"><?php esc_html_e( 'to max.', 'wt-smart-coupons-for-woocommerce-pro' ); ?></label>
							<input type="text" id="_wbte_sc_bogo_max_qty" name="_wbte_sc_bogo_max_qty" class="wbte_sc_bogo_edit_input wbte_sc_bogo_input_only_number" value="<?php echo esc_attr( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_max_qty' ) ); ?>" placeholder="<?php esc_attr_e( 'Optional', 'wt-smart-coupons-for-woocommerce-pro' ); ?>">
						</th>
					</tr>
					
					<?php } ?>
				</tbody>
			</table>
			<!-- Customer buys -->
			<table class="wbte_sc_bogo_edit_table wbte_sc_bogo_customer_buys_table">
				<tbody>
					<tr>
						<th colspan="2">
							<div class="wbte_sc_bogo_edit_custom_drop_down_head">
								<p><?php esc_html_e( 'Customer buys', 'wt-smart-coupons-for-woocommerce-pro' ); ?>
									<span class="wbte_sc_bogo_edit_add_button wbte_sc_bogo_edit_add_customer_buys"><?php esc_html_e( '+ Add', 'wt-smart-coupons-for-woocommerce-pro' ); ?></span>
								</p>
								<div class="wbte_sc_bogo_edit_customer_buys_select wbte_sc_bogo_edit_custom_drop_down">
									<p data-row="wbte_sc_bogo_edit_products_row"><?php esc_html_e( 'Product restriction', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
									<p data-row="wbte_sc_bogo_edit_categories_row"><?php esc_html_e( 'Category restriction', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
								</div>
							</div>
						</th>
					</tr>
					<?php
						$specific_products   = array_filter( explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_product_ids' ) ) );
						$excluded_products   = array_filter( explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_exclude_product_ids' ) ) );
						$specific_categories = array_filter( explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_product_categories' ) ) );
						$excluded_categories = array_filter( explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_exclude_product_categories' ) ) );
					?>
					<!-- Product restriction -->
					<tr class="<?php echo ( empty( $specific_products ) && empty( $excluded_products ) ) ? 'wbte_sc_bogo_conditional_hidden ' : ' '; ?> wbte_sc_bogo_edit_products_categories_row" data-row="wbte_sc_bogo_edit_products_row">
						<td colspan="2">
							<div class="wbte_sc_bogo_edit_products_categories_tab">
								<div class="wbte_sc_bogo_edit_custom_drop_down_head">
									<div class="wbte_sc_bogo_product_restriction_select_btn wbte_sc_bogo_edit_custom_drop_down_btn">
										<p><?php ! empty( $excluded_products ) ? esc_html_e( 'Any product(s) except', 'wt-smart-coupons-for-woocommerce-pro' ) : esc_html_e( 'Specific product(s) only', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
										<span class="dashicons dashicons-arrow-down-alt2"></span>
									</div>
									<div class="wbte_sc_bogo_edit_custom_drop_down">
										<p data-group="wbte_sc_prod_row" data-val="specific" class="wbte_sc_bogo_edit_specific_prod_btn wbte_sc_bogo_prod_cat_restriction_sub_btn wbte_sc_bogo_edit_custom_drop_down_sub_btn wbte_sc_bogo_excl_sel_icn"><?php esc_html_e( 'Specific product(s) only', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
										<p data-group="wbte_sc_prod_row" data-val="exclude" class="wbte_sc_bogo_edit_excluded_prod_btn wbte_sc_bogo_prod_cat_restriction_sub_btn wbte_sc_bogo_edit_custom_drop_down_sub_btn wbte_sc_bogo_excl_sel_icn"><?php esc_html_e( 'Any product(s) except', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
									</div>
									<input type="hidden" value="<?php echo ! empty( $excluded_products ) ? 'exclude' : 'specific'; ?>">
								</div>

								<div class="wbte_sc_bogo_edit_specific_products_row <?php echo ! empty( $excluded_products ) ? 'wbte_sc_bogo_conditional_hidden ' : ' '; ?>">
									<select id="wbte_sc_bogo_specific_products" class="wc-product-search" multiple="multiple" style="width: 95%;" name="wbte_sc_bogo_product_ids[]" data-placeholder="<?php esc_attr_e( 'Search for product', 'wt-smart-coupons-for-woocommerce-pro' ); ?>" data-action="woocommerce_json_search_products_and_variations">
									<?php
									foreach ( $specific_products as $product_id ) {
										$product = wc_get_product( $product_id );
										if ( is_object( $product ) ) {
											echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . esc_html( wp_strip_all_tags( $product->get_formatted_name() ) ) . '</option>';
										}
									}
									?>
									</select>
									<br>
									<?php
									echo $ds_obj->get_component(
										'checkbox normal',
										array(
											'values' => array(
												'name'  => '_wt_product_condition',
												'id'    => 'wbte_sc_bogo_specific_products_all',
												'value' => 'and',
												'is_checked' => esc_attr( 'and' === self::get_coupon_meta_value( $coupon_id, '_wt_product_condition' ) ),
												'label' => esc_html__( 'Apply coupon only if all the selected products are in the cart', 'wt-smart-coupons-for-woocommerce-pro' ),
											),
											'class'  => array( 'wbte_sc_bogo_prod_cat_condition_checkbox' ),
										)
									);
									?>
								</div>

								<div class="wbte_sc_bogo_edit_excluded_products_row <?php echo empty( $excluded_products ) ? 'wbte_sc_bogo_conditional_hidden ' : ' '; ?>">
									<select id="wbte_sc_bogo_excluded_products" class="wc-product-search" multiple="multiple" style="width: 95%;" name="wbte_sc_bogo_exclude_product_ids[]" data-placeholder="<?php esc_attr_e( 'Search for product', 'wt-smart-coupons-for-woocommerce-pro' ); ?>" data-action="woocommerce_json_search_products_and_variations">
									<?php
									foreach ( $excluded_products as $product_id ) {
										$product = wc_get_product( $product_id );
										if ( is_object( $product ) ) {
											echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . esc_html( wp_strip_all_tags( $product->get_formatted_name() ) ) . '</option>';
										}
									}
									?>
									</select>
								</div>
								<?php echo wp_kses_post( $trash_icon ); ?>
							</div>
						</td>
					</tr>
					<tr class="<?php echo ( ( ! empty( $specific_products ) || ! empty( $excluded_products ) ) && ( ! empty( $specific_categories ) || ! empty( $excluded_categories ) ) ) ? '' : 'wbte_sc_bogo_conditional_hidden '; ?> wbte_sc_bogo_prod_cat_and_or_row">
						<td colspan="2">
							<div class="wbte_sc_bogo_prod_cat_and_or wbte_sc_bogo_tab_btn_radio">
								<?php $selected_and_or = self::get_coupon_meta_value( $coupon_id, '_wbte_sc_product_cat_condition' ); ?>
								<label>
									<input type="radio" name="_wbte_sc_product_cat_condition" id="wbte_sc_bogo_prod_cat_and" value="and" <?php checked( 'and', $selected_and_or ); ?> />
									<div class="first box">
									<span><?php esc_html_e( 'And', 'wt-smart-coupons-for-woocommerce-pro' ); ?></span>
									</div>
								</label>
								<label>
									<input type="radio" name="_wbte_sc_product_cat_condition" id="wbte_sc_bogo_prod_cat_or" value="or" <?php checked( 'or', $selected_and_or ); ?> />
									<div class="second box">
									<span><?php esc_html_e( 'Or', 'wt-smart-coupons-for-woocommerce-pro' ); ?></span>
									</div>
								</label>
								<div></div>
							</div>
						</td>
					</tr>
					<!-- Category restriction -->
					<tr class="<?php echo ( empty( $specific_categories ) && empty( $excluded_categories ) ) ? 'wbte_sc_bogo_conditional_hidden ' : ' '; ?> wbte_sc_bogo_edit_products_categories_row" data-row="wbte_sc_bogo_edit_categories_row">
						<td colspan="2">
							<div class="wbte_sc_bogo_edit_products_categories_tab">
								<div class="wbte_sc_bogo_edit_custom_drop_down_head">
									<div class="wbte_sc_bogo_cat_restriction_select_btn wbte_sc_bogo_edit_custom_drop_down_btn">
										<p><?php ! empty( $excluded_categories ) ? esc_html_e( 'From any category except', 'wt-smart-coupons-for-woocommerce-pro' ) : esc_html_e( 'From specific categories only', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
										<span class="dashicons dashicons-arrow-down-alt2"></span>
									</div>
									<div class="wbte_sc_bogo_edit_custom_drop_down">
										<p data-group="wbte_sc_cat_row" data-val="specific" class="wbte_sc_bogo_edit_specific_cat_btn wbte_sc_bogo_prod_cat_restriction_sub_btn wbte_sc_bogo_edit_custom_drop_down_sub_btn wbte_sc_bogo_excl_sel_icn"><?php esc_html_e( 'From specific categories only', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
										<p data-group="wbte_sc_cat_row" data-val="exclude" class="wbte_sc_bogo_edit_excluded_cat_btn wbte_sc_bogo_prod_cat_restriction_sub_btn wbte_sc_bogo_edit_custom_drop_down_sub_btn wbte_sc_bogo_excl_sel_icn"><?php esc_html_e( 'From any category except', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
									</div>
									<input type="hidden" value="<?php echo ! empty( $excluded_categories ) ? 'exclude' : 'specific'; ?>">
								</div>

								<div class="wbte_sc_bogo_edit_specific_cat_row <?php echo ! empty( $excluded_categories ) ? 'wbte_sc_bogo_conditional_hidden ' : ' '; ?>">
									<select id="product_categories" name="wbte_sc_bogo_product_categories[]" style="width: 95%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Any category', 'wt-smart-coupons-for-woocommerce-pro' ); ?>">
									<?php
									$categories = get_terms( 'product_cat' );

									if ( $categories ) {
										foreach ( $categories as $category ) {
											echo '<option value="' . esc_attr( $category->term_id ) . '"' . esc_attr( wc_selected( $category->term_id, $specific_categories ) ) . '>' . esc_html( $category->name ) . '</option>';
										}
									}
									?>
									</select>
									<?php
									echo $ds_obj->get_component(
										'checkbox normal',
										array(
											'values' => array(
												'name'  => '_wt_category_condition',
												'id'    => 'wbte_sc_bogo_specific_categories_all',
												'value' => 'and',
												'is_checked' => esc_attr( 'and' === self::get_coupon_meta_value( $coupon_id, '_wt_category_condition' ) ),
												'label' => esc_html__( 'Apply coupon only if products from all the selected categories are in the cart', 'wt-smart-coupons-for-woocommerce-pro' ),
											),
											'class'  => array( 'wbte_sc_bogo_prod_cat_condition_checkbox' ),
										)
									);
									?>
								</div>

								<div class="wbte_sc_bogo_edit_excluded_cat_row <?php echo empty( $excluded_categories ) ? 'wbte_sc_bogo_conditional_hidden ' : ' '; ?>">
									<select id="exclude_product_categories" name="wbte_sc_bogo_exclude_product_categories[]" style="width: 95%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e( 'No categories', 'wt-smart-coupons-for-woocommerce-pro' ); ?>">
									<?php
									$categories = get_terms( 'product_cat' );

									if ( $categories ) {
										foreach ( $categories as $category ) {
											echo '<option value="' . esc_attr( $category->term_id ) . '"' . esc_attr( wc_selected( $category->term_id, $excluded_categories ) ) . '>' . esc_html( $category->name ) . '</option>';
										}
									}
									?>
									</select>
								</div>

								<?php echo wp_kses_post( $trash_icon ); ?>
							</div>
						</td>
					</tr>
					<tr class="<?php echo ( ! empty( $specific_products ) || ! empty( $excluded_products ) || ! empty( $specific_categories ) || ! empty( $excluded_categories ) ) ? 'wbte_sc_bogo_conditional_hidden ' : ''; ?> wbte_sc_bogo_prod_cat_default_row">
						<td colspan="2"><?php esc_html_e( 'Any product (default)', 'wt-smart-coupons-for-woocommerce-pro' ); ?></td>
					</tr>
				</tbody>
			</table>
			<!-- Additonal conditions -->
			<?php include_once '----step2-optional-conditions.php'; ?>
		</div>
		<div class="wbte_sc_bogo_step_short_description wbte_sc_bogo_step2_short_description">
			<!-- Values will assign from js -->
			<p></p>
		</div>
		<br>
		<div class="wbte_sc_bogo_step_add_desc"></div>
	</div>
</div>