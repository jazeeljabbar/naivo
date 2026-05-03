<?php
/**
 * Fired during plugin activation
 *
 * @link       http://www.webtoffee.com
 * @since      1.0.0
 *
 * @package    Wt_Smart_Coupon
 * @subpackage Wt_Smart_Coupon/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Wt_Smart_Coupon
 * @subpackage Wt_Smart_Coupon/includes
 * @author     Webtoffee <info@webtoffee.com>
 */

if( ! class_exists ( 'Wt_Smart_Coupon_Activator' ) ) {
	class Wt_Smart_Coupon_Activator {

		/**
		 * Run immediately on plugin actuvation
		 *
		 * Check Woocommerce is activated, check is basic version is activated,
		 * enable woocommerce coupon settings if it disabled
		 *
		 * @since    1.0.0
		 */
		public static function activate() {
			if ( ! class_exists( 'WooCommerce' ) ) {
				deactivate_plugins( WT_SMARTCOUPON_BASE_NAME );
				wp_die(__("Oops! Woocommerce not activated..", 'wt-smart-coupons-for-woocommerce-pro'), "", array('back_link' => 1));
				
			}
			
			/**
			 *	Enable woocommmerce coupon settings
			 *	@since 1.2.9
			 */
			update_option( 'woocommerce_enable_coupons', 'yes' );

			/**
		     *	Install necessary tables
		     *	@since 2.0.6
		     */
			Wt_Smart_Coupon::install_tables();

			$is_existing_user = get_option( 'wt-smart-coupon-for-woo' );
			if ( ! $is_existing_user ) {
				
				//Activate new BOGO as default.
				update_option( 'wbte_sc_new_bogo_actvated', true );
			}

			do_action( 'after_wt_smart_coupon_for_woocommerce_is_activated' );
			update_option( 'wbte_sc_activation_hook_version', WEBTOFFEE_SMARTCOUPON_VERSION );			
			
		}

	}
}