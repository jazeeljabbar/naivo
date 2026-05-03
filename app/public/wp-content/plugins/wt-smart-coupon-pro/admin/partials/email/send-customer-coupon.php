<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php _e('Hi there,', 'wt-smart-coupons-for-woocommerce-pro'); ?></p>			
<p> 
	<?php printf(__("Congratulations! You've got a coupon! To redeem your discount use coupon code %s during checkout.", 'wt-smart-coupons-for-woocommerce-pro'), '<b>'.esc_html($coupon->get_code()).'</b>');?>
</p>
<p><?php _e("You've got a coupon!",'wt-smart-coupons-for-woocommerce-pro'); ?> </p>
<p>
	<?php 
  	$coupon_data  = Wt_Smart_Coupon_Public::get_coupon_meta_data( $coupon );
	echo Wt_Smart_Coupon_Public::get_coupon_html( $coupon,$coupon_data,'email_coupon' );
	?>
</p>
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
