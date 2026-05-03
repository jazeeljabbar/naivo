<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<?php do_action('woocommerce_email_header', $email_heading, $email); ?>
<p> <?php  _e('Hi there,'  ,'wt-smart-coupons-for-woocommerce-pro'); ?> </p>	

	<?php
    // translators: placeholder is the name of the site
    $credit_amount = 0;
    $coupon_html = array();
    $coupon_message =$credit_email_args['message'] ;
    $coupons = $credit_email_args['coupon_id'];
    $coupons = maybe_unserialize( $coupons  );


    if( is_array( $coupons ) && !empty($coupons )) {
       
        foreach( $coupons as $coupon_id ) {
		
            $coupon_title = get_the_title( $coupon_id );
            $coupon = new WC_Coupon( $coupon_title );
            $credit_amount+= apply_filters('wt_sc_alter_giftcard_email_price', $coupon->get_amount(), $coupon);
            $coupon_data  = Wt_Smart_Coupon_Public::get_coupon_meta_data( $coupon );
            $coupon_html[] =  Wt_Smart_Coupon_Public::get_coupon_html( $coupon,$coupon_data,'email_coupon' );

        }
    } else { 
        $coupon_title = get_the_title( $coupons );
        

        $amount = Wt_Smart_Coupon_Admin::get_formatted_price( 10 );

        if( ! $coupon_title && $coupons ==0 ) { // for email preview
            $coupon = -1;
            $credit_amount = 10;
            $coupon_data = array(
                'coupon_type'           => __('Store Credit','wt-smart-coupons-for-woocommerce-pro'),
                'coupon_amount'         => $amount,
                'coupon_expires'        => '',
                'email_restriction'     => ''
            );
        } else {
            $coupon = new WC_Coupon( $coupon_title );
            $credit_amount+= apply_filters('wt_sc_alter_giftcard_email_price', $coupon->get_amount(), $coupon);
            $coupon_data  = Wt_Smart_Coupon_Public::get_coupon_meta_data( $coupon );

        }
        
        $coupon_html[] =  Wt_Smart_Coupon_Public::get_coupon_html($coupon, $coupon_data, 'email_coupon' ); 
    }

   
    $amount = Wt_Smart_Coupon_Admin::get_formatted_price( $credit_amount );
    
    echo '<p>'; 
        printf( __('You have received a new store credit for %s from %s. To redeem the store credit, use the below given code during checkout.', 'wt-smart-coupons-for-woocommerce-pro'), '<span class="credit_amount">'.$amount.'</span>', get_site_url());
    echo '</p>';

    
    

    if( $coupon_message ) { 
        ?>
        <p class="wt_credit_message">  <?php  echo  $coupon_message; ?> </p>
        <?php
    } else { ?>
        <p class="wt_credit_message"><?php _e("You've got a gift!", 'wt-smart-coupons-for-woocommerce-pro'); ?> </p>
    <?php
    } ?>

    <p>
    <?php
    foreach( $coupon_html as $coupon_item ) {
        echo $coupon_item;
    }
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