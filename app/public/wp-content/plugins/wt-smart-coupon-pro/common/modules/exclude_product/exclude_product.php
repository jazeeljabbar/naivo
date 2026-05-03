<?php
/**
 * Exclude Product from coupon admin/public section
 *
 * @link       
 * @since 2.0.1     
 *
 * @package  Wt_Smart_Coupon
 */
if (!defined('ABSPATH')) {
    exit;
}
if( ! class_exists ( 'Wt_Smart_Coupon_Exclude_Product' ) ) {

	class Wt_Smart_Coupon_Exclude_Product
    {
        public $module_base='exclude_product';
        public $module_id='';
        public static $module_id_static='';
        private static $instance = null;
        public static $coupon_color_config=array();
        public function __construct()
        {
            $this->module_id=Wt_Smart_Coupon::get_module_id($this->module_base);
            self::$module_id_static=$this->module_id;

            add_filter('wt_sc_module_default_settings', array($this, 'default_settings'), 10, 2); 
            
            /**
             *  To share excluded products ids
             *  Any plugin/codes use apply_filter for the below filter will get the product ids. 
             *  Using in our Gift cards plugin and store credit module.
             * 
             *  @since 2.0.7
             */
            add_filter('wt_sc_store_credit_disabled_products', array($this, 'get_store_credit_disabled_products'), 10, 1); 
            
        }

        /**
         * Get Instance
         * @since 1.3.5
         */
        public static function get_instance()
        {
            if(self::$instance==null)
            {
                self::$instance=new Wt_Smart_Coupon_Exclude_Product();
            }
            return self::$instance;
        }

        /**
         *  Default settings
         *  @since 1.3.5
        */
        public function default_settings($settings, $base_id)
        {
            if($base_id!=$this->module_id)
            {
                return $settings;
            }

            self::migrate_settings(); /* migrate old settings. If exists */
            
            return array(
                'disabled_products' => array(),
                'disabled_store_credits'  => array()
            );  
        }

        /**
         *  Migrate old settings, If exists
         */
        protected static function migrate_settings()
        {
            $smart_coupon_option = get_option('wt_smart_coupon_options');
            if(isset($smart_coupon_option['exclude_from_coupons']) && !empty($smart_coupon_option['exclude_from_coupons'])) /* old data exists */
            {
                Wt_Smart_Coupon::update_settings($smart_coupon_option['exclude_from_coupons'], self::$module_id_static);
                
                //remove old option
                unset($smart_coupon_option['exclude_from_coupons']);
                update_option('wt_smart_coupon_options', $smart_coupon_option);
            }
        }

        public function get_current_settings()
        {
            return Wt_Smart_Coupon::get_settings(self::$module_id_static);
        }
        

        /**
         * Update disable product options
         * @since 2.0.1
         */
        public function set_disabled_products( $products = array() )
        {         
            Wt_Smart_Coupon::update_option('disabled_products', $products, self::$module_id_static);       
        }

        /**
         * Update disable product options
         * @since 2.0.1
         */
        public function set_disabled_store_credit( $products = array() )
        {
            Wt_Smart_Coupon::update_option('disabled_store_credits', $products, self::$module_id_static);
        }

        /**
         *  Get store credit disabled product ids.
         *  This method is also using to share data via filter `wt_sc_store_credit_disabled_products`. This is called in `Gift cards` plugin.
         * 
         *  @since 2.0.7
         *  @param int[] Product Ids, This function is a callback function for `wt_sc_store_credit_disabled_products`
         *  @return int[] Product Ids
         */
        public function get_store_credit_disabled_products($product_ids = array())
        {
            return Wt_Smart_Coupon::get_option('disabled_store_credits', self::$module_id_static);
        }
    }
    Wt_Smart_Coupon_Exclude_Product::get_instance();
}