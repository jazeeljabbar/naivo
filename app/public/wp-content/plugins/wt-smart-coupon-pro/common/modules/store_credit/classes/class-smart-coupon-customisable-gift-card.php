<?php
if (!defined('WPINC')) {
    die;
}
if( ! class_exists ( 'Wt_Smart_Coupon_Customisable_Gift_Card' ) ) {
    class Wt_Smart_Coupon_Customisable_Gift_Card 
    {

        /**
         * Get the tmeplate Image
         * @since 1.2.8
         */
        public static function get_template_image($template)
        {
            if(class_exists('Wt_Smart_Coupon_Store_Credit') && method_exists('Wt_Smart_Coupon_Store_Credit', 'get_gift_card_template'))
            {
                return Wt_Smart_Coupon_Store_Credit::get_gift_card_template($template);
            }  
            return array();
        }
              
    }    
}