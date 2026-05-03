<?php
/**
 * Giveaway products public section
 *
 * @link       
 * @since 2.0.1     
 *
 * @package  Wt_Smart_Coupon  
 */
if (!defined('ABSPATH')) {
    exit;
}

if(!class_exists('Wt_Smart_Coupon_Giveaway_Product')) /* common module class not found so return */
{
    return;
}

class Wt_Smart_Coupon_Giveaway_Product_Public extends Wt_Smart_Coupon_Giveaway_Product
{
    public $module_base='giveaway_product';
    public $module_id='';
    public static $module_id_static='';
    private static $instance = null;
    
    public static $bogo_allowed_options_to_display_products=array('specific_product', 'same_product_in_the_cart');
    public static $bogo_eligible_session_id='wt_sc_bogo_eligible';
    public static $break_add_to_cart_loop_session_id='wt_sc_break_add_to_cart_loop'; /* this is used to break add to cart indefinite looping when cart contents convert as giveaway */
    public static $giveaway_count_adjust=false;
    public static $giveaway_fully_availed_flag='fully_availed'; /* value to indicate giveaway was fully availed. This value is used to hide the giveaway eligible message */
    
    private static $cheapest_giveaway_loop_count = 0;
    private static $cheapest_giveaway_frequency_backup = 0;

    public static $bogo_discounts=array(); /* BOGO coupon type giveaway total discount */
    
    private static $allowed_customer_gets_cheapest_giveaway = array('any_product_from_category', 'any_product_from_store', 'any_product_from_category_in_the_cart'); /* `Customer gets` allowed for cheapest giveaway option. */

    /** 
     *  Reason for auto add disabled 
     *  This is using for `convert existing cart item as giveaway` functionality.
     * 
     *  @since  2.2.0
     *  @var    int
     *  @see    Wt_Smart_Coupon_Giveaway_Product_Public::is_auto_add_giveaway()    
     */
    private static $giveaway_auto_add_disabled_reason = 0; 

    public function __construct()
    {
        $this->module_id=Wt_Smart_Coupon::get_module_id($this->module_base);
        self::$module_id_static=$this->module_id;

        /**
         *  Ajax hooks
         * 
         */ 
         $this->hooks_ajax(); 


        /**
         *  Display hooks
         * 
         */
        $this->hooks_display();
        

        
        /**
         * 
         * Value update/calculation hooks
         * 
         */
        $this->hooks_calc_and_update();
        
        
        /**
         * 
         * Action/processing hooks
         * 
         */
        $this->hooks_actions_and_processing();

       

        /**
         *  
         * Other hooks
         * 
         */
        $this->hooks_others();

    }

    
    /**
     * Get Instance
     */
    public static function get_instance()
    {
        if(is_null(self::$instance))
        {
            self::$instance = new Wt_Smart_Coupon_Giveaway_Product_Public();
        }

        return self::$instance;
    }


    /**
     *  This function lists all ajax hooks. 
     *  
     *  @since 2.0.8
     */
    public function hooks_ajax()
    {
        // Ajax hook to return variation ID on giveaway product attribute change
        add_action('wc_ajax_update_variation_id', array($this, 'ajax_find_matching_product_variation_id'));

        // Ajax function for adding Giveaway products into cart when customer clicks on the product.
        add_action('wc_ajax_wt_choose_free_product', array($this, 'add_to_cart'));
    }

    /**
     *  This function lists all hooks related to action/processing. 
     *  
     *  @since 2.0.8
     */
    public function hooks_actions_and_processing()
    {
        // ===== wp_loaded ================================

        // Remove free product when coupon removed.
        add_action('woocommerce_removed_coupon', array($this, 'remove_free_product_from_cart'), 10, 1);

        // Remove giveaway available session if exists.
        add_action('woocommerce_removed_coupon', array($this, 'remove_giveaway_available_session'), 10, 1);


        /**
         *  Add giveaway product
         *  This is applicable for `specific_product` when single giveaway item with 100% discount and apply repeatedly enabled.
         *  
         */
        add_action('woocommerce_add_to_cart',  array($this, 'check_and_add_giveaway_on_add_to_cart'), 111, 6);
        add_action('woocommerce_after_cart_item_quantity_update', array($this, 'check_to_add_giveaway'), 111, 6);


        /** 
         *  Check the newly added item is eligible as giveaway product. 
         *  If yes then convert the item as giveaway 
         *  This is applicable for any_product_from_category, any_product_from_store, any_product_from_category_in_the_cart
         *  
         */
        add_action('woocommerce_add_to_cart', array($this, 'applicable_for_giveaway'), 111, 6);
        add_action('woocommerce_after_cart_item_quantity_update', array($this, 'reg_applicable_for_giveaway'), 111, 6);

        //=====================================================================

  
        
        

        // ===== wp ==========================================================

        /**
         *  @since 2.0.5
         *  
         *  Trigger WC coupon is_valid check 
        */
        add_action('wp', array( $this, 'trigger_coupon_is_valid'));


        /** 
         * @since 2.0.6
         * 
         * Exclude giveaway products from other coupons 
         */
        add_filter('woocommerce_coupon_is_valid_for_product', array($this, 'exclude_giveaway_from_other_discounts'), 10, 4);


        /** 
         *  @since 2.0.8
         * 
         *  To add giveaway products automatically to the cart.
         *  This is applicable for `specific_product` BOGO and normal coupons with giveaway 
         */
        add_action('woocommerce_applied_coupon', array($this, 'add_free_product_into_cart'), 10, 1);


        /** 
         *  @since 2.2.0
         * 
         *  To work BOGO coupons in checkout page
         */
        add_action('woocommerce_applied_coupon', array($this, 'add_giveaway_in_checkout'), 10, 1);
        //=====================================================================

        
          
        

        // ===== template_redirect ============================================

        /* Remove free products from the cart if cart is empty */
        add_action('template_redirect', array($this, 'check_any_free_products_without_coupon'), 15);

        /* Adjust giveaway count when eligibility changed */
        add_action('template_redirect', array($this, 'adjust_giveaway_count_when_eligibility_changed'), 15);

        
        /* Display giveaway products in the cart page or add giveaway products to the cart */
        add_action('template_redirect', array($this, 'add_giveaway_products_with_coupon'), 16);


        /**
         *  Check and convert the cart items based on `cheapest as giveaway` option
         *  
         *  @since 2.0.7
         */
        add_action('template_redirect', array($this, 'convert_cheapest_as_giveaway'), 40);

        //=====================================================================

        /**
         *  To check and show giveaway message when giveaway product removed (message not showing in block cart/checkout).
         *  
         *  @since 2.4.0
         */
        add_action( 'woocommerce_cart_item_removed', array( $this, 'adjust_giveaway_count_when_eligibility_changed' ) );

        /**
         *  Check if the Undo product is applicable for the giveaway
         *  
         *  @since 2.4.2
         */
        add_action( 'woocommerce_cart_item_restored', array( $this, 'check_undo_products_for_giveaway'), 10, 2 );
    }

    /**
     *  This function lists all hooks related to value updates/calculation. 
     *  
     *  @since 2.0.8 
     */
    public function hooks_calc_and_update()
    {
        // Update cart item value for applying price before tax calculation.
        add_filter('woocommerce_get_cart_item_from_session', array($this, 'update_cart_item_in_session'), 15, 3); 

        // Update cart Item data
        add_filter('woocommerce_add_cart_item', array($this, 'update_cart_item_values'), 15, 4); 
 
        // Remove/hide giveaway product meta data from item meta array
        add_filter('woocommerce_order_item_get_formatted_meta_data', array($this, 'unset_free_product_order_item_meta_data'), 10, 2);

        // Update total after discount
        add_filter('woocommerce_after_calculate_totals', array($this, 'discounted_calculated_total'), 1000, 1);

        // Update gift item details as order item meta when creating an order    
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_free_product_details_into_order'), 10, 4);

        add_action( 'woocommerce_order_after_calculate_totals', array( $this, 'update_order_total' ), 100, 2 );
    }

    
    /**
     *  This function lists all hooks related to display. 
     *  
     *  @since 2.0.8
     */
    public function hooks_display()
    {
        // Mention its a giveaway product in the cart item table
        add_action('woocommerce_after_cart_item_name', array($this, 'display_giveaway_product_description'), 10, 1);

        // Show updated cart item price in the table
        add_filter('woocommerce_cart_item_price', array($this, 'update_cart_item_price'), 10, 2);

        // Set cart item quantity as non editable
        add_filter('woocommerce_cart_item_quantity', array($this, 'update_cart_item_quantity_field'), 5, 3);

        // Show discount rows in the cart/order checkout summary section
        add_action('woocommerce_cart_totals_before_shipping', array($this, 'add_give_away_product_discount'), 10, 0); 
        add_action('woocommerce_review_order_before_shipping', array($this, 'add_give_away_product_discount'), 10, 0);

        // Show/Update subtotal HTML 
        add_filter('woocommerce_cart_item_subtotal', array($this, 'add_custom_cart_item_total'), 1000, 2);

        // Display order item totals
        add_filter('woocommerce_get_order_item_totals', array($this, 'woocommerce_get_order_item_totals'), 11, 3);

        // Alter the coupon title text when printing the coupon in My account, cart, checkout etc
        add_filter('wt_smart_coupon_meta_data', array($this, 'alter_coupon_title_text'), 10, 2);

        /**
         *  @since 2.0.5
         *  
         *  Alter coupon price section in order summary section 
        */
        add_filter('woocommerce_coupon_discount_amount_html', array( $this, 'alter_coupon_discount_amount_html'), 100, 2);
    
        /* Show giveaway eligible message for Any product from store(any_product_from_store), Any product from specific category(any_product_from_category), Any product from the same category as in cart(any_product_from_category_in_the_cart) */
        add_action('wp_head', array($this, 'show_giveaway_eligible_message'));


        /** 
         *  Add data to wc checkout data (For checkout/cart block)
         *  
         *  @since 2.3.0
         */
        add_filter( 'wbte_sc_alter_blocks_data', array( $this, 'add_blocks_data' ) );
    }

    
    /**
     *  This function lists all hooks other than above list 
     *  
     *  @since 2.0.8
     */
    public function hooks_others()
    {
        /**
         *  @since 2.0.5
         *  
         *  Scripts and styles for giveaway section 
         */
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        
        /**
         *  Enable Individual use option for `cheapest as giveaway` enabled coupons
         *  
         *  @since 2.0.7
         */
        add_filter('woocommerce_coupon_get_individual_use', array($this, 'set_cheapest_giveaway_coupon_to_individual_use'), 20, 2);
        
        
        /**
         *  Force remove coupons that can be used along with individual use coupons
         *  
         *  @since 2.0.7  
         */
        add_filter('woocommerce_apply_individual_use_coupon', array($this, 'force_remove_individual_use_allowed_coupons'), 20, 3);
        
        
        /**
         *  Do not allow other coupons along with `cheapest as giveaway` enabled coupons
         * 
         *  @since 2.0.7
         */
        add_filter('woocommerce_apply_with_individual_use_coupon', array($this, 'reject_other_coupon_along_with_cheapest_giveaway_coupon'), 20, 3);
       

        /** 
         *  Add block to the block list
         *  
         *  @since 2.3.0
         */
        add_filter( 'wt_sc_blocks_register', array( $this, 'register_blocks' ) );  
    }


    /**
     *  Show giveaway available message after applying a coupon. Applicable for any_product_from_category, any_product_from_store, any_product_from_category_in_the_cart
     *  Hooked into `wp_head` 
     * 
     *  @since 2.0.4
     *  @since 2.0.7  Added any product from category in the cart option
     *  @since 2.3.0  Disabled giveaway available message on block cart/checkout
     */
    public function show_giveaway_eligible_message()
    {
        $cart = WC()->cart;
        
        if(is_null($cart))
        {
            return;
        }

        // Disable message on block cart/checkout
        if ( function_exists( 'has_block' ) && 
            ( ( is_checkout() && has_block( 'woocommerce/checkout' ) ) || ( is_cart() && has_block( 'woocommerce/cart' ) ) )
        ) {
            return;
        }

        $coupons = $cart->get_applied_coupons();
        $coupons = (!is_array($coupons) ? array() : $coupons);
        
        $bogo_eligible = self::get_bogo_eligible_session();
        
        /* Alter the message or set as empty to hide the message on current page */
        $bogo_eligible = apply_filters('wt_sc_alter_giveaway_eligible_message', $bogo_eligible);
        $bogo_eligible = (!is_array($bogo_eligible) ? array() : $bogo_eligible);
        
        foreach($bogo_eligible as $coupon_code => $message)
        {
            if(in_array($coupon_code, $coupons))
            {
                if("" !== $message && $message !== self::$giveaway_fully_availed_flag)
                {
                    wc_add_notice($message, 'notice');
                }
            }else
            {
                self::remove_bogo_eligible_session($coupon_code);
            }
        }
    }

    
    /**
     *  For specific product(specific_product):
     *  Add hook to show products under the cart. Check for auto add eligibility and add the product.
     * 
     *  For same product in cart (same_product_in_the_cart):
     *  Add hook to show products.  Check for auto add eligibility and add the product.
     * 
     *  For others(any_product_from_category, any_product_from_store, any_product_from_category_in_the_cart)
     *  If eligible, show giveaway available message 
     * 
     *  @since 2.0.4 Added BOGO option compatibility
     *  @since 2.0.7 Added compatibility for any product from category in the cart 
     *  @since 2.1.0 Auto adding giveaway option added for  `specific_product` and `same_product_in_the_cart`
     */
    public function add_giveaway_products_with_coupon()
    {
        $cart = self::get_cart_object();

        if(is_null($cart) || $cart->is_empty())
        {
            return;
        }


        $coupons = $cart->get_applied_coupons();
        $coupons = (!is_array($coupons) ? array() : $coupons);

        foreach($coupons as $coupon_code)
        {

            $coupon_code = wc_format_coupon_code($coupon_code);
            $coupon = new WC_Coupon($coupon_code);
            
            if(!$coupon->get_id())
            {
                continue;
            }

            $coupon_id  = $coupon->get_id();   
            
            if(self::is_bogo($coupon))
            {
                $bogo_customer_gets = $this->get_coupon_meta_value($coupon_id, '_wt_sc_bogo_customer_gets');
                
                if('specific_product' === $bogo_customer_gets)
                {
                    $this->process_specific_product_giveaway($coupon_id, $coupon_code);

                }elseif('any_product_from_category' === $bogo_customer_gets || 'any_product_from_store' === $bogo_customer_gets || 'any_product_from_category_in_the_cart' === $bogo_customer_gets) /* any product from store or any product from specific category */
                {
                    $this->store_giveaway_available_message($coupon_id, $bogo_customer_gets); /* show message */

                }else
                {
                    /**
                     *  same_product_in_the_cart
                     */
                    $free_products = $this->get_coupon_product_list_for_any_product_from_cart($coupon_id, $coupon_code);

                    if(!empty($free_products))
                    {  
                        $this->set_hook_to_show_giveaway_products();
                    }
                }
                
            }else
            {
                $this->process_specific_product_giveaway($coupon_id, $coupon_code);
            }
        }
    }

    
    /**
     *  This function will decide whether to show or add to cart get the giveaway items
     *  For BOGO specific products and normal coupons
     *  
     *  @since 2.0.4
     *  @since 2.0.8    Automatic giveaway adding hook moved.
     *  @since 2.1.0    Giveaway auto add compatibility.
     *  @param      int         $coupon_id          ID of coupon
     *  @param      string      $coupon_code        Coupon code
     */
    public function process_specific_product_giveaway($coupon_id, $coupon_code)
    {
        $free_products = self::get_giveaway_products($coupon_id); 

        if(!empty($free_products) && !$this->is_auto_add_giveaway($coupon_id, $coupon_code, $free_products, 'specific_product'))
        {  
            $this->set_hook_to_show_giveaway_products();
        }
    }
    
    /** 
     *  This function will hook a callback function to show giveaway products in the cart page
     *  
     *  @since 2.0.4
     */
    public function set_hook_to_show_giveaway_products()
    { 
        add_action('woocommerce_after_cart_table', array($this, 'display_giveaway_products'), 1); 
    }

    /**
     *  Add required scripts/styles for giveaway products
     *  
     *  @since 2.0.5
     */
    public function enqueue_scripts()
    { 
        if(function_exists('is_cart') && is_cart())
        {
            wp_enqueue_style('wt-smart-coupon-giveaway', plugin_dir_url( __FILE__ ).'assets/css/main.css', array(), WEBTOFFEE_SMARTCOUPON_VERSION, 'all');
            wp_enqueue_script('wt-smart-coupon-giveaway', plugin_dir_url( __FILE__ ).'assets/js/main.js', array('jquery'), WEBTOFFEE_SMARTCOUPON_VERSION, false);
        }   
    }

    /**
     *  Prepare array of giveaway list from cart items. This will exclude free items 
     */
    public function prepare_cart_items_as_giveaway($qty_price_data)
    {
        $new_coupon_products=array();
        foreach(WC()->cart->get_cart() as $cart_item)
        {
            if(self::is_a_free_item($cart_item))
            {
                continue;
            }
            $item_id = ($cart_item['variation_id']>0 ? $cart_item['variation_id'] : $cart_item['product_id']);
            $new_coupon_products[$item_id]=$qty_price_data;
            if( 0 < $cart_item['variation_id'] ){
                $new_coupon_products[$item_id]['attributes'] = $cart_item['variation'];
            }
        }
        return $new_coupon_products;
    }

    /**
     * Callback function for displaying giveaway products in the cart page.
     * @since 1.0.0
     * @since 1.3.5     [Bug fix] Variation product image not displaying on checkout page
     * @since 2.0.4     Added compatibility with BOGO type coupons
     * @since 2.0.5     Auto hiding giveaway products when all eligible products was added to cart
     * @since 2.3.0     Method name updated from `display_give_away_products` to `display_giveaway_products`
     * 
     * @param bool      $only_for_bogo_coupons      Only show giveaway products for BOGO coupons(Optional). Default: false
     */
    public function display_giveaway_products()
    {
        global $woocommerce;
        $applied_coupons  = $woocommerce->cart->applied_coupons;
        if(empty($applied_coupons))
        {
            return;
        }

        $free_products=array();
        $add_to_cart_all=array();           
        $show_quantity_option=array();           
        foreach($applied_coupons as $coupon_code)
        {
            $coupon_code=wc_format_coupon_code($coupon_code);
            $coupon = new WC_Coupon($coupon_code);
            if(!$coupon)
            {
                continue;
            }

            $coupon_id=$coupon->get_id();
            $add_to_cart_all[$coupon_id]=false;
            $show_quantity_option[$coupon_id]=0;

            $qty_price_data = $this->get_qty_price_data($coupon_id);

            if(self::is_bogo($coupon))
            {
                $bogo_customer_gets = $this->get_coupon_meta_value($coupon_id, '_wt_sc_bogo_customer_gets');
                if(in_array($bogo_customer_gets, self::$bogo_allowed_options_to_display_products))
                {
                    if('specific_product' === $bogo_customer_gets)
                    {
                        $bogo_product_condition=$this->get_coupon_meta_value($coupon_id, '_wt_sc_bogo_product_condition');
                        
                        $bogo_products=$this->get_all_bogo_giveaway_products($coupon_id);

                        $frequency=$this->get_coupon_applicable_count($coupon_id, $coupon_code);
                       
                        /**
                         *  Giveaway max quantity checking
                         *  Note: `$bogo_products` is a reference argument for the below function 
                         */
                        $this->check_giveaway_max_quantity($coupon_code, $coupon_id, $bogo_customer_gets, $bogo_product_condition, $bogo_products, $frequency);

                        $free_products[$coupon_code]=$bogo_products;                    

                        if('and'===$bogo_product_condition)
                        {
                            $add_to_cart_all[$coupon_id]=true; /* no single add to cart button */
                        }

                    }elseif('same_product_in_the_cart' === $bogo_customer_gets)
                    {
                        $coupon_products = $this->get_coupon_product_list_for_any_product_from_cart($coupon_id, $coupon_code);
                        
                        if(!empty($coupon_products))
                        {
                            $free_products[$coupon_code] = $coupon_products;
                            $show_quantity_option[$coupon_id] = $this->prepare_balance_quantity_for_same_product_in_cart($coupon_id, $coupon_code);
                        }                    
                    }
                }  
            }else
            {
                // Get cart item data
                $total_qty = self::get_total_coupon_cart_item_qty( $coupon_code ); //total cart quantity for the coupon
                $total_qty = ( is_array( $total_qty ) && !empty( $total_qty ) ? array_sum( $total_qty ) : 0 );

                /* allowed maximum quantity */
                $discount_quantity = $this->get_non_individual_discount_quantity( $coupon_id );
                
                if( $discount_quantity>$total_qty ) /* balance quantity exists. Otherwise it will not show the giveaway products */
                {
                    $free_product_id_arr = self::get_giveaway_products( $coupon_id );
                    if( !empty( $free_product_id_arr ) )
                    {
                        $qty_price_arr = array_fill( 0, count( $free_product_id_arr ), $qty_price_data );
                        $new_coupon_products = array_combine( $free_product_id_arr, $qty_price_arr );
                        $free_products[ $coupon_code ] = $new_coupon_products;
                    }
                }
            }
        }

        if(empty($free_products))
        {
            return;  
        }

        include_once plugin_dir_path( __FILE__ ).'views/_cart_giveaway_products.php';
    }

    /**
     *  Ajax action function for getting variation id
     * 
     *  @since 1.0.0
     *  @since 2.0.8 Implemented customized messages option
     */
    public function ajax_find_matching_product_variation_id()
    {
        $out=array('status'=>false, 'status_msg'=>__('Invalid request', 'wt-smart-coupons-for-woocommerce-pro'));
        
        if(check_ajax_referer( 'wt_smart_coupons_public', '_wpnonce', false))
        {         
            if(isset($_POST['attributes']) && isset($_POST['product']))
            {
                $product_id = Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST['product'], 'int');
                $attributes = Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST['attributes'], 'text_arr');
                if($product_id!='' && !empty($attributes))
                {
                    $variation_id=$this->find_matching_product_variation_id($product_id, $attributes);
                    $_product = wc_get_product($variation_id);
                    $image = $_product ? wp_get_attachment_image_src( $_product->get_image_id(), 'woocommerce_thumbnail' ) : false; 
                    $img_url = '';
                    if( $image && is_array( $image ) && isset( $image[0] ) )
                    {
                        $img_url = $image[0];
                    }
                    if($this->is_purchasable($_product))
                    {
                        $out = array( 
                            'variation_id' => $variation_id, 
                            'status'       => true, 
                            'status_msg'   => __( 'Success', 'wt-smart-coupons-for-woocommerce-pro' ) , 
                            'img_url'      => $img_url 
                        );
                    }else
                    {
                        $out['status_msg'] = $this->get_customized_text('non_purchasable_giveaway_varaition');
                    }
                }    
            }
        }

        echo json_encode($out);
        wp_die();
    }

    /**
     * Function for getting variation id from product and selected attributes
     * @param $prodcut_id Given Product Id.
     * @param $attributes Attribute values ad key value pair.
     * @since 1.0.0
     */
    public function find_matching_product_variation_id($product_id, $attributes)
    {
        return (new \WC_Product_Data_Store_CPT())->find_matching_product_variation(
            new \WC_Product($product_id),
            $attributes
        );
    }

    /**
     * Helper function to get giveaway product discount text
     * @since 1.2.4
     * @since 2.0.4 Added compatibility with BOGO type coupons
     */
    public function get_give_away_discount_text($coupon_code=0, $product_data=array())
    {
        if($coupon_code>0)
        {
            if(is_int($coupon_code))
            {
                $coupon_id = $coupon_code;
            } else {
                $coupon_id  = wc_get_coupon_id_by_code( $coupon_code );
            }
            $wt_product_discount_amount     = get_post_meta( $coupon_id, '_wt_product_discount_amount',true );
            $wt_product_discount_type       = get_post_meta( $coupon_id, '_wt_product_discount_type',true );
        
        }else
        {
            $dummy_qty_price=self::get_dummy_qty_price();
            $product_data=(empty($product_data) ? $dummy_qty_price : $product_data); 
            $wt_product_discount_amount=(isset($product_data['price']) ? $product_data['price'] : $dummy_qty_price['price']);
            $wt_product_discount_type=(isset($product_data['price_type']) ? $product_data['price_type'] : $dummy_qty_price['price_type']);
        }
      
        
        if(''==$wt_product_discount_amount  || ''==$wt_product_discount_type)
        {
            return '100%';
        }
        switch($wt_product_discount_type)
        {
            case 'percent': 
                $discount_text = $wt_product_discount_amount.'%';
                break;
            default:
                $discount_text = Wt_Smart_Coupon_Admin::get_formatted_price( $wt_product_discount_amount );
        }
        return $discount_text;
    }


    /**
     *  Applicable for: specific_product, same_product_in_the_cart
     *  This method will be called when product quantity is updated
     *  
     *  @since 2.0.4
     *  @since 2.1.0    Added same_product_in_the_cart option.
     *  @param      string      $cart_item_key      Cart item key
     *  @param      int         $quantity
     *  @param      int         $old_quantity
     *  @param      object      $cart
     */
    public function check_to_add_giveaway($cart_item_key, $quantity, $old_quantity, $cart)
    {
        $cart_item_data = isset($cart->cart_contents[$cart_item_key]) ? $cart->cart_contents[$cart_item_key] : null;
        
        if(is_null($cart_item_data))
        {
            return;
        }

        if(self::is_a_free_item($cart_item_data))
        {
            return; /* already a free item so no need to check */
        }
        
        if($old_quantity<$quantity) //quantity increased
        {
            $cart=WC()->cart;
            $coupons=$cart->get_applied_coupons();
            
            foreach($coupons as $coupon_code)
            {
                $coupon_code=wc_format_coupon_code($coupon_code);
                $coupon = new WC_Coupon($coupon_code);
                
                if(!$coupon)
                {
                    continue;
                }
                
                if(self::is_bogo($coupon))
                {
                    $coupon_id=$coupon->get_id();
                    $bogo_customer_gets = $this->get_coupon_meta_value($coupon_id, '_wt_sc_bogo_customer_gets');
                    
                    if('specific_product' === $bogo_customer_gets || 'same_product_in_the_cart' === $bogo_customer_gets)
                    {
                        /* recalculate the apply frequency quantity with the newly added quantity */
                        $this->recalculate_apply_frequency_count($coupon);

                        $this->add_free_product_into_cart($coupon_code);
                    }
                    $this->adjust_giveaway_count_when_eligibility_changed();
                }
            }
        }else{

            // Only on the REST API; otherwise, it will be handled via the wp_loaded hook.
            if ( defined( 'REST_REQUEST' ) ) {
                
                // Re-calculate the eligibility.
                $coupons = $cart->get_applied_coupons();
                foreach ( $coupons as $coupon_code ) {
                    $coupon_code    = wc_format_coupon_code( $coupon_code );
                    $coupon         = new WC_Coupon( $coupon_code );
                    if ( ! $coupon ) {
                        continue;
                    }
                    if ( self::is_bogo( $coupon ) ) {
                        $this->recalculate_apply_frequency_count( $coupon );
                    }
                }

                // Adjust the giveaway quantity.
                $this->adjust_giveaway_count_when_eligibility_changed();
            }
        }
    }


    /**
     * Get free product added success message
     * @since 1.3.5
     * @since 2.0.4 Code updated
     *              New argument $giveaway_data - Giveaway price, price type, quantity
     * @since 2.0.8 Implemented customized messages option
     */
    public function get_free_product_added_message($product, $coupon_code, $giveaway_data=array())
    {
        $message = '';
        
        if(is_int($product))
        {
            $product = wc_get_product($product);
        }
        
        if($product)
        {
            if(empty($giveaway_data))
            {
                $giveaway_data=$this->get_product_giveaway_data($product->get_id(), $coupon_code);
            }
            
            if($this->is_full_free_item($product, $giveaway_data))
            {
                $message = $this->get_customized_text('full_free_giveaway_added_to_cart', array('coupon_code' => $coupon_code));

            }else
            {
                $discount_text = $this->get_give_away_discount_text(0, $giveaway_data);
                $message = sprintf(__("Surprise! A free product has been added to your cart with a %s discount.", 'wt-smart-coupons-for-woocommerce-pro'), $discount_text);
            }
        }

        return apply_filters('wt_sc_alter_free_product_added_message', $message, $product, $coupon_code);
    }

    /**
     *  Add Giveaway product into cart
     *  Checks auto add possibility and add the product. Applicable for `specific_product` and `same_product_in_the_cart
     * 
     *  @since 1.0.0
     *  @since 1.3.4  [Bug fix] Giveaway product is added repeatedly when logged in back to the site.
     *  @since 2.0.4  Code updated
     *                Added compatibility for BOGO coupon types
     *  @since 2.1.0  Giveaway auto add compatibility.
     *  @since 2.2.0  Convert existing cart item as giveaway for `specific_product`
     */
    public function add_free_product_into_cart($coupon_code)
    {
        if(is_null($cart = self::get_cart_object()))
        {
            return;
        }


        // Prevent indefinite loop
        if ( !is_null( WC()->session ) && ! is_null( WC()->session->get( self::$break_add_to_cart_loop_session_id ) ) ) {
            WC()->session->set(self::$break_add_to_cart_loop_session_id, null);
            return; 
        }

        $coupons        = $cart->get_applied_coupons();
        $coupon_code    = wc_format_coupon_code($coupon_code);       
        
        if(!in_array($coupon_code, $coupons)) /* current coupon code not in the applied coupon list */
        {
            return;
        }        

        $coupon             = new WC_Coupon($coupon_code);
        $coupon_id          = $coupon->get_id();
        $bogo_customer_gets = $this->get_coupon_meta_value($coupon_id, '_wt_sc_bogo_customer_gets');

        if(self::is_bogo($coupon) && 'specific_product' !== $bogo_customer_gets && 'same_product_in_the_cart' !== $bogo_customer_gets) 
        {
            return; /* If coupon type is BOGO, this option is allowed for `specific product` and `same_product_in_the_cart` options */
        }

        if('same_product_in_the_cart' === $bogo_customer_gets)
        { 
            $this->same_product_auto_add_to_cart($coupon_id, $coupon_code);
            return;
        }
        
        $free_products = self::get_giveaway_products($coupon_id);
        
        if(!empty($free_products))
        {
            if(
                $this->is_auto_add_giveaway($coupon_id, $coupon_code, $free_products, $bogo_customer_gets)  // Auto add allowed.
                || 5 === self::$giveaway_auto_add_disabled_reason  // Auto add disabled, because variable product in the giveaway list. But we want to do an `existing item conversion` checking. 
            ) {
                $success_message_arr = array();

                foreach($free_products as $item_id)
                {
                    $giveaway_data = $this->get_product_giveaway_data($item_id, $coupon_code);

                    /* This function will prepare quantity based on coupon frequency. If apply repeatedly enabled */
                    $giveaway_qty = $this->prepare_quantity_based_on_apply_frequency($coupon_id, $giveaway_data['qty']);
                
                    //get cart item data
                    $product_cart_item_qty = self::get_product_cart_item_qty($item_id, $coupon_code);

                    if(empty($product_cart_item_qty)) /* product does not exists in the cart */
                    {
                        $giveaway_added = $this->check_convert_existing_to_giveaway_specific_product( $item_id, $giveaway_qty, $coupon_code, $coupon_id );
                        $success_message = $this->get_free_product_added_message(wc_get_product($item_id), $coupon_code, $giveaway_data);
                        
                        if($giveaway_added && "" !== $success_message)
                        {
                            $success_message_arr[] = $success_message;
                        }

                    }else
                    {
                        $total_qty_in_cart = array_sum($product_cart_item_qty);
                        
                        if($total_qty_in_cart < $giveaway_qty) //lesser qty in cart. Case when apply repeatedly enabled and customer increased the cart item quantity
                        {
                            $this->check_convert_existing_to_giveaway_specific_product( $item_id, ( $giveaway_qty - $total_qty_in_cart ), $coupon_code, $coupon_id );
                        }
                    }
                }

                if(!empty($success_message_arr))
                {
                    if(1 === count($success_message_arr))
                    {
                        wc_add_notice($success_message_arr[0], 'success');
                    }else
                    {
                        /**
                         *  Alter message when multiple giveaways added once.
                         * 
                         *  @since 2.1.1
                         */
                        $success_message = apply_filters('wt_sc_alter_multiple_giveaway_added_msg', __("Lucky You! Your cart is now loaded with incredible giveaway goodies!", "wt-smart-coupons-for-woocommerce-pro"));
                        
                        wc_add_notice($success_message, 'success');
                    }
                }

            }else
            {
                $this->set_hook_to_show_giveaway_products(); 
            }
        }
    }

    /** 
     *  This function will store the message for customer to add products to avail the giveaway
     *  The stored message will show via wp_head hook. Applicable for any_product_from_category, any_product_from_store, any_product_from_category_in_the_cart
     *  
     *  @since 2.0.4    
     *  @since 2.0.7 Added compatibility for `Any product from category in the cart` 
     *  @since 2.0.8 Implemented customized messages option
     *  @param      int         $coupon_id              ID of coupon
     *  @param      string      $bogo_customer_gets     BOGO customer gets option value
     */
    public function store_giveaway_available_message($coupon_id, $bogo_customer_gets)
    {
        $coupon_code    = wc_get_coupon_code_by_id($coupon_id);
        $message        = '';
        
        $bogo_eligible  = self::get_bogo_eligible_session();      
        $bogo_eligible  = (!isset($bogo_eligible[$coupon_code]) ? '' : $bogo_eligible[$coupon_code]);

        if('any_product_from_category' === $bogo_customer_gets || 'any_product_from_category_in_the_cart' === $bogo_customer_gets)
        {
            $bogo_free_categories = ('any_product_from_category' === $bogo_customer_gets ? $this->get_processed_category_list_for_bogo_eligible_msg( $coupon_code, $coupon_id ) : $this->get_cart_item_categories_for_coupon($coupon_code, $coupon_id)); 

            if(!empty($bogo_free_categories))
            {           
                $category_ids = array_keys($bogo_free_categories);

                if('any_product_from_category_in_the_cart' === $bogo_customer_gets)
                {
                    /**
                     *  In this type chance for comma separated category ids.
                     *  The $category_ids will be like: array(1, 10, 15, '12,17,33', '10,4', 4)
                     *  
                     */
                    $category_ids = explode(",", implode(",", $category_ids));
                }

                $cat_arr = get_terms(array(
                    'taxonomy'      => 'product_cat',
                    'orderby'       => 'name',
                    'hide_empty'    => false,
                    'include'       => $category_ids,
                ));

                if(is_array($cat_arr) && $bogo_eligible != self::$giveaway_fully_availed_flag)
                {
                    $cat_link_arr=array();
                    
                    foreach($cat_arr as $cat)
                    {
                        $cat_link_arr[] = '<a href="'.esc_attr(get_term_link($cat->term_id)).'" class="wt_sc_giveaway_category_link">'.esc_html($cat->name).'</a>';
                    }
                              
                    $message = $this->get_customized_text('add_product_from_the_cat_as_giveaway', array('coupon_code' => $coupon_code, 'giveaway_categories' => implode(", ", $cat_link_arr)));                                  
                    
                    self::remove_bogo_eligible_session($coupon_code); //in some cases of `any_product_from_category_in_the_cart` the message need to be updated.
                    self::set_bogo_eligible_session($coupon_id, $message);
                }
            }

        }elseif('any_product_from_store' === $bogo_customer_gets) /* when coupon condition is `all products from store` */
        {
            $message = $this->get_customized_text('add_any_product_from_the_store_as_giveaway', array('coupon_code' => $coupon_code));
            self::set_bogo_eligible_session($coupon_id, $message);
        }
    }

    /** 
     *  Check customer was added the full eligible quantities of free products.
     *  This function was using to toggle the giveaway available message. Applicable for any_product_from_category, any_product_from_store, any_product_from_category_in_the_cart
     *  
     *  @since      2.0.4    
     *  @param      int         $coupon_id              ID of coupon
     *  @param      string      $coupon_code            Coupon code
     *  @param      int         $max_qty_allowed        Maximum giveaway quantity allowed. `Apply frequency` calculation included.
     *  @param      int         $total_qty_in_cart      Total quantity of giveaway in the cart
     */
    public static function set_bogo_fully_availed( $coupon_id, $coupon_code, $max_qty_allowed, $total_qty_in_cart ) { 
        
        if ( $max_qty_allowed <= $total_qty_in_cart ) {
            
            // All eligible quantities of free products are in the cart. So can remove the info message
            self::remove_bogo_eligible_session( $coupon_code );
            self::set_bogo_eligible_session( $coupon_id, self::$giveaway_fully_availed_flag );                   
        
        } else {
            
            /* This is to clear the existing eligible session. The value assigning hook will be called later. */
            self::remove_bogo_eligible_session( $coupon_code );
            self::set_bogo_eligible_session( $coupon_id, '' );
        }
    }

    /**
     *  Remove BOGO eligible session when the corresponding coupon was removed
     *  @since 2.0.4 
     *  @param coupon code
     */
    public static function remove_bogo_eligible_session($coupon_code)
    {
        $bogo_eligible=self::get_bogo_eligible_session();
        $coupon_code=wc_format_coupon_code($coupon_code);
        if(isset($bogo_eligible[$coupon_code]))
        {
            unset($bogo_eligible[$coupon_code]);
            WC()->session->set(self::$bogo_eligible_session_id, $bogo_eligible);
        }
    }

    /**
     *  Get BOGO eligible sessions if exists
     *  @since 2.0.4 
     *  @return     array   Empty array if not exists, otherwise an array with the session info
     */
    public static function get_bogo_eligible_session()
    {
        $bogo_eligible = !is_null( WC()->session ) ? WC()->session->get( self::$bogo_eligible_session_id ) : array();
        return (is_null($bogo_eligible) ? array() : $bogo_eligible);
    }

    /**
     *  Add the coupon code to BOGO eligible session array
     *  @since 2.0.4 
     *  @param int      coupon id
     *  @param string   value for BOGO eligible session. Here BOGO available message, BOGO fully availed info etc
     */
    public static function set_bogo_eligible_session($coupon_id, $data)
    {
        $bogo_eligible=self::get_bogo_eligible_session();
        $coupon_code=wc_format_coupon_code(wc_get_coupon_code_by_id($coupon_id));
        if(!isset($bogo_eligible[$coupon_code]) || (isset($bogo_eligible[$coupon_code]) && $bogo_eligible[$coupon_code]==""))
        {
            $bogo_eligible[$coupon_code]=$data;
            WC()->session->set(self::$bogo_eligible_session_id, $bogo_eligible);
        }
    }

    /**
     *  Error/Validation messages when giveaway products are adding to cart.
     *  @since 2.0.4
     *  @since 2.0.5    Message is added to wc_notice for removing alert error message on ajax response
     *  @since 2.0.8    Implemented customized messages option
     *  @param string $reason reason string
     *  @param array $extra_args extra arguments to process the message
     *  @param string $coupon_type coupon type
     */
    public static function set_add_to_cart_messages($reason, $extra_args=array(), $coupon_type=null)
    {
        $out='';
        switch($reason)
        {
            case "product_id_missing":
            case "coupon_id_missing":
            case "product_not_under_giveaway_list":
                $out=__("Oops! It seems like you've made an invalid request. Please try again.", 'wt-smart-coupons-for-woocommerce-pro');
                break;
            case "product_is_not_a_bogo_product":
            case "given_product_is_not_under_the_category":
            case "non_free_item_of_the_given_category_product_not_in_the_cart":
            case "non_free_product_not_found_in_the_cart": //`same_product_in_the_cart`
                $out=__("Oops! It seems like you've moved an invalid product to cart. Please try again.", 'wt-smart-coupons-for-woocommerce-pro');
                break;
            case "product_max_quantity_reached":
                $out = __("You've exceeded the maximum quantity of products to avail the giveaway.", 'wt-smart-coupons-for-woocommerce-pro');
                break;
            case "coupon_max_quantity_reached":
                $coupon_code = wc_get_coupon_code_by_id($extra_args['coupon_id']);
                $out = $this->get_customized_text($reason, array('coupon_code' => $coupon_code));
                break;
            case "no_free_product_in_the_cart":
                $out=__("Something went wrong! It seems like there are no products available for this coupon. Please contact our support team.", 'wt-smart-coupons-for-woocommerce-pro');
                break;
            case "already_availed_bogo":
                $coupon_code = wc_get_coupon_code_by_id($extra_args['coupon_id']);
                $out = $this->get_customized_text($reason, array('coupon_code' => $coupon_code));
                break;
            default:
                $out=__("Oops! It seems like you've made an invalid request. Please try again.", 'wt-smart-coupons-for-woocommerce-pro');
        }

        if(isset($extra_args['apply_frequency']) && 'repeat'==$extra_args['apply_frequency'])
        {
            $out.=" ".__("Please add more products to cart to avail more giveaway.", 'wt-smart-coupons-for-woocommerce-pro');
        }

        $msg = apply_filters('wt_sc_alter_giveaway_addtocart_messages', $out, $reason, $extra_args, $coupon_type);

        if("" !== $msg) //sometimes some messages are hidden
        {
            wc_add_notice($msg, 'error');
            wc_print_notices();
        }
    }

    /**
     *  Ajax action function for adding Giveaway products into cart.
     *  @since 1.0.0
     *  @since 2.0.4 Added compatibility with BOGO type coupons 
     */
    public function add_to_cart()
    {
        check_ajax_referer( 'wt_smart_coupons_public', '_wpnonce' );

        $coupon_id = (isset($_POST['coupon_id']) ?  absint($_POST['coupon_id']) : 0);
        $product_id = (isset($_POST['product_id']) ?  absint($_POST['product_id']) : 0);
        $variation_id = (isset($_POST['variation_id']) ?  absint($_POST['variation_id']) : 0);
        $add_to_cart_all = (isset($_POST['add_to_cart_all']) ?  absint($_POST['add_to_cart_all']) : 0);
        $quantity = (isset($_POST['quantity']) ?  absint($_POST['quantity']) : 0);

        if(0===$coupon_id)
        {
            self::set_add_to_cart_messages("coupon_id_missing");
            wp_die();
        }
        if(0===$add_to_cart_all) /* individual add to cart */
        {
            if(0===$product_id)
            {
                self::set_add_to_cart_messages("product_id_missing", array('coupon_id'=>$coupon_id));
                wp_die();
            }
        }

        $coupon=new WC_Coupon($coupon_id);
        $coupon_code=wc_format_coupon_code($coupon->get_code());
        $bogo_customer_gets = $this->get_coupon_meta_value($coupon_id, '_wt_sc_bogo_customer_gets');
        if(self::is_bogo($coupon))
        {
            if('specific_product'===$bogo_customer_gets)
            {
                $this->specific_product_add_to_cart($coupon, $coupon_id, $coupon_code, $bogo_customer_gets, $product_id, $variation_id); 

            }elseif('same_product_in_the_cart'===$bogo_customer_gets)
            {
                $this->same_product_add_to_cart($coupon, $coupon_id, $coupon_code, $bogo_customer_gets, $product_id, $variation_id, $quantity);             
            
            }

            /** only the above 2 types are allowed in BOGO **/

        }else
        {
            $this->non_bogo_add_to_cart($coupon, $coupon_id, $coupon_code, $bogo_customer_gets, $product_id, $variation_id);
        }
        
        $notices=wc_get_notices('error');
        if(count($notices)>0)
        {
            $last_error=end($notices);
            if(isset($last_error['notice']))
            {
                echo '<ul class="woocommerce-error" role="alert">
                        <li>'.wp_kses_post($last_error['notice']).'</li>
                </ul>';
                wc_clear_notices(); /* to avoid notice printing on page refresh */ 
                wp_die();
            }
        }else
        {
            echo 'success'; /* no translation required */
            wp_die();
        }
    }


    /**
     *  Ajax sub function
     *  Add to cart for non BOGO coupon types
     *  @since 2.0.4
     */
    private function non_bogo_add_to_cart($coupon, $coupon_id, $coupon_code, $bogo_customer_gets, $product_id, $variation_id)
    {
        $free_product_id_arr=self::get_giveaway_products($coupon_id);
        $item_id=0;
        if(in_array($variation_id, $free_product_id_arr))
        {
            $item_id=$variation_id;

        }elseif(in_array($product_id, $free_product_id_arr))
        {
            $item_id=$product_id;
        }else
        { 
            self::set_add_to_cart_messages("product_not_under_giveaway_list", array('coupon_id'=>$coupon_id), $coupon->get_discount_type());
            wp_die();
        }

        //get cart item data
        $total_qty=self::get_total_coupon_cart_item_qty($coupon_code); //total cart quantity for the coupon
        
        /* allowed maximum quantity */
        $discount_quantity=$this->get_non_individual_discount_quantity($coupon_id);

        if(empty($total_qty)) /* product does not exists in the cart */
        {
            /* no free product in the cart */
            $this->add_item_to_cart(($variation_id>0 ? $variation_id : $product_id), $discount_quantity, $coupon_code);

        }else
        {            
            
            $total_qty=array_sum($total_qty);
            if($discount_quantity>$total_qty) /* balance quantity exists */
            {
                $this->add_item_to_cart(($variation_id>0 ? $variation_id : $product_id), ($discount_quantity - $total_qty), $coupon_code);

            }else
            {
                self::set_add_to_cart_messages(
                    "coupon_max_quantity_reached",
                    array(
                        'customer_gets'=>$bogo_customer_gets,
                        'max_qty'=>$discount_quantity,
                        'item_id'=>$item_id,
                        'coupon_id'=>$coupon_id,
                        'apply_frequency'=>'once',
                    ), 
                    $coupon->get_discount_type()
                );
                wp_die();
            }
        }
    }

    /**
     *  Get giveaway quantity for `any_product_from_store`, `same_product_in_the_cart`, `any_product_from_category_in_the_cart`, `any_product_from_category`
     *  Also this function will calculate the quantity based on `apply repeatedly` option
     *  
     *  @since 2.0.4
     *  @since 2.0.7    $frequency added as an optional argument. If frequency given then value will be prepared based on the given frequency
     *  @param  int      Coupon id
     *  @return int      Quantity
     */
    private function get_quantity_for_non_individual_quantity_bogo($coupon_id, $frequency = null)
    {
        /* allowed quantity */
        $item_qty = $this->get_non_individual_discount_quantity($coupon_id);

        /* apply repeatedly quantity preparation */
        return $this->prepare_quantity_based_on_apply_frequency($coupon_id, $item_qty, $frequency);
    }

    /**
     *  Get non individual discount quantity. 
     *  Applicable for Non BOGO and `any_product_from_store`, `same_product_in_the_cart`, `any_product_from_category_in_the_cart`, `any_product_from_category` in BOGO
     *  
     *  @since  2.0.5
     *  @since  2.1.1   Added compatibility for `any_product_from_category`
     *  @param  int     Coupon id
     *  @return int     Quantity
     */
    private function get_non_individual_discount_quantity($coupon_id)
    {
        /* Allowed quantity */
        $discount_quantity = (int) $this->get_coupon_meta_value($coupon_id, '_wt_product_discount_quantity');

        // For backward compatibility. Previously we have individual quantity for each category. So we are taking the quantity of first item, if there is no quantity is configured.
        if(0 === $discount_quantity && 'any_product_from_category' === $this->get_coupon_meta_value($coupon_id, '_wt_sc_bogo_customer_gets')) 
        {
            $bogo_free_categories = $this->get_coupon_meta_value( $coupon_id, '_wt_sc_bogo_free_categories' );
            $first_category_data = reset($bogo_free_categories);
            $discount_quantity = (int) isset($first_category_data['qty']) ? $first_category_data['qty'] : 0;
        }

        return (0 === $discount_quantity ? 1 : $discount_quantity);
    }
    
    /**
     *  Ajax sub function
     *  Add to cart products on `same_product_in_the_cart` as coupon option
     *  @since 2.0.4
     */
    private function same_product_add_to_cart($coupon, $coupon_id, $coupon_code, $bogo_customer_gets, $product_id, $variation_id, $quantity=0)
    {
        //check the current product is in the cart as non giveaway product
        if(!self::non_free_product_exists(array('product_id'=>$product_id, 'variation_id'=>$variation_id)))
        {
            self::set_add_to_cart_messages(
                "non_free_product_not_found_in_the_cart",
                array(
                    'customer_gets'=>$bogo_customer_gets,
                    'coupon_id'=>$coupon_id,
                    'apply_frequency'=>$this->get_coupon_meta_value($coupon_id, '_wt_sc_bogo_apply_frequency'),
                ), 
                self::$bogo_coupon_type_name
            );
            wp_die();
        }

        /* get balance giveaway allowed quantity */
        $balance_qty=$this->prepare_balance_quantity_for_same_product_in_cart($coupon_id, $coupon_code);
        if($balance_qty<=0)
        {

            /* allowed quantity */
            $item_qty=$this->get_quantity_for_non_individual_quantity_bogo($coupon_id);

            self::set_add_to_cart_messages(
                "coupon_max_quantity_reached",
                array(
                    'customer_gets'=>$bogo_customer_gets,
                    'max_qty'=>$item_qty,
                    'coupon_id'=>$coupon_id,
                    'apply_frequency'=>$this->get_coupon_meta_value($coupon_id, '_wt_sc_bogo_apply_frequency'),
                ), 
                self::$bogo_coupon_type_name
            );
            wp_die();
        }

        /* This function will prepare product list based on product/category restriction */
        $coupon_products = $this->prepare_product_list_for_any_product_from_cart($coupon);
        
        $add_to_cart_qty=($quantity>0 ? min($balance_qty, $quantity) : $balance_qty);
        $add_to_cart_id=($variation_id>0 ? $variation_id : $product_id);
        
        if(!$this->is_product_category_restriction_enabled($coupon_id) || empty($coupon_products)) /* No product/category restriction */ 
        {           
            $this->add_item_to_cart($add_to_cart_id, $add_to_cart_qty, $coupon_code);
        }else
        {                                                
            if(isset($coupon_products[$product_id]) || isset($coupon_products[$variation_id])) /* current product is under coupon product list */
            {
                $this->add_item_to_cart($add_to_cart_id, $add_to_cart_qty, $coupon_code);
            }
        }
    }

    /**
     *  Ajax sub function
     *  Add to cart product on `specific_product` as coupon option
     *  @since 2.0.4
     */
    private function specific_product_add_to_cart($coupon, $coupon_id, $coupon_code, $bogo_customer_gets, $product_id, $variation_id)
    {
        $coupon_code=wc_format_coupon_code($coupon_code);
        $bogo_product_condition = $this->get_coupon_meta_value($coupon_id, '_wt_sc_bogo_product_condition');
        $bogo_products=$this->get_all_bogo_giveaway_products($coupon_id);
        
        $frequency=$this->get_coupon_applicable_count($coupon_id, $coupon_code);


        if('and'==$bogo_product_condition) //Add all to cart
        {
            /**
             *  Giveaway max quantity checking
             *  Note: `$bogo_products` is a reference argument for the below function 
             */
            $is_giveaway_fully_added = $this->check_giveaway_max_quantity($coupon_code, $coupon_id, $bogo_customer_gets, $bogo_product_condition, $bogo_products, $frequency, array('update_quantity' => false));

            if(!empty($bogo_products)) /* after checking the existing items, any remaining items to be added */
            {
                $is_giveaway_fully_added=false;
                $product_id_arr=Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST['product_id_arr'], 'absint_arr');
                $variation_id_arr=Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST['variation_id_arr'], 'absint_arr');
                $attributes_arr=Wt_Smart_Coupon_Security_Helper::sanitize_item(wc_clean( wp_unslash( $_POST['attributes'] ) ), 'text_arr');
                foreach($product_id_arr as $key=>$product_id)
                {
                    $variation_id=(isset($variation_id_arr[$key]) ? $variation_id_arr[$key] : 0);
                    if($variation_id>0)
                    {
                        if(isset($bogo_products[$variation_id]))
                        {
                            $giveaway_qty=$bogo_products[$variation_id]['qty'];

                        }elseif(isset($bogo_products[$product_id]))
                        {
                            $giveaway_qty=$bogo_products[$product_id]['qty'];
                        }
                        $args['variation_attributes'] = isset($attributes_arr[$key]) ? $attributes_arr[$key] : array();
                        $giveaway_qty=$this->prepare_quantity_based_on_apply_frequency($coupon_id, $giveaway_qty);
                        $this->add_item_to_cart( $variation_id, $giveaway_qty, $coupon_code, '', $args );

                    }else
                    {
                        if(isset($bogo_products[$product_id]))
                        {
                            $giveaway_qty=$this->prepare_quantity_based_on_apply_frequency($coupon_id, $bogo_products[$product_id]['qty']);
                            $this->add_item_to_cart($product_id, $giveaway_qty, $coupon_code);
                        }
                    }
                }
            }

            if($is_giveaway_fully_added)
            {
                self::set_add_to_cart_messages(
                    "already_availed_bogo", 
                    array(
                        'coupon_id'=>$coupon_id, 
                        'customer_gets'=>$bogo_customer_gets,
                        'apply_frequency'=>$this->get_coupon_meta_value($coupon_id, '_wt_sc_bogo_apply_frequency'),
                    ), 
                    self::$bogo_coupon_type_name);
                wp_die();
            }

        }else
        {

            /**
             *  Giveaway max quantity checking
             *  Note: `$bogo_products` is a reference argument for the below function 
             */
            $this->check_giveaway_max_quantity($coupon_code, $coupon_id, $bogo_customer_gets, $bogo_product_condition, $bogo_products, $frequency, array('throw_error'=>true));

            $item_data=array();
            $item_id=0;
            if($variation_id>0 && isset($bogo_products[$variation_id]))
            {
                $item_data=$bogo_products[$variation_id];
                $item_id=$variation_id;

            }elseif(isset($bogo_products[$product_id]))
            {
                $item_data=$bogo_products[$product_id];
                $item_id = ($variation_id > 0 ? $variation_id : $product_id);

            }else
            {
                self::set_add_to_cart_messages("product_is_not_a_bogo_product", array('coupon_id'=>$coupon_id, 'customer_gets'=>$bogo_customer_gets), self::$bogo_coupon_type_name);
                wp_die();
            }

            /* allowed quantity */
            $item_data['qty']=(absint($item_data['qty'])===0 ? 1 : $item_data['qty']);

            /* prepare item max quantity based on applicable frequency. If apply repeatedly enabled */
            $giveaway_qty=$this->prepare_quantity_based_on_apply_frequency($coupon_id, $item_data['qty']);

            $product_cart_item_qty=self::get_product_cart_item_qty($item_id, $coupon_code); /* get cart item data. Coupon code is given so return single item array. Here the quantity will be total if multiple records exists */
            if(empty($product_cart_item_qty)) /* product not already added so add it. */
            {
                //add to cart with the $giveaway_qty quantity
                $this->add_item_to_cart(($variation_id>0 ? $variation_id : $product_id), $giveaway_qty, $coupon_code);

            }else /* product already in cart. So check its a free item of current coupon */
            {
                $total_qty=array_sum($product_cart_item_qty); //total cart quantity              

                if($giveaway_qty!=$total_qty) /* quantity mismatch so update */
                {
                    //update quantity. And show a quantity updated message
                    $qty_increment= $giveaway_qty-$total_qty;
                    $this->update_existing_free_item_qty($product_cart_item_qty, $qty_increment);
                }else
                {
                    self::set_add_to_cart_messages(
                        "product_max_quantity_reached", 
                        array(
                            'customer_gets'=>$bogo_customer_gets,
                            'max_qty'=>$giveaway_qty,
                            'coupon_id'=>$coupon_id,
                            'product_id'=>$product_id,
                            'variation_id'=>$variation_id,
                        ), 
                        self::$bogo_coupon_type_name
                    );
                    wp_die();
                }
            }

        }
    }

    /**
     *  Update giveaway item quantity
     *  @since 2.0.4
     */
    private function update_existing_free_item_qty($product_cart_item_qty, $qty_increment)
    {   
        $cart_item_key_arr=array_keys($product_cart_item_qty);                    
        $cart_item_key=$cart_item_key_arr[0]; /* update quantity of the first item */
        $new_quantity=$product_cart_item_qty[$cart_item_key]+$qty_increment;

        $this->update_cart_qty($cart_item_key, $new_quantity);
    }

    /**
     *  Update cart item quantity
     *  @since 2.0.4
     */
    private function update_cart_qty($cart_item_key, $quantity)
    {
        $cart = WC()->cart; 
        $cart->set_quantity($cart_item_key, $quantity);
    }
    
    
    /**
     *  Giveaway add to cart function
     *  
     *  @since 2.0.4
     *  @since 2.2.0        Extra args added as optional argument.
     *  @param int          $item_id        Product/variation id
     *  @param int          $quantity       Quantity
     *  @param string       $coupon_code    Coupon code
     *  @param int|string   $category       Category ID/Category slug(Category ids separated by comma), On category wise giveaway [Optional]
     *  @param array        $args           Extra args [Optional]
     */
    private function add_item_to_cart( $item_id, $quantity, $coupon_code, $category = '', $args = array() )
    {
        $product = wc_get_product($item_id);
        if($product)
        {
            if(!$this->is_purchasable($product))
            {
                return false;
            }
            if('variable'===$product->get_type())
            {
                return false; /* not possible to add variable parent  */  
            }

            if(!$product->has_enough_stock($quantity))
            {
                $quantity = $product->get_stock_quantity();
                if($quantity===0)
                {
                    return false;
                }
            }
            
            $variation_id   = 0;
            $product_id     = $item_id;
            $variation = isset( $args['variation_attributes'] ) ? $args['variation_attributes'] : array();

            if($product && 'variation'===$product->get_type())
            {
                $variation_id = $product_id;
                $product_id   = $product->get_parent_id();
                
                if(empty($variation)){
                    $variation =  isset( $_POST['attributes'] ) ? wc_clean( wp_unslash( $_POST['attributes'] ) ) : array(); 
                    $variation = empty($variation) ? array() : $variation ;
                }
                

                if( empty( $variation ) ){
                    $variation_attributes = $product->get_variation_attributes();
                    
                    foreach( $variation_attributes as $key => $value ){
                        if( empty($value) ){
                            $variation[$key] = isset( $_POST[$key] ) ? sanitize_text_field( wp_unslash( $_POST[$key] ) ) : '';
                        }
                    }
                }

                foreach( $variation as $attribute_name => $options ){
                    if( '' === $options ){
                        return;
                    }
                }
            }

            $cart_item_data = array(
                'free_product'          => 'wt_give_away_product',
                'free_gift_coupon'      => $coupon_code,
                'free_category'         => $category
            );

            //Extra cart item data
            if ( isset( $args['cart_item_data'] ) && is_array( $args['cart_item_data'] ) ) { 
                $cart_item_data = array_merge( $cart_item_data, $args['cart_item_data'] );
            }

            $cart_item_data = apply_filters('wt_sc_alter_giveaway_cart_item_data_before_add_to_cart', $cart_item_data, $product_id, $variation_id, $quantity);
            
            return WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation, $cart_item_data);
        }

        return false;
    }

    /**
     *  Checks the product purchasable or not.
     *  If varaible product, checks any of the variation is purchasable, and returns the variation id if successfull, otherwise false will return
     *  
     *  @since 2.0.4
     *  @param  Wc_Product
     *  @param  variation attributes    optional    only applicable for variable products, If any of the variation was purchasable, the attributes of first purchasable variation will assigned to this variable.
     *  @return boolean/integer
     */
    public function is_purchasable($_product, &$variation_attributes=array())
    {
        if(is_int($_product))
        {
            $_product=wc_get_product($_product);
        }

        if(!$_product)
        {
            return false;
        }

        if($_product->is_type('variable')) /* variation choosing option */
        {
            $variations=$_product->get_available_variations();
            if(empty($variations) && false!==$variations)
            {
                return false;
            }else
            {
                $is_purchasable=false;
                foreach($variations as $variation)
                { 
                    $variation_id=$variation['variation_id'];
                    $variation_product=wc_get_product($variation_id);
                    if($this->is_purchasable($variation_product)) /* any of the product is purchasable */
                    {
                        $variation_attributes=$variation['attributes'];
                        $is_purchasable=true;
                        break;
                    }
                }

                if(!$is_purchasable) /* all variations are not purchasable */
                {
                    return false;
                }else
                {
                    return $variation_id; // ID of first purchasable variation
                }
            }
        }else
        {
            if(!$_product->has_enough_stock(1))
            {
                $quantity = $_product->get_stock_quantity();
                if($quantity===0)
                {
                    return false;
                }
            }
        }

        return $_product->is_purchasable();
    }

    /**
     *  Register for applicable for giveaway checking. This function will trigger after cart item quantity update
     *  @since 2.0.4 
     */
    public function reg_applicable_for_giveaway($cart_item_key, $quantity, $old_quantity, $cart)
    {
        if($old_quantity<$quantity) //quantity increased
        {
            $cart_item_data=$cart->cart_contents[$cart_item_key];
            $product_id=$cart_item_data['product_id'];
            $variation_id=$cart_item_data['variation_id'];
            $variation=$cart_item_data['variation'];
            $increased_quantity=$quantity-$old_quantity;

            /** 
             *  avoid calling this function on add_to_cart
             *  prevent looping 
             */
            if(isset($_REQUEST['update_cart']) && is_null(WC()->session->get(self::$break_add_to_cart_loop_session_id)))
            {
                $this->applicable_for_giveaway($cart_item_key, $product_id, $increased_quantity, $variation_id, $variation, $cart_item_data);
            }
        }
    }

    
    /**
     *  Checks the newly added item is eligible as giveaway product. If yes then convert the item as giveaway
     *  
     *  @since 2.0.4 
     *  @since 2.0.7    Added compatibility for `Any product from category in the cart`
     */
    public function applicable_for_giveaway($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data)
    {
        global $wt_sc_just_added_coupons; /* just added auto coupons */

        if(self::is_a_free_item($cart_item_data))
        {
            return; /* already a free item so no need to check */
        }

        if( !is_null( WC()->session ) && WC()->session->get( self::$break_add_to_cart_loop_session_id ) === 1 )
        {
            WC()->session->set(self::$break_add_to_cart_loop_session_id, null);
            return; /* prevent indefinite loop */
        }
       
        
        $bogo_eligible=self::get_bogo_eligible_session();

        if(!empty($bogo_eligible))
        {
            $cart = WC()->cart;

            if($cart->get_cart_contents_count() === $quantity) /* cart is empty before adding this item */
            {
                return;
            }

            $bogo_eligible=array_keys($bogo_eligible); //not needed the eligible message 
            $coupons=$cart->get_applied_coupons();
            $applied_eligible=array_intersect($bogo_eligible, $coupons);
            
            if(!empty($applied_eligible))
            {
                $cart_items=WC()->cart->get_cart();
                
                $current_cart_item = $cart_items[$cart_item_key];
                $current_cart_item_qty = $current_cart_item['quantity'];
                $current_cart_item_price = $current_cart_item['line_subtotal']/$current_cart_item_qty;
                $newly_added_qty = $quantity; //this is for a backup, because value of $quantity may change. This is using in eligibile quantity calculation section
                $args['variation_attributes'] = isset( $current_cart_item['variation'] ) ? $current_cart_item['variation'] : array() ;

                /* Remove the newly added quantity. This is to avoid the apply frequency calculation issue */
                $new_cart_item_qty = $current_cart_item_qty - $quantity;
                $cart->set_quantity($cart_item_key, $new_cart_item_qty);

                $wt_sc_just_added_coupons=!is_array($wt_sc_just_added_coupons) ? array() : array_unique($wt_sc_just_added_coupons);
                $existing_coupons=array_diff($applied_eligible, $wt_sc_just_added_coupons);
                
                if(!empty($existing_coupons))/* already added coupons are there. So give priority */
                {
                    /* $quantity is a reference argument */
                    $this->set_as_giveaway($existing_coupons, $variation_id, $product_id, $quantity);
                }

                if($quantity>0) /* balance quantity exists */
                {
                    $new_coupon_eligibility_qty = 0; //required minimum eligibility qty for newly added coupons(If exists)

                    if(!empty($wt_sc_just_added_coupons)) /* newly added coupons are there */
                    {
                        $do_qty_chk = false;
                        
                        foreach($wt_sc_just_added_coupons as $i => $coupon_code)
                        {
                            if($quantity<=0)
                            {
                                break;
                            }

                            $coupon_code = wc_format_coupon_code($coupon_code);

                            if(0 === wc_get_coupon_id_by_code($coupon_code))
                            {
                                unset($wt_sc_just_added_coupons[$i]); //this will be usefull for next `foreach` for adding giveaway  
                                continue;
                            }

                            $coupon     = new WC_Coupon($coupon_code);
                            $coupon_id  = $coupon->get_id();
                            $bogo_customer_gets = $this->get_coupon_meta_value($coupon_id, '_wt_sc_bogo_customer_gets');
                            
                            if('any_product_from_category' === $bogo_customer_gets)
                            {
                                $bogo_free_categories = $this->get_coupon_meta_value($coupon_id, '_wt_sc_bogo_free_categories');
                                
                                if(!empty($bogo_free_categories))
                                {
                                    $bogo_free_category_ids = array_keys($bogo_free_categories);
                                    $product_cats   = Wt_Smart_Coupon_Common::get_product_cat_ids($product_id);
                                    $matching_cats  = array_intersect($product_cats, $bogo_free_category_ids);
                                    
                                    if(!empty($matching_cats))
                                    {
                                        //check contribution for eligibility by the current product
                                        $do_qty_chk = true;

                                    }else
                                    {
                                        unset($wt_sc_just_added_coupons[$i]);
                                        continue;
                                    }

                                }else
                                {
                                    unset($wt_sc_just_added_coupons[$i]);
                                    continue;
                                }

                            }elseif('any_product_from_store' === $bogo_customer_gets || 'any_product_from_category_in_the_cart' === $bogo_customer_gets)
                            {
                                //check contribution for eligibility by the current product
                                $do_qty_chk = true;

                            }else
                            {
                                //no chance but ..., $bogo_eligible list only contains the eligible coupons
                                continue;
                            }

                            if($do_qty_chk)
                            {
                                $eligibility_qty = $this->get_eligibility_contribution($coupon_code, $newly_added_qty, $current_cart_item_price, $product_id, $variation_id);

                                if($eligibility_qty > ($quantity + $new_coupon_eligibility_qty)) /* the remaining (quantity + the quantity already considered for eligibility) is lesser than the current required eligible quantity */
                                {
                                    unset($wt_sc_just_added_coupons[$i]);
                                    continue; //the coupon will be removed.
                                }else
                                {
                                    /**
                                     *  adjust the quantity.
                                     *  sometimes same product may give eligibility for multiple coupons 
                                     */
                                    if(0 === $new_coupon_eligibility_qty)
                                    {
                                        $quantity -= $eligibility_qty;
                                        $new_coupon_eligibility_qty = $eligibility_qty;

                                    }else
                                    {
                                        if($new_coupon_eligibility_qty < $eligibility_qty) /* product gives eligibility for multiple coupons, current coupon needs more quantity than previous coupon */
                                        {
                                            $quantity -= ($eligibility_qty - $new_coupon_eligibility_qty);

                                            $new_coupon_eligibility_qty = $eligibility_qty; //reset the value with new higher value
                                        }
                                    }                                    
                                }
                            }
                        }

                        if($new_coupon_eligibility_qty > 0)
                        {
                            $this->set_as_normal_cartitem( $product_id, $variation_id, $new_coupon_eligibility_qty, $args );
                        }
                        
                        if($quantity > 0 && !empty($wt_sc_just_added_coupons)) //balance quantity exists
                        {
                            /* $quantity is a reference argument */
                            $this->set_as_giveaway($wt_sc_just_added_coupons, $variation_id, $product_id, $quantity); 
                        }
                    }

                    if($quantity > 0) /* balance quantity exists. Add it as normal cart item */
                    {
                        $this->set_as_normal_cartitem( $product_id, $variation_id, $quantity, $args );
                    }
                }
            }
        }
    }

    private function get_eligibility_contribution($coupon_code, $quantity, $cart_item_price, $product_id, $variation_id)
    {
        $eligibility_qty=1;
        
        if(Wt_Smart_Coupon_Common::module_exists('coupon_restriction'))
        {
            $eligibility_qty=Wt_Smart_Coupon_Restriction_Public::get_eligibility_contribution($coupon_code, $quantity, $cart_item_price, $product_id, $variation_id);
        }

        return $eligibility_qty;
    }

    private function set_as_normal_cartitem( $product_id, $variation_id, $quantity, $args = array() )
    {
        $product = wc_get_product(($variation_id>0 ? $variation_id : $product_id));
        $variation = array();

        if('variation'===$product->get_type())
        {
            $variation = $product->get_variation_attributes();
            $variation_attributes = isset( $args['variation_attributes'] ) ? $args['variation_attributes'] : array();
            foreach ($variation_attributes as $key => $value) {
                $variation[$key] = $variation_attributes[$key];
            }
        }
        
        WC()->session->set(self::$break_add_to_cart_loop_session_id, 1); /* to inform not check again for giveaway */
        
        WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation, array());
    }

    private function set_as_giveaway($coupons, $variation_id, $product_id, &$quantity)
    {
        $item_id=($variation_id>0 ? $variation_id : $product_id);
        
        foreach($coupons as $coupon_code)
        {
            $coupon_code=wc_format_coupon_code($coupon_code);
            $coupon=new WC_Coupon($coupon_code);
            
            if($coupon)
            {
                //recalculate the eligibility count for the current coupon. Because we removed the newly added quantity
                $this->recalculate_apply_frequency_count($coupon);                 

                $coupon_id=$coupon->get_id();
                $bogo_customer_gets = $this->get_coupon_meta_value($coupon_id, '_wt_sc_bogo_customer_gets');                       
                $cart_items=WC()->cart->get_cart();
                
                if('any_product_from_category' === $bogo_customer_gets)
                {
                    $bogo_free_categories = $this->get_coupon_meta_value($coupon_id, '_wt_sc_bogo_free_categories');

                    if ( empty( $bogo_free_categories ) ) { // No categories added by admin, so skip
                        continue;
                    }

                    $bogo_free_category_ids = array_keys( $bogo_free_categories );
                    $product_cats = Wt_Smart_Coupon_Common::get_product_cat_ids( $product_id );
                    $matching_cats = array_values( array_intersect( $product_cats, $bogo_free_category_ids ) );

                    if ( empty( $matching_cats ) ) { // Current product is not belongs to the coupon categories                   
                        continue;
                    }

                    $category_condition = $this->get_any_product_from_category_condition( $coupon_code );

                    if ( 'any' === $category_condition ) {

                        $total_frequency_in_cart    = 0; // Reference argument to the below function
                        $total_remain_qty           = 0; // Reference argument to the below function
                        $cat_data_arr               = $this->get_category_data_for_coupon( $coupon_code, $bogo_free_categories, $total_frequency_in_cart, $total_remain_qty );
                        $frequency                  = $this->get_coupon_applicable_count( $coupon_id, $coupon_code ); // Total eligible frequency

                        $total_allowed_qty  = $this->get_quantity_for_non_individual_quantity_bogo( $coupon_id, $frequency ); // Total giveaway quantity allowed for this coupon
                        $total_qty_in_cart  = array_sum( array_column( $cat_data_arr, 'cart_qty' ) );
                        $balance_qty        = $total_allowed_qty - $total_qty_in_cart;
                        $to_add_qty         = max( 0, min( $balance_qty, $quantity ) );

                        if ( 0 < $to_add_qty ) {
                            
                            // Add the giveaway to cart
                            $this->add_item_to_cart( $item_id, $to_add_qty, $coupon_code, $matching_cats[0] );
                            
                            $total_remain_qty = $total_allowed_qty - ( $total_qty_in_cart + $to_add_qty );
                            $quantity -= $to_add_qty;

                            $this->trigger_dummy_bogo_fully_availed( $coupon_id, $coupon_code, ( 0 === (int) $total_remain_qty ) );
                        }
                    
                    } else {
                        
                        // Legacy code. Because we have stopped support for `all` condition for this type of BOGO.
                        include_once __DIR__ . "/classes/giveaway_product_legacy.php";
                        Wt_Smart_Coupon_Giveaway_Product_Public_Legacy::get_instance()->set_as_giveaway__any_product_from_category($coupon_code, $coupon_id, $item_id, $cart_items, $matching_cats, $bogo_free_categories, $quantity);
                        
                        // $quantity is a reference argument for the above function. 
                        // No more quantity in total added quantity so break the main loop, otherwise continue the loop for other coupon(if exists)
                        if ( 0 === (int) $quantity ) {                       
                            break;
                        }
                    }

                }elseif('any_product_from_store' === $bogo_customer_gets)
                {
                    $total_qty_used=0;
                    $current_product_free_qty=0;
                    foreach($cart_items as $item_key=>$cart_item) 
                    {
                        if(self::is_a_free_item($cart_item, $coupon_code)) /* a free item under the given coupon */
                        {
                            $total_qty_used+=$cart_item['quantity'];
                            if(self::is_same_prodct($cart_item, $product_id, $variation_id))
                            {
                                $current_product_free_qty+=$cart_item['quantity'];
                                WC()->cart->remove_cart_item($item_key);  
                            }
                        }
                    }

                    /* allowed quantity */
                    $max_item_qty=$this->get_quantity_for_non_individual_quantity_bogo($coupon_id);

                    $qty_for_free=0;
                    if($total_qty_used<$max_item_qty) /* remaining quantity exists */
                    {
                        $balance_discount_qty=$max_item_qty-$total_qty_used;
                        $qty_for_free=min($quantity, $balance_discount_qty);
                        $quantity-=$qty_for_free; //any balance will added to next coupon, in the next iteration
                    }

                    $total_qty_for_free=$current_product_free_qty+$qty_for_free;

                    $this->add_item_to_cart($item_id, $total_qty_for_free, $coupon_code);
                                            
                    /* check and set, is bogo fully availed or not */
                    self::set_bogo_fully_availed($coupon_id, $coupon_code, $max_item_qty, ($total_qty_used+$qty_for_free));

                    if($quantity==0) //no more quantity in total added quantity so break the main loop, otherwise continue the loop for other coupon(if exists)
                    {
                        break;
                    }
                }elseif('any_product_from_category_in_the_cart' === $bogo_customer_gets)
                {

                    $coupon_categories = (array) $coupon->get_product_categories();
                    sort($coupon_categories, SORT_NUMERIC); //sort ascending. This is for, easy `prod_cat_slug` comparison

                    /**
                     *  Is separate pool required for categories in combination with outside categories
                     * 
                     *  @see  is_separate_pool_for_outside_cat() Documentation
                     */       
                    $separate_pool_for_outside_cat = $this->is_separate_pool_for_outside_cat();

                    //prepare category slug for giveaway
                    $prod_cat_slug = $this->prepare_product_cat_slug($coupon_categories, !empty($coupon_categories), $separate_pool_for_outside_cat, $product_id);
                
                    $bogo_free_categories = $this->get_cart_item_categories_for_coupon($coupon_code, $coupon_id, false);

                    if(!isset($bogo_free_categories[$prod_cat_slug]))
                    {
                        continue; //the newly added product is not a giveaway for the current coupon
                    }

                    $cat_data = $bogo_free_categories[$prod_cat_slug];
                    $giveaway_qty = $cat_data['giveaway_qty'];
                    $cart_giveaway_qty = $cat_data['cart_giveaway_qty'];


                    $convert_as_giveaway = 0; //the available quantity to be converted as giveaway

                    if($giveaway_qty > $cart_giveaway_qty)
                    {
                        $balance_discount_qty = $giveaway_qty - $cart_giveaway_qty;
                        $convert_as_giveaway = min($quantity, $balance_discount_qty);
                        $quantity -= $convert_as_giveaway; //any balance will added to next coupon, in the next iteration
                    
                        $this->add_item_to_cart($item_id, $convert_as_giveaway, $coupon_code, $prod_cat_slug);
                    }
                    
                    
                    /**
                     *  Check and set is bogo fully availed or not. This will also hide the message if fully availed.
                     */
                    $total_giveaway_qty = 0; //total giveaway for the coupon. Not for a single category pool
                    $total_cart_giveaway_qty = 0; //total giveaway for the coupon already in the cart

                    foreach($bogo_free_categories as $cat_data) //using `foreach` instead of in-built functions (array_column, array_sum) to optimize the code
                    {
                        $total_giveaway_qty += $cat_data['giveaway_qty'];
                        $total_cart_giveaway_qty += $cat_data['cart_giveaway_qty'];
                    }

                    $total_cart_giveaway_qty += $convert_as_giveaway; //add the newly added quantity too
                    self::set_bogo_fully_availed($coupon_id, $coupon_code, $total_giveaway_qty, $total_cart_giveaway_qty);
                    
                                      
                    /** 
                     *  No more quantity in `total added` quantity so break the main loop, otherwise continue the loop for other coupons(if exists)
                     */
                    if(0 === $quantity) 
                    {
                        break;
                    }
                }
            }
        }
    }

    /**
     *  Sort the coupon categories based on the discount amount. Category with least discount amount will be the first one.
     *  Update the quantity based on `Apply repeatedly` option
     *  @since 2.0.4
     */
    public function sort_category_by_profit($matching_cats, $bogo_free_categories, $product, $coupon_id)
    {
        $out = array();
        
        if ( 1 === count( $matching_cats ) ) {
            $cat_id = reset( $matching_cats );
            $cat_data = $bogo_free_categories[ $cat_id ];
            
            /* prepare quantity for apply repeatedly */
            $cat_data['qty'] = $this->prepare_quantity_based_on_apply_frequency( $coupon_id, $cat_data['qty'] );

            $out[ $cat_id ] = $cat_data;
            return $out;
        }

        $product_price = self::get_product_price( $product );
        
        foreach($matching_cats as $cat_id)
        {
            $cat_data=$bogo_free_categories[$cat_id];

            /* prepare quantity for apply repeatedly */
            $cat_data['qty']=$this->prepare_quantity_based_on_apply_frequency($coupon_id, $cat_data['qty']);

            if('percent'==$cat_data['price_type'])
            {
                $discount_amount=((($cat_data['price']*$product_price)/100)*$cat_data['qty']);
            }else
            {
                $discount_amount=$cat_data['price']*$cat_data['qty'];
            }
            $cat_data['discount_amount']=$discount_amount;
            $out[$cat_id]=$cat_data;
        }
        uasort($out, function($a, $b){ return $a['discount_amount'] - $b['discount_amount']; });                     
        return $out;
    }

    /**
     * Action function for displaying description for Giveaway product on cart page
     * 
     *  @since  1.0.0
     *  @since  2.0.4       Added compatibility for BOGO type coupons
     *  @since  2.0.8       Implemented customized messages option
     *  @since  2.3.0       Checking and preparing codes moved to separate function
     */
    public function display_giveaway_product_description( $cart_item )
    {
        $info_text = $this->get_cart_lineitem_giveaway_text( $cart_item );
        
        if ( $info_text ) { // This is a free item
            echo wp_kses_post( $info_text );
        }
    }

    /**
     * Update Cart item values
     * @since 1.0.0
     * @since 2.0.4 Added compatibility for BOGO type coupons
     */
    public function update_cart_item_values($cart_item, $product_id = 0, $variation_id = 0, $qty = 1)
    {
        if(self::is_a_free_item($cart_item)) 
        {
            $coupon_code = wc_format_coupon_code($cart_item['free_gift_coupon']);
            $coupon=new WC_Coupon($coupon_code);
            if($coupon)
            {
                $coupon_id=$coupon->get_id();
                if(false === $this->is_apply_discount_before_tax_enabled($coupon_id))
                {
                    return $cart_item;
                } 

                $item_id = ($cart_item['variation_id']>0 ? $cart_item['variation_id'] : $cart_item['product_id']);
                $product = wc_get_product($item_id);
                $giveaway_data=$this->get_product_giveaway_data($item_id, $coupon_code, $cart_item);

                $discount=self::get_available_discount_for_giveaway_product($product, $giveaway_data); 

                $product_price=self::get_product_price($product);

                $discounted_price = ($product_price - $discount);
                $cart_item['data']->set_price($discounted_price);
                $cart_item['data']->set_regular_price($product_price);
            }
        }

        return $cart_item;
    }

    /**
     *  Update cart item value for applying price before tax calculation.
     *  @since 1.0.0
     */
    public function update_cart_item_in_session( $session_data = array(), $values = array(), $key = '' )
    {
        if(self::is_a_free_item($session_data))
        {
            $coupon_code = wc_format_coupon_code($session_data['free_gift_coupon']);
            $coupon_id =  wc_get_coupon_id_by_code($coupon_code);

            if(false === $this->is_apply_discount_before_tax_enabled($coupon_id))
            {
                return $session_data;
            }
            
            $qty =(isset($session_data['quantity']) ?  $session_data['quantity'] :  1);
            
            $session_data = $this->update_cart_item_values($session_data, $session_data['product_id'], $session_data['variation_id'], $qty);
        }
        return $session_data;
    }

    /**
     *  Function for updating cart item price display ( used when apply giveaway discount before tax calculation).
     *  @since 1.2.4
     */
    public function update_cart_item_price($price, $cart_item)
    {
        return $this->alter_cart_item_price($price, $cart_item, false);
    }

    /**
     * Update Cart item Quantity field non editable
     * @since 1.0.0
     */
    public function update_cart_item_quantity_field($product_quantity = '', $cart_item_key = '', $cart_item = array() )
    {
        if(self::is_a_free_item($cart_item))
        {
            $product_quantity = sprintf( '%s <input type="hidden" name="cart[%s][qty]" value="%s" />', $cart_item['quantity'], $cart_item_key, $cart_item['quantity']);
        }
        return $product_quantity;
    }

    /**
     *  Add Free gift item price details into cart and checkout.
     *  @since 1.0.0
     *  @since 2.0.4 Added compatibility for BOGO type coupons
     *  @since 2.0.8 Implemented customized messages option
    */
    public function add_give_away_product_discount()
    {
        $cart_object=WC()->cart;
        if($this->is_cart_contains_free_products('', $cart_object))
        {   
            $cart_items=$cart_object->get_cart();
            foreach($cart_items as $cart_item_key=>$cart_item)
            {
                if(self::is_a_free_item($cart_item))
                {
                    $coupon_code=(isset($cart_item['free_gift_coupon']) ? $cart_item['free_gift_coupon'] : '');
                    if(!empty($coupon_code))
                    {
                        $coupon_code=wc_format_coupon_code($coupon_code);
                        $coupon=new WC_Coupon($coupon_code);
                        if($coupon && !self::is_bogo($coupon))
                        {
                            $coupon_id=$coupon->get_id();

                            if($this->is_apply_discount_before_tax_enabled($coupon_id)) /* currently only applicable for non BOGO */
                            {
                                continue;
                            }

                            $item_id = ($cart_item['variation_id']>0 ? $cart_item['variation_id'] : $cart_item['product_id']);
                            $product = wc_get_product($item_id);
                            $giveaway_data=$this->get_product_giveaway_data($item_id, $coupon_code, $cart_item);

                            $discount=(float) self::get_available_discount_for_giveaway_product($product, $giveaway_data)*$cart_item['quantity'];

                            $label_text = $this->get_customized_text('nonbogo_giveaway_cart_summary_label', array('coupon_code' => $coupon_code));
                            $label_text = apply_filters('wt_sc_alter_giveaway_cart_summary_label', $label_text, $cart_item);                          
                            
                            $discount_price=Wt_Smart_Coupon_Admin::get_formatted_price((number_format((float) $discount, 2, '.', '')));
                            $discount_price=apply_filters('wt_sc_alter_giveaway_cart_summary_value', '-'.$discount_price, $discount, $cart_item); 
                            ?>
                            <tr class="woocommerce-give_away_product wt_give_away_product">
                                <th><?php echo wp_kses_post($label_text); ?></th>
                                <td><?php echo wp_kses_post($discount_price); ?></td>                      
                            </tr>
                            <?php
                        }
                    }
                } 
            }
        }
    }

    /**
     *  
     *  Exclude the free giveaway products from applying other coupons.
     *  This is applicable when product is 'free giveaway`.
     *  @param bool     $valid   is valid or not
     *  @param WC_Product $product   Product instance
     *  @param WC_Product     $coupon   Coupon data
     *  @param array  $values  Cart item values.
     *  @return bool
     *  @since    2.0.6
     *  @since    2.4.3 Only exclude if full giveaway.
     */
    public function exclude_giveaway_from_other_discounts($valid, $product, $coupon, $values)
    {
        if( self::is_a_free_item( $values ) && 0 >= $values['data']->get_price() )
        {
            $valid = false;
        }

        return $valid;
    }

    
    /**
     * Filter function for updating cart item price ( Displaying cart item price in cart and checkout page )
     * 
     * @since 1.0.0
     * @param $price Price html.
     * @param $cart_item Cart item object 
     */
    public function add_custom_cart_item_total( $price, $cart_item ) {
        return $this->alter_cart_item_price( $price, $cart_item );
    }

    
    /**
     *  Show altered cart item price for giveaway item.
     * 
     *  @since 2.3.0    The discount calculation moved to separate function.
     */
    private function alter_cart_item_price( $price, $cart_item, $is_total = true )
    {
        $out = $price;
        if ( self::is_a_free_item( $cart_item ) ) {

            $discount_data = $this->calculate_bogo_discount( $cart_item, $is_total );

            if ( $this->is_apply_discount_before_tax_enabled( $discount_data['coupon_id'] ) ) { 
                $out = '<del><span>' . wp_kses_post( wc_price( $discount_data['product_price'] ) ) . '</span></del> <br /><span>' . wp_kses_post( wc_price( $discount_data['sale_price_after_discount'] ) ) . '</span>';
            } else {
                $out = '<span>' . wp_kses_post( wc_price( $discount_data['product_price'] ) ) . '</span> <br /> <span class="wt_sc_bogo_cart_item_discount">' . esc_html__( 'Discount: ', 'wt-smart-coupons-for-woocommerce-pro' ) . wp_kses_post( wc_price( $discount_data['discount'] ) ) . '</span>'; 
            }           
        }

        return $out; 
    }

    /**
     *  Calculate the Cart Total after reducing the free product price.
     *  @since 1.0.0.
     *  @since 2.0.4 Added compatibility for BOGO type coupons
    */
    public function discounted_calculated_total($cart_object)
    {
        $new_total = $cart_object->get_total('edit');
        if($this->is_cart_contains_free_products('', $cart_object))
        {     
            $cart_items=$cart_object->get_cart();
            foreach($cart_items as $cart_item_key=>$cart_item)
            {
                if(self::is_a_free_item($cart_item))
                {
                    $coupon_code=$cart_item['free_gift_coupon'];
                    if(!empty($coupon_code))
                    {
                        $coupon_code=wc_format_coupon_code($coupon_code);
                        $coupon=new WC_Coupon($coupon_code);
                        if($coupon)
                        {
                            $coupon_id=$coupon->get_id();

                            if($this->is_apply_discount_before_tax_enabled($coupon_id))
                            {
                                continue;
                            } 
                            
                            $item_id = ($cart_item['variation_id']>0 ? $cart_item['variation_id'] : $cart_item['product_id']);
                            $product = wc_get_product($item_id);
                            $giveaway_data=$this->get_product_giveaway_data($item_id, $coupon_code, $cart_item);

                            $discount=self::get_available_discount_for_giveaway_product($product, $giveaway_data);
                            $new_total = $new_total-($discount*$cart_item['quantity']);
                        }
                    }
                } 
            }
            $new_total=round($new_total, $cart_object->dp);
            $cart_object->set_total($new_total);
        }
    }

    /**
     *  Removes any free products from the cart if their related coupon is not present in the cart
     *  @since 1.3.4
     */
    public function check_any_free_products_without_coupon()
    {
        if(is_null(($cart = self::get_cart_object())))
        {
            return;
        }

        if(is_object( $cart ) && is_callable(array($cart, 'is_empty')) && !$cart->is_empty()) 
        {
            $coupons=$cart->get_applied_coupons();           
            $cart_items = $cart->get_cart();
            $cart_items =((isset($cart_items) && is_array($cart_items)) ? $cart_items : array());            
            foreach($cart_items as $cart_item_key => $cart_item)
            {                  
                if(self::is_a_free_item($cart_item))
                {
                    if(!in_array($cart_item['free_gift_coupon'], $coupons)) /* coupon not found in the applied coupon list */
                    {
                        $cart->remove_cart_item($cart_item_key); /* remove the free item */
                    }
                }
            }
        }                
    }

    /**
     * Remove giveaway available session. If already added    
     * @since 2.0.2
     */
    public function remove_giveaway_available_session($coupon_code)
    {
        self::remove_bogo_eligible_session($coupon_code); 
    }

    /**
     * Remove Free Product from cart (Hook to When Coupon removed)
     * @since 1.0.0
     * @since 2.0.2     Code updated
     */
    public function remove_free_product_from_cart($coupon_code)
    {
        $cart=WC()->cart;
        $applied_coupons  = $cart->get_applied_coupons(); 
        if(isset($coupon_code) && !empty($coupon_code) && !in_array($coupon_code, $applied_coupons))
        {
            foreach($cart->get_cart() as $cart_item_key => $cart_item )
            {
                if(self::is_a_free_item($cart_item, $coupon_code))
                {
                    $cart->remove_cart_item($cart_item_key);
                }
            }         
        }
    }


    /**
     * Add Free Prodcut details on cart item list.
     * @since 1.0.0
     * @since 2.0.2 Code updated
    */
    public function add_free_product_details_into_order($item, $cart_item_key, $values, $order)
    {
        if(!self::is_a_free_item($values))
        {
            return;
        }        
        $item->add_meta_data('free_product' , $values['free_product']);
        $item->add_meta_data('free_gift_coupon' , $values['free_gift_coupon']);
    }


    /**
     * Display free product discount detail in order details.
     * @since 1.0.0
     */
    public function woocommerce_get_order_item_totals($total_rows, $order, $tax_display)
    {
        $out=array();
        $order_items = $order->get_items();
        foreach($order_items as $order_item_id=>$order_item)
        {
            $giveaway_info = $this->prepare_giveaway_info_for_order($order_item_id, $order_item, $order);
            
            if($giveaway_info)
            {
                $coupon_code    = wc_get_order_item_meta($order_item_id, 'free_gift_coupon', true);
                $label_text     = $this->get_customized_text('giveaway_order_summary_label', array('coupon_code' => $coupon_code));
                $label_text     = apply_filters('wt_sc_alter_order_detail_giveaway_info_label', $label_text, $order_item, $order_item_id, $order);
                
                $out['free_product_'.$order_item_id] = array(
                    'label'     => $label_text,
                    'value'     => $giveaway_info,
                );
            }
        }

        if(!empty($out))
        {
            $offset = array_search('shipping', array_keys($total_rows));
            $total_rows = array_merge(
                array_slice($total_rows, 0, $offset),
                $out,
                array_slice($total_rows, $offset, null)
            );
        }

        return $total_rows;
    }

    /**
     * Manage Item Meta on order page
     * @since 1.0.0
     */  
    public function unset_free_product_order_item_meta_data($formatted_meta, $item)
    {
        foreach($formatted_meta as $key => $meta)
        {
            if(in_array($meta->key, array('free_product', 'free_gift_coupon', 'free_category')))
            {
                unset($formatted_meta[$key]);
            }            
        }
        return $formatted_meta;
    }


    /**
     *  Get current product cart item quantity
     *  @since 2.0.4
     *  @return array
     */
    public static function get_product_cart_item_qty($item_id, $coupon_code)
    {
        $out=array();
        foreach(WC()->cart->get_cart() as $cart_item_key=>$cart_item)
        {
            if($cart_item['product_id']==$item_id || $cart_item['variation_id']==$item_id) //product found
            {
                if(self::is_a_free_item($cart_item, $coupon_code))
                {
                    $out[$cart_item_key]=$cart_item['quantity'];                    
                }
            }   
        }
        return $out;
    }

    /**
     *  Checks the current cart item is a free item. Or a free item under the given coupon code
     *  @since 2.0.4
     *  @return bool
     */
    public static function is_a_free_item($cart_item, $coupon_code = "")
    {
        $out = isset($cart_item['free_gift_coupon']) && isset($cart_item['free_product']) && 'wt_give_away_product' === $cart_item['free_product'];
        
        if("" !== $coupon_code && $out)
        {
            $out = (wc_format_coupon_code($cart_item['free_gift_coupon']) === wc_format_coupon_code($coupon_code));
        }

        $out = apply_filters('wt_sc_alter_is_free_cart_item', $out, $cart_item, $coupon_code); /* other plugins to confirm their giveaway item */
        return $out;
    }

    /**
     *  Checks the current cart item is the same product/variation
     *  @since 2.0.4
     *  @param  array   $cart_item          Cart item array
     *  @param  int     $product_id         Product ID
     *  @param  int     $variation_id       Variation ID
     *  @return bool
     */
    public static function is_same_prodct($cart_item, $product_id, $variation_id)
    {
        return ($cart_item['product_id']==$product_id && $cart_item['variation_id']==$variation_id);
    }

    /**
     *  Get total quantity of current coupon free products
     *  @return array 
     */
    public static function get_total_coupon_cart_item_qty($coupon_code)
    {
        $out=array();
        foreach(WC()->cart->get_cart() as $cart_item_key=>$cart_item)
        {
            if(self::is_a_free_item($cart_item, $coupon_code))
            {
                $out[$cart_item_key]=$cart_item['quantity'];                    
            }   
        }
        return $out;
    }

    /**
     * Check whether cart contains any Giveaway products from given coupon
     * 
     * @since 1.0.0
     * @since 2.0.2 Code updated, added cart object as second argument(optional)
     * @since 2.0.8 Code updated.
     * 
     * @param   string          $coupon_code    Optional. Coupon code
     * @param   WC_Cart|null    $cart           Optional. Cart object
     * @return  bool            True when free product exists otherwise false
     */
    public function is_cart_contains_free_products($coupon_code = '', $cart = null)
    {
        $cart = (is_null($cart) ? ((is_object(WC()) && isset(WC()->cart)) ? WC()->cart : null) : $cart);
        $out  = false;

        if(is_null($cart))
        {
            return $out;
        }


        $cart_items = $cart->get_cart();

        foreach($cart_items as $cart_item)
        {
            if(self::is_a_free_item($cart_item, $coupon_code))
            {
                $out = true;
                break;
            }
        }

        return $out;
    }


    /**
     *  Remove/Update quantity of giveaway items when eligibility count was changed. This will be called on `wp_loaded` hook
     *  
     *  @since  2.0.4
     *  @since  2.0.7       Added compatibility for WPML on `Specific product` giveaway.
     */
    public function adjust_giveaway_count_when_eligibility_changed()
    {
        if(true === self::$giveaway_count_adjust || is_null(($cart = self::get_cart_object())) || !Wt_Smart_Coupon_Public::module_exists('coupon_restriction') || is_null(WC()->session))
        {
            return;
        }

        self::$giveaway_count_adjust    = true;           
        $applied_coupons                = $cart->applied_coupons;
        $applied_coupons                = (!is_array($applied_coupons) ? array() : $applied_coupons);
        $cart_items                     = $cart->get_cart(); 
        
        foreach($applied_coupons as $coupon_code)
        {
            $coupon_code    = wc_format_coupon_code($coupon_code);
            $coupon         = new WC_Coupon($coupon_code);
            
            if(self::is_bogo($coupon))
            {
                $coupon_id = $coupon->get_id();
                
                $bogo_product_condition = $this->get_coupon_meta_value($coupon_id, '_wt_sc_bogo_product_condition');               
                $frequency = $this->get_coupon_applicable_count($coupon_id, $coupon_code);
                $bogo_customer_gets = $this->get_coupon_meta_value($coupon_id, '_wt_sc_bogo_customer_gets');

                if('specific_product' === $bogo_customer_gets)
                {
                    $bogo_products=$this->get_all_bogo_giveaway_products($coupon_id);
                    $cart_available_qty=array();
                    foreach($cart_items as $item_key=>$cart_item)
                    {
                        if(self::is_a_free_item($cart_item, $coupon_code)) /* a free item under the given coupon */
                        {
                            $item_id = $this->check_giveaway_id_match_on_multi_lang_site($cart_item, $coupon_id, $bogo_products);

                            if($item_id>0)
                            {
                                if(!isset($cart_available_qty[$item_id]))
                                {
                                    $cart_available_qty[$item_id]=array();
                                }
                                $cart_available_qty[$item_id][$item_key]=$cart_item['quantity'];

                            }else
                            {
                                //a non giveaway free product. Remove it
                                WC()->cart->remove_cart_item($item_key);
                            }
                        }
                    }

                    $total_eligibility=$frequency;
                    foreach($cart_available_qty as $item_id=>$available_qty_data)
                    {
                        if($total_eligibility<=0) //no eligibility remaining
                        {   
                            foreach($available_qty_data as $cart_item_key=>$quantity)
                            {
                                //eligibility reached. Remove it
                                WC()->cart->remove_cart_item($cart_item_key);
                            } 
                        }
                        $total_qty_in_cart=array_sum($available_qty_data);
                        
                        if('and' === $bogo_product_condition) /* product condition `and` */
                        {
                            $giveaway_qty=$this->prepare_quantity_based_on_apply_frequency($coupon_id, $bogo_products[$item_id]['qty']);
                            if($giveaway_qty<$total_qty_in_cart)
                            {
                                foreach($available_qty_data as $cart_item_key=>$quantity)
                                {
                                    if(0 >= $giveaway_qty) /* max giveaway quantity reached. So remove it */
                                    {
                                        WC()->cart->remove_cart_item($cart_item_key);
                                        continue;  
                                    }

                                    if($quantity >= $giveaway_qty)
                                    {
                                        $this->update_cart_qty($cart_item_key, $giveaway_qty);
                                        $giveaway_qty=0;
                                    }else
                                    {
                                        $giveaway_qty = $giveaway_qty - $quantity;
                                    }
                                }
                            }     
                            continue; //no need to execute the below codes, Its for product condition `or`
                        }
                        
                        $cr_eligibility=floor($total_qty_in_cart/$bogo_products[$item_id]['qty']);
                        if($cr_eligibility<=$total_eligibility)
                        {
                            $total_eligibility=$total_eligibility-$cr_eligibility;
                        }else /* there are some extra giveaway items */
                        {
                            $max_qty=$total_eligibility*$bogo_products[$item_id]['qty'];
                            foreach($available_qty_data as $cart_item_key=>$quantity)
                            {
                                if(0 >= $max_qty)
                                {
                                    //eligibile max qty reached. Remove it
                                    WC()->cart->remove_cart_item($cart_item_key);
                                    continue;
                                }

                                if($max_qty >= $quantity)
                                {
                                    $max_qty=$max_qty-$quantity;
                                }else
                                {
                                    $this->update_cart_qty($cart_item_key, $max_qty);
                                    $max_qty=0;
                                }
                            }
                        }
                    }
                }elseif('same_product_in_the_cart' === $bogo_customer_gets)
                {
                    /* allowed quantity */
                    $item_qty=$this->get_quantity_for_non_individual_quantity_bogo($coupon_id);

                    $total_qty_in_cart=0;
                    foreach(WC()->cart->get_cart() as $cart_item_key=>$cart_item)
                    {
                        if(self::is_a_free_item($cart_item, $coupon_code)) //this is a free item but not from this coupon, so we need to skip it
                        {
                            if(!$this->non_free_product_exists($cart_item)) //non free product for the current free product not found so the current free product is not valid as giveaway */
                            {
                                WC()->cart->remove_cart_item($cart_item_key);
                            }else
                            {

                                if($total_qty_in_cart<$item_qty) 
                                {
                                    
                                    $balance_qty=$item_qty-$total_qty_in_cart; /* balance quantity allowed for giveaway */

                                    if($balance_qty<$cart_item['quantity']) /* current cart item quantity is greater than allowed. So adjust the quantity */
                                    {
                                        $this->update_cart_qty($cart_item_key, $balance_qty);
                                        $total_qty_in_cart+=$balance_qty;
                                    }else
                                    {
                                        $total_qty_in_cart+=$cart_item['quantity'];
                                    }

                                }else /* max quantity reached. So remove the upcoming items */
                                {
                                    WC()->cart->remove_cart_item($cart_item_key); 
                                }
                            }   
                        }
                    }

                }elseif('any_product_from_store' === $bogo_customer_gets)
                {
                    /* allowed quantity */
                    $max_qty_allowed=$this->get_quantity_for_non_individual_quantity_bogo($coupon_id);                   
                    
                    $max_qty_allowed_backup=$max_qty_allowed; /* this is using for BOGO available message toggling section */
                    $total_qty_in_cart=0;

                    foreach($cart_items as $cart_item_key=>$cart_item)
                    {
                        if(self::is_a_free_item($cart_item, $coupon_code))
                        {
                            if(0 === $max_qty_allowed)
                            {
                                WC()->cart->remove_cart_item($cart_item_key);
                                continue;
                            }

                            if($cart_item['quantity']<=$max_qty_allowed)
                            {
                               $max_qty_allowed-=$cart_item['quantity'];
                               $total_qty_in_cart+=$cart_item['quantity'];
                            }else
                            {
                                $total_qty_in_cart+=$max_qty_allowed;
                                $this->update_cart_qty($cart_item_key, $max_qty_allowed);
                                $max_qty_allowed=0;
                            }
                        }
                    }

                    /* check and set is bogo fully availed or not */
                    self::set_bogo_fully_availed($coupon_id, $coupon_code, $max_qty_allowed_backup, $total_qty_in_cart);

                }elseif('any_product_from_category' === $bogo_customer_gets)
                {
                    $category_condition = $this->get_any_product_from_category_condition( $coupon_code );

                    if ( 'any' === $category_condition ) {

                        $frequency = $this->get_coupon_applicable_count( $coupon_id, $coupon_code ); // Total eligible frequency
                        $bogo_free_categories = $this->get_coupon_meta_value( $coupon_id, '_wt_sc_bogo_free_categories' );                         
                        $total_allowed_qty = $this->get_quantity_for_non_individual_quantity_bogo( $coupon_id, $frequency ); // Total giveaway quantity allowed for this coupon

                        foreach( $cart_items as $item_key => $cart_item ) { // Loop through the cart items                           
                            
                            // Free item of current coupon
                            if( self::is_a_free_item( $cart_item, $coupon_code ) ) {

                                // The current giveaway category not belongs to the coupon settings. So remove.
                                if ( ! isset( $cart_item['free_category'] ) || 
                                    ! isset( $bogo_free_categories[ $cart_item['free_category'] ] ) 
                                    ) {
                                    $cart->remove_cart_item( $item_key );
                                    continue;
                                }

                                // No more quantity allowed, so remove 
                                if( 0 === $total_allowed_qty ) {                               
                                    $cart->remove_cart_item( $item_key );
                                }


                                if( $total_allowed_qty < $cart_item['quantity'] ) {                                                      
                                    $this->update_cart_qty( $item_key, $total_allowed_qty ); // Reduce the quantity
                                    $total_allowed_qty = 0;
                                } else {                               
                                    $total_allowed_qty -= $cart_item['quantity'];
                                }                                
                            }                              
                        }

                        $this->trigger_dummy_bogo_fully_availed( $coupon_id, $coupon_code, ( 0 === (int) $total_allowed_qty ) );

                    } else {
                        
                        // Legacy code. Because we have stopped support for `all` condition for this type of BOGO.
                        include_once __DIR__ . "/classes/giveaway_product_legacy.php";
                        Wt_Smart_Coupon_Giveaway_Product_Public_Legacy::get_instance()->adjust_giveaway_count_when_eligibility_changed__any_product_from_category( $coupon_code, $coupon_id, $cart_items );
                    
                    }

                }elseif('any_product_from_category_in_the_cart' === $bogo_customer_gets)
                {
                    $bogo_free_categories = $this->get_cart_item_categories_for_coupon($coupon_code, $coupon_id, false);
                    
                    if(!empty($bogo_free_categories))
                    {
                        foreach($bogo_free_categories as $prod_cat_slug => $cat_data)
                        {
                            if($cat_data['giveaway_qty'] < $cat_data['cart_giveaway_qty']) //cart giveaway quantity exceeds the limits
                            {
                                $to_deduct = $cat_data['cart_giveaway_qty'] - $cat_data['giveaway_qty'];

                                foreach($cat_data['giveaway_item_arr'] as $cart_item_key => $cart_item)
                                {
                                    if(0 === $to_deduct) //all extra quantities are removed
                                    {
                                        break;
                                    }

                                    $to_remove = min($cart_item['quantity'], $to_deduct);
                                    $new_qty = $cart_item['quantity'] - $to_remove;

                                    $this->update_cart_qty($cart_item_key, $new_qty);

                                    $to_deduct -= $to_remove;
                                }
                            }
                        }

                        /* check free products other than the above category list */
                        foreach($cart_items as $cart_item_key => $cart_item)
                        {
                            /**
                             *  Is a free item and the giveaway category not exists in the BOGO category list
                             */
                            if(self::is_a_free_item($cart_item, $coupon_code) && !isset($bogo_free_categories[$cart_item['free_category']]))
                            {
                                $cart->remove_cart_item($cart_item_key);
                            }
                        }


                        //re-prepare the category list
                        $bogo_free_categories = $this->get_cart_item_categories_for_coupon($coupon_code, $coupon_id);

                    }else /* Normal products are not available. So remove all giveaways of the current coupon */
                    { 
                        foreach($cart_items as $cart_item_key => $cart_item)
                        {
                            if(self::is_a_free_item($cart_item, $coupon_code))
                            {
                                $cart->remove_cart_item($cart_item_key);
                            }
                        }                 
                    }

                    //trigger the function based on the fully availed. Empty categories means BOGO fully availed
                    $this->trigger_dummy_bogo_fully_availed($coupon_id, $coupon_code, empty($bogo_free_categories));
                
                }            
            }
        }    
    }

    /**
     *  Alter coupon block title text.
     *  @since  2.0.4
     *  @since  2.0.8   Implemented customized messages option
     *  @param      array     $coupon_data    Coupon data
     *  @param      object    $coupon         WC_Coupon object
     *  @return     array     $coupon_data
     */
    public function alter_coupon_title_text($coupon_data, $coupon)
    {
        if(self::is_bogo($coupon))
        {
            $coupon_data['coupon_amount'] = '';
            $bogo_label = $this->get_customized_text('bogo_coupon_block_label', array('coupon_code' => $coupon->get_code()));
            $coupon_data['coupon_type'] = apply_filters( 'wt_sc_alter_coupon_title_text', $bogo_label, $coupon);
        }
        return $coupon_data;
    }

    /**
     *  Checks non free product of current cart item exists. Using in `same_product_in_the_cart` option
     *  @since  2.0.4
     *  @param      array     $cart_item_to_check    Cart item 
     *  @return     bool  
     */
    private function non_free_product_exists($cart_item_to_check)
    {
        $is_exists=false;
        foreach(WC()->cart->get_cart() as $cart_item_key=>$cart_item)
        {
            if(!self::is_a_free_item($cart_item)) //not a free item
            {
                if(self::is_same_prodct($cart_item_to_check, $cart_item['product_id'], $cart_item['variation_id']))
                {
                    $is_exists=true;
                    break;
                }
            }
        } 

        return $is_exists;
    }

    /**
     *  Is product/category restriction enabled
     *  @since  2.0.4
     *  @param      int      $coupon_id    Coupon ID 
     *  @return     bool  
     */
    private function is_product_category_restriction_enabled($coupon_id)
    {
        $wt_enable_product_category_restriction='yes';
        if(Wt_Smart_Coupon_Common::module_exists('coupon_restriction'))
        {
            $wt_enable_product_category_restriction =Wt_Smart_Coupon_Restriction::get_coupon_meta_value($coupon_id, '_wt_enable_product_category_restriction');
        }

        return wc_string_to_bool($wt_enable_product_category_restriction);
    }

    /**
     *  Is apply frequency enabled and prepare the quantity based on applicable frequency
     *  @since  2.0.4
     *  @since  2.0.5   Frequency taking functionality moved to another new function named `get_coupon_applicable_count`
     *  @since 2.0.7    $frequency added as an optional argument. If frequency given then value will be prepared based on the given frequency
     *  @param      int      $coupon_id    Coupon ID 
     *  @param      int      $quantity     Quantity 
     *  @return     int      $quantity     Quantity 
     */
    private function prepare_quantity_based_on_apply_frequency($coupon_id, $quantity, $frequency = null)
    {
        $wt_sc_bogo_apply_frequency = $this->get_coupon_meta_value($coupon_id, '_wt_sc_bogo_apply_frequency');
        
        if('repeat' === $wt_sc_bogo_apply_frequency)
        {
            $coupon_code    = wc_get_coupon_code_by_id($coupon_id);
            $frequency      = (is_null($frequency) ? $this->get_coupon_applicable_count($coupon_id, $coupon_code) : $frequency);     
            $quantity       = ($quantity * $frequency);
        }

        return $quantity;
    }

    /**
     *  This method will take coupon applicable count from session created by coupon restriction module
     *  @since 2.0.5
     */ 
    private function get_coupon_applicable_count($coupon_id, $coupon_code)
    {
        $frequency=1;
        if(Wt_Smart_Coupon_Public::module_exists('coupon_restriction'))
        {
            //count error in block cart/checkout, so recalculate 
            $this->recalculate_apply_frequency_count( new WC_Coupon( $coupon_id ) );

            $bogo_applicable_count=Wt_Smart_Coupon_Restriction_Public::get_bogo_applicable_count_session(); 
            $coupon_code=wc_format_coupon_code($coupon_code);
            $frequency=absint(isset($bogo_applicable_count[$coupon_code]) ? $bogo_applicable_count[$coupon_code] : 1);
            $frequency=($frequency<1 ? 1 : $frequency);

            $wt_sc_bogo_apply_frequency = $this->get_coupon_meta_value($coupon_id, '_wt_sc_bogo_apply_frequency');
            $frequency=('once' === $wt_sc_bogo_apply_frequency ? 1 : $frequency);
        }

        return $frequency;
    }

    /**
     *  Recalculate apply frequency count.
     *  @since  2.0.4
     *  @param  object      $coupon    WC_Coupon object 
     */
    private function recalculate_apply_frequency_count($coupon)
    {
        $coupon_id=$coupon->get_id();
        $wt_sc_bogo_apply_frequency = $this->get_coupon_meta_value($coupon_id, '_wt_sc_bogo_apply_frequency');
        if('repeat' === $wt_sc_bogo_apply_frequency)
        {
            if(Wt_Smart_Coupon_Public::module_exists('coupon_restriction'))
            {
                $coupon_restriction_obj=Wt_Smart_Coupon_Restriction_Public::get_instance();
                try {
                    $coupon_restriction_obj->wt_woocommerce_coupon_is_valid(true, $coupon);
                }catch(Exception $e)
                {
                   wc_add_notice($e->getMessage(), 'error'); 
                }
            }
        }
    }

    /**
     *  This function will prepare product list based product/category restriction. Applicable for `same_product_in_the_cart`
     *  @since 2.0.5
     *  @param $coupon object WC_coupon object
     *  @param $qty_price_data array Price/Quantity data for giveaway (optional)
     */
    private function prepare_product_list_for_any_product_from_cart($coupon, $qty_price_data=array())
    {
        $coupon_products = $coupon->get_product_ids();
        $coupon_products = (!is_array($coupon_products) ? array() : $coupon_products);

        $coupon_categories = $coupon->get_product_categories();
        
        $new_coupon_products = array();

        foreach(WC()->cart->get_cart() as $cart_item)
        {
            $found = false;
            
            if($cart_item['variation_id']>0)
            {
                if(in_array($cart_item['variation_id'], $coupon_products) || in_array($cart_item['product_id'], $coupon_products))
                {
                    $new_coupon_products[$cart_item['variation_id']] = $qty_price_data;
                    $found = true;
                }
            }else
            {
                if(in_array($cart_item['product_id'], $coupon_products))
                {
                    $new_coupon_products[$cart_item['product_id']] = $qty_price_data;
                    $found = true;
                }
            }

            if(!$found) /* if the cart item not include in the product restriction */
            {
                $product_cats = Wt_Smart_Coupon_Common::get_product_cat_ids($cart_item['product_id']);
                $matching_cats=array_intersect($coupon_categories, $product_cats); /* $coupon_categories must be the first argument, because its in the order of product direct category then parent category. To maintain the order we have to use $coupon_categories as first argument */
                if(!empty($matching_cats)) /* this product is under the given categories */
                {
                    $item_id=($cart_item['variation_id']>0 ? $cart_item['variation_id'] : $cart_item['product_id']);
                    $new_coupon_products[$item_id]=$qty_price_data;    
                    
                    if( 0 < $cart_item['variation_id'] ){
                        $new_coupon_products[$item_id]['attributes'] = $cart_item['variation'];
                    }                                                                                   
                }          
            }
        }

        return $new_coupon_products;
    }

    /**
     *  Calculating balance giveaway quantity based on the giveaway products exists in the cart. (`same_product_in_the_cart`)
     *  @since  2.0.5
     *  @param  $coupon_id      int     ID of coupon
     *  @param  $coupon_code    string  Coupon code
     *  @return $balance_qty    int     Balance giveaway quantity to be added to cart
     */
    private function prepare_balance_quantity_for_same_product_in_cart($coupon_id, $coupon_code)
    {
        /* allowed quantity */
        $item_qty=$this->get_quantity_for_non_individual_quantity_bogo($coupon_id);

        //get cart item data
        $total_qty=self::get_total_coupon_cart_item_qty($coupon_code);

        $total_qty=!empty($total_qty) ? array_sum($total_qty) : 0; //existing free products in the cart

        return max(($item_qty-$total_qty), 0); //avoid negative values
    }

    /**
     *  Trigger WC is_valid coupon check. This is required for showing giveaway available message
     *  @since 2.0.5
     */
    public function trigger_coupon_is_valid()
    {
        $cart = self::get_cart_object();
        if(is_object($cart) && is_callable(array($cart, 'is_empty')) && !$cart->is_empty())
        {
            foreach($cart->get_applied_coupons() as $coupon_code)
            {
                $coupon=new WC_Coupon($coupon_code);
                $coupon->is_valid();
            }
        }
    }

    /**
     *  This function is used to check the giveaway max quantity based on the available giveaway quantity in cart and apply repeatedly option
     *  Applicable for `specific_product` condition
     * 
     *  @since 2.0.5
     *  
     *  @param  $coupon_code                string              coupon code
     *  @param  $coupon_id                  int                 coupon id
     *  @param  $bogo_customer_gets         string              customer gets option in giveaway (using when $throw_error argument is true)
     *  @param  $bogo_product_condition     string              Any(or)/All(and) products. 
     *  @param  $bogo_products              array               Array of giveaway products under the current coupon (reference argument)
     *  @param  $frequency                  int                 Applicable frequency based on apply repeatedly option   
     *  @param  $options                    array               Other optional arguments
     *                                                          $throw_error    boolean     Throw error message when max quantity reached. Othewise return an empty array($bogo_products) [Optional]. Default: false (Do not throw error) - Applicable for `or` product condition
     *                                                          $update_qty     boolean     Update existing giveaway product quantiy if mismatch found. Default: false (Do not update quantity) - Applicable for `and` product condition
     *  
     *  @return                             void/boolean        `void` when $bogo_product_condition is `or` and $throw_error is true when max quantity reached
     *                                                          `boolean` when $bogo_product_condition is `and` and $update_quantity is true
     */
    public function check_giveaway_max_quantity($coupon_code, $coupon_id, $bogo_customer_gets, $bogo_product_condition, &$bogo_products, $frequency, $options=array())
    {
        $cart_items=WC()->cart->get_cart();
        
        if('and' === $bogo_product_condition)
        {
            $update_qty=isset($options['update_quantity']) ? (bool) $options['update_quantity'] : false;

            $is_giveaway_fully_added=true; // only applicable when $update_qty is true
            $cart_items = WC()->cart->get_cart();

            $giveaway_data = array();

            foreach($cart_items as $cart_item_key => $cart_item)
            {
                if(self::is_a_free_item($cart_item, $coupon_code))
                {
                    $item_id = $this->prepare_item_id_for_specific_product_bogo( $cart_item, $bogo_products );

                    if ( 0 < $item_id)
                    {
                       if ( isset( $giveaway_data[ $item_id ] ) )
                       {
                            $giveaway_data[$item_id] += $cart_item['quantity'];
                       }else
                       {
                            $giveaway_data[$item_id] = $cart_item['quantity'];
                       }
                    }
                }               
            }

            foreach($cart_items as $cart_item_key => $cart_item)
            {
                if(self::is_a_free_item($cart_item, $coupon_code))
                {
                    $item_id = $this->prepare_item_id_for_specific_product_bogo( $cart_item, $bogo_products );

                    if ( 0 < $item_id)
                    {
                        $bogo_item_data = $bogo_products[$item_id];
                        $bogo_item_data['qty'] = ( 0 === absint($bogo_item_data['qty']) ? 1 : $bogo_item_data['qty'] );
                        $giveaway_qty = $this->prepare_quantity_based_on_apply_frequency( $coupon_id, $bogo_item_data['qty'] );

                        if((int) $giveaway_qty === (int) $giveaway_data[$item_id])
                        {
                            unset($bogo_products[$item_id]); /* remove already added product from bogo list */
                        }else
                        {
                            if($update_qty)
                            {
                                //quantity mismatch so update
                                $this->update_cart_qty( $cart_item_key, $giveaway_qty );
                                $is_giveaway_fully_added = false;
                                unset( $bogo_products[ $item_id ] ); /* remove already added product from bogo list */
                            }
                        }
                    }
                }
            }

            if($update_qty)
            {
                return $is_giveaway_fully_added;
            }

        }else
        {
            
            $throw_error=isset($options['throw_error']) ? (bool) $options['throw_error'] : false;

            $cart_available_qty=array();
            foreach($cart_items as $item_key=>$cart_item)
            {
                if(self::is_a_free_item($cart_item, $coupon_code)) /* a free item under the given coupon */
                {
                    $item_id=0;
                    if($cart_item['variation_id']>0 && isset($bogo_products[$cart_item['variation_id']]))
                    {
                        $item_id=$cart_item['variation_id'];
                    }elseif(isset($bogo_products[$cart_item['product_id']]))
                    {
                        $item_id=$cart_item['product_id'];
                    }

                    if($item_id>0)
                    {
                        if(!isset($cart_available_qty[$item_id]))
                        {
                            $cart_available_qty[$item_id]=array();
                        }
                        $cart_available_qty[$item_id][$item_key]=$cart_item['quantity'];
                        
                    }else
                    {
                        //a non giveaway free product. Remove it
                        WC()->cart->remove_cart_item($item_key);
                    }
                }
            }

            $total_eligibility=$frequency;
            foreach($cart_available_qty as $item_id=>$available_qty_data)
            {
                $total_qty_in_cart=array_sum($available_qty_data);
                $cr_eligibility=floor($total_qty_in_cart/$bogo_products[$item_id]['qty']);
                if($cr_eligibility>=$total_eligibility)
                {
                    if($throw_error)
                    {
                        self::set_add_to_cart_messages(
                            "already_availed_bogo", 
                            array(
                                'coupon_id'=>$coupon_id, 
                                'customer_gets'=>$bogo_customer_gets,
                                'apply_frequency'=>$this->get_coupon_meta_value($coupon_id, '_wt_sc_bogo_apply_frequency'),
                            ), 
                            self::$bogo_coupon_type_name);

                        wp_die();
                    }else
                    {
                        $bogo_products=array();
                    }
                }else
                {
                    $total_eligibility=$total_eligibility-$cr_eligibility;
                }
            }
        }
    }


    /**
     *  Show the giveaway discount on cart summary section.
     * 
     *  @since 2.3.0        [Fix] Discount is not calculated when the cart is refreshed via ajax.
     * 
     *  @param  string      $discount_amount_html   Coupon Discount HTML
     *  @param  WC_Coupon   $coupon                 Coupon object
     *  @return string      $discount_amount_html   Coupon Discount HTML
     */
    public function alter_coupon_discount_amount_html( $discount_amount_html, $coupon ) {
        $cart = self::get_cart_object();

        if ( ! is_null( $cart ) && self::is_bogo( $coupon ) ) {
            
            // Check the discounts are not already calculated.
            if ( empty( self::$bogo_discounts ) ) {
                foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
                    // The below method will calculate and store the discount to `bogo_discounts` varaiable.
                    $this->calculate_bogo_discount( $cart_item );
                }
            }

            $coupon_code    = wc_format_coupon_code( $coupon->get_code() );
            $coupon_id      = $coupon->get_id();     
            $discount       = ( isset( self::$bogo_discounts[ $coupon_code ] ) ? self::$bogo_discounts[ $coupon_code ] : 0 );
            $discount       = ( ! $this->is_apply_discount_before_tax_enabled( $coupon_id ) ? $discount * -1 : $discount );

            $discount_amount_html = wc_price( $discount );
        }

        return $discount_amount_html;
    }


    /**
     *  When the giveaway scenario: 
     *  1. The giveaway condition is specific product 
     *  2. Only single prodcut with 100% discount
     *  3. Apply repeatedly enabled
     *  Update giveaway quantity when new cart item added
     * 
     *  @since 2.0.7
     * 
     */
    public function check_and_add_giveaway_on_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data)
    {
        $this->check_to_add_giveaway($cart_item_key, $quantity, 0, WC()->cart);
    }


    /**
     *  Convert cheapest cart item as giveaway.
     * 
     *  @since 2.0.7
     */
    public function convert_cheapest_as_giveaway()
    {
        if(is_null(($cart = self::get_cart_object()))) //cart object not available
        {
            return;   
        }

        
        /**
         *  Cart is empty or a single item
         *  
         *  @since 2.0.8
         */
        $cart_items = $cart->get_cart();

        if(empty($cart_items) || (!empty($cart_items) && 1 === array_sum(array_column($cart_items, 'quantity'))))
        {
            return;
        }


        $applied_coupon_codes = WC()->cart->get_applied_coupons();

        if(empty($applied_coupon_codes))
        {
            return; //no coupons applied
        }  

        global $wbte_sc_cheapest_currently_adding;

        foreach($applied_coupon_codes as $applied_coupon_code) //find cheapest giveaway enabled coupons
        {
            $coupon = new WC_Coupon($applied_coupon_code);
            $coupon_id = $coupon->get_id();
            $bogo_customer_gets = $this->get_coupon_meta_value($coupon_id, '_wt_sc_bogo_customer_gets');

            if($this->is_cheapest_giveaway_enabled_coupon($coupon))
            {
                $wbte_sc_cheapest_currently_adding = 1; // Assign 1 to indicate that product is adding to the cart where coupon has 'apply cheapest' enabled.
                if('any_product_from_category' === $bogo_customer_gets)
                {
                    $this->apply_cheapest_giveaway_for_any_product_from_category($coupon, $coupon_id, $applied_coupon_code);

                }elseif('any_product_from_store' === $bogo_customer_gets)
                {
                    $this->apply_cheapest_giveaway_for_any_product_from_store($coupon, $coupon_id, $applied_coupon_code);

                }elseif('any_product_from_category_in_the_cart' === $bogo_customer_gets)
                {
                    $this->apply_cheapest_giveaway_for_any_product_from_category_in_the_cart($coupon, $coupon_id, $applied_coupon_code);
                }
                $wbte_sc_cheapest_currently_adding = 0; //The process of 'apply cheapest' is completed, so assign 0.
            }
        }

    }


    /**
     *  Enable Individual use` option for cheapest giveaway enabled coupons
     *   
     *  @since  2.0.7 
     *  @param  bool            $is_enabled     `Individual use` enabled or not
     *  @param  WC_Coupon       $coupon         WC_Coupon object
     *  @return bool            `Individual use` enabled or not
     */
    public function set_cheapest_giveaway_coupon_to_individual_use($is_enabled, $coupon)
    {
        if($this->is_cheapest_giveaway_enabled_coupon($coupon))
        {
           $is_enabled = true; //always an individual use coupon 
        }    

        return $is_enabled;
    }


    /**
     *  Force remove coupons that can be used along with individual use coupons
     *  
     *  @since 2.0.7
     *  @param $allowed_coupons     array       Array of coupon codes that can be used along with individual use coupons 
     *  @param $the_coupon          WC_Coupon   WC_Coupon object
     *  @param $applied_coupons     array       Array of applied coupon codes
     */
    public function force_remove_individual_use_allowed_coupons($allowed_coupons, $the_coupon, $applied_coupons)
    {
        foreach($applied_coupons as $applied_coupon_code) //find any coupon with cheapest giveaway option enabled.
        {
            $coupon = new WC_Coupon($applied_coupon_code);

            if($this->is_cheapest_giveaway_enabled_coupon($coupon))
            {
                $allowed_coupons = array(); //empty the array
                break;
            }
        }

        return $allowed_coupons;
    }


    /**
     *  Do not allow other coupons along with `cheapest as giveaway` enabled coupons
     * 
     *  @since 2.0.7
     *  @param bool         $allow_coupon                   Is allow the newly applied coupon
     *  @param WC_Coupon    $coupon                         WC_Coupon object for newly applied coupon
     *  @param WC_Coupon    $individual_enabled_coupon      WC_Coupon object for individual enabled coupon
     *  @return bool        Is allow or not the current coupon
     */
    public function reject_other_coupon_along_with_cheapest_giveaway_coupon($allow_coupon, $coupon, $individual_enabled_coupon)
    {
        return ($this->is_cheapest_giveaway_enabled_coupon($individual_enabled_coupon) ? false : $allow_coupon);
    }

    
    /**
     *  Checks the current coupon was `Cheapest giveaway` option enabled.
     *   
     *  @since  2.0.7 
     *  @param  WC_Coupon    $coupon     WC_Coupon object
     *  @return bool        Is `Cheapest giveaway` enabled or not
     */
    private function is_cheapest_giveaway_enabled_coupon($coupon)
    {
        $coupon_id = $coupon->get_id();
        $bogo_customer_gets = $this->get_coupon_meta_value($coupon_id, '_wt_sc_bogo_customer_gets');

        return (self::is_bogo($coupon) 
            && wc_string_to_bool(self::get_coupon_meta_value($coupon_id, '_wt_sc_cheapest_item_as_giveaway'))
            && in_array($bogo_customer_gets, self::$allowed_customer_gets_cheapest_giveaway)
        );
    }

    
    /**
     *  Convert cheapest item as giveaway if the coupon giveaway option is `any_product_from_category`
     * 
     *  @since 2.0.7
     *  @since 2.1.1            Added support for `any` category condition
     *  @param $coupon          WC_Coupon   WC_Coupon object
     *  @param $coupon_id       int         Coupon id
     *  @param $coupon_code     string      Coupon code  
     */
    private function apply_cheapest_giveaway_for_any_product_from_category($coupon, $coupon_id, $coupon_code)
    {
        $bogo_free_categories   = $this->get_coupon_meta_value($coupon_id, '_wt_sc_bogo_free_categories');
        $bogo_free_category_ids = array_keys($bogo_free_categories);
        $category_condition = $this->get_any_product_from_category_condition( $coupon_code );

        if ( 'any' === $category_condition ) {

            $already_converted_as_giveaway = array(); //cart item keys of giveaway items
            $price_of_eligibility_item_with_lowest_price = $this->get_price_of_eligibility_item_having_lowest_price($coupon, $coupon_id, $coupon_code);
            $frequency = $this->get_coupon_applicable_count($coupon_id, $coupon_code);


            cheapest_category_giveaway_start: //we have to re-start from here in some cases
            $temp_arr = array(); //temp cart items array

            //for sorting purpose
            $price_arr  = array();
            $coupon_arr = array();

            $cart_items = WC()->cart->get_cart();

            /**
             *  Prepare cart item list under the current categories
             */
            foreach($cart_items as $cart_item_key => $cart_item)
            {
                if(in_array($cart_item_key, $already_converted_as_giveaway)) //skip the items that are already converted as giveaway for previous category
                {
                    continue;
                }


                $product_cats = Wt_Smart_Coupon_Common::get_product_cat_ids($cart_item['product_id']);
                $matching_cats = array_intersect($product_cats, $bogo_free_category_ids);
                if( ! empty($matching_cats) ) /* this item is belongs to the current coupon categories */
                {
                    $item_id = ($cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id']);
                    $product = wc_get_product($item_id);
                    $product_price = self::get_product_price($product);

                    $cart_item['wt_price']      = $product_price;
                    $cart_item['wt_category']   = reset($matching_cats); // Take the first category, on cheapest category, all the categories have equal value.
                    $temp_arr[$cart_item_key]   = $cart_item;
                    $price_arr[]                = $product_price;
                    $coupon_arr[]               = (isset($cart_item['free_gift_coupon']) ? $cart_item['free_gift_coupon'] : '');                
                }
            }


            /**
             *  Check and convert as giveaway or normal items
             */
            if(!empty($temp_arr)) //items present in the current categories
            {
                //sort the item by price descending first then reverse the array.
                array_multisort($price_arr, SORT_DESC, SORT_REGULAR, $temp_arr, $coupon_arr);              
                $temp_arr = array_reverse($temp_arr);

                //this is used to run multiple iteration when required
                $old_giveaway_count = 0;
                $new_giveaway_count = 0;

                $eligibility_qty = $this->get_quantity_for_non_individual_quantity_bogo($coupon_id, $frequency); //Prepare based on the given $frequency. This is for giving compatibility when multiple iteration exists

                // Loop through the sorted cart items
                foreach($temp_arr as $cart_item_key => $cart_item)
                {
                    $item_id = ($cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id']);
                    $product_price = $cart_item['wt_price'];
                    $category_id = $cart_item['wt_category'];
                    $args['variation_attributes'] = isset( $cart_item['variation'] ) ? $cart_item['variation'] : array() ;

                    /**
                     *  Do not add giveaway that has price higher than the lowest priced eligible item. 
                     */
                    if(!is_null($price_of_eligibility_item_with_lowest_price) && $price_of_eligibility_item_with_lowest_price < $product_price)
                    {
                        if(self::is_a_free_item($cart_item))
                        {
                            //convert as normal item
                            $this->convert_giveaway_cartitem_as_normal_cartitem($cart_item_key);
                            $old_giveaway_count += $cart_item['quantity'];
                        }

                        continue; //no need to do further checks
                    }

                    /**
                     *  Check the item is available for giveaway or already giveaway
                     */
                    if(self::is_a_free_item($cart_item)) //already a free item
                    {
                        $old_giveaway_count += $cart_item['quantity'];

                        if(0 === $eligibility_qty) //check eligibility is remaining
                        {
                            //convert as normal item
                            $this->convert_giveaway_cartitem_as_normal_cartitem($cart_item_key);

                        }else
                        {
                            //deduct eligibility
                            $to_deduct = $cart_item['quantity'];

                            //this is to skip this item in the next category loop
                            $already_converted_as_giveaway[] = $cart_item_key; 

                            if($cart_item['quantity'] > $eligibility_qty)
                            {
                                $to_deduct = $eligibility_qty;

                                //the specified quantity will convert as normal item
                                $this->convert_giveaway_cartitem_as_normal_cartitem($cart_item_key, ($cart_item['quantity'] - $eligibility_qty));
                            }

                            $new_giveaway_count += $to_deduct;
                            $eligibility_qty -= $to_deduct;
                        }

                    }else
                    {
                        $qty_available_for_giveaway = 0;
                        $cart_item_quantity = $cart_item['quantity'];

                        //loop through the eligibility qty
                        for($i = 0; $i < min($eligibility_qty, $cart_item_quantity); $i++)
                        {
                            $cart_items = WC()->cart->get_cart(); //need to take the cart list again to get the refreshed list.
                            
                            $cart_item = $cart_items[$cart_item_key];
                            $new_qty = $cart_item['quantity'] - 1;
                            $args['variation_attributes'] = isset( $cart_item['variation'] ) ? $cart_item['variation'] : array() ;

                            $this->update_cart_qty($cart_item_key, $new_qty);

                            if(!$coupon->is_valid() || $frequency > $this->get_coupon_applicable_count($coupon_id, $coupon_code)) //coupon eligibility gone, or eligibility count reduced.
                            {                      
                                if(0 === $new_qty)
                                {
                                    $this->set_as_normal_cartitem( $cart_item['product_id'], $cart_item['variation_id'], 1, $args );

                                }else
                                {
                                   $this->update_cart_qty($cart_item_key, ($new_qty + 1)); 
                                }

                                break;  //break the loop
                            }else
                            {
                                $qty_available_for_giveaway++;
                            }

                        }

                        if($qty_available_for_giveaway > 0) //we got some quantity to convert as giveaway
                        {
                            $new_cart_item_key = $this->add_item_to_cart( $item_id, $qty_available_for_giveaway, $coupon_code, $category_id, $args ); //add the quantity as giveaway
                            
                            if(false !== $new_cart_item_key)
                            {
                                $eligibility_qty = $eligibility_qty - $qty_available_for_giveaway; //deduct the currently converted quantity
                                $new_giveaway_count += $qty_available_for_giveaway;

                                //this is to skip this item in the next category loop
                                $already_converted_as_giveaway[]= $new_cart_item_key;
                            }
                        }
                    }
                }

                if(1 > self::$cheapest_giveaway_loop_count && $old_giveaway_count > $new_giveaway_count) //we have to recheck the items.
                {
                    self::$cheapest_giveaway_loop_count = 1; //to prevent indefinite loop

                    // Repeat the check, because new giveaway count is lesser than old giveaway count
                    goto cheapest_category_giveaway_start;
                }

                // Trigger BOGO fully availed
                $this->trigger_dummy_bogo_fully_availed( $coupon_id, $coupon_code, (0 === (int) $eligibility_qty) );
                $this->store_giveaway_available_message( $coupon_id, 'any_product_from_category' ); // Call again the message storing function, because `cheapest bogo` functionality executed after the default message storing function.
            }

        } else {
            
            // Legacy code. Because we have stopped support for `all` condition for this type of BOGO.
            include_once __DIR__ . "/classes/giveaway_product_legacy.php";
            Wt_Smart_Coupon_Giveaway_Product_Public_Legacy::get_instance()->apply_cheapest_giveaway_for__any_product_from_category( $coupon, $coupon_id, $coupon_code );
        }
    }

    
    /**
     *  This is for applying cheapest giveaway for `any_product_from_category`. 
     *  Unlike `any_product_from_store`, here we are preparing list of cart items based on the categories so the eligibility items may or may not in the list. 
     *  So we have to take the lowest priced eligibility item from the whole cart items instead of the prepared list.
     *      
     *  @since 2.0.7
     *  @param $coupon          WC_Coupon   Coupon object
     *  @param $coupon_id       int         Id of coupon
     *  @param $coupon_code     string      Coupon code
     *  @return null|float      Price of lowest priced eligibility cart item, If not found return null
     */
    private function get_price_of_eligibility_item_having_lowest_price($coupon, $coupon_id, $coupon_code)
    {
        $cart_items = WC()->cart->get_cart();
        $price_arr = array(); //for sorting purpose
        $coupon_arr = array(); //for sorting purpose

        foreach($cart_items as $cart_item_key => $cart_item)
        {
            $item_id = ($cart_item['variation_id']>0 ? $cart_item['variation_id'] : $cart_item['product_id']);
            $product = wc_get_product($item_id);
            $product_price = self::get_product_price($product);

            $cart_items[$cart_item_key]['wt_price'] = $product_price;
            
            $price_arr[]  = $product_price;
            $coupon_arr[] = (isset($cart_item['free_gift_coupon']) ? $cart_item['free_gift_coupon'] : '');
        }

        array_multisort($price_arr, SORT_DESC, SORT_REGULAR, $cart_items, $coupon_arr);
        $cart_items = array_reverse($cart_items);

        $price_of_eligibility_item_with_lowest_price = null;
        $frequency = $this->get_coupon_applicable_count($coupon_id, $coupon_code); //if it was a second iteration then take old frequency backup otherwise fresh one.
        
        //loop through the sorted cart items
        foreach($cart_items as $cart_item_key => $cart_item)
        {
            if(self::is_a_free_item($cart_item)) //no need to check free items for eligibility
            {
                unset( $cart_items[ $cart_item_key ] ); // Remove free items. Usefull when no lowset product is returned after the loop completion
                continue;
            }

            $this->update_cart_qty($cart_item_key, 0); //remove the item first
            $args['variation_attributes'] = isset( $cart_item['variation'] ) ? $cart_item['variation'] : array() ;

            if(!$coupon->is_valid() || $frequency > $this->get_coupon_applicable_count($coupon_id, $coupon_code)) //coupon eligibility gone, or eligibility count reduced.
            {
                //this item is required for the coupon to be valid or for eligibility count
                $item_id = ($cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id']);
                $product = wc_get_product($item_id);
                $price_of_eligibility_item_with_lowest_price = self::get_product_price($product);

                $this->set_as_normal_cartitem( $cart_item['product_id'], $cart_item['variation_id'], $cart_item['quantity'], $args );

                break; //break the loop, further check is not required, we only need the cheapest item.

            }else
            {
                $this->set_as_normal_cartitem( $cart_item['product_id'], $cart_item['variation_id'], $cart_item['quantity'], $args );
            }
        }

        if ( is_null( $price_of_eligibility_item_with_lowest_price ) ) {
            $first_cart_item = reset( $cart_items ); // First cart item
            $item_id = ( $first_cart_item['variation_id'] > 0 ? $first_cart_item['variation_id'] : $first_cart_item['product_id'] );
            $product = wc_get_product( $item_id );
            $price_of_eligibility_item_with_lowest_price = self::get_product_price( $product );
        }

        return $price_of_eligibility_item_with_lowest_price;
    }

    
    /**
     *  Convert cheapest item as giveaway if the coupon giveaway option is `any_product_from_store`
     * 
     *  @since 2.0.7
     *  @param $coupon          WC_Coupon   WC_Coupon object
     *  @param $coupon_id       int         Coupon id
     *  @param $coupon_code     string      Coupon code  
     */
    private function apply_cheapest_giveaway_for_any_product_from_store($coupon, $coupon_id, $coupon_code)
    {     
        /**
         *  Sort the cart items ascending by price.
         *  First we are sorting the array in descending order then reversing the array. Because, in case of multiple cheap price items and that items include giveaway then we need giveaway items in first positions, this will avoid switching giveaway items in each refresh
         */
        $cart_items = WC()->cart->get_cart();
        $price_arr = array(); //for sorting purpose
        $coupon_arr = array(); //for sorting purpose
        
        //this is used to run multiple iteration when required
        $old_giveaway_count = 0;
        $new_giveaway_count = 0;

        foreach($cart_items as $cart_item_key => $cart_item) 
        {
            $item_id = ($cart_item['variation_id']>0 ? $cart_item['variation_id'] : $cart_item['product_id']);
            $product = wc_get_product($item_id);
            $product_price = self::get_product_price($product);

            $cart_items[$cart_item_key]['wt_price'] = $product_price;
            
            $price_arr[]  = $product_price;
            $coupon_arr[] = (isset($cart_item['free_gift_coupon']) ? $cart_item['free_gift_coupon'] : '');
        }

        array_multisort($price_arr, SORT_DESC, SORT_REGULAR, $cart_items, $coupon_arr);
        $cart_items = array_reverse($cart_items);
   

        /**
         *  Check the cart items and convert as giveaway
         * 
         */
        $frequency              = (1 === self::$cheapest_giveaway_loop_count ? self::$cheapest_giveaway_frequency_backup : $this->get_coupon_applicable_count($coupon_id, $coupon_code)); //if it was a second iteration then take old frequency backup otherwise fresh one.
        $eligibility_qty        = $this->get_quantity_for_non_individual_quantity_bogo($coupon_id, $frequency); //Prepare based on the given $frequency. This is for giving compatibility when multiple iteration exists
        $eligibility_qty_back   = $eligibility_qty; //value backup

        $price_of_eligibility_item_with_lowest_price = null; //this is usefull when multiple cheapest item with same price exists 

        //loop through the sorted cart items
        foreach($cart_items as $cart_item_key => $cart_item)
        {
            $item_id = ($cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id']);
            $product_price = $cart_item['wt_price'];
            $args['variation_attributes'] = isset( $cart_item['variation'] ) ? $cart_item['variation'] : array() ;

            /**
             *  Do not add giveaway that has price higher than the lowset priced eligible item. 
             */
            if(!is_null($price_of_eligibility_item_with_lowest_price) && $price_of_eligibility_item_with_lowest_price < $product_price)
            {
                if(self::is_a_free_item($cart_item))
                {
                    //convert as normal item
                    $this->convert_giveaway_cartitem_as_normal_cartitem($cart_item_key);
                    $old_giveaway_count += $cart_item['quantity'];
                }

                continue; //no need to do further checks
            }

            /**
             *  Check the item is available for giveaway or already giveaway
             */
            if(self::is_a_free_item($cart_item)) //already a free item
            {
                $old_giveaway_count += $cart_item['quantity'];

                if(0 === $eligibility_qty) //check eligibility is remaining
                {
                    //convert as normal item
                    $this->convert_giveaway_cartitem_as_normal_cartitem($cart_item_key);

                }else
                {
                    //deduct eligibility
                    $to_deduct = $cart_item['quantity'];

                    if($cart_item['quantity'] > $eligibility_qty)
                    {
                        $to_deduct = $eligibility_qty;

                        //the specified quantity will convert as normal item
                        $this->convert_giveaway_cartitem_as_normal_cartitem($cart_item_key, ($cart_item['quantity'] - $eligibility_qty));

                    }

                    $new_giveaway_count += $to_deduct;
                    $eligibility_qty -= $to_deduct;
                }

            }else
            {
                $qty_available_for_giveaway = 0;

                //loop through the eligibility qty
                for($i = 0; $i < min($eligibility_qty, $cart_item['quantity']); $i++)
                {
                    $cart_items = WC()->cart->get_cart(); //need to take the cart list again to get the refreshed list.
                    
                    $cart_item = $cart_items[$cart_item_key];
                    $new_qty = $cart_item['quantity'] - 1;
                    $args['variation_attributes'] = isset( $cart_item['variation'] ) ? $cart_item['variation'] : array() ;

                    $this->update_cart_qty($cart_item_key, $new_qty);

                    if(!$coupon->is_valid() || $frequency > $this->get_coupon_applicable_count($coupon_id, $coupon_code)) //coupon eligibility gone, or eligibility count reduced.
                    {                      
                        if(0 === $new_qty)
                        {
                            $this->set_as_normal_cartitem( $cart_item['product_id'], $cart_item['variation_id'], 1, $args );

                        }else{
                           $this->update_cart_qty($cart_item_key, ($new_qty + 1)); 
                        }

                        $price_of_eligibility_item_with_lowest_price = $product_price; // take the price of eligiblity item with lowset price. Not giving giveaways with price greater than this price.
                        break;  //break the loop
                    }else
                    {
                        $qty_available_for_giveaway++;
                    }

                }

                if($qty_available_for_giveaway > 0) //we got some quantity to convert as giveaway
                {
                    $this->add_item_to_cart( $item_id, $qty_available_for_giveaway, $coupon_code, '', $args ); //add the quantity as giveaway
                    
                    $eligibility_qty = $eligibility_qty - $qty_available_for_giveaway; //deduct the currently converted quantity
                    $new_giveaway_count += $qty_available_for_giveaway;
                }
            }
        }

        if(1 > self::$cheapest_giveaway_loop_count && $old_giveaway_count > $new_giveaway_count) //we have to recheck the items.
        {
            self::$cheapest_giveaway_loop_count = 1; //to prevent idefinite loop
            self::$cheapest_giveaway_frequency_backup = $frequency; //assumes the frequency will also change. So we are storing the existing value for next iteration
            
            return $this->apply_cheapest_giveaway_for_any_product_from_store($coupon, $coupon_id, $coupon_code);
        }

        /* check and set, is bogo fully availed or not */
        self::set_bogo_fully_availed($coupon_id, $coupon_code, $eligibility_qty_back, ($eligibility_qty_back - $eligibility_qty));

        if($eligibility_qty_back > ($eligibility_qty_back - $eligibility_qty))
        {
            $this->store_giveaway_available_message($coupon_id, 'any_product_from_store'); /* show message */
        }
    }


    /**
     *  Convert giveaway item as normal cart item.
     *  
     *  @since 2.0.7
     *  @param  $cart_item_key  string  Cart item key
     *  @param  $quantity  int  Quantity to be converted as giveaway. Optional argument. If no quantity given then the whole cart item quantity is converetd as normal cart item 
     */
    private function convert_giveaway_cartitem_as_normal_cartitem($cart_item_key, $quantity = null)
    {
        $cart_item = WC()->cart->cart_contents[$cart_item_key];
        $args['variation_attributes'] = isset( $cart_item['variation'] ) ? $cart_item['variation'] : array() ;
        
        if(is_null($quantity)) //quantity not specified so move all quantity as normal product
        {
            WC()->cart->remove_cart_item($cart_item_key);
            $quantity = $cart_item['quantity'];
        }else
        {
            $this->update_cart_qty($cart_item_key, ($cart_item['quantity'] - $quantity)); 
        }
        
        $this->set_as_normal_cartitem( $cart_item['product_id'], $cart_item['variation_id'], $quantity, $args );

    }

    
    /**
     *  Check cart `giveaway product id` and coupon `giveaway product id` to confirm the current giveaway item belongs to the coupon.
     *  When a multi language plugin(WPML) is active then the function will compare ids of all languages with giveaway product ids to get a match
     * 
     *  @since 2.0.7
     *  @param $cart_item       array       Cart item array
     *  @param $coupon_id       int         Id of coupon
     *  @param $bogo_products   array       Associative array of giveaway products and its data
     *  @return $item_id        int         If any match found then the matched ID will return otherwise 0
     */
    private function check_giveaway_id_match_on_multi_lang_site($cart_item, $coupon_id, $bogo_products = null)
    {
        $bogo_products = is_null($bogo_products) ? self::get_all_bogo_giveaway_products($coupon_id) : $bogo_products;
        $item_id = 0;
        
        if(0 < $cart_item['variation_id'] && isset($bogo_products[$cart_item['variation_id']]))
        {
            $item_id = $cart_item['variation_id'];

        }elseif(isset($bogo_products[$cart_item['product_id']]))
        {
            $item_id = $cart_item['product_id'];
        }

        /**
         *  For multi language compatibility
         */
        if(0 === $item_id)
        {
            $multi_lang_obj = Wt_Smart_Coupon_Mulitlanguage::get_instance();

            if($multi_lang_obj->is_multilanguage_plugin_active())
            {
                $bogo_product_ids = array_keys($bogo_products); //product ids

                if(0 < $cart_item['variation_id']) //variable product
                {
                    /**
                     *  Take ids of all languages
                     */
                    $all_lang_ids = $multi_lang_obj->get_all_translations($cart_item['variation_id'], 'post_product');

                    if(!empty($all_lang_ids) && !empty($matching_ids = array_intersect($all_lang_ids, $bogo_product_ids)))
                    {
                        $item_id = (int) reset($matching_ids); //take first item
                    }
                }

                if(0 === $item_id)
                {  
                    /**
                     *  Take ids of all languages
                     */
                    $all_lang_ids = $multi_lang_obj->get_all_translations($cart_item['product_id'], 'post_product');

                    if(!empty($all_lang_ids) && !empty($matching_ids = array_intersect($all_lang_ids, $bogo_product_ids)))
                    {
                        $item_id = (int) reset($matching_ids); //take first item
                    }
                }
            }
        }

        return $item_id;
    }


    /**
     *  Get all giveaway product ids for cart operations.
     * 
     *  @since 2.0.7
     *  @param $post_id     int     Id of coupon
     *  @return $free_products     int[]     Array of giveaway product ids. Product ids will be updated to current language product ids if multi language plugin(WPML) is active
     */
    public static function get_giveaway_products($post_id)
    {
        $free_products = parent::get_giveaway_products($post_id);
        $free_products_original = $free_products; //assumes main language product id

        $multi_lang_obj = Wt_Smart_Coupon_Mulitlanguage::get_instance();

        if($multi_lang_obj->is_multilanguage_plugin_active())
        {
            $out = array();

            foreach($free_products as $product_id)
            {
                /**
                 *  Take id of product in the current language.
                 * 
                 *  @param  $product_id         int     Id of product
                 *  @param  post type           string  Post type
                 *  @param  Return original     bool    Return original if no translation found in the current language. Default: false
                 * 
                 */
                $out[] = apply_filters('wpml_object_id', $product_id, 'product', TRUE);
            }
            
            $free_products = $out;
        }

        /**
         *  Alter BOGO product ids for cart (Only applicable for frontend functionalities)
         * 
         *  @param  $free_products              int[]       Array of giveaway product ids. Product ids of this array was converted to current language ids if any multi lang plugin(WPML) exists.
         *  @param  $post_id                    int         Id of coupon
         *  @param  $free_products_original     int[]       Array of giveaway product ids. Here the product ids are the ids configured by admin from backend.
         * 
         */
        return apply_filters('wt_sc_alter_bogo_giveaway_product_ids_for_cart', $free_products, $post_id, $free_products_original);
    }


    /**
     *  Get all giveaway products and its data for cart operations.
     * 
     *  @since 2.0.7
     *  @param $post_id     int     Id of coupon
     *  @return $bogo_products     array     Associative array of giveaway products and its data. Product ids will be updated to current language product ids if multi language plugin(WPML) is active
     */
    public static function get_all_bogo_giveaway_products($post_id)
    {
        $bogo_products = parent::get_all_bogo_giveaway_products($post_id);
        $bogo_products_original = $bogo_products; //assumes main language product id

        $multi_lang_obj = Wt_Smart_Coupon_Mulitlanguage::get_instance();

        if($multi_lang_obj->is_multilanguage_plugin_active())
        {
            $out = array();

            foreach($bogo_products as $product_id => $product_data)
            {
                /**
                 *  Take id of product in the current language.
                 * 
                 *  @param  $product_id         int     Id of product
                 *  @param  post type           string  Post type
                 *  @param  Return original     bool    Return original if no translation found in the current language. Default: false
                 * 
                 */
                $product_id = apply_filters('wpml_object_id', $product_id, 'product', TRUE);

                $out[$product_id] = $product_data;
            }
            
            $bogo_products = $out;
        }

        /**
         *  Alter BOGO products data for cart (Only applicable for frontend functionalities)
         * 
         *  @param  $bogo_products              array       An associative array of giveaway products and its giveaway data. Product ids of this array was converted to current language ids if any multi lang plugin(WPML) exists.
         *  @param  $post_id                    int         Id of coupon
         *  @param  $bogo_products_original     array       An associative array of giveaway products and its giveaway data. Here the product ids are the ids configured by admin from backend.
         * 
         */
        return apply_filters('wt_sc_alter_bogo_giveaway_products_for_cart', $bogo_products, $post_id, $bogo_products_original);
    }

    
    /**
     *  Get cart categories for the coupon.
     *  Applicable for `any_product_from_category_in_the_cart`
     * 
     *  Return array structure: array(
     *      category_id => array(
     *          'qty'       => (int) Total item quantity under category,
     *          'frequency' => (int) Total eligible frequency
     *          'giveaway_qty' => (int) Total available quantity
     *          'cart_giveaway_qty' => (int) Total giveaway quantity in the cart
     *      )
     *  )
     * 
     *  @since  2.0.8
     *  @param  string  $coupon_code    Coupon code 
     *  @param  int     $coupon_id      Coupon id
     *  @param  bool    $skip_availed   Skip already availed category (Optional). Default: true
     *  @return array   Array of category data
     */
    public function get_cart_item_categories_for_coupon($coupon_code, $coupon_id, $skip_availed = true)
    {
        $out  = array();
        $cart = WC()->cart;

        if(is_null($cart))
        {
            return $out;
        }

        $coupon = new WC_Coupon($coupon_code);

        $matching_items = self::get_matching_items($coupon->get_code());
        $cart_items = (array) $cart->get_cart();
        $coupon_categories = (array) $coupon->get_product_categories();
        sort($coupon_categories, SORT_NUMERIC); //sort ascending. This is for, easy `prod_cat_slug` comparison
        $is_category_restriction_enabled = !empty($coupon_categories);
        $cat_qty_arr = array();
        $normal_item_arr = array();

        /**
         *  Is separate pool required for categories in combination with outside categories
         * 
         *  @see  is_separate_pool_for_outside_cat() Documentation
         */       
        $separate_pool_for_outside_cat = $this->is_separate_pool_for_outside_cat();      
        
        foreach($matching_items as $cart_item_key => $is_matching)
        {
            if($is_matching && isset($cart_items[$cart_item_key]))
            {
                $cart_item = $cart_items[$cart_item_key];

                $prod_cat_slug = $this->prepare_product_cat_slug($coupon_categories, $is_category_restriction_enabled, $separate_pool_for_outside_cat, $cart_item['product_id']);

                if(isset($cat_qty_arr[$prod_cat_slug]))
                {
                    $cat_qty_arr[$prod_cat_slug] += $cart_item['quantity'];
                }else
                {
                    $cat_qty_arr[$prod_cat_slug] = $cart_item['quantity'];
                    $normal_item_arr[$prod_cat_slug] = array();
                }

                $normal_item_arr[$prod_cat_slug][$cart_item_key] = $cart_item;
            }
        }
        
        
        /**
         *  Prepare min quantity
         * 
         */
        $min_qty = 1;

        if($is_category_restriction_enabled && !empty($categories_data = self::get_categories_data($coupon)) ) /* coupon restriction category data */
        {
            foreach($categories_data as $category_data)
            {
                $min_qty = max(1, $category_data['min']);
                break; //just need the first value. Currently not allowing different values for categories
            }
        }
        else if( 0 < ( $coupon_min_qty = $this->get_coupon_meta_value( $coupon_id, '_wt_min_cat_qty' ) ) ){ //If category restricted is empty, but added 'Minimum quantity for each category'.
            $min_qty = max( 1, $coupon_min_qty );
        }
         
        
        /**
         *  Prepare eligible frequency
         *  Total giveaway quantity
         */
        $single_eligibility_qty = $this->get_non_individual_discount_quantity($coupon_id);
        $wt_sc_bogo_apply_frequency = $this->get_coupon_meta_value($coupon_id, '_wt_sc_bogo_apply_frequency');

        foreach($cat_qty_arr as $prod_cat_slug => $quantity)
        {
            $frequency = floor($quantity / $min_qty); //eligible frequency
            $frequency = ('repeat' === $wt_sc_bogo_apply_frequency ? $frequency : min($frequency, 1)); //Only one or zero for apply once

            $out[$prod_cat_slug] = array(
                'qty'       => $quantity,
                'frequency' => $frequency,
                'giveaway_qty' => (int) ($frequency * $single_eligibility_qty), //total giveaway quantity eligible
                'cart_giveaway_qty' => 0,
                'normal_item_arr' => $normal_item_arr[$prod_cat_slug],
                'giveaway_item_arr' => array(),
            );
        } 


        /**
         *  Prepare quantity of existing giveaway in the cart
         */
        foreach($cart_items as $cart_item_key => $cart_item)
        {
            if(self::is_a_free_item($cart_item, $coupon_code) && isset($cart_item['free_category']) && isset($out[$cart_item['free_category']]) )
            {
               $out[$cart_item['free_category']]['cart_giveaway_qty'] += $cart_item['quantity'];
               $out[$cart_item['free_category']]['giveaway_item_arr'][$cart_item_key] = $cart_item;
            }
        }


        /**
         *  Skip already availed categories
         * 
         */
        if($skip_availed)
        {
            foreach($out as $prod_cat_slug => $out_data)
            { 
                if($out_data['giveaway_qty'] === $out_data['cart_giveaway_qty'])
                {
                    unset($out[$prod_cat_slug]);
                }
            } 
        }
        
        return $out;
    }


    /**
     *  Get the cart products that are matched the coupon eligibility criteria
     * 
     *  @since  2.0.8
     *  @param  string  $coupon_code    Coupon code
     *  @return array   An associative array with cart item key as array key and `is matched product` boolean value and array value
     *                  array(
     *                      'cart_item_key_1' => true, //matched product
     *                      'cart_item_key_2' => false, //not a matched product
     *                  )     
     */
    public static function get_matching_items($coupon_code)
    {
        if(Wt_Smart_Coupon_Public::module_exists('coupon_restriction'))
        {
            return (array) (isset(Wt_Smart_Coupon_Restriction_Public::$matching_items[$coupon_code]) ? Wt_Smart_Coupon_Restriction_Public::$matching_items[$coupon_code] : array());
        }else
        {
            return array();
        }
    }


    /**
     *  Category restriction data. Includes min/max quantity
     *       
     * 
     *  @since  2.0.8
     *  @param  WC_Coupon   $coupon     Coupon object
     *  @return array       Category data array
     *                      array(
     *                          [(int) category_id] => 
     *                              array(
     *                                  'min' => (int) minimum quantity, 
     *                                  'max' => (int) maximum quantity,
     *                              )
     *                        )
     */
    public static function get_categories_data($coupon)
    {
        if(Wt_Smart_Coupon_Public::module_exists('coupon_restriction'))
        {
            return (array) Wt_Smart_Coupon_Restriction_Public::get_categories_data($coupon);
        }else
        {
            return array();
        }
    }
    

    /**
     *  Prepare product category slug
     *  This is applicable for any_product_from_category_in_the_cart
     * 
     *  @since  2.0.8
     *  @param  array   $coupon_categories                  product category ids for current coupon
     *  @param  bool    $is_category_restriction_enabled    Is product category restrictions available for the coupon
     *  @param  bool    $separate_pool_for_outside_cat      Is separate pool required for categories in combination with outside categories
     *  @param  int     $product_id                         Product id
     *  @return string      Product category slug (Category ids separated by comma)
     */
    private function prepare_product_cat_slug($coupon_categories, $is_category_restriction_enabled, $separate_pool_for_outside_cat, $product_id)
    {
        $product_cats = (array) Wt_Smart_Coupon_Common::get_product_cat_ids($product_id);                           

        if($is_category_restriction_enabled && !$separate_pool_for_outside_cat) /* category restriction and merged pool */
        {
            /** 
             *  Skip categories outside restriction
             *  The output will be sorted in ascending order because the first argument($coupon_categories) is already sorted by ascending order.
             */
            $inside_cat_arr = array_intersect($coupon_categories, $product_cats);
            $prod_cat_slug = implode(",", $inside_cat_arr); 

        }else
        {
            sort($product_cats, SORT_NUMERIC);  //sort ascending. This is for easy `prod_cat_slug` comparison
            $prod_cat_slug = implode(",", $product_cats);
        }

        return $prod_cat_slug;
    }

  
    /**
     *  This is applicable for `any_product_from_category_in_the_cart`
     *  Using when category restricitions are enabled.
     *  Cateogory combination with outside categories are considered as separate pool.
     * 
     *  Eg:
     *  
     *  Category restriction:
     *      Cat A, Cat C
     * 
     *  Cart
     *      Pro A (Cat C)
     *      Pro B (Cat C, Cat D)
     * 
     *  If separate pool:
     *      [Cat C]         - 1
     *      [Cat C, Cat D]  - 1
     * 
     *  If merged pool:
     *      [Cat C]         - 2  
     * 
     *  @since  2.0.8
     *  @return bool
     */
    private function is_separate_pool_for_outside_cat()
    {
        return apply_filters('wt_sc_prepare_separate_pool_for_outside_categories', true);  
    }


    /**
     *  Get WC_Cart object
     *  
     *  @since 2.0.8
     *  @return null|WC_Cart 
     */
    public static function get_cart_object()
    {
        if( Wt_Smart_Coupon_Public::is_admin() )
        {
            return null;
        }

        return ((is_object(WC()) && isset(WC()->cart)) ? WC()->cart : null);
    }


    /**
     *  This function will trigger BOGO fully availed saving function with dummy values
     *  
     *  @since 2.0.8
     *  @param int          $coupon_id          Id of coupon
     *  @param string       $coupon_code        Coupon code
     *  @param bool         $fully_availed      BOGO fully availed or not
     */
    private function trigger_dummy_bogo_fully_availed( $coupon_id, $coupon_code, $fully_availed )
    {
        /* preparing dummy values to trigger the below function */
        $dummy_max_qty_allowed = 2;
        $dummy_total_qty_in_cart = ( $fully_availed ? 2 : 1 );

        /* check and set is bogo fully availed or not */
        self::set_bogo_fully_availed( $coupon_id, $coupon_code, $dummy_max_qty_allowed, $dummy_total_qty_in_cart );
    }

    
    
    /**
     *  Convert cheapest item as giveaway if the coupon giveaway option is `any_product_from_category_in_the_cart`
     * 
     *  @since 2.0.8
     *  @param $coupon          WC_Coupon   WC_Coupon object
     *  @param $coupon_id       int         Coupon id
     *  @param $coupon_code     string      Coupon code  
     */
    private function apply_cheapest_giveaway_for_any_product_from_category_in_the_cart($coupon, $coupon_id, $coupon_code)
    {
        
        $bogo_free_categories = $this->get_cart_item_categories_for_coupon($coupon_code, $coupon_id, false);
       
        foreach($bogo_free_categories as $prod_cat_slug => $cat_data)
        {
            if(0 === $cat_data['cart_giveaway_qty'])
            {
                continue; //no giveaway under the category so skip
            }

            $temp_arr   = array(); //temp cart items array

            //for sorting purpose
            $price_arr  = array();
            $coupon_arr = array();

            $cart_items = array_merge($cat_data['giveaway_item_arr'], $cat_data['normal_item_arr']); //cart items under current category group

            /**
             *  Prepare cart item list under the current category (for sorting)
             */
            foreach($cart_items as $cart_item_key => $cart_item)
            {
                $item_id = ($cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id']);
                $product = wc_get_product($item_id);
                $product_price = self::get_product_price($product);

                $cart_item['wt_price']      = $product_price;
                $temp_arr[$cart_item_key]   = $cart_item;
                $price_arr[]                = $product_price;
                $coupon_arr[]               = (isset($cart_item['free_gift_coupon']) ? $cart_item['free_gift_coupon'] : ''); 
            }


            /**
             *  Check and convert as giveaway or normal items
             */
            if(!empty($temp_arr)) //items present in the current category
            {
                //sort the item by price descending first then reverse the array.
                array_multisort($price_arr, SORT_DESC, SORT_REGULAR, $temp_arr, $coupon_arr);              
                $temp_arr = array_reverse($temp_arr);

                $cat_data = $bogo_free_categories[$prod_cat_slug];
                $cart_giveaway_qty = $cat_data['cart_giveaway_qty'];
                
                //loop through the sorted cart items
                foreach($temp_arr as $cart_item_key => $cart_item)
                {
                    if(self::is_a_free_item($cart_item))
                    {
                        if(0 === $cart_giveaway_qty)
                        {
                            //convert as normal item
                            $this->convert_giveaway_cartitem_as_normal_cartitem($cart_item_key);
                        }else
                        {
                            //deduct eligibility
                            $to_deduct = $cart_item['quantity']; 

                            if($cart_item['quantity'] > $cart_giveaway_qty)
                            {
                                $to_deduct = $cart_giveaway_qty;

                                //the specified quantity will convert as normal item
                                $this->convert_giveaway_cartitem_as_normal_cartitem($cart_item_key, ($cart_item['quantity'] - $cart_giveaway_qty));
                            }

                            $cart_giveaway_qty -= $to_deduct;
                        } 
                    }else
                    {
                        if(0 === $cart_giveaway_qty)
                        {
                            continue;  
                        }else
                        {
                            $item_id = ($cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id']);

                            $giveaway_qty = min($cart_item['quantity'], $cart_giveaway_qty);
                            $new_qty = $cart_item['quantity'] - $giveaway_qty; //new quantity for existing cart item

                            $this->update_cart_qty($cart_item_key, $new_qty); //update the quantity or remove the item (if quantity is zero)
                            $this->add_item_to_cart($item_id, $giveaway_qty, $coupon_code, $prod_cat_slug); //add the quantity as giveaway

                            $cart_giveaway_qty -= $giveaway_qty;
                        }
                    }  
                }
            }          
        }
    }


    /**
     *  Is automatically add giveaway products to cart.
     *  Applicable for `specific_product` and `same_product_in_the_cart`.
     * 
     *  @since  2.1.0
     *  @since  2.2.0       Return added, Disabled reason is storing in `self::$giveaway_auto_add_disabled_reason` variable.
     *  @param  int         $coupon_id              Id of coupon
     *  @param  string      $coupon_code            Coupon code
     *  @param  int[]       $free_products          Available free product ids
     *  @param  string      $bogo_customer_gets     BOGO customer gets
     *  @return bool        Is auto add or not. 
     */
    private function is_auto_add_giveaway($coupon_id, $coupon_code, $free_products, $bogo_customer_gets)
    {
        self::$giveaway_auto_add_disabled_reason = 0; // Reset the value
        
        /**
         *  Only applicable for `specific_product` and `same_product_in_the_cart`
         */
        if('specific_product' !== $bogo_customer_gets && 'same_product_in_the_cart' !== $bogo_customer_gets)
        {
            self::$giveaway_auto_add_disabled_reason = 1; // Customer gets not applicable
            return false;
        }

        $same_product_in_cart_allowed_product_types = array();

        if('same_product_in_the_cart' === $bogo_customer_gets)
        {
            $total_qty=self::get_total_coupon_cart_item_qty($coupon_code); //total cart giveaway quantity for the coupon
            $total_qty=(is_array($total_qty) && !empty($total_qty) ? array_sum($total_qty) : 0);

            $total_products = count($free_products); //this not the sum of all cart items, this is just number or cart items.
            $giveaway_qty = $this->prepare_balance_quantity_for_same_product_in_cart($coupon_id, $coupon_code);

            if(0 === $total_products || 0 === $giveaway_qty || 0 < ($giveaway_qty % $total_products)) //no giveaway products OR no quantity remaining OR reminder exists. So we are unable to auto add the products
            {
                self::$giveaway_auto_add_disabled_reason = 2;
                return false;
            }

            /**
             *  Alter the product types for auto add. Applicable for `same_product_in_the_cart`.
             *  
             *  @since 2.1.0
             *  @param string[]     Product types. Default: array(`simple`)
             *  @param string       Coupon code
             */
            $same_product_in_cart_allowed_product_types = ( array ) apply_filters( 'wt_sc_alter_same_product_in_cart_allowed_product_types_for_auto_add', array( 'simple', 'variation' ), $coupon_code );
        }
        

        /**
         *  Specific product and `or` condition and more than 1 giveaway
         */
        $bogo_product_condition = $this->get_coupon_meta_value($coupon_id, '_wt_sc_bogo_product_condition');

        if('specific_product' === $bogo_customer_gets && 'and' !== $bogo_product_condition && 1 < count($free_products))
        {
            self::$giveaway_auto_add_disabled_reason = 3;
            return false;
        }

        foreach($free_products as $free_product_id)
        {
            $free_product = wc_get_product($free_product_id);

            if( ! $this->is_purchasable($free_product))
            {
                self::$giveaway_auto_add_disabled_reason = 4;
                return false;
            }

            /**
             *  `specific_product` BOGO
             */
            if('specific_product' === $bogo_customer_gets && 'variable' === $free_product->get_type())
            {
                self::$giveaway_auto_add_disabled_reason = 5; // Variable product in the giveaway products. `specific_product`
                return false;
            }

            /**
             *  Variation product in `same_product_in_the_cart` BOGO
             */
            if('same_product_in_the_cart' === $bogo_customer_gets && !in_array($free_product->get_type(), $same_product_in_cart_allowed_product_types)) 
            {
                self::$giveaway_auto_add_disabled_reason = 6;
                return false;
            }

            /**
             *  Variation product in `specific_product` non BOGO without attributes
             */
            if( 'specific_product' === $bogo_customer_gets && 'variation' === $free_product->get_type() )
            {
                foreach( $free_product->get_variation_attributes() as $attribute_name => $options ){
                    if( '' === $options ){
                        self::$giveaway_auto_add_disabled_reason = 7;
                        return false;
                    }
                }
            }
        }

        return true;
    }


    /**
     *  Giveaway eligible product list for `same_product_in_the_cart`
     * 
     *  @since  2.1.0
     *  @param  int         $coupon_id      Id of coupon
     *  @param  string      $coupon_code    Coupon code
     *  @return array       Associative array of product giveaway data. Product id as array key.
     *                      Return an empty array when all giveaways are in the cart
     */
    private function get_coupon_product_list_for_any_product_from_cart($coupon_id, $coupon_code)
    {
        $qty_price_data = $this->get_qty_price_data($coupon_id);
        $coupon_products = array();

        if(0 < $this->prepare_balance_quantity_for_same_product_in_cart($coupon_id, $coupon_code))
        {
            if($this->is_product_category_restriction_enabled($coupon_id))
            {
                $coupon = new WC_Coupon($coupon_code);

                /* this function will prepare product list based product/category restriction */
                $coupon_products = $this->prepare_product_list_for_any_product_from_cart($coupon, $qty_price_data);

                if(empty($coupon_products))
                {
                    //no products in the coupon restriction section, so use entire cart items
                    $coupon_products = $this->prepare_cart_items_as_giveaway($qty_price_data);
                }
            }else
            {
                $coupon_products = $this->prepare_cart_items_as_giveaway($qty_price_data); //Show all products in the cart as giveaway items
            }
        }

        return $coupon_products;
    }

    
    /**
     *  Get quantity and price data for BOGO coupon
     * 
     *  @since  2.1.0
     *  @param  int         $coupon_id      Id of coupon
     *  @return array       Array of giveaway data  
     */
    private function get_qty_price_data($coupon_id)
    {
        return array(
            'qty'        => $this->get_non_individual_discount_quantity($coupon_id), 
            'price'      => $this->get_coupon_meta_value($coupon_id, '_wt_product_discount_amount'), 
            'price_type' => $this->get_coupon_meta_value($coupon_id, '_wt_product_discount_type'),
        );
    }


    /**
     *  Auto giveaway adding for `same_product_in_the_cart`
     * 
     *  @since  2.1.0
     *  @param  int         $coupon_id      Id of coupon
     *  @param  string      $coupon_code    Coupon code  
     */
    private function same_product_auto_add_to_cart($coupon_id, $coupon_code)
    {
        $free_products  = $this->get_coupon_product_list_for_any_product_from_cart($coupon_id, $coupon_code);

        if(!$this->is_auto_add_giveaway($coupon_id, $coupon_code, array_keys($free_products), 'same_product_in_the_cart'))
        {
            return;
        }

        $total_products = count($free_products); //this not the sum of all cart items, this is just number or cart items.
        $giveaway_qty   = $this->prepare_balance_quantity_for_same_product_in_cart($coupon_id, $coupon_code);  
        $each_qty = ($giveaway_qty / $total_products); //giveaway quantity for each cart item

        foreach($free_products as $free_product_id => $giveaway_data)
        {
            $args['variation_attributes'] = isset( $giveaway_data['attributes'] ) ? $giveaway_data['attributes'] : array() ;
            $this->add_item_to_cart( $free_product_id, $each_qty, $coupon_code, '', $args ); //add current item as giveaway
        }
    }


    /**
     *  Get cart categories data for the coupon.
     *  Applicable for `any_product_from_category`
     * 
     *  Return array structure: array(
     *      category_id => array(
     *          'cart_qty'  => (int) Total giveaway quantity in the cart,
     *          'frequency' => (int) Total frequency in the cart,
     *          'remaining' => (int) Remaining quantity to fill the current quantity,
     *          'cat_qty'   => (int) Quantity assigned by admin to the category
     *      )
     *  )
     * 
     *  @since  2.1.1
     *  @param  string  $coupon_code                Coupon code 
     *  @param  array   $bogo_free_categories       BOGO category data of coupon
     *  @param  int     $total_frequency_in_cart    Total frequency in cart. (Reference variable. Value will be zero)
     *  @param  int     $total_remain_qty           Total remain quantity to complete the frequency. (Reference variable. Value will be zero)
     *  @return array   Array of category data
     */
    public function get_category_data_for_coupon( $coupon_code, $bogo_free_categories, &$total_frequency_in_cart, &$total_remain_qty ) {

        $cart_items   = WC()->cart->get_cart();
        $cat_qty_arr  = array(); // Cart giveaways
        $cat_data_arr = array();

        // Preparing cart giveaway quantity
        foreach ( $cart_items as $item_key => $cart_item ) {

            if ( self::is_a_free_item( $cart_item, $coupon_code ) ) { // Free item of current coupon
                
                if ( ! isset( $bogo_free_categories[ $cart_item['free_category'] ] ) ) {

                    WC()->cart->remove_cart_item($item_key); // The current giveaway category not belongs to the coupon settings
                    continue;
                }

                if ( isset( $cat_qty_arr[ $cart_item['free_category'] ] ) ) {                               
                    
                    $cat_qty_arr[ $cart_item['free_category'] ] += $cart_item['quantity'];
                } else {   
                    
                    $cat_qty_arr[ $cart_item['free_category'] ] = $cart_item['quantity'];
                }
            }
        }


        foreach ( $bogo_free_categories as $cat_id => $cat_data ) {

            $qty     = max( 1, absint( $cat_data['qty'] ) ); // Quantity set for the category by admin                     
            $cat_qty = isset( $cat_qty_arr[ $cat_id ] ) ? $cat_qty_arr[ $cat_id ] : 0; // Giveaway quantity under the current category in the cart.
            $cat_frq = ( 0 < $cat_qty ? ceil( $cat_qty / $qty ) : 0 ); // Frequency hold by the current category in the cart as giveaway
            $remain  = max( 0, ( ( $cat_frq * $qty ) - $cat_qty ) ); // The remaining quantity required to complete the current frequency

            $cat_data_arr[ $cat_id ] = array(
                'cart_qty'  => (int) $cat_qty,
                'frequency' => (int) $cat_frq,
                'remaining' => (int) $remain,
                'cat_qty'   => (int) $qty, // Quantity assigned by admin to the category
            );

            $total_frequency_in_cart += $cat_frq;
            $total_remain_qty += $remain;
        }

        return $cat_data_arr;
    }


    /**
     *  Get proccessed category list for Giveaway eligible message. 
     *  This function will remove categories without giveaway when all frequencies are availed
     *  Applicable for `any_product_from_category`
     * 
     *  @since  2.1.1
     *  @param  string      $coupon_code    Coupon code
     *  @param  int         $coupon_id      Coupon id
     *  @return array       Coupon data array   
     */
    public function get_processed_category_list_for_bogo_eligible_msg( $coupon_code, $coupon_id ) {

        $total_frequency_in_cart = 0; // Reference argument to the below function
        $total_remain_qty = 0; // Reference argument to the below function
        $bogo_free_categories = $this->get_coupon_meta_value( $coupon_id, '_wt_sc_bogo_free_categories' );
        $cat_data_arr = $this->get_category_data_for_coupon( $coupon_code, $bogo_free_categories, $total_frequency_in_cart, $total_remain_qty );
        $coupon = new WC_Coupon( $coupon_code );

        // In `any` condition, the calculation is not category specific.
        if ( 'any' === $this->get_any_product_from_category_condition( $coupon_code ) ) {
            return $cat_data_arr;
        }

        $frequency = $this->get_coupon_applicable_count( $coupon_id, $coupon_code ); // Total eligible frequency

        if ( (int) $total_frequency_in_cart === (int) $frequency ) { // All frequency availed, So remove categories with no giveaways.

            foreach ( $cat_data_arr as $cat_id => $cat_data ) {
                if ( 0 === (int) $cat_data['cart_qty'] ) {
                    unset( $cat_data_arr[ $cat_id ] );
                }   
            }
        }

        return $cat_data_arr;
    }


    /**
     *  Take the giveaway item id based on BOGO configuration.
     *  
     *  @since  2.1.1
     *  @param  array       $cart_item          Cart item array
     *  @param  array       $bogo_products      BOGO product array
     *  @return int         Item id, variation id when variation is configured as BOGO product, otherwise product id
     */
    private function prepare_item_id_for_specific_product_bogo( $cart_item, $bogo_products )
    {
        $item_id = 0;
                
        if ( 0 < $cart_item['variation_id'] && isset( $bogo_products[ $cart_item['variation_id'] ] ) )
        {
            $item_id = $cart_item['variation_id'];

        }elseif (isset( $bogo_products[ $cart_item['product_id'] ] ))
        {
            $item_id = $cart_item['product_id'];
        }

        return $item_id;
    }

    /**
     *  To work BOGO coupons in checkout page
     *  
     *  @since  2.2.0
     *  @param  string  $coupon_code    Coupon code
     */
    public function add_giveaway_in_checkout($coupon_code){
        
        if( wc_get_checkout_url() === wp_get_referer() ){
            $this->add_giveaway_products_with_coupon();
            $this->convert_cheapest_as_giveaway();
            $this->show_giveaway_eligible_message();
        }
    }


    /**
     *  Check and convert existing cart item into giveaway for `specific_product` giveaway
     * 
     *  @since  2.2.0
     *  @param  int         $item_id            Product/variation id
     *  @param  int         $giveaway_qty       Giveaway quantity
     *  @param  string      $coupon_code        Coupon code
     *  @param  int         $coupon_id          Coupon ID
     *  @return bool        true when giveaway added
     */
    public function check_convert_existing_to_giveaway_specific_product( $item_id, $giveaway_qty, $coupon_code, $coupon_id ) {
        
        $cart_items = WC()->cart->get_cart();

        if ( empty( $cart_items ) ) { // Cart is empty so return.
            return false;
        }

        if( 1 === array_sum( array_column( $cart_items, 'quantity' ) ) ) { // Only one quantity in the cart. So no need to check for existing conversion    
            // Add new items
            return $this->add_item_to_cart( $item_id, $giveaway_qty, $coupon_code );
        }


        if ( true === wc_string_to_bool( self::get_coupon_meta_value( $coupon_id, '_wt_sc_convert_existing_as_giveaway' ) ) ) { 
    
            $frequency = $this->get_coupon_applicable_count( $coupon_id, $coupon_code );
            $coupon = new WC_Coupon( $coupon_code );
            $giveaway_eligible_products = array();
            $current_item_id = 0;
            $giveaway_added = false;

            // Check the item was available in the cart            
            foreach( $cart_items as $cart_item_key => $cart_item ) {
                
                // All quantity converted to giveaway
                if ( 0 >= $giveaway_qty ) {
                    break; 
                }

                // A giveaway cart item, so skip
                if ( self::is_a_free_item( $cart_item ) ) { 
                    continue;
                }

                // Match with product id or variation id
                if ( (int) $cart_item['product_id'] !== (int) $item_id && (int) $cart_item['variation_id'] !== (int) $item_id ) {
                    continue;
                }

                $current_item_id = ( 0 < $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'] );
                $args['variation_attributes'] = isset( $cart_item['variation'] ) ? $cart_item['variation'] : array() ;

                // Loop through the cart item quantity
                for ( $y = 1; $y <= $cart_item['quantity']; $y++ ) { 

                    // Deduct one quantity from the item.
                    $new_qty = $cart_item['quantity'] - $y; 
                    $this->update_cart_qty( $cart_item_key, $new_qty );

                    // Coupon eligibility gone, or eligibility count reduced.
                    if ( ! $coupon->is_valid() || $frequency > $this->get_coupon_applicable_count( $coupon_id, $coupon_code ) ) { 
                        
                        // To inform not check again for giveaway
                        WC()->session->set( self::$break_add_to_cart_loop_session_id, 1 ); 
                        
                        if ( 0 === $new_qty ) {
                            $this->set_as_normal_cartitem( $cart_item['product_id'], $cart_item['variation_id'], 1, $args );
                        } else {
                           $this->update_cart_qty( $cart_item_key, ( $new_qty + 1 ) ); 
                        }

                        $y = $y - 1;
                        break;

                    } else {

                        // Quantity to convert as giveaway reached.
                        if ( (int) $giveaway_qty === $y ) { 
                            break;
                        }
                    }
                }

                // Got some quantity to convert as giveaway
                if ( 0 < $y ) { 
                    $giveaway_added = $this->add_item_to_cart( $current_item_id, $y, $coupon_code, '', $args );
                    $giveaway_qty -= $y;
                }
            }

            if ( 0 < $giveaway_qty ) { // Not all the required quantity got from the existing products. So add new items
                $giveaway_added = $this->add_item_to_cart( ( 0 < $current_item_id ? $current_item_id : $item_id ), $giveaway_qty, $coupon_code );
            }

            return $giveaway_added;
            
        } else {

            // Add new items
            return $this->add_item_to_cart( $item_id, $giveaway_qty, $coupon_code );
        }   
    }

    

    /** 
     *  Add block to the block list
     *  
     *  @since  2.3.0
     *  @param  array       $registered_blocks      Blocks data array
     *  @return array       $registered_blocks      Blocks data array
     */
    public function register_blocks( $registered_blocks ) {

        $registered_blocks['giveaway_product'] = array(
            'block_dir' => 'giveaway-product',
            'script_handles' => array( 'frontend-js' ),
            'post_fields' => array( 'cartitem_giveaway_text' => '', 'giveaway_eligible_message' => '', 'giveaway_products_html' => '' ),
        );

        return $registered_blocks;
    }



    /**
     *  Get giveaway description text for cart item.
     *  
     *  @since  2.3.0
     *  @param  array       $cart_item   Cart item array
     *  @return string      Giveaway description text for giveaway cart item otherwise empty string
     */
    public function get_cart_lineitem_giveaway_text( $cart_item ) {

        $product_id     = isset( $cart_item['product_id'] ) ? absint( $cart_item['product_id'] ) : 0;
        $variation_id   = isset( $cart_item['variation_id'] ) ? absint( $cart_item['variation_id'] ) : 0;

        if ( self::is_a_free_item( $cart_item ) ) {
            $coupon_code    = wc_format_coupon_code( $cart_item['free_gift_coupon'] );
            $item_id        = ( $variation_id > 0 ? $variation_id : $product_id );
            $product        = wc_get_product( $item_id );

            $giveaway_data = $this->get_product_giveaway_data( $item_id, $coupon_code, $cart_item );

            if ( $this->is_full_free_item( $product, $giveaway_data ) ) {
                $free_gift_text = $this->get_customized_text('full_free_giveaway_cart_item', array('coupon_code' => $coupon_code));
            } else {
                $discount_text  = $this->get_give_away_discount_text(0, $giveaway_data); /* set coupon id as zero(first argument) because we have already fetched data */
                $free_gift_text = $this->get_customized_text( 'partialy_free_giveaway_cart_item', array( 'coupon_code' => $coupon_code, 'giveaway_product_discount' => $discount_text ) );
            }

            return apply_filters( 'wt_sc_alter_giveaway_cart_lineitem_text', '<p style="color:green;clear:both">' . $free_gift_text . '</p>', $cart_item );     
        }

        return '';
    }

    /**
     *  Add to wc checkout script data.
     *  Hooked into: wbte_sc_alter_blocks_data
     *  
     *  @since  2.3.0
     *  @param  array       $block_data         Params array
     *  @return array       $block_data         Params array
     */
    public function add_blocks_data( $block_data ) {

        $cart = self::get_cart_object();
            
        if ( ! is_null( $cart ) && ! $cart->is_empty() ) { 

            // Giveaway cart item text ================================
            $out = array();

            foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
                $info_text = $this->get_cart_lineitem_giveaway_text( $cart_item );
                
                if ( $info_text ) { // This is a free item
                    $out[ $cart_item_key ] =  wp_kses_post( $info_text );
                }
            }

            if ( ! empty( $out ) ) {
               $block_data['cartitem_giveaway_text'] = $out;
            }


            // Giveaway eligible message ================================
            $out = array();
            $this->add_giveaway_products_with_coupon(); // Prepare and store message if applicable.
            $coupons = $cart->get_applied_coupons();
            $coupons = (!is_array($coupons) ? array() : $coupons);           
            $bogo_eligible = self::get_bogo_eligible_session();
            
            /* Alter the message or set as empty to hide the message on current page */
            $bogo_eligible = apply_filters('wt_sc_alter_giveaway_eligible_message', $bogo_eligible);
            $bogo_eligible = (!is_array($bogo_eligible) ? array() : $bogo_eligible);
            
            foreach ( $bogo_eligible as $coupon_code => $message ) {
                if ( in_array( $coupon_code, $coupons ) ) {
                    if ( "" !== $message && $message !== self::$giveaway_fully_availed_flag ) {
                       $out[] = $message;
                    }
                } else {
                    self::remove_bogo_eligible_session( $coupon_code );
                }
            }

            if ( ! empty( $out ) ) {
               $block_data['giveaway_eligible_message'] = implode( "<br />", $out ); // Merge to single message 
            }


            // Giveaway products ================================
            $out = '';
            ob_start();
            $this->display_giveaway_products();
            $out = ob_get_clean();
            $block_data['giveaway_products_html'] = $out;
        }

        return $block_data;
    }

    /**
     *  Calculate the giveaway total discount.
     *  Method also returns: product price, sale price after discount, discount, and coupon id. 
     * 
     *  @since 2.3.0
     * 
     *  @param  array   $cart_item      Cart
     *  @param  bool    $is_total       When true the discount will be calculated for the total line item not for single quantity. Optional. Default: true
     *  @return array   An associative array including discount data and coupon id.
     */ 
    private function calculate_bogo_discount( $cart_item, $is_total = true ) {

        $out = array(
            'product_price'             => 0,
            'sale_price_after_discount' => 0,
            'discount'                  => 0,
            'coupon_id'                 => 0,
        );

        if ( self::is_a_free_item( $cart_item ) ) {
            
            $coupon_code    = wc_format_coupon_code( $cart_item['free_gift_coupon'] );
            $coupon_id      = wc_get_coupon_id_by_code( $coupon_code );
            $item_id        = ( $cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id'] );
            $product        = wc_get_product( $item_id );
            $product_price  = self::get_product_price( $product );
            $giveaway_data  = $this->get_product_giveaway_data( $item_id, $coupon_code, $cart_item );
            
            $discount = self::get_available_discount_for_giveaway_product( $product, $giveaway_data );
            $sale_price_after_discount = ( $product_price - $discount );

            if( $is_total ) {
                $sale_price_after_discount  = $sale_price_after_discount * $cart_item['quantity'];
                $product_price              = $product_price * $cart_item['quantity'];
                $discount                   = $discount * $cart_item['quantity'];

                if ( ! isset( self::$bogo_discounts[ $coupon_code ] ) ) {
                    self::$bogo_discounts[ $coupon_code ] = 0;
                }

                self::$bogo_discounts[ $coupon_code ] += $discount; 
            }

            $out = array(
                'product_price'             => $product_price,
                'sale_price_after_discount' => $sale_price_after_discount,
                'discount'                  => $discount,
                'coupon_id'                 => $coupon_id,
            );
        }

        return $out;
    }

    /**
     *  Check if the Undo product is applicable for the giveaway.
     *  When a product is removed from the cart, WooCommerce provides an option to undo that action. This function triggers when the product is recovered.
     *  Hooked into `woocommerce_cart_item_restored`
     * 
     *  @since 2.4.2
     * 
     *  @param  string     $cart_item_key      Cart item key of recovered product
     *  @param  WC_Cart    $cart               Cart object
     */ 
    public function check_undo_products_for_giveaway( $cart_item_key, $cart ){
        
        $cart_item_data = $cart->cart_contents[ $cart_item_key ];
        if( !empty( $cart_item_data ) ){
            $product_id   = $cart_item_data['product_id'];
            $qty          = $cart_item_data['quantity'];
            $variation_id = $cart_item_data['variation_id'];
            $variation    = $cart_item_data['variation'];

            $this->applicable_for_giveaway( $cart_item_key, $product_id, $qty, $variation_id, $variation, $cart_item_data );
        }
    }

    /**
	 * Update order total if BOGO coupon applied.
	 * Only applicable for BOGO coupons which disabled 'Apply tax only on discounted value' option.
	 * 
	 * @since 3.0.0
	 * @param bool   $taxes 	Having taxes or not.
	 * @param object $order 	Order object.
	 */
	public function update_order_total( $taxes, $order ){

		if ( is_cart() || is_checkout() ) { // To avoid executing these in cart and checkout pages.
			return;
		}
		$new_total = $order->get_total( 'edit' );
        $old_total = $new_total;
		$order_coupons = $order->get_coupons();
		if( !empty( $order_coupons ) ){
			foreach ( $order->get_items() as $item_id => $item ) {
				if( self::is_a_free_item( $item ) ){
					$coupon_code = $item['free_gift_coupon'];

					if ( ! empty( $coupon_code ) ) {
						$coupon_code = wc_format_coupon_code( $coupon_code );
						$coupon      = new WC_Coupon( $coupon_code );

						if ( $coupon ) {
							$coupon_id = $coupon->get_id();

                            if( $this->is_apply_discount_before_tax_enabled( $coupon_id ) )
                            {
                                return;
                            } 

							$item_id = $item['variation_id'] > 0 ? $item['variation_id'] : $item['product_id'];
							$product = wc_get_product( $item_id );

                            $giveaway_data = $this->get_product_giveaway_data( $item_id, $coupon_code, $item );

							$discount   = self::get_available_discount_for_giveaway_product( $product, $giveaway_data );
							$new_total -= ( $discount * $item['quantity'] );
						}
					}
				}
			}
            if( $new_total !== $old_total ){
                $new_total = round( $new_total, wc_get_price_decimals() );
                $order->set_total( $new_total );
            }
		}
	}
}
Wt_Smart_Coupon_Giveaway_Product_Public::get_instance();