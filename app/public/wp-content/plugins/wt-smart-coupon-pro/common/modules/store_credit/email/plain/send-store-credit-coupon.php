<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

echo $email_heading . "\n\n";

// translators: placeholder is the name of the site
$coupon_message =$credit_email_args['message'] ;
$coupons = $credit_email_args['coupon_id'];
$coupons = maybe_unserialize( $coupons  );
$coupon_code_to_display = '';
if( is_array( $coupons ) && !empty($coupons )) {
    foreach( $coupons as $coupon_id ) {
    
        $coupon_title = get_the_title( $coupon_id );
        $coupon = new WC_Coupon( $coupon_title );
        $coupon_code_to_display .=  $coupon->get_code().'  ';
    

    }
} else { 
    $coupon_title = get_the_title( $coupons );
    $coupon = new WC_Coupon( $coupon_title );
    $coupon_code_to_display .=  $coupon->get_code();
    
}

$amount = apply_filters('wt_sc_alter_giftcard_email_price', $coupon->get_amount(), $coupon);

$amount = Wt_Smart_Coupon_Admin::get_formatted_price($amount);

printf(__('You have received a new store credit for %s from %s. To redeem the store credit, use the below given code during checkout.', 'wt-smart-coupons-for-woocommerce-pro' ), $amount, get_site_url());


if($coupon_message)
{
    _e($coupon_message, 'wt-smart-coupons-for-woocommerce-pro');
} else {
    _e("You've got a gift!", 'wt-smart-coupons-for-woocommerce-pro');
}

echo  $coupon_code_to_display;

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n----------------------------------------\n\n";
}

echo apply_filters( 'woocommerce_email_footer_text', get_option('woocommerce_email_footer_text'));
