<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$coupon_data  = Wt_Smart_Coupon_Public::get_coupon_meta_data($coupon);
?>

<?php 
	_e('Hi there,', 'wt-smart-coupons-for-woocommerce-pro');
	echo "&nbsp;\n"; 
?>	

<?php
	printf(__("Did you forget something? You've left behind some products in your cart. Grab them before they go out of stock at an additional discount of %s. Hurry up!!", 'wt-smart-coupons-for-woocommerce-pro'), $coupon_data['coupon_amount']);
	echo "&nbsp;\n"; 
?>

<?php
	_e('To redeem your discount use following coupon code during checkout.', 'wt-smart-coupons-for-woocommerce-pro');
	echo "&nbsp;\n";
?>

<?php
echo $coupon->get_code();
echo "&nbsp;\n";
?>

<?php _e('Go to your cart', 'wt-smart-coupons-for-woocommerce-pro'); ?> [<?php echo wc_get_cart_url();?>]

<?php
	/**
	 * Show user-defined additional content - this is set in each email's settings.
	 */
	if ( $additional_content ) {
		echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
		echo "\n\n----------------------------------------\n\n";
	}
?>
<?php echo wp_kses_post(apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text'))); ?>