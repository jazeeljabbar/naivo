<?php
/**
 * BOGO edit page content
 *
 * @since   3.0.0
 * @package    Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$bogo_discount_type     = array(
	'wbte_sc_bogo_bxgx'            => __( 'Buy product X, get product X/Y', 'wt-smart-coupons-for-woocommerce-pro' ),
	'wbte_sc_bogo_cheap_expensive' => __( 'Cheapest/ Most expensive item in cart', 'wt-smart-coupons-for-woocommerce-pro' ),
);
$selected_discount_type = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_type' );

$trash_icon = '<span style="height: 24px;"  class="wbte_sc_bogo_edit_trash">' . wp_kses_post( $ds_obj->render_html( array( 'html' => '{{wbte-ds-icon-trash}}' ) ) ) . '</span>';

include_once plugin_dir_path( __FILE__ ) . '---wbte-header.php';
?>

<form id="wbte_sc_bogo_coupon_save" method="POST">
	<input type="hidden" id="wt_sc_bogo_coupon_id" name="wt_sc_bogo_coupon_id" value="<?php echo esc_attr( $coupon_id ); ?>">
	<input type="hidden" name="wbte_sc_bogo_type" value="<?php echo esc_attr( $selected_discount_type ); ?>">
	<div class="wbte_sc_bogo_edit_main">
		<div class="wbte_sc_bogo_edit_content">
			<div class="wbte_sc_bogo_edit_head">
				<img class="wbte_sc_bogo_goback_btn" src="
				<?php
				echo esc_url(
					$ds_obj->get_asset(
						array(
							'name' => 'left-arrow-1',
							'type' => 'icon',
						)
					)
				);
				?>
				" onclick="window.location.href = '<?php echo esc_url( admin_url( 'admin.php?page=' . self::$bogo_page_name ) ); ?>'">
				<h3><?php echo esc_html( $bogo_discount_type[ $selected_discount_type ] ); ?></h3>
			</div>
			<?php

				$customer_gets           = array(
					'specific_product'          => __( 'Specific product(s)', 'wt-smart-coupons-for-woocommerce-pro' ),
					'same_product_in_the_cart'  => __( 'Same product', 'wt-smart-coupons-for-woocommerce-pro' ),
					'any_product_from_category' => __( 'Product from specific category', 'wt-smart-coupons-for-woocommerce-pro' ),
					'any_product_from_store'    => __( 'Any product in store', 'wt-smart-coupons-for-woocommerce-pro' ),
				);
				$selected_triggers_when  = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_triggers_when' );
				$customer_gets_selected  = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets' );
				$customer_gets_cheap_exp = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets_cheap_exp' );

				require_once plugin_dir_path( __FILE__ ) . '---step1.php';
				require_once plugin_dir_path( __FILE__ ) . '---step2.php';
				require_once plugin_dir_path( __FILE__ ) . '---step3.php';
				?>
		</div>
		<?php require_once plugin_dir_path( __FILE__ ) . '---edit-general.php'; ?>
	</div>