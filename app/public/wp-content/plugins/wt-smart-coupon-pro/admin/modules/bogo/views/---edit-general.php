<?php
/**
 * BOGO other fields in edit page
 * eg:- Schedule, Auto, title, description etc
 *
 * @package    Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$start_date       = get_post_meta( $coupon_id, '_wt_coupon_start_date', true );
$end_date         = ! is_null( $coupon->get_date_expires() ) ? $coupon->get_date_expires()->date( 'Y-m-d' ) : '';
$schedule_enabled = 'yes' === self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_schedule' );

// Get today's date in 'Y-m-d' format for the 'min' attribute.
$today_date = gmdate( 'Y-m-d' );

// Start time.
$start_time          = get_post_meta( $coupon_id, '_wt_coupon_start_time', true );
$start_time_arr      = $start_time ? explode( ':', $start_time ) : array();
$start_time_hour     = isset( $start_time_arr[0] ) ? Wt_Smart_Coupon_Lifespan::get_instance()->format_start_expiry_time( $start_time_arr[0] ) : 00;
$start_time_meridiem = __( 'AM', 'wt-smart-coupons-for-woocommerce-pro' );
if ( 12 < $start_time_hour ) {
	$start_time_meridiem = __( 'PM', 'wt-smart-coupons-for-woocommerce-pro' );
	$start_time_hour    -= 12;
}
$start_time_hour   = ( '00' === $start_time_hour ) ? 12 : $start_time_hour;
$start_time_minute = isset( $start_time_arr[1] ) ? Wt_Smart_Coupon_Lifespan::get_instance()->format_start_expiry_time( $start_time_arr[1] ) : 00;

if ( '' === $start_time ) {
	$start_time_hour   = 11;
	$start_time_minute = 59;
}

// Expiry.
$expiry_time          = get_post_meta( $coupon_id, '_wt_coupon_expiry_time', true );
$expiry_time_arr      = $expiry_time ? explode( ':', $expiry_time ) : array();
$expiry_time_hour     = isset( $expiry_time_arr[0] ) ? Wt_Smart_Coupon_Lifespan::get_instance()->format_start_expiry_time( $expiry_time_arr[0] ) : '';
$expiry_time_meridiem = __( 'AM', 'wt-smart-coupons-for-woocommerce-pro' );
if ( 12 < $expiry_time_hour ) {
	$expiry_time_meridiem = __( 'PM', 'wt-smart-coupons-for-woocommerce-pro' );
	$expiry_time_hour    -= 12;
}
$expiry_time_hour   = ( '00' === $expiry_time_hour ) ? 12 : $expiry_time_hour;
$expiry_time_minute = isset( $expiry_time_arr[1] ) ? Wt_Smart_Coupon_Lifespan::get_instance()->format_start_expiry_time( $expiry_time_arr[1] ) : '';

if ( '' === $expiry_time ) {
	$expiry_time_hour   = 11;
	$expiry_time_minute = 59;
}

$is_master_coupon = get_post_meta( $coupon_id, Wt_Smart_Coupon_Admin::$master_coupon_meta_key, true );

// Expiry in days.
$expiry_in_days_enabled = "1" === self::get_coupon_meta_value( $coupon_id, '_wt_coupon_enable_days' );
$expiry_in_days = self::get_coupon_meta_value( $coupon_id, '_wt_coupon_expiry_in_days' );
?>

	<div class="wbte_sc_bogo_edit_general">

		<div class="wbte_sc_bogo_tab_btn_radio wbte_sc_bogo_edit_gnrl_sts_radio 
		<?php
		echo isset( $_GET['newly_created'] ) ? ' hide' : '';
		echo $is_master_coupon ? ' wbte_sc_bogo_master_coupon' : '';
		?>
		">
			<?php
			$_coupon_sts = $coupon->get_status();
			if ( 'publish' !== $_coupon_sts ) {
				$_coupon_sts = 'draft';
			}
			?>
			<span><?php esc_html_e( 'Offer:', 'wt-smart-coupons-for-woocommerce-pro' ); ?></span>&nbsp;
			<label>
				<input type="radio" name="_wbte_sc_bogo_selected_sts" id="_wbte_sc_bogo_selected_sts_publish" value="publish" 
				<?php
				checked( 'publish', $_coupon_sts );
				echo $is_master_coupon ? ' disabled' : '';
				?>
				/>
				<div class="first box active">
				<span><?php esc_html_e( 'Active', 'wt-smart-coupons-for-woocommerce-pro' ); ?></span>
				</div>
			</label>
			<label>
				<input type="radio" name="_wbte_sc_bogo_selected_sts" id="_wbte_sc_bogo_selected_sts_draft" value="draft" 
				<?php
				checked( 'draft', $_coupon_sts );
				echo $is_master_coupon ? ' disabled' : '';
				?>
				/>
				<div class="second box inactive">
				<span><?php esc_html_e( 'Inactive', 'wt-smart-coupons-for-woocommerce-pro' ); ?></span>
				</div>
			</label>
		</div>
		<br><br>
		<div>
			<label for="wbte_sc_bogo_coupon_name" class="wbte_sc_bogo_input_title"><?php esc_html_e( 'Offer name', 'wt-smart-coupons-for-woocommerce-pro' ); ?></label>
			<?php echo wp_kses_post( wc_help_tip( __( 'The offer title is used to identify a BOGO campaign within the plugin and the store.', 'wt-smart-coupons-for-woocommerce-pro' ) ) ); ?><br>
			<input type="text" id="wbte_sc_bogo_coupon_name" name="wbte_sc_bogo_coupon_name" class="wbte_sc_bogo_text_input" placeholder="<?php esc_attr_e( 'Offer name', 'wt-smart-coupons-for-woocommerce-pro' ); ?>" value="<?php echo esc_html( self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_coupon_name' ) ); ?>">
		</div>
		<br>

		<label for="woocommerce-coupon-description" class="wbte_sc_bogo_input_title"><?php esc_html_e( 'Description', 'wt-smart-coupons-for-woocommerce-pro' ); ?></label>
		<?php echo wp_kses_post( wc_help_tip( __( 'Add a short note to display on the coupon to help customers better understand the rules.', 'wt-smart-coupons-for-woocommerce-pro' ) ) ); ?>
		<br>
		<textarea type="text" id="woocommerce-coupon-description" name="woocommerce-coupon-description" class="wbte_sc_bogo_text_input" placeholder="<?php esc_attr_e( 'Description', 'wt-smart-coupons-for-woocommerce-pro' ); ?>" rows="5" ><?php echo esc_html( $coupon->get_description() ); ?></textarea><br>

		<p><?php esc_html_e( 'Activate offer', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>

		<?php
		echo $ds_obj->get_component(
			'radio-group multi-line',
			array(
				'values' => array(
					'name'  => 'wbte_sc_bogo_code_condition',
					'items' => array(
						array(
							'label' => sprintf( 
								__( 'Automatically %s %s %s', 'wt-smart-coupons-for-woocommerce-pro' ), 
								wp_kses_post( wc_help_tip( __( 'The offer is automatically applied when eligible products are added to the cart', 'wt-smart-coupons-for-woocommerce-pro' ) ) ), 
								wp_kses_post( '<span class="wbte_sc_bogo_code_copy_container"><span class="wbte_sc_hidden_tooltip">' . __( 'Copy coupon code for admin use', 'wt-smart-coupons-for-woocommerce-pro' ) . '</span><img class="wbte_sc_bogo_code_copy" src="' . esc_url( "{$admin_img_path}copy.svg" ) . '" alt="' . esc_attr__( 'copy code', 'wt-smart-coupons-for-woocommerce-pro' ) . '" /></span>' ), 
								wp_kses_post( '<span class="wbte_sc_bogo_help_text wbte_sc_bogo_code_cond_help_txt">' . __( 'Offer name will be displayed in the cart summary when offer is applied', 'wt-smart-coupons-for-woocommerce-pro' ) . '</span>' ) 
							),
							'value' => 'wbte_sc_bogo_code_auto',
							'is_checked' => 'wbte_sc_bogo_code_auto' === self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_code_condition' ),
						),
						array(
							'label' => sprintf( esc_html__( 'Through coupon code %s', 'wt-smart-coupons-for-woocommerce-pro' ), wp_kses_post( wc_help_tip( __( 'The user must enter the coupon code after adding eligible items to the cart to redeem the offer', 'wt-smart-coupons-for-woocommerce-pro' ) ) ) ),
							'value' => 'wbte_sc_bogo_code_manual',
							'is_checked' => 'wbte_sc_bogo_code_manual' === self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_code_condition' ),
						),
					),
				),
				'class' => array( 'wbte_sc_bogo_edit_code_cond_radio' )
			)
		);
		?>

		<div class=" <?php echo 'wbte_sc_bogo_code_manual' === self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_code_condition' ) ? '' : 'wbte_sc_bogo_conditional_hidden '; ?>">
			<input type="text" id="wbte_sc_bogo_coupon_code" name="wbte_sc_bogo_coupon_code" class="wbte_sc_bogo_text_input" placeholder="<?php esc_attr_e( 'Coupon code', 'wt-smart-coupons-for-woocommerce-pro' ); ?>" value="<?php echo esc_html( $coupon->get_code() ); ?>">
			<br><span class="wbte_sc_bogo_help_text">
			<?php
			esc_html_e(
				'Enter a new coupon code',
				'wt-smart-coupons-for-woocommerce-pro'
			);
			?>
			</span>
			<span class="wbte_sc_bogo_coupon_code_error_span"></span>
		</div>
		<br>

		<div class="wbte_sc_bogo_display_div">
			<?php
				$selected              = $this->get_coupon_meta_value( $coupon_id, '_wc_make_coupon_available', true );
				$selected              = $selected ? explode( ',', $selected ) : array();
				$make_coupon_available = array(
					'my_account' => __( 'My Account', 'wt-smart-coupons-for-woocommerce-pro' ),
					'checkout'   => __( 'Checkout', 'wt-smart-coupons-for-woocommerce-pro' ),
					'cart'       => __( 'Cart', 'wt-smart-coupons-for-woocommerce-pro' ),
				);
				?>
			<p>
				<?php
				esc_html_e( 'Display offer on', 'wt-smart-coupons-for-woocommerce-pro' );
				echo wp_kses_post( wc_help_tip( __( 'The available BOGO offers will be listed on the selected pages', 'wt-smart-coupons-for-woocommerce-pro' ) ) );
				echo wp_kses_post( '<span class="wbte_sc_bogo_selected_display_span">' );
				if ( empty( $selected ) ) {
					echo wp_kses_post( '<span class="wbte_sc_bogo_edit_add_button wbte_sc_bogo_coupon_display_add_btn">' . __( '+ Add', 'wt-smart-coupons-for-woocommerce-pro' ) . '</span>' );
				} else {
					foreach ( $selected as $select ) {
						echo wp_kses_post( '<span class="wbte_sc_bogo_selected_display ' . $select . '">' . $make_coupon_available[ $select ] . '</span>' );
					}
					echo wp_kses_post( '<img src="' . esc_url( $admin_img_path ) . 'edit.svg" alt="' . __( 'Edit', 'wt-smart-coupons-for-woocommerce-pro' ) . '">' );
				}
				echo wp_kses_post( '</span>' );
				?>
			</p>
			<?php
			foreach ( $make_coupon_available as $display_slug => $display_title ) {

				echo $ds_obj->get_component(
					'checkbox normal',
					array(
						'values' => array(
							'name'       => '_wc_make_coupon_available[]',
							'id'         => esc_attr( $display_slug ),
							'value'      => esc_attr( $display_slug ),
							'is_checked' => esc_attr( in_array( $display_slug, $selected, true ) ),
							'label'      => esc_attr( $display_title ),
						),
					)
				);
			}
			?>
		</div>
		<br>
		<div class="wbte_sc_checkbox_container wbte_sc_special_checkbox_container">
			<input type="checkbox" id="wbte_sc_bogo_schedule" name="wbte_sc_bogo_schedule" <?php echo $schedule_enabled ? ' checked' : ''; ?> value="yes">
			<label for="wbte_sc_bogo_schedule"><?php esc_html_e( 'Schedule', 'wt-smart-coupons-for-woocommerce-pro' ); ?>
			<?php echo wp_kses_post( wc_help_tip( __( 'Set a start and end date for your offer. The offer will be active only within this period', 'wt-smart-coupons-for-woocommerce-pro' ) ) ); ?>
		</label>&emsp;
			
		</div>
		<div id="wbte_sc_bogo_schedule_content" <?php echo $schedule_enabled ||$expiry_in_days_enabled ? '' : ' style=" display: none;"'; ?>>
			<!-- Start on -->
			<label for="_wt_coupon_start_date">
				<p><?php esc_html_e( 'Starts on', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
			</label>
			<div class="wbte_sc_schedule_field_row">
				<input type="date" class="wbte_sc_bogo_date_picker" id="_wt_coupon_start_date" name="_wt_coupon_start_date" value="<?php echo ! empty( $start_date ) ? esc_attr( $start_date ) : ''; ?>" min="<?php echo esc_attr( $today_date ); ?>">
				<span class="wbte_sc_coupon_time_field_group" title="<?php esc_attr_e( '12 hour format', 'wt-smart-coupons-for-woocommerce-pro' ); ?>">
					<input type="text" class="wt_sc_coupon_time_field" name="_wt_sc_coupon_start_time_hour" min="1" max="12" value="<?php echo esc_attr( $start_time_hour ); ?>" placeholder="11" title="<?php esc_attr_e( 'Hour', 'wt-smart-coupons-for-woocommerce-pro' ); ?>" maxlength="2">
					<input type="text" class="wt_sc_coupon_time_field" name="_wt_sc_coupon_start_time_minute" min="0" max="59" value="<?php echo esc_attr( $start_time_minute ); ?>" placeholder="59" title="<?php esc_attr_e( 'Minute', 'wt-smart-coupons-for-woocommerce-pro' ); ?>" maxlength="2"> 
				</span>  
				<div class="wbte_sc_bogo_am_pm_div wbte_sc_bogo_edit_custom_drop_down_head">
					<div class="wbte_sc_bogo_time_am_pm wbte_sc_bogo_edit_custom_drop_down_btn">
						<p><?php echo esc_html( $start_time_meridiem ); ?></p>
						<span class="dashicons dashicons-arrow-down-alt2"></span>
					</div>
					<div class="wbte_sc_bogo_edit_custom_drop_down wbte_sc_bogo_time_dropdown" style="z-index:3;">
						<p class="wbte_sc_bogo_edit_custom_drop_down_sub_btn wbte_sc_bogo_excl_sel_icn" data-val="AM"><?php esc_html_e( 'AM', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
						<p class="wbte_sc_bogo_edit_custom_drop_down_sub_btn wbte_sc_bogo_excl_sel_icn" data-val="PM"><?php esc_html_e( 'PM', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
					</div>
					<input type="hidden" name="wbte_sc_bogo_start_meridiem" id="wbte_sc_bogo_start_meridiem" value="<?php echo $start_time_meridiem; ?>">
				</div>
			</div>
			<!-- Expiry -->
			<div class="wbte_sc_schedule_expiry_head_row">
				<label for="expiry_date">
					<p><?php esc_html_e( 'Ends on', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
				</label>
				<?php 
				echo $ds_obj->get_component(
					'checkbox normal',
					array(
						'values' => array(
							'name'       => '_wt_coupon_enable_days',
							'id'         => '_wt_coupon_enable_days',
							'value'      => 1,
							'is_checked' => $expiry_in_days_enabled,
							'label'      => __( 'Set expiry in days', 'wt-smart-coupons-for-woocommerce-pro' ),
						),
					)
				);
				?>
			</div>
			<div class="wbte_sc_schedule_expiry_div" <?php echo ! $expiry_in_days_enabled ? '' : 'style="display: none;"'; ?>>
				<div class="wbte_sc_schedule_field_row wbte_sc_schedule_expiry_field_row">
					<input type="date" class="wbte_sc_bogo_date_picker" id="expiry_date" name="expiry_date"  value="<?php echo ! empty( $end_date ) ? esc_attr( $end_date ) : ''; ?>" min="<?php echo esc_attr( $today_date ); ?>">
					<span class="wbte_sc_coupon_time_field_group" title="<?php esc_attr_e( '12 hour format', 'wt-smart-coupons-for-woocommerce-pro' ); ?>">
						<input type="text" class="wt_sc_coupon_time_field" name="_wt_sc_coupon_expiry_time_hour" min="1" max="12" value="<?php echo esc_attr( $expiry_time_hour ); ?>" placeholder="11" title="<?php esc_attr_e( 'Hour', 'wt-smart-coupons-for-woocommerce-pro' ); ?>" maxlength="2">
						<input type="text" class="wt_sc_coupon_time_field" name="_wt_sc_coupon_expiry_time_minute" min="0" max="59" value="<?php echo esc_attr( $expiry_time_minute ); ?>" placeholder="59" title="<?php esc_attr_e( 'Minute', 'wt-smart-coupons-for-woocommerce-pro' ); ?>" maxlength="2"> 
					</span>  
					<div class="wbte_sc_bogo_am_pm_div wbte_sc_bogo_edit_custom_drop_down_head">
						<div class="wbte_sc_bogo_time_am_pm wbte_sc_bogo_edit_custom_drop_down_btn" style="z-index:2;">
							<p><?php echo esc_html( $expiry_time_meridiem ); ?></p>
							<span class="dashicons dashicons-arrow-down-alt2"></span>
						</div>
						<div class="wbte_sc_bogo_edit_custom_drop_down wbte_sc_bogo_time_dropdown"  style="z-index:1;">
							<p class="wbte_sc_bogo_edit_custom_drop_down_sub_btn wbte_sc_bogo_excl_sel_icn" data-val="AM"><?php esc_html_e( 'AM', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
							<p class="wbte_sc_bogo_edit_custom_drop_down_sub_btn wbte_sc_bogo_excl_sel_icn" data-val="PM"><?php esc_html_e( 'PM', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
						</div>
						<input type="hidden" name="wbte_sc_bogo_expire_meridiem" id="wbte_sc_bogo_expire_meridiem" value="<?php echo $expiry_time_meridiem; ?>">
					</div>
				</div>
				<div class="wbte_sc_bogo_end_date_warning">
					<img src="<?php echo esc_url( $admin_img_path ); ?>exclamation-triangle.svg" alt="<?php esc_attr_e( 'Expiry date already passed', 'wt-smart-coupons-for-woocommerce-pro' ); ?>">
					<p><?php esc_html_e( 'Set a new end date as the scheduled one has already passed', 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>
				</div>
			</div>
			<div class="wbte_sc_schedule_expiry_in_days_div wbte_sc_parent_div <?php echo $expiry_in_days_enabled ? '' : 'wbte_sc_bogo_conditional_hidden'; ?>">
				<label>
					<?php
					echo sprintf( __( 'Offer ends in %s days %s (from time of setup) %s', 'wt-smart-coupons-for-woocommerce-pro' ), '<input type="text" id="_wt_coupon_expiry_in_days" name="_wt_coupon_expiry_in_days" class="wbte_sc_bogo_edit_input wbte_sc_bogo_input_only_number" value="' . esc_attr( $expiry_in_days ) . '" style="width: 60px; margin:0 5px; text-align: center;" />', '<span class="wbte_sc_bogo_help_text">', '</span>' );
					?>
				</label>
			</div>
		</div>
		
		<div class="wbte_sc_bogo_edit_save_buttons">
			<?php
			if ( isset( $_GET['newly_created'] ) ) {
				echo $ds_obj->get_component(
					'button filled medium',
					array(
						'values' => array(
							'button_title' => esc_html__( 'Save & Activate', 'wt-smart-coupons-for-woocommerce-pro' ),
						),
						'class'  => array( 'wbte_sc_bogo_save_and_activate' ),
						'attr'   => array( 'data-btn-id' => 'wbte_sc_bogo_save_and_activate' ),
					)
				);
			}
				echo $ds_obj->get_component(
					'button outlined medium',
					array(
						'values' => array(
							'button_title' => esc_html__( 'Save', 'wt-smart-coupons-for-woocommerce-pro' ),
						),
						'class'  => array( 'wbte_sc_bogo_save_and_draft' ),
						'attr'   => array( 'data-btn-id' => 'wbte_sc_bogo_save_and_draft' ),
					)
				);
				?>
		</div>
	</div>
</form>