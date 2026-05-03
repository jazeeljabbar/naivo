<?php
/**
 * Checkout options public
 *
 * @link       
 * @since 2.0.9   
 *
 * @package  Wt_Smart_Coupon  
 */

if(!defined('ABSPATH'))
{
    exit;
}

if(!class_exists('Wt_Smart_Coupon_Checkout_Options')) /* common module class not found so return */
{
    return;
}

class Wt_Smart_Coupon_Checkout_Options_Public extends Wt_Smart_Coupon_Checkout_Options
{
    public $module_base = 'checkout_options';
    public $module_id = '';
    public static $module_id_static = '';
    private static $instance = null;
    
    public function __construct()
    {
        $this->module_id = Wt_Smart_Coupon::get_module_id($this->module_base);
        self::$module_id_static = $this->module_id;

        add_filter('woocommerce_coupon_is_valid', array($this, 'wt_woocommerce_coupon_is_valid'), 10, 2);
    }


    /**
     *  Get Instance
     * 
     *  @since 2.0.9
     */
    public static function get_instance()
    {
        if(is_null(self::$instance))
        {
            self::$instance = new Wt_Smart_Coupon_Checkout_Options_Public();
        }

        return self::$instance;
    }


    /**
     *  Validate the coupon
     * 
     *  @since  2.0.9
     *  @param  bool        $valid      Is valid or not    
     *  @param  WC_Coupon   $coupon     Coupon object 
     *  @throws Exception   When validation fails
     *  @return bool        True when coupon is valid 
     */
    public function wt_woocommerce_coupon_is_valid($valid, $coupon)
    {
        if(!$valid) //already invalid so not needed to validate here.
        {
            return $valid;
        }

        $coupon_id = $coupon->get_id();
        
        
        /**
         *  Validate shipping methods
         */
        $shipping_method_ids = self::get_processed_coupon_meta_value($coupon_id, '_wt_sc_shipping_methods');

        if(!empty($shipping_method_ids))
        {
            $chosen_shipping_methods = !is_null( WC()->session ) ? WC()->session->get( 'chosen_shipping_methods' ) : array(); // Chosen shipping method in the cart.
               
            /**
             * @since 2.0.2
             * [Bug fix] Shows a warning when `Hide shipping costs until an address is entered` option enabled.
             */
            if($chosen_shipping_methods)
            {
                $chosen_shipping = $chosen_shipping_methods[0];
                
                /**
                 *  Added compatibility for shipping method value that has no `colon`.
                 *  
                 *  @since 2.0.7
                 */
                if(false !== strpos($chosen_shipping, ":"))
                {
                    $chosen_shipping = substr($chosen_shipping, 0, strpos($chosen_shipping, ":"));
                }

                /**
                 *  To add compatibility for dynamic shipping methods. 
                 *  Shipping method ids of some plugins are formatted in different ways. So we have to process these shipping methods to do proper validation.
                 *  
                 *  @since 2.0.8
                 *  @param string   $chosen_shipping            The chosen shipping method 
                 *  @param array    $chosen_shipping_methods    The chosen shipping method array from WC 
                 */
                $chosen_shipping = apply_filters('wt_sc_chosen_shipping_for_validation', $chosen_shipping, $chosen_shipping_methods);
                
                if (!in_array($chosen_shipping, $shipping_method_ids)) {
                        
                    if($coupon->get_free_shipping()) /* checks current coupon allows free shipping */
                    {
                        if($chosen_shipping!=='free_shipping')
                        {
                            $valid = false;
                        }
                    }else
                    {
                        $valid = false;
                    }
                }

                if(!$valid)
                {
                    $msg = $this->get_customized_text('invalid_shipping_method', array('coupon_code' => $coupon->get_code()));
                    
                    if("" !== $msg)
                    {
                        throw new Exception($msg, 109);
                    }else
                    {
                        return $valid;
                    }
                } 
            }
        }


        /**
         *  Validate payment methods
         */
        $payment_method_ids = self::get_processed_coupon_meta_value($coupon_id, '_wt_sc_payment_methods');

        if(!empty($payment_method_ids))
        {
            $chosen_payment_method = isset(WC()->session->chosen_payment_method) ? WC()->session->chosen_payment_method : ''; // Chosen payment method in the cart.

            if(!in_array($chosen_payment_method, $payment_method_ids))
            {
                throw new Exception(__( 'Sorry, this coupon is not applicable to selected Payment method', 'wt-smart-coupons-for-woocommerce-pro'), 109);
                
                return false; // phpcs:ignore
            }
        }


        /**
         *  Validate user roles
         */
        $user = wp_get_current_user();
        $current_user_roles = (array) $user->roles; //take all roles of current user

        if( 0 === $user->ID ) {
            $current_user_roles[] = 'wbte_sc_guest';
        }

        /**
         *  Include user roles
         */
        $include_user_roles = self::get_processed_coupon_meta_value($coupon_id, '_wt_sc_user_roles');

        if(!empty($include_user_roles) && empty(array_intersect($include_user_roles, $current_user_roles)))
        {
            $valid = false;
            $msg = $this->get_customized_text('invalid_user_role', array('coupon_code' => $coupon->get_code()));
                         
            if("" !== $msg)
            {
                throw new Exception($msg, 109);
            }else
            {
                return $valid;
            }
        }

        
        /**
         *  Exclude user roles
         * 
         *  @since 2.0.7
         */
        $exclude_user_roles = self::get_processed_coupon_meta_value($coupon_id, '_wt_sc_exclude_user_roles');

        if(!empty($exclude_user_roles) && !empty(array_intersect($exclude_user_roles, $current_user_roles)))
        {
            $valid = false;
            $msg = $this->get_customized_text('invalid_user_role', array('coupon_code' => $coupon->get_code()));
                    
            if("" !== $msg)
            {
                throw new Exception($msg, 109);
            }else
            {
                return $valid;
            }
        }


        /**
         *  Validate billing/shipping country
         * 
         */
        $available_locations = self::get_processed_coupon_meta_value($coupon_id, '_wt_coupon_available_location');

        if ( ! empty($available_locations) && ! is_null( WC()->session ) ) {
            
            $_wt_need_check_location_in  = self::get_coupon_meta_value($coupon_id, '_wt_need_check_location_in'); //check in billing address or shipping address
            
            if('billing' === $_wt_need_check_location_in)
            {
                $choosed_country = !is_null( WC()->session ) ? WC()->session->customer['country'] : ''; //billing country
                $choosed_state = !is_null( WC()->session ) ? WC()->session->customer['state'] : ''; //billing state
            }else
            {
                $choosed_country = !is_null( WC()->session ) ? WC()->session->customer['shipping_country'] : ''; //shipping country
                $choosed_state = !is_null( WC()->session ) ? WC()->session->customer['shipping_state'] : ''; //shipping state
            }

            $choosed_state = $choosed_country . ':' . $choosed_state;

            /**
             *  Country/state include/exclude
             * 
             *  @since 2.0.9
             */
            $available_location_inc_exc = self::get_coupon_meta_value($coupon_id, '_wt_coupon_available_location_inc_exc');

            if('include' === $available_location_inc_exc)
            {
                $valid = in_array($choosed_country, $available_locations) || in_array($choosed_state, $available_locations);
            }else
            {
                $valid = !in_array($choosed_country, $available_locations) && !in_array($choosed_state, $available_locations);
            }

            if(!$valid)
            {
                $msg = $this->get_customized_text('invalid_location', array('coupon_code' => $coupon->get_code()));
                
                if("" !== $msg)
                {
                    throw new Exception($msg, 109);
                }else
                {
                    return $valid;
                }
            }
        } 

        return $valid;
    }

    /**
     *  Get customized notification messages
     *  
     *  @since  2.0.9
     *  @param  string      $key    Unique key for the message
     *  @param  array       $args   Values for the function: Coupon code, Placeholders etc
     *  @return string      Empty string when message was disabled otherwise the message
     */
    public function get_customized_text($key, $args = array())
    {
        return Wt_Smart_Coupon_Public::get_customized_text($key, $args);
    }
}

Wt_Smart_Coupon_Checkout_Options_Public::get_instance();

