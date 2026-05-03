<?php
/*
  Plugin Name: WooCommerce Plugin for PayUmoney
  Plugin URI: http://wapinfosolutions.com/woocommerce-payu
  Description: Extends WooCommerce. Provides a <a href="http://www.payu.in/">PayU India</a> gateway for WooCommerce.
  Version: 1.0
  Author: wapinfosystems
  Author URI: http://wapinfosolutions.com
  Requires at least: 3.8
  Tested up to: 4.8
 */


// Init PayU IN Gateway after WooCommerce has loaded
add_action('plugins_loaded', 'init_payu_money_gateway', 0);

/**
 * init_payu_money_gateway function.
 *
 * @description Initializes the gateway.
 * @access public
 * @return void
 */
function init_payu_money_gateway() {
    // If the WooCommerce payment gateway class is not available, do nothing
    if (!class_exists('WC_Payment_Gateway'))
        return;

    // Localization
    load_plugin_textdomain('woocommerce_payu_money', false, dirname(plugin_basename(__FILE__)) . '/languages');

    require_once( plugin_basename('classes/payu_money.class.php') );

    add_filter('woocommerce_payment_gateways', 'add_payu_money_gateway');

    /**
     * add_gateway()
     *
     * Register the gateway within WooCommerce.
     *
     * @since 1.0.0
     */
    function add_payu_money_gateway($methods) {
        $methods[] = 'WCPM_Gateway_Payu_Money';
        return $methods;
    }

}

// Add the Indian Rupee to the currency list
add_filter('woocommerce_currencies', 'wcpm_add_indian_rupee');

function wcpm_add_indian_rupee($currencies) {
    $currencies['INR'] = __('Indian Rupee', 'woocommerce_payu_money');
    return $currencies;
}

add_filter('woocommerce_currency_symbol', 'wcpm_add_indian_rupee_currency_symbol', 10, 2);

function wcpm_add_indian_rupee_currency_symbol($currency_symbol, $currency) {
    switch ($currency) {
        case 'INR': $currency_symbol = 'Rs.';
            break;
    }
    return $currency_symbol;
}
