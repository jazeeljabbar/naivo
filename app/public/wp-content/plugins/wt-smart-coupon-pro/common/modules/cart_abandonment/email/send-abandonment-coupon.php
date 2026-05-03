<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$coupon_data  = Wt_Smart_Coupon_Public::get_coupon_meta_data($coupon);
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email); ?>
<p> <?php  _e('Hi there,', 'wt-smart-coupons-for-woocommerce-pro'); ?> </p>	

<p>
<?php
	printf(__("Did you forget something? You've left behind some products in your cart. Grab them before they go out of stock at an additional discount of %s. Hurry up!!", 'wt-smart-coupons-for-woocommerce-pro'), $coupon_data['coupon_amount']);
?>
</p>

<p>
<?php
	printf(__('To redeem your discount use following coupon code during checkout.', 'wt-smart-coupons-for-woocommerce-pro'));
?>
</p>


<div style="height:150px;">
	<?php 
	$coupon_data  = Wt_Smart_Coupon_Public::get_coupon_meta_data($coupon);
	echo Wt_Smart_Coupon_Public::get_coupon_html($coupon, $coupon_data, 'email_coupon'); 
	?>		
</div>

<p>
	<?php
		$style='background:#0085ba; border:none; color:#fff; text-decoration:none; padding:10px; text-align:center;';
  		$style=apply_filters('wt_sc_alter_abandonment_email_button_style', $style, $coupon);
  	?>
	<a style="<?php echo esc_attr($style);?>" href="<?php echo esc_attr(wc_get_cart_url());?>"><?php  _e('Go to your cart', 'wt-smart-coupons-for-woocommerce-pro'); ?></a>
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

<?php do_action('woocommerce_email_footer', $email); ?>