<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              
 * @since             1.0.0
 * @package           Wt_Smart_Coupon
 *
 * @wordpress-plugin
 * Plugin Name:       Smart Coupons for WooCommerce Pro
 * Plugin URI:        
 * Description:       Implement add-on coupon features to elevate sales in your WooCommerce Store.
 * Version:           3.2.0
 * Author:            WebToffee
 * Author URI:        https://www.webtoffee.com/
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       wt-smart-coupons-for-woocommerce-pro
 * Domain Path:       /languages
 * WC tested up to:   9.6
 * Requires Plugins:  woocommerce
 */
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
if (!defined('WEBTOFFEE_SMARTCOUPON_VERSION')) {
    define('WEBTOFFEE_SMARTCOUPON_VERSION', '3.2.0');
}

if (!defined('WT_SMARTCOUPON_FILE_NAME')) {
    define('WT_SMARTCOUPON_FILE_NAME', __FILE__);
}

if (!defined('WT_SMARTCOUPON_BASE_NAME')) {
    define('WT_SMARTCOUPON_BASE_NAME', plugin_basename(__FILE__));
}

if (!defined('WT_SMARTCOUPON_MAIN_PATH')) {
    define('WT_SMARTCOUPON_MAIN_PATH', plugin_dir_path(__FILE__));
}

if (!defined('WT_SMARTCOUPON_URL')) {
    define('WT_SMARTCOUPON_URL', plugin_dir_url(__FILE__) );
}
if (!defined('WT_SMARTCOUPON_INSTALLED_VERSION')) { 
    define('WT_SMARTCOUPON_INSTALLED_VERSION', 'PREMIUM');
}

/**
 * Changelog in plugins page
 *  
 * @since 3.1.0
 * @param array $data An array of plugin metadata.
 */
function wbte_sc_update_message( $data ) {
    if ( isset( $data['upgrade_notice'] ) ) {
        add_action( 'admin_print_footer_scripts', 'wbte_sc_plugin_screen_update_notice_js' );
        $msg = str_replace( array( '<p>', '</p>' ), array( '<div>', '</div>' ), $data['upgrade_notice'] );

        echo '<style type="text/css">
        #wt-smart-coupon-pro-update .update-message p:last-child{ display:none;}     
        #wt-smart-coupon-pro-update ul{ list-style:disc; margin-left:30px;}
        .wt_sc_update_message{ padding-left:30px;}
        </style>
        <div class="update-message wt_sc_update_message">' . wp_kses_post( wpautop( $msg ) ) . '</div>';
    }
}

/**
 * Javascript code for changelog in plugins page
 *  
 * @since 3.1.0
 */
function wbte_sc_plugin_screen_update_notice_js() {   
    global $pagenow;
    if ( 'plugins.php' != $pagenow ) {
        return;
    }
    ?>
    <script>
        ( function( $ ){
            var update_dv=$('#wt-smart-coupon-pro-update');
            update_dv.find('.wt_sc_update_message').next('p').remove();
            update_dv.find('a.update-link:eq(0)').on('click', function(){
                $('.wt_sc_update_message').remove();
            });
        })( jQuery );
    </script>
    <?php
}
add_action( 'in_plugin_update_message-wt-smart-coupon-pro/wt-smart-coupon-pro.php', 'wbte_sc_update_message' );

/** @since 1.3.5 */
if (!defined('WT_SC_PLUGIN_NAME'))
{
    define('WT_SC_PLUGIN_NAME','wt-smart-coupon-for-woo');
    define('WT_SC_PLUGIN_ID','wt_smart_coupon_for_woo');
    define('WT_SC_SETTINGS_FIELD', WT_SC_PLUGIN_NAME); /* option name to store settings */
    define('WT_SC_ACTIVATION_ID','wtsmartcoupon'); 
    define('WT_SC_EDD_ACTIVATION_ID','196729'); 
}

include_once ABSPATH.'wp-admin/includes/plugin.php';

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wt-smart-coupon-activator.php
 */
function activate_wt_smart_coupon_pro() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-wt-smart-coupon-activator.php';
    Wt_Smart_Coupon_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wt-smart-coupon-deactivator.php
 */
function deactivate_wt_smart_coupon_pro() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-wt-smart-coupon-deactivator.php';
    Wt_Smart_Coupon_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_wt_smart_coupon_pro');
register_deactivation_hook(__FILE__, 'deactivate_wt_smart_coupon_pro');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-wt-smart-coupon.php';

if ( ! class_exists( 'WooCommerce' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    deactivate_plugins( WT_SMARTCOUPON_BASE_NAME );
    wp_die(__("Oops! Woocommerce not activated, It should required for Smart coupon for woocommerce.", 'wt-smart-coupons-for-woocommerce-pro'), "", array('back_link' => 1));
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wt_smart_coupon_pro() {

    $plugin = Wt_Smart_Coupon::get_instance();
    $plugin->run();

}

/**
 *  Declare compatibility WooCommerce features.
 * 
 *  @since 2.0.8    Compatibility with custom order tables for WooCommerce
 *  @since 2.3.0    Compatibility with cart/checkout blocks 
 *  
 */
add_action(
    'before_woocommerce_init',
    function () {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) 
        && interface_exists( '\Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface' )
        ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
        }
    }
);

run_wt_smart_coupon_pro();