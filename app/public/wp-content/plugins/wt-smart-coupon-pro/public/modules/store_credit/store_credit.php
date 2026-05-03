<?php
/**
 * Store credit public facing
 *
 * @link       
 * @since 2.0.0     
 *
 * @package  Wt_Smart_Coupon  
 */
if (!defined('ABSPATH')) {
    exit;
}
if(!class_exists('Wt_Smart_Coupon_Store_Credit')) /* common module class not found so return */
{
    return;
}
class Wt_Smart_Coupon_Store_Credit_Public extends Wt_Smart_Coupon_Store_Credit
{
    public $module_base='store_credit';
    public $module_id='';
    public $module_path='';
    public $module_url='';
    public static $module_id_static='';

    private static $instance = null;

    protected $discounts = array();
    protected $total_discounts=array();
    protected $is_store_credit_purchase_product=null;
    protected $store_credit_purchase_product_id=0;


    public function __construct()
    {
        $this->init_vars();

        /**
         *  Store credit purchase related functions
         */
        include_once $this->module_path.'classes/class-store-credit-purchase.php';
        Wt_Smart_Coupon_Store_Credit_Purchase::get_instance();
        
        
        /**
         *  Store credit applying(using) related functions
         */
        include_once $this->module_path.'classes/class-store-credit-apply.php';
        Wt_Smart_Coupon_Store_Credit_Apply::get_instance();

        /**
         *  Store credit on My account page
         */
        include_once $this->module_path.'classes/class-store-credit-display.php';
        Wt_Smart_Coupon_Store_Credit_Display::get_instance();


        /** 
         *  Add block to the block list
         *  
         *  @since 2.4.0
         */
        add_filter( 'wt_sc_blocks_register', array( $this, 'register_blocks' ) );      
    }

    /**
     * Get Instance
     * @since 2.0.0
     */
    public static function get_instance()
    {
        if(self::$instance==null)
        {
            self::$instance=new Wt_Smart_Coupon_Store_Credit_Public();
        }
        return self::$instance;
    }

    public function init_vars()
    {
        $this->module_id=Wt_Smart_Coupon::get_module_id($this->module_base);
        self::$module_id_static=$this->module_id;

        $this->module_path=plugin_dir_path( __FILE__ );
        $this->module_url=plugin_dir_url( __FILE__ ); 
    }

    /**
     *  Get product object on single product page. In some themes, '$product' is a string instead of an object.
     * 
     *  @since 2.1.2
     *  @return object|null     WC_Product object
     */
    public static function get_product_object() {

        global $product, $post;
        
        if ( is_product() ) {
            
            if( ! is_object( $product ) ) { // In some themes the $product object is not ready
                $product = wc_get_product($post->ID);
            }

            return $product;
        }

        return null;
    }

    
    /** 
     *  Add block to the block list
     *  
     *  @since  2.4.0
     *  @param  array       $registered_blocks      Blocks data array
     *  @return array       $registered_blocks      Blocks data array
     */
    public function register_blocks( $registered_blocks ) {

        $registered_blocks['store_credit'] = array(
            'block_dir' => 'store-credit',
            'script_handles' => array( 'frontend-js' ),
            'post_fields' => array( 'cartitem_giftcard_image' => '' ),
        );
        
        return $registered_blocks;
    }


    /**
     *  Get WC_Cart object
     *  
     *  @since  2.4.0
     *  @return null|WC_Cart 
     */
    public static function get_cart_object() {
        if ( is_admin() ) {
            return null;
        }

        return ( ( is_object( WC() ) && isset( WC()->cart ) ) ? WC()->cart : null );
    }
}
Wt_Smart_Coupon_Store_Credit_Public::get_instance();