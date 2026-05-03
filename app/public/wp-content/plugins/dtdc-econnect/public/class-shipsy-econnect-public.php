<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://shipsy.io/
 * @since      1.0.0
 *
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Shipsy_Econnect
 * @subpackage Shipsy_Econnect/public
 * @author     shipsyplugins <pradeep.mishra@shipsy.co.in>
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Shipsy_Econnect_Public {

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
	 * Can change it on setting page
	 *
	 * @var bool
	 */
	public $use_track_button = true;


	/**
	 * Can change it on setting page
	 *
	 * @var bool
	 */
	public $custom_domain;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		require_once SHIPSY_ECONNECT_PATH . 'admin/helper/helper.php';
		require_once SHIPSY_ECONNECT_PATH . 'config/settings.php';

		$this->custom_domain = 'track.' . shipsy_slugify($ORGANISATION) . '.com'; // phpcs:ignore
		$this->plugin_name   = $plugin_name;
		$this->version       = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Shipsy_Econnect_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Shipsy_Econnect_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/shipsy-econnect-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Shipsy_Econnect_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Shipsy_Econnect_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/shipsy-econnect-public.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Get details of order saved in db.
	 *
	 * @param string $order_id The order id for which to fetch details.
	 * @return array|stdClass[]
	 */
	public function get_tracking_items( string $order_id ) {
		/**
		 * TODO: Cache db calls everywhere.
		 */
		$tracking_items = $this->get_tracking_items_from_db( $order_id );

		if ( is_array( $tracking_items ) ) {
			return $tracking_items;
		} else {
			return array();
		}
	}

	/**
	 * Function to fetch tracking details of order form db.
	 *
	 * @param string $order_id The order id for which to fetch details.
	 * @return array|object|stdClass[]|null
	 */
	public function get_tracking_items_from_db( string $order_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'sync_track_order';

		// phpcs:disable
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `$table_name` WHERE orderId=%s",
				$order_id
			)
		);
		// phpcs:enable
	}

	/**
	 * Display Shipment info in the frontend (order view/tracking page).
	 *
	 * @param string $order_id The order id for which to display tracking details.
	 * @returns void
	 */
	public function display_tracking_info( string $order_id ) {
		wc_get_template(
			'myaccount/view-order.php',
			array(
				'tracking_items'   => $this->get_tracking_items( $order_id ),
				'use_track_button' => $this->use_track_button,
				'domain'           => $this->custom_domain,
			),
			'',
			SHIPSY_ECONNECT_PATH . '/templates/'
		);
	}

}
