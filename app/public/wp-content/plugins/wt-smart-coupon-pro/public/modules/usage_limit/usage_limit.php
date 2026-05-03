<?php
/**
 * Coupon usage limit public area
 *
 * @link       
 * @since 2.1.0
 *
 * @package  Wt_Smart_Coupon  
 */
if (!defined('ABSPATH')) {
    exit;
}

class Wt_Smart_Coupon_Usage_Limit_Public
{
    public $module_base = 'usage_limit';
    public $module_id = '';
    public static $module_id_static = '';
    private static $instance = null;
    
    public function __construct()
    {
        $this->module_id = Wt_Smart_Coupon::get_module_id($this->module_base);
        self::$module_id_static = $this->module_id;

        add_filter('woocommerce_coupon_get_discount_amount', array($this, 'calculate_maximum_discount'), 20, 5);

        /**
         *  Do `Allow once per product` validation for guest and logged in users
         * 
         *  @since 2.1.0
         */
        add_filter('woocommerce_coupon_is_valid', array($this, 'woocommerce_coupon_is_valid_for_logged_in'), 10, 2);
        add_filter('woocommerce_after_checkout_validation', array($this, 'woocommerce_coupon_is_valid_for_guest'), 20, 1);
        add_filter('woocommerce_store_api_checkout_order_processed', array($this, 'woocommerce_coupon_is_valid_for_guest'), 20, 1);
    }

    
    /**
     *  Get Instance
     * 
     *  @since 2.1.0
     */
    public static function get_instance()
    {
        if(is_null(self::$instance))
        {
            self::$instance = new Wt_Smart_Coupon_Usage_Limit_Public();
        }
        return self::$instance;
    }


    /**
     *  Calculate discounting amount
     * 
     *  @since  2.1.0
     *  @since  2.1.1       [Fix] Calculation issue when `usage limit to x items` is enabled.
     *  @param  float       $discount               Coupon discount
     *  @param  float       $discounting_amount     Coupon available discount
     *  @param  array       $cart_item              Cart item array
     *  @param  bool        $single                 Is single item
     *  @param  WC_Coupon   $coupon                 Coupon object
     *  @return float       Coupon discount
     */
    public function calculate_maximum_discount($discount, $discounting_amount, $cart_item, $single, $coupon)
    {
        if(!$coupon->is_type('percent') && !$coupon->is_type('fixed_product'))
        {
            return $discount;
        }

        $cart_discount_details = isset($cart_item['wt_discount_details']) ? $cart_item['wt_discount_details'] : array();
        $max_discount = get_post_meta($coupon->get_id(), '_wt_max_discount', true);

        if(is_numeric($max_discount) && $max_discount > 0 && !is_null($cart_item) && WC()->cart->subtotal_ex_tax)
        {
            if($coupon->is_type('percent')) // Percentage coupon
            {             
                $subtotal_quantity_arr = $this->get_allowed_prodcuts_from_cart($coupon, 'subtotal_quantity_arr');
                $cart_item_qty  = isset($subtotal_quantity_arr[$cart_item['key']]) && isset($subtotal_quantity_arr[$cart_item['key']]['quantity']) ? $subtotal_quantity_arr[$cart_item['key']]['quantity'] : 0; // Take the allowed quantity    
                
                if(0 === $cart_item_qty) {
                    return $discount; // May be the product is not applicable for the coupon. Eg: Limit to x item is reached.
                }

                $subtotal_for_available_product = array_sum( array_column( $subtotal_quantity_arr, 'price' ) ); // Prepare the subtotal
                $product_price = $this->get_product_cart_item_price( $cart_item['data'] );
                $discount_percent = ( $product_price * $cart_item_qty ) / $subtotal_for_available_product;        
                $balance_discount = 0;    

            }else  // Fixed product coupon
            {
                $quantity_arr   = $this->get_allowed_prodcuts_from_cart( $coupon, 'quantity_arr' ); // Take all allowed quantity of applicable cart items as array.
                $quantity_arr_count = array_sum( $quantity_arr );
                $cart_item_qty  = isset($quantity_arr[ $cart_item['key'] ]) ? $quantity_arr[ $cart_item['key'] ] : 0; // Take the allowed quantity    
                
                if(0 === $cart_item_qty) {
                    return $discount; // May be the product is not applicable for the coupon. Eg: Limit to x item is reached.
                }

                $coupon_amount      = $coupon->get_amount();

                //If a giveaway product which price is less than discount, add balance giveaway amount to other products
                $balance_discount = 0;
                $quantity_arr_count_temp = $quantity_arr_count;
                $cart_items = ! is_null( WC()->cart ) ? WC()->cart->get_cart() : array();
                foreach( $cart_items as $cart_item_key => $cart_item_temp ) 
                {
                    if ( ! $coupon->is_valid_for_product( wc_get_product( $cart_item_temp['product_id'] ), $cart_item_temp ) && ! $coupon->is_valid_for_cart() ) {
                        continue;
                    }

                    if( $cart_item_key === $cart_item['key'] ) continue;
                    $cart_item_qty_temp = $quantity_arr[$cart_item_temp['key']] ?? 0;
                
                    if( 0 === $cart_item_qty_temp ) {
                        break; 
                    }

                    $product_price = $cart_item_temp['data']->get_price();
                    
                    // Calculating the discount before the actual calculation, to check whether the discount amount is less than the product price.
                    $discount_percent_temp = ( $coupon_amount * $cart_item_qty_temp ) / ( $coupon_amount * $quantity_arr_count );  
                    $_discount_temp = $max_discount * $discount_percent_temp;
                    // If the discount amount is less than the product price, store the balance discount amount, which will be added to the final total discount.
                    if( $product_price < $_discount_temp ){
                        $balance_discount += $_discount_temp - $product_price;
                        $quantity_arr_count_temp -= $cart_item_temp['quantity'];
                    }
                } 
            
                $discount_percent   = ( $coupon_amount * $cart_item_qty ) / ( $coupon_amount * $quantity_arr_count );  

                $balance_disc_add = ( $balance_discount / $quantity_arr_count_temp ) * $cart_item['quantity'];
            }

            $_discount = ( $max_discount * $discount_percent );
            if( $balance_discount > 0 ){
                $_discount += $balance_disc_add;
            }
            $discount = min( $_discount, $discount );
        }
    
        return $discount;
    }

    
    /**
     *  Get allowed products data for a coupon
     * 
     *  @since  2.1.0
     *  @since  2.1.1       Preparing data based on `usage limit to x items` option
     *                      Quantity and individual price preparation added.
     *  @param  WC_Coupon   $coupon     Coupon object
     *  @return float       Discount amount
     */
    public function get_allowed_prodcuts_from_cart($coupon, $output = 'subtotal')
    {
        $cart = WC()->cart;
        $coupon_id = $coupon->get_id();

        $to_return = ('subtotal' === $output ? 0 : array()); // To return variable, based on $output argument
        if ( is_null( $cart ) ) {
            return $to_return;
        }
        $cart = $cart->get_cart();

        // used to apply any reduction before calculating discount (will implement later ).
        $pre_discount_to_sub_total = apply_filters('wt_pre_applied_discount_into_sub_total', 0);

        $to_apply_quantity = 0;
        $limit_usage_qty = false;

        if ( null !== $coupon->get_limit_usage_to_x_items() ) {
            $to_apply_quantity = absint($coupon->get_limit_usage_to_x_items());
            $limit_usage_qty = true;
        }

        foreach( $cart as $cart_item_key => $cart_item ) 
        {            
            if( 0 >= $cart_item['data']->get_price() ) continue; //Exclude if full giveaway.
            $cart_item_qty = is_null( $cart_item ) ? 1 : $cart_item['quantity'];

            if($limit_usage_qty)
            {
                if(0 === (int) $to_apply_quantity) {
                    break;
                }

                $cart_item_qty = min($to_apply_quantity, $cart_item_qty);
                $to_apply_quantity -= $cart_item_qty;
            }

            $_product = $cart_item['data'];                
            
            if ( $coupon->is_valid_for_product($_product) ) 
            {                
                if ( 'quantity_arr' === $output )
                {                                     
                    $to_return[$cart_item_key] = $cart_item_qty;

                }elseif ( 'subtotal_quantity_arr' === $output )
                {
                    $to_return[$cart_item_key] = array(
                        'quantity'  => $cart_item_qty,
                        'price'     => $this->get_product_cart_item_price( $_product )  * $cart_item_qty,
                    );

                }else
                {
                    $to_return += $this->get_product_cart_item_price( $_product )  * $cart_item_qty;
                }
            }
        }
        
        return $to_return;
    }


    /**
     *  `Allow once per product` validation for logged in users
     * 
     *  @since  2.1.0
     *  @param  bool        $valid      Is valid                 
     *  @param  WC_Coupon   $coupon     Coupon object
     *  @return bool        Is valid or not                
     */
    public function woocommerce_coupon_is_valid_for_logged_in($valid, $coupon)
    {      
        /**
         *  Skip this validation when: 
         *      1. Already not valid
         *      2. Cart object not available
         *      3. `Allow once per product` not enabled
         *      4. User not logged in    
         */
        if(
            !$valid || 
            is_null($cart = WC()->cart) || 
            !$this->is_allow_once_per_product_enabled($coupon) || 
            !is_user_logged_in()           
        )
        {
            return $valid;
        }
        
        return $this->do_allow_once_per_product_validation($valid, $coupon, $this->get_product_variation_ids());
    }


    /**
     *  `Allow once per product` validation for guest users
     * 
     *  @since  2.1.0
     *  @param  array | object   $posted     Post data or Order object           
     */
    public function woocommerce_coupon_is_valid_for_guest($posted)
    {
        /**
         *  Only do the validation when:
         *      1. User not logged in.
         *      2. Cart object is available
         */
        if(
            !is_user_logged_in() && 
            !is_null($cart = WC()->cart)
        ) 
        {
            $billing_email = '';
            if( is_object( $posted ) && method_exists( $posted, 'get_billing_email' ) ){
                $billing_email = sanitize_email( $posted->get_billing_email() ) ;
            }else{
                $billing_email = isset( $posted['billing_email'] ) ? sanitize_email($posted['billing_email']) : '' ; 
            }
            $discounts = new WC_Discounts( $cart ); //Discounts object
            $product_variation_id_arr = $this->get_product_variation_ids(); //products and variation ids in the cart.

            foreach ( $cart->get_applied_coupons() as $coupon_code )
            {
                $coupon = new WC_Coupon( $coupon_code );               

                /**
                 *  The checkings here is:
                 *      1. `Allow once per product` is enabled.
                 *      2. The coupon is already valid for cart.
                 *      3. Validate `Allow once per product` using billing email.
                 */
                if (
                    $this->is_allow_once_per_product_enabled( $coupon ) && 
                    !is_wp_error( $discounts->is_coupon_valid( $coupon ) ) &&
                    !$this->do_allow_once_per_product_validation( true, $coupon, $product_variation_id_arr, $billing_email ) 
                ) 
                {
                    // Show a message and remove the coupon
                    $coupon->add_coupon_message( WC_Coupon::E_WC_COUPON_INVALID_REMOVED );
                    $cart->remove_coupon( $coupon_code );
                }
            }   
        }
    }


    /**
     *  Is `allow once per product` option enabled for this coupon.
     * 
     *  @since  2.1.0
     *  @access private
     *  @param  WC_Coupon   $coupon     Coupon object
     *  @return bool        Is enabled or not 
     */
    private function is_allow_once_per_product_enabled($coupon)
    {
        return wc_string_to_bool( get_post_meta( $coupon->get_id(), '_wt_sc_allow_once_per_product', true ) );
    }


    /**
     *  Get product/variation ids of cart
     *  
     *  @since 2.1.0
     *  @access private
     *  @return int[]   product/variation ids
     */
    private function get_product_variation_ids()
    {
        $product_variation_id_arr = array();

        foreach(WC()->cart->get_cart() as $cart_item_key => $cart_item)
        {
            $product_variation_id_arr[] = $cart_item['product_id'];
                
            if(0 < $cart_item['variation_id'])
            {
                $product_variation_id_arr[] = $cart_item['variation_id'];
            }
        }

        return $product_variation_id_arr;
    }


    /**
     *  Do the `allow once per product` validation
     *  
     *  @since 2.1.0
     *  @access private
     *  @param  bool        $valid                          Is valid                 
     *  @param  WC_Coupon   $coupon                         Coupon object
     *  @param  int[]       $product_variation_id_arr       product/variation ids
     *  @param  string      $billing_email                  Billing email. Optional. Using when guest user validation.
     *  @return bool        Is valid or not 
     */
    private function do_allow_once_per_product_validation($valid, $coupon, $product_variation_id_arr, $billing_email = '')
    {
        global $wpdb;

        /**
         *  Declare variables for query
         */ 
        $coupon_code = wc_sanitize_coupon_code($coupon->get_code());
        $order_item_meta_tb = $wpdb->prefix . 'woocommerce_order_itemmeta';
        $order_items_tb = $wpdb->prefix . 'woocommerce_order_items';
        $post_meta_tb = $wpdb->prefix . 'postmeta';
        $orders_tb = $wpdb->prefix . 'wc_orders';
        $post_tb = $wpdb->prefix . 'posts';

        
        /**
         *  Prepare query sections for guest and logged in users
         */
        if(is_user_logged_in())
        {
            $user_chk_sql_join  = Wt_Smart_Coupon_Common::is_wc_hpos_enabled() ? "LEFT JOIN {$orders_tb} AS o ON (o.id = p.order_id) " : "LEFT JOIN {$post_meta_tb} AS pm ON ( pm.post_id = p.order_id AND pm.`meta_key` = '_customer_user' ) ";
            $user_chk_sql_where = Wt_Smart_Coupon_Common::is_wc_hpos_enabled() ? "o.customer_id=%d" : "pm.meta_value = %d";
        }else
        {
            $user_chk_sql_join  = Wt_Smart_Coupon_Common::is_wc_hpos_enabled() ? "LEFT JOIN {$orders_tb} AS o ON (o.id = p.order_id) " : "LEFT JOIN {$post_meta_tb} AS pm ON ( pm.post_id = p.order_id AND pm.`meta_key` = '_billing_email' ) ";
            $user_chk_sql_where = Wt_Smart_Coupon_Common::is_wc_hpos_enabled() ? "o.billing_email=%s" : "pm.meta_value = %s";
        }

        if( Wt_Smart_Coupon_Common::is_wc_hpos_enabled() ){
            $hpos_block_sql_where = " AND o.status != 'wc-checkout-draft' ";
            $post_block_sql_join = "";
            $post_block_where = "";
        }else{
            $post_block_sql_join = " LEFT JOIN {$post_tb} AS wp ON ( wp.ID = pm.post_id ) ";
            $hpos_block_sql_where = "";
            $post_block_where = " AND wp.post_status != 'wc-checkout-draft' ";
        }


        /**
         *  Prepare the query
         */
        $sql = "SELECT
            COUNT(p.order_id) AS ttl 
        FROM
            {$order_item_meta_tb} AS im
        LEFT JOIN {$order_items_tb} AS p
        ON
            (
                im.order_item_id = p.order_item_id AND p.`order_item_type` = 'line_item'
            )
        LEFT JOIN {$order_item_meta_tb} AS im2
        ON
            (
                im.order_item_id = im2.order_item_id AND im2.`meta_key` = 'free_gift_coupon'
            )
        LEFT JOIN {$order_items_tb} AS c
        ON
            (
                c.order_id = p.order_id AND c.`order_item_type` = 'coupon'
            )
        {$user_chk_sql_join}
        {$post_block_sql_join}
        WHERE
        (
            im.meta_key = '_product_id' OR im.meta_key = '_variation_id'
        ) AND im.meta_value IN(" . implode(", ", array_fill(0, count($product_variation_id_arr), '%d')) . ") AND c.order_item_name = %s AND {$user_chk_sql_where} AND(
            im2.meta_value = %s OR im2.meta_value IS NULL
        )
        {$hpos_block_sql_where}{$post_block_where}";


        /**
         *  Creating value array for DB prepare.
         */
        $prepare_values     = $product_variation_id_arr;
        $prepare_values[]   = $coupon_code;
        
        if(is_user_logged_in())
        {
            $prepare_values[] = !is_null( WC()->session ) ? WC()->session->get_customer_id() : get_current_user_id();
        }else{
            $prepare_values[] = $billing_email;
        }
        
        $prepare_values[] = $coupon_code;


        /**
         *  Perform the query
         */
        $final_sql = $wpdb->prepare($sql, $prepare_values);
        if($final_sql) //sql prepared correctly
        {
            $prev_order_count   = $wpdb->get_var( $final_sql );
        }

        
        /**
         *  Check and return
         */
        if(is_string($prev_order_count)) // DB operation was success
        {
            return ( 0 === absint($prev_order_count) );

        }else //may be unable to perform the DB operation 
        {
            return $valid;
        }
    }

    /**
     *  Get cart item price based on tax settings
     * 
     *  @since 2.1.1
     *  @param WC_Product   $_product  Product object
     *  @param float        Product price
     */
    private function get_product_cart_item_price($_product)
    {
        if(wc_prices_include_tax()) 
        {                     
            return wc_get_price_including_tax( $_product );
        }else
        {          
            return wc_get_price_excluding_tax( $_product );
        }
    }
}
Wt_Smart_Coupon_Usage_Limit_Public::get_instance();