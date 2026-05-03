<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://shipsy.io/
 * @since      1.0.0
 *
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/includes
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
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/includes
 * @author     shipsyplugins <pradeep.mishra@shipsy.co.in>
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Shipsy_Econnect {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Shipsy_Econnect_Loader    $loader    Maintains and registers all hooks for the plugin.
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
		if ( defined( 'SHIPSY_ECONNECT_VERSION' ) ) {
			$this->version = SHIPSY_ECONNECT_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'shipsy-econnect';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Shipsy_Econnect_Loader. Orchestrates the hooks of the plugin.
	 * - Shipsy_Econnect_i18n. Defines internationalization functionality.
	 * - Shipsy_Econnect_Admin. Defines all hooks for the admin area.
	 * - Shipsy_Econnect_Public. Defines all hooks for the public side of the site.
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
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-shipsy-econnect-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-shipsy-econnect-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-shipsy-econnect-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-shipsy-econnect-public.php';

		$this->loader = new Shipsy_Econnect_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Shipsy_Econnect_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Shipsy_Econnect_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Shipsy_Econnect_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		// action hook for admin menu.
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'shipsy_config_menu' );

		// action hook for ajax endpoint helper apis.
		$this->loader->add_action( 'wp_ajax_shipsy_get_endpoint_url', $plugin_admin, 'ajax_endpoint_url_helper' );
		$this->loader->add_action( 'wp_ajax_shipsy_get_all_addresses', $plugin_admin, 'ajax_all_addresses_helper' );
		$this->loader->add_action( 'wp_ajax_shipsy_get_shipping_address', $plugin_admin, 'ajax_shipping_address_helper' );
		$this->loader->add_action( 'wp_ajax_on_sync_submit', $plugin_admin, 'shipsy_sync_submit' );
		$this->loader->add_action( 'wp_ajax_sync_result', $plugin_admin, 'shipsy_sync_result' );
		$this->loader->add_action( 'wp_ajax_pending_consignments_sync', $plugin_admin, 'ajax_get_pending_consignments' );
		$this->loader->add_action( 'wp_ajax_auto_sync_status_update', $plugin_admin, 'ajax_auto_sync_status_update' );
		$this->loader->add_action( 'wp_ajax_shipsy_download_label', $plugin_admin, 'ajax_shipsy_download_label' );

		$this->loader->add_action( 'admin_post_on_config_submit', $plugin_admin, 'shipsy_config_submit' );
		$this->loader->add_action( 'admin_post_on_setting_submit', $plugin_admin, 'shipsy_setting_submit' );
		$this->loader->add_action( 'admin_post_on_setup_submit', $plugin_admin, 'shipsy_setup_submit' );

		$this->loader->add_action( 'admin_head', $plugin_admin, 'shipsy_order_action_button_css' );
		$this->loader->add_filter( 'woocommerce_admin_order_actions', $plugin_admin, 'shipsy_order_action_button', 100, 2 );

		#Legacy
		$this->loader->add_filter( 'bulk_actions-edit-shop_order', $plugin_admin, 'track_manage_multiple_order', 20, 1 );
		$this->loader->add_filter( 'handle_bulk_actions-edit-shop_order', $plugin_admin, 'multiple_track_manage', 10, 3 );
		$this->loader->add_filter( 'manage_edit-shop_order_columns', $plugin_admin, 'wc_add_column');
		$this->loader->add_action( 'manage_shop_order_posts_custom_column', $plugin_admin, 'column_value_legacy');

		#HPOS
		$this->loader->add_filter( 'bulk_actions-woocommerce_page_wc-orders', $plugin_admin, 'track_manage_multiple_order', 20, 1 );
		$this->loader->add_filter( 'handle_bulk_actions-woocommerce_page_wc-orders', $plugin_admin, 'multiple_track_manage', 10, 3 );
		$this->loader->add_filter( 'manage_woocommerce_page_wc-orders_columns', $plugin_admin, 'wc_add_column');
		$this->loader->add_action( 'manage_woocommerce_page_wc-orders_custom_column', $plugin_admin, 'column_value', 10, 2 );


		$this->loader->add_action( 'admin_notices', $plugin_admin, 'notice_messages' );
		$this->loader->add_action( 'admin_head', $plugin_admin, 'shipsy_style_order_status_list' );
		$this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'shipsy_woocommerce_add_order_metabox' );

		// shipsy cron job actions and filters.
		$this->loader->add_filter( 'cron_schedules', $plugin_admin, 'shipsy_add_cron_interval' );
		$this->loader->add_action( 'init', $plugin_admin, 'shipsy_setup_cron' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Shipsy_Econnect_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'woocommerce_view_order', $plugin_public, 'display_tracking_info' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return    Shipsy_Econnect_Loader    Orchestrates the hooks of the plugin.
	 * @since     1.0.0
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}
