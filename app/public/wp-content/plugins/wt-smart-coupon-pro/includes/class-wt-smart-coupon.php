<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://www.webtoffee.com
 * @since      1.0.0
 *
 * @package    Wt_Smart_Coupon
 * @subpackage Wt_Smart_Coupon/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Wt_Smart_Coupon
 * @subpackage Wt_Smart_Coupon/includes
 * @author     WebToffee <info@webtoffee.com>
 */
if( ! class_exists ( 'Wt_Smart_Coupon' ) ) {
	class Wt_Smart_Coupon {

		/**
		 * The loader that's responsible for maintaining and registering all hooks that power
		 * the plugin.
		 *
		 * @since    1.0.0
		 * @access   protected
		 * @var      Wt_Smart_Coupon_Loader    $loader    Maintains and registers all hooks for the plugin.
		 */
		protected $loader;

		/**
		 * The unique identifier of this plugin.
		 *
		 * @since    1.0.0
		 * @access   protected
		 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
		 */
		protected $plugin_name;

		/**
		 * The current version of the plugin.
		 *
		 * @since    1.0.0
		 * @access   protected
		 * @var      string    $version    The current version of the plugin.
		 */
		protected $version;

		protected $plugin_base_name = WT_SMARTCOUPON_BASE_NAME;

		private static $stored_options=array();

		public static $no_image="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=";
		
		public $plugin_admin=null;
		public $plugin_common=null;
		public $plugin_public=null;

		private static $instance = null;

		/**
		 * Define the core functionality of the plugin.
		 *
		 * Set the plugin name and the plugin version that can be used throughout the plugin.
		 * Load the dependencies, define the locale, and set the hooks for the admin area and
		 * the public-facing side of the site.
		 *
		 * @since    1.0.0
		 */
		public function __construct() {
				
			if ( defined( 'WEBTOFFEE_SMARTCOUPON_VERSION' ) ) {
				$this->version = WEBTOFFEE_SMARTCOUPON_VERSION;
			} else {
				$this->version = '3.2.0';
			}
			$this->plugin_name = WT_SC_PLUGIN_NAME;

			$this->load_dependencies();
			$this->set_locale();
			$this->define_common_hooks();
			$this->define_admin_hooks();
			$this->define_public_hooks();

		}

		/**
         * Get Instance
         * @since 1.3.5
         */
        public static function get_instance()
        {
            if(self::$instance==null)
            {
                self::$instance=new Wt_Smart_Coupon();
            }

            return self::$instance;
        }

		/**
		 * Load the required dependencies for this plugin.
		 *
		 * Include the following files that make up the plugin:
		 *
		 * - Wt_Smart_Coupon_Loader. Orchestrates the hooks of the plugin.
		 * - Wt_Smart_Coupon_i18n. Defines internationalization functionality.
		 * - Wt_Smart_Coupon_Admin. Defines all hooks for the admin area.
		 * - Wt_Smart_Coupon_Public. Defines all hooks for the public side of the site.
		 *
		 * Create an instance of the loader which will be used to register the hooks
		 * with WordPress.
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		private function load_dependencies() {

			/**
			 * The class responsible for orchestrating the actions and filters of the
			 * core plugin.
			 */
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wt-smart-coupon-loader.php';

			/**
			 * Webtoffee Security Library
			 * Includes Data sanitization, Access checking
			 */
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wt-security-helper.php';
			/**
			 * Webtoffee Language Functions
			 * Includes functions to manage translations
			 */

			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wt-multi-languages.php';

			/**
			 * The class responsible for defining internationalization functionality
			 * of the plugin.
			 */
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wt-smart-coupon-i18n.php';

			
			/**
			 * @since 1.3.5
			 * The class responsible for defining all actions common to admin/public.
			 */
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'common/class-wt-smart-coupon-common.php';

			/**
			 * The class responsible for defining all actions that occur in the admin area.
			 */
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wt-smart-coupon-admin.php';


			/**
			 * The class responsible for defining all actions that occur in the public-facing
			 * side of the site.
			 */
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wt-smart-coupon-public.php';
			
			require_once plugin_dir_path( dirname( __FILE__ ) ) .'public/class-myaccount-smart-coupon.php';
			
			/**
			 * @since 2.0.8
			 * 
			 * The class responsible for handling review seeking banner side of the site.
			 */
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wt-smart-coupon-review_request.php';


			/**
			 * @since 2.3.0
			 * 
			 * This file is responsible for handling all the block related operations of the plugin.
			 */
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'blocks/wt-sc-blocks.php';

			
			$this->loader = new Wt_Smart_Coupon_Loader();
		}

		/**
		 * Define the locale for this plugin for internationalization.
		 *
		 * Uses the Wt_Smart_Coupon_i18n class in order to set the domain and to register the hook
		 * with WordPress.
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		private function set_locale() {

			$plugin_i18n = new Wt_Smart_Coupon_i18n();

			$this->loader->add_action( 'init', $plugin_i18n, 'load_plugin_textdomain' );

		}

		/**
		 * Register all of the hooks related to the admin/public area functionality
		 * of the plugin.
		 *
		 * @since    1.3.5
		 * @access   private
		 */
		private function define_common_hooks() {

			$this->plugin_common= Wt_Smart_Coupon_Common::get_instance( $this->get_plugin_name(), $this->get_version() );
			$this->plugin_common->register_modules();

			 /**
	         *  @since 2.0.1
	         *  Smart coupon plugin common hook on order status change
	         */
			$this->loader->add_action('woocommerce_order_status_changed', $this->plugin_common, 'on_order_status_change', 19, 4);

			

			/**
			 * Coupon lookup table
			 * @since 2.0.6
			 */		
			//Insert existing coupon data to lookup table
			$this->loader->add_action('init', $this->plugin_common, 'update_existing_coupon_data_to_lookup_table', 10);

			//Update lookup table on coupon object save
			$this->loader->add_action('woocommerce_after_data_object_save', $this->plugin_common, 'update_coupon_lookup_on_object_save', 1000, 2);

			//Update lookup table on coupon meta data save
			$this->loader->add_action('woocommerce_process_shop_coupon_meta', $this->plugin_common, 'update_coupon_lookup_on_meta_save', 1000, 2);

			//Update lookup table on coupon usage count change
			$this->loader->add_action('woocommerce_increase_coupon_usage_count', $this->plugin_common, 'update_coupon_lookup_on_usage_count_change', 1000, 3);
			$this->loader->add_action('woocommerce_decrease_coupon_usage_count', $this->plugin_common, 'update_coupon_lookup_on_usage_count_change', 1000, 3);

			//Update lookup table on post meta update
			$this->loader->add_action('updated_post_meta', $this->plugin_common, 'update_coupon_lookup_on_postmeta_change', 1000, 4);
			$this->loader->add_action('added_post_meta', $this->plugin_common, 'update_coupon_lookup_on_postmeta_change', 1000, 4);
			$this->loader->add_action('deleted_post_meta', $this->plugin_common, 'update_coupon_lookup_on_postmeta_change', 1000, 4);

			//Update lookup table on post status update
			$this->loader->add_action('transition_post_status', $this->plugin_common, 'update_coupon_lookup_on_post_status_change', 1000, 3);


			/**
			 * 	Check and update lookup table. Priority number must be lower, because data updation hook must fire after this one
			 * 	@since 2.0.7
			 */
			$this->loader->add_action('init', $this->plugin_common, 'check_and_update_lookup_table', 1);


			/**
	         *  Register the messages that are customizable via admin panel
	         *  @since 2.0.8
	         */
	        $this->loader->add_filter('wt_sc_intl_add_notifications', $this->plugin_common, 'register_customized_texts');


	        /**
	         *  Time based coupon expiry compatibility for `WebToffee Gift cards` plugin
	         *  @since 2.0.8
	         */
	        $this->loader->add_filter('wt_gc_alter_store_credit_expiry', $this->plugin_common, 'store_credit_expiry_for_gift_cards_plugin', 10, 2);

			$this->loader->add_action( 'wbte_sc_after_coupon_generated_meta_added', $this->plugin_common, 'update_coupon_display_after_meta_updated' );

			/**
			 *  Trigger after activation hook if not triggered
			 * 
			 *  @since 2.4.0
			 */
			$this->loader->add_action( 'admin_init', $this->plugin_common, 'check_and_trigger_activation_action_hook' );


			/**
			 * 	@since 2.4.3
			 * 	Delete coupon row from coupon lookup table when coupon permenantly deleted.
			 */
			$this->loader->add_action( 'delete_post', $this->plugin_common, 'coupon_delete_from_lookup_table_when_deleted', 10, 2 );

			/**
			 * 	@since 3.2.0
			 * 	Use translation files from wp-content/languages/plugins/wt-smart-coupon-pro/
			 */
			$this->loader->add_action( 'init', $this->plugin_common, 'use_translation_files_from_wp_content_languages' );

	        
		}

		/**
		 * Register all of the hooks related to the admin area functionality
		 * of the plugin.
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		private function define_admin_hooks() {

			$this->plugin_admin = Wt_Smart_Coupon_Admin::get_instance( $this->get_plugin_name(), $this->get_version() );

			$this->loader->add_filter( 'wt_smart_coupons_alter_role_access', $this->plugin_admin,'wt_sc_alter_user_roles',10,1  );
			$this->loader->add_action('admin_enqueue_scripts', $this->plugin_admin, 'enqueue_styles',10,0 );
			$this->loader->add_action('admin_enqueue_scripts', $this->plugin_admin, 'enqueue_scripts',10,0 );
			$this->loader->add_filter('plugin_action_links_' . $this->get_plugin_base_name(), $this->plugin_admin, 'add_plugin_links_wt_smartcoupon');
			$this->loader->add_filter('woocommerce_coupon_data_tabs', $this->plugin_admin, 'admin_coupon_options_tabs', 20, 1);
			$this->loader->add_action('woocommerce_coupon_data_panels', $this->plugin_admin, 'admin_coupon_options_panels', 10, 0);
			$this->loader->add_action('webtoffee_coupon_metabox_customer',$this->plugin_admin, 'admin_coupon_metabox_customer', 10, 2);
			$this->loader->add_action('woocommerce_process_shop_coupon_meta', $this->plugin_admin, 'process_shop_coupon_meta', 10, 2);
			$this->loader->add_filter( 'views_edit-shop_coupon', $this->plugin_admin, 'smart_coupons_views_row');
			$this->loader->add_action('woocommerce_coupon_options', $this->plugin_admin,'add_new_coupon_options',10,2);
			$this->loader->add_filter( 'woocommerce_screen_ids', $this->plugin_admin,'add_wc_screen_id',10,1  );

			// Coupon with product @since 1.1.0
            $this->loader->add_action( 'wp_ajax_wt_json_search_coupons', $this->plugin_admin, 'wt_json_search_coupons'  );

            $this->loader->add_filter('woocommerce_email_styles',$this->plugin_admin, 'coupon_inline_style', 1, 1);

			
			/**
			* 	Ajax hook for saving settings, Includes plugin main settings and settings from module
			* 	@since 1.3.5
			*/
			$this->loader->add_action('wp_ajax_wt_sc_save_settings', $this->plugin_admin, 'save_settings');


			/**
			* 	Smart coupon settings button on coupons page
			* 	@since 1.3.5
			*/
			$this->loader->add_action('admin_head-edit.php', $this->plugin_admin, 'coupon_page_settings_button');

			/** 
			*	Initiate admin modules 
			* 	@since 1.3.5
			*/
			$this->plugin_admin->register_modules();

			/** 
			*	Admin menu 
			* 	@since 1.3.5
			*/
			$this->loader->add_action('admin_menu', $this->plugin_admin, 'admin_menu',11); 

			/** 
			*	Tooltips 
			* 	@since 1.3.5
			*/
			$this->loader->add_action('init', $this->plugin_admin, 'register_tooltips', 11);

			/**
			 *  Help links meta box
			 * 	@since 1.3.5
			 */
			$this->loader->add_action("add_meta_boxes", $this->plugin_admin, "help_links_meta_box");
		

			//saving hook for debug tab
			$this->loader->add_action('admin_init', $this->plugin_admin, 'debug_save');	

			/**
			 * 	Column for used coupons in order listing page
			 * 	@since 2.0.5
			 *  @since 2.0.8   Added HPOS Compatibility
			 */
			$this->loader->add_filter('manage_edit-shop_order_columns', $this->plugin_admin, 'add_order_used_coupon_column', 10, 1);
			$this->loader->add_filter('manage_woocommerce_page_wc-orders_columns', $this->plugin_admin, 'add_order_used_coupon_column', 10, 1); //HPOS
			$this->loader->add_action('manage_shop_order_posts_custom_column', $this->plugin_admin, 'add_order_used_coupon_column_content', 10, 2);
			$this->loader->add_action('manage_woocommerce_page_wc-orders_custom_column', $this->plugin_admin, 'add_order_used_coupon_column_content', 10, 2); //HPOS

			/**
	         *  @since 2.0.5
	         *  Saving new coupon count
	         */
			$this->loader->add_action('wp_insert_post', $this->plugin_admin, 'save_created_coupon_count', 10, 3);

			
			/**
			 * 	Column for used in orders in coupon listing page
			 * 	@since 2.0.6
			 *  @since 2.0.8   Added HPOS Compatibility
			 */
			$this->loader->add_filter('manage_edit-shop_coupon_columns', $this->plugin_admin, 'add_coupon_used_in_order_column', 10, 1);
			$this->loader->add_action('manage_shop_coupon_posts_custom_column', $this->plugin_admin, 'add_coupon_used_in_order_column_content', 10, 2);
			$this->loader->add_action('parse_request', $this->plugin_admin, 'search_order_using_coupon');
			$this->loader->add_filter('woocommerce_shop_order_list_table_prepare_items_query_args', $this->plugin_admin, 'search_order_using_coupon_hpos'); //HPOS


			/**
			 * 	Alter the master coupon status based on module settings
			 * 	Show master coupon info in coupon listing and edit page
			 * 	Prevent publishing master coupon
			 * 	@since 2.0.6
			 */
			$this->loader->add_action('wt_sc_intl_before_setting_update', $this->plugin_admin, 'settings_before_update_to_alter_master_coupon_status', 10, 2);
			$this->loader->add_action('wt_sc_intl_after_setting_update', $this->plugin_admin, 'settings_after_update_to_alter_master_coupon_status', 10, 2);		
			$this->loader->add_filter('manage_shop_coupon_posts_custom_column', $this->plugin_admin, 'coupon_list_page_master_coupon_info', 11, 2);
			$this->loader->add_action('save_post_shop_coupon', $this->plugin_admin, 'prevent_master_coupon_from_publishing', 11, 3);
			$this->loader->add_action('admin_enqueue_scripts', $this->plugin_admin, 'alter_publish_button_for_master_coupon');
			$this->loader->add_action('post_submitbox_misc_actions', $this->plugin_admin, 'coupon_edit_page_master_coupon_info');

			/**
			 * 	Column for allowed emails in coupon listing page
			 * 	@since 2.0.6
			 */
			$this->loader->add_filter('manage_edit-shop_coupon_columns', $this->plugin_admin, 'add_coupon_allowed_email_column', 10, 1);
			$this->loader->add_action('manage_shop_coupon_posts_custom_column', $this->plugin_admin, 'add_coupon_allowed_email_column_content', 10, 2);
			

			/**
			 *  Search coupons using email
			 * 	
			 *	@since 2.0.7
			 */
			$this->loader->add_action('parse_request', $this->plugin_admin, 'search_coupon_using_email');


			/**
			 *  Lookup table migration in progress message
			 * 	
			 * 	@since 2.0.7
			 */
			$this->loader->add_action("admin_notices", $this->plugin_admin, "lookup_table_migration_message");

			/**
			 *  My account coupons additional display migration
			 * 	
			 * 	@since 2.4.0
			 */
			$this->loader->add_action( "after_wt_smart_coupon_for_woocommerce_is_activated", $this->plugin_admin, "my_account_coupon_additional_display_migration" );

			/**
			 * Show the GDPR promotion banner, if the class `Wt_Gdpr_Promotion_banner` not exists, and the revamped and legacy gdpr plugins are not active.
			 * 
			 * @since 3.0.0
			 */
			if ( ! class_exists( 'Wt_Gdpr_Promotion_banner' ) && !is_plugin_active( 'webtoffee-cookie-consent/webtoffee-cookie-consent.php' ) && !is_plugin_active( 'webtoffee-gdpr-cookie-consent/cookie-law-info.php' ) ) {
				require_once plugin_dir_path( __DIR__ ) . 'admin/modules/banner/class-wt-gdpr-promotion-banner.php';
			}

			/**
			 *  Include Design System file.
			 * 
			 * 	@since 3.0.0
			 */
			$this->loader->add_action( "admin_init", $this->plugin_admin, "include_design_system" );

			/**
			 *  Delete coupon in lookup table that doesn't exist in post table..
			 * 
			 * 	@since 3.1.0
			 */
			$this->loader->add_action( "after_wt_smart_coupon_for_woocommerce_is_activated", $this->plugin_admin, "delete_coupon_from_lookup_table" );
		}

		/**
		 * Register all of the hooks related to the public-facing functionality
		 * of the plugin.
		 *
		 * @since    1.0.0
		 * @since    2.0.8 	Apply coupon on click ajax event changed from wp_ajax to wc_ajax
		 * @access   private
		 */
		private function define_public_hooks()
		{
			$this->plugin_public = Wt_Smart_Coupon_Public::get_instance($this->get_plugin_name(), $this->get_version());
			$this->loader->add_action( 'wp_enqueue_scripts', $this->plugin_public, 'enqueue_styles' );
			$this->loader->add_action( 'wp_enqueue_scripts', $this->plugin_public, 'enqueue_scripts' );
			$this->loader->add_action('woocommerce_after_cart_table',$this->plugin_public,'display_available_coupon_in_cart');
			$this->loader->add_action('woocommerce_before_checkout_form',$this->plugin_public,'display_available_coupon_in_checkout'); 

			/** 
			 * @since 2.0.8 Changed from wp_ajax to wc_ajax 
			 */
			$this->loader->add_action('wc_ajax_apply_coupon_on_click', $this->plugin_public, 'apply_coupon'); 

			$this->loader->add_action('woocommerce_order_details_after_order_table',$this->plugin_public,'add_coupon_details_with_order',10,1 );
			$this->loader->add_action('woocommerce_email_after_order_table',$this->plugin_public,'add_coupon_details_with_order_email',10,4 );


			/** 
			*	Compatibility with `Advanced Dynamic Pricing for WooCommerce - By AlgolPlus` 
			* 	@since 2.0.4
			*/
			$this->loader->add_filter('wdp_calculate_totals_hook_priority', $this->plugin_public, 'alter_advanced_dynamic_pricing_plugin_calculate_totals_hook_priority', 100000, 1);	

			/** 
			*	Initiate public modules 
			* 	@since 2.0.0
			*/
			$this->plugin_public->register_modules();


			/** 
			 * 	Set checkout values on block checkout
			 * 	
			 * 	@since 2.3.0
			 */
			$this->loader->add_action( 'wc_ajax_wbte_sc_set_block_checkout_values', $this->plugin_public, 'set_block_checkout_values' );

			/**
			 *  Add 'available coupon in block cart/checkout' blocks data
			 * 
			 * 	@since 3.2.0
			 */
			$this->loader->add_filter( 'wbte_sc_alter_blocks_data', $this->plugin_public, 'add_coupon_blocks_data' );

		}

		/**
         *  Register modules    
         *  @since 1.3.5     
         *  @since 2.0.4 Disables admin/public modules if dependend common module is not active    
         *  @since 2.0.8 Must use modules introduced    
         */
		public static function register_modules($modules, $module_option_name, $module_path, &$existing_modules, $mu_modules)
		{
			$wt_sc_modules=get_option($module_option_name);
            if($wt_sc_modules===false)
            {
                $wt_sc_modules=array();
            }

            $is_not_common_module=false;
            if(strpos($module_option_name, '_public_')!==false || strpos($module_option_name, '_admin_')!==false) //current modules is admin/public
            {
            	$is_not_common_module=true;
            }

            foreach ($modules as $module) //loop through module list and include its file
            {
                $is_active=1;
                if(isset($wt_sc_modules[$module]))
                {
                    $is_active = absint($wt_sc_modules[$module]); //checking module status
                }else
                {
                    $wt_sc_modules[$module]=1; //add it to module list, default status is active
                }

                if(in_array($module, $mu_modules)) //must use modules
                {
                	$is_active = 1;
                }

                $module_file=$module_path."modules/$module/$module.php";
                if(file_exists($module_file) && 1 === $is_active)
                {
                	$include_module_file=true;
                	if($is_not_common_module)
                	{
                		/* Common modules: module entry exists and module not active. So do not include the current public/admin module files */
                		if(in_array($module, Wt_Smart_Coupon_Common::$modules) && !Wt_Smart_Coupon_Common::module_exists($module))
                		{
                			$include_module_file=false;
                		}
                	}
                	if($include_module_file)
                	{
                    	$existing_modules[]=$module; //this is for module_exits checking
                    	require_once $module_file;
                    }
                }
            }
            
            $out=array();
            foreach($wt_sc_modules as $k=>$m) //remove non existing module info from DB
            {
                if(in_array($k, $modules))
                {
                    $out[$k]=$m;
                }
            }
            update_option($module_option_name, $out);

		}

		/**
		 * Run the loader to execute all of the hooks with WordPress.
		 *
		 * @since    1.0.0
		 */
		public function run() {
			$this->loader->run();
		}

		public function get_plugin_name() {
			return $this->plugin_name;
		}

		/**
		 * The reference to the class that orchestrates the hooks with the plugin.
		 *
		 * @since     1.0.0
		 * @return    Wt_Smart_Coupon_Loader    Orchestrates the hooks of the plugin.
		 */
		public function get_loader() {
			return $this->loader;
		}


		public function get_version() {
			return $this->version;
		}
			
		public function get_plugin_base_name() {
			return $this->plugin_base_name;
		}
		public static function wt_sc_is_woocommerce_prior_to($version) {
			$woocommerce_is_pre_version = (!defined('WC_VERSION') || version_compare(WC_VERSION, $version, '<')) ? true : false;
			return $woocommerce_is_pre_version;
		}
		/**
		* Used to flush the rewrite rules once the plugin is activated
		*
		* @since  1.3.2
		* @access public
		* @throws Exception Error message.
		*/
		public static function wt_smartcoupon_check_if_flushed_rules() {
			$wt_sc_check_flushed_rules = get_option( 'wt_sc_flush_rules', 'false' );
			if ( 'false' === $wt_sc_check_flushed_rules ) {
				flush_rewrite_rules();
				update_option( 'wt_sc_flush_rules', 'true', 'no' );
			}
		}

		/**
		 * Generate tab head for settings page.
		 * method will translate the string to current language
		 * @since     1.3.5
		 */
		public static function generate_settings_tabhead($title_arr, $type="plugin")
		{	
			$out_arr=apply_filters("wt_sc_".$type."_settings_tabhead", $title_arr);
			foreach($out_arr as $k=>$v)
			{			
				if(is_array($v))
				{
					$v=(isset($v[2]) ? $v[2] : '').$v[0].' '.(isset($v[1]) ? $v[1] : '');
				}
			?>
				<a class="nav-tab" href="#<?php echo esc_attr($k);?>"><?php echo wp_kses_post($v); ?></a>
			<?php
			}
		}

		/**
         *  Migrate old settings, If exists
         */
        protected static function migrate_settings($settings)
        {
            $smart_coupon_option = get_option( 'wt_smart_coupon_options' );
            if(
            	isset($smart_coupon_option['wt_copon_general_settings']) 
            	&& !empty($smart_coupon_option['wt_copon_general_settings']) /* old data exists */
            ) 
            {
            	$old_settings=$smart_coupon_option['wt_copon_general_settings'];
            	$settings['wt_account_endpoint']=isset($old_settings['wt_account_endpoint']) ? $old_settings['wt_account_endpoint'] : $settings['wt_account_endpoint'];
            	$settings['wt_endpoint_title']=isset($old_settings['wt_endpoint_title']) ? $old_settings['wt_endpoint_title'] : $settings['wt_endpoint_title'];
            	$settings['display_used_coupons_my_account']=isset($old_settings['display_used_coupons_my_acount']) ? $old_settings['display_used_coupons_my_acount'] : $settings['display_used_coupons_my_account'];
            	$settings['display_expired_coupons_my_account']=isset($old_settings['display_expired_coupons_my_acount']) ? $old_settings['display_expired_coupons_my_acount'] : $settings['display_expired_coupons_my_account'];
            	$settings['wt_coupon_prefix']=isset($old_settings['wt_coupon_prefix']) ? $old_settings['wt_coupon_prefix'] : $settings['wt_coupon_prefix'];
            	$settings['wt_coupon_suffix']=isset($old_settings['wt_coupon_suffix']) ? $old_settings['wt_coupon_suffix'] : $settings['wt_coupon_suffix'];
            	$settings['wt_coupon_length']=isset($old_settings['wt_coupon_lenght']) ? $old_settings['wt_coupon_lenght'] : $settings['wt_coupon_length'];
            	$settings['no_of_characters_for_bulk_generate']=isset($old_settings['no_of_characters_for_bulk_generate']) ? $old_settings['no_of_characters_for_bulk_generate'] : $settings['no_of_characters_for_bulk_generate'];
            	$settings['email_coupon_for_order_status']=isset($old_settings['email_coupon_for_order_status']) ? $old_settings['email_coupon_for_order_status'] : $settings['email_coupon_for_order_status'];
                
                Wt_Smart_Coupon::update_settings($settings); /* update old settings */
                
                //remove old option
                unset($smart_coupon_option['wt_copon_general_settings']);
                update_option('wt_smart_coupon_options', $smart_coupon_option);
            }
        }

		/**
		 * Get default settings
		 * @since     1.3.5
		 */
		public static function default_settings($base_id='')
		{
			$settings=array(
				'wt_account_endpoint'        				  => 'wt-smart-coupon',
				'wt_endpoint_title'        					  => __('My Coupons', 'wt-smart-coupons-for-woocommerce-pro'),
				'display_used_coupons_my_account'       	  => true,
                'display_expired_coupons_my_account'    	  => false,
                'wt_coupon_prefix'                      	  => '',
                'wt_coupon_suffix'                      	  => '',
                'wt_coupon_length'                      	  => 12,
                'no_of_characters_for_bulk_generate'   		  => 12,
                'only_display_cart_valid_coupons'       	  => 'no', /** @since 2.1.0 */
				'wbte_sc_enable_coupons_page'				  => 'yes', /** @since 2.4.0 */
				'wbte_sc_coupons_page_additional_display'	  => array( 'used_coupons' ),
				'wbte_sc_enable_myaccount_storecredit_page'   => 'yes',
				'wbte_account_storecredit_endpoint'			  => 'wt-store-credit',
				'wbte_account_storecredit_page_title' 	  =>  __( 'My Store Credits', 'wt-smart-coupons-for-woocommerce-pro' ),
				'wbte_account_storecredit_additional_display' => array( 'used_coupons' )
			);

			self::migrate_settings($settings); /* migrate old settings. If exists */

			if($base_id!='')
			{
				$settings=apply_filters('wt_sc_module_default_settings', $settings, $base_id);
			}
			return $settings;
		}

		/**
		 * Get current settings.
		 * @since     1.3.5
		 */
		public static function get_settings($base_id='')
		{ 
			$settings=self::default_settings($base_id);
			$option_name=($base_id=="" ? WT_SC_SETTINGS_FIELD : $base_id);
			$option_id=($base_id=="" ? 'main' : $base_id); //to store in the stored option variable
			$current_settings=get_option($option_name, array());
			if(!empty($current_settings)) 
			{
				foreach($settings as $setting_key=>$setting)
				{
					if(isset($current_settings[$setting_key]))
					{
						if(is_array($setting) && self::is_assoc_arr($setting)) /* may be sub setting */
						{
							$settings[$setting_key]=wp_parse_args($current_settings[$setting_key], $settings[$setting_key]);

						}else{	/* assumes not a sub setting */						
							$settings[$setting_key]=$current_settings[$setting_key];						
						}
					}
				}
			}
			//stripping escape slashes
			$settings=self::arr_stripslashes($settings);
			$settings=apply_filters('wt_sc_alter_settings', $settings, $base_id);
			return $settings;
		}


		/**
		 * Update current settings.
		 * @param $base_id  Module id
		 * @since     1.3.5
		 */
		public static function update_settings($the_options, $base_id='')
		{
			if($base_id!="" && $base_id!='main') //main is reserved so do not allow modules named main
			{
				self::$stored_options[$base_id]=$the_options;
				update_option($base_id, $the_options);
			}
			if($base_id=="")
			{
				self::$stored_options['main']=$the_options;
				update_option(WT_SC_SETTINGS_FIELD, $the_options);
			}
		}

		/**
		 * Update option value,
		 * @since     1.3.5
		 * @return mixed
		 */
		public static function update_option($option_name, $value, $base='')
		{
			$the_options=self::get_settings($base);
			$the_options[$option_name]=$value;
			self::update_settings($the_options,$base);
		}

		/**
		 * Get option value, move the option to common option field if it was individual
		 * @since  1.3.5
		 * @return mixed
		 */
		public static function get_option($option_name, $base='', $the_options=null)
		{
			if(is_null($the_options))
			{
				$the_options=self::get_settings($base);
			}
			$vl=isset($the_options[$option_name]) ? $the_options[$option_name] : false;
			$vl=apply_filters('wt_sc_alter_option',$vl,$the_options,$option_name,$base);
			return $vl;
		}

		public static function get_module_id($module_base)
		{
			return WT_SC_PLUGIN_NAME.'_'.$module_base;
		}
		
		/**
		*	@since 1.3.5
		*	Get module base from module id
		*/
		public static function get_module_base($module_id)
		{
			if(strpos($module_id, WT_SC_PLUGIN_NAME.'_')!==false) //valid module ID
			{
				return str_replace(WT_SC_PLUGIN_NAME.'_', '', $module_id);
			}
			return false;
		}

		public static function is_assoc_arr($arr)
		{
			return array_keys($arr)!==range(0, count($arr)-1);
		}

		/**
		 * Strip slashes
		 * @since     1.3.5
		 */
		protected static function arr_stripslashes($arr)
		{
			if(is_array($arr) || is_object($arr))
			{
				foreach($arr as &$arrv)
				{
					$arrv=self::arr_stripslashes($arrv);
				}
				return $arr;
			}else
			{
				return stripslashes($arr);
			}
		}

		/**
	     *	Install necessary tables
	     *	@since 2.0.6
	     */
	    public static function install_tables()
	    {
	        global $wpdb;   
	        require_once ABSPATH.'wp-admin/includes/upgrade.php';       
	        
	        if(is_multisite()) 
	        {
	            // Get all blogs in the network and activate plugin on each one
	            $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
	            
	            foreach($blog_ids as $blog_id) 
	            {
	                switch_to_blog($blog_id);
	                self::install_lookup_table();
	                restore_current_blog();
	            }

	        }else 
	        {
	            self::install_lookup_table();
	        }   
	    }

	    /**
	     * 	Get lookup table name
	     * 	@since 2.0.6
	     */
	    public static function get_lookup_table_name()
	    {
	    	global $wpdb;
	    	return esc_sql( $wpdb->prefix . 'wt_sc_coupon_lookup' );
	    }

	    public static function is_table_exists($table_name)
	    {
	    	global $wpdb;
	    	return $wpdb->get_results($wpdb->prepare("SHOW TABLES LIKE %s", '%'.$table_name.'%'), ARRAY_N); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	    }

	    /**
	     * 	Create lookup table for saving coupon data
	     * 	@since 2.0.6
	     */
	    public static function install_lookup_table()
	    {
	    	global $wpdb;
	        $table_name = self::get_lookup_table_name();
	        require_once ABSPATH.'wp-admin/includes/upgrade.php';
	        
	        if(!self::is_table_exists($table_name))  
	        {
	            $sql_qry = "CREATE TABLE IF NOT EXISTS {$table_name} (
	              `id` bigint(20) NOT NULL AUTO_INCREMENT,
	              `coupon_id` bigint(20) NOT NULL DEFAULT '0',
	              `is_auto_coupon` int(11) NOT NULL DEFAULT '0',
	              `auto_coupon_priority` bigint(20) NOT NULL DEFAULT '0',
	              `my_account_display` int(11) NOT NULL DEFAULT '0',
	              `cart_display` int(11) NOT NULL DEFAULT '0',
	              `checkout_display` int(11) NOT NULL DEFAULT '0',
				  `post_status` varchar(100) NOT NULL,
				  `email_restriction` text NOT NULL,
				  `user_roles` text NOT NULL,
				  `exclude_user_roles` text NOT NULL,
				  `expiry` varchar(100) NOT NULL,
				  `discount_type` varchar(100) NOT NULL,
				  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
				  `usage_limit` bigint(20) NOT NULL DEFAULT '0',
				  `usage_count` bigint(20) NOT NULL DEFAULT '0',
				  `usage_limit_per_user` int(11) NOT NULL DEFAULT '0',
				  `is_wt_gc_wallet_coupon` int(11) NOT NULL DEFAULT '0',
	              PRIMARY KEY(`id`),
	              INDEX `COUPON_ID`(`coupon_id`)
	            ) DEFAULT CHARSET=utf8;";

	            dbDelta($sql_qry);

	            self::update_lookup_table_version();
	        }else
	        {
	        	if(self::get_lookup_table_version() > self::get_installed_lookup_table_version()) //new version available
            	{

		        	if(2 > self::get_installed_lookup_table_version()) // Lesser than 2 so, add update added in version 2.
		        	{
			        	$search_query = "SHOW COLUMNS FROM `$table_name` LIKE 'exclude_user_roles'";
				        
				        if(!$wpdb->get_results($search_query, ARRAY_N)) 
				        {
				        	$wpdb->query("ALTER TABLE {$table_name} ADD `exclude_user_roles` TEXT NOT NULL AFTER `user_roles`");
				        }
				    }

				    if ( 3 > self::get_installed_lookup_table_version() ) { /** @since 2.3.0 	Lesser than 3 so, add the new columns in version 3. */	        	
			        	$search_query = "SHOW COLUMNS FROM `$table_name` LIKE 'auto_coupon_priority'";		        
				        if ( ! $wpdb->get_results( $search_query, ARRAY_N ) ) {
				        	$wpdb->query( "ALTER TABLE {$table_name} ADD `auto_coupon_priority` bigint(20) NOT NULL DEFAULT '0' AFTER `is_auto_coupon`" );
				        }
				    }


				    // future version updates will come here.....

				    //finally update the version number to latest
			    	self::update_lookup_table_version();
			    }
	        }

	        if(!self::is_table_exists($table_name))
	        {
	        	deactivate_plugins(WT_SMARTCOUPON_BASE_NAME);
	        	wp_die(sprintf(__("An error occurred while activating %sSmart Coupons for WooCommerce Pro%s: Unable to create database table. %s", "wt-smart-coupons-for-woocommerce-pro"), '<b>', '</b>', $table_name), "", array('link_url' => admin_url('plugins.php'), 'link_text' => __('Go to plugins page', 'wt-smart-coupons-for-woocommerce-pro') ));
	        }
	    }

	    /**
	     * 	Installed version of lookup table
	     * 
	     * 	@since 2.0.7
	     * 	@return int 	installed lookup table version
	     */
	    public static function get_installed_lookup_table_version()
	    {
	    	return absint(get_option('wt_sc_coupon_lookup_version', 1));
	    }

	    /**
	     * 	Update lookup table version to latest
	     * 
	     * 	@since 2.0.7
	     */
	    public static function update_lookup_table_version()
	    {
	    	update_option('wt_sc_coupon_lookup_version', self::get_lookup_table_version());
	    }

	    /**
	     * 	New lookup table version for the plugin
	     * 
	     * 	@since 2.0.7
	     * 	@since 2.3.0 	Lookup table version 3
	     * 	@return int 	new lookup table version
	     */
	    public static function get_lookup_table_version()
	    {
	    	return 3;
	    }
	}
}