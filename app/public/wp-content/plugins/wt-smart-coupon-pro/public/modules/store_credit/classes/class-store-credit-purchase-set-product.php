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

class Wt_Smart_Coupon_Store_Credit_Purchase_Setup_Product extends Wt_Smart_Coupon_Store_Credit_Purchase
{
    private static $instance = null;
    public function __construct()
    {

        add_action( 'init', array( $this, 'init' ) );

        //Make the store credit product purchasable (Without setting any Price)
        add_filter('woocommerce_is_purchasable', array($this, 'make_product_purchasable'), 10, 2);
        
        //Set the user choosed/entered price as product price. Price will take from cart item data
        add_action('woocommerce_before_calculate_totals', array($this, 'make_price_dynamic'), 10, 1); 
    
    }

    /**
     * Initialize the store credit product settings.
     * 
     * @since 3.1.0 Moved from __construct to init to avoid issues with translating text before init.
     */
    public function init(){

        $enabled_customizing_store_credit = self::is_extended_store_credit_enabled();

        if( $enabled_customizing_store_credit )
        {
            //Remove quantity option for cart page
            add_filter( 'woocommerce_is_sold_individually', array( $this, 'remove_quantity_selection_for_gift_card' ), 10, 2 );
            
            //Make the store credit product virtual by default
            add_filter( 'woocommerce_is_virtual', array( $this, 'make_the_store_credit_product_virtual' ), 10, 2 );
        }
    }

    /**
     * Get Instance
     * @since 2.0.0
     */
    public static function get_instance()
    {
        if(self::$instance==null)
        {
            self::$instance=new Wt_Smart_Coupon_Store_Credit_Purchase_Setup_Product();
        }
        return self::$instance;
    }

    /**
     *  Make the store credit product purchasable (Without setting any Price)
     */
    public function make_product_purchasable($purchasable, $product)
    {
        if($this->is_product_is_store_credit_purchase($product->get_id()))
        {
            return true;
        }
        return $purchasable;
    }

    /**
     * Set the user choosed/entered price as product price.
     */
    public function make_price_dynamic($cart_obj)
    {
        if(is_admin())
        {
            return;
        }
        foreach ($cart_obj->get_cart() as $key => $item )
        {
            if(!isset($item['wt_credit_amount'])) /* not a store credit product */
            {
                continue;
            }
            $item['data']->set_price($item['wt_credit_amount']);
        }
    }

    /** 
     * Remove quantity option for cart page
     */
    public function remove_quantity_selection_for_gift_card($solid_individually, $product)
    {
        if(!$product || !$this->is_product_is_store_credit_purchase($product->get_id()))
        {
            return $solid_individually;
        }
        return apply_filters('wt_store_credit_product_is_sold_individually', true);
    }


    /**
     * Make the store credit product virtual by default
     */
    public function make_the_store_credit_product_virtual ($is_virtual, $product) {

        if( !$product  || ! $this->is_product_is_store_credit_purchase( $product->get_id() ) ) {
            
            return $is_virtual;
        }

        return apply_filters('wt_store_credit_product_is_virtual', true);
    }
}