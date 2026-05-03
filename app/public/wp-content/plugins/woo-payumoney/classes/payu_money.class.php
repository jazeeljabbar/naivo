<?php

class WCPM_Gateway_Payu_Money extends WC_Payment_Gateway {

    /**
     * __construct function.
     *
     * @since 1.0.0
     * @access public
     * @return void
     */
    public function __construct() {
        global $woocommerce;


        $this->id = 'wcpm_payu_money';
        $this->method_title = __('PayUMoney', 'woocommerce_payu_money');
        $this->icon = $this->wcpm_plugin_url() . '/assets/images/icon.png';
        $this->has_fields = true;
        $this->liveurl = 'https://secure.payu.in/_payment';
        $this->testurl = 'https://test.payu.in/_payment';

        // Load the form fields.
        $this->wcpm_init_form_fields();

        // Load the settings.
        $this->init_settings();

        // Check if the currency is set to INR. If not we disable the plugin here.
        if (get_option('woocommerce_currency') == 'INR') {
            $payu_money_enabled = $this->settings['enabled'];
        } else {
            $payu_money_enabled = 'no';
        } // End check currency

        $this->enabled = $payu_money_enabled;
        $this->title = $this->settings['title'];
        $this->description = $this->settings['description'];
        $this->merchantid = $this->settings['merchantid'];
        $this->salt = $this->settings['salt'];        
        $this->testmode = $this->settings['testmode'];

        // IPN
        if (isset($_GET['payu_money_callback']) && esc_attr($_GET['payu_money_callback']) == '1') {
            $this->wcpm_check_transaction_status();
        }

        // Receipt
        add_action('woocommerce_receipt_wcpm_payu_money', array($this, 'wcpm_receipt_page'));

        /* 1.6.6 */
        add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));

        /* 2.0.0 */
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

// End Constructor

    /**
     * Initialise Gateway Settings Form Fields
     *
     * @since 1.0.0
     */
    function wcpm_init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce_payu_money'),
                'type' => 'checkbox',
                'label' => __('Enable PayUMoney', 'woocommerce_payu_money'),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __('Title', 'woocommerce_payu_money'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce_payu_money'),
                'default' => __('PayUMoney', 'woocommerce_payu_money')
            ),
            'description' => array(
                'title' => __('Description', 'woocommerce_payu_money'),
                'type' => 'textarea',
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce_payu_money'),
                'default' => __('Direct payment via PayUMoney. PayUMoney accepts VISA, MasterCard, Debit Cards and the Net Banking of all major banks.', 'woocommerce_payu_money'),
            ),
            'merchantid' => array(
                'title' => __('Merchant Key', 'woocommerce_payu_money'),
                'type' => 'text',
                'description' => __('This key is generated at the time of activation of your site and helps to uniquely identify you to PayUMoney', 'woocommerce_payu_money'),
                'default' => ''
            ),
            'salt' => array(
                'title' => __('SALT', 'woocommerce_payu_money'),
                'type' => 'text',
                'description' => __('String of characters provided by PayUMoney', 'woocommerce_payu_money'),
                'default' => ''
            ),
            'testmode' => array(
                'title' => __('Test Mode', 'woocommerce_payu_money'),
                'type' => 'checkbox',
                'label' => __('Enable PayUMoney Test Mode', 'woocommerce_payu_money'),
                'default' => 'no'
            )
        );
    }

// End wcpm_init_form_fields()

    /**
     * Get the plugin URL
     *
     * @since 1.0.0
     */
    function wcpm_plugin_url() {
        if (isset($this->plugin_url))
            return $this->plugin_url;

        if (is_ssl()) {
            return $this->plugin_url = str_replace('http://', 'https://', WP_PLUGIN_URL) . '/' . plugin_basename(dirname(dirname(__FILE__)));
        } else {
            return $this->plugin_url = WP_PLUGIN_URL . '/' . plugin_basename(dirname(dirname(__FILE__)));
        }
    }

// End wcpm_plugin_url()

    /**
     * Admin Panel Options
     * - Options for bits like 'title' and availability on a country-by-country basis
     *
     * @since 1.0.0
     */
    public function wcpm_admin_options() {
        ?>
        <h3><?php _e('PayUMoney', 'woocommerce_payu_money'); ?></h3>
        <p><?php _e('PayUMoney works by sending the user to <a href="https://www.payumoney.com/">PayUMoney</a> to enter their payment information. Note that PayUMoney will only take payments in Indian Rupee.', 'woocommerce_payu_money'); ?></p>
        <?php
        if (get_option('woocommerce_currency') == 'INR') {
            ?>
            <table class="form-table">
                <?php
                // Generate the HTML For the settings form.
                $this->generate_settings_html();
                ?>
            </table><!--/.form-table-->
            <?php
        } else {
            ?>
            <div class="inline error"><p><strong><?php _e('Gateway Disabled', 'woocommerce_payu_money'); ?></strong> <?php echo sprintf(__('Choose Indian Rupee (Rs.) as your store currency in <a href="%s">Pricing Options</a> to enable the PayUMoney Gateway.', 'woocommerce_payu_money'), admin_url('?page=woocommerce&tab=general')); ?></p></div>
            <?php
        } // End check currency
    }

// End wcpm_admin_options()

    /**
     * Add fields to pre-select method of payment
     *
     * @since 1.4
     */
    function wcpm_payment_fields() {
        if ($this->description) {
            echo wpautop(wptexturize($this->description));
        }
    }

// End wcpm_payment_fields()

    /**
     * Generate the PayU India button link.
     *
     * @since 1.0.0
     */
    function wcpm_generate_payu_money_form($order_id) {
        global $woocommerce;
        $order = new WC_Order($order_id);

        //$productinfo = sprintf( __( 'Order #%s from ', 'woocommerce_payu_money' ), $order_id ) . get_bloginfo( 'name' );
        $productinfo = "productinfo";
        //Hash data
        $hash_data['key'] = $this->merchantid;
        $hash_data['txnid'] = substr(hash('sha256', mt_rand() . microtime()), 0, 20); // Unique alphanumeric Transaction ID
        $hash_data['amount'] = number_format($order->order_total, 2, '.', '');
        $hash_data['productinfo'] = $productinfo;
        $hash_data['firstname'] = $order->billing_first_name;
        $hash_data['email'] = $order->billing_email;
        $hash_data['udf1'] = $order_id;
        $hash_data['hash'] = $this->wcpm_calculate_hash_before_transaction($hash_data);


        update_post_meta($order_id, '_order_txnid', $hash_data['txnid']);

        // PayU Args
        $payu_money_args = array(
            // Merchant details
            'key' => $this->merchantid,
            'surl' => add_query_arg('payu_money_callback', 1, $this->get_return_url($order)),
            'furl' => add_query_arg(array('payu_money_callback' => 1, 'payu_money_status' => 'failed'), $this->get_return_url($order)),
            'curl' => add_query_arg(array('payu_money_callback' => 1, 'payu_money_status' => 'cancelled'), $this->get_return_url($order)),
            // Customer details
            'firstname' => $order->billing_first_name,
            'lastname' => $order->billing_last_name,
            'email' => $order->billing_email,
            'address1' => $order->billing_address_1,
            'address2' => $order->billing_address_2,
            'city' => $order->billing_city,
            'state' => $order->billing_state,
            'zipcode' => $order->billing_postcode,
            'country' => $order->billing_country,
            'phone' => $order->billing_phone,
            'service_provider' => 'payu_paisa',
            // Item details
            'productinfo' => $productinfo,
            'amount' => number_format($order->order_total, 2, '.', ''),
            // Pre-selection of the payment method tab
            'pg' => $this->wcpm_get_get_var('pg'),
            'udf1' => $order_id
        );

        $payuform = '';

        foreach ($payu_money_args as $key => $value) {
            if ($value) {
                $payuform .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />' . "\n";
            }
        }


        $payuform .= '<input type="hidden" name="txnid" value="' . $hash_data['txnid'] . '" />' . "\n";
        $payuform .= '<input type="hidden" name="hash" value="' . $hash_data['hash'] . '" />' . "\n";

        // Get the right URL in case the test mode is enabled
        $posturl = $this->liveurl;
        if ($this->testmode == 'yes') {
            $posturl = $this->testurl;
        }


        // The form
        return '<form action="' . $posturl . '" method="POST" name="payform" id="payform">
				' . $payuform . '
				<input type="submit" class="button" id="submit_payu_money_payment_form" value="' . __('Pay via PayU', 'woocommerce_payu_money') . '" /> <a class="button cancel" href="' . $order->get_cancel_order_url() . '">' . __('Cancel order &amp; restore cart', 'woocommerce_payu_money') . '</a>
				<script type="text/javascript">
					jQuery(function(){
						jQuery("body").block(
							{
								message: "<img src=\"' . $woocommerce->plugin_url() . '/assets/images/ajax-loader.gif\" alt=\"Redirecting...\" />' . __('Thank you for your order. We are now redirecting you to PayU to make payment.', 'woocommerce_payu_money') . '",
								overlayCSS:
								{
									background: "#fff",
									opacity: 0.6
								},
								css: {
							        padding:        20,
							        textAlign:      "center",
							        color:          "#555",
							        border:         "3px solid #aaa",
							        backgroundColor:"#fff",
							        cursor:         "wait"
							    }
							});
							jQuery("#payform").attr("action","' . $posturl . '");
						jQuery("#submit_payu_money_payment_form").click();
					});
				</script>
			</form>';
    }

// End wcpm_generate_payu_money_form()

    /**
     * Process the payment and return the result.
     *
     * @since 1.0.0
     */
    function process_payment($order_id) {
        $order = new WC_Order($order_id);

        return array(
            'result' => 'success',
            'redirect' => add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, add_query_arg('pg', $this->wcpm_get_post_var('pg'), $order->get_checkout_payment_url(true))))
        );
    }

// End wcpm_process_payment()

    /**
     * Receipt page.
     *
     * Display text and a button to direct the user to the payment screen.
     *
     * @since 1.0.0
     */
    function wcpm_receipt_page($order) {
        echo '<p>' . __('Thank you for your order, please click the button below to pay with PayU.', 'woocommerce_payu_money') . '</p>';

        echo $this->wcpm_generate_payu_money_form($order);
    }

// End wcpm_receipt_page()

    /**
     * Check the validity of data recived in $_POST and the status of transaction
     *
     * @since 1.0.0
     */
    function wcpm_check_transaction_status() {

        global $woocommerce;

        $salt = $this->salt;

        if (!empty($_POST)) {
            foreach ($_POST as $key => $value) {
                $txnRs[$key] = htmlentities($value, ENT_QUOTES);
            }
        } else {
            die('No transaction data was passed!');
        }

        $txnid = $txnRs['txnid'];


        /* Checking hash / true or false */
        if ($this->wcpm_check_hash_after_transaction($salt, $txnRs)) {

            if ($txnRs['status'] == 'success') {
                $this->wcpm_payment_success($txnRs['udf1']);
            }

            if ($txnRs['status'] == 'pending') {
                $this->wcpm_payment_pending($txnRs['udf1']);
            }

            if ($txnRs['status'] == 'failure') {
                $this->wcpm_payment_failure($txnRs['udf1']);
            }
        } else {
            die('Hash incorrect!');
        }
    }

// End wcpm_check_transaction_status()

    /**
     * @since 1.0.0
     */
    function wcpm_payment_success($txnid) {

        global $woocommerce;

        $order = new WC_Order($txnid);



        $order->add_order_note(__('PayUMoney payment completed', 'woocommerce_payu_money') . ' (Transaction id: ' . $txnid . ')');
        $order->payment_complete();

        $woocommerce->cart->empty_cart();
        return true;
    }

    // End wcpm_payment_success()

    /**
     * @since 1.0.0
     */
    function wcpm_payment_pending($txnid) {

        if ($this->wcpm_payu_money_transaction_verification($txnid) == 'pending') {

            global $woocommerce;

            
	    $order = new WC_Order($txnid);
            $order->update_status('pending');
            $order->add_order_note(sprintf(__('PayU payment pending (Transaction id: %s)', 'woocommerce_payu_money'), $txnid));

            return false;
        } else {

            die('PayU verification failed!');
        }
    }

// End wcpm_payment_pending()

    /**
     * @since 1.0.0
     */
    function wcpm_payment_failure($txnid) {
        global $woocommerce;

         $order = new WC_Order($txnid);

        $order->update_status('failed');
        $order->add_order_note(sprintf(__('PayU payment failed (Transaction id: %s)', 'woocommerce_payu_money'), $txnid));

        return false;
    }

// End wcpm_payment_failure()

    /**
     * @since 1.0.0
     */
    function wcpm_payu_money_transaction_verification($txnid) {

        $this->verification_liveurl = 'https://info.payu.in/merchant/postservice';
        $this->verification_testurl = 'https://test.payu.in/merchant/postservice';

        $host = $this->verification_liveurl;
        if ($this->testmode == 'yes') {
            $host = $this->verification_testurl;
        }

        $hash_data['key'] = $this->merchantid;
        $hash_data['command'] = 'verify_payment';
        $hash_data['var1'] = $txnid;
        $hash_data['hash'] = $this->wcpm_calculate_hash_before_verification($hash_data);

        // Call the PayU, and verify the status
        $response = $this->wcpm_send_request($host, $hash_data);

        $response = unserialize($response);

        return $response['transaction_details'][$txnid]['status'];
    }

// End wcpm_payu_money_transaction_verification()

    /**
     * @since 1.0.0
     */
    function wcpm_send_request($host, $data) {

        $response = wp_remote_post($host, array(
            'method' => 'POST',
            'body' => $data,
            'timeout' => 70,
            'sslverify' => false
        ));

        if (is_wp_error($response))
            throw new Exception(__('There was a problem connecting to the payment gateway.', 'woocommerce_payu_money'));

        if (empty($response['body']))
            throw new Exception(__('Empty PayUMoney response.', 'woocommerce_payu_money'));

        $parsed_response = $response['body'];

        return $parsed_response;
    }

// End wcpm_send_request()

    /**
     * @since 1.0.0
     */
    function wcpm_calculate_hash_before_transaction($hash_data) {

        $hash_sequence = "key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5|udf6|udf7|udf8|udf9|udf10";
        $hash_vars_seq = explode('|', $hash_sequence);
        $hash_string = '';

	
        foreach ($hash_vars_seq as $hash_var) {
            $hash_string .= isset($hash_data[$hash_var]) ? $hash_data[$hash_var] : '';
            $hash_string .= '|';
        }
	

        $hash_string .= $this->salt;
	

        $hash_data['hash'] = strtolower(hash('sha512', $hash_string));

	
	
        return $hash_data['hash'];
    }

// End wcpm_calculate_hash_before_transaction()

    /**
     * @since 1.0.0
     */
    function wcpm_check_hash_after_transaction($salt, $txnRs) {


        if (isset($txnRs['additionalCharges'])) {
            $merc_hash_string = $txnRs['additionalCharges'] . '|' . $salt . '|' . $txnRs['status'];
        } else {
            $merc_hash_string = $salt . '|' . $txnRs['status'];
        }

        $hash_sequence = "udf10|udf9|udf8|udf7|udf6|udf5|udf4|udf3|udf2|udf1|email|firstname|productinfo|amount|txnid|key";
        $hash_vars_seq = explode('|', $hash_sequence);
        foreach ($hash_vars_seq as $merc_hash_var) {
            $merc_hash_string .= '|';
            $merc_hash_string .= isset($txnRs[$merc_hash_var]) ? $txnRs[$merc_hash_var] : '';
        }



        $merc_hash = strtolower(hash('sha512', $merc_hash_string));

        /* The hash is valid */
        if ($merc_hash == $txnRs['hash']) {
            return true;
        } else {
            return false;
        }
    }

// End wcpm_check_hash_after_transaction()

    /**
     * @since 1.0.0
     */
    function wcpm_calculate_hash_before_verification($hash_data) {

        $hash_sequence = "key|command|var1";
        $hash_vars_seq = explode('|', $hash_sequence);
        $hash_string = '';

        foreach ($hash_vars_seq as $hash_var) {
            $hash_string .= isset($hash_data[$hash_var]) ? $hash_data[$hash_var] : '';
            $hash_string .= '|';
        }

        $hash_string .= $this->salt;
        $hash_data['hash'] = strtolower(hash('sha512', $hash_string));

        return $hash_data['hash'];
    }

// End wcpm_calculate_hash_before_verification()

    /**
     *  Get post data if set
     *
     * @since 1.4
     */
    function wcpm_get_post_var($name) {
        if (isset($_POST[$name])) {
            return $_POST[$name];
        }
        return NULL;
    }

    /**
     *  Get get data if set
     *
     * @since 1.4
     */
    function wcpm_get_get_var($name) {
        if (isset($_GET[$name])) {
            return $_GET[$name];
        }
        return NULL;
    }

}

//  End Class