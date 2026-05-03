<?php
/**
 * Gift coupon
 *
 * @link       
 * @since 2.0.1   
 *
 * @package  Wt_Smart_Coupon  
 */
if (!defined('ABSPATH')) {
    exit;
}

class Wt_Smart_Coupon_Gift_Coupon
{
    public $module_base='gift_coupon';
    public $module_id='';
    public static $module_id_static='';
    private static $instance = null;

    public function __construct()
    {
        $this->module_id=Wt_Smart_Coupon::get_module_id($this->module_base);
        self::$module_id_static=$this->module_id;

        add_action('woocommerce_email_classes', array($this, 'add_gift_coupon_emails'), 10, 1);
        
        add_action('wt_sc_on_order_cancelled', array($this, 'revoke_gift_coupon'), 10, 4);
        add_action('wt_sc_on_order_refunded', array($this, 'revoke_gift_coupon'), 10, 4);


        /**
         *  Add settings
         * 
         *  @since 2.3.0
         */
        add_filter( 'wt_sc_module_default_settings', array( $this, 'default_settings' ), 10, 2 );


        /**
         *  Set the coupon is activated or not
         * 
         *  @since 2.3.0
         */
        add_filter( 'wbte_sc_is_coupon_activated', array( $this, 'is_activated' ), 10, 2 );
    }

    /**
     * Get Instance
     */
    public static function get_instance()
    {
        if(self::$instance==null)
        {
            self::$instance=new Wt_Smart_Coupon_Gift_Coupon();
        }
        return self::$instance;
    }

    /**
     * Include Gift coupon email class
     */ 
    public function add_gift_coupon_emails($email_classes)
    { 
        require_once( dirname(__FILE__).'/classes/class-wt-smart-coupon-gift-email.php');
        $email_classes['WT_smart_Coupon_Gift'] = new WT_smart_Coupon_Gift();
        return $email_classes;
    }

    /**
     *  Revoke gift coupon if the order cancelled or refunded
     *  @since 2.0.1   
     *  @since 2.0.8   Added HPOS Compatibility
     */
    public function revoke_gift_coupon($order_id, $status_from, $status_to, $order)
    {
        $coupons = Wt_Smart_Coupon_Common::get_order_meta($order_id, 'wt_coupons');
        
        $coupons = maybe_unserialize($coupons);
        
        if(!empty($coupons))
        {
            $email = Wt_Smart_Coupon_Common::get_order_meta( $order_id, 'wt_coupon_send_to' );
            foreach($coupons as $coupon_id)
            {
                $coupon_obj = new WC_Coupon($coupon_id);
                $email_restrictions = $coupon_obj->get_email_restrictions();
                $key = array_search( $email, $email_restrictions ) ;
                if( false !== $key ) {
                    unset( $email_restrictions[$key] );
                }
                $coupon_obj->set_email_restrictions( $email_restrictions );
                $coupon_obj->save();
                if( empty( $email_restrictions ) ){
                    wp_update_post( array( 'ID'=>$coupon_id, 'post_status'=>'draft' ) );
                }
            }
        }
    }

    /**
     *  Get module ID
     *  @since 2.0.6
     */
    public function get_module_id()
    { 
        return $this->module_id; 
    }


    /**
     *  Add order note after coupon email
     * 
     *  @since  2.1.0
     *  @param  WC_Order $order             Order object
     *  @param  string   $trigger_type      Triggered by admin from back end via resend button or by placing an order from front end.
     *                                      For future use.
     */
    public function add_order_note_after_coupon_email($order, $trigger_type)
    {
        $order->add_order_note(__('Gift coupon(s) emailed to recipient.', 'wt-smart-coupons-for-woocommerce-pro'));
    }


    /**
     *  Default settings
     *  
     *  @since  2.3.0
     *  @param  array       $settings   Settings array
     *  @param  string      $base_id    Module id
     *  @return array       Settings array
     */
    public function default_settings( $settings, $base_id ) {
        if ( $base_id !== $this->module_id ) {
            return $settings;
        }

        // Migrate `email_coupon_for_order_status` option from general settings to module settings.
        $plugin_settings = (array) get_option( WT_SC_SETTINGS_FIELD, array() );
        
        if ( isset( $plugin_settings['email_coupon_for_order_status'] ) ) {
            
            // Add setting value to module settings.
            $module_settings = (array) get_option( $this->module_id, array() );
            $module_settings['email_gift_coupon_for_order_status'] = $plugin_settings['email_coupon_for_order_status'];
            update_option( $this->module_id, $module_settings );


            // Delete the setting from plugin settings.
            unset( $plugin_settings['email_coupon_for_order_status'] );
            update_option( WT_SC_SETTINGS_FIELD, $plugin_settings );
        }


        return array(   
            'email_gift_coupon_for_order_status' => 'completed',
            'email_gift_coupon_to_buyer'         => 'no',
            'generate_unique_gift_coupons'       => 'yes',
        );
    }


    /**
     *  Set the generated gift coupon is actiavted or not.
     *  Hooked into: wbte_sc_is_coupon_activated
     * 
     *  @since  2.3.0
     *  @param  bool        $actiavted          Is coupon actiavted.
     *  @param  WC_Coupon   $coupon             Coupon object.
     *  @return bool        Is coupon actiavted.
     */
    public function is_activated( $actiavted, $coupon ) {
        $coupon_id = $coupon->get_id();

        // Only for generated gift coupon.
        if ( '1' === get_post_meta( $coupon_id, '_wbte_sc_generated_gift_coupon', true ) ) { 
            return ( '1' === get_post_meta( $coupon_id, '_wbte_sc_generated_gift_coupon_activated', true ) );
        }

        return $actiavted;
    }

    /**
     *  To check `Generate unique coupons` of gift coupons enabled or not
     * 
     *  @since  2.4.0
     *  @return bool        Generate unique coupons enabled
     */
    public function is_unique_giftcoupon_generate_enabled(){
        return 'yes' === Wt_Smart_Coupon::get_option( 'generate_unique_gift_coupons', $this->module_id );
    }

    /**
     *  Set email restriction to coupon
     *  
     *  @since 2.4.0
     *  @param int      $coupon_id  Id of coupon
     *  @param string   $email      Email address
     */
    protected static function set_coupon_email_restriction( $coupon_id, $email )
    {
        $coupon_obj = new WC_Coupon($coupon_id);
        $email_restrictions = $coupon_obj->get_email_restrictions();
        if( !in_array( $email, $email_restrictions ) ){
            $email_restrictions[] = $email;
        }
        $coupon_obj->set_email_restrictions( $email_restrictions );
        $coupon_obj->save();
    }

    /**
     *  Trigger action hook after coupon genertaed meta updated.
     * 
     *  @since  2.4.0
     *  @param  int         $coupon_id  Id of coupon which the coupon generated meta is updated.
     *  @param  string      $meta_key   Updated meta key.
     */
    public static function trigger_after_coupon_generated_meta_added( $coupon_id, $meta_key ){

        /**
         *  Hook to trigger after coupon genertaed meta updated.
         *  
         *  @since  2.4.0
         *  @param  int  $coupon_id      Coupon id
         *  @param  int  $meta_key       Updated meta key
         */
        do_action( 'wbte_sc_after_coupon_generated_meta_added', $coupon_id, $meta_key );
    }
}
Wt_Smart_Coupon_Gift_Coupon::get_instance();