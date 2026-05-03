<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<?php  
	_e('Hi there,', 'wt-smart-coupons-for-woocommerce-pro');

	// translators: placeholder is the URL of the site
	sprintf(__('Thanks for signing up with us.!  We would like to welcome you to our %s with a gift.', 'wt-smart-coupons-for-woocommerce-pro' ), get_bloginfo('url'));
	echo "&nbsp;\n";

	_e( 'Use the following coupon code during your next purchase to avail the discount.', 'wt-smart-coupons-for-woocommerce-pro');
	echo "&nbsp;\n";

	echo $coupon->get_code();
	echo "&nbsp;\n";

	/**
	 * Show user-defined additional content - this is set in each email's settings.
	 */
	if ( $additional_content ) {
		echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
		echo "\n\n----------------------------------------\n\n";
	}

	echo wp_kses_post(apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text'))); 
?>