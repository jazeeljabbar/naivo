<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Advanced_Shipment_Tracking_Admin_Notice {

	/**
	 * Instance of this class.
	 *
	 * @var object Class Instance
	 */
	private static $instance;
	
	/**
	 * Initialize the main plugin function
	*/
	public function __construct() {
		$this->init();
	}

	/**
	 * List of shipping plugins
	 *
	 * @var array
	 */
	public $shipping_services = array(
		'woocommerce-shipstation-integration/woocommerce-shipstation.php' => 'ShipStation',
		'dokan-pro/dokan-pro.php' => 'Dokan',
		'woocommerce-services/woocommerce-services.php' => 'WooCommerce Services',
		'woocommerce-shipping-ups/woocommerce-shipping-ups.php' => 'WooCommerce Shipping',
		'ali2woo-lite/alinext-lite.php' => 'AliExpress Dropshipping',
		'woo-gls-print-label-and-tracking-code/wp-gls-print-label.php' => 'GLS Print Label',
		'woocommerce-gls/woocommerce-gls.php' => 'WooCommerce GLS by Tehster',
		'woocommerce-gls-premium/woocommerce-gls.php' => 'WooCommerce GLS by Tehster',
		'woocommerce-germanized/woocommerce-germanized.php' => 'WooCommerce Germanized',
	);
	
	/**
	 * Get the class instance
	 *
	 * @return WC_Advanced_Shipment_Tracking_Admin_Notice
	*/
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
	
	/*
	* init from parent mail class
	*/
	public function init() {
		
		add_action( 'admin_init', array( $this, 'ast_pro_notice_ignore_cb' ) );

		$page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';

		if ( 'woocommerce-advanced-shipment-tracking' != $page ) {
			// Shipping Integration Notice
			//add_action( 'admin_notices', array( $this, 'ast_admin_notice_shipping_integration' ) );
			
			// AST PRO Notice
			add_action( 'admin_notices', array( $this, 'ast_pro_admin_notice' ) );
			
			// Trackship Notice
			// add_action( 'admin_notices', array( $this, 'ast_pro_trackship_notice' ) );

			// AST free Review Notice
			//add_action( 'admin_notices', array( $this, 'ast_review_notice' ) );

			// Customer Info Notice
			//add_action( 'admin_notices', array( $this, 'customer_info_notice' ) );
		}

		// AST PRO Notice
		add_shortcode( 'ast_settings_admin_notice', array( $this, 'ast_settings_admin_notice' ) );
	}

	/**
	 * Check if any shipping service plugin is active
	 */
	public function is_any_shipping_plugin_active() {
		foreach ( $this->shipping_services as $plugin_file => $service_name ) {
			if ( is_plugin_active( $plugin_file ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Display admin notice for missing shipping integration
	 */
	public function ast_settings_admin_notice() {
		// Only show the notice if no shipping plugins are active AND it's before or on May 15, 2025
		ob_start();
		if ( $this->is_any_shipping_plugin_active() && strtotime('now') > strtotime('2025-05-15') ) {
			include 'views/admin_message_panel.php';
		} else if ( ! $this->is_any_shipping_plugin_active() ) {
			include 'views/admin_message_panel.php';
		}
		return ob_get_clean();
	}

	/*
	* Display admin notice on plugin install or update
	*/
	public function ast_pro_trackship_notice() {
		
		// Check if the date is past May 15, 2025
		if ( strtotime( 'now' ) > strtotime( '2025-05-15' ) ) {
			return;
		}
		
		if ( function_exists( 'trackship_for_woocommerce' ) ) {
			return;
		}
		
		if ( get_option('ts4wc_notice_ignore_381') ) {
			return;
		}
		
		if ( !get_option('ast_pro_update_ignore_382') ) {
			return;
		}
		
		$nonce = wp_create_nonce('ast_pro_dismiss_notice');
		$dismissable_url = esc_url(add_query_arg(['ts4wc-notice' => 'true', 'nonce' => $nonce]));

		?>
		<style>
		.wp-core-ui .notice.trackship-dismissable-notice{
			position: relative;
			padding-right: 38px;
			border-left-color: #005B9A;
		}
		.wp-core-ui .notice.trackship-dismissable-notice h3{
			margin-bottom: 5px;
		} 
		.wp-core-ui .notice.trackship-dismissable-notice a.notice-dismiss{
			padding: 9px;
			text-decoration: none;
		} 
		.wp-core-ui .button-primary.trackship_notice_btn {
			background: #005B9A;
			color: #fff;
			border-color: #005B9A;
			text-transform: uppercase;
			padding: 0 11px;
			font-size: 12px;
			height: 30px;
			line-height: 28px;
			margin: 5px 0 15px;
		}
		.trackship-dismissable-notice strong{
			font-weight:bold;
		}
		</style>
		<div class="notice updated notice-success trackship-dismissable-notice">
			<a href="<?php esc_html_e( $dismissable_url ); ?>" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></a>
			<h2>🚀 Provide seamless experience from Shipping to Delivery! 🎉</h2>
			<p><a href="https://wordpress.org/plugins/trackship-for-woocommerce/" target="_blank">TrackShip for WooCommerce</a> automates tracking from shipping to delivery, saving you time and reducing customer service costs:</p>
			<ul>
				<li>🔹Automatically track all shipments and keep customers informed.</li>
				<li>🔹Create a seamless branded tracking experience to boost engagement.</li>
				<li>🔹Reduce "Where is my order?" support tickets with real-time updates.</li>
			</ul>
			<p><strong>Special Offer:</strong> Get <strong>50% OFF</strong> your first three months with coupon code <strong>TRACKSHIP503M</strong>(Valid until April 15)</p>
			<a class="button-primary trackship_notice_btn" target="blank" href="https://my.trackship.com/settings/#billing">Upgrade Now & Save 50%</a>
			<a class="button-primary trackship_notice_btn" href="<?php esc_html_e( $dismissable_url ); ?>">Dismiss</a>
		</div>	
		<?php
	}

	/*
	* Dismiss admin notice for trackship
	*/
	public function ast_pro_notice_ignore_cb() {
		if ( isset( $_GET['ts4wc-notice'] ) ) {
			if (isset($_GET['nonce'])) {
				$nonce = sanitize_text_field($_GET['nonce']);
				if (wp_verify_nonce($nonce, 'ast_pro_dismiss_notice')) {
					update_option('ts4wc_notice_ignore_381', 'true');
				}
			}
		}

		if ( isset( $_GET['ast-pro-update-notice'] ) ) {
			if (isset($_GET['nonce'])) {
				$nonce = sanitize_text_field($_GET['nonce']);
				if (wp_verify_nonce($nonce, 'ast_pro_dismiss_notice')) {
					update_option('ast_pro_update_ignore_382', 'true');
				}
			}
		}

		if ( isset( $_GET['integration-pro-ignore'] ) ) {
			if (isset($_GET['nonce'])) {
				$nonce = sanitize_text_field($_GET['nonce']);
				if (wp_verify_nonce($nonce, 'ast_pro_dismiss_notice')) {
					update_option('integration_notice_ignore_377', 'true');
				}
			}
		}

		if ( isset( $_GET['ast-review-ignore'] ) ) {
			if (isset($_GET['nonce'])) {
				$nonce = sanitize_text_field($_GET['nonce']);
				if (wp_verify_nonce($nonce, 'ast_pro_dismiss_notice')) {
					update_option('ast_review_notice_ignore_378', 'true');
				}
			}
		}

		if ( isset( $_GET['cutomer-info-notice'] ) ) {
			if (isset($_GET['nonce'])) {
				$nonce = sanitize_text_field($_GET['nonce']);
				if (wp_verify_nonce($nonce, 'ast_pro_dismiss_notice')) {
					update_option('customer_info_notice_ignore_379', 'true');
				}
			}
		}
	}

	/*
	* Display admin notice on plugin install or update
	*/
	public function ast_pro_admin_notice() {
		
		if ( get_option('ast_pro_update_ignore_382') ) {
			return;
		}
		
		$nonce = wp_create_nonce('ast_pro_dismiss_notice');
		$dismissable_url = esc_url(add_query_arg(['ast-pro-update-notice' => 'true', 'nonce' => $nonce]));

		?>
		<style>		
		.wp-core-ui .notice.ast-dismissable-notice{
			position: relative;
			padding-right: 38px;
			border-left-color: #005B9A;
		}
		.wp-core-ui .notice.ast-dismissable-notice h3{
			margin-bottom: 5px;
		} 
		.wp-core-ui .notice.ast-dismissable-notice a.notice-dismiss{
			padding: 9px;
			text-decoration: none;
		} 
		.wp-core-ui .button-primary.ast_notice_btn {
			background: #005B9A;
			color: #fff;
			border-color: #005B9A;
			text-transform: uppercase;
			padding: 0 11px;
			font-size: 12px;
			height: 30px;
			line-height: 28px;
			margin: 5px 0 0px;
		}
		.ast-dismissable-notice strong{
			font-weight:bold;
		}
		</style>
		<div class="notice updated notice-success ast-dismissable-notice">
			<a href="<?php esc_html_e( $dismissable_url ); ?>" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></a>
			<h2>🚀 Upgrade to AST PRO & Automate Your Shipping Workflow!</h2>
			<p>Streamline your fulfillment process with Advanced Shipment Tracking PRO and save hours on daily shipping tasks. Automate tracking, integrate with 20+ shipping services, and manage all shipments from a centralized dashboard—right inside WooCommerce.</p>
			
			<p><strong>🎉 Get 20% Off*!</strong> Use code <strong>ASTPRO20</strong> at checkout.</p>
			<a class="button-primary ast_notice_btn" target="blank" href="https://www.zorem.com/product/woocommerce-advanced-shipment-tracking/">Upgrade Now</a>
			<a class="button-primary ast_notice_btn" href="<?php esc_html_e( $dismissable_url ); ?>">Dismiss</a>
			<p><strong>★</strong> for new customers only</p>
		</div>
		<?php
	}

	/**
	 * Display admin notice on plugin install or update
	 */
	public function ast_admin_notice_shipping_integration() {

		// Check if the date is past May 15, 2025
		if ( strtotime( 'now' ) > strtotime( '2025-05-15' ) ) {
			return;
		}
		if ( get_option( 'integration_notice_ignore_377' ) || !$this->is_any_shipping_plugin_active() ) {
			return;
		}

		foreach ( $this->shipping_services as $plugin_file => $service_name) {
			if ( is_plugin_active( $plugin_file ) ) {
				$this->display_ast_pro_notice( $service_name );
				break; // Show only one notice
			}
		}
	}

	/**
	 * Display AST PRO upgrade notice
	 */
	private function display_ast_pro_notice( $service_name ) {
		// Check if the date is past May 15, 2025
		if ( strtotime( 'now' ) > strtotime( '2025-05-15' ) ) {
			return;
		}
		
		$nonce = wp_create_nonce('ast_pro_dismiss_notice');
		$dismissable_url = esc_url(add_query_arg(['integration-pro-ignore' => 'true', 'nonce' => $nonce]));

		?>
		<style>
		.wp-core-ui .notice.ast-dismissable-notice {
			position: relative;
			padding-right: 38px;
			border-left-color: #005B9A;
		}
		.wp-core-ui .notice.ast-dismissable-notice h3 {
			margin-bottom: 5px;
		}
		.wp-core-ui .notice.ast-dismissable-notice a.notice-dismiss {
			padding: 9px;
			text-decoration: none;
		}
		.wp-core-ui .button-primary.ast_intigration_btn {
			background: #005B9A;
			color: #fff;
			border-color: #005B9A;
			text-transform: uppercase;
			padding: 0 11px;
			font-size: 12px;
			height: 30px;
			line-height: 28px;
			margin: 5px 0 15px;
		}
		.ast-dismissable-notice strong{
			font-weight: bold;
		}
		</style>
		<div class="notice updated notice-success ast-dismissable-notice">
			<a href="<?php esc_html_e($dismissable_url); ?>" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></a> 
			<h3><strong>🚀 Automate Your WooCommerce Order Fulfillment with AST PRO! 🎉</strong></h3>
			<p>You are using <strong><?php esc_html($service_name); ?></strong>, and with <strong>AST PRO</strong> you can streamline your order fulfillment process by automatically adding tracking information and marking orders as fulfilled—saving you time and effort.</p>
			<ul>
				<li>✅ Auto-add tracking details to orders</li>
				<li>✅ Mark orders as fulfilled instantly</li>
				<li>✅ Eliminate manual updates & reduce errors</li>
			</ul>
			<p>🔥 Limited-Time Offer: Use code <strong>ASTPRO30</strong> to save 30% on your upgrade!</p>
			<p>⏳ Hurry! Offer valid until <strong>May 15, 2025.</strong></p>
			<a href="https://www.zorem.com/product/woocommerce-advanced-shipment-tracking/" class="button button-primary ast_intigration_btn">👉 Upgrade Now</a>
			<a class="button-primary ast_intigration_btn" href="<?php esc_html_e($dismissable_url); ?>">Not interested</a>
		</div>
		<?php
	}

	/*
	* Display admin notice on plugin install or update
	*/
	public function ast_review_notice() {
		if ( get_option('ast_review_notice_ignore_378') ) {
			return;
		}
		
		$nonce = wp_create_nonce('ast_pro_dismiss_notice');
		$dismissable_url = esc_url(add_query_arg(['ast-review-ignore' => 'true', 'nonce' => $nonce]));

		?>
		<style>		
		.wp-core-ui .notice.ast-review-dismissable-notice{
			position: relative;
			padding-right: 38px;
			border-left-color: #005B9A;
			padding-bottom: 10px;
			margin-bottom: 5px;
		}
		.wp-core-ui .notice.ast-review-dismissable-notice h3{
			margin-bottom: 5px;
		} 
		.wp-core-ui .notice.ast-review-dismissable-notice a.notice-dismiss{
			padding: 9px;
			text-decoration: none;
		} 
		.wp-core-ui .button-primary.ast_review_notice_btn {
			background: #005B9A;
			color: #fff;
			border-color: #005B9A;
			text-transform: uppercase;
			padding: 0 11px;
			font-size: 12px;
			height: 30px;
			line-height: 28px;
			margin: 5px 0 0px;
		}
		.ast-review-dismissable-notice strong{
			font-weight:bold;
		}
		</style>
		<div class="notice updated notice-success ast-review-dismissable-notice">
			<a href="<?php esc_html_e( $dismissable_url ); ?>" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></a>
			<h2>⭐ Enjoying AST? Leave Us a Review!</h2>
			<p>We hope <strong>Advanced Shipment Tracking</strong> has improved your order fulfillment workflow! Your feedback helps us grow and continue improving the plugin.</p>
			<p>If you love using AST, we’d really appreciate it if you could take a moment to leave us a <strong>5-star review.</strong> It helps us keep improving and providing the best experience for you!</p>
			<p><strong>👍 Support AST & Share Your Experience!</strong></p>
			<a class="button-primary ast_review_notice_btn" target="blank" href="https://wordpress.org/support/plugin/woo-advanced-shipment-tracking/reviews/#new-post">LEAVE A REVIEW</a>
			<a class="button-primary ast_review_notice_btn" href="<?php esc_html_e( $dismissable_url ); ?>">Dismiss</a>
		</div>
		<?php
	}

	/*
	* Display admin notice on plugin install or update
	*/
	public function customer_info_notice() {

		if ( get_option('customer_info_notice_ignore_379') ) {
			return;
		}

		$nonce = wp_create_nonce('ast_pro_dismiss_notice');
		$dismissable_url = esc_url(add_query_arg(['cutomer-info-notice' => 'true', 'nonce' => $nonce]));

		?>
		<style>		
		.wp-core-ui .notice.ast-dismissable-notice{
			position: relative;
			padding-right: 38px;
			border-left-color: #005B9A;
		}
		.wp-core-ui .notice.ast-dismissable-notice h3{
			margin-bottom: 5px;
		} 
		.wp-core-ui .notice.ast-dismissable-notice a.notice-dismiss{
			padding: 9px;
			text-decoration: none;
		} 
		.wp-core-ui .button-primary.cf_notice_btn {
			background: #005B9A;
			color: #fff;
			border-color: #005B9A;
			text-transform: uppercase;
			padding: 0 11px;
			font-size: 12px;
			height: 30px;
			line-height: 28px;
			margin: 5px 0 10px;
		}
		.ast-dismissable-notice strong{
			font-weight:bold;
		}
		</style>
		<div class="notice updated notice-success ast-dismissable-notice">
			<a href="<?php esc_html_e( $dismissable_url ); ?>" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></a>
			<h2>👀 Instantly See Everything About Your Customers – In One Place!</h2>
			<p>Check out our new Customer Info plugin – instantly access order history, subscriptions, support tickets, and more, all in one place inside WooCommerce.</p>

			<a class="button-primary cf_notice_btn" target="blank" href="https://woocommerce.com/products/customer-info/">👉 Learn More</a>
			<a class="button-primary cf_notice_btn" href="<?php esc_html_e( $dismissable_url ); ?>">Dismiss</a>
		</div>
		<?php
	}

}
