<?php
/**
 * Nth order coupon admin/public section
 *
 * @link       
 * @since 2.0.1     
 *
 * @package  Wt_Smart_Coupon
 */
if (!defined('ABSPATH'))
{
    exit;
}
if(!class_exists('Wt_Smart_Coupon_Nth_Order'))
{
	class Wt_Smart_Coupon_Nth_Order
    {
        public $module_base='nth_order';
        public $module_id='';
        public static $module_id_static='';
        private static $instance = null;
        public static $meta_arr = array(); /** @since 2.0.9 */

        public function __construct()
        {
            $this->module_id=Wt_Smart_Coupon::get_module_id($this->module_base);
            self::$module_id_static=$this->module_id;

            /**
             *  Register the messages that are customizable via admin panel
             *  @since 2.0.8
             */
            add_filter('wt_sc_intl_add_notifications', array($this, 'register_customized_texts'));


            self::$meta_arr = array(
                
                'wt_nth_order_no_of_orders' =>  array(
                    'default'   => 0, /* default value */
                    'type'      => 'int', /* value type */
                ),
                'nth_coupon_no_of_coupon_condition' =>  array(
                    'default'   => '', 
                    'type'      => 'text',
                ),
                'wt_order_Status_need_to_count' =>  array(
                    'default'   => array(), 
                    'type'      => 'text_arr',
                ),
                'wt_nth_order_order_total' =>  array(
                    'default'   => 0, 
                    'type'      => 'float',
                ),
                'nth_coupon_exclude_already_awarded' =>  array(
                    'default'   => false, 
                    'type'      => 'boolean',
                ),

                /** 
                 *  Order within a specific date range or within specific days
                 * 
                 *  @since 2.0.9 
                 */
                '_wt_sc_nth_order_date_from' =>  array(
                    'default'   => '', 
                    'type'      => 'text',
                ),
                '_wt_sc_nth_order_date_to' =>  array(
                    'default'   => '', 
                    'type'      => 'text',
                ),
                '_nth_coupon_order_date_or_days' =>  array(
                    'default'   => 'date', //other possible value: days
                    'type'      => 'text',
                ),
                '_wt_sc_nth_order_within_days' =>  array(
                    'default'   => '', 
                    'type'      => 'absint',
                ),

                /** 
                 *  Specific products purchased
                 * 
                 *  @since 2.0.9 
                 */
                '_wt_sc_nth_order_products' =>  array(
                    'default'   => array(), 
                    'type'      => 'int_arr',
                ),
            );
        }

        /**
         * Get Instance
         */
        public static function get_instance()
        {
            if(self::$instance==null)
            {
                self::$instance=new Wt_Smart_Coupon_Nth_Order();
            }
            return self::$instance;
        }

        /**
         *  Register the messages that are customizable via admin panel
         *  
         *  @since  2.0.8
         *  @param  array    $notifications  Array of message info
         *  @return array    Array of message info
         */
        public function register_customized_texts($notifications)
        {
            $status_locked_msg = __('Hiding this message may lead to validation failure in checkout', 'wt-smart-coupons-for-woocommerce-pro');

            $notifications['nth_order_equal'] = array(
                'message'           => sprintf(__('The coupon is applicable only on %s order.', 'wt-smart-coupons-for-woocommerce-pro'), '{required_order_text}'),
                'description'       => __('Displays when this coupon can be applied only to a specific order.', 'wt-smart-coupons-for-woocommerce-pro'),
                'status'            => 1, 
                'status_locked'     => $status_locked_msg, 
                'supported_placeholders' => array(
                    'coupon_code'           => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
                    'required_order_text'   => __('Nth order, which the coupon is eligible for.', 'wt-smart-coupons-for-woocommerce-pro'),
                    'current_order_count'   => __('Current order count', 'wt-smart-coupons-for-woocommerce-pro'),
                ),
                'available_filters' => array(
                    
                ),
                'module'    => 'nth_order',
                'group'     => 'warning',
                'initiater' => 'sc', //smart coupon
            );

            $notifications['nth_order_greater_equal'] = array(
                'message'           => sprintf(__('Coupon is applicable only after %s order(s).','wt-smart-coupons-for-woocommerce-pro'), '{required_order_count}'),
                'description'       => __('Displays when the coupon can only be used after reaching a certain order count.', 'wt-smart-coupons-for-woocommerce-pro'),
                'status'            => 1, 
                'status_locked'     => $status_locked_msg,
                'supported_placeholders' => array(
                    'coupon_code'           => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
                    'required_order_count'  => __('Required order count.', 'wt-smart-coupons-for-woocommerce-pro'),
                    'current_order_count'   => __('Current order count.', 'wt-smart-coupons-for-woocommerce-pro'),
                ),
                'available_filters' => array(
                    
                ),
                'module'    => 'nth_order',
                'group'     => 'warning',
                'initiater' => 'sc', //smart coupon
            );

            $notifications['nth_order_total'] = array(
                'message'           => __("Your previous order total does not meet the coupon's eligibility criteria.", 'wt-smart-coupons-for-woocommerce-pro'),
                'description'       => __('Displays When coupon can be used only if the previous order count meets the minimum requirement.', 'wt-smart-coupons-for-woocommerce-pro'),
                'status'            => 1, 
                'status_locked'     => $status_locked_msg,
                'supported_placeholders' => array(
                    'coupon_code'           => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
                    'required_order_total'  => __('Required Total order value', 'wt-smart-coupons-for-woocommerce-pro'),
                    'current_order_total'   => __('Current Total order value', 'wt-smart-coupons-for-woocommerce-pro'),
                ),
                'available_filters' => array(
                    
                ),
                'module'    => 'nth_order',
                'group'     => 'warning',
                'initiater' => 'sc', //smart coupon
            );

            $notifications['nth_order_exclude_awarded'] = array(
                'message'           => __('Coupon already redeemed.', 'wt-smart-coupons-for-woocommerce-pro'),
                'description'       => __('Displays when the coupon has already been awarded to the user and can only be used once.', 'wt-smart-coupons-for-woocommerce-pro'),
                'status'            => 1, 
                'status_locked'     => $status_locked_msg,
                'supported_placeholders' => array(
                    'coupon_code'           => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
                ),
                'available_filters' => array(

                ),
                'module'    => 'nth_order',
                'group'     => 'warning',
                'initiater' => 'sc', //smart coupon
            );


            $notifications['nth_order_user_not_logged_in'] = array(
                'message'           => __('Coupon applicable only for logged in users.', 'wt-smart-coupons-for-woocommerce-pro'),
                'description'       => __('Displays when a guest user attempts to use the nth order coupon while the guest checkout option is turned off.', 'wt-smart-coupons-for-woocommerce-pro'),
                'status'            => 1, 
                'status_locked'     => $status_locked_msg,
                'supported_placeholders' => array(
                    'coupon_code'           => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
                ),
                'available_filters' => array(

                ),
                'module'    => 'nth_order',
                'group'     => 'warning',
                'initiater' => 'sc', //smart coupon
            );

            /**
             *  Orders within specific time.
             *  Not purchased some specific products
             *  
             *  @since 2.0.9
             */
            $notifications['nth_order_within_date'] = array(
                'message'           => __('You are not eligible for this coupon.', 'wt-smart-coupons-for-woocommerce-pro'),
                'description'       => __('Displays when the coupon is not applicable for the current user. The user orders within the specific time does not meet the eligibility criteria. Or user does not purchased specific products.', 'wt-smart-coupons-for-woocommerce-pro'),
                'status'            => 1, 
                'status_locked'     => $status_locked_msg, 
                'supported_placeholders' => array(
                    'coupon_code'        => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
                ),
                'available_filters' => array(
                    
                ),
                'module'    => 'nth_order',
                'group'     => 'warning',
                'initiater' => 'sc', //smart coupon
            );


            /**
             *  Order less than or equal
             *  
             *  @since 2.1.0
             */
            $notifications['nth_order_less_than_equal'] = array(
                'message'           => sprintf(__('This coupon is not available to users with more than %s orders.','wt-smart-coupons-for-woocommerce-pro'), '{required_order_count}'),
                'description'       => __('Displays when a coupon is used before reaching a certain order count.', 'wt-smart-coupons-for-woocommerce-pro'),
                'status'            => 1, 
                'status_locked'     => $status_locked_msg,
                'supported_placeholders' => array(
                    'coupon_code'           => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
                    'required_order_count'  => __('Required order count.', 'wt-smart-coupons-for-woocommerce-pro'),
                    'current_order_count'   => __('Current order count.', 'wt-smart-coupons-for-woocommerce-pro'),
                ),
                'available_filters' => array(
                    
                ),
                'module'    => 'nth_order',
                'group'     => 'warning',
                'initiater' => 'sc', //smart coupon
            );

            return $notifications;
        }


        /** 
         *  Prepare meta value, If meta not exists, use default value
         *  @since 2.0.9
         *  @param $post_id     int         ID of coupon
         *  @param $post_id     string      Meta key
         *  @param $default     mixed       Default value(Optional). If meta not exists returns the default value
         */
        public static function get_coupon_meta_value($post_id, $meta_key, $default='')
        {
            $default_vl=(isset(self::$meta_arr[$meta_key]) && isset(self::$meta_arr[$meta_key]['default']) ? self::$meta_arr[$meta_key]['default'] : $default);
            return (metadata_exists('post', $post_id, $meta_key) ? get_post_meta($post_id, $meta_key, true) : $default_vl);
        }
    }
    Wt_Smart_Coupon_Nth_Order::get_instance();
}