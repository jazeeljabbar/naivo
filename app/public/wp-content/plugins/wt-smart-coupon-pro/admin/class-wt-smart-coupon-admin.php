<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://www.webtoffee.com
 * @since      1.0.0
 *
 * @package    Wt_Smart_Coupon
 * @subpackage Wt_Smart_Coupon/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wt_Smart_Coupon
 * @subpackage Wt_Smart_Coupon/admin
 * @author     WebToffee <info@webtoffee.com>
 */
if( ! class_exists ( 'Wt_Smart_Coupon_Admin' ) ) {
    class Wt_Smart_Coupon_Admin {

        private $plugin_name;
        private $version;

        private $options_before_saving = array(); /* To store the existing settings before saving the settings. Currently this value is used to alter the master coupon status */

        /*
         * module list, Module folder and main file must be same as that of module name
         * Please check the `register_modules` method for more details
         */
        public static $modules=array(
            'coupon_style',
            'coupon_banner',
            'bulk_generate',
            'import_coupon',
            'store_credit',
            'licence_manager',
            'url_coupon',
            'nth_order',
            'gift_coupon',
            'usage_limit', /** @since 2.1.0 Renamed from limit_max_discount */
            'auto_coupon',
            'exclude_product',
            'coupon_lifespan',
            'giveaway_product',
            'combo_coupon',
            'cart_abandonment',
            'signup_coupon',
            'coupon_shortcode',
            'duplicate_coupon',
            'coupon_restriction',
            'notifications', /** @since 2.0.8 */
            'checkout_options', /** @since 2.0.9 */
            'request_feature', /** @since 2.1.0 */
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

        public static $tooltip_arr=array();

        private static $instance = null;

        public static $master_coupon_meta_key = '_wt_sc_master_coupon'; //to identify current coupon is a master coupon

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
                self::$instance=new Wt_Smart_Coupon_Admin($plugin_name, $version);
            }

            return self::$instance;
        }


        /**
         * Smart coupon settings button on coupons page
         * @since 1.3.5
         */
        public function coupon_page_settings_button()
        {
            global $current_screen;
            if($current_screen->post_type!='shop_coupon')
            {
                return;
            }
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function($)
                {
                    jQuery('.page-title-action').after('<a href="<?php echo esc_attr(admin_url('admin.php?page='.WT_SC_PLUGIN_NAME));?>" class="page-title-action"><?php _e('Smart coupon settings', 'wt-smart-coupons-for-woocommerce-pro');?></a>');
                });
            </script>
            <?php
        }


        /**
        *   @since 1.3.5 
        *   Save admin settings and module settings ajax hook
        */
        public function save_settings()
        {
            $out=array(
                'status'=>false,
                'msg'=>__('Error', 'wt-smart-coupons-for-woocommerce-pro'),
            );

            $base=(isset($_POST['wt_sc_settings_base']) ? sanitize_text_field($_POST['wt_sc_settings_base']) : 'main');
            $base_id=($base=='main' ? '' : Wt_Smart_Coupon::get_module_id($base));
            if(Wt_Smart_Coupon_Security_Helper::check_write_access('smart_coupons', 'wt_smart_coupons_admin_nonce')) 
            {
                $the_options=Wt_Smart_Coupon::get_settings($base_id);

                do_action('wt_sc_intl_before_setting_update', $the_options, $base_id);
                
                //multi/ajax select, checkbox form fields array. (It will not return a $_POST val if it's value is empty so we need to set default value)
                $default_val_needed_fields=array(
                    
                    'only_display_cart_valid_coupons' => 'no',
                    'wbte_sc_enable_coupons_page'     => 'no',
                    'wbte_sc_coupons_page_additional_display' => array()

                ); //this is for plugin settings default. Modules can alter

                /* this is an internal filter */
                $default_val_needed_fields = apply_filters('wt_sc_intl_default_val_needed_fields', $default_val_needed_fields, $base_id);


                $default_settings = Wt_Smart_Coupon::default_settings($base_id);               
                
                foreach($the_options as $key => $value) 
                {
                    if(isset($_POST[$key]))
                    {
                        /* Caution !!! If the item value is an associative array then this checking will fail */ 
                        if(is_array($default_settings[$key])  && Wt_Smart_Coupon::is_assoc_arr($default_settings[$key])) /* maybe sub settings. */
                        {
                            foreach($default_settings[$key] as $sub_key=>$sub_value)
                            {
                                if(isset($_POST[$key][$sub_key]))
                                {
                                    $the_options[$key][$sub_key]=Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST[$key][$sub_key], 'text_arr'); /* only considering first level of sub settings, may be chance for array values so giving `text_arr` as sanitization input */
                                }else{
                                    
                                    if(array_key_exists($key, $default_val_needed_fields)) /* for multi/ajax select fields */
                                    {
                                        if(is_array($default_val_needed_fields[$key]) && array_key_exists($sub_key, $default_val_needed_fields[$key]))
                                        {
                                            if(isset($_POST[$key][$sub_key.'_hidden'])) /* this is multi/ajax select field */
                                            {
                                                $the_options[$key][$sub_key]=$default_val_needed_fields[$key][$sub_key];
                                            }
                                        }
                                    }
                                }
                            }

                        }else
                        {
                            $the_options[$key]=Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST[$key], 'text_arr');
                        }
                    }else
                    {
                        if(isset($default_val_needed_fields[$key]) && isset($_POST[$key.'_hidden'])) /* for multi/ajax select fields */ 
                        {
                            $the_options[$key] = $default_val_needed_fields[$key];
                        }
                    }
                }

                if(isset($_POST['wt_account_endpoint']))
                {
                    update_option('wt_sc_flush_rules', 'false', 'no');
                }
                
                Wt_Smart_Coupon::update_settings($the_options, $base_id);

                do_action('wt_sc_intl_after_setting_update', $the_options, $base_id);

                $out['status']=true;
                $out['msg']=__('Settings Updated', 'wt-smart-coupons-for-woocommerce-pro');
            }
            echo json_encode($out);
            exit();
        }

        protected function debug_save_sub($option_name, $mu_modules)
        {
            $wt_sc_modules=get_option($option_name);
            if($wt_sc_modules===false)
            {
                $wt_sc_modules=array();
            }
            if(isset($_POST[$option_name]))
            {
                $wt_sc_post=Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST[$option_name], 'text_arr');
                foreach($wt_sc_modules as $k=>$v)
                {
                    if(isset($wt_sc_post[$k]) && $wt_sc_post[$k]==1)
                    {
                        $wt_sc_modules[$k]=1;
                    }else
                    {
                        $wt_sc_modules[$k]=0;

                        if(in_array($k, $mu_modules))
                        {
                            $wt_sc_modules[$k] = 1; //block disabling MU modules
                        }
                    }
                }
            }else
            {
                foreach($wt_sc_modules as $k=>$v)
                {
                    $wt_sc_modules[$k]=0;

                    if(in_array($k, $mu_modules))
                    {
                        $wt_sc_modules[$k] = 1; //block disabling MU modules
                    }
                }
            }

            update_option($option_name, $wt_sc_modules);
        }

        /**
        * Form action for debug settings tab
        * @since 2.0.1
        */
        public function debug_save()
        {   
            if(isset($_POST['wt_sc_admin_modules_btn']))
            {
                if(Wt_Smart_Coupon_Security_Helper::check_write_access('smart_coupons', 'wt_smart_coupons_admin_nonce')) 
                {
                    return;
                }
                $this->debug_save_sub('wt_sc_public_modules', Wt_Smart_Coupon_Public::$mu_modules);
                $this->debug_save_sub('wt_sc_common_modules', Wt_Smart_Coupon_Common::$mu_modules);
                $this->debug_save_sub('wt_sc_admin_modules', Wt_Smart_Coupon_Admin::$mu_modules);
                wp_redirect($_SERVER['REQUEST_URI']); exit();
            }

            if(Wt_Smart_Coupon_Security_Helper::check_role_access('smart_coupons')) //Check access
            {
                //module debug settings saving hook
                do_action('wt_sc_module_save_debug_settings');
            }
        }

        /**
         * Help links metabox html
         * @since 1.3.5
         */
        public function help_links_meta_box_html()
        {
            include WT_SMARTCOUPON_MAIN_PATH.'/admin/views/_help_links_meta_box.php';
        }


        /**
         * Help links metabox
         * @since 1.3.5
         */
        public function help_links_meta_box()
        {
            add_meta_box("wt-sc-help-links", __("Quick links", 'wt-smart-coupons-for-woocommerce-pro'), array($this, "help_links_meta_box_html"), "shop_coupon", "side", "low", null);
        }

        /**
         *  Registers modules    
         *  @since 1.3.5     
         */
        public function register_modules()
        { 
            Wt_Smart_Coupon::register_modules(self::$modules, 'wt_sc_admin_modules', plugin_dir_path( __FILE__ ), self::$existing_modules, self::$mu_modules);  
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
        *   @since 1.3.5
        *   Set tooltip for form fields 
        */
        public static function set_tooltip($key, $base_id="", $custom_css="")
        {
            $tooltip_text=self::get_tooltips($key, $base_id);
            if($tooltip_text!="")
            {
                $tooltip_text="<span style='display:inline-block; color:#16a7c5; ".($custom_css!="" ? esc_attr($custom_css) : 'margin-top:0px; margin-left:2px; position:absolute;')."' class='dashicons dashicons-editor-help wt-sc-tips' data-wt-sc-tip='".esc_attr($tooltip_text)."'></span>";
            }
            return $tooltip_text;
        }

        /**
        *   @since 1.3.5
        *   Get tooltip config data for non form field items
        *   @return array 'class': class name to enable tooltip, 'text': tooltip text including data attribute if not empty
        */
        public static function get_tooltip_configs($key, $base_id="")
        {
            $out=array('class'=>'','text'=>'');
            $text=self::get_tooltips($key,$base_id);
            if($text!="")
            {
                $out['text']=" data-wt-sc-tip='".esc_attr($text)."'";
                $out['class']=' wt-sc-tips';
            }   
            return $out;
        }

        /**
        *   @since 1.3.5
        *   This function will take tooltip data from modules 
        *
        */
        public function register_tooltips()
        {
            include(plugin_dir_path( __FILE__ ).'data/data.tooltip.php');
            self::$tooltip_arr = array(
                'main' => $arr
            );
            /* hook for modules to register tooltip */
            self::$tooltip_arr = apply_filters('wt_sc_alter_tooltip_data', self::$tooltip_arr);
        }

        /**
        *   Get tooltips
        *   @since 1.3.5
        *   @param string $key array key for tooltip item
        *   @param string $base module base id
        *   @return tooltip content, empty string if not found
        */
        public static function get_tooltips($key, $base_id='')
        {
            $arr=($base_id!="" && isset(self::$tooltip_arr[$base_id]) ? self::$tooltip_arr[$base_id] : self::$tooltip_arr['main']);
            return (isset($arr[$key]) ? $arr[$key] : '');
        }


        /**
         * Registers menu options
         * Hooked into admin_menu
         *
         * @since    1.3.5
         */
        public function admin_menu()
        {
            $menus=array(
                array(
                    'menu',
                    __('General settings', 'wt-smart-coupons-for-woocommerce-pro'),
                    __('Smart Coupons', 'wt-smart-coupons-for-woocommerce-pro'),
                    'manage_woocommerce',
                    WT_SC_PLUGIN_NAME,
                    array($this, 'admin_settings_page'),
                    'dashicons-tag',
                    59
                ),
               array(
                    'submenu',
                    WT_SC_PLUGIN_NAME,
                    __('All coupons','wt-smart-coupons-for-woocommerce-pro'),
                    __('All coupons','wt-smart-coupons-for-woocommerce-pro'),
                    'edit_shop_coupons',
                    'edit.php?post_type=shop_coupon',
                ),
                array(
                    'submenu',
                    WT_SC_PLUGIN_NAME,
                    __('Add coupon','wt-smart-coupons-for-woocommerce-pro'),
                    __('Add coupon','wt-smart-coupons-for-woocommerce-pro'),
                    'edit_shop_coupons',
                    'post-new.php?post_type=shop_coupon',
                ),
            );
            $menus=apply_filters('wt_sc_admin_menu', $menus);

            if(is_array($menus))
            {
                $menus[]=array(
                    'submenu',
                    WT_SC_PLUGIN_NAME,
                    __('General settings','wt-smart-coupons-for-woocommerce-pro'),
                    __('General settings','wt-smart-coupons-for-woocommerce-pro'),
                    'manage_woocommerce',
                    WT_SC_PLUGIN_NAME,
                    array($this, 'admin_settings_page'),
                );
                foreach($menus as $menu)
                {
                    if($menu[0]=='submenu')
                    {
                        if(isset($menu[6]))
                        {
                            add_submenu_page($menu[1],$menu[2],$menu[3],$menu[4],$menu[5],$menu[6]);
                        }else{
                            add_submenu_page($menu[1],$menu[2],$menu[3],$menu[4],$menu[5]);
                        }
                        
                    }else
                    {
                        add_menu_page($menu[1],$menu[2],$menu[3],$menu[4],$menu[5],$menu[6],$menu[7]);  
                    }
                }
            }

            if(function_exists('remove_submenu_page')){
                remove_submenu_page(WT_SC_PLUGIN_NAME, WT_SC_PLUGIN_NAME);
            }
        }


        /**
         * Admin settings page
         *
         * @since    1.3.5
         */
        public function admin_settings_page()
        {
            include WT_SMARTCOUPON_MAIN_PATH.'admin/partials/wt-smart-coupon-admin-display.php';
        }

        /**
        *   Form field generator
        *   @since 1.3.5
        */
        public static function generate_form_field($args, $base='')
        {   
            include WT_SMARTCOUPON_MAIN_PATH."admin/views/_form_field_generator.php";
        }

        /**
         *  Envelope settings tab content with tab div.
         *  relative path is not acceptable in view file
         *  @since 1.3.5
         */
        public static function envelope_settings_tabcontent($target_id, $view_file="", $html="", $view_params=array(), $need_submit_btn=0)
        {
            ?>
                <div class="wt-sc-tab-content" data-id="<?php echo $target_id;?>">
                    <?php
                    if($view_file!="" && file_exists($view_file))
                    {
                        include_once $view_file;
                    }else
                    {
                        echo $html;
                    }
                    ?>
                    <?php 
                    if($need_submit_btn==1)
                    {
                        self::add_settings_footer();
                    }
                    ?>
                </div>
            <?php
        }

        /**
        *   Add setting tab footer
        *   @since 1.3.5
        */
        public static function add_settings_footer($settings_button_title='', $settings_footer_left='', $settings_footer_right='')
        {
            include WT_SMARTCOUPON_MAIN_PATH."admin/views/admin-settings-save-button.php";
        }

        /**
        *   Image preview popup HTML
        *   @since 2.0.0
        */
        public static function img_preview_popup_html()
        {
            include WT_SMARTCOUPON_MAIN_PATH."admin/views/_img_preview_popover.php";
        }

        /**
         * Save Custom meata fields added in coupon 
         * @since 1.0.0
         */
        public function process_shop_coupon_meta($post_id, $post) {

            if ( !class_exists( 'Wt_Smart_Coupon_Security_Helper' ) || !method_exists( 'Wt_Smart_Coupon_Security_Helper', 'check_user_has_capability' ) || !Wt_Smart_Coupon_Security_Helper::check_user_has_capability() ) 
            {
                wp_die(__('You do not have sufficient permission to perform this operation', 'wt-smart-coupons-for-woocommerce-pro'));
            }

            if( isset($_POST['_wt_valid_for_number']) && $_POST['_wt_valid_for_number']!='' ) {
                $_wt_valid_for_number = Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST['_wt_valid_for_number']);
                update_post_meta($post_id, '_wt_valid_for_number', $_wt_valid_for_number );
                
                if ( isset( $_POST['_wt_valid_for_type'] ) && '' != $_POST['_wt_valid_for_type']  ) {
                    $wt_valid_for_type = $_POST['_wt_valid_for_type'];
                } else {
                    $wt_valid_for_type = 'days';
                }
                update_post_meta($post_id, '_wt_valid_for_type', $wt_valid_for_type );

            }


            if(isset( $_POST['_wc_make_coupon_available'] ) && $_POST['_wc_make_coupon_available']!='' )
            {               
                $_wc_make_coupon_available = Wt_Smart_Coupon_Security_Helper::sanitize_item( $_POST['_wc_make_coupon_available'], 'text_arr');
                update_post_meta($post_id, '_wc_make_coupon_available', implode(',', $_wc_make_coupon_available ) );
            }else
            {
                update_post_meta($post_id, '_wc_make_coupon_available',  '' );
            }

        }

        /**
         * Enqueue Admin styles.
         * @since 1.0.0
         */
        public function enqueue_styles() {
            $screen    = get_current_screen();
            $screen_id = $screen ? $screen->id : '';
            
            if ( 
                (function_exists('wc_get_screen_ids') && in_array( $screen_id, wc_get_screen_ids())) || 
                (isset($_GET['page']) && ($_GET['page']==WT_SC_PLUGIN_NAME || strpos($_GET['page'], WT_SC_PLUGIN_NAME)===0))
            ) 
            {
                wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/wt-smart-coupon-admin.css', array('woocommerce_admin_styles','wc-admin-layout'), $this->version, 'all');
                wp_enqueue_style( 'wp-color-picker' );
            }


            /**
             *  Enqueue style for code preview in hooks help section
             *  
             *  @since 2.1.0
             */
            if ( isset( $_GET['page'] ) && WT_SC_PLUGIN_NAME === $_GET['page'] ) {
                wp_enqueue_style( $this->plugin_name . '_highlightjs', plugin_dir_url( __FILE__ ) . 'assets/libraries/highlight/styles/stackoverflow-light.min.css', array(), $this->version, 'all' );
            }
        }
        
        /**
         * Enqueue Admin Scripts.
         * @since 1.0.0
         */
        public function enqueue_scripts() {

            $screen    = get_current_screen();
            $screen_id = $screen ? $screen->id : '';

            $script_parameters=array(
                'no_image'=>Wt_Smart_Coupon::$no_image,
                'search_categories_nonce' => wp_create_nonce( 'search-categories' ), /** @since 2.1.1 [Fix] Conflict with the Smart Coupons StoreApps plugin - 403 (Forbidden) issue while searching categories */
                'search_products_nonce' => wp_create_nonce( 'search-products' ), /** @since 2.1.1 [Fix] to avoid future nonce conflict like category search problem mentioned above */
                'msgs'=>array(
                    'settings_error'=>sprintf(__('Unable to update settings due to an internal error. %s To troubleshoot please click %s here. %s', 'wt-smart-coupons-for-woocommerce-pro'), '<br />', '<a href="https://www.webtoffee.com/how-to-fix-the-unable-to-save-settings-issue/" target="_blank">', '</a>'),
                    'is_required'=>__("is required", 'wt-smart-coupons-for-woocommerce-pro'),
                    'copied'=>__("Copied!", 'wt-smart-coupons-for-woocommerce-pro'),
                    'error'=>__("Error", 'wt-smart-coupons-for-woocommerce-pro'),
                    'loading'=>__("Loading...", 'wt-smart-coupons-for-woocommerce-pro'),
                    'please_wait'=>__("Please wait...", 'wt-smart-coupons-for-woocommerce-pro'),
                    'are_you_sure'=>__("Are you sure?", 'wt-smart-coupons-for-woocommerce-pro'),
                    'are_you_sure_to_delete'=>__("Are you sure you want to delete?", 'wt-smart-coupons-for-woocommerce-pro'),
                    'saving' => __("Saving...", 'wt-smart-coupons-for-woocommerce-pro'),
                    'old_bogo_disabled' => __( "Old BOGO module is disabled", 'wt-smart-coupons-for-woocommerce-pro' ),
                    'switch_new_bogo' => __( "Switch to our new BOGO module for the latest features", 'wt-smart-coupons-for-woocommerce-pro' ),
                    'update_now' => __( "Update now", 'wt-smart-coupons-for-woocommerce-pro' ),
                ),
                'is_new_bogo_activated' => class_exists( 'Wbte_Smart_Coupon_Bogo_Common' ) 
                && method_exists( 'Wbte_Smart_Coupon_Bogo_Common', 'is_new_bogo_activated' ) 
                && Wbte_Smart_Coupon_Bogo_Common::is_new_bogo_activated(),
            );
            
            $script_parameters['ajaxurl'] = admin_url( 'admin-ajax.php' );
            $script_parameters['nonce'] = wp_create_nonce( 'wt_smart_coupons_admin_nonce' );
            
            if ( 
                (function_exists('wc_get_screen_ids') && in_array( $screen_id, wc_get_screen_ids())) || 
                (isset($_GET['page']) && ($_GET['page']==WT_SC_PLUGIN_NAME || strpos($_GET['page'], WT_SC_PLUGIN_NAME)===0))
            ) 
            {
                wp_enqueue_media();
                wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/wt-smart-coupon-admin.js', array('jquery', 'wp-color-picker', 'jquery-tiptip', 'wc-enhanced-select'), $this->version, false);
                wp_localize_script($this->plugin_name,'WTSmartCouponAdminOBJ',$script_parameters );
            }

            /**
             *  Enqueue script for code preview in hooks help section
             *  
             *  @since 2.1.0
             */
            if ( isset( $_GET['page'] ) && WT_SC_PLUGIN_NAME === $_GET['page'] ) {
                wp_enqueue_script( $this->plugin_name . '_highlightjs', plugin_dir_url( __FILE__ ) . 'assets/libraries/highlight/highlight.min.js', array(), $this->version, false );
            }
        }

        /**
         * Add  Smart coupon pages into woocommcerce screen ID's
         * @since 1.0.0
         */
        public function add_wc_screen_id( $screen_ids ) {
            
            $screen_ids[] = 'admin_page_wt-smart-coupon';
            $screen_ids[] = 'dashboard_page_wt-smart-coupon';
            $screen_ids[] = '_page_wt-smart-coupon';
            return $screen_ids;
        }

        /**
         * Add tabs to the coupon option page.
         * @since 1.0.0
         */
        public function admin_coupon_options_tabs($tabs) {

            $tabs['wt_coupon_checkout_options'] = array(
                'label' => __('Checkout options', 'wt-smart-coupons-for-woocommerce-pro'),
                'target' => 'webtoffee_coupondata_checkout1',
                'class' => 'webtoffee_coupondata_checkout1',
            );

            return $tabs;
        }

        /**
         * wt_coupon_checkout_options Page content
         * @since 1.0.0
         */
        public function admin_coupon_options_panels() {

            global $thepostid, $post;
            $thepostid = empty($thepostid) ? $post->ID : $thepostid;
            ?>
            <div id="webtoffee_coupondata_checkout1" class="panel woocommerce_options_panel">
            <?php
            do_action('webtoffee_coupon_metabox_checkout', $thepostid, $post);
            ?>
            </div>

            <?php
        }
        

        /**
         * Plugin action link.
         * @since 1.0.0
         */
        public function add_plugin_links_wt_smartcoupon($links) {


            $plugin_links = array(
                '<a target="_blank" href="https://www.webtoffee.com/support/">' . __('Support', 'wt-smart-coupons-for-woocommerce-pro') . '</a>',
                '<a target="_blank" href="https://www.webtoffee.com/category/documentation/smart-coupons-for-woocommerce/">' . __('Documentation', 'wt-smart-coupons-for-woocommerce-pro') . '</a>',
                '<a href="'.admin_url('admin.php?page='.WT_SC_PLUGIN_NAME).'">' . __('Settings', 'wt-smart-coupons-for-woocommerce-pro') . '</a>',
            );
            
            return array_merge($plugin_links, $links);
        }

        /**
         * Add smart Coupon tabs into Coupon page.
         * @since 1.0.0
         */
        public function smart_coupons_views_row( $views = null ) {

            global $typenow;

            if ( $typenow == 'shop_coupon' ) {
                
                do_action( 'smart_coupons_display_views' );
            }

            return $views;

        }


        /**
         * Add other coupon general options.
         * @since 1.1.0
         */
        function add_new_coupon_options( $coupon_id, $coupon )
        {
            
            $wc_make_coupon_available = get_post_meta($coupon_id , '_wc_make_coupon_available', true );
        
            $wc_make_coupon_available = $wc_make_coupon_available ? explode(',', $wc_make_coupon_available) : array();

            $make_coupon_available = array(
                'my_account'    => __('My Account','wt-smart-coupons-for-woocommerce-pro'),
                'checkout'      => __('Checkout','wt-smart-coupons-for-woocommerce-pro'),
                'cart'          => __('Cart','wt-smart-coupons-for-woocommerce-pro'),
            );
            ?>
            <p class="form-field"><label for="_wc_make_coupon_available"><?php _e('Display coupon in', 'wt-smart-coupons-for-woocommerce-pro'); ?></label>
            <select id="_wc_make_coupon_available" name="_wc_make_coupon_available[]" style="width: 50%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php _e('Please select', 'wt-smart-coupons-for-woocommerce-pro'); ?>">
                    <?php
                    if(!empty($make_coupon_available))
                    {
                        foreach ($make_coupon_available as $section => $name ) {
                            if( !empty( $wc_make_coupon_available )  && in_array($section, $wc_make_coupon_available ) ) {
                                $selected = 'selected = selected';
                            } else {
                                $selected = '';
                            }
    
                            echo '<option value="' . esc_attr($section) . '" '.$selected.'>'
                            . esc_html($name) . '</option>';
                        }
                    }
                    
                    ?>
                </select> 
                <?php echo wc_help_tip( __('Display coupon in the selected pages', 'wt-smart-coupons-for-woocommerce-pro') ); ?>

            </p>
            
            <?php
        }
        

        /**
         * Ajax function for populating Coupon on multiselect field.
         * @since 1.1.0
         */
        public function wt_json_search_coupons()
        {

            global $wpdb; 
            if (!Wt_Smart_Coupon_Security_Helper::check_write_access( 'smart_coupons', 'search-coupons' ))
            {
                wp_die(__('You do not have sufficient permission to perform this operation', 'wt-smart-coupons-for-woocommerce-pro'));
            }

            $term = (string) (isset($_GET['term']) ? wc_clean(wp_unslash($_GET['term'])) : ''); 
            $post_id =(isset($_GET['post_id']) ? absint($_GET['post_id']) : 0);
            $no_coupon_type =(isset($_GET['no_coupon_type']) ? absint($_GET['no_coupon_type']) : 0); /* no coupon type in coupon code */
            $input_name =(isset($_GET['input_name']) ? sanitize_text_field($_GET['input_name']) : ''); /* the input name, this is to identify, request is coming from which section */

			if(empty($term))
            {
				die();
			}
            
            $posts = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT p.*
                     FROM {$wpdb->prefix}posts p
                     LEFT JOIN {$wpdb->prefix}postmeta pm 
                        ON p.ID = pm.post_id 
                        AND pm.meta_key = %s
                     WHERE p.post_type = %s
                       AND (p.post_title LIKE %s OR pm.meta_value LIKE %s)
                       AND p.post_status = %s",
                    'wbte_sc_bogo_coupon_name',
                    'shop_coupon',
                    '%' . $wpdb->esc_like( $term ) . '%',
                    '%' . $wpdb->esc_like( $term ) . '%',
                    'publish'
                )
            );


			$found_coupons = array();

			$all_discount_types = wc_get_coupon_types();

			if ( $posts ) {
				foreach ( $posts as $post_item ) {
                    
                    if( $post_id== $post_item->ID){
                        continue;
                    }

                    /**
                     *  Exclude master coupons from search results
                     *  @since 2.0.6
                     */
                    $is_master_coupon = get_post_meta($post_item->ID, self::$master_coupon_meta_key, true);
                    
                    if($is_master_coupon)
                    {
                        continue;        
                    }


					$discount_type = get_post_meta($post_item->ID, 'discount_type', true);

					if(!empty($all_discount_types[$discount_type]))
                    {
                        $discount_type_html = '';
                        $_title = $post_item->post_title;
                        
                        if(0 === $no_coupon_type)
                        {
                            $discount_type_html = ' (' . __('Type', 'wt-smart-coupons-for-woocommerce-pro' ) . ': ' . esc_html($all_discount_types[$discount_type]) . ')';
                        }

                        if( 'BOGO' === $all_discount_types[$discount_type] && $_coupon_id = $post_item->ID )
                        {
                            $_title = get_post_meta( $_coupon_id, 'wbte_sc_bogo_coupon_name', true );
                            $discount_type_html = wp_kses_post( '( ' . __( 'Type', 'wt-smart-coupons-for-woocommerce-pro') . ': ' . __('BOGO', 'wt-smart-coupons-for-woocommerce-pro') . __( ', ID', 'wt-smart-coupons-for-woocommerce-pro') . ': ' . $_coupon_id . ' )' );
                        }

                        $found_coupons[ $post_item->ID ] = "$_title $discount_type_html";
					}
				}
            }

            /**
             *  Alter json result
             *  @since 2.0.6 
             */
            $found_coupons = apply_filters('wt_sc_alter_json_search_coupons', $found_coupons, $input_name);
            $found_coupons = (!is_array($found_coupons) ?  array() : $found_coupons);

			wp_send_json($found_coupons);

		}

        /**
         * Get Smartcoupon Settings options
         * @since 1.1.0
         */
        public static function get_options() {
            $smart_coupon_options = apply_filters('wt_smart_coupon_default_options',array(
                
            ));
            $smart_coupon_saved_option = get_option('wt_smart_coupon_options');
            if ( !empty($smart_coupon_saved_option) ) {
                foreach ( $smart_coupon_saved_option as $key => $option ) {
                    $smart_coupon_options[$key] = $option;
                }
            }
            update_option("wt_smart_coupon_options", $smart_coupon_options);
            return $smart_coupon_options;
        }

        public static function get_option ( $option_name ) {
            $smart_coupon_options = self::get_options();

            if( isset( $smart_coupon_options[ $option_name ] ) ) {
                return $smart_coupon_options[ $option_name ] ;
            }

            foreach(  $smart_coupon_options as $smart_coupon_option  ) {
               if( isset( $smart_coupon_option[ $option_name ] ) ){
                   return $smart_coupon_option[ $option_name ];
               }
            }
            return false;
        }

       

        /**
         * Add Coupon Styles into Woocommerce Email Style.
         * 
         * @since 1.1.0
         * @since 2.0.7 Moved coupon styles to coupon_style module. And appending via filter `wt_sc_alter_coupon_email_css`
         */
        function coupon_inline_style($style)
        {
            $css = '
            .wt_gift_coupon_preview_caption {
                float: left;
                width: 100%;
                padding: 20px 0px;
                text-align: center;
                color: #fff;
              }
              .wt_gift_coupon_preview_image img{
                float: left;
                width: 100%;
                height: auto;
              }
              
              .wt_coupon-code-block {
                float: left;
                width: 100%;
                background: #ffffff;
                padding: 20px 0px;
              }
              
              .wt_coupon-code-block .coupon-code {
                float: left;
                text-align: left;
                background: #0e0b0d;
                padding: 10px;
                color: #fff;
                border-radius: 6px;
                margin-left:20px;
              }
              
              .wt_coupon-code-block .coupon_price {
                float: right;
                text-align: right;
                font-size: 32px;
                font-weight: 700;
                color: #0e0b0d;
                padding: 0px;
                line-height: 36px;
                margin-right:20px;
              }
              
              .coupon-message-block {
                float: left;
                width: 100%;
                color: #fff;
                padding: 20px 0px;
              }
              
              .coupon-message-block .coupon-message {
                float: left;
                text-align: left;
                margin-left:20px;
              }
              
              .coupon-message-block .coupon-from {
                float: right;
                text-align: right;
                margin-right:20px;
              }
              .wt_store_credit_email_wrapper{
                  margin:0 auto;
                  max-width:600px;
                  background:#ffffff;
              }
            
            ';
            if(Wt_Smart_Coupon_Common::module_exists('store_credit'))
            {
                $store_credit_templates =Wt_Smart_Coupon_Store_Credit::get_instance()->get_gift_card_templates();
            }else{
                $store_credit_templates =array();
            }

            $store_credit_template_css = '';
            if(!empty($store_credit_templates))
            {
                foreach($store_credit_templates  as $template => $template_details )
                {
                    $store_credit_template_css .= '
                    .wt_gift_coupon_preview_caption.'.$template.'{
                        background-color:'.$template_details['top_bg_color'].'
                    }
                    .coupon-message-block.'.$template.'{
                        background-color:'.$template_details['bottom_bg_color'].'
                    }
                    ';
                }
            }
            
            $smart_coupon_email_css = apply_filters('wt_sc_alter_coupon_email_css', $css.$store_credit_template_css);

            return $style.$smart_coupon_email_css;
        }


        /**
         * Coupon send order status
         * @since 1.1.0
         */

        public static function success_order_statuses() {
            $order_statuses = array(
                'processing'=>__('Processing','wt-smart-coupons-for-woocommerce-pro'),
                'completed'=>__('Completed','wt-smart-coupons-for-woocommerce-pro'),               
            );

            return apply_filters( 'wt_coupon_success_order_statuses', $order_statuses  );
        }

        /**
         * Get Coupon Properties
         * @since 1.2.0
         */
        public static function wt_get_coupon_properties( $coupon, $key ) {
            switch ( $key ) {
                case 'type':
                    $need_to_get = array( $coupon, 'get_discount_type' );
                    break;
                default:
                    $need_to_get = array( $coupon, 'get_' . $key );
                    break;
            }
        
            return ( is_callable( $need_to_get ) ? call_user_func( $need_to_get ) : $coupon->{ $key } );
        }


        /**
         * Generate a random Coupon code.
         * @since 1.0.0
         * moved into admin common function on 1.2.0
         */
        public static function generate_random_coupon($prefix, $suffix, $length = 12)
        {
            global $wpdb;
            $random_coupon = '';
            $charset       = apply_filters('wt_allowed_characters_for_random_coupon', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
            $count         = strlen($charset);
            
            while ( $length-- ) :
                $random_coupon .= $charset[ mt_rand( 0, $count-1 ) ];
            endwhile;

            $coupon_code = wc_sanitize_coupon_code($prefix.$random_coupon.$suffix);
            $query = "SELECT ID FROM $wpdb->posts WHERE post_type ='shop_coupon' AND post_status ='publish' AND post_title = %s ";
            while($wpdb->get_var($wpdb->prepare($query, $coupon_code)))
            {
                return self::generate_random_coupon($prefix, $suffix, $length);
            }
        
            return $coupon_code;     
        }


        /**
         * Clone the coupon 
         * @since 1.2.6
         * @since 2.0.6 Cloned coupon status set as published when cloning from a drafted master coupon. Applicable for `Signup coupon`, `Abandonment cart coupon`
         */
        public static function clone_coupon($coupon, $prefix='', $suffix='', $coupon_length='' )
        {
            global $wpdb;
            $coupon_obj = get_post($coupon);

            if (isset( $coupon_obj ) && $coupon_obj != null ) {
                
                $general_settings_options = Wt_Smart_Coupon::get_settings();
                
                if('' === $prefix) {
                    $prefix         = isset( $general_settings_options['wt_coupon_prefix'] )? $general_settings_options['wt_coupon_prefix'] : '';
                }
                if('' === $suffix) {
                    $suffix         = isset( $general_settings_options['wt_coupon_suffix'] )? $general_settings_options['wt_coupon_suffix'] : '';
                }
                if( '' === $coupon_length ) {
                    $coupon_length  = ( isset( $general_settings_options['wt_coupon_length'] ) && '' !== $general_settings_options['wt_coupon_length'] ) ? $general_settings_options['wt_coupon_length'] : 12 ;
                }

                $coupon_title = Wt_Smart_Coupon_Admin::generate_random_coupon($prefix,$suffix,$coupon_length);
               

                $args = json_decode(json_encode($coupon_obj), true);
                $args['post_title'] = $coupon_title;
                $should_change_fields = array('ID','post_date','post_date_gmt','post_name','post_modified','post_modified_gmt','guid','comment_count');
                
                foreach( $should_change_fields as $field ) {
                    unset( $args[ $field ] );
                }

                if(get_post_meta($coupon, self::$master_coupon_meta_key, true)) /* From a master coupon */
                {
                    $args['post_status'] = 'publish';
                }               

                $new_post_id = wp_insert_post( $args );
                $new_coupon  = new WC_Coupon($new_post_id );

                $taxonomies = get_object_taxonomies($coupon_obj->post_type); 
                foreach ($taxonomies as $taxonomy) {
                    $post_terms = wp_get_object_terms($coupon, $taxonomy, array('fields' => 'slugs'));
                    wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
                }

                $post_meta_data = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$coupon");
                if (0 !== count($post_meta_data)) {

                    $meta_no_need_to_clone = apply_filters('wt_smart_coupon_meta_no_need_to_clone',  array('_wp_old_slug', 'wt_credit_history', '_wt_smart_coupon_initial_credit', self::$master_coupon_meta_key));

                    foreach ($post_meta_data as $meta_info)
                    {
                        $meta_key = $meta_info->meta_key;
                        
                        if(!in_array($meta_key, $meta_no_need_to_clone))
                        {
                            $meta_value = addslashes( is_null( $meta_info->meta_value) ? '' : $meta_info->meta_value ); 
                            
                            if(is_serialized($meta_value)){ 

                              /**
                               * Serialized associative array fails to undergo unserialization during the cloning process.
                               * 
                               * @since 2.0.8 
                               * 
                               */
                              $meta_value =  maybe_unserialize(stripslashes($meta_value));

                            }

                            if ( '_wbte_sc_auto_coupon_priority' === $meta_key ) {
                                $meta_value = $new_post_id;
                            }

                            update_post_meta( $new_post_id, $meta_key, $meta_value );

                        }
                    }
                    
                    /**
                     * Special case for category meta
                     */
                    $categories = get_post_meta( $coupon, 'product_categories', true );
                    update_post_meta( $new_post_id, 'product_categories', $categories );

                    $new_coupon->save();
                }

                return $new_post_id;
            }

            return false;
        }


        /**
         * helper function for getting formatted price
         * @since 1.2.9
         */
        public static function get_formatted_price( $amount ) {
            $currency = get_woocommerce_currency_symbol();
            $currentcy_positon = get_option('woocommerce_currency_pos');
    
            switch( $currentcy_positon ) {
                case 'left' : 
                    return $currency.$amount;
                case 'left_space' : 
                    return $currency.' '.$amount;
                case 'right_space' : 
                    return $amount.' '.$currency;
                default  : 
                    return $amount.$currency;
            }
        }
        /**
         * Callback for changing user role in Webtoffee security helper
         * @since 1.3.0
         */
        public static function wt_sc_alter_user_roles() {
            return array('manage_woocommerce');
        }
        
        /**
         * Get WC_DateTime object for a date
         * @since 1.3.0
         * @since 2.0.1 Also accepts timestamp as argument
         */
        public static function wt_sc_get_date_prop( $value )
        {
            if(is_int($value))
            {
                $timestamp=$value;
            }else
            {
                if ( 1 === preg_match( '/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(Z|((-|\+)\d{2}:\d{2}))$/', $value, $date_bits ) ) {
                    $offset    = ! empty( $date_bits[7] ) ? iso8601_timezone_to_offset( $date_bits[7] ) : wc_timezone_offset();
                    $timestamp = gmmktime( $date_bits[4], $date_bits[5], $date_bits[6], $date_bits[2], $date_bits[3], $date_bits[1] ) - $offset;
                } else {
                    $timestamp = wc_string_to_timestamp( get_gmt_from_date( gmdate( 'Y-m-d H:i:s', wc_string_to_timestamp( $value ) ) ) );
                }
            }
            $datetime = new WC_DateTime( "@{$timestamp}", new DateTimeZone( 'UTC' ) );

			// Set local timezone or offset.
            if ( get_option( 'timezone_string' ) ) {
                $datetime->setTimezone( new DateTimeZone( wc_timezone_string() ) );
            } else {
                $datetime->set_utc_offset( wc_timezone_offset() );
            }
            
            return $datetime;
        }

        /**
         *  Column title for used coupons in order listing page. Column will be added just before total column
         *  @since 2.0.5
         */
        public function add_order_used_coupon_column($columns)
        { 
            $out=array();
            foreach($columns as $column_key => $column_title)
            {
                if("order_total" == $column_key)
                {
                    $out['wt_sc_order_used_coupons'] = __('Coupon(s) used', 'wt-smart-coupons-for-woocommerce-pro');
                }
                $out[$column_key] = $column_title;
            }
            return $out;
        }

        /**
         *  Column content for used coupons in order listing page. Shows coupon discount as tooltip
         *  @since 2.0.5
         */
        public function add_order_used_coupon_column_content($column_name, $post_ID)
        {
            if('wt_sc_order_used_coupons' == $column_name)
            {
                $order = new WC_Order($post_ID);
                $coupons = $order->get_items('coupon');
                if(!empty($coupons))
                {
                    ?>
                    <ul class="wc_coupon_list">
                        <?php
                        foreach($coupons as $coupon_id => $coupon)
                        {
                            ?>
                            <li class="code">
                                <span class="tips" data-tip="<?php echo esc_attr(wc_price($coupon->get_discount(), array('currency' => $order->get_currency()))); ?>">
                                    <?php 
                                    
                                    $_coupon_id = wc_get_coupon_id_by_code( $coupon->get_code() );
                                    if( $_coupon_id && 'wbte_sc_bogo' === get_post_meta( $_coupon_id, 'discount_type', true ) )
                                    {
                                        echo esc_html( get_post_meta( $_coupon_id, 'wbte_sc_bogo_coupon_name', true ) ); 
                                    }else{
                                        echo esc_html($coupon->get_code()); 
                                    }
                                    ?>
                                </span>
                            </li>
                            <?php
                        }
                        ?>
                    </ul>
                    <?php
                }
            }
        }

        /**
         *  @since 2.0.5
         *  Saving new coupon count
         */
        public function save_created_coupon_count($post_id, $post, $update)
        {
            if(!$update && 'shop_coupon' === $post->post_type && 'auto-draft' === $post->post_status)
            {
                $auto_draft = get_option('wt_sc_auto_draft_coupons', array());
                $auto_draft[$post_id] = 1;

                update_option('wt_sc_auto_draft_coupons', $auto_draft);
            }


            if('shop_coupon' === $post->post_type && 'auto-draft' !== $post->post_status)
            {
                $auto_draft = get_option('wt_sc_auto_draft_coupons', array());

                $coupons_created = (int) get_option('wt_sc_coupons_created', 0);

                $is_update_needed = false;

                if($update && isset($auto_draft[$post_id])) //auto draft item saving as shop coupon
                {
                    $coupons_created++;
                    $is_update_needed = true;
                    
                    unset($auto_draft[$post_id]);
                    update_option('wt_sc_auto_draft_coupons', $auto_draft);
                }

                if(!$update)
                {
                    $coupons_created++;
                    $is_update_needed = true;
                }          

                if($is_update_needed)
                {
                    update_option('wt_sc_coupons_created', $coupons_created);
                }
            }
        }

        /**
         *  Column title for `used orders` in coupon listing page. Column will be added just after `expiry date` column
         *  @since 2.0.6
         */
        public function add_coupon_used_in_order_column($columns)
        {
            $out=array();
            $new_column_title = __('Used in orders', 'wt-smart-coupons-for-woocommerce-pro');
            $new_column_key = 'wt_sc_coupon_used_in_orders'; 

            foreach($columns as $column_key => $column_title)
            {
                $out[$column_key] = $column_title;
                if("expiry_date" === $column_key)
                {
                    $out[$new_column_key] = $new_column_title;
                }           
            }

            if(!isset($out[$new_column_key])) //if `expiry date` column is missing
            {
                $out[$new_column_key] = $new_column_title;
            }

            return $out;
        }

        /**
         *  Column content for `used orders` in coupon listing page.
         *  @since 2.0.6
         *  @since 2.0.8   Added HPOS Compatibility
         */
        public function add_coupon_used_in_order_column_content($column_name, $post_ID)
        {
            if('wt_sc_coupon_used_in_orders' == $column_name)
            {
                $coupon_code = wc_sanitize_coupon_code(wc_get_coupon_code_by_id($post_ID));
                if($coupon_code)
                {    
                    $url = Wt_Smart_Coupon_Common::is_wc_hpos_enabled() ?  admin_url('admin.php?page=wc-orders&paged=1&s=coupon:' . $coupon_code . '&m=0') :  admin_url('edit.php?s=coupon:' . $coupon_code . '&post_status=all&post_type=shop_order&action=-1&paged=1&action2=-1');       
                    ?>
                    <a href="<?php echo esc_attr($url);?>" title="<?php esc_attr_e('Show orders', 'wt-smart-coupons-for-woocommerce-pro');?>"><span class="dashicons dashicons-external"></span></a>
                    <?php
                }
            }
        }

        /**
         *  Alter WP order search section to handle `orders by coupon` search. 
         *  Search format - coupon:{coupon_code}
         *  @since 2.0.6
         */
        public function search_order_using_coupon($wp)
        {
            global $pagenow, $wpdb;
            if('edit.php' !== $pagenow || !isset($wp->query_vars['s']) || 'shop_order'!== $wp->query_vars['post_type'])
            {
                return;
            }
            
            $wp->query_vars['s']=trim($wp->query_vars['s']);
            
            if('coupon:' === strtolower(substr($wp->query_vars['s'], 0, 7)))
            {
                $coupon_code = wc_sanitize_coupon_code(substr($wp->query_vars['s'], 7));
                
                if(!$coupon_code)
                {
                    return;
                }

                $order_ids=$wpdb->get_col(
                    $wpdb->prepare(
                        "SELECT order_id
                        FROM {$wpdb->prefix}woocommerce_order_items as order_items
                        WHERE order_item_name LIKE %s AND order_item_type = %s ",
                        '%'.$wpdb->esc_like(wc_clean($coupon_code)).'%', 'coupon'
                    ));                

                if(empty($order_ids))
                {
                    return;
                } 
                
                unset($wp->query_vars['s'], $_REQUEST['s']); //prevent WP default search

                $wp->query_vars['post__in'] = $order_ids;
            }
        }

        /**
         *  To get the list of modules with master coupon option
         *  @since 2.0.6
         */
        private function get_modules_with_master_coupon_option()
        {
            return apply_filters('wt_sc_intl_alter_modules_list_with_master_coupon_option', array());
        }

        /**
         *  To get the list of modules with master coupon option
         *  @since 2.0.6
         */
        private function get_master_coupon_option_name($base_id)
        {
            return apply_filters('wt_sc_intl_master_coupon_option_name', 'wt_master_coupon', $base_id);
        }

        /**
         *  Save the existing settings/options before saving the settings. The stored values are used to find the difference between old and new settings.
         *  This is for altering status of associated master coupons.
         *  @since 2.0.6
         *  @param  $the_options                array              settings
         *  @param  $base_id                    string             module id
         **/
        public function settings_before_update_to_alter_master_coupon_status($the_options, $base_id)
        {
            if(in_array($base_id, $this->get_modules_with_master_coupon_option()))
            {
                $this->options_before_saving = $the_options;    
            }       
        }


        /**
        * 
        *  Compare the new settings with old settings. To alter the master coupon status based on the settings.
        *  When the master coupon is updated, then the status of coupon is changed to 'draft' if the settings is set to 'not same as the master coupon'. 
        *  
        * @since 2.0.6
        * @since 2.1.1     [FIX] Creating new post meta everytime while saving Signup coupon admin setting
        * @since 2.3.0     Coupon status switching to `publish` on `as is` only when the coupon has any email restrictions.
        * 
        * @param  $a_the_options              array              settings
        * @param  $base_id                    string             module id
        *  
        **/
        public function settings_after_update_to_alter_master_coupon_status($options_after_saving, $base_id)
        {
            if(in_array($base_id, $this->get_modules_with_master_coupon_option()))
            {   
                $master_coupon_option_name = $this->get_master_coupon_option_name($base_id);
                $new_master_coupon = (int) $options_after_saving[$master_coupon_option_name];
                $old_master_coupon = (isset($this->options_before_saving[$master_coupon_option_name]) ? (int) $this->options_before_saving[$master_coupon_option_name] : 0); 
                
                /* no changes for master coupon and `same as master coupon` so return` */
                if($new_master_coupon === $old_master_coupon && $options_after_saving['use_master_coupon_as_is'] === $this->options_before_saving['use_master_coupon_as_is'])
                {
                    return;
                }

                $new_master_coupon_obj = new WC_Coupon( $new_master_coupon );
                
                if($new_master_coupon === $old_master_coupon)
                {
                    if(0 === $old_master_coupon)
                    {
                        return;
                    }

                    // `Same as master coupon` option changed to `yes` so updated the coupon status to `publish` when there is any email restrictions available.
                    if(1 === (int) $options_after_saving['use_master_coupon_as_is'] && ! empty( $new_master_coupon_obj->get_email_restrictions() ) )
                    {
                        $args = array(
                            'ID' => $old_master_coupon,
                            'post_status' => 'publish',
                        );
                        wp_update_post($args);

                    }else
                    {
                        $args = array(
                            'ID' => $old_master_coupon,
                            'post_status' => 'draft',
                        );
                        wp_update_post($args);
                    }

                    if (!get_post_meta($old_master_coupon, self::$master_coupon_meta_key, true)) 
                    {
                        add_post_meta($old_master_coupon, self::$master_coupon_meta_key, $base_id); // store a post meta in the coupon to identify its a master coupon  
                    }
                }
                else
                {   
                    // `same as master coupon` option changed to `no` so updated the current coupon status to `draft` and change the old coupon status to `publish`
                    if (0 === (int) $options_after_saving['use_master_coupon_as_is'])
                    {   
                        $new_data = array(
                            'ID' => $new_master_coupon,
                            'post_status' => 'draft',

                        );
                       
                        //and the old one's status is updated to 'publish'
                        $old_data = array(
                            'ID' => $old_master_coupon,
                            'post_status' => 'publish',

                        );

                    }else
                    {
                        $new_data = array(
                            'ID' => $new_master_coupon,
                        );

                        // Set new coupon status to publish only when there is any email restrictions available.
                        $new_data['post_status'] = ( empty( $new_master_coupon_obj->get_email_restrictions() ) ? 'draft' : 'publish' );

                        $old_data = array(
                            'ID' => $old_master_coupon,
                            'post_status' => 'publish',
                        );
                    }

                    if($new_master_coupon > 0)
                    {
                        wp_update_post($new_data);
                        add_post_meta($new_master_coupon, self::$master_coupon_meta_key, $base_id); // store a post meta in the coupon to identify its a master coupon
                    
                    }

                    if($old_master_coupon > 0)
                    {                      
                        delete_post_meta($old_master_coupon, self::$master_coupon_meta_key); // remove the post meta to identify its a master coupon
                        wp_update_post($old_data);
                    }
                }                   
            }       
        }


        /**
         *  Show master coupon info in coupon listing section
         *  @since 2.0.6
         *  @param string  $column     Column name.
         *  @param int        $coupon_id  Coupon ID.
         */
        public function coupon_list_page_master_coupon_info($column, $coupon_id)
        {
            if('coupon_code'!==$column)
            {
                return;
            }

            $is_master_coupon = get_post_meta($coupon_id, self::$master_coupon_meta_key, true); //this contains module id

            if($is_master_coupon && in_array($is_master_coupon, $this->get_modules_with_master_coupon_option()))
            {
                $module_base = ucfirst(Wt_Smart_Coupon::get_module_base($is_master_coupon));
                echo '<br /><i><b>';
                echo esc_html(__('Master coupon for', 'wt-smart-coupons-for-woocommerce-pro'). ' ' . str_replace('_', ' ', $module_base));
                echo '</b></i>';
            }
        }

        /**
         *  Prevent master coupon from publishing. If the `same as master coupon` option is `No`.
         *  @since 2.0.6
         */
        public function prevent_master_coupon_from_publishing($post_id, $post, $update)
        {
            foreach($this->get_modules_with_master_coupon_option() as $module_id)
            {
                $the_options=Wt_Smart_Coupon::get_settings($module_id);
                $master_coupon_option_name = $this->get_master_coupon_option_name($module_id);

                if($the_options[$master_coupon_option_name]>0 && $post_id === (int) $the_options[$master_coupon_option_name] &&  1 !== (int) $the_options['use_master_coupon_as_is'])
                {
                    // unhook this function so it doesn't loop infinitely
                    remove_action('save_post_shop_coupon', array($this, 'prevent_master_coupon_from_publishing'), 11);

                    // update the post, which calls save_post again
                    wp_update_post(array('ID' => $post_id, 'post_status' => 'draft'));

                    break;
                }
            }
        }

        /**
         *  Set publish button text as `Update` for drafted master coupons.
         *  @since 2.0.6
         */
        public function alter_publish_button_for_master_coupon($hook)
        {
            global $post;

            if(('post-new.php' === $hook  || 'post.php' === $hook) && 'shop_coupon' === $post->post_type)
            {
                foreach($this->get_modules_with_master_coupon_option() as $module_id)
                {
                    $the_options=Wt_Smart_Coupon::get_settings($module_id);
                    $master_coupon_option_name = $this->get_master_coupon_option_name($module_id);

                    if($the_options[$master_coupon_option_name]>0 && $post->ID === (int) $the_options[$master_coupon_option_name] &&  1 !== (int) $the_options['use_master_coupon_as_is'])
                    {
                        ?>
                        <script type="text/javascript">
                            document.addEventListener("DOMContentLoaded", function(event) { 
                                document.getElementById('publish').value = '<?php esc_html_e('Update', 'wt-smart-coupons-for-woocommerce-pro');?>';
                            });
                        </script>
                        <?php
                        break;
                    }
                }
            }
        }

        /**
         *  Show master coupon info in coupon edit page
         *  @since 2.0.6
         */
        public function coupon_edit_page_master_coupon_info($post)
        {
            foreach($this->get_modules_with_master_coupon_option() as $module_id)
            {
                $the_options=Wt_Smart_Coupon::get_settings($module_id);
                $master_coupon_option_name = $this->get_master_coupon_option_name($module_id);

                if($the_options[$master_coupon_option_name]>0 && $post->ID === (int) $the_options[$master_coupon_option_name])
                {
                    $module_base = ucfirst(Wt_Smart_Coupon::get_module_base($module_id));

                    echo '<div class="notice notice-info notice-alt inline"><p>';
                    echo esc_html(__('Master coupon for', 'wt-smart-coupons-for-woocommerce-pro'). ' ' . str_replace('_', ' ', $module_base));
                    echo '</p></div>';

                    break;
                }
            }
        }


        /**
         *  Column title for `allowed emails` in coupon listing page. Column will be added just after `categories` column
         *  @since 2.0.6
         */
        public function add_coupon_allowed_email_column($columns)
        {
            $out=array();
            $email_column_title = __('Allowed email(s)', 'wt-smart-coupons-for-woocommerce-pro');
            $email_column_key = 'wt_sc_coupon_allowed_emails'; 

            foreach($columns as $column_key => $column_title)
            {
                $out[$column_key] = $column_title;
                if("coupon_categories" === $column_key)
                {
                    $out[$email_column_key] = $email_column_title;
                }           
            }
            
            if(!isset($out[$email_column_key])) 
            {
                $out[$email_column_key] = $email_column_title;
            }

            return $out;
        }

        /**
         *  Column content for `allowed emails` in coupon listing page.
         *  @since 2.0.6
         */
        public function add_coupon_allowed_email_column_content($column_name, $post_ID)
        {
            if('wt_sc_coupon_allowed_emails' == $column_name)
            {
                $coupon_obj = new WC_Coupon( $post_ID );
                $restrictions = $coupon_obj->get_email_restrictions();
                echo esc_html(is_array($restrictions) ? implode(", ", $restrictions) : ''); 
            }
        }
        

        /**
         *  Alter WP coupon search section to handle `coupons by email` search. 
         *  Search format - email:{email@example.com}
         *  
         *  @since 2.0.7
         */
        public function search_coupon_using_email($wp)
        {
            global $pagenow, $wpdb;
            
            if('edit.php' !== $pagenow || !isset($wp->query_vars['s']) || 'shop_coupon' !== $wp->query_vars['post_type'])
            {
                return;
            }
            
            $wp->query_vars['s'] = trim($wp->query_vars['s']);
            
            if('email:' === strtolower(substr($wp->query_vars['s'], 0, 6)))
            {
                $email = trim(substr($wp->query_vars['s'], 6));
                
                if(!$email)
                {
                    return;
                }

                $post_ids = $wpdb->get_col($wpdb->prepare("SELECT pm.post_id FROM {$wpdb->postmeta} AS pm LEFT JOIN {$wpdb->posts} AS p ON (p.ID = pm.post_id AND p.post_type = 'shop_coupon') WHERE pm.meta_key = 'customer_email' AND pm.meta_value LIKE %s", '%' . $wpdb->esc_like($email) . '%')); // WPCS: db call ok.
                
                if(empty($post_ids))
                {
                    return;
                } 
                
                unset($wp->query_vars['s'], $_REQUEST['s']); //prevent WP default search

                $wp->query_vars['post__in'] = $post_ids;
                $wp->query_vars['email'] = $email;
            }
        }


        /**
         *  Shows a progress message while migrating data from post table to lookup table
         *  
         *  @since 2.0.7
         */
        public function lookup_table_migration_message()
        {
            $migration_status = absint(get_option('wt_sc_coupon_lookup_updated', 0));
            $last_updated_id = absint(get_option('wt_sc_coupon_lookup_migration_last_id', 0));

            if(0 === $migration_status || 0 < $last_updated_id) //migration not started or in progress
            {
                ?>
                <div class="notice notice-info">
                    <p>
                        <h3><?php _e('Lookup table update in progress', 'wt-smart-coupons-for-woocommerce-pro');?></h3>
                        <p><?php echo sprintf(__('%sSmart Coupons for WooCommerce%s plugin is updating the database lookup table in the background. The site may experience a slower response time during the process. Please be patient.', 'wt-smart-coupons-for-woocommerce-pro'), '<b>', '</b>');?>
                        </p>
                        <p style="font-weight:bold;">
                            <?php
                            global $wpdb;
                            $row = $wpdb->get_row("SELECT COUNT(p.ID) AS total_records FROM {$wpdb->posts} AS p WHERE p.post_type = 'shop_coupon'", ARRAY_A);
                            $total = absint(!empty($row) && isset($row['total_records']) ? $row['total_records'] : 0);
                            $migrated = Wt_Smart_Coupon_Common::get_lookup_table_record_count();
                            echo sprintf(__('Progress: %d out of %d', 'wt-smart-coupons-for-woocommerce-pro'), $migrated, $total); ?>
                        </p>
                    </p>
                </div>
                <?php
            }

        }


        /**
         *  Search order by coupon - HPOS compatible
         *  Search format - coupon:{coupon_code}
         *  
         *  @since  2.0.8
         *  @param  array   $query_args     array of query arguments
         *  @return array   $query_args     array of query arguments
         */
        public function search_order_using_coupon_hpos($query_args)
        {
            if(Wt_Smart_Coupon_Common::is_hpos_orders_page() 
                && isset($query_args['s']) && "" !== trim($query_args['s']) 
                && 'coupon:' === strtolower(substr(trim($query_args['s']), 0, 7))
            )
            {
                $coupon_code = wc_sanitize_coupon_code(substr($query_args['s'], 7));

                if($coupon_code)
                {
                    global $wpdb; 

                    $order_ids = $wpdb->get_col($wpdb->prepare(
                        "SELECT order_id
                        FROM {$wpdb->prefix}woocommerce_order_items as order_items
                        WHERE order_item_name LIKE %s AND order_item_type = %s ",
                        '%'.$wpdb->esc_like(wc_clean($coupon_code)).'%', 'coupon'
                    ));

                    if(!empty($order_ids))
                    {
                        $query_args['id'] = $order_ids;
                    }else
                    {
                        $query_args['id'] = array(-1); //to empty the result
                    }

                    unset($query_args['s']); //unset it, otherwise it will perform string search
                }
            }

            return $query_args;
        }


        /**
         *  Update checkbox field data to postmeta.
         *  Checkbox fields have no post data when unchecked
         *  
         *  @since 2.0.9   
         *  @param $post_id         int         post/coupon id
         *  @param $field_name      string      post meta/form field key name  
         */
        public static function update_checkbox_field_post_meta($post_id, $field_name)
        {
            if(isset($_POST[$field_name]) && 'yes' === sanitize_text_field($_POST[$field_name]))
            {
                update_post_meta($post_id, $field_name, 'yes');
            }else
            {
                update_post_meta($post_id, $field_name, 'no');
            }
        }

        /**
         *  Migrate My coupons display 'used coupons', 'expired coupons' radio value to new checkbox in options.
         *  
         *  @since 2.4.0    
         */
        public static function my_account_coupon_additional_display_migration()
        {
            if( !Wt_Smart_Coupon::get_option( 'wbte_sc_my_coupons_display_migration' ) ){
                $additional_display = array();
                if( wc_string_to_bool( Wt_Smart_Coupon::get_option( 'display_used_coupons_my_account' ) ) ){
                    $additional_display[] = 'used_coupons';
                }
                if( wc_string_to_bool( Wt_Smart_Coupon::get_option( 'display_expired_coupons_my_account' ) ) ){
                    $additional_display[] = 'expired_coupons';
                }
                Wt_Smart_Coupon::update_option( 'wbte_sc_coupons_page_additional_display', $additional_display );

                Wt_Smart_Coupon::update_option( 'wbte_sc_my_coupons_display_migration', 1 );
            }
        }

        /**
         * 	Load the design system files and initiate it.
         * 	
         *  @since    3.0.0
         */
        public function include_design_system() {
            
            include_once plugin_dir_path( __FILE__ ) . 'wt-ds/class-wbte-ds.php';
            
            if( class_exists( 'Wbte\Sc\Ds\Wbte_Ds' ) ){
                /**
                 * Just initiate it. This is to load the CSS and JS.
                 */
                Wbte\Sc\Ds\Wbte_Ds::get_instance( WEBTOFFEE_SMARTCOUPON_VERSION ); 
            }
            
        }

        /**
         *  Delete non-existing coupons from the lookup table.
         * 
         *  @since 3.1.0
         */
        public function delete_coupon_from_lookup_table(){

            if( !get_option( 'wbte_sc_removed_non_existing_coupons_lookup_tb' ) ){

                $lookup_tb = Wt_Smart_Coupon::get_lookup_table_name();

                if ( Wt_Smart_Coupon::is_table_exists( $lookup_tb ) ) {

                    global $wpdb;
                    
                    $lookup_table_coupon_ids = $wpdb->get_col( "SELECT coupon_id FROM $lookup_tb" );
            
                    if ( ! empty( $lookup_table_coupon_ids ) ) {
                        
                        $placeholders = implode( ',', array_fill( 0, count( $lookup_table_coupon_ids ), '%d' ) );
            
                        // Query to find existing coupon IDs in wp_posts.
                        $existing_coupon_ids = $wpdb->get_col(
                            $wpdb->prepare(
                                "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'shop_coupon' AND ID IN ($placeholders)",
                                $lookup_table_coupon_ids
                            )
                        );
            
                        // Calculate IDs to remove (in custom table but not in wp_posts).
                        $ids_to_remove = array_diff( $lookup_table_coupon_ids, $existing_coupon_ids );
            
                        // Remove invalid coupon IDs from the custom table.
                        if ( ! empty( $ids_to_remove ) ) {
                            $placeholders = implode( ',', array_fill( 0, count( $ids_to_remove ), '%d' ) );
            
                            $wpdb->query(
                                $wpdb->prepare(
                                    "DELETE FROM $lookup_tb WHERE coupon_id IN ($placeholders)",
                                    $ids_to_remove
                                )
                            );
                        }
                    }

                    update_option( 'wbte_sc_removed_non_existing_coupons_lookup_tb', 1 );
                }
            }
           
        }
    }
}