<?php
/**
 * Store credit admin/public section
 *
 * @link       
 * @since 2.0.0     
 *
 * @package  Wt_Smart_Coupon
 */
if (!defined('ABSPATH')) {
    exit;
}
if( ! class_exists ( 'Wt_Smart_Coupon_Store_Credit' ) ) {

	class Wt_Smart_Coupon_Store_Credit
    {
        public $module_base='store_credit';
        public $module_id='';
        public static $module_id_static='';
        private static $instance = null;
        public static $coupon_color_config=array();
        public static $is_extended=null;

        protected $gift_card_email_trigger_type = ''; /** @since 2.1.0 To add custom order notes */

        public function __construct()
        {
            $this->module_id=Wt_Smart_Coupon::get_module_id($this->module_base);
            self::$module_id_static=$this->module_id;

            /**
             *  We are keeping this file after revamp for giving compatibilty for old users who are using custom email template.
             */
            include_once(plugin_dir_path( __FILE__ ).'classes/class-smart-coupon-customisable-gift-card.php');

            add_filter('wt_sc_module_default_settings', array($this, 'default_settings'), 10, 2);

            add_filter('woocommerce_email_classes', array($this, 'add_store_credit_emails'), 11, 1);

            add_filter('woocommerce_coupon_discount_types', array($this, 'add_store_credit_discount_type'));

            add_action('woocommerce_order_status_changed', array($this, 'manage_credit_on_order_status_change'), 10, 4);

            /* Send store credit email on order status change. This is applicable for store credit purchase */
            add_action('woocommerce_order_status_changed', array($this,'send_coupon_email_on_status_change'), 10, 4);
        
        }

        /**
         * Get Instance
         * @since 2.0.0
         */
        public static function get_instance()
        {
            if(self::$instance==null)
            {
                self::$instance=new Wt_Smart_Coupon_Store_Credit();
            }
            return self::$instance;
        }
        public static function get_template_location()
        {
            return plugin_dir_url(__FILE__).'assets/images/';
        }
        public static function get_custom_templates()
        {
            return Wt_Smart_Coupon::get_option('custom_gift_card_template', self::$module_id_static);
        }
        public static function get_default_gift_card_templates()
        {
            $base_url=self::get_template_location();

            $design_categories=array(
                'general'       =>__('General', 'wt-smart-coupons-for-woocommerce-pro'),
                'birthday'      =>__('Birthday', 'wt-smart-coupons-for-woocommerce-pro'),
                'new_year'      =>__('New year', 'wt-smart-coupons-for-woocommerce-pro'),
                'anniversary'   =>__('Anniversary', 'wt-smart-coupons-for-woocommerce-pro'),
                'christmas'     =>__('Christmas', 'wt-smart-coupons-for-woocommerce-pro'),
            );


            $design_images = array(
                'general'       => array( 
                    'image_url'         => esc_url($base_url.'general-gift.jpg'),
                    'top_bg_color'      => '#f5a640',
                    'bottom_bg_color'   => '#f5a640',                  
                    'category'          => $design_categories['general'],
                ),
                'general_2'       => array( 
                    'image_url'         => esc_url($base_url.'gift-card-2.png'),
                    'top_bg_color'      => '#a6080b',
                    'bottom_bg_color'   => '#a6080b',                  
                    'category'          => $design_categories['general'],
                ),
                'general_3'       => array( 
                    'image_url'         => esc_url($base_url.'gift-card-3.png'),
                    'top_bg_color'      => '#2e2621',
                    'bottom_bg_color'   => '#2e2621',                  
                    'category'          => $design_categories['general'],
                ),
                'general_4'       => array( 
                    'image_url'         => esc_url($base_url.'gift-card-4.png'),
                    'top_bg_color'      => '#c7bba4',
                    'bottom_bg_color'   => '#c7bba4',                  
                    'category'          => $design_categories['general'],
                ),             
                'happy_birthday' => array(
                    'image_url'         => esc_url($base_url.'happy-bdy.jpg'),
                    'top_bg_color'      => '#373a9e',
                    'bottom_bg_color'   => '#373a9e',
                    'category'          => $design_categories['birthday'],
                ),
                'happy_birthday_2' => array(
                    'image_url'         => esc_url($base_url.'happy-birthday-2.png'),
                    'top_bg_color'      => '#00b3f0',
                    'bottom_bg_color'   => '#00b3f0',
                    'category'          => $design_categories['birthday'],
                ),
                'happy_birthday_3' => array(
                    'image_url'         => esc_url($base_url.'happy-birthday-3.png'),
                    'top_bg_color'      => '#d19c26',
                    'bottom_bg_color'   => '#d19c26',
                    'category'          => $design_categories['birthday'],
                ),
                'happy_birthday_4' => array(
                    'image_url'         => esc_url($base_url.'happy-birthday-4.png'),
                    'top_bg_color'      => '#c43784',
                    'bottom_bg_color'   => '#ff6cb5',
                    'category'          => $design_categories['birthday'],
                ),
                'happy_birthday_5' => array(
                    'image_url'         => esc_url($base_url.'happy-birthday-5.png'),
                    'top_bg_color'      => '#f372a4',
                    'bottom_bg_color'   => '#f372a4',
                    'category'          => $design_categories['birthday'],
                ),
                'happy_birthday_6' => array(
                    'image_url'         => esc_url($base_url.'happy-birthday-6.png'),
                    'top_bg_color'      => '#5010c7',
                    'bottom_bg_color'   => '#5010c7',
                    'category'          => $design_categories['birthday'],
                ),
                'happy_birthday_7' => array(
                    'image_url'         => esc_url($base_url.'happy-birthday-7.png'),
                    'top_bg_color'      => '#eaaeae',
                    'bottom_bg_color'   => '#eaaeae',
                    'category'          => $design_categories['birthday'],
                ),
                'new_year'      => array( 
                    'image_url'         => esc_url($base_url.'new-year.jpg'),
                    'top_bg_color'      => '#e84353',
                    'bottom_bg_color'   => '#e84353',
                    'category'          => $design_categories['new_year'],
                ),
                'new_year_2'      => array( 
                    'image_url'         => esc_url($base_url.'happy-new-year-2.png'),
                    'top_bg_color'      => '#063eb7',
                    'bottom_bg_color'   => '#063eb7',
                    'category'          => $design_categories['new_year'],
                ),
                'new_year_3'      => array( 
                    'image_url'         => esc_url($base_url.'happy-new-year-3.png'),
                    'top_bg_color'      => '#d4c2b8',
                    'bottom_bg_color'   => '#d4c2b8',
                    'category'          => $design_categories['new_year'],
                ),
                'new_year_4'      => array( 
                    'image_url'         => esc_url($base_url.'happy-new-year-4.png'),
                    'top_bg_color'      => '#2f200e',
                    'bottom_bg_color'   => '#2f200e',
                    'category'          => $design_categories['new_year'],
                ),
                'anniversary'   => array(
                    'image_url'         => esc_url($base_url.'anniversary.jpg'),
                    'top_bg_color'      => '#5e2b92',
                    'bottom_bg_color'   => '#5e2b92',
                    'category'          => $design_categories['anniversary'],
                ),
                'anniversary_2'   => array(
                    'image_url'         => esc_url($base_url.'anniversary-2.png'),
                    'top_bg_color'      => '#bca778',
                    'bottom_bg_color'   => '#bca778',
                    'category'          => $design_categories['anniversary'],
                ),
                'anniversary_3'   => array(
                    'image_url'         => esc_url($base_url.'anniversary-3.png'),
                    'top_bg_color'      => '#023d88',
                    'bottom_bg_color'   => '#023d88',
                    'category'          => $design_categories['anniversary'],
                ),
                'anniversary_4'   => array(
                    'image_url'         => esc_url($base_url.'anniversary-4.png'),
                    'top_bg_color'      => '#dd5879',
                    'bottom_bg_color'   => '#dd5879',
                    'category'          => $design_categories['anniversary'],
                ),
                'christmas'     => array( 
                    'image_url'         => esc_url($base_url.'christmas.jpg'),
                    'top_bg_color'      => '#357933',
                    'bottom_bg_color'   => '#357933',
                    'category'          => $design_categories['christmas'],
                ),
                'christmas_2'     => array( 
                    'image_url'         => esc_url($base_url.'merry-christmas-2.png'),
                    'top_bg_color'      => '#6b0202',
                    'bottom_bg_color'   => '#6b0202',
                    'category'          => $design_categories['christmas'],
                ),
                'christmas_3'     => array( 
                    'image_url'         => esc_url($base_url.'merry-christmas-3.png'),
                    'top_bg_color'      => '#003938',
                    'bottom_bg_color'   => '#003938',
                    'category'          => $design_categories['christmas'],
                ),
                'christmas_4'     => array( 
                    'image_url'         => esc_url($base_url.'merry-christmas-4.png'),
                    'top_bg_color'      => '#2e2e2e',
                    'bottom_bg_color'   => '#2e2e2e',
                    'category'          => $design_categories['christmas'],
                ),
            );
    
            return $design_images;
        }

        /**
         *  Get all available gift card templates
         */
        public static function get_gift_card_templates()
        {
            $design_images=self::get_default_gift_card_templates();
            $custom_templates=self::get_custom_templates();
            
            foreach($custom_templates as $template_k=>$template_v) 
            {
                $template_v['category']=__($template_v['category'], 'wt-smart-coupons-for-woocommerce-pro'); /* to add translation */
                $design_images[$template_k]=$template_v; /* add to main list */
            }

            return apply_filters('wt_smart_coupon_store_credit_designs', $design_images);
        }

        public static function get_gift_card_template($template_slug)
        {
            $templates = self::get_gift_card_templates();
            $templates = (is_array($templates) ? $templates : array());

            return (isset($templates[$template_slug]) ? $templates[$template_slug] : (isset($templates['general']) ? $templates['general'] : array()));
        }

        public static function get_hidden_templates()
        {
            return Wt_Smart_Coupon::get_option('gift_card_template_to_hide', self::$module_id_static);
        }

        public static function is_display_templates_by_category()
        {
            return Wt_Smart_Coupon::get_option('display_templates_by_category', self::$module_id_static);
        }

        public static function get_template_category_from_template_list($templates)
        {
            return is_array($templates) ? array_column($templates, 'category') : array();
        }

        /**
         * Register store credit coupon type
         * @since 2.0.0
         */
        public function add_store_credit_discount_type($discount_types)
        {
            $discount_types['store_credit'] = __('Store credit', 'wt-smart-coupons-for-woocommerce-pro');
            return $discount_types;
        }

        /**
         *  Register Store credit email class to WC email
         *  @since 2.0.0
         */
        public function add_store_credit_emails($email_classes)
        {
            include_once(plugin_dir_path( __FILE__ ).'classes/class-wt-smart-coupon-store-credit-email.php');
            $email_classes['WT_smart_Coupon_Store_Credit_Email'] = new WT_Smart_Coupon_Store_Credit_Email();
            return $email_classes;
        }

        /**
         *  Default settings
         *  @since 2.0.0
         */
        public function default_settings($settings, $base_id)
        {
            if($base_id!=$this->module_id)
            {
                return $settings;
            }
            $default_settings=array(
                'apply_store_credit_before_tax'         => false,
                'store_credit_purchase_product'         => '',
                'minimum_store_credit_purchase'         => '',
                'maximum_store_credit_purchase'         => '',
                'send_purchased_credit_on_order_status' => 'processing',
                'store_credit_coupon_prefix'            => '',
                'store_credit_coupon_suffix'            => '',
                'store_credit_coupon_length'            => 12,
                'enabled_extended_store_credit'         => true,
                'make_coupon_individual_use_only'       => true,
                'display_option'                        => 'denominations_only',
                'denominations'                         => '',
                
                /* store credit email params */              
                'wt_sc_send_email_caption'              => __('Have a nice day', 'wt-smart-coupons-for-woocommerce-pro'),
                'wt_sc_send_email_description'          => __('A gift awaiting you','wt-smart-coupons-for-woocommerce-pro'),

                /* gift card template */
                'custom_gift_card_template'             => array(), /* custom added templates */
                'gift_card_template_to_hide'            => array(), /* templates not to show in front end */
                'display_templates_by_category'         => true, /* Display templates by category in front end Gift card purchase page */
            );

            self::migrate_settings($default_settings); /* migrate old settings. If exists */
            
            return $default_settings;  
        }

        /**
         *  Migrate old settings, If exists
         */
        protected static function migrate_settings($default_settings)
        {
            $smart_coupon_option = get_option( 'wt_smart_coupon_options' );
            if(isset($smart_coupon_option['wt_store_credit_settings']) && !empty($smart_coupon_option['wt_store_credit_settings'])) /* old data exists */
            {           
                $old_settings=$smart_coupon_option['wt_store_credit_settings'];
                
                /* extended store credit */
                $old_settings['enabled_extended_store_credit']=(isset($smart_coupon_option['enabled_extended_store_credit']) ? $smart_coupon_option['enabled_extended_store_credit'] : $default_settings['enabled_extended_store_credit']);

                /* denomination settings */
                $denomination_settings=(isset($smart_coupon_option['store_credit_denominaton_settings']) ? $smart_coupon_option['store_credit_denominaton_settings'] : array());
                $old_settings['display_option']=(isset($denomination_settings['display_option']) ? $denomination_settings['display_option'] : $default_settings['display_option']);
                $old_settings['denominations']=(isset($denomination_settings['denominations']) ? $denomination_settings['denominations'] : $default_settings['denominations']);


                Wt_Smart_Coupon::update_settings($old_settings, self::$module_id_static);

                //remove old option
                unset($smart_coupon_option['wt_store_credit_settings']);
                unset($smart_coupon_option['enabled_extended_store_credit']);
                unset($smart_coupon_option['store_credit_denominaton_settings']);
                update_option('wt_smart_coupon_options', $smart_coupon_option);
            }
        }

        public static function get_store_credit_settings()
        {
            return Wt_Smart_Coupon::get_settings(self::$module_id_static);
        }

        public static function is_extended_store_credit_enabled()
        {
            if(self::$is_extended==null)
            {
                self::$is_extended=Wt_Smart_Coupon::get_option('enabled_extended_store_credit', self::$module_id_static); 
            }
            return self::$is_extended;
        }

        public static function get_associated_product()
        {
            return Wt_Smart_Coupon::get_option('store_credit_purchase_product', self::$module_id_static);
        }

        /**
         * Is current coupon is store credit.
         */
        public static function is_store_credit($coupon)
        {
            return $coupon->is_type('store_credit');
        }

        /**
         * Get caption for gift card
         * @since 2.0.0
         */
        public static function get_gift_card_caption($template)
        {
            return apply_filters('wt_smart_coupon_gift_card_caption', __(Wt_Smart_Coupon::get_option('wt_sc_send_email_caption', self::$module_id_static), 'wt-smart-coupons-for-woocommerce-pro') , $template);
        }
        
        /**
         * Get message for gift card
         * @since 2.0.0
         */
        public static function get_gift_card_message($template)
        {
            return apply_filters('wt_smart_coupon_gift_card_message', Wt_Smart_Coupon::get_option('wt_sc_send_email_description', self::$module_id_static), $template);
        }

        /**
         * Whether store credit apply before shipping and tax
         */
        public function apply_before_tax()
        {
            return Wt_Smart_Coupon::get_option('apply_store_credit_before_tax', self::$module_id_static);
        }

        /**
         * Get the success statuses for an order
         */
        public function wt_success_order_status()
        {
            $success_status = array(
                'pending'       => 'Pending',
                'on-hold'       => 'On hold',
                'processing'    => 'Processing',
                'completed'     => 'Completed'
            );
            return apply_filters( 'wt_smart_coupon_success_order_statuses', $success_status );
        }

        /**
         * Get the failed statuses for an order
         */
        public function wt_failed_order_status()
        {
            $failed_status = array(
                'refunded'      => 'Refunded',
                'cancelled'     => 'Cancelled',
                'failed'        => 'Failed'

            );
            return apply_filters( 'wt_smart_coupon_failed_order_statuses', $failed_status );
        }

        /**
         * Manage the credit refund on changing order status.
         *
         */
        public function manage_credit_on_order_status_change($order_id, $old_status, $new_status, $order)
        {           
            $success_order_statuses = array_keys($this->wt_success_order_status());
            $failed_order_statuses  = array_keys($this->wt_failed_order_status());
            if(in_array($old_status, $success_order_statuses) && in_array($new_status, $failed_order_statuses))
            {
                $this->reimburse_credit_value($order);
                $this->remove_store_credit_from_order($order);
            }
        }

        /**
        * Remove created store credit W.R.T order if an order fails or refunded
        * 
        * @since    1.3.3
        * @since    2.0.8   Added HPOS Compatibility
        * @access   public
        * @param    WC_Order Order object
        */
        public function remove_store_credit_from_order($order)
        {
            if(!is_object( $order) || ! is_a($order, 'WC_Order'))
            {
                return false;
            }

            $order_id   =   $order->get_id();        
            $coupon_attached = Wt_Smart_Coupon_Common::get_order_meta($order_id, 'wt_credit_coupon_template_details');
            
            if(empty($coupon_attached))
            {
                $coupon_attached = Wt_Smart_Coupon_Common::get_order_meta($order_id, 'wt_credit_coupons');
            }

            $coupons = maybe_unserialize($coupon_attached);

            if(!empty($coupons) && is_array($coupons))
            {              
                foreach($coupons as $coupon_item )
                {
                    $coupon_id = ( isset( $coupon_item['coupon_id'] ) ? $coupon_item['coupon_id'] : '' ); 
                    if( $coupon_id )
                    {
                        wp_delete_post($coupon_id);
                    }
                }

                Wt_Smart_Coupon_Common::delete_order_meta($order, 'wt_credit_coupon_template_details');
                Wt_Smart_Coupon_Common::delete_order_meta($order, 'wt_credit_coupons');
            }
        }

        /**
        * Reimburse Credit amount on for an order ( On failed, refund etc. )
        */
        public function reimburse_credit_value($order)
        {
            if( !is_object( $order) || ! is_a( $order,'WC_Order')  ) {
                return false;
            }

            $credit_used = $credit_amount = $this->get_credit_used_for_order( $order );
            $update = false;
            if( $credit_amount ) {
                foreach( $credit_amount as $coupon_code => $amount ) {

                    $coupon         = new WC_Coupon( $coupon_code );
                    if( ! is_object( $coupon ) || ! is_a( $coupon,'WC_Coupon') ) {
                        continue;
                    }
                    $coupon_id      = $coupon->get_id();
                    $discount_type  = $coupon->get_discount_type();
                    if( !$coupon_id || $discount_type != 'store_credit'  ) {
                        continue;
                    }

                    
                    $current_amount = $coupon->get_amount();
                    $usage_count    = $coupon->get_usage_count();
                    $usage_count    = ( $usage_count > 0 )? $usage_count - 1 : 0 ;
                    $new_amount     = $amount + $current_amount;

                    $coupon->set_usage_count( $usage_count );
                    $coupon->set_amount( $new_amount );
                    $coupon->save();

                    $credit_history = get_post_meta( $coupon_id , 'wt_credit_history',true);
                    $credit_history_this_order = array(
                        'order'             =>  $order->get_id(),
                        'previous_credit'   =>  $current_amount,
                        'updated_credit'    =>  $new_amount,
                        'credit_used'       =>  '-',
                        'reimbursed'        =>   $amount,
                        'comments'          =>  __( 'Reimburse credit value' , 'wt-smart-coupons-for-woocommerce-pro')
                    );
                    $time_stamp = current_time( 'timestamp' );
                    $credit_history[ "'".$time_stamp."'" ] = $credit_history_this_order;
                    
                    update_post_meta( $coupon_id, 'wt_credit_history', $credit_history );
                    unset( $credit_used[$coupon_code] );
                    $update = true;
                }
            }
            if( $update ) {
                $this->update_credit_used_for_order( $order, $credit_used );
            }
        }

        /**
         * Get credit used for an order
         * 
         * @since 2.0.8   Added HPOS Compatibility
         */
        public function get_credit_used_for_order($order)
        {
            $order_id = self::get_order_id($order);

            if(!$order_id){ return false; }

            $credit_amount = Wt_Smart_Coupon_Common::get_order_meta($order_id, 'wt_store_credit_used'); 

            if(is_array($credit_amount ) && !empty($credit_amount))
            {
                return $credit_amount;
            }

            return false;
        }

        /**
        * Get total credit used for an order
        */
        public function get_total_credit_used_for_an_order($order)
        {
            $credit_used = $this->get_credit_used_for_order($order);
            $credit = 0;
            
            if($credit_used && is_array($credit_used))
            {
                $credit = array_sum($credit_used);
            }

            return $credit;
        }
        
        /**
        * Update credit used for an order
        * 
        * @since 2.0.8   Added HPOS Compatibility
        */
        function update_credit_used_for_order($order, $credit_used)
        {
            $order_id = self::get_order_id($order);

            if(!$order_id){ return false; }

            if(!is_array($credit_used) || empty($credit_used))
            {
                Wt_Smart_Coupon_Common::delete_order_meta($order_id, 'wt_store_credit_used');
                return;
            }

            Wt_Smart_Coupon_Common::update_order_meta($order_id, 'wt_store_credit_used', $credit_used);
        }

        public static function get_order_id($order)
        {
            if(is_int($order))
            {
                $order_id = $order;
            }else
            {
                if(!is_object($order) || !is_a($order,'WC_Order'))
                {
                    return false;
                }

                $order_id = $order->get_id();
            }

            return $order_id;
        }

        public function create_store_credit_coupon($credit_value, $prefix, $suffix, $coupon_length, $description="")
        {
            $general_settings_options = Wt_Smart_Coupon::get_settings();
            if( '' === $prefix ) {
                $prefix         = isset( $general_settings_options['wt_coupon_prefix'] ) ? $general_settings_options['wt_coupon_prefix'] : '';
            }
            if( '' === $suffix ) {
                $suffix         = isset( $general_settings_options['wt_coupon_suffix'] ) ? $general_settings_options['wt_coupon_suffix'] : '';
            }
            if( '' === $coupon_length ) {
                $coupon_length  = ( isset( $general_settings_options['wt_coupon_length'] ) && '' !== $general_settings_options['wt_coupon_length'] ) ? $general_settings_options['wt_coupon_length'] : 12 ;
            }

            /* generate random coupon code */
            $coupon_code=Wt_Smart_Coupon_Admin::generate_random_coupon($prefix, $suffix, $coupon_length);
            
            /* create a coupon */
            $coupon_args = array(
                'post_title'    => strtolower($coupon_code),
                'post_content'  => '',
                'post_status'   => 'publish',
                'post_author'   => 1,
                'post_type'     => 'shop_coupon'
            );

            $coupon_id = wp_insert_post($coupon_args);
            update_post_meta($coupon_id, 'wt_auto_generated_store_credit_coupon', true);

            $coupon_obj = new WC_Coupon($coupon_id);
            $coupon_obj->set_amount($credit_value);
            $coupon_obj->set_discount_type('store_credit');
            $coupon_obj->set_description($description);

            return array('coupon_id'=>$coupon_id, 'coupon_obj'=>$coupon_obj);
        }

        /**
         *  Check the generated coupon is send to customer/activated
         */
        public function is_generated_coupon_activated($coupon_id)
        {
            if(get_post_meta($coupon_id, '_wt_sc_send_the_generated_credit', true))
            {
                return true;
            }

            if(get_post_meta($coupon_id, '_wt_smart_coupon_credit_activated', true))
            {
                return true;
            }

            return false;
        }

        public static function get_order_status_for_gift_card_email($order)
        {
            $status = Wt_Smart_Coupon::get_option('send_purchased_credit_on_order_status', self::$module_id_static);
            $status_arr = is_array($status) ? $status : array($status);

            return apply_filters('wt_sc_alter_purchased_credit_sending_status', $status_arr, $order); 
        }

        /**
         *  Send credit coupon email to the customer on changing order status. This is applicable for store credit purchase
         * 
         *  @since 2.0.8   Added HPOS Compatibility
         */
        public function send_coupon_email_on_status_change($order_id, $old_status, $new_status, $order)
        {
            $coupon_attached = Wt_Smart_Coupon_Common::get_order_meta($order_id, 'wt_credit_coupons'); //coupons attached to the order
            
            if(!empty($coupon_attached)) //this order includes a store credit coupon
            {
                $status_arr = self::get_order_status_for_gift_card_email($order);
                
                if(in_array($new_status, $status_arr)) //status matched
                {
                    $order = new WC_Order($order_id);
                    $order_items = $order->get_items();

                    foreach($order_items as $order_item)
                    {
                        $coupons_generated = $order_item->get_meta('wt_credit_coupon_generated');
            
                        if(empty($coupons_generated) || !is_array($coupons_generated)) //not a gift cart order item
                        {
                            continue;
                        }

                        $coupon_template_details = $order_item->get_meta('wt_credit_coupon_template_details');
                        $coupon_template_details = (!empty($coupon_template_details) && is_array($coupon_template_details) ? $coupon_template_details : array());

                        foreach($coupons_generated as $generated_coupon)
                        {
                            $coupon_id = $generated_coupon['coupon_id'];
                            $coupon_obj = new WC_Coupon($coupon_id);

                            if(!$coupon_obj)
                            {
                                continue;
                            }

                            if(self::is_generated_coupon_activated($coupon_id)) //already send
                            {
                                continue;
                            }

                            $coupon_template_data = isset($coupon_template_details[$coupon_id]) && is_array($coupon_template_details[$coupon_id]) ? $coupon_template_details[$coupon_id] : array();

                            if(isset($coupon_template_data['wt_smart_coupon_schedule']) && (int) $coupon_template_data['wt_smart_coupon_schedule']>0)
                            {  
                                $credit_email_args = array(
                                    'send_to'   => (isset($coupon_template_data['wt_credit_coupon_send_to']) ? $coupon_template_data['wt_credit_coupon_send_to'] : ''),
                                    'coupon_id' => $coupon_id,
                                    'message'   => (isset($coupon_template_data['wt_credit_coupon_send_to_message']) ? $coupon_template_data['wt_credit_coupon_send_to_message'] : ''),
                                    'order_id'  => $order_id,
                                    'template'  => (isset($coupon_template_data['wt_smart_coupon_template_image']) ? $coupon_template_data['wt_smart_coupon_template_image'] : ''),
                                    'from_name' => (isset($coupon_template_data['wt_credit_coupon_from']) ? $coupon_template_data['wt_credit_coupon_from'] : ''),
                                    'extended'  => (isset($coupon_template_data['extended']) ? $coupon_template_data['extended'] : false),
                                );

                                if(as_has_scheduled_action('wt_send_coupon_email_as_per_schedule', $credit_email_args, 'wt-smart-coupon-store-credit')) //check schedule already exists, 
                                {
                                    continue;
                                }

                                $coupon_schedule = Wt_Smart_Coupon_Admin::wt_sc_get_date_prop(absint($coupon_template_data['wt_smart_coupon_schedule']))->getOffsetTimestamp();

                                if($coupon_schedule>time()) //future date, so schedule
                                {
                                    $credit_email_args['schedule'] = $coupon_schedule;
                                    do_action('wt_smart_coupon_schedule_credit', $credit_email_args);

                                }else
                                {
                                    //past date so send it now.
                                    $this->gift_card_email_trigger_type = 'status_reached'; /** @since 2.1.0 For customized order notes */
                                    $this->do_send_mail($order_id, $coupon_id);
                                }   

                            }else
                            {
                                //send it now
                                $this->gift_card_email_trigger_type = 'status_reached'; /** @since 2.1.0 For customized order notes */
                                $this->do_send_mail($order_id, $coupon_id);
                            }
                        }                          
                    }
                }
            }
        }

        /**
         *  Send store credit purchase email
         * 
         *  @since 2.0.8   Added HPOS Compatibility
         */
        public function do_send_mail($order_id, $coupon_id = 0, $force_send = false)
        {
            $coupons = Wt_Smart_Coupon_Common::get_order_meta($order_id, 'wt_credit_coupons');

            if(empty($coupons)) /* no attached coupons found. */
            {
                return;
            }

            if(empty(Wt_Smart_Coupon_Common::get_order_meta($order_id, 'wt_credit_coupon_send_to')))
            {
                return $this->send_store_credit_email_for_order($order_id, $coupon_id, $force_send); /* new version or old version with extended enabled */
            }else
            {
                return $this->send_store_credit_email_for_order_old($order_id, $coupon_id); /* this is for older versions below 2.0.0 */
            }
        }

        /**
         *  Send store credit email for non extended store credit orders.
         *  This is for version below 2.0.0
         *  
         *  @since 2.0.8   Added HPOS Compatibility
         */
        public function send_store_credit_email_for_order_old($order_id, $coupon_id = 0)
        {
            $coupons = Wt_Smart_Coupon_Common::get_order_meta($order_id, 'wt_credit_coupons' );
            $send_to = Wt_Smart_Coupon_Common::get_order_meta($order_id, 'wt_credit_coupon_send_to' );
            $message = Wt_Smart_Coupon_Common::get_order_meta($order_id, 'wt_credit_coupon_send_to_message' );
            $coupons = maybe_unserialize($coupons);
            $coupon_ids = array();
            
            if(!empty($coupons))
            {
                foreach($coupons as $coupon_item)
                {
                    if($coupon_id > 0  &&  $coupon_id != $coupon_item['coupon_id']) //send only for a specific coupon in the generated list
                    {
                        continue;
                    }

                    $coupon_ids[] = $coupon_item['coupon_id'];
                    update_post_meta($coupon_item['coupon_id'], '_wt_smart_coupon_credit_activated', true );
                }
            }
            
            $send_now = apply_filters('wt_send_credit_coupon_on_order_success_status', true, $order_id);

            if($send_now && !empty($coupon_ids))
            {
                $credit_email_args = array(
                    'send_to'   => $send_to,
                    'coupon_id' => $coupon_ids,
                    'message'   => $message,
                    'extended'  => false,
                );
                WC()->mailer();
                do_action('wt_send_store_credit_coupon_to_customer', $credit_email_args);
                return true;
            }
        } 

        /**
         *  Send store credit email for extended store credit orders.
         * 
         */
        public function send_store_credit_email_for_order($order_id, $coupon_id = 0, $force_send = false)
        {
            $email_send = false;
            $order = new WC_Order($order_id);
            $order_items = $order->get_items();

            foreach($order_items as $order_item)
            {
                $coupons_generated = $order_item->get_meta('wt_credit_coupon_generated');
                
                if(empty($coupons_generated) || !is_array($coupons_generated)) //not a gift cart order item
                {
                    continue;
                }

                $coupon_template_details = $order_item->get_meta('wt_credit_coupon_template_details');
                $coupon_template_details = (!empty($coupon_template_details) && is_array($coupon_template_details) ? $coupon_template_details : array());

                foreach($coupons_generated as $generated_coupon)
                {
                    $generated_coupon_id = $generated_coupon['coupon_id'];
                    
                    if($coupon_id > 0  &&  $coupon_id != $generated_coupon_id) //send only for a specific coupon in the generated list
                    {
                        continue;
                    }

                    if(!isset($coupon_template_details[$generated_coupon_id])) //coupon template details not exists
                    {
                        continue;
                    }

                    $coupon_obj = new WC_Coupon($generated_coupon_id);

                    if(!$coupon_obj) //coupon not exists
                    {
                        continue;
                    }

                    $coupon_template_data = $coupon_template_details[$generated_coupon_id];
                    $coupon_ids =   isset($coupon_template_data['coupon_id']) ? $coupon_template_data['coupon_id']  : '';
                    $template   =   isset($coupon_template_data['wt_smart_coupon_template_image'])? $coupon_template_data['wt_smart_coupon_template_image'] : '';
                    $extended   =   isset($coupon_template_data['extended'])? $coupon_template_data['extended'] : false ;

                    if(!$extended && $template!="") /* check older version and extended enabled. Compatibility for v2.0.0 */
                    {
                        $extended = true;
                    }

                    $send_now = apply_filters('wt_send_credit_coupon_on_order_success_status', true, $order_id, $coupon_template_data);

                    if($send_now && !empty($coupon_ids))
                    {
                        $credit_email_args = array(
                            'send_to'   => (isset($coupon_template_data['wt_credit_coupon_send_to']) ?  $coupon_template_data['wt_credit_coupon_send_to']: ''),
                            'coupon_id' => $coupon_ids,
                            'message'   => (isset($coupon_template_data['wt_credit_coupon_send_to_message']) ? $coupon_template_data['wt_credit_coupon_send_to_message'] : ''),
                            'order_id'  => $order_id,
                            'template'  => $template,
                            'from_name' => (isset($coupon_template_data['wt_credit_coupon_from']) ? $coupon_template_data['wt_credit_coupon_from'] : $order->get_billing_email()),                        
                            'extended'  => $extended,
                        );

                        if($force_send && isset($coupon_template_data['wt_smart_coupon_schedule']) && (int) $coupon_template_data['wt_smart_coupon_schedule']>0 && as_has_scheduled_action('wt_send_coupon_email_as_per_schedule', $credit_email_args, 'wt-smart-coupon-store-credit')) //check schedule exists
                        {
                            as_unschedule_action('wt_send_coupon_email_as_per_schedule', $credit_email_args, 'wt-smart-coupon-store-credit'); //unschedule
                        }
                        
                        WC()->mailer();
                        do_action('wt_send_store_credit_coupon_to_customer', $credit_email_args);
                        
                        $email_send=true;
                        $current_coupon_id = (is_array($coupon_ids) ? $coupon_ids[0] : $coupon_ids);
                        
                        update_post_meta($current_coupon_id, '_wt_smart_coupon_credit_activated', true);
                        update_post_meta($current_coupon_id, '_wt_sc_send_the_generated_credit', true);
                        update_post_meta($current_coupon_id, '_wt_sc_send_date_gmt', current_time('mysql', true)); /** @since 2.1.0 update the last sent time in GMT */


                        /**
                         *  Add customized order notes
                         * 
                         *  @since 2.1.0
                         */
                        $coupon_code = '<b>' . wc_sanitize_coupon_code($coupon_obj->get_code()) . '</b>';
                        $order_note_msg = '';

                        switch ($this->gift_card_email_trigger_type)
                        {
                            case 'schedule_reached':
                                $order_note_msg = sprintf(__('Gift card %s emailed to recipient on scheduled date.', 'wt-smart-coupons-for-woocommerce-pro'), $coupon_code); /* translators: %s coupon code. */
                                break;

                            case 'resend':
                                $order_note_msg = sprintf(__('Gift card %s has been resent to the recipient via the Resend button.', 'wt-smart-coupons-for-woocommerce-pro'), $coupon_code); /* translators: %s coupon code. */
                                break;

                            case 'force_send':
                                $order_note_msg = sprintf(__('Gift card %s emailed to recipient manually using the force sent button.', 'wt-smart-coupons-for-woocommerce-pro'), $coupon_code); /* translators: %s coupon code. */
                                break;

                            default: //applicable for `status_reached` and `send`
                                $order_note_msg = sprintf(__('Gift card %s emailed to recipient.', 'wt-smart-coupons-for-woocommerce-pro'), $coupon_code); /* translators: %s coupon code. */
                                break;
                        }

                        $order->add_order_note($order_note_msg); //add note


                    }
                }
            }

            return $email_send;
        }

        
        /**
         *  Get ids of products that are not allowed for store credit.
         *  
         *  @since 2.0.7
         *  @return int[] Product Ids
         */
        public static function get_store_credit_disabled_products()
        {
            return apply_filters('wt_sc_store_credit_disabled_products', array());
        }

    }
    Wt_Smart_Coupon_Store_Credit::get_instance();
}