<?php
/**
 * Nth order coupon public section
 *
 * @link       
 * @since 2.0.1     
 *
 * @package  Wt_Smart_Coupon
 */
if (!defined('ABSPATH')) {
    exit;
}
if(!class_exists('Wt_Smart_Coupon_Nth_Order')) /* common module class not found so return */
{
    return;
}

class Wt_Smart_Coupon_Nth_Order_Public extends Wt_Smart_Coupon_Nth_Order
{
    public $module_base='nth_order';
    public $module_id='';
    public static $module_id_static='';

    private static $instance = null;

    public function __construct()
    {
        $this->module_id=Wt_Smart_Coupon::get_module_id($this->module_base);
        self::$module_id_static=$this->module_id;

        add_action('woocommerce_coupon_is_valid', array($this, 'validate_nth_order_coupon'), 16, 2);

        add_action('woocommerce_thankyou', array($this, 'check_nth_coupon_already_awarded'), 10, 1);
    
        /**
         *  @since 2.0.0
         *  Validate nth order coupon for guest users
         */
        add_action('woocommerce_after_checkout_validation', array($this, 'validate_nth_order_coupon_for_guest'), 16, 2);

    }

    /**
     * Get Instance
     * @since 2.0.1
     */
    public static function get_instance()
    {
        if(self::$instance==null)
        {
            self::$instance=new Wt_Smart_Coupon_Nth_Order_Public();
        }
        return self::$instance;
    }

    /**
     * Helper function to get nth coupon values
     */
    public function get_nt_order_meta( $coupon_id,$meta_key )
    {
        if( '' == $meta_key || '' == $coupon_id ) return false;

        return get_post_meta($coupon_id, $meta_key, true); 
    }

    /**
     * Helper function to get all the orders of an user with specified status
     * 
     * @since 1.2.8
     * @since 2.0.8   Added HPOS Compatibility
     * @since 2.0.9   Order within date/days and specific product purchases
     */
    public function get_success_order_details($coupon_id, $get_order_args, $args = array())
    {

        /**
         *  Order within date/days
         *  
         *  @since 2.0.9
         */
        $order_date_from = $this->get_coupon_meta_value($coupon_id, '_wt_sc_nth_order_date_from');
        $order_date_to = $this->get_coupon_meta_value($coupon_id, '_wt_sc_nth_order_date_to');
        $order_date_or_days = $this->get_coupon_meta_value($coupon_id, '_nth_coupon_order_date_or_days');
        $order_within_days = absint($this->get_coupon_meta_value($coupon_id, '_wt_sc_nth_order_within_days'));

        if ( 'date' === $order_date_or_days && ( "" !== $order_date_from || "" !== $order_date_to ) )
        {
            if ( "" !== $order_date_from )
            {
                $get_order_args['date_after'] = $order_date_from;
            }

            if ( "" !== $order_date_to )
            {
                $get_order_args['date_before'] = $order_date_to;
            }

        } elseif ( 'days' === $order_date_or_days && 0 < $order_within_days )
        {
            $get_order_args['date_before'] = current_datetime()->modify( '+1 day' )->format( 'Y-m-d' ); //we have to set tommorrow as the date to include the whole current day
            $get_order_args['date_after'] = current_datetime()->modify( '-' . $order_within_days . ' day' )->format( 'Y-m-d' );
        }

        $is_filter_by_products      = (isset($args['is_filter_by_products']) && true === $args['is_filter_by_products']);
        $is_product_ids_available   = isset($args['product_ids']) && is_array($args['product_ids']) && !empty($args['product_ids']);
        $is_product_restriction     = $is_product_ids_available && $is_filter_by_products;
        $is_take_product_ids        = $is_product_ids_available && !$is_filter_by_products;

        $order_details          = array();
        $all_order_coupons      = array();
        $all_order_pro_var_ids  = array(); //product/variation ids of all orders
        $all_orders_total       = 0;
        $customer_orders        = Wt_Smart_Coupon_Common::get_orders($get_order_args);

        if( !empty($customer_orders) && is_array( $customer_orders ) )
        {
            foreach( $customer_orders as $order )
            {
                if($is_product_restriction && !$this->is_order_contain_products($order, $args['product_ids']))
                {
                    continue; //non of the given product ids are not in the order  
                }

                $order_id       = $order->get_id();
                $order_total    = $order->get_total();
                
                if(isset($args['coupon_ids'])) /* Take coupon ids, if required */
                {
                    $order_coupons  = $order->get_coupons();

                    if($order_coupons) //old used coupons
                    {
                        foreach($order_coupons as $coupon)
                        {
                            $all_order_coupons[] = $coupon->get_id();
                        }
                    }
                }

                if($is_take_product_ids) /* take product/variation ids */
                {
                    $all_order_pro_var_ids = array_merge($all_order_pro_var_ids, $this->get_order_pro_var_ids($order));
                }

                $order_details[$order_id] = array('order_total' => $order_total, 'order_obj' => $order);
                $all_orders_total += $order_total;
            }
        }

        return array (
            'total'         => $all_orders_total,
            'order_details' => $order_details,
            'coupon_ids'    => array_unique($all_order_coupons), //all order coupon ids
            'pro_var_ids'   => array_unique($all_order_pro_var_ids), //all order product/variation ids
        );        
    }

    /**
     * Helper function to check, this is an nth order coupon
     * @since 1.2.8
     */
    public function is_a_nth_order_coupon($coupon_id)
    {
        if( ! $coupon_id ) {
            return false;
        }
        $no_of_orders = $this->get_nt_order_meta( $coupon_id,'wt_nth_order_no_of_orders');
        if( $no_of_orders ) {
            return true;
        }
        return false;
    }

    /**
     *  Validate nth order coupon for guest users
     *  
     *  @since 2.0.0 
     *  @since 2.0.9    Specific product purchases
     */
    public function validate_nth_order_coupon_for_guest($data, $errors)
    {
        $user_id = get_current_user_id();

        if(!$user_id && 'yes' === get_option('woocommerce_enable_guest_checkout')) /* non logged in user and guest checkout enabled */
        {               
            $applied_coupons = WC()->cart->get_applied_coupons();
            $billing_email = $data['billing_email'];
            
            $old_orders = false;
            $old_coupon_ids = array();

            foreach($applied_coupons as $coupon_code)
            {
                $coupon = new WC_Coupon( $coupon_code );
                if(!is_object($coupon) || !is_a($coupon, 'WC_Coupon'))
                {
                    continue;
                }

                $coupon_id = $coupon->get_id();
                $no_of_order_condition= $this->get_nt_order_meta($coupon_id, 'nth_coupon_no_of_coupon_condition');
                
                /* check purchase history option is enabled */
                if(!$this->is_nth_order_validation_enabled($no_of_order_condition))
                {
                    continue; /* purchase history section is not applicable for this coupon */
                }
                
                
                if(false === $old_orders) /* prevent calling multiple times inside the loop */
                {
                    /**
                     *  Fetch all orders of the user, this is to check already awarded
                     */   
                    $order_args = array( 
                        'billing_email' => $billing_email,
                        'limit'         => -1,
                    );

                    $args = array(
                        'coupon_ids' => 1, //to take coupon ids
                    );

                    $old_orders = $this->get_success_order_details($coupon_id, $order_args, $args);
                    $old_coupon_ids = $old_orders['coupon_ids'];
                }


                /* already awarded or not */
                $exclude_already_awarded = $this->get_nt_order_meta($coupon_id, 'nth_coupon_exclude_already_awarded');
                if($exclude_already_awarded && in_array($coupon_id, $old_coupon_ids))
                {
                    $errors->add('validation', $this->get_customized_text('nth_order_exclude_awarded', array('coupon_code' => $coupon_code)));
                    continue;
                }


                /**
                 *  Fetch orders based on coupon config
                 */   
                $order_args = array( 
                    'billing_email' => $billing_email,
                    'post_status'   => $this->get_success_order_statuses($coupon_id),
                    'limit'         => -1,
                );


                /**
                 *  Specific products purchased
                 *  
                 *  @since 2.0.9
                 */
                $product_ids = array_filter($this->get_coupon_meta_value($coupon_id, '_wt_sc_nth_order_products'));
                $is_filter_by_products = (!empty($product_ids) && $this->is_filter_orders_by_products($coupon_id)); /* filter orders by products */

                $args = array(
                    'product_ids' => $product_ids,
                    'is_filter_by_products' => $is_filter_by_products, 
                );

                $order_details = $this->get_success_order_details($coupon_id, $order_args, $args);

                $validate_args = array(
                    'no_of_order_condition' => $no_of_order_condition, 
                    'no_of_orders'          => $this->get_nt_order_meta( $coupon_id,'wt_nth_order_no_of_orders'), 
                    'current_total_orders'  => count($order_details['order_details']), /* total orders with the specified statuses */
                    'min_order_total'       => $this->get_nt_order_meta($coupon_id, 'wt_nth_order_order_total'), /* minimum order total */ 
                    'old_order_total'       => $order_details['total'],
                    'coupon_code'           => $coupon->get_code(),
                    'coupon_id'             => $coupon_id,
                    'product_ids'           => $product_ids,
                    'is_filter_by_products' => $is_filter_by_products,
                    'order_pro_var_ids'     => $order_details['pro_var_ids'], //old order product/variation ids
                );
                
                $nth_coupon_validate_message = $this->get_nth_coupon_validate_message($validate_args);
                
                if('' !== $nth_coupon_validate_message)
                {
                    $errors->add('validation', $nth_coupon_validate_message);
                }
            }
        }
    }

    /**
     * Validate coupon for nth order coupons
     * @since 1.2.8 
     * @since 1.3.5     Code re-structured.
     *                  [Bug fix] Unable to apply coupon for non-logged in user.
     * @since 2.0.0     Guest user compatibility added.
     * @since 2.0.9     Specific product purchases
     */
    public function validate_nth_order_coupon( $is_valid, $coupon )
    {
        if(!$is_valid) /* already not valid then return */
        {
            return $is_valid;
        }
        
        $coupon_id = $coupon->get_id();
        $no_of_order_condition = $this->get_nt_order_meta( $coupon_id, 'nth_coupon_no_of_coupon_condition' );
        
        /* check purchase history option is enabled */
        if(!$this->is_nth_order_validation_enabled($no_of_order_condition))
        {
            return $is_valid; /* purchase history section is not applicable for this coupon */
        }

        $user_id=get_current_user_id();
        
        if(!$user_id) /* user not logged in */
        {
            if(wc_string_to_bool(get_option('woocommerce_enable_guest_checkout'))) /* guest checkout enabled */
            {
                return $is_valid; /* skip the validation now. Validation for non logged in users will done on checkout.  */
            }else
            {    

                $msg = $this->get_customized_text('nth_order_user_not_logged_in', array('coupon_code' => $coupon->get_code()));

                if("" !== $msg)
                {
                    throw new Exception($msg, 109);
                }
                
                return false;
            }
        }

        /* already awarded or not */
        $exclude_already_awarded = $this->get_nt_order_meta($coupon_id, 'nth_coupon_exclude_already_awarded');
        $awarded_to_user = get_user_meta($user_id, 'wt_awarded_nth_coupon_'.$coupon_id, true);
        
        if($exclude_already_awarded && $awarded_to_user)
        {
            $msg = $this->get_customized_text('nth_order_exclude_awarded', array('coupon_code' => $coupon->get_code()));

            if("" !== $msg)
            {
                throw new Exception($msg, 109);
            }
            
            return false; 
        }

        /**
         *  Fetch orders
         */   
        $order_args = array( 
            'customer'      => $user_id,
            'post_status'   => $this->get_success_order_statuses($coupon_id),
            'limit'         => -1,
        );


        /**
         *  Specific products purchased
         *  
         *  @since 2.0.9
         */
        $product_ids = array_filter($this->get_coupon_meta_value($coupon_id, '_wt_sc_nth_order_products'));
        $is_filter_by_products = (!empty($product_ids) && $this->is_filter_orders_by_products($coupon_id)); /* filter orders by products */

        $args = array(
            'product_ids' => $product_ids,
            'is_filter_by_products' => $is_filter_by_products,
        );

        $order_details = $this->get_success_order_details($coupon_id, $order_args, $args);


        $validate_args = array(
            'no_of_order_condition' => $no_of_order_condition, 
            'no_of_orders'          => $this->get_coupon_meta_value($coupon_id, 'wt_nth_order_no_of_orders'), 
            'current_total_orders'  => count($order_details['order_details']), /* total orders with the specified statuses */
            'min_order_total'       => $this->get_nt_order_meta($coupon_id, 'wt_nth_order_order_total'), /* minimum order total */ 
            'old_order_total'       => $order_details['total'],
            'coupon_code'           => $coupon->get_code(),
            'coupon_id'             => $coupon_id,
            'product_ids'           => $product_ids,
            'is_filter_by_products' => $is_filter_by_products,
            'order_pro_var_ids'     => $order_details['pro_var_ids'], //old order product/variation ids
        );
        
        $nth_coupon_validate_message = $this->get_nth_coupon_validate_message($validate_args);        
        
        if('' !== $nth_coupon_validate_message)
        {
            throw new Exception($nth_coupon_validate_message, 109);
            return false;
        }

        return $is_valid;
    }

    /**
     *  Nth order coupon success order statuses
     *  @since 2.0.0 
     */
    public function get_nth_coupon_success_order_statuses()
    {
        return apply_filters('wt_sc_alter_nth_coupon_success_order_statuses', array('processing', 'on-hold', 'completed'));
    }

    /**
     *  Nth order coupon validation and message
     *  @since 2.0.0
     *  @since 2.0.8    Implemented customized messages option
     *  @since 2.0.9    Order within date/days and specific product purchases
     *  @since 2.1.0    Order `less than or equal`
     */
    private function get_nth_coupon_validate_message($args)
    {
        /**
         *  Order within date/days
         *  
         *  @since 2.0.9
         */
        $coupon_id = $args['coupon_id'];
        $order_date_from = $this->get_coupon_meta_value($coupon_id, '_wt_sc_nth_order_date_from');
        $order_date_to = $this->get_coupon_meta_value($coupon_id, '_wt_sc_nth_order_date_to');
        $order_date_or_days = $this->get_coupon_meta_value($coupon_id, '_nth_coupon_order_date_or_days');
        $order_within_days = absint($this->get_coupon_meta_value($coupon_id, '_wt_sc_nth_order_within_days'));

        $is_order_within_date_enabled = ( 'date' === $order_date_or_days && ( "" !== $order_date_from || "" !== $order_date_to ) );
        $is_order_within_days_enabled = ( 'days' === $order_date_or_days && 0 <= $order_within_days );

        $msg = '';

        if('equals' === $args['no_of_order_condition'] && absint($args['no_of_orders']) !== absint($args['current_total_orders']))
        {
            $msg = $this->get_customized_text('nth_order_equal', array('coupon_code' => $args['coupon_code'], 'required_order_text' => $this->number_format($args['no_of_orders']+1), 'current_order_count' => $args['current_total_orders']));
        }

        if('greater_or_equal' === $args['no_of_order_condition'] && $args['no_of_orders'] > $args['current_total_orders'])
        {
            $msg = $this->get_customized_text('nth_order_greater_equal', array('coupon_code' => $args['coupon_code'], 'required_order_count' => $args['no_of_orders'], 'current_order_count' => $args['current_total_orders']));
        }

        
        /**
         *  Order less than or equal
         *  
         *  @since 2.1.0
         */
        if('less_than_or_equal' === $args['no_of_order_condition'] && $args['no_of_orders'] < $args['current_total_orders'])
        {
            $msg = $this->get_customized_text('nth_order_less_than_equal', array('coupon_code' => $args['coupon_code'], 'required_order_count' => $args['no_of_orders'], 'current_order_count' => $args['current_total_orders']));
        }


        if($args['min_order_total'] && $args['old_order_total'] < $args['min_order_total'])
        {
            $msg = $this->get_customized_text('nth_order_total', array('coupon_code' => $args['coupon_code'], 'required_order_total' => $args['min_order_total'], 'current_order_total' => $args['old_order_total']));
        }

        if(!$args['is_filter_by_products'] && !empty($args['product_ids']) && !empty($args['order_pro_var_ids'])) //filter by products not enabled and product ids exists
        {

            /**
             *  Toggle `or`/`and` condition when checking products purchased previously
             *  If true means customer purchased any(or) of the products previously
             *  
             *  @since  2.0.9
             *  @param  bool    `or`/`and` condition. Default: true (or)
             *  @param  int     Id of the coupon
             *  @return bool    True means customer purchased any(or) of the products previously 
             */  
            $is_valid = apply_filters('wt_sc_purchased_any_of_the_products', true, $coupon_id) ? !empty(array_intersect($args['product_ids'], $args['order_pro_var_ids'])) : empty(array_diff($args['product_ids'], $args['order_pro_var_ids']));
            $msg = !$is_valid ? '-' : $msg; //adding a dummy value to $msg, real message will be assigned in the below condition.
        }

        if( "" !== $msg && ( $is_order_within_date_enabled || $is_order_within_days_enabled || !empty( $args['product_ids'] ) ) ) /** Not valid and `within date` or `product restriction` is enabled so we have to show a generic message */
        {
            $msg = $this->get_customized_text( 'nth_order_within_date', array( 'coupon_code' => $args['coupon_code'] ) );
        }

        return $msg;
    }

    /**
    * helper function for creating number for mar list 1st 2nd 3rd nth etc.
    * @since 1.2.8
    * @since 1.3.5    returns `first` for zero
    * @param $num - Given number
    */
    public function number_format($num)
    {      
        if($num==0)
        {
            return __('first', 'wt-smart-coupons-for-woocommerce-pro');
        }
        if ( ($num / 10) % 10 != 1 )
        {
            switch( $num % 10 )
            {
                case 1: return $num . __('st','wt-smart-coupons-for-woocommerce-pro');
                case 2: return $num . __('nd','wt-smart-coupons-for-woocommerce-pro');
                case 3: return $num . __('rd','wt-smart-coupons-for-woocommerce-pro'); 
            }
        }
        return $num . __('th','wt-smart-coupons-for-woocommerce-pro');
    }

    /**
    * Add user meta details 
    * @since 1.2.8
    */
    function check_nth_coupon_already_awarded($order)
    {
        $order_obj = wc_get_order($order);
        $user_id = get_current_user_id();
        if(Wt_Smart_Coupon::wt_sc_is_woocommerce_prior_to('3.7'))
        {
            $coupons  = $order_obj->get_used_coupons();
        }else
        {
            $coupons  = $order_obj->get_coupon_codes();
        }
        if(is_array($coupons))
        {
             foreach($coupons as $coupon )
             {
                 $coupon_obj = new WC_coupon( $coupon );
                 if( $this->is_a_nth_order_coupon( $coupon_obj->get_id() ) ) {
                     update_user_meta($order_obj->get_customer_id(), 'wt_awarded_nth_coupon_'.$coupon_obj->get_id(),1 );
                 }
             }
        }
    }


    /**
     *  Get customized notification messages
     *  
     *  @since  2.0.8
     *  @param  string      $key    Unique key for the message
     *  @param  array       $args   Values for the function: Coupon code, Placeholders etc
     *  @return string      Empty string when message was disabled otherwise the message
     */
    public function get_customized_text($key, $args = array())
    {
        return Wt_Smart_Coupon_Public::get_customized_text($key, $args);
    }

    /**
     *  Is nth order validation enabled for this coupon
     *  
     *  @since  2.0.9
     *  @param  string      $no_of_order_condition      Order condtion
     *  @return bool        True when nth order validation enabled
     */
    private function is_nth_order_validation_enabled($no_of_order_condition)
    {
        return (empty($no_of_order_condition) || '- Select -' === $no_of_order_condition || 'please_select' === $no_of_order_condition) ? false : true;
    }


    /**
     *  Order statuses for nth order validation
     *  
     *  @since  2.0.9
     *  @param  int         $coupon_id  Id of coupon
     *  @return string[]                Array of statuses, If not set return empty array
     */
    private function get_success_order_statuses($coupon_id)
    {
        $order_statuses=$this->get_nt_order_meta($coupon_id, 'wt_order_Status_need_to_count'); /* allowed order statuses */
        $order_statuses=(is_string($order_statuses) ? explode(",", $order_statuses) : $order_statuses);
        
        return (empty($order_statuses) ? $this->get_nth_coupon_success_order_statuses() : $order_statuses);
    }

    /**
     *  Get order product/variation ids
     * 
     *  @since 2.0.9
     *  @param  WC_Order    $order              Order object 
     *  @return int[]       $order_pro_var_ids  Array of product/variation ids
     */
    private function get_order_pro_var_ids($order)
    {
        $order_pro_var_ids = array(); //product/variation ids

        foreach($order->get_items() as $item_id => $item)
        {
            $order_pro_var_ids[] = $item->get_product_id();
            $order_pro_var_ids[] = $item->get_variation_id();
        }

        return $order_pro_var_ids;
    }

    /**
     *  Is an order contains specific products
     * 
     *  @since  2.0.9
     *  @param  WC_Order    $order  Order object
     *  @param  int[]       $product_ids  Array of product/variation ids
     *  @return bool        True when order contains any of the product/variation
     */
    private function is_order_contain_products($order, $product_ids)
    {       
        $order_pro_var_ids = $this->get_order_pro_var_ids($order);

        return (!empty(array_intersect($order_pro_var_ids, $product_ids)));
    }


    /**
     *  Filter user orders by products. Only take orders contains any of the products
     *  
     *  @since  2.0.9
     *  @param  int     Id of the coupon
     *  @return bool    True when filter by product is enabled. Default: false
     */
    private function is_filter_orders_by_products($coupon_id)
    {
        /**
         *  Filter user orders by products. Only take orders contains any of the products
         *  
         *  @since  2.0.9
         *  @param  bool    Is filter orders. Default: false
         *  @param  int     Id of the coupon
         *  @return bool    True when filter by product is enabled. Default: false
         */
        return (bool) apply_filters('wt_sc_purchase_history_filter_orders_by_products', false, $coupon_id);
    }
}
Wt_Smart_Coupon_Nth_Order_Public::get_instance();
