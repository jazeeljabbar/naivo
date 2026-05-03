<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

if( ! class_exists ( 'WT_MyAccount_SmartCoupon' ) ) {
    class WT_MyAccount_SmartCoupon {

       
        public static $endpoint;

        protected $endpoint_title;

        public function __construct() {

            add_action( 'init', array( $this, 'init' ) );

            // Actions used to insert a new endpoint in the WordPress.
            add_action('init', array($this, 'add_endpoints'));
            add_filter('query_vars', array($this, 'add_query_vars'), 0);

            // Change the My Accout page title.
            add_filter('the_title', array($this, 'endpoint_title'));

            // Insering your new tab/page into the My Account page.
            add_filter('woocommerce_account_menu_items', array($this, 'wt_smartcoupon_menu'));
            
            add_action( 'after_switch_theme', array( $this, 'wt_custom_flush_rewrite_rules') );
        }

        /**
         * Initialize the endpoint and title.
         * 
         * @since 3.1.0 Moved from __construct to init to avoid issues with translating text before init.
         */
        public function init() {
            $wt_coupon_general_settings = Wt_Smart_Coupon::get_settings();

            $end_point = empty( $wt_coupon_general_settings['wt_account_endpoint'] ) ? 'wt-smart-coupon' : sanitize_title( $wt_coupon_general_settings['wt_account_endpoint'] );
            $this->endpoint_title = empty( $wt_coupon_general_settings['wt_endpoint_title'] ) ? __( 'My Coupons', 'wt-smart-coupons-for-woocommerce-pro' ) : $wt_coupon_general_settings['wt_endpoint_title'];

            self::$endpoint  = $end_point;

            add_action( 'woocommerce_account_' . self::$endpoint . '_endpoint', array( $this, 'endpoint_content' ) );
        }
        
        /**
         * Flush rewrite rules on plugin activation.
         */
        function wt_custom_flush_rewrite_rules() {
            flush_rewrite_rules();
        }
        public function add_endpoints() {
            add_rewrite_endpoint(self::$endpoint, EP_ROOT | EP_PAGES);
            Wt_Smart_Coupon::wt_smartcoupon_check_if_flushed_rules();
        }

        public function add_query_vars($vars) {
           
            $vars[] = self::$endpoint;
            return $vars;
        }

        public function endpoint_title($title) {

            global $wp_query;

            $smartcoupon_title = $this->endpoint_title;
            $is_endpoint = isset($wp_query->query_vars[self::$endpoint]);
            if ($is_endpoint && !is_admin() && is_main_query() && in_the_loop() && is_account_page()) {
                $title = __($smartcoupon_title, 'wt-smart-coupons-for-woocommerce-pro');
                remove_filter('the_title', array($this, 'endpoint_title'));
            }
			
            return $title;
        }

        public function wt_smartcoupon_menu($items)
        {
            if( wc_string_to_bool( Wt_Smart_Coupon::get_option( 'wbte_sc_enable_coupons_page' ) ) ){
                $logout = null;

                if(isset($items['customer-logout']))
                {
                    $logout = $items['customer-logout'];
                    unset($items['customer-logout']);
                }
            
                $items[self::$endpoint] = __($this->endpoint_title, 'wt-smart-coupons-for-woocommerce-pro');

                if(!is_null($logout))
                {
                    $items['customer-logout'] = $logout;
                }
            }

            return $items;
        }

        public function endpoint_content() {

            $params = array();
            wc_get_template('myaccount/my-account-coupon-view.php', $params, '', WT_SMARTCOUPON_MAIN_PATH. 'public/templates/');
        }

        public static function install() {
            flush_rewrite_rules();
        }

    }
}
new WT_MyAccount_SmartCoupon();