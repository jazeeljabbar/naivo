<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
* @since 2.0.8 Added HPOS Compatibility
*/
?>

<?php do_action('woocommerce_email_header', $email_heading, $email); ?>
<p> <?php  _e('Hi there,', 'wt-smart-coupons-for-woocommerce-pro'); ?> </p>	
<p>
	<?php
	_e("Congratulations! You've got a coupon! To redeem your discount, use following coupon code during checkout.", 'wt-smart-coupons-for-woocommerce-pro');  
    $coupon_message = Wt_Smart_Coupon_Common::get_order_meta($order->get_id(), 'wt_coupon_send_to_message');
    
    if($coupon_message)
    {
        ?>
        </p><?php echo $coupon_message; ?></p>
        <?php
    }else 
    {   
        ?>
        <p><?php _e("You've got a gift!", 'wt-smart-coupons-for-woocommerce-pro'); ?></p>
        <?php
    }
    
    $coupons = Wt_Smart_Coupon_Common::get_order_meta($order->get_id(), 'wt_coupons');
    $coupons = maybe_unserialize($coupons);
    
    if(!empty($coupons))
    {
        foreach($coupons as $coupon_id)
        {		
            $coupon = new WC_Coupon( $coupon_id );
            $coupon_data  = Wt_Smart_Coupon_Public::get_coupon_meta_data($coupon);                  
            ?>
            <p><?php echo Wt_Smart_Coupon_Public::get_coupon_html($coupon, $coupon_data, 'email_coupon'); ?></p>
            <?php
        }
    }
	?>

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