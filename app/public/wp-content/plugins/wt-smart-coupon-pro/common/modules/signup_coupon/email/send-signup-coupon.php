<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<?php do_action('woocommerce_email_header', $email_heading, $email); ?>
<p><?php _e('Hi there,', 'wt-smart-coupons-for-woocommerce-pro');?></p>	
<p>
	<?php
	// translators: placeholder is the URL of the site
	sprintf(__('Thanks for signing up with us.!  We would like to welcome you to our %s with a gift.', 'wt-smart-coupons-for-woocommerce-pro' ), get_bloginfo('url'));

	$coupon_data  = Wt_Smart_Coupon_Public::get_coupon_meta_data($coupon);
	?>
</p>

<p><?php _e('Use the following coupon code during your next purchase to avail the discount.', 'wt-smart-coupons-for-woocommerce-pro'); ?></p> 
<p><?php echo Wt_Smart_Coupon_Public::get_coupon_html($coupon, $coupon_data, 'email_coupon'); ?></p>

<p>
	<?php 
	    /**
	     * Show user-defined additional content - this is set in each email's settings.
	     */
	    if ( $additional_content ) {
	        echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	    }
	?>
</p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>