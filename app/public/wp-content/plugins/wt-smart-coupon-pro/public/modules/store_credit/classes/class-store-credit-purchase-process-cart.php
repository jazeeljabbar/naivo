<?php
/**
 * Store credit purchase as gift card or coupon.
 *
 * @link       
 * @since 2.0.0     
 *
 * @package  Wt_Smart_Coupon  
 */
if (!defined('ABSPATH')) {
    exit;
}
if(!class_exists('Wt_Smart_Coupon_Store_Credit_Purchase')) 
{
    return;
}

class Wt_Smart_Coupon_Store_Credit_Purchase_Process_Cart extends Wt_Smart_Coupon_Store_Credit_Purchase
{
    private static $instance = null;
    public function __construct()
    {
        parent::init_vars();

        /**
         * Validate the store credit amount on add to cart.
         */
        add_filter('woocommerce_add_to_cart_validation', array($this, 'validate_store_credit_on_add_to_cart'), 10, 2);

        /**
         *  Add store credit details into cart item data.
         */
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_store_credit_template_details_to_cart_item_data'), 10, 3);


        /**
         * Set credit amount session on cart.
         */
        add_action('woocommerce_add_to_cart', array($this, 'save_credit_details_in_session'), 10, 6);

        /**
         * Update cart item price for credit purchase.
         */
        add_filter('woocommerce_cart_item_price', array($this, 'cart_item_price_for_credit_purchase'), 10, 3);

        add_filter('woocommerce_cart_item_thumbnail', array($this, 'display_gift_card_image_in_cart_item'), 10, 3);
        
        add_filter('woocommerce_get_item_data', array($this, 'display_credit_details_into_cart_item'), 10, 2 );

        /**
         * Create random coupon and update the credit details into order item meta
         */
        add_action('woocommerce_new_order_item', array($this, 'update_coupon_data_into_order'), 1, 3);

        /**
         * Save created coupon details into order meta data.
         * The same callback also hooked to `woocommerce_store_api_checkout_order_processed`
         */
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_credit_details_in_order'));
    

        /**
         *  Reset the quantity to one on add to cart
         *  @since 2.0.8
         */
        add_filter('woocommerce_add_to_cart_product_id', array($this, 'reset_quantity_request_data_to_one_on_add_to_cart'), 8, 1);

        /**
         *  Save created coupon details into order meta data. When checkout block was enabled.
         *  The same callback also hooked to `woocommerce_checkout_update_order_meta` 
         * 
         *  @since 2.3.0
         */
        add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'save_credit_details_in_order' ) );
    

        /** 
         *  Add gift card image data to wc checkout data (For checkout/cart block)
         *  
         *  @since 2.4.0
         */
        add_filter( 'wbte_sc_alter_blocks_data', array( $this, 'add_gift_card_img_data' ) );
    }


    /**
     * Get Instance
     * @since 2.0.0
     */
    public static function get_instance()
    {
        if(self::$instance==null)
        {
            self::$instance=new Wt_Smart_Coupon_Store_Credit_Purchase_Process_Cart();
        }
        return self::$instance;
    }
    
    /**
     *  Validate the store credit fields on add to cart.
     *  Validates Credit amount, Email
     */
    public function validate_store_credit_on_add_to_cart($passed, $product_id)
    {
        if(!$this->is_product_is_store_credit_purchase($product_id))
        {
            return $passed;
        }
        
        $settings=self::get_store_credit_settings();

        $min_price = $this->get_giftcard_min_max_price($settings);
        $max_price = $this->get_giftcard_min_max_price($settings, 'max');

        /**
         *  If predefined denominations enabled, Then check the max price with highest price in denominations
         */
        if(''!=$settings['denominations'] && ('denominations_only'==$settings['display_option'] || 'denominations_and_user_specific'==$settings['display_option']))
        {
            $denominations=$this->process_denomination_list($settings['denominations']);
            $highest_denomination=max($denominations);
            if($highest_denomination>$max_price)
            {
                $max_price=$highest_denomination;
            }
        }

        if(!isset($_REQUEST['wt_credit_amount']) || ( isset($_REQUEST['wt_credit_amount']) && 0==floatval($_REQUEST['wt_credit_amount'])))
        {
            wc_add_notice(__('Store credit amount is required!', 'wt-smart-coupons-for-woocommerce-pro'), 'error');
            return false;
        }
        
        $min_price_value = Wt_Smart_Coupon_Admin::get_formatted_price($min_price);
        $max_price_value = Wt_Smart_Coupon_Admin::get_formatted_price($max_price);
        $credit_amount = $this->sanitize_price($_REQUEST['wt_credit_amount']);
        
        if(($min_price>0 &&  $max_price>0) && ($credit_amount<$min_price ||  $credit_amount>$max_price ))
        {
            wc_add_notice(sprintf(__( 'The credit value should be in between %s and %s ', 'wt-smart-coupons-for-woocommerce-pro'), $min_price_value, $max_price_value), 'error');
            return false;
        }

        if($min_price>0)
        {             
            if($credit_amount<$min_price)
            {
                wc_add_notice(sprintf(__('The credit value should be greater than %s', 'wt-smart-coupons-for-woocommerce-pro'), $min_price_value), 'error');
                return false;
            }
        }
        if($max_price > 0)
        {
            if($credit_amount > $max_price )
            {
                wc_add_notice(sprintf(__('The credit value should be less than %s', 'wt-smart-coupons-for-woocommerce-pro'), $max_price_value), 'error');
                return false;
            }
        }

        /* Email validation */
        if(isset($_REQUEST['wt_credit_coupon_send_to']))
        {
            if(!is_email($_REQUEST['wt_credit_coupon_send_to']))
            {
                wc_add_notice(__( 'Please enter valid email address ', 'wt-smart-coupons-for-woocommerce-pro'), 'error');
                return false;
            }
        }else
        {
            wc_add_notice(__( 'Recipient email is required ', 'wt-smart-coupons-for-woocommerce-pro'), 'error');
            return false;
        }

        if(!isset($_REQUEST['wt_smart_coupon_send_today'])) /* not `send today` */
        {
            if(isset($_REQUEST['wt_smart_coupon_schedule_field']) && ""!=trim($_REQUEST['wt_smart_coupon_schedule_field']))
            {
                $date_input=Wt_Smart_Coupon_Security_Helper::sanitize_item($_REQUEST['wt_smart_coupon_schedule_field']);
                $date_input_d=(isset($_REQUEST['wt_smart_coupon_schedule_d']) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_REQUEST['wt_smart_coupon_schedule_d']) : '');
                $date_input_m=(isset($_REQUEST['wt_smart_coupon_schedule_m']) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_REQUEST['wt_smart_coupon_schedule_m']) : '');
                $date_input_y=(isset($_REQUEST['wt_smart_coupon_schedule_y']) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_REQUEST['wt_smart_coupon_schedule_y']) : '');
                
                if(trim($date_input_d)=="" || trim($date_input_m)=="" || trim($date_input_y)=="")
                {
                    wc_add_notice(__('Schedule date is mandatory.', 'wt-smart-coupons-for-woocommerce-pro'), 'error');
                    return false;
                }

                if(strtotime($date_input_y.'-'.$date_input_m.'-'.$date_input_d)===false)
                {
                    wc_add_notice(__('Please enter a valid date.', 'wt-smart-coupons-for-woocommerce-pro'), 'error');
                    return false;
                }

            }else
            {
                wc_add_notice(__('Schedule date is mandatory.', 'wt-smart-coupons-for-woocommerce-pro'), 'error');
                return false;
            }
        }
        return $passed;
    }


    /**
     * Save store credit details into cart item session
     */
    public function add_store_credit_template_details_to_cart_item_data($cart_item_data, $product_id, $variation_id)
    {
        if(!$this->is_product_is_store_credit_purchase($product_id)) /* not a store credit purchase */
        {
            return $cart_item_data;
        } 

        $template_items = array(
            'wt_credit_coupon_send_to'=>(isset($_REQUEST['wt_credit_coupon_send_to']) ? sanitize_email($_REQUEST['wt_credit_coupon_send_to']) : ''),
            'wt_credit_coupon_send_to_message'=>(isset($_REQUEST['wt_credit_coupon_send_to_message']) ? sanitize_textarea_field($_REQUEST['wt_credit_coupon_send_to_message']) : ''),
            'wt_credit_coupon_from'=>(isset($_REQUEST['wt_credit_coupon_from']) ? sanitize_text_field($_REQUEST['wt_credit_coupon_from']) : ''),
            'wt_smart_coupon_schedule'=>(isset($_REQUEST['wt_smart_coupon_schedule_field']) ? sanitize_text_field($_REQUEST['wt_smart_coupon_schedule_field']) : ''),
            'wt_smart_coupon_schedule_y'=>(isset($_REQUEST['wt_smart_coupon_schedule_y']) ? sanitize_text_field($_REQUEST['wt_smart_coupon_schedule_y']) : ''),
            'wt_smart_coupon_schedule_m'=>(isset($_REQUEST['wt_smart_coupon_schedule_m']) ? sanitize_text_field($_REQUEST['wt_smart_coupon_schedule_m']) : ''),
            'wt_smart_coupon_schedule_d'=>(isset($_REQUEST['wt_smart_coupon_schedule_d']) ? sanitize_text_field($_REQUEST['wt_smart_coupon_schedule_d']) : ''),
            'wt_smart_coupon_template_image'=>(isset($_REQUEST['wt_credit_coupon_image']) ? sanitize_text_field($_REQUEST['wt_credit_coupon_image']) : ''), /* empty on non extended */
        );

        if(self::is_extended_store_credit_enabled())
        {   
            $template_items['extended'] = true;
            if("" === $template_items['wt_smart_coupon_template_image'])
            {
                $template_items['wt_smart_coupon_template_image'] = "general"; /* default template for extended store credit */
            }
        }
        
        $cart_item_data['wt_credit_amount'] = (isset($_REQUEST['wt_credit_amount']) ? $this->sanitize_price($_REQUEST['wt_credit_amount']) : 0);
        $cart_item_data['wt_store_credit_template'] = $template_items;
        
        return $cart_item_data;
    }

    /**
     * Set credit amount session on cart.
     */
    public function save_credit_details_in_session($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data)
    {
        if(!empty($variation_id) && $variation_id>0) // Variable Product.
        {  
            return;
        }

        if(!isset($cart_item_data['wt_credit_amount']) || empty($cart_item_data['wt_credit_amount']))
        {
            return;
        }

        if($this->is_product_is_store_credit_purchase($product_id))
        {
            $wt_credit_amount = !is_null( WC()->session ) ? WC()->session->get( 'wt_credit_amount' ) : array() ;
            if(empty($wt_credit_amount) || !is_array($wt_credit_amount))
            {
                $wt_credit_amount = array();
            }
            $wt_credit_amount[$cart_item_key] = $cart_item_data['wt_credit_amount'];
            WC()->session->set('wt_credit_amount', $wt_credit_amount);
        }
    }

    /**
     * Update cart item price for credit purchase.
     */
    public function cart_item_price_for_credit_purchase($product_price, $cart_item, $cart_item_key)
    {
        $wt_credit_amount = (is_object(WC()->session) && is_callable(array(WC()->session, 'get')) ? WC()->session->get('wt_credit_amount') : array());

        if(!empty($wt_credit_amount) && isset($wt_credit_amount[$cart_item_key]) && !empty($wt_credit_amount[$cart_item_key]))
        {
            return wc_price($wt_credit_amount[$cart_item_key]);

        }elseif(!empty($cart_item['wt_credit_amount']))
        {
            return wc_price($cart_item['wt_credit_amount']);
        }
        return $product_price;
    }

    /**
     * Display store credit cart items into cart and checkout page
     */
    public function display_credit_details_into_cart_item($item_data, $cart_item_data)
    {      
        if(isset($cart_item_data['wt_store_credit_template']))
        {
            $store_credit_details = $cart_item_data['wt_store_credit_template'];
            $item_data[] = array(
                'key' =>  __('Recipient email','wt-smart-coupons-for-woocommerce-pro'),
                'value' => $store_credit_details['wt_credit_coupon_send_to'],
            );
            
            if(""!=trim($store_credit_details['wt_credit_coupon_from']))
            {
               $item_data[] = array(
                    'key' =>  __('Sender name','wt-smart-coupons-for-woocommerce-pro'),
                    'value' => $store_credit_details['wt_credit_coupon_from'],
                ); 
            }
                       
            $item_data[] = array(
                'key'   =>  __('Send date','wt-smart-coupons-for-woocommerce-pro'),
                'value' => ("" !== $store_credit_details['wt_smart_coupon_schedule'] ? $store_credit_details['wt_smart_coupon_schedule'] : __('Today', 'wt-smart-coupons-for-woocommerce-pro')),
            ); 

        }
        return $item_data;
    }

    public function display_gift_card_image_in_cart_item($product_image, $cart_item, $cart_item_key = "")
    { 
        if(isset($cart_item['wt_store_credit_template']) && isset($cart_item['wt_store_credit_template']['wt_smart_coupon_template_image']))
        {
            $template_data=self::get_gift_card_template($cart_item['wt_store_credit_template']['wt_smart_coupon_template_image']);
            
            if(isset($template_data['image_url']) && "" !== $template_data['image_url'])
            {
                $product_image = preg_replace('(src="(.*?)")', 'src="'.$template_data['image_url'].'"', $product_image);
                $product_image = preg_replace('(srcset="(.*?)")', 'srcset="'.$template_data['image_url'].'"', $product_image);
            }
        }
        return $product_image;
    }

    public function update_coupon_data_into_order($item_id, $item, $order_id)
    {
        $product=(is_callable(array($item, 'get_product')) ? $item->get_product() : '');
        if(!is_object($product) || !is_a($product, 'WC_Product'))
        {
            return;
        }

        if(!property_exists($item, 'legacy_values'))/** @since 2.1.0 Undefined property issue. */
        {
            return;
        }

        $legacy_values = $item->legacy_values;

        if(!isset($legacy_values['wt_credit_amount']) || floatval($legacy_values['wt_credit_amount'])<=0)
        {
            return;
        }

        $enabled_customizing_store_credit = self::is_extended_store_credit_enabled();
        if($enabled_customizing_store_credit) /* for extended store credit */
        {
            if(!isset($legacy_values['wt_store_credit_template']))
            {
                return;
            }else
            {
                if(empty($legacy_values['wt_store_credit_template']))
                {
                   return; 
                }
            }
        }

        $product_short_desc = strip_tags($product->get_short_description());
        $qty    = (is_callable(array($item, 'get_quantity')) ? $item->get_quantity() : 1);
        $qty    = (!empty($qty) ? $qty : 1);
        $credit_value  = floatval($legacy_values['wt_credit_amount']);
        $store_credit_template_data = $legacy_values['wt_store_credit_template'];

        if(isset($store_credit_template_data['wt_credit_coupon_send_to']))
        {
            $email = Wt_Smart_Coupon_Security_Helper::sanitize_item($store_credit_template_data['wt_credit_coupon_send_to'], 'email');
        }else
        {
            $email = Wt_Smart_Coupon_Security_Helper::sanitize_item($_REQUEST['billing_email'], 'email');
        }
        $message = Wt_Smart_Coupon_Security_Helper::sanitize_item($store_credit_template_data['wt_credit_coupon_send_to_message'], 'textarea');


        /* coupon configs */
        $settings = self::get_store_credit_settings();
        $prefix = $settings['store_credit_coupon_prefix'];
        $suffix = $settings['store_credit_coupon_suffix'];
        $coupon_length = $settings['store_credit_coupon_length'];


        $coupons_generated = array();
        $store_credit_data = array();

        for($i = 0; $i < $qty; $i++)
        {
            $coupon_data = $this->create_store_credit_coupon($credit_value, $prefix, $suffix, $coupon_length, $product_short_desc);
            
            if(!empty($coupon_data))
            {
                $coupon_id  = $coupon_data['coupon_id'];
                $coupon_obj = $coupon_data['coupon_obj'];
                
                if(true === $this->store_credit_email_restriction())
                {
                    $coupon_obj->set_email_restrictions($email);
                }

                if(isset($settings['make_coupon_individual_use_only']))
                {
                    $coupon_obj->set_individual_use(true);
                }

                $coupon_obj->save();
                $coupons_generated[] = array(
                    'coupon_id'         => $coupon_id,
                    'credited_amount'   => $credit_value,
                );
                $store_credit_data[$coupon_id] = $legacy_values['wt_store_credit_template'];
                $store_credit_data[$coupon_id]['coupon_id'] = $coupon_id;

                
                //Saving time in GMT
                if("" !== $store_credit_data[$coupon_id]['wt_smart_coupon_schedule'])
                {
                    $date_in_y_m_d = $store_credit_data[$coupon_id]['wt_smart_coupon_schedule_y'] . '-' . $store_credit_data[$coupon_id]['wt_smart_coupon_schedule_m'] . '-' . $store_credit_data[$coupon_id]['wt_smart_coupon_schedule_d']; // Prepare a known format date to avoid date validation issues.
                    $store_credit_data[$coupon_id]['wt_smart_coupon_schedule'] = Wt_Smart_Coupon_Admin::wt_sc_get_date_prop($date_in_y_m_d)->getTimestamp();
                }

                do_action('wt_smart_coupon_purchased_store_credit_coupon_added', $coupon_obj);
            }            
        }
        
        wc_add_order_item_meta($item_id, 'wt_credit_coupon_generated', $coupons_generated);
        wc_add_order_item_meta($item_id, 'wt_credit_coupon_template_details', $store_credit_data);

    }

    /**
     *  Save created coupon details into order meta data.
     * 
     *  @since 2.0.8            Added HPOS Compatibility
     *  @since 2.3.0            Checkout block compatibility
     *  @param int|WC_Order     $order              Order id/WC_Order
     */
    public function save_credit_details_in_order( $order )
    {       
        $order          = ! is_object( $order ) ? wc_get_order( $order ) : $order;
        $order_id       = $order->get_id();
        $order_items    = $order->get_items();

        $coupons = array();
        foreach($order_items as $item_id => $order_item)
        {
            $coupons_generated=$order_item->get_meta('wt_credit_coupon_generated');
            if(!empty($coupons_generated) && is_array($coupons_generated))
            {
                $coupons  = array_merge($coupons, $coupons_generated);               
            }
        }
        if(!empty($coupons))
        {
            Wt_Smart_Coupon_Common::update_order_meta($order_id, 'wt_credit_coupons', maybe_serialize($coupons));
        }
        
    }

    /**
     *  Reset the `$_REQUEST`,`$_POST`, `$_GET` quantity varaiable to one on add to cart. We are using this hook becuase to show the quantity as one in all sections including messages.
     *  
     *  @since  2.0.8
     *  @param  int     $product_id     Id of product
     *  @return int     $product_id
     */
    public function reset_quantity_request_data_to_one_on_add_to_cart($product_id)
    {
        if(0 < $product_id
            && (isset($_REQUEST['add-to-cart']) || isset($_POST['product_id']))
            && $this->is_product_is_store_credit_purchase($product_id)
        )
        {
            $_REQUEST['quantity'] = $_GET['quantity'] = $_POST['quantity'] = 1;
        }

        return $product_id;
    }


    /**
     *  Add gift card image data
     *  Hooked into: wbte_sc_alter_blocks_data
     *  
     *  
     *  @since  2.4.0
     *  @param  array       $block_data         Params array
     *  @return array       $block_data         Params array
     */
    public function add_gift_card_img_data( $block_data ) {

        $cart = self::get_cart_object();
            
        if ( ! is_null( $cart ) && ! $cart->is_empty() ) { 

            $out = array();

            foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
                
                if ( isset( $cart_item['wt_store_credit_template'] ) && isset( $cart_item['wt_store_credit_template']['wt_smart_coupon_template_image'] ) && "" !== $cart_item['wt_store_credit_template']['wt_smart_coupon_template_image'] ) {
                    
                    $template_data = self::get_gift_card_template( $cart_item['wt_store_credit_template']['wt_smart_coupon_template_image'] );
                    
                    if ( isset( $template_data['image_url'] ) && "" !== $template_data['image_url'] ) {
                        $out[ $cart_item_key ] = esc_url( $template_data['image_url'] );
                    }
                }
            }

            if ( ! empty( $out ) ) {
               $block_data['cartitem_giftcard_image'] = $out;
            }
        }

        return $block_data;
    }
}