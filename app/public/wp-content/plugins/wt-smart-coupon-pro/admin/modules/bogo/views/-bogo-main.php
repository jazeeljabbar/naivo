<?php
/**
 * Content of new BOGO page
 *
 * @package    Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$ds_obj = Wbte\Sc\Ds\Wbte_Ds::get_instance( WEBTOFFEE_SMARTCOUPON_VERSION );

if ( ! self::is_new_bogo_activated() ) {
	include_once plugin_dir_path( __FILE__ ) . '--new-bogo-switching.php';
	return;
}

if ( isset( $_GET['wbte_bogo_id'] ) ) {
	$coupon    = new WC_Coupon( absint( wp_unslash( $_GET['wbte_bogo_id'] ) ) );
	$coupon_id = $coupon->get_id();
	include_once plugin_dir_path( __FILE__ ) . '--bogo-edit-page.php';
	return;
}

// Include common BOGO header.
echo $ds_obj->get_component(
	'header',
	array(
		'values' => array(
			'plugin_name'      => 'Smart coupon',
			'developed_by_txt' => esc_html__( 'Developed by', 'wt-smart-coupons-for-woocommerce-pro' ),
			'plugin_logo' => esc_url( $admin_img_path . 'voucher_tag.svg' ),
		),
	)
);

$discount_tag_img = '<img src="' . esc_url( $admin_img_path ) . 'bogo_discount_tag.svg" alt="">';
require_once plugin_dir_path( __FILE__ ) . '--bogo-main-general.php';
$_all_bogo_coupon_count = self::get_total_bogo_counts();
?>
<div class="wbte_sc_bogo_body">
	<div class="wbte_sc_bogo_outer_box <?php echo ( 0 >= $_all_bogo_coupon_count ) ? '' : 'wbte_sc_bogo_outer_box_listing'; ?>">
		<div class="wbte_sc_bogo_general_settings_button">
			<img src="<?php echo esc_url( $admin_img_path ); ?>settings_gear.svg" alt="<?php esc_attr_e( 'Settings', 'wt-smart-coupons-for-woocommerce-pro' ); ?>" title="<?php esc_attr_e( 'General settings', 'wt-smart-coupons-for-woocommerce-pro' ); ?>">
		</div>
		<?php
		if ( 0 >= $_all_bogo_coupon_count ) {
			include_once plugin_dir_path( __FILE__ ) . '--first-bogo-campaign.php';
		} else {
			include_once plugin_dir_path( __FILE__ ) . '--bogo-listing.php';
		}
		?>
	</div>
	<?php 
	if ( class_exists( 'Wt_Smart_Coupon_Request_feature' ) && method_exists( 'Wt_Smart_Coupon_Request_feature', 'add_feature_request_form' ) ) {
		Wt_Smart_Coupon_Request_feature::get_instance()->add_feature_request_form();
	}

	// Before changing the help widget items( or order of items ) update js file also. In js file a class and data attribute is added to the second item.
	echo $ds_obj->get_component(
		'help-widget',
		array(
			'values' => array(
				'items' => array(
				array( 'title' => __( 'Setup Guide', 'wt-smart-coupons-for-woocommerce-pro' ), 'icon' => 'book', 'href' => esc_url( 'https://www.webtoffee.com/woocommerce-bogo-discounts/' ), 'target' => '_blank' ),
				array( 'title' => __( 'Request a feature', 'wt-smart-coupons-for-woocommerce-pro' ), 'icon' => 'light-bulb-1' ),
				array( 'title' => __( 'Contact support', 'wt-smart-coupons-for-woocommerce-pro' ), 'icon' => 'headphone', 'target' => '_blank', 'href' => esc_url( 'https://www.webtoffee.com/support/' ) ),
				),
				'hover_text' => esc_html__( 'Help', 'wt-smart-coupons-for-woocommerce-pro' ),
			)
		)
	);
	?>
</div>




