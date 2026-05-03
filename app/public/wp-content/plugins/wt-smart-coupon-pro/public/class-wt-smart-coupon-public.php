<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://www.webtoffee.com
 * @since      1.0.0
 *
 * @package    Wt_Smart_Coupon
 * @subpackage Wt_Smart_Coupon/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Wt_Smart_Coupon
 * @subpackage Wt_Smart_Coupon/public
 * @author     webtoffee <info@webtoffee.com>
 */

if( ! class_exists ( 'Wt_Smart_Coupon_Public' ) ) {
    class Wt_Smart_Coupon_Public {

        /**
         * The ID of this plugin.
         *
         * @since    1.0.0
         * @access   private
         * @var      string    $plugin_name    The ID of this plugin.
         */
        private $plugin_name;

        /**
         * The version of this plugin.
         *
         * @since    1.0.0
         * @access   private
         * @var      string    $version    The current version of this plugin.
         */
        private $version;

        /**
         *  Module list, Module folder and main file must be same as that of module name
         *  Please check the `register_modules` method for more details
         *  @since 2.0.0
         */
        public static $modules=array(
            'store_credit',
            'coupon_banner',
            'url_coupon',
            'nth_order',
            'gift_coupon',
            'auto_coupon',
            'exclude_product',
            'giveaway_product',
            'cart_abandonment',
            'coupon_restriction',
            'notifications', /** @since 2.0.8 */
            'checkout_options', /** @since 2.0.9 */
            'usage_limit', /** @since 2.1.0 */
            'bogo', /** @since 3.0.0 */
        );

        /** 
         * Must use modules
         * @since 2.0.8 
         */
        public static $mu_modules = array(
            'notifications',
        );

        public static $existing_modules=array();

        private static $instance = null;

        /**
         * To store overwrited coupon message.
         * @var array $overwrite_coupon_message
         */
        public $overwrite_coupon_message = array();

        /**
         * Initialize the class and set its properties.
         *
         * @since    1.0.0
         * @param      string    $plugin_name       The name of the plugin.
         * @param      string    $version    The version of this plugin.
         */
        public function __construct($plugin_name, $version) {

            $this->plugin_name = $plugin_name;
            $this->version = $version;
   
        }

        /**
         * Get Instance
         * @since 2.0.0
         */
        public static function get_instance($plugin_name, $version)
        {
            if(self::$instance==null)
            {
                self::$instance=new Wt_Smart_Coupon_Public($plugin_name, $version);
            }

            return self::$instance;
        }

        /**
         *  Registers modules    
         *  @since 2.0.0     
         */
        public function register_modules()
        {            
            Wt_Smart_Coupon::register_modules(self::$modules, 'wt_sc_public_modules', plugin_dir_path( __FILE__ ), self::$existing_modules, self::$mu_modules);          
        }

        /**
         *  Check module enabled    
         *  @since 2.0.0     
         */
        public static function module_exists($module)
        {
            return in_array($module, self::$existing_modules);
        }

        /**
         * Register the stylesheets for the public-facing side of the site.
         *
         * @since    1.0.0
         */
        public function enqueue_styles() {

            wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/wt-smart-coupon-public.css', array(), $this->version, 'all');
            wp_enqueue_style('dashicons');
        }

        /**
         * Register the JavaScript for the public-facing side of the site.
         *
         * @since    1.0.0
         */
        public function enqueue_scripts() {

            $_nonces = array(
                'public' => wp_create_nonce( 'wt_smart_coupons_public' ),
                'apply_coupon' => wp_create_nonce( 'wt_smart_coupons_apply_coupon' ),
            );
            $params=array( 
                'ajaxurl' => esc_url(admin_url('admin-ajax.php')) ,
                'wc_ajax_url' => esc_url(home_url('/?wc-ajax=')),
                'nonces' => $_nonces,
                'labels' => array(
                    'please_wait'=>__('Please wait...', 'wt-smart-coupons-for-woocommerce-pro'),
                    'choose_variation'=>__('Please choose a variation', 'wt-smart-coupons-for-woocommerce-pro'),
                    'error'=>__('Error !!!', 'wt-smart-coupons-for-woocommerce-pro'),
                ),
                'shipping_method' => ( !is_null( WC()->session ) && is_array( WC()->session->get( 'chosen_shipping_methods' ) ) ) ? WC()->session->get( 'chosen_shipping_methods' ) : array(),
                'payment_method' => !is_null( WC()->session ) ? esc_html( WC()->session->get( 'chosen_payment_method' ) ) : '',
                'is_cart' => is_cart(),
            );
            wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/wt-smart-coupon-public.js', array('jquery'), $this->version, false);
            wp_localize_script($this->plugin_name,'WTSmartCouponOBJ', $params);
        }        

        /**
         * Get formatted Meta values of a coupon.
         * @since 1.0.0
         */
        public static function get_coupon_meta_data( $coupon ) {

            if( !$coupon || !is_a ( $coupon,'WC_Coupon') ) {
                return;
            }

            $discount_types = wc_get_coupon_types();
            $coupon_data = array();
            $coupon_amount = $coupon->get_amount();
            switch( $coupon->get_discount_type() ) {
                case 'fixed_cart':
                    $coupon_data['coupon_type']     = __( 'Cart discount', 'wt-smart-coupons-for-woocommerce-pro' );
                    $coupon_data['coupon_amount']   = Wt_Smart_Coupon_Admin::get_formatted_price( $coupon_amount ) ;
                    break;

                case 'fixed_product':
                    $coupon_data['coupon_type']     = __( 'Product discount', 'wt-smart-coupons-for-woocommerce-pro' );
                    $coupon_data['coupon_amount']   = Wt_Smart_Coupon_Admin::get_formatted_price( $coupon_amount );
                    break;

                case 'percent_product':
                    $coupon_data['coupon_type']     = __( 'Product discount', 'wt-smart-coupons-for-woocommerce-pro' );
                    $coupon_data['coupon_amount']   = $coupon_amount . '%';
                    break;

                case 'percent':
                    $coupon_data['coupon_type'] = __('Cart discount', 'wt-smart-coupons-for-woocommerce-pro' );
                    $coupon_data['coupon_amount'] = $coupon_amount . '%';
                    break;
                case 'store_credit':
                    $coupon_data['coupon_type'] = $discount_types[ $coupon->get_discount_type() ];
                    $coupon_data['coupon_amount'] = Wt_Smart_Coupon_Admin::get_formatted_price( $coupon_amount );
                    break;

                default:

                    $coupon_data['coupon_type'] = $discount_types[ $coupon->get_discount_type() ];
                    $coupon_data['coupon_amount'] = $coupon_amount;
                    break;

            }

            if( $coupon_amount === 0 && $coupon->get_free_shipping() ) {
                $coupon_data['coupon_type'] = __('Free shipping','wt-smart-coupons-for-woocommerce-pro');
		        $coupon_data['coupon_amount'] = '';
            }

            
            $free_products  = get_post_meta( $coupon->get_id(), '_wt_free_product_ids', true );

            if($coupon_amount === 0 && $free_products && is_string($free_products) && trim($free_products)!="")
            {              
                $coupon_data['coupon_type'] =  __('Free products', 'wt-smart-coupons-for-woocommerce-pro');
                $coupon_data['coupon_amount'] = '';
            }

            $coupon_data['coupon_expires']      =  self::get_coupon_expires($coupon);
            $coupon_data['email_restriction']   = $coupon->get_email_restrictions();
            $coupon_data['coupon_id']           = $coupon->get_id();
            $coupon_data['start_date']   = self::get_coupon_starts($coupon);

            return apply_filters('wt_smart_coupon_meta_data', $coupon_data, $coupon);
        }

        /**
         *  Get coupon expiry
         *  
         *  @since  2.0.7   Added compatibility for coupon start/expiry time
         *                  Compatibility to return date string
         * 
         *  @param  WC_Coupon    $coupon            The coupon object.
         *  @param  bool         $is_timestamp      Is return timestamp seconds, Optional, Default:true
         *  @return null|int|string                 Return timestamp seconds when $is_timestamp is true otherwise date time string. Format: Y-m-d H:i:s
         *                                          Return null when invalid date and $is_timestamp is true
         *                                          Return empty string when invalid date and $is_timestamp is false
         */
        public static function get_coupon_expires($coupon, $is_timestamp = true)
        {         
            $coupon_expiry = null;

            $coupon_id = $coupon->get_id();
            $coupon_expiry_days = (int) get_post_meta($coupon_id, '_wt_coupon_expiry_in_days', true);
            $coupon_expiry_days_enabled = (bool) (get_post_meta($coupon_id, '_wt_coupon_enable_days', true));

            if( true === $coupon_expiry_days_enabled && 0 < $coupon_expiry_days && !is_null( $coupon->get_date_created() ) )
            {
                $coupon_created = $coupon->get_date_created()->getOffsetTimestamp();
                $start_date = self::get_coupon_starts($coupon);
                $base_date = (isset($start_date) && !empty($start_date) ? $start_date : $coupon_created);


                $coupon_expiry_days = '+'.$coupon_expiry_days.' days';
                $coupon_expiry = strtotime($coupon_expiry_days, $base_date);

            }else
            {

                $coupon_expiry_date = $coupon->get_date_expires();
                
                if(isset($coupon_expiry_date) && !is_null($coupon_expiry_date))
                {
                    $coupon_expiry = $coupon_expiry_date->getOffsetTimestamp();

                    /**
                     *  Add expiry time to expiry date, if exists
                     *  
                     *  @since 2.0.7
                     */
                    $coupon_expiry = self::add_time_value_to_date($coupon_expiry, $coupon_id, '_wt_coupon_expiry_time');
                }
            }

            return ($is_timestamp ? $coupon_expiry : (!is_null($coupon_expiry) ? date('Y-m-d H:i:s', $coupon_expiry) : ''));
        }

        
        /**
         *  Coupon start date with start time
         *  
         *  @since 2.0.7
         */
        public static function get_coupon_starts($coupon, $is_timestamp = true)
        {
            $coupon_starts = null;

            $coupon_id = $coupon->get_id();

            $start_date = get_post_meta($coupon_id, '_wt_coupon_start_date', true);
            $start_date_obj = ($start_date ? Wt_Smart_Coupon_Admin::wt_sc_get_date_prop($start_date) : null);

            if(!is_null($start_date_obj))
            {
                $coupon_starts = $start_date_obj->getOffsetTimestamp();
                
                /** add start time to date */
                $coupon_starts = self::add_time_value_to_date($coupon_starts, $coupon_id, '_wt_coupon_start_time');
            }
     
            return ($is_timestamp ? $coupon_starts : (!is_null($coupon_starts) ? date('Y-m-d H:i:s', $coupon_starts) : ''));
        }

        
        private static function add_time_value_to_date($base_date, $coupon_id, $time_meta_key)
        {
            $_time = get_post_meta($coupon_id, $time_meta_key, true);

            if($base_date && is_string($_time) && "" !== $_time) //only applicable when base date exists
            {
                $_time_arr = explode(':', $_time);
                $_time_hour = (isset($_time_arr[0]) ? absint($_time_arr[0]) * 3600 : 0); //convert to seconds
                $_time_minute = (isset($_time_arr[1]) ? absint($_time_arr[1]) * 60 : 0); //convert to seconds

                $base_date += ($_time_hour + $_time_minute); //add the seconds to base date
            }

            return $base_date;
        }



         /**
         *  Get formatted start date of a coupon.
         *  @since 1.3.2
         *  @since 2.0.1  Added support for offset timestamp
         */
        public static function get_coupon_start_date($coupon_id , $timestamp = false, $offset_timestamp=false) {
            if($timestamp === true)
            {
                $out=(int) get_post_meta($coupon_id, '_wt_coupon_start_date_timestamp', true);
            }else
            {
                $out = get_post_meta( $coupon_id, '_wt_coupon_start_date', true );
            }
           
            if($offset_timestamp===true)
            {
                if($out)
                {
                    $out = Wt_Smart_Coupon_Admin::wt_sc_get_date_prop($out)->getOffsetTimestamp();
                }
            }
            return $out;
        }
        

        /**
         *  Get formatted Start/Expiry date of a coupon.
         *  @since 2.0.1
         *  @since 2.0.7 Showing coupon expiry/start time along with date
         */
        public static function get_coupon_start_expiry_date_texts($date, $type = "start_date")
        {
            if("start_date" === $type && !apply_filters('wt_smart_coupon_show_start_date', false))
            {
                return '';
            }


            if("start_date" !== $type && current_time('timestamp') > $date) //expiry date
            {
                return __('Expired', 'wt-smart-coupons-for-woocommerce-pro');
            }


            $date_format = get_option('date_format', 'F j, Y');

            /**
             *  Checks the date format not contains time related items and the timestamp is not a full date, it contains hours and minutes
             *  `ahisguv` - time related format strings. Eg: a for AM/PM, h for hour
             *  
             *  @since 2.0.7
             */
            if(false === strpbrk($date_format, 'ahisguv') && 0 < ($date % 86400))
            {
                $date_format .= ' g:i a'; 
            }

            $date_format = apply_filters('wt_sc_alter_coupon_start_expiry_date_format', $date_format, $type);
            $date_text = ("start_date" === $type ? __('Starts on ', 'wt-smart-coupons-for-woocommerce-pro') : __('Expires on ', 'wt-smart-coupons-for-woocommerce-pro')). esc_html(date_i18n($date_format, $date)); 
            
            return apply_filters('wt_sc_alter_coupon_start_expiry_date_text', $date_text, $date, $type);

        }

        /**
         * Get all coupons used by a customer in previous orders.
         * 
         * @since 1.0.0
         * @since 2.0.8   Added HPOS Compatibility
         */
        public static function get_coupon_used_by_a_customer( $user,$coupon_code = '', $return = 'COUPONS' )
        {
            global $current_user,$woocommerce,$wpdb;

            if( !$user ) {
                $user = wp_get_current_user();
            }
            $coupon_used = array();
            $customer_id = $user->ID;
            
            $customer_orders = Wt_Smart_Coupon_Common::get_orders(array( 
                'customer'      => $customer_id,
                'limit'         => -1,
            ));

            if ($customer_orders) :
                foreach ($customer_orders as $order) :
                    
                    if( Wt_Smart_Coupon::wt_sc_is_woocommerce_prior_to( '3.7' ) ) {
                        $coupons  = $order->get_used_coupons();
                    } else {
                        $coupons  = $order->get_coupon_codes();
                    }
                    if( $coupons ) {
                        $coupon_used = array_merge( $coupon_used, $coupons );
                    }
                endforeach;

                if( $return =='NO_OF_TIMES' && $coupon_code != '' ) {
                    $count_of_used = array_count_values($coupon_used);
                    
                    return isset( $count_of_used[ $coupon_code ] )? $count_of_used[ $coupon_code ] : 0 ;

                }
                return apply_filters('wt_smart_coupon_used_coupons',array_unique( $coupon_used ),$user );

            else :
                return false;
            endif;
        }

        /**
         *  Get sort order for available coupons
         *  @since 2.0.5
         */
        public static function get_available_coupons_sort_order()
        {
            return apply_filters('wt_sc_alter_available_coupons_sort_order', 'created_date:asc');
        }

        /**
         *  Get user coupons
         * 
         *  @since 2.0.5
         *  @since 2.0.6    SQL query updated to take data from lookup table
         *  @since 2.0.7    Recursively fetching the coupon ids to reach the limit.
         *                  [Bug fix] Non existing post ids are in the list.
         */
        public static function get_user_coupons($user = '', $offset = 0, $limit = 30, $args = array())
        {
            global $wpdb;
            
            if(!$user)
            {
                $user= wp_get_current_user();
            }
            
            if($user)
            {
                $user_id = $user->ID;
                $email = $user->user_email;
            }else
            {
                return array();
            }

            $type = (isset($args['type']) ? $args['type'] : 'available_coupons');

            if(!in_array($type, array('available_coupons', 'expired_coupons', 'auto_coupons')))
            {
                return array(); /* not in the allowed type list */
            }

            $lookup_tb = Wt_Smart_Coupon::get_lookup_table_name();

            if(!Wt_Smart_Coupon::is_table_exists($lookup_tb))  //table not created so return empty array.
            {
                return array();
            }

            /**
             *  Only cart valid coupons
             * 
             *  @since 2.1.0
             */
            $cart_valid_coupons = (isset($args['cart_valid_coupons']) && true === $args['cart_valid_coupons']);

            if($cart_valid_coupons)
            {
                $cart = ((is_object(WC()) && isset(WC()->cart)) ? WC()->cart : null);
                
                if(!is_null($cart))
                {
                    $discounts_obj = new WC_Discounts($cart); //declare WC_Discounts object for is valid checking
                }else
                {
                    $cart_valid_coupons = false; //only true when cart object is available
                }  
            }

            
            $already_fetched_ids = isset($args['already_fetched_ids']) && is_array($args['already_fetched_ids']) ? $args['already_fetched_ids'] : array();
            $already_fetched_ids_count = count($already_fetched_ids);

            $sql = "SELECT coupon_id AS ID FROM {$lookup_tb} WHERE post_status = 'publish' AND is_wt_gc_wallet_coupon = 0";
            $sql_placeholder = array();

            if('available_coupons' == $type || 'expired_coupons' == $type)
            {
                $section=(isset($args['section']) ? $args['section'] : 'my_account');
                $allowed_sections = array('my_account', 'checkout', 'cart');

                if("" != $section && in_array($section, $allowed_sections))
                {
                    $sql .= " AND {$section}_display = 1";
                }

            }else /* 'auto_coupons' */
            {
                $sql .= " AND is_auto_coupon = 1";
            }

            //usage count check
            $used_by_meta_sql = "(SELECT COUNT(pm.meta_id) FROM {$wpdb->postmeta} AS pm WHERE pm.post_id = coupon_id AND pm.meta_key = '_used_by' AND pm.meta_value = %d)";

            //email restriction
            if("" !== trim($email))
            {
                $sql .= " AND (email_restriction = 'a:0:{}' OR email_restriction LIKE %s OR email_restriction LIKE '%*%')";
                $sql_placeholder[] = '%'.$wpdb->esc_like($email).'%';
            }else
            {
                $sql .= " AND (email_restriction = 'a:0:{}' OR email_restriction = '')";
            }


            /**
             * User roles
             *  
             * @since 2.0.7 Added exclude role checking
             */
            $user_roles = (isset($user->roles) && is_array($user->roles) ? $user->roles : array());

            /**
             *  Guest users do not have user roles, so add one to the array to filter user coupons.
             *  @since 2.4.0
             */
            if( empty( $user_roles ) && 0 === $user_id ){
                $user_roles = array( 'wbte_sc_guest' );
            }

            if(!empty($user_roles))
            {
                $role_sql_arr = array();
                $exclude_role_sql_arr = array();

                foreach($user_roles as $k => $user_role)
                {
                    $role_sql_arr[] = "user_roles LIKE %s";
                    $exclude_role_sql_arr[] = "exclude_user_roles NOT LIKE %s";
                    $user_roles[$k] = '%'.$wpdb->esc_like($user_role).'%';
                }
                
                //include user role
                $sql .= " AND (user_roles = '' OR " . $wpdb->prepare(implode(" OR ", $role_sql_arr), $user_roles) . ")";
                
                //exclude user role
                $sql .= " AND (exclude_user_roles = '' OR (" . $wpdb->prepare(implode(" AND ", $exclude_role_sql_arr), $user_roles) . "))";

            }
            
            $gmt_offset = wc_timezone_offset();
            
            if( 'available_coupons' === $type || 'auto_coupons' === $type )
            {
                //expiry date check
                $sql .= " AND (expiry = '' OR 
                    ((UNIX_TIMESTAMP(expiry) + TIME_TO_SEC(TIMEDIFF(NOW(), UTC_TIMESTAMP()))) - %d) >= UNIX_TIMESTAMP()
                )";
                $sql_placeholder[] = $gmt_offset;

                //store credit amount check
                $sql .= " AND ((discount_type = 'store_credit' AND amount > 0) OR discount_type != 'store_credit')";

                //usage limit check
                $sql .= " AND (usage_limit = 0 OR (usage_limit > 0 AND usage_limit > usage_count))";
                $sql .= " AND (usage_limit_per_user = 0 OR (usage_limit_per_user > 0 AND usage_limit_per_user > {$used_by_meta_sql}))";
                $sql_placeholder[] = $user_id;

            }else /* 'expired_coupons' */
            {
                //expiry date check, store credit amount check, usage limit check
                $sql .= " AND (
                    (expiry != '' AND 
                        ((UNIX_TIMESTAMP(expiry) + TIME_TO_SEC(TIMEDIFF(NOW(), UTC_TIMESTAMP()))) - %d) < UNIX_TIMESTAMP()
                    ) OR 
                    (discount_type = 'store_credit' AND amount <= 0) OR 
                    ((usage_limit > 0 AND usage_limit <= usage_count) OR (usage_limit_per_user > 0 AND usage_limit_per_user <= {$used_by_meta_sql}))
                )";

                $sql_placeholder[] = $gmt_offset;
                $sql_placeholder[] = $user_id;
            }


            /* process order by data */
            $orderby_data = (isset($args['orderby']) ? $args['orderby'] : self::get_available_coupons_sort_order());
            $orderby_arr = explode(":", $orderby_data);

            $orderby_allowed = array('created_date' => 'id', 'amount' => 'amount');
            $orderby = (!isset($orderby_allowed[$orderby_arr[0]]) ? 'created_date' : $orderby_arr[0]);

            $orderby_order = strtolower(isset($orderby_arr[1]) ? $orderby_arr[1] : 'asc');
            $orderby_order = strtoupper(!in_array($orderby_order, array('asc', 'desc')) ? 'asc' : $orderby_order);

            $sql .= " ORDER  BY {$orderby_allowed[$orderby]} {$orderby_order} LIMIT %d, %d";
            $sql_placeholder[] = $offset;
            $sql_placeholder[] = $limit;


            $final_sql = $wpdb->prepare($sql, $sql_placeholder);

            $post_ids = $wpdb->get_col($final_sql);
            $post_ids = ($post_ids ? $post_ids : array());

            
            /**
             *  Filter the post_ids. In some cases lookup table was unable to record the post unavailability. So we need to do a further check here to evaluate this.
             * 
             *  @since 2.0.7
             */
            $fetched_post_ids = $post_ids; //this is using to check any data exists in DB
            $remaining_limit = $limit - $already_fetched_ids_count; //may be not the first iteration.
            $filtered_post_ids = array();

            foreach($post_ids as $post_id)
            {
                if('publish' === get_post_status($post_id))
                {
                    /**
                     *  Only cart valid coupons
                     * 
                     *  @since 2.1.0
                     */
                    if($cart_valid_coupons && is_wp_error($discounts_obj->is_coupon_valid(new WC_Coupon($post_id))))
                    {
                        continue; //we need only cart valid coupons, and the current coupon is not valid.
                    }

                    $filtered_post_ids[] = $post_id;

                    if(count($filtered_post_ids) === $remaining_limit) //may be not the first iteration.
                    {
                        break;
                    }
                }
            }

            $post_ids = array_merge($already_fetched_ids, $filtered_post_ids); //may be this not the first loop. So merge post ids from previous recrusion.


            /**
             *  Recrusively fetch the coupon ids.
             *  Here we are checking  not empty of $fetched_post_ids instead of $post_ids because we have to find data in the DB is finished or not. This is usefull when all of the post_ids are filtered by the above `foreach`.
             *  Here we are altered the offset, but not the limit by remaining count, Because it may increase the iteration count in some situations.
             *  Count of $fetched_post_ids and $limit is equal then assumes database have more records to fetch 
             * 
             *  @since 2.0.7
             */ 
            if(!empty($fetched_post_ids) && count($fetched_post_ids) === $limit && count($post_ids) < $limit)
            {
                $new_offset = $limit + $offset;
                $args['already_fetched_ids'] = $post_ids;
                       
                return self::get_user_coupons($user, $new_offset, $limit, $args);
            }

            return apply_filters('wt_sc_alter_user_coupons', $post_ids, $args);
        }

        /**
         *  Fix the flex item alignment issue
         *  @since 2.0.5
         */
        public static function add_hidden_coupon_boxes($count = 2)
        {
            for($i=0; $i<$count; $i++)
            {
                echo '<div class="wt-sc-hidden-coupon-box"></div>';
            }
        }

        /**
         *  Print available coupon for a user
         * 
         *  @param $user        object  WP_User instance.
         *  @param $section     string  section to print 
         *  @param $offset      int     offset of records 
         *  @param $limit       int     max records 
         *  @param $print       bool    If false function will only return the data as array. Otherwise print the data along with return
         *  
         *  @return array    array of coupon objects. Empty array if no record exists
         */
        public static function print_user_available_coupon($user = '', $section = 'my_account', $offset = 0, $limit = 30, $print = true, $by_shortcode = false)
        {
            global $wpdb;
            if(!$user)
            {
                $user= wp_get_current_user();
            }
            if($user)
            {
                $user_id = $user->ID; 
                $email = $user->user_email;
            }else
            {
                return array();
            }
            
            $orderby = (isset($_GET['wt_sc_available_coupons_orderby']) ? sanitize_text_field($_GET['wt_sc_available_coupons_orderby']) : self::get_available_coupons_sort_order());
            
            /**
             *  Display cart valid coupons
             */
            $only_display_cart_valid_coupons = wc_string_to_bool(Wt_Smart_Coupon::get_option('only_display_cart_valid_coupons'));
            $only_display_cart_valid_coupons = ($only_display_cart_valid_coupons && ('cart' === $section || 'checkout' === $section)); //currently only applicable for cart/checkout pages

            $display_invalid_coupons  = (bool) apply_filters('wt_smart_coupon_display_invalid_coupons', ($only_display_cart_valid_coupons ? false : true), $section);

            $args = array(
                    'type' => 'available_coupons', 
                    'section' => $section, 
                    'orderby' => $orderby, 
                    'by_shortcode' => $by_shortcode
            );

            if(false === $display_invalid_coupons) //to return only cart valid coupons
            {
               $args['cart_valid_coupons'] = true; 
            }

            $post_ids = self::get_user_coupons($user, $offset, $limit, $args);
            $out = array();
            
            if($print)
            {
                echo '<div class="wt_coupon_wrapper">';
                
                //inject CSS for coupon block
                self::print_coupon_default_css();
            }

            if(!empty($post_ids))
            {                
                $e = 0; 
                $allowed_html = array();
                
                if( $print ) {
                    $allowed_html = Wt_Smart_Coupon_Common::get_allowed_html();
                }

                foreach($post_ids as $post_id)
                {
                    $post = get_post($post_id); 
                    
                    $coupon_obj = new WC_Coupon($post->ID);
                    $coupon_data = self::get_coupon_meta_data($coupon_obj);
                    $coupon_data['display_on_page'] = ($by_shortcode ? 'by_shortcode' : $section.'_page');


                    // Limit to defined email addresses.
                    if(!self::is_coupon_emails_allowed(array($email), $coupon_obj))
                    {
                        continue;
                    }

                    /* alter coupon post object before printing */
                    $post=apply_filters('wt_alter_coupon_for_user_before_printing', $post, $user, $section);
                    
                    /* alter coupon data before printing */
                    $coupon_data = apply_filters('wt_alter_coupon_data_for_user_before_printing', $coupon_data, $post, $user, $section);

                    if($print)
                    {
                        if(0 === $e) //to print coupon CSS in first template, to avoid same multiple CSS blocks
                        {
                            echo wp_kses( self::get_coupon_html( $post, $coupon_data, "available_coupon", true ), $allowed_html ); // phpcs:ignore
                        }else
                        {
                            echo  self::get_coupon_html( $post, $coupon_data ); // phpcs:ignore
                        }

                        $e++;
                    }

                    $out[]=$coupon_obj;
                }

                if($print)
                {
                    self::add_hidden_coupon_boxes();
                }

            }else
            {
                if($print && 'my_account'===$section)
                {
                    echo '<div class="wt_sc_myaccount_no_available_coupons">';
                        echo apply_filters('wt_sc_alter_myaccount_no_available_coupons_msg', __("Sorry, you don't have any available coupons", 'wt-smart-coupons-for-woocommerce-pro'));
                    echo '</div>';
                }
            }
            if($print)
            {
                echo '</div>';
            }

            if($print && apply_filters('wt_sc_enable_pagination_in_user_available_coupons', true, $section) && !empty($post_ids))
            {
            ?>
                <div class="wt_sc_pagination">
                    <div class="woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination">
                        <?php 
                        global $wp;
                        $current_url = home_url($wp->request);
                        
                        $url_params=!is_array($_GET) ? array() : $_GET; 
                        
                        /* previous link */
                        $prev_url='';
                        $prev_link_html='';
                        if($offset>0)
                        {
                            $new_offset=max(($offset-$limit), 0); /* lesser than zero is not allowed */
                            $post_ids = self::get_user_coupons($user, $new_offset, 1, $args);
                            
                            if(!empty($post_ids)) /* show previous link */
                            {
                                if(0 === $new_offset)
                                {
                                    unset($url_params['wt_sc_available_coupons_offset']);   
                                }else{
                                    $url_params['wt_sc_available_coupons_offset']=$new_offset;
                                }

                                $prev_url=$current_url.'?'.build_query($url_params);
                                $prev_link_html='<a href="'.esc_attr($prev_url).'" class="wt_sc_pagination_previous woocommerce-button woocommerce-button--previous woocommerce-Button woocommerce-Button--previous button">'.__('Previous', 'wt-smart-coupons-for-woocommerce-pro').'</a>';
                            }
                        }

                        echo wp_kses_post(apply_filters("wt_sc_alter_user_available_coupons_previous_link_html", $prev_link_html, $prev_url));

                        /* next link */
                        $next_url='';
                        $next_link_html='';
                        $new_offset=$offset+$limit;
                        $post_ids = self::get_user_coupons($user, $new_offset, 1, $args);
                        if(!empty($post_ids)) /* show next link */ 
                        {   
                            $url_params['wt_sc_available_coupons_offset']=$new_offset;                  
                            $next_url=$current_url.'?'.build_query($url_params);
                            $next_link_html='&nbsp;<a href="'.esc_attr($next_url).'" class="wt_sc_pagination_next woocommerce-button woocommerce-button--next woocommerce-Button woocommerce-Button--next button">'.__('Next', 'wt-smart-coupons-for-woocommerce-pro').'</a>';
                        }

                        echo wp_kses_post(apply_filters("wt_sc_alter_user_available_coupons_next_link_html", $next_link_html, $next_url));

                        ?>
                    </div>
                </div>
            <?php
            }

            return $out;
        }

        /**
         * Action for displaying avalable coupon in cart page.
         * @since 1.1.0
         * @since 2.0.5 Added filter to alter coupons per page
         */
        public function display_available_coupon_in_cart()
        {
            $offset = (isset($_GET['wt_sc_available_coupons_offset']) ? absint($_GET['wt_sc_available_coupons_offset']) : 0);
            $limit  = apply_filters('wt_sc_cart_available_coupons_per_page', 20);
            
            self::print_user_available_coupon('', 'cart', $offset, $limit);
        }

        
        /**
         * Action for displaying avalable coupon in checkout page.
         * @since 1.1.0
         * @since 2.0.5 Added filter to alter coupons per page
         */
        public function display_available_coupon_in_checkout()
        {
            $offset = (isset($_GET['wt_sc_available_coupons_offset']) ? absint($_GET['wt_sc_available_coupons_offset']) : 0); 
            $limit  = apply_filters('wt_sc_checkout_available_coupons_per_page', 20);
            
            do_action('wt_smart_coupon_before_checkout_coupons');
            $available_coupons = self::print_user_available_coupon('', 'checkout', $offset, $limit);
            do_action('wt_smart_coupon_after_checkout_coupons', array('available_coupons' => $available_coupons,));
        }
        

        /**
         *  Get coupon html based on current style
         *  
         *  @since 1.1.0
         *  @since 2.0.7 Taking data from `Coupon_Style` module
         */
        public static function get_coupon_html($coupon, $coupon_data, $coupon_type = "available_coupon", $include_css = false)
        {          
            $coupon_html = '';

            if(Wt_Smart_Coupon_Common::module_exists('coupon_style'))
            {
                if("email_coupon" === $coupon_type) //strip the CSS from template and add it to email CSS
                {
                    /* forcefully include CSS along with HTML*/
                    $include_css = true;
                }

                $coupon_id = (isset($coupon_data['coupon_id']) ? absint($coupon_data['coupon_id']) : 0);
                $coupon = new WC_Coupon($coupon_id);
                $coupon_html = Wt_Smart_Coupon_Style::prepare_coupon_html($coupon, $coupon_data, $coupon_type, $include_css);              
            }

            return $coupon_html;
        }
        

        /**
         *  This function will print CSS for default coupon code block
         *  
         *  @since 2.0.7 
         */
        public static function print_coupon_default_css()
        {
            if(Wt_Smart_Coupon_Common::module_exists('coupon_style'))
            {
                echo '<style type="text/css">';
                echo wp_kses_post(Wt_Smart_Coupon_Style::get_coupon_default_css());
                echo '</style>';
            }
        }


       
        /**
         * Ajax action function for applying coupon on button click
         * 
         *  @since 2.0.8    Added custom coupon success message option.
         */
        function apply_coupon()
        {
            check_ajax_referer( 'wt_smart_coupons_apply_coupon', '_wpnonce' );
            $coupon_code = ( isset( $_POST['coupon_code'])  ?  Wt_Smart_Coupon_Security_Helper::sanitize_item( $_POST['coupon_code'] ) : false);
            $coupon_id = ( isset($_POST['coupon_id'])  ?  Wt_Smart_Coupon_Security_Helper::sanitize_item( $_POST['coupon_id'], 'absint' ) : 0);
            
            if(!$coupon_code && 0 === $coupon_id)
            {
                return;
            }

            $coupon_obj = new WC_Coupon($coupon_id);

            if(0 === $coupon_obj->get_id())
            {
                return;
            }

            $coupon_code = $coupon_obj->get_code();

            if(0 < WC()->cart->get_cart_contents_count())
            {  
                WC()->cart->add_discount($coupon_code);
            }else
            {

                $message = __('Coupon code applied successfully, Please add products into cart', 'wt-smart-coupons-for-woocommerce-pro');

                if(self::module_exists('notifications'))
                {
                   $message = Wt_Smart_Coupon_Notifications_Public::get_coupon_applied_message($coupon_code, $message); 
                }

                $new_message = apply_filters('wt_smart_coupon_click_to_apply_coupon_message_cart_empty', $message);
                $this->start_overwrite_coupon_success_message($coupon_code, $new_message);
           
                WC()->cart->add_discount($coupon_code);
                
                $this->stop_overwrite_coupon_success_message();
            }
            
            wc_print_notices();

            die();
        }


        /**
         * Overwrite the coupon added message
         */
        public function start_overwrite_coupon_success_message($coupon, $new_message = "")
        {
            $this->overwrite_coupon_message[$coupon] =  $new_message;
            add_filter('woocommerce_coupon_message', array( $this, 'overwrite_coupon_code_message'), 10, 3);
        }

        /**
         * Display the coupon message
         */
        public function overwrite_coupon_code_message($msg, $msg_code, $coupon)
        {
            if(isset($this->overwrite_coupon_message[$coupon->get_code()]))
            {
                $msg = $this->overwrite_coupon_message[$coupon->get_code()];
            }
            return $msg;
        }

        /**
         * Stop overwriting coupon
         */
        public function stop_overwrite_coupon_success_message()
        {
            remove_filter('woocommerce_coupon_message', array( $this, 'overwrite_coupon_code_message'), 10);
            $this->overwrite_coupon_message = array();
        }



        /**
         * Add Gift coupon details with order table.
         * 
         * @since 1.1.0
         * @since 2.0.8   Added HPOS Compatibility
         */
        function add_coupon_details_with_order( $order ) {
            $order_id = $order->get_id();
            $coupon_attached = Wt_Smart_Coupon_Common::get_order_meta($order_id , 'wt_coupons');
            
            if( $coupon_attached ) {
                $coupons = maybe_unserialize( $coupon_attached );
                if( empty($coupons )) {
                    return;
                }
                ?>
                <h4><?php _e('Gift coupons issued','wt-smart-coupons-for-woocommerce-pro'); ?></h4>
                <table>
                    <tr>
            
                        <td><?php _e('Number of coupons gifted','wt-smart-coupons-for-woocommerce-pro'); ?></td>
                        <td><?php echo sizeof( $coupons ); ?></td>
                    </tr>
                </table>

                <?php
                
            }
        }

        /**
         *  Add Gift coupon details with Email order.
         * 
         *  @since 1.1.0
         *  @since 2.0.8   Added HPOS Compatibility
         */
        function add_coupon_details_with_order_email( $order, $sent_to_admin, $plain_text, $email ) {
            if( $sent_to_admin ) {
                return;
            }

            $order_id = $order->get_id();
            $coupon_attached = Wt_Smart_Coupon_Common::get_order_meta($order_id , 'wt_coupons');
            
            if( $coupon_attached ) {
                $coupons = maybe_unserialize( $coupon_attached );
                ?>
                <h2><?php _e('Gift coupons issued','wt-smart-coupons-for-woocommerce-pro'); ?></h2>
                <?php
                if( $plain_text ) {
                    _e('Number of coupons gifted: ','wt-smart-coupons-for-woocommerce-pro');
                    echo sizeof( $coupons );
                } else {
                    ?>
                    <div style="margin-bottom:20px">
                        <table cellspacing="0" cellpadding="6"  style="color:#636363;border:none;vertical-align:middle;width:100%;font-family:'Helvetica Neue',Helvetica,Roboto,Arial,sans-serif" >
                            
                            <tr>
                                <th colspan="2" style="border:1px solid #e5e5e5"><?php _e('Number of coupons gifted','wt-smart-coupons-for-woocommerce-pro'); ?></th>
                                <td style="border:1px solid #e5e5e5" ><?php echo sizeof( $coupons ); ?></td>
                            </tr>
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        </table>
                    </div>

                <?php

                }
            }
        }



        public static function get_store_credit_url( $coupon_id ) {
			$view_store_credit_url = wc_get_endpoint_url('wt-view-store-credit', $coupon_id, wc_get_page_permalink('myaccount'));
			return apply_filters('wt_smart_coupon_view_credit_history_url', $view_store_credit_url, $coupon_id );
		}

        /**
         *  Alter calculate totals priority of `Advanced Dynamic Pricing for WooCommerce - By AlgolPlus` plugin to get compatibility
         *  @since  2.0.4
         *  @param  int priority number
         *  @return int priority number
         */
        public function alter_advanced_dynamic_pricing_plugin_calculate_totals_hook_priority($priority)
        {
            return 1; //our calculate_totals priority is 1000
        }

        /**
         * Checks if the given email address(es) matches the ones specified on the coupon.
         * @since 2.0.6 [Bug fix] Email validation is not working properly when email has capital letters
         * @param array $check_emails Array of customer email addresses.
         * @param array $restrictions Array of allowed email addresses.
         * @return bool
         */
        public static function is_coupon_emails_allowed($check_emails, $coupon_obj)
        {
            $restrictions = $coupon_obj->get_email_restrictions();
                    
            if(empty($restrictions))
            {
                return true;
            }

            foreach ( $check_emails as $check_email ) {
                
                $check_email = strtolower($check_email);

                // With a direct match we return true.
                if ( in_array( $check_email, $restrictions, true ) ) {
                    return true;
                }

                // Go through the allowed emails and return true if the email matches a wildcard.
                foreach ( $restrictions as $restriction ) {
                    // Convert to PHP-regex syntax.
                    $regex = '/^' . str_replace( '*', '(.+)?', $restriction ) . '$/';
                    preg_match( $regex, $check_email, $match );
                    if ( ! empty( $match ) ) {
                        return true;
                    }
                }
            }

            // No matches, this one isn't allowed.
            return false;
        } 

        /**
         *  Get customized notification messages
         *  
         *  @since  2.0.8
         *  @param  string      $key    Unique key for the message
         *  @param  array       $args   Values for the function: Coupon code, Placeholders etc
         *  @return string      Empty string when message was disabled otherwise the message
         */
        public static function get_customized_text($key, $args = array())
        {
            $msg = '';
            
            if(self::module_exists('notifications'))
            {
                $msg = Wt_Smart_Coupon_Notifications_Public::get_customized_text($key, $args);
            }

            return (false === $msg ? '' : $msg);
        }


        /**
         *  Ajax callback to save checkout values for block checkout.
         * 
         *  @since 2.3.0
         */
        public function set_block_checkout_values() {

            // Nonce verification.
            $nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
            $nonce = ( is_array( $nonce ) ? reset( $nonce ) : $nonce );

            if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wt_smart_coupons_public' ) ) {
                exit();
            }

            // Set payment method
            $payment_method = ( isset( $_POST['payment_method'] ) ? wc_clean( wp_unslash( $_POST['payment_method'] ) ) : '' );
            WC()->session->set( 'chosen_payment_method', $payment_method );

            
            //Set shipping
            $chosen_shipping_methods = !is_null( WC()->session ) ? WC()->session->get( 'chosen_shipping_methods' ) : array() ;
            $posted_shipping_methods = isset( $_POST['shipping_method'] ) ? wc_clean( wp_unslash( $_POST['shipping_method'] ) ) : array();

            if ( is_array( $posted_shipping_methods ) ) {
                foreach ( $posted_shipping_methods as $i => $value ) {
                    $chosen_shipping_methods[ $i ] = $value;
                }
            }
            
            WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );

            exit();
        }

        /**
         *  Determines whether the current request is for an administrative interface page.
         * 
         *  @since 2.4.2
         *  @return boolean   Is admin or not
         */
        public static function is_admin(){
            /**
             *  Hook to bypass is_admin check
             *  Example scenario: When scripts using admin ajax on frontend.
             *  
             *  @since  2.2.0 'wt_sc_bypass_is_admin_check'
             *  @since  2.4.2 Moved hook 'wt_sc_bypass_is_admin_check' from coupon restriction public module to here
             * 
             *  @param  bool     Is admin or not, Default: true
             */
            return is_admin() && apply_filters( 'wt_sc_bypass_is_admin_check', true );
        }

        
        /**
         * Add 'available coupon in block cart/checkout' blocks data
         * Hooked into: wbte_sc_alter_blocks_data
         * 
         *  @since 3.2.0
         *  @param array $block_data block data array.
         *  @return array block data array with added coupon blocks data.
         */
        public function add_coupon_blocks_data( $block_data ) {

            ob_start();            
            $this->display_available_coupon_in_cart();
            $coupons_html = ob_get_clean();
            $block_data['coupon_blocks_cart'] = $coupons_html;

            ob_start();            
            $this->display_available_coupon_in_checkout();
            $coupons_html = ob_get_clean();
            $block_data['coupon_blocks_checkout'] = $coupons_html;

            return $block_data;
        }

    }
}