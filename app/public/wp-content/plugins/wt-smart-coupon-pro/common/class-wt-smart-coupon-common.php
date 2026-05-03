<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://www.webtoffee.com
 * @since      1.3.5
 *
 * @package    Wt_Smart_Coupon
 * @subpackage Wt_Smart_Coupon/common
 */

if( ! class_exists ( 'Wt_Smart_Coupon_Common' ) ) {
    class Wt_Smart_Coupon_Common {

        /**
         * The ID of this plugin.
         *
         * @since    1.3.5
         * @access   private
         * @var      string    $plugin_name    The ID of this plugin.
         */
        private $plugin_name;

        /**
         * The version of this plugin.
         *
         * @since    1.3.5
         * @access   private
         * @var      string    $version    The current version of this plugin.
         */
        private $version;

        /*
         * module list, Module folder and main file must be same as that of module name
         * Please check the `register_modules` method for more details
         */
        public static $modules=array(
            'coupon_style',
            'coupon_banner',
            'coupon_category',
            'store_credit',
            'nth_order',
            'gift_coupon',
            'exclude_product',
            'cart_abandonment',
            'signup_coupon',
            'coupon_shortcode',
            'coupon_restriction',
            'giveaway_product',
            'notifications', /** @since 2.0.8 */
            'checkout_options', /** @since 2.0.9 */
            'auto_coupon', /** @since 2.3.0 */
            'bogo', /** @since 3.0.0 */
            'wbte_sc_language_translation', /** @since 3.2.0 */
        );

        /** 
         * Must use modules
         * @since 2.0.8 
         */
        public static $mu_modules = array(
            'notifications',
        );

        private static $hpos_enabled = null;

        public static $existing_modules=array();

        private static $instance = null;

        public static $lookup_table_allowed_meta_keys = array(
            '_wt_make_auto_coupon' => array('is_auto_coupon', '%d'),
            '_wbte_sc_auto_coupon_priority' => array('auto_coupon_priority', '%d'), /** @since 2.3.0 */ 
            '_wc_make_coupon_available' => array(array('my_account_display', 'cart_display', 'checkout_display'), '%d'), 
            'customer_email' => array('email_restriction', '%s'), 
            '_wt_sc_user_roles' => array('user_roles', '%s'), 
            '_wt_sc_exclude_user_roles' => array('exclude_user_roles', '%s'), 
            '_wt_coupon_expiry_in_days' => array('expiry', '%s'), 
            '_wt_coupon_enable_days' => array('expiry', '%s'), 
            '_wt_coupon_start_date' => array('expiry', '%s'), 
            '_wt_coupon_start_time' => array('expiry', '%s'), /** @since 2.0.7 */
            '_wt_coupon_expiry_time' => array('expiry', '%s'), /** @since 2.0.7 */
            'date_expires' => array('expiry', '%s'), 
            'discount_type' => array('discount_type', '%s'),
            'coupon_amount' => array('amount', '%f'), 
            'usage_limit' => array('usage_limit', '%d'),
            'usage_limit_per_user' => array('usage_limit_per_user', '%d'), 
            'usage_count' => array('usage_count', '%d'), 
            '_wt_gc_user_wallet_coupon' => array('is_wt_gc_wallet_coupon', '%d'),
        );

        /**
         * Initialize the class and set its properties.
         *
         * @since    1.3.5
         * @param      string    $plugin_name       The name of the plugin.
         * @param      string    $version    The version of this plugin.
         */
        public function __construct($plugin_name, $version) {

            $this->plugin_name = $plugin_name;
            $this->version = $version;
   
        }

        /**
         * Get Instance
         * @since 1.3.5
         */
        public static function get_instance($plugin_name, $version)
        {
            if(self::$instance==null)
            {
                self::$instance=new Wt_Smart_Coupon_Common($plugin_name, $version);
            }

            return self::$instance;
        }

        /**
         *  Registers modules    
         *  @since 1.3.5     
         */
        public function register_modules()
        {            
            Wt_Smart_Coupon::register_modules(self::$modules, 'wt_sc_common_modules', plugin_dir_path( __FILE__ ), self::$existing_modules, self::$mu_modules);          
        }

        /**
         *  Check module enabled    
         *  @since 1.3.5     
         */
        public static function module_exists($module)
        {
            return in_array($module, self::$existing_modules);
        }

        /**
         *  @since 2.0.1
         *  Smart coupon plugin common hook on order status change.
         *  Hook to add third party statuses
         */
        public function on_order_status_change($order_id, $status_from, $status_to, $order)
        {
            $order_statuses=array('cancelled', 'refunded', 'completed');
            
            $order_statuses=apply_filters('wt_sc_on_order_status_change_statuses', $order_statuses);
            
            if(false!==$status_key=array_search($status_to, $order_statuses))
            {
                do_action('wt_sc_on_order_'.$order_statuses[$status_key], $order_id, $status_from, $status_to, $order);
            }else
            {
                foreach($order_statuses as $order_status)
                {
                    /* third party plugin statuses */
                    $custom_statuses=apply_filters('wt_sc_custom_order_status_'.$order_status, array());
                    if(in_array($status_to, $custom_statuses))
                    {
                        do_action('wt_sc_on_order_'.$order_status, $order_id, $status_from, $status_to, $order);
                    }
                }
            }
        }

        /**
         *  @since 2.0.1
         *  Check the coupon exists
         */
        public static function is_coupon_exists($coupon)
        {
            global $wpdb;
            if(!is_null($wpdb->get_row($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_type ='shop_coupon' AND post_status = 'publish' AND post_title = %s ",$coupon ))))
            {
                return true;                
            }
            return false;
        }

        public static function get_product_from_cart_item($cart_item)
        {
            if(is_null($cart_item['data']) && is_a($cart_item, 'WC_Order_Item_Product'))
            {
                return $cart_item->get_product();
            }

            return (isset($cart_item['data']) ? $cart_item['data'] : null);
        }

        /**
         *  This function will return cart item key if the argument is WC_Cart item, otherwise return order item id
         */
        public static function get_item_key_form_item($cart_item)
        {
            if(is_null($cart_item['key']) && is_a($cart_item, 'WC_Order_Item_Product'))
            {
                return $cart_item->get_id();
            }

            return (isset($cart_item['key']) ? $cart_item['key'] : null);
        }

        /**
         *  Check is order edit, apply coupon etc from backend
         */
        public static function is_order_edit($cart_item)
        {
            return is_a($cart_item, 'WC_Order_Item_Product');
        }

        /**
         *  Get calculate totals hook priority
         *  @since 2.0.6
         *  @param int      $priority   Priority number
         *  @param string   $hook       Hook name, In which hook the priority is applying. Eg: `woocommerce_after_calculate_totals`, `woocommerce_order_after_calculate_totals`
         */
        public static function get_calculate_totals_hook_priority($priority, $hook)
        {
            return apply_filters('wt_sc_calculate_totals_hook_priority', $priority, $hook);
        }

        /**
         *  Insert existing coupon data to lookup table
         *  @since 2.0.6
         *  @since 2.0.7    Code updated to handle slow sites
         *                  New filter: wt_sc_lookup_table_migration_batch_limit
         *                  Duplicate removal added
         */
        public function update_existing_coupon_data_to_lookup_table()
        {
            if(get_option('wt_sc_coupon_lookup_updated'))
            {
                return; //update was already done.  
            }

            global $wpdb;
            $lookup_tb = Wt_Smart_Coupon::get_lookup_table_name();
            
            Wt_Smart_Coupon::install_lookup_table(); //this method will check and create lookup table, if not exists

            if(!Wt_Smart_Coupon::is_table_exists($lookup_tb))  //table not created so return.
            {
                return;
            }

            $last_updated_id = absint(get_option('wt_sc_coupon_lookup_migration_last_id', 0));

            /**
             *  Alter migration batch limit
             * 
             *  @since 2.0.7
             */
            $batch_limit = apply_filters('wt_sc_lookup_table_migration_batch_limit', 100);

            $results = $wpdb->get_results($wpdb->prepare("SELECT p.ID, p.post_status, p.post_date_gmt FROM {$wpdb->posts} AS p WHERE p.post_type = 'shop_coupon' AND p.ID > %d ORDER BY p.ID ASC LIMIT %d", $last_updated_id, $batch_limit), ARRAY_A);

            foreach($results as $result)
            {
                $this->update_data_to_lookup_table($result, true);
                $last_updated_id = $result['ID'];
            }

            //remove duplicates
            $wpdb->query("DELETE t1 FROM {$lookup_tb} t1 INNER JOIN {$lookup_tb} t2 WHERE t1.id < t2.id AND t1.coupon_id = t2.coupon_id");


            $results = $wpdb->get_results($wpdb->prepare("SELECT p.ID, p.post_status, p.post_date_gmt FROM {$wpdb->posts} AS p WHERE p.post_type = 'shop_coupon' AND p.ID > %d ORDER BY p.ID ASC LIMIT 1", $last_updated_id), ARRAY_A);

            if(empty($results)) //no more data. So update as completed
            {
                add_option('wt_sc_coupon_lookup_updated', time());
                delete_option('wt_sc_coupon_lookup_migration_last_id'); // migration completed, so not required anymore
            }else
            {
                update_option('wt_sc_coupon_lookup_migration_last_id', $last_updated_id); //update current batch last id
            }

        }


        /**
         *  Update lookup table on coupon object save
         *  @since  2.0.6
         */
        public function update_coupon_lookup_on_object_save($data_obj, $data_store)
        {
            if(!is_a($data_obj, 'WC_Coupon'))
            {
                return;
            }

            $this->check_and_update_coupon_lookup_table($data_obj->get_id());
        }

        /**
         *  Update lookup table on coupon meta data save
         *  @since  2.0.6
         */
        public function update_coupon_lookup_on_meta_save($post_id, $post)
        {
            $this->check_and_update_coupon_lookup_table($post_id);
        }

        /**
         *  Update lookup table on coupon usage count change
         *  @since  2.0.6
         */
        public function update_coupon_lookup_on_usage_count_change($coupon, $new_count, $used_by)
        {
            $post_id = $coupon->get_id();

            if($this->is_no_lookup_table_entry($post_id)) //no record in lookup table
            {
                $this->check_and_update_coupon_lookup_table($post_id, true); //second argument is true for force insert, so it will skip the update/insert check.
            }else
            {
                global $wpdb;
                $lookup_tb = Wt_Smart_Coupon::get_lookup_table_name();
                
                if(Wt_Smart_Coupon::is_table_exists($lookup_tb))
                {
                    $wpdb->update($lookup_tb, array('usage_count' => $new_count), array('coupon_id' => $post_id), array('%d'), array('%d'));
                }
            }
        }

        /**
         *  Update lookup table on post meta update
         *  @since  2.0.6
         */
        public function update_coupon_lookup_on_postmeta_change($meta_id, $object_id, $meta_key, $meta_value)
        { 
            if(in_array($meta_key, array_keys(self::$lookup_table_allowed_meta_keys)) && 'shop_coupon' === get_post_type($object_id))
            {
                $this->check_and_update_coupon_lookup_table($object_id, false, $meta_key);
            }
        }

        /**
         *  Update lookup table on post status update
         *  @since  2.0.6
         */
        public function update_coupon_lookup_on_post_status_change($new_status, $old_status, $post)
        { 
            if('shop_coupon' === get_post_type($post))
            {
                $post_id = $post->ID;

                if($this->is_no_lookup_table_entry($post_id)) //no record in lookup table
                {
                    $this->check_and_update_coupon_lookup_table($post_id, true); //second argument is true for force insert, so it will skip the update/insert check.
                }else
                {
                    global $wpdb;
                    $lookup_tb = Wt_Smart_Coupon::get_lookup_table_name();

                    if(Wt_Smart_Coupon::is_table_exists($lookup_tb))
                    {
                        $wpdb->update($lookup_tb, array('post_status' => $new_status), array('coupon_id' => $post_id), array('%s'), array('%d'));
                    }
                }
            }
        }

        /**
         *  Insert/update coupon data to lookup table
         * 
         *  @since  2.0.6
         *  @since  2.0.7 Added already exists checking
         *  @param  array   $data_row   post data array from database
         *  @param  boolean   $insert   insert or update to existing
         *  @param  string   $meta_key   Any specific meta key
         */
        private function update_data_to_lookup_table($data_row, $insert = false, $meta_key = '')
        {
            global $wpdb;
            $lookup_tb  = Wt_Smart_Coupon::get_lookup_table_name();

            if(!Wt_Smart_Coupon::is_table_exists($lookup_tb))  //table not created so return.
            {
                return;
            }

            $coupon_id  = $data_row['ID'];
            $coupon     = new WC_Coupon($coupon_id);

            if($insert && !$this->is_no_lookup_table_entry($coupon_id)) //inserting and already data exists
            {
                return;
            }

            /**
             *  Update a specific meta key
             */
            if(!$insert && "" != $meta_key && isset(self::$lookup_table_allowed_meta_keys[$meta_key])) //specific meta key update
            {
                if('_wc_make_coupon_available' === $meta_key)
                {
                    $coupon_loc = $this->get_meta_data_for_lookup_table($coupon, $coupon_id, $meta_key);
                    
                    if( self::is_activated_coupon( $coupon ) ){
                        $update_data = array(
                            'my_account_display'    => absint( in_array( 'my_account', $coupon_loc ) ), 
                            'cart_display'          => absint( in_array( 'cart', $coupon_loc ) ),
                            'checkout_display'      => absint( in_array( 'checkout', $coupon_loc ) ), 
                        );
                    }else{
                        // Make coupon display false if coupon not activated
                        $update_data = array(
                            'my_account_display'    => 0, 
                            'cart_display'          => 0,
                            'checkout_display'      => 0, 
                        );
                    }

                    $update_data_format = array('%d', '%d', '%d');
                }else
                {
                    $update_data = array(
                        self::$lookup_table_allowed_meta_keys[$meta_key][0]    => $this->get_meta_data_for_lookup_table($coupon, $coupon_id, $meta_key), 
                    );
                    $update_data_format = array(self::$lookup_table_allowed_meta_keys[$meta_key][1]);
                }

                $wpdb->update($lookup_tb, $update_data, array('coupon_id' => $coupon_id), $update_data_format, array('%d'));

                return;
            }


            $coupon_loc = $this->get_meta_data_for_lookup_table($coupon, $coupon_id, '_wc_make_coupon_available');

            $data_arr = array(
                        'coupon_id'             => $coupon_id, 
                        'is_auto_coupon'        => $this->get_meta_data_for_lookup_table($coupon, $coupon_id, '_wt_make_auto_coupon'),                       
                        'auto_coupon_priority'  => $this->get_meta_data_for_lookup_table($coupon, $coupon_id, '_wbte_sc_auto_coupon_priority'),
                        'my_account_display'    => absint(in_array('my_account', $coupon_loc)), 
                        'cart_display'          => absint(in_array('cart', $coupon_loc)),
                        'checkout_display'      => absint(in_array('checkout', $coupon_loc)), 
                        'post_status'           => $data_row['post_status'], 
                        'email_restriction'     => $this->get_meta_data_for_lookup_table($coupon, $coupon_id, 'customer_email'),
                        'user_roles'            => $this->get_meta_data_for_lookup_table($coupon, $coupon_id, '_wt_sc_user_roles'),
                        'exclude_user_roles'    => $this->get_meta_data_for_lookup_table($coupon, $coupon_id, '_wt_sc_exclude_user_roles'),
                        'expiry'                => $this->get_meta_data_for_lookup_table($coupon, $coupon_id, 'expiry'), 
                        'discount_type'         => $this->get_meta_data_for_lookup_table($coupon, $coupon_id, 'discount_type'), 
                        'amount'                => $this->get_meta_data_for_lookup_table($coupon, $coupon_id, 'coupon_amount'),
                        'usage_limit'           => $this->get_meta_data_for_lookup_table($coupon, $coupon_id, 'usage_limit'),
                        'usage_limit_per_user'  => $this->get_meta_data_for_lookup_table($coupon, $coupon_id, 'usage_limit_per_user'),
                        'usage_count'           => $this->get_meta_data_for_lookup_table($coupon, $coupon_id, 'usage_count'),
                        'is_wt_gc_wallet_coupon'=> $this->get_meta_data_for_lookup_table($coupon, $coupon_id, '_wt_gc_user_wallet_coupon'),
                    );

                    // Make coupon display false if coupon not activated
                    if( !self::is_activated_coupon( $coupon ) ){
                        $data_arr['my_account_display'] = 0;
                        $data_arr['cart_display'] = 0;
                        $data_arr['checkout_display'] = 0;
                    }

            $data_format_arr =  array(
                '%d',   //coupon_id
                '%d',   //is_auto_coupon
                '%d',   //auto_coupon_priority
                '%d',   //my_account_display
                '%d',   //cart_display
                '%d',   //checkout_display
                '%s',   //post_status
                '%s',   //email_restriction
                '%s',   //user_roles
                '%s',   //exclude_user_roles
                '%s',   //expiry
                '%s',   //discount_type
                '%f',   //amount
                '%d',   //usage_limit
                '%d',   //usage_limit_per_user
                '%d',   //usage_count
                '%d',   //is_wt_gc_wallet_coupon         
            );
            
            if($insert)
            {
                if($this->is_no_lookup_table_entry($coupon_id))
                {
                    $wpdb->insert($lookup_tb, $data_arr, $data_format_arr);
                }
            }else
            {
                $wpdb->update($lookup_tb, $data_arr, array('coupon_id' => $coupon_id), $data_format_arr, array('%d'));
            }
        }

        private function get_meta_data_for_lookup_table($coupon, $coupon_id, $meta_key)
        {
            $out = '';
            switch ($meta_key) 
            {
                case '_wt_make_auto_coupon':
                case '_wbte_sc_auto_coupon_priority':
                case '_wt_gc_user_wallet_coupon':
                    $out = absint(get_post_meta($coupon_id, $meta_key, true));
                    break;
                
                case '_wc_make_coupon_available':
                    $out = explode(",", strval(get_post_meta($coupon_id, '_wc_make_coupon_available', true)));
                    break;
                
                case 'customer_email':
                    $out = maybe_serialize($coupon->get_email_restrictions());
                    break;
                
                case '_wt_sc_user_roles':
                case '_wt_sc_exclude_user_roles':
                    $out = get_post_meta($coupon_id, $meta_key, true);
                    break;
                
                case 'discount_type':
                    $out = $coupon->get_discount_type();
                    break;
                
                case 'coupon_amount':
                    $out = $coupon->get_amount();
                    break;
                
                case 'usage_limit':
                    $out = $coupon->get_usage_limit();
                    break;
                
                case 'usage_limit_per_user':
                    $out = $coupon->get_usage_limit_per_user();
                    break;
                
                case 'usage_count':
                    $out = $coupon->get_usage_count();
                    break;
                
                case 'expiry': //not a meta key                   
                case '_wt_coupon_expiry_in_days':                   
                case '_wt_coupon_enable_days':                   
                case '_wt_coupon_start_date':                   
                case '_wt_coupon_start_time':                   
                case '_wt_coupon_expiry_time':                   
                case 'date_expires':                   
                    
                    $out = Wt_Smart_Coupon_Public::get_coupon_expires($coupon, false);

                    break;
                
                default:
                    $out = '';
                    break;
            }

            return $out;
        }

        /**
         *  Check and update coupon data to lookup table
         *  @since  2.0.6
         *  @param  int   $post_id   post id
         *  @param  boolean   $force_insert   Force insert. Without data exists check.
         *  @param  string   $meta_key   Any specific meta key
         */
        private function check_and_update_coupon_lookup_table($post_id, $force_insert = false, $meta_key = '')
        {
            global $wpdb;

            $lookup_tb = Wt_Smart_Coupon::get_lookup_table_name();

            if(!Wt_Smart_Coupon::is_table_exists($lookup_tb))  //table not created so return.
            {
                return;
            }

            $post_row = $wpdb->get_row($wpdb->prepare("SELECT ID, post_status, post_date_gmt FROM {$wpdb->posts} WHERE ID=%d", absint($post_id)), ARRAY_A);

            $this->update_data_to_lookup_table($post_row, ($force_insert ? $force_insert : $this->is_no_lookup_table_entry($post_id)), $meta_key);
        }

        /**
         *  Check there is lookup table entry exists
         *  @since  2.0.6
         *  @param  int   $post_id   post id
         *  @return  boolean   true when no data exists in lookup table
         */
        private function is_no_lookup_table_entry($post_id)
        {
            global $wpdb;
            $lookup_tb = Wt_Smart_Coupon::get_lookup_table_name();

            if(!Wt_Smart_Coupon::is_table_exists($lookup_tb))  //table not created so return true.
            {
                return true;
            }

            $row = $wpdb->get_row($wpdb->prepare("SELECT coupon_id FROM {$lookup_tb} WHERE coupon_id=%d", $post_id), ARRAY_A);
            return empty($row);
        }

        
        public function check_and_update_lookup_table()
        {
            if(Wt_Smart_Coupon::get_lookup_table_version() > Wt_Smart_Coupon::get_installed_lookup_table_version()) //new version available
            {
                Wt_Smart_Coupon::install_lookup_table();
            }
        }

        /**
         *  Convert order items like cart items. This will help us to give compatibility for coupons in both frontend and backend.
         *  
         *  @since  2.0.7
         *  @since  2.0.8       Preparing items from WC_Discount order items
         *  @param  object[]    $wc_discount_order_items    WC_Discount order items
         *  @return array       $cart_items                 Processed order items, structure similar to cart items
         */
        public static function convert_order_item_like_cart_item($wc_discount_order_items)
        {
            $new_cart_items = array();

            foreach($wc_discount_order_items as $order_item_id => $item)
            {
                $order_item = $item->object;
                $order_item_key = $item->key;

                $_product = $order_item->get_product();

                $new_cart_items[$order_item_key] = array(
                    'key'               => $order_item_key,
                    'product_id'        => $order_item->get_product_id(),
                    'variation_id'      => $order_item->get_variation_id(),
                    'variation'         => array(),
                    'quantity'          => $order_item->get_quantity(),      
                    'line_tax_data'     => $order_item->get_taxes(),
                    'line_subtotal'     => $order_item->get_subtotal(),
                    'line_subtotal_tax' => $order_item->get_subtotal_tax(),
                    'line_total'        => $order_item->get_total(),
                    'line_tax'          => $order_item->get_total_tax(),
                    'data'              => $_product,
                    
                );


                /**
                 *  Variation product. So prepare variation data 
                 */
                if(0 < $order_item->get_variation_id())
                {
                    $attributes = $_product->get_attributes();

                    foreach($attributes as $attribute_key => $attribute_val)
                    {
                        $attributes['attribute_'.$attribute_key] = $attribute_val;
                        unset($attributes[$attribute_key]);
                    }

                    $new_cart_items[$order_item_key]['variation'] = $attributes;
                }


                /**
                 *  Check current item is a giveaway and add giveaway data
                 * 
                 */
                $is_free_item = wc_get_order_item_meta($order_item_key, 'free_product', true);

                if($is_free_item)
                {
                    $new_cart_items[$order_item_key]['free_product']        = $is_free_item;
                    $new_cart_items[$order_item_key]['free_gift_coupon']    = wc_get_order_item_meta($order_item_key, 'free_gift_coupon', true);
                    $new_cart_items[$order_item_key]['free_category']       = wc_get_order_item_meta($order_item_key, 'free_category', true);
                }          
            }

            return apply_filters('wt_sc_alter_converted_order_items', $new_cart_items, $order_items);
        }

        /**
         *  To get total records in lookup table. Using in lookup table migration message.
         *  
         *  @since 2.0.7
         *  @return int Total records in lookup table
         */
        public static function get_lookup_table_record_count()
        {
            global $wpdb;
            $lookup_tb = Wt_Smart_Coupon::get_lookup_table_name();

            if(!Wt_Smart_Coupon::is_table_exists($lookup_tb))  //table not created so return zero.
            {
                return 0;
            }

            $row = $wpdb->get_row("SELECT COUNT(DISTINCT coupon_id) AS total_records FROM {$lookup_tb}", ARRAY_A);

            return absint(!empty($row) && isset($row['total_records']) ? $row['total_records'] : 0);
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
            $notifications['invalid_shipping_method'] = array(
                'message'           => __('Sorry, this coupon is not valid for the selected shipping method.', 'wt-smart-coupons-for-woocommerce-pro'),
                'description'       => __('Displays when the coupon entered is not valid for the selected shipping option.', 'wt-smart-coupons-for-woocommerce-pro'),
                'status'            => 1, 
                'supported_placeholders' => array(
                    'coupon_code' => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
                ),
                'available_filters' => array(),
                'module'   => 'main',
                'group'         => 'warning',
                'initiater'     => 'sc', //smart coupon
            );

            $notifications['invalid_user_role'] = array(
                'message'           => __('Sorry! this coupon can’t be redeemed from your current user role.', 'wt-smart-coupons-for-woocommerce-pro'),
                'description'       => __('Displays when selected coupon does not apply to the role of logged-in user.', 'wt-smart-coupons-for-woocommerce-pro'),
                'status'            => 1, 
                'supported_placeholders' => array(
                    'coupon_code' => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
                ),
                'available_filters' => array(),
                'module'   => 'main',
                'group'         => 'warning',
                'initiater'     => 'sc', //smart coupon
            );

            $notifications['invalid_location'] = array(
                'message'           => __('Sorry, this coupon is not valid for the selected location.', 'wt-smart-coupons-for-woocommerce-pro'),
                'description'       => __('Displays when the coupon is not valid for the selected billing/shipping location.', 'wt-smart-coupons-for-woocommerce-pro'),
                'status'            => 1, 
                'supported_placeholders' => array(
                    'coupon_code' => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
                ),
                'available_filters' => array(),
                'module'   => 'main',
                'group'         => 'warning',
                'initiater'     => 'sc', //smart coupon
            );

            return $notifications;
        }

        
        /**
         * Get WC_Order object from the given value.
         * 
         * @since   2.0.8
         * @static
         * @param   int|WC_order    $order      Order id or order object
         * @return  WC_order        Order object
         */
        public static function get_order($order)
        {
            return (is_int($order) || (is_string($order) && 0 < absint($order)) ? wc_get_order(absint($order)) : $order);
        }

       
        /**
         * Get order id from the given value.
         * 
         * @since   2.0.8
         * @static
         * @param   int|WC_order    $order      Order id or order object
         * @return  int             Order id
         */
        public static function get_order_id($order)
        {
            return (is_int($order) ? $order : $order->get_id());
        }


        /**
         * Is WooCommerce HPOS enabled
         * 
         * @since   2.0.8
         * @static
         * @return  bool    True when enabled otherwise false
         */
        public static function is_wc_hpos_enabled()
        {
            if(is_null(self::$hpos_enabled))
            {
                if(class_exists('Automattic\WooCommerce\Utilities\OrderUtil'))
                {
                    self::$hpos_enabled = Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
                }else
                {
                    self::$hpos_enabled = false;
                }
            }

            return self::$hpos_enabled;
        }

       
        /**
         * Get order meta value.
         * HPOS and non-HPOS compatible
         * 
         * @since   2.0.8
         * @static
         * @param   int|WC_order    $order      Order id or order object
         * @param   string          $meta_key   Meta key
         * @param   mixed           $default    Optional, Default value for the meta
         */
        public static function get_order_meta($order, $meta_key, $default = '')
        {
            if(self::is_wc_hpos_enabled())
            {
                $order = self::get_order($order); 

                if(!$order)
                {
                    return $default;
                }

                $meta_value = $order->get_meta($meta_key);
                return (!$meta_value ? get_post_meta($order->get_id(), $meta_key, true) : $meta_value);

            }else
            {
                $order_id = self::get_order_id($order);

                $meta_value = get_post_meta($order_id, $meta_key, true);

                if(!$meta_value)
                {
                    $order = wc_get_order($order_id);
                    return $order ? $order->get_meta($meta_key) : $default;

                }else
                {
                    return $meta_value;
                }
            }
        }


        /**
         * Delete order meta.
         * HPOS and non-HPOS compatible
         * 
         * @since   2.0.8
         * @static
         * @param   int|WC_order    $order      Order id or order object
         * @param   string          $meta_key   Meta key
         */
        public static function delete_order_meta($order, $meta_key)
        {
            if(self::is_wc_hpos_enabled())
            {
                $order = self::get_order($order);
                $order->delete_meta_data($meta_key);
                $order->save();

                delete_post_meta($order->get_id(), $meta_key); //fallback
            }else
            {
                $order_id = self::get_order_id($order);
                delete_post_meta($order_id, $meta_key);

                //fallback
                $order = wc_get_order($order_id);
                $order->delete_meta_data($meta_key);
                $order->save();
            }
        }


        /**
         * Update order meta.
         * HPOS and non-HPOS compatible
         * 
         * @since   2.0.8
         * @static
         * @param   int|WC_order    $order      Order id or order object
         * @param   string          $meta_key   Meta key
         * @param   mixed           $value      Value for meta
         */
        public static function update_order_meta($order, $meta_key, $value)
        {
            if(self::is_wc_hpos_enabled())
            {
                $order = self::get_order($order);
                $order->update_meta_data($meta_key, $value);
                $order->save();
            }else
            {
                $order_id = self::get_order_id($order);
                update_post_meta($order_id, $meta_key, $value);
            }
        }


        /**
         * Get orders based on the arguments provided
         * 
         * @since   2.0.8
         * @static
         * @param   array   $args     Query arguments for `wc_get_orders` function
         * @return  array   Orders
         */
        public static function get_orders($args)
        {
            return wc_get_orders($args);
        }


        /**
         * Is current admin page is HPOS enabled orders page
         * 
         * @since   2.0.8
         * @static
         * @return  bool    True when current page is HPOS orders page
         */
        public static function is_hpos_orders_page()
        {
            $basename = basename(parse_url($_SERVER['PHP_SELF'], PHP_URL_PATH));
            $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : ''; 
            $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : ''; 

            return ('admin.php' === $basename && 'wc-orders' === $page && ('' === $action || '-1' === $action));
        }


        /**
         *  Custom expiry for store credit coupons, This is for Gift cards plugin by WebToffee
         * 
         *  @since  2.0.8
         *  @param  WC_DateTime|NULL    $date_expires   object if the date is set or null if there is no date.
         *  @param  WC_Coupon           $coupon         Coupon object
         *  @return WC_DateTime|NULL    object if the date is set or null if there is no date.  
         */
        public static function store_credit_expiry_for_gift_cards_plugin($date_expires, $coupon)
        {
            $expiry = Wt_Smart_Coupon_Public::get_coupon_expires($coupon);
            $datetime = null;

            if($expiry && is_int($expiry))
            {
                $datetime = new WC_DateTime("@{$expiry}", new DateTimeZone(wc_timezone_string()));
            }

            return $datetime;
        }



        /**
         *  Get category ids of a product
         * 
         * @since  2.0.8
         * @param  int      $product_id     Product ID.
         * @return int[]
         */
        public static function get_product_cat_ids($product_id)
        {
            if(apply_filters('wt_sc_product_categories_with_ancestors', false, $product_id))
            {
                return wc_get_product_cat_ids($product_id);
            }else
            {
                return wc_get_product_term_ids($product_id, 'product_cat');
            }
        }
        
        
        /**
         *  Checks the current page is a valid order page(HPOS and NON HPOS) and also the existence of the given meta
         *  
         *  @since  2.1.0
         *  @param  string   $meta_key   Order meta key to check
         *  @return string   Order screen id. Empty string for non valid order page.
         */
        public static function is_valid_order_to_show_coupons_metabox($meta_key)
        {
            global $post, $theorder;
            
            /* Non HPOS */
            if(
                is_object($post) && property_exists($post, 'post_type') && property_exists($post, 'ID') 
                && 'shop_order' === $post->post_type && !empty(get_post_meta($post->ID, $meta_key, true))
            )
            {
                return 'shop_order'; //valid NON HPOS order page with order coupons available
            }

            
            /* HPOS */
            if(
                is_object($theorder) && method_exists($theorder, 'get_id') 
                && !empty(self::get_order_meta($theorder->get_id(), $meta_key))
            )
            {
                return wc_get_page_screen_id('shop-order');
            }

            return '';
        }

        
        /**
         *  Prepare allowed HTML for wp_kses.
         *  
         *  @since  2.3.0
         *  @return array   $allowed_html   Allowed HTML array
         */
        public static function get_allowed_html() {
            $allowed_html = wp_kses_allowed_html( 'post' );

            $new_allowed_html = array(
                'style'  => array(
                    'type' => true
                ),
            );

            $new_allowed_html = apply_filters( 'wt_sc_kses_allowed_html', $new_allowed_html );

            if ( ! empty( $new_allowed_html ) ) {
                foreach ( $new_allowed_html as $tag => $attributes ) {
                    if ( ! empty( $attributes ) && array_key_exists( $tag, $allowed_html ) ) {
                        $allowed_html[ $tag ] = array_merge( $allowed_html[ $tag ], $attributes );
                    } else {
                        $allowed_html[ $tag ] = $attributes;
                    }
                }
            }

            return $allowed_html;
        }


        /**
         *  Function to determine whether the coupon has been activated.
         *  In some cases the coupon was published but need to be activated to use. For example generated coupons.
         * 
         *  @since  2.3.0
         *  @param  WC_Coupon   $coupon     Coupon object.
         *  @return bool        Is actiavted or not.
         */
        public static function is_activated_coupon( $coupon ) {

            /**
             *  Is the current coupon is actiavted for use.
             *  
             *  @since  2.3.0
             *  @param  bool        Is actiavted or not.   
             *  @param  WC_Coupon   $coupon     Coupon object.   
             */
            return (bool) apply_filters( 'wbte_sc_is_coupon_activated', true, $coupon );
        }

        /**
         *  Change coupon display in lookup table when 'wbte_sc_after_coupon_generated_meta_added' triggered.
         * 
         * Scenario: Make generated gift coupon display (cart/my_account/checkout) to false in lookup table. Also make these coupons to display as per data in postmeta after giftcoupons activated. Function 'check_and_update_coupon_lookup_table' will check did coupon activated or not.
         * 
         *  @since  2.4.0
         *  @param  int   $coupon_id     Coupon id.
         */
        public function update_coupon_display_after_meta_updated( $coupon_id ){
            if( 'shop_coupon' === get_post_type( $coupon_id ) && get_post_meta( $coupon_id, '_wbte_sc_generated_gift_coupon', true ) ){
                $this->check_and_update_coupon_lookup_table( $coupon_id, false, '_wc_make_coupon_available' );
            }
        }

        /**
         * Delete coupon row from coupon lookup table when coupon permenantly deleted.
         * 
         * @since 2.4.3
         * @param int       $post_id Post ID of the deleted coupon.
         * @param object    $post    Post object of the deleted coupon.
         */
        public static function coupon_delete_from_lookup_table_when_deleted( $post_id, $post )
        {
            if( 'shop_coupon' === get_post_type( $post ) )
            {
                global $wpdb;
                $lookup_tb = Wt_Smart_Coupon::get_lookup_table_name();

                if( Wt_Smart_Coupon::is_table_exists( $lookup_tb ) )  
                {
                    $wpdb->delete( $lookup_tb, array( 'coupon_id' => $post_id ), array( '%d' ) );
                }
            }
        }

        /**
         * Get the list of BOGO restricted pages.
         * Restricted pages can be altered using the filter `wbte_sc_bogo_coupon_type_restricted_pages`.
         * @since 3.0.0
         * 
         * @return array Array of BOGO restricted pages.
         */
        public static function bogo_restricted_pages(){
            $restricted_pages = array( 'wt-smart-coupon-for-woo_bulk_generate', 'wt-smart-coupon-generate' );

		    return apply_filters( 'wbte_sc_bogo_coupon_type_restricted_pages', $restricted_pages );
        }

         /**
         *  Trigger 'after_wt_smart_coupon_for_woocommerce_is_activated' hook by comparing version in option
         *  
         *  @since 2.4.0
         *  @since 3.1.0 Moved from gift_coupon module to common module.
         */
        public static function check_and_trigger_activation_action_hook(){
            
            if( !get_option( 'wbte_sc_activation_hook_version' ) || version_compare( get_option( 'wbte_sc_activation_hook_version' ), WEBTOFFEE_SMARTCOUPON_VERSION, '<' ) ){
                do_action( 'after_wt_smart_coupon_for_woocommerce_is_activated' );
                update_option( 'wbte_sc_activation_hook_version', WEBTOFFEE_SMARTCOUPON_VERSION );		
            }
        }

        
        /**
         *  Use translation files from wp-content/languages/plugins/wt-smart-coupon-pro/
         *  Since 3.2.0 translation files are fetched from server instead of adding all language files in plugin.
         * 
         *  @since 3.2.0
         */
        public static function use_translation_files_from_wp_content_languages(){
            $plugin_dir = WP_CONTENT_DIR . '/languages/plugins/wt-smart-coupon-pro/';
            $plugin_text_domain = 'wt-smart-coupons-for-woocommerce-pro';

            $locale = get_locale();
            $mo_file_path = "{$plugin_dir}/{$plugin_text_domain}-{$locale}.mo";

            if( file_exists( $mo_file_path ) ){
                load_textdomain( $plugin_text_domain, $mo_file_path );
            }else{
                load_plugin_textdomain( $plugin_text_domain, false, dirname( WT_SMARTCOUPON_BASE_NAME ) . '/languages/' );
            }
        }
    }
}