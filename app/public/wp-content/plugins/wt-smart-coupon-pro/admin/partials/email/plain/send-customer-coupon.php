<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<?php 
	echo esc_html(wp_strip_all_tags($email_heading));

	echo "&nbsp\n";
	_e('Hi there,', 'wt-smart-coupons-for-woocommerce-pro');	

	echo "&nbsp\n";
	printf(__("Congratulations! You've got a coupon! To redeem your discount use coupon code %s during checkout.", 'wt-smart-coupons-for-woocommerce-pro'), esc_html($coupon->get_code()));

	echo "&nbsp\n";
	_e("You've got a coupon!",'wt-smart-coupons-for-woocommerce-pro'); 

	$coupon=new WC_Coupon($coupon);
	echo $coupon->get_code();
	echo "&nbsp\n";
	
	/**
	 * Show user-defined additional content - this is set in each email's settings.
	 */
	if ( $additional_content ) {
		echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
		echo "\n\n----------------------------------------\n\n";
	}
	
	echo wp_kses_post(apply_filters( 'woocommerce_email_footer_text', get_option('woocommerce_email_footer_text')));

?>