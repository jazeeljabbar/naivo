<?php
/**
 * BOGO edit page step 3
 *
 * @package    Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wbte_sc_bogo_edit_step">
	<div class="wbte_sc_bogo_edit_step_head">
		<p class="wbte_sc_bogo_edit_step_title"><?php esc_html_e( 'Step 3', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
		<p><?php esc_html_e( 'Apply offer', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
		<span class="wbte_sc_bogo_step_arrow dashicons"></span>
	</div>
	<div class="wbte_sc_bogo_edit_step_content">
		<div class="wbte_sc_bogo_step_opened">
			<table class="wbte_sc_bogo_edit_table wbte_sc_bogo_apply_repeatedly_table">
				<tbody>
					<tr>
						<td class="wbte_sc_bogo_edit_radio_fields" >
							<?php
							$apply_offer_times_selected = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_apply_offer' );
							echo $ds_obj->get_component(
								'radio-group inline',
								array(
									'values' => array(
										'name'  => 'wbte_sc_bogo_apply_offer',
										'items' => array(
											array(
												'label' => esc_html__( 'Once', 'wt-smart-coupons-for-woocommerce-pro' ),
												'value' => 'wbte_sc_bogo_apply_once',
												'is_checked' => esc_attr( 'wbte_sc_bogo_apply_once' === $apply_offer_times_selected ),
											),
											array(
												'label' => esc_html__( 'Repeatedly', 'wt-smart-coupons-for-woocommerce-pro' ),
												'value' => 'wbte_sc_bogo_apply_repeatedly',
												'is_checked' => esc_attr( 'wbte_sc_bogo_apply_repeatedly' === $apply_offer_times_selected ),
											),
											array(
												'label' => esc_html__( 'Based on custom rules', 'wt-smart-coupons-for-woocommerce-pro' ),
												'value' => 'wbte_sc_bogo_apply_custom',
												'is_checked' => esc_attr( 'wbte_sc_bogo_apply_custom' === $apply_offer_times_selected ),
											),
										),
									),
								)
							);
							?>
						</td>
					</tr>
					<tr class="wbte_sc_bogo_apply_once_row">
						<td colspan="2">
							<p class="wbte_sc_bogo_repeatedly_once_msg" style="margin-top:10px;"></p>
						</td>
					</tr>
					<tr class="wbte_sc_bogo_apply_repeatedly_row">
						<td colspan="2">
							<p class="wbte_sc_bogo_repeatedly_msg"></p>
						</td>
					</tr>
					<tr class="wbte_sc_bogo_apply_repeatedly_row">
						<td colspan="2">
							<p>
							<?php
							printf(
								// Translators: 1: Apply repeatedly limit.
								esc_html__( 'Limit for applying repeatedly %s times', 'wt-smart-coupons-for-woocommerce-pro' ),
								'<span><input type="text" id="wbte_sc_bogo_repeatedly_times" name="wbte_sc_bogo_repeatedly_times" class="wbte_sc_bogo_edit_input wbte_sc_bogo_input_only_number" value="' . esc_attr( self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_repeatedly_times' ) ) . '"></span>'
							);
							?>
							</p>
						</td>
					</tr>
					<tr class="wbte_sc_bogo_apply_repeatedly_custom_row">
						<?php
							$wbte_sc_bogo_apply_custom_min   = explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_apply_custom_min' ) );
							$wbte_sc_bogo_apply_custom_max   = explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_apply_custom_max' ) );
							$wbte_sc_bogo_apply_custom_times = explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_apply_custom_times' ) );
						?>
						<td colspan="2">
							<p class="wbte_sc_bogo_apply_custom_row_p">
								<?php
								if ( self::is_bxgx( $coupon_id ) ) {
									printf(
										wp_kses_post( __( '%s to %s items, Get %s', 'wt-smart-coupons-for-woocommerce-pro' ) ),
										'<span class="wbte_sc_bogo_step2_summary_customer_action wbte_sc_bogo_no_style_span"></span>&nbsp;<span class="wbte_sc_bogo_repeatedly_input_span wbte_sc_bogo_apply_custom_min_span_input"><input type="text" id="wbte_sc_bogo_apply_custom_min[0]" name="wbte_sc_bogo_apply_custom_min[0]" class="wbte_sc_bogo_edit_input wbte_sc_bogo_input_only_number" value=""></span><span class="wbte_sc_bogo_apply_custom_min_span"></span>',
										'<span class="wbte_sc_bogo_repeatedly_input_span wbte_sc_bogo_apply_custom_max_span_input"><input type="text" id="wbte_sc_bogo_apply_custom_max[0]" name="wbte_sc_bogo_apply_custom_max[0]" class="wbte_sc_bogo_edit_input wbte_sc_bogo_input_only_number" value=""></span><span class="wbte_sc_bogo_apply_custom_max_span"></span>',
										'<span class="wbte_sc_bogo_edit_gets_selected"></span>&nbsp;x&nbsp;<span class="wbte_sc_apply_custom_first_giveaway_qty"></span><input type="hidden" id="wbte_sc_bogo_apply_custom_times[0]" name="wbte_sc_bogo_apply_custom_times[0]" class="wbte_sc_bogo_edit_input wbte_sc_bogo_input_only_number" value="">'
									);
								} else {
									printf(
										wp_kses_post( __( 'Cart contains %s to %s items, Get %s x %s', 'wt-smart-coupons-for-woocommerce-pro' ) ),
										'<span class="wbte_sc_bogo_repeatedly_input_span wbte_sc_bogo_apply_custom_min_span_input"><input type="text" id="wbte_sc_bogo_apply_custom_min[0]" name="wbte_sc_bogo_apply_custom_min[0]" class="wbte_sc_bogo_edit_input wbte_sc_bogo_input_only_number" value=""></span><span class="wbte_sc_bogo_apply_custom_min_span"></span>',
										'<span class="wbte_sc_bogo_repeatedly_input_span wbte_sc_bogo_apply_custom_max_span_input"><input type="text" id="wbte_sc_bogo_apply_custom_max[0]" name="wbte_sc_bogo_apply_custom_max[0]" class="wbte_sc_bogo_edit_input wbte_sc_bogo_input_only_number" value=""></span><span class="wbte_sc_bogo_apply_custom_max_span"></span>',
										'<span class="wbte_sc_apply_custom_first_giveaway_qty"></span><input type="hidden" id="wbte_sc_bogo_apply_custom_times[0]" name="wbte_sc_bogo_apply_custom_times[0]" class="wbte_sc_bogo_edit_input wbte_sc_bogo_input_only_number" value="">',
										'<span class="wbte_sc_bogo_edit_gets_selected"></span>'
									);
								}
								?>
							</p>
						</td>
					</tr>
					<tr class="wbte_sc_bogo_apply_repeatedly_custom_range_hidden_row wbte_sc_bogo_conditional_hidden"> 
						<td colspan="2" style="display: flex; gap:7px;">
							<p class="wbte_sc_bogo_apply_custom_row_p">
								<?php
								printf(
									wp_kses_post( __( '%s to %s items, Get %s', 'wt-smart-coupons-for-woocommerce-pro' ) ),
									'<span class="wbte_sc_bogo_step2_summary_customer_action wbte_sc_bogo_no_style_span"></span>&nbsp;<input type="text" id="wbte_sc_bogo_apply_custom_min[x]" name="wbte_sc_bogo_apply_custom_min[x]" class="wbte_sc_bogo_edit_input wbte_sc_bogo_input_only_number wbte_sc_bogo_exclude_serialize" value="">',
									'<input type="text"
									id="wbte_sc_bogo_apply_custom_max[x]" name="wbte_sc_bogo_apply_custom_max[x]" class="wbte_sc_bogo_edit_input wbte_sc_bogo_input_only_number wbte_sc_bogo_exclude_serialize" value="">',
									'<span class="wbte_sc_bogo_edit_gets_selected">' . esc_html( $customer_gets[ $customer_gets_selected ] ) . '</span> &nbsp;x&nbsp;<input type="text" id="wbte_sc_bogo_apply_custom_times[x]" name="wbte_sc_bogo_apply_custom_times[x]" class="wbte_sc_bogo_edit_input wbte_sc_bogo_input_only_number wbte_sc_bogo_exclude_serialize" value="">'
								);
								?>
								<img style="right: 0; position: relative;" class="wbte_sc_bogo_edit_trash" src="<?php echo esc_url( $admin_img_path ); ?>trash.svg" alt="<?php esc_html_e( 'Trash', 'wt-smart-coupons-for-woocommerce-pro' ); ?>">
							</p>
							
						</td>
					</tr>
					<tr class="wbte_sc_bogo_apply_repeatedly_custom_range_cheap_exp_hidden_row wbte_sc_bogo_conditional_hidden"> 
						<td colspan="2" style="display: flex; gap:7px;">
							<p class="wbte_sc_bogo_apply_custom_row_p">
								<?php
								printf(
									wp_kses_post( __( 'Cart contains %s to %s items, Get %s', 'wt-smart-coupons-for-woocommerce-pro' ) ),
									'<input type="text" id="wbte_sc_bogo_apply_custom_min[x]" name="wbte_sc_bogo_apply_custom_min[x]" class="wbte_sc_bogo_edit_input wbte_sc_bogo_input_only_number wbte_sc_bogo_exclude_serialize" value="">',
									'<input type="text"
									id="wbte_sc_bogo_apply_custom_max[x]" name="wbte_sc_bogo_apply_custom_max[x]" class="wbte_sc_bogo_edit_input wbte_sc_bogo_input_only_number wbte_sc_bogo_exclude_serialize" value="">',
									'<input type="text" id="wbte_sc_bogo_apply_custom_times[x]" name="wbte_sc_bogo_apply_custom_times[x]" class="wbte_sc_bogo_edit_input wbte_sc_bogo_input_only_number wbte_sc_bogo_exclude_serialize" value=""> &nbsp;x&nbsp;<span class="wbte_sc_bogo_edit_gets_selected">' . esc_html( $customer_gets[ $customer_gets_selected ] ) . '</span>'
								);
								?>
								<img style="right: 0; position: relative;" class="wbte_sc_bogo_edit_trash" src="<?php echo esc_url( $admin_img_path ); ?>trash.svg" alt="<?php esc_html_e( 'Trash', 'wt-smart-coupons-for-woocommerce-pro' ); ?>">
							</p>
							
						</td>
					</tr>
					<?php
					$repeatedly_data = array();
					$last_index      = count( $wbte_sc_bogo_apply_custom_times ) - 1;
					foreach ( array_slice( $wbte_sc_bogo_apply_custom_times, 1, null, true ) as $repeat_index => $repeat_value ) {
						if ( empty( $repeat_value ) ) {
							continue;
						}
						$repeatedly_data[ $repeat_index ] = array(
							'qty_min' => $wbte_sc_bogo_apply_custom_min[ $repeat_index ],
							'qty_max' => $wbte_sc_bogo_apply_custom_max[ $repeat_index ],
							'times'   => $wbte_sc_bogo_apply_custom_times[ $repeat_index ],
						);
					}
					$field_index = 1;
					foreach ( $repeatedly_data as $repeat_index => $data ) {
						?>
								<tr class="wbte_sc_bogo_apply_repeatedly_custom_row wbte_sc_bogo_apply_repeatedly_custom_range_row"> 
									<td colspan="2" style="display: flex; gap:7px;">
										<p>
											<?php
											$qty_max = ( ( $repeat_index === $last_index ) && ( 0 === intval( $data['qty_max'] ) ) ) ? '' : $data['qty_max'];
											if ( self::is_bxgx( $coupon_id ) ) {

												printf(
													wp_kses_post( __( '%s to %s items, Get %s x %s', 'wt-smart-coupons-for-woocommerce-pro' ) ),
													'<span class="wbte_sc_bogo_step2_summary_customer_action wbte_sc_bogo_no_style_span"></span>&nbsp;<input type="text" id="wbte_sc_bogo_apply_custom_min[' . esc_attr( $field_index ) . ']" name="wbte_sc_bogo_apply_custom_min[' . esc_attr( $field_index ) . ']" class="wbte_sc_bogo_edit_input wbte_sc_bogo_input_only_number" value="' . esc_attr( $data['qty_min'] ) . '">',
													'<input type="text" id="wbte_sc_bogo_apply_custom_max[' . esc_attr( $field_index ) . ']" name="wbte_sc_bogo_apply_custom_max[' . esc_attr( $field_index ) . ']" class="wbte_sc_bogo_edit_input wbte_sc_bogo_input_only_number" value="' . esc_attr( $qty_max ) . '">',
													'<span class="wbte_sc_bogo_edit_gets_selected">' . esc_html( $customer_gets[ $customer_gets_selected ] ) . '</span>',
													'<input type="text" id="wbte_sc_bogo_apply_custom_times[' . esc_attr( $field_index ) . ']" name="wbte_sc_bogo_apply_custom_times[' . esc_attr( $field_index ) . ']" class="wbte_sc_bogo_edit_input wbte_sc_bogo_input_only_number" value="' . esc_attr( $data['times'] ) . '">'
												);

											} else {

												printf(
													wp_kses_post( __( 'Cart contains %s to %s items, Get %s x %s', 'wt-smart-coupons-for-woocommerce-pro' ) ),
													'<input type="text" id="wbte_sc_bogo_apply_custom_min[' . esc_attr( $field_index ) . ']" name="wbte_sc_bogo_apply_custom_min[' . esc_attr( $field_index ) . ']" class="wbte_sc_bogo_edit_input wbte_sc_bogo_input_only_number" value="' . esc_attr( $data['qty_min'] ) . '">',
													'<input type="text" id="wbte_sc_bogo_apply_custom_max[' . esc_attr( $field_index ) . ']" name="wbte_sc_bogo_apply_custom_max[' . esc_attr( $field_index ) . ']" class="wbte_sc_bogo_edit_input wbte_sc_bogo_input_only_number" value="' . esc_attr( $qty_max ) . '">',
													'<input type="text" id="wbte_sc_bogo_apply_custom_times[' . esc_attr( $field_index ) . ']" name="wbte_sc_bogo_apply_custom_times[' . esc_attr( $field_index ) . ']" class="wbte_sc_bogo_edit_input wbte_sc_bogo_input_only_number" value="' . esc_attr( $data['times'] ) . '">',
													'<span class="wbte_sc_bogo_edit_gets_selected">' . esc_html( $customer_gets[ $customer_gets_selected ] ) . '</span>',
												);
											}

											?>
											<img style="right: 0; position: relative;" class="wbte_sc_bogo_edit_trash" src="<?php echo esc_url( $admin_img_path ); ?>trash.svg" alt="<?php esc_attr_e( 'Trash', 'wt-smart-coupons-for-woocommerce-pro' ); ?>" title="<?php esc_attr_e( 'Delete range', 'wt-smart-coupons-for-woocommerce-pro' ); ?>">
										</p>
									</td>
								</tr>
							<?php
							++$field_index;
					}
					?>
					
					<tr class="wbte_sc_bogo_repeatedly_custom_range_btn_row wbte_sc_bogo_apply_repeatedly_custom_row">
						<td>
							<p class="wbte_sc_bogo_repeatedly_custom_range_btn"><img src="<?php echo esc_url( $admin_img_path ); ?>add_range.svg" alt="<?php esc_attr_e( 'add_range', 'wt-smart-coupons-for-woocommerce-pro' ); ?>"><?php esc_attr_e( 'Add rule', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class="wbte_sc_bogo_step_short_description wbte_sc_bogo_custom_summary">
			<!-- Value assign in js -->
			<span class="wbte_sc_bogo_apply_repeatedly_short wbte_sc_bogo_no_style_span"></span>
			<span class="wbte_sc_bogo_repeatedly_additional_summary wbte_sc_bogo_no_style_span"></span>
		</div>
	</div>
	<span class="wbte_sc_bogo_step_arrow dashicons"></span>
</div>