<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * @since 2.0.8 Added HPOS Compatibility
 */
?>
<?php echo esc_html(wp_strip_all_tags($email_heading)); ?>
<?php _e('Hi there,', 'wt-smart-coupons-for-woocommerce-pro'); ?>	

<?php
_e("Congratulations! You've got a coupon! To redeem your discount, use following coupon code during checkout.", 'wt-smart-coupons-for-woocommerce-pro');

$coupon_message = Wt_Smart_Coupon_Common::get_order_meta($order->get_id(), 'wt_coupon_send_to_message');

if($coupon_message) 
{
    echo wp_strip_all_tags($coupon_message);
}else
{ 
    _e("You've got a gift!", 'wt-smart-coupons-for-woocommerce-pro');
}

$coupons = Wt_Smart_Coupon_Common::get_order_meta($order->get_id(), 'wt_coupons');
$coupons = maybe_unserialize($coupons);

if(!empty($coupons))
{
    foreach( $coupons as $coupon_id )
    {		      
        $coupon = new WC_Coupon( $coupon_id );
        echo $coupon->get_code();
        echo "&nbsp;\n";
    }
}
?>
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