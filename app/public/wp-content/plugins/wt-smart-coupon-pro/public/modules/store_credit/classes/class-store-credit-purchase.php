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
if(!class_exists('Wt_Smart_Coupon_Store_Credit_Public')) 
{
    return;
}

class Wt_Smart_Coupon_Store_Credit_Purchase extends Wt_Smart_Coupon_Store_Credit_Public
{
    private static $instance = null;
    public function __construct()
    {
        parent::init_vars();

        /**
         *  Setup the product
         */
        include_once $this->module_path.'classes/class-store-credit-purchase-set-product.php';
        Wt_Smart_Coupon_Store_Credit_Purchase_Setup_Product::get_instance();


        /**
         *  Setup the product page
         */
        include_once $this->module_path.'classes/class-store-credit-purchase-set-product-page.php';
        Wt_Smart_Coupon_Store_Credit_Purchase_Setup_Product_Page::get_instance();
         

        /**
         *  Process the cart
         */
        include_once $this->module_path.'classes/class-store-credit-purchase-process-cart.php';
        Wt_Smart_Coupon_Store_Credit_Purchase_Process_Cart::get_instance();

        
        /**
         *  Schedule email
         */
        include_once $this->module_path.'classes/class-store-credit-purchase-schedule-email.php';
        Wt_Smart_Coupon_Store_Credit_Purchase_Schedule_Email::get_instance();

    }

    /**
     * Get Instance
     * @since 2.0.0
     */
    public static function get_instance()
    {
        if(self::$instance==null)
        {
            self::$instance=new Wt_Smart_Coupon_Store_Credit_Purchase();
        }
        return self::$instance;
    }


    /**
     * Whether the given product is used for purchasing Store Credit
     * @since 2.0.1 [Bug fix] Store credit purchase page not working if there are some other products exists in the single product page.
     * @access  public
     * @param   int         $product_id
     * @return  boolean     
     */
    public function is_product_is_store_credit_purchase($product_id)
    {
        $this->is_store_credit_purchase_product=false;
        $store_credit_product =self::get_associated_product();
        if($store_credit_product=="")
        {
            return false;
        }

        $translated_products = Wt_Smart_Coupon_Mulitlanguage::get_instance()->get_all_translations($store_credit_product, "post_product");
        if($product_id == $store_credit_product || in_array($product_id, $translated_products))
        {
            $this->is_store_credit_purchase_product=true;
            $this->store_credit_purchase_product_id=$product_id;
        }

        return $this->is_store_credit_purchase_product;
    }


    /**
     * Sanitize price field
     * @access public
     * @return float
     */
    public function sanitize_price($price)
    {
        return filter_var(sanitize_text_field($price), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    /**
    * Check whether to disable or enable email restriction on store credits after the purchase
    * @access public
    * @return bool
    */
    public function store_credit_email_restriction()
    {
        return apply_filters('wt_smart_coupon_store_credit_email_restriction', true);
    }

    /**
     * Process denomination list
     * @access protected
     * @return array  $denominations
     * @param  string $denominations
     */
    protected function process_denomination_list($denominations)
    {
        $denominations=array_map('floatval', explode(',', $denominations));
        return apply_filters('wt_sc_alter_giftcard_predifined_amounts', array_unique(array_filter($denominations)));
    }

    /**
     * Get store credit purchase schedule date format
     * @access protected
     * @return string  date format
     */
    protected function get_schedule_date_format()
    {
        return apply_filters('wt_smart_coupon_store_credit_date_format', 'mm/dd/yy');
    }

    /**
     *  Get minimum maximum amount for gift card purchase
     * 
     *  @since 2.0.7
     *  @param array  $settings  settings array
     *  @param string  $type  min/max
     *  @return float  price value
     */
    public function get_giftcard_min_max_price($settings, $type = 'min')
    {
        $settings_key = ('min' === $type ? 'minimum_store_credit_purchase' : 'maximum_store_credit_purchase');
        $price = (float) isset($settings[$settings_key]) ? $settings[$settings_key] : 0;

        return (float) apply_filters('wt_sc_alter_giftcard_'.$type.'_value', $price);
    }
}