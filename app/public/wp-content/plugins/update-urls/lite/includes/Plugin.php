<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the dashboard.
 *
 * @since      1.2
 *
 * @package    UpdateURLS
 * @subpackage UpdateURLS/includes
 */

namespace KaizenCoders\UpdateURLS;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, dashboard-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.2
 * @package    UpdateURLS
 * @subpackage UpdateURLS/includes
 * @author     KaizenCoders <hello@kaizencoders.com>
 */
class Plugin {

	/**
	 * @var Plugin $instance
	 */
	static $instance = null;

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.2
	 * @access   protected
	 * @var      Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.2
	 * @access   protected
	 * @var      string $UpdateURLS The string used to uniquely identify this plugin.
	 */
	protected $plugin_name = 'update-urls';

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.2
	 * @access   protected
	 * @var      string $version The current version of the plugin.
	 */
	protected $version = '1.2';

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.2
	 */
	public function __construct( $version = '' ) {
		$this->version = $version;
		$this->loader  = new Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.2
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new I18n();
		$plugin_i18n->set_domain( $this->get_plugin_name() );
		$plugin_i18n->load_plugin_textdomain();
	}

	public function is_pro() {
		return false;
	}

	/**
	 * Define constant
	 *
	 * @since 1.0.0
	 */
	public function define_constants() {

		$upload_dir = wp_upload_dir( null, false );

		if ( ! defined( 'KC_UU_LOG_DIR' ) ) {
			define( 'KC_UU_LOG_DIR', $upload_dir['basedir'] . '/kaizencoders_uploads/update-urls/logs/' );
		}

		if ( ! defined( 'KC_UU_UPLOADS_DIR' ) ) {
			define( 'KC_UU_UPLOADS_DIR', $upload_dir['basedir'] . '/kaizencoders_uploads/update-urls/uploads/' );
		}

		if ( ! defined( 'KC_UU_AJAX_SECURITY' ) ) {
			define( 'KC_UU_AJAX_SECURITY', 'UpdateURLS_ajax_request' );
		}

		if ( ! defined( 'KC_UU_ADMIN_TEMPLATES_DIR' ) ) {
			/* @const KC_UU_ADMIN_TEMPLATES_DIR */
			define( 'KC_UU_ADMIN_TEMPLATES_DIR', KC_UU_PLUGIN_DIR . 'lite/includes/Admin/Templates' );
		}
	}

	/**
	 * Register all the hooks related to the dashboard functionality
	 * of the plugin.
	 *
	 * @since    1.2
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Admin( $this );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );

		// Utilities.
		$this->loader->add_action( 'admin_print_scripts', $plugin_admin, 'handle_admin_notices' );
		$this->loader->add_filter( 'admin_footer_text', $plugin_admin, 'update_admin_footer_text' );
		$this->loader->add_action( 'in_plugin_update_message-update-urls/update-urls.php', $plugin_admin, 'in_plugin_update_message', 10, 2 );
	}

	/**
	 * Register all the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.2
	 * @access   private
	 */
	private function define_frontend_hooks() {

		$plugin_frontend = new Frontend( $this );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_frontend, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_frontend, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all the hooks with WordPress.
	 *
	 * Load the dependencies, define the locale, and set the hooks for the Dashboard and
	 * the public-facing side of the site.
	 *
	 * @since    1.2
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return    string    The name of the plugin.
	 * @since     1.2
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return    Loader    Orchestrates the hooks of the plugin.
	 * @since     1.2
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string    The version number of the plugin.
	 * @since     1.2
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Init Classes
	 *
	 * @since 1.2
	 */
	public function init_classes() {

		$classes = array(
			'KaizenCoders\UpdateURLS\Install',
			'KaizenCoders\UpdateURLS\Feedback',
			'KaizenCoders\UpdateURLS\Promo',
		);

		foreach ( $classes as $class ) {
			$this->loader->add_class( $class );
		}

	}

	public static function instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Plugin ) ) {

			self::$instance = new Plugin( KC_UU_PLUGIN_VERSION );

			self::$instance->define_constants();
			// self::$instance->load_dependencies();
			self::$instance->set_locale();
			self::$instance->define_admin_hooks();
			self::$instance->define_frontend_hooks();
			self::$instance->init_classes();

		}

		return self::$instance;
	}

}
