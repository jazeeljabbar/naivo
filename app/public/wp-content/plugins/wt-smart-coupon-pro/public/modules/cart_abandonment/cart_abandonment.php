<?php
/**
 * Cart abandonment public section.
 *
 * @link       
 * @since 2.0.1     
 *
 * @package  Wt_Smart_Coupon  
 */
if (!defined('ABSPATH')) {
    exit;
}

if(!class_exists ('Wt_Smart_Coupon_Cart_Abandonment')) /* common module class not found so return */
{
    return;
}

class Wt_Smart_Coupon_Cart_Abandonment_Public extends Wt_Smart_Coupon_Cart_Abandonment
{
    public $module_base='cart_abandonment';
    public $module_id='';
    public static $module_id_static='';
    private static $instance = null;
    public function __construct()
    {
        $this->module_id=Wt_Smart_Coupon::get_module_id($this->module_base);
        self::$module_id_static=$this->module_id;

        add_action('woocommerce_add_to_cart', array($this, 'wt_insert_abandonment_data'), 100);
        add_action('woocommerce_cart_item_removed', array($this, 'wt_insert_abandonment_data'), 100);
        add_action('woocommerce_cart_item_restored', array($this, 'wt_insert_abandonment_data'), 100);
        add_action('woocommerce_after_cart_item_quantity_update', array($this, 'wt_insert_abandonment_data'), 100);
        add_action('wt_check_and_create_abandonment_item', array($this, 'create_abandonment_coupon'), 10,3);
        add_action('woocommerce_order_status_changed', array($this, 'check_successfull_order_is_recovered'), 10, 4);
    }

    /**
     * Get Instance
     */
    public static function get_instance()
    {
        if(self::$instance==null)
        {
            self::$instance=new Wt_Smart_Coupon_Cart_Abandonment_Public();
        }
        return self::$instance;
    }

    /**
     *  Callback for the scheduled action for creating abandonment coupon
     * 
     *  @since 1.2.8 
     *  @since 2.0.8    Added checking for the existence of master coupon
     *  @since 2.3.0    Coupon status changing to publish, on master coupon `as is` condition.
     */
    public function create_abandonment_coupon($user_id, $abandoment_id, $email)
    {
        $settings = $this->get_settings();
        
        /**
         *  Option enabled and Master coupon added 
         */          
        if(!wc_string_to_bool($settings['enable_abandonment_coupon']) || empty($settings['abandonment_master_coupon']))
        {
            return;
        }


        $master_coupon = new WC_Coupon($settings['abandonment_master_coupon']); //for checking the existence of master coupon

        if(!$master_coupon || (is_a($master_coupon, 'WC_Coupon') && 0 === $master_coupon->get_id()))
        {
            return;
        }

        
        $cart_items = $this->get_abandonment_data( $abandoment_id );

        if($cart_items->is_cart_ignored || $cart_items->is_cart_recovered) {
            // nothing to do cart ignored or recovered.
            return false;
        }
        
        // create and send coupon - update status into email send
        $user_id = $cart_items->user_id;
        $user = get_user_by('ID',$user_id);
        $user_email = $user->user_email;


        if(isset($settings['use_master_coupon_as_is']) && $settings['use_master_coupon_as_is'])
        {
            // no need to create a coupon update allowed email and send the coupon.
            $coupon_obj = new WC_Coupon($master_coupon);
            $coupon_code = $coupon_obj->get_code();
            $email_restrictions = $coupon_obj->get_email_restrictions();
            if(!in_array($user_email,$email_restrictions)){
                $email_restrictions[] = $user_email;
            }
            $coupon_obj->set_email_restrictions( $email_restrictions );
            $coupon_obj->save();
            $randon_coupon = $master_coupon;

            /** @since 2.3.0  Changing the status to publish */
            wp_update_post( array( 'ID' => $coupon_obj->get_id(), 'post_status' => 'publish' ) );

        }else
        {           
            // Create send the coupon.
            $coupon_prefix = $settings['abandonment_coupon_prefix'];
            $coupon_suffix = $settings['abandonment_coupon_suffix'];
            $coupon_length = $settings['abandonment_coupon_length'];
            $randon_coupon = Wt_Smart_Coupon_Admin::clone_coupon($master_coupon->get_id(), $coupon_prefix, $coupon_suffix, $coupon_length);
            if( $randon_coupon )
            {
                $coupon_obj = new WC_Coupon( $randon_coupon );
                $coupon_code = $coupon_obj->get_code();
                $coupon_obj->set_email_restrictions($user_email);
                $coupon_obj->save();
            }
        }
        add_user_meta($user_id, 'wt_send_abandonment_coupon', true);
        
        WC()->mailer();
        do_action('wt_abandonment_coupon_created', $randon_coupon, $user, $coupon_obj);
        
    }

    /**
     *  Insert abandonment data into table
     * 
     *  @since 1.2.8
     *  @since 2.0.8   Added checking for the existence of master coupon
     */
    public function wt_insert_abandonment_data()
    {
        global $wpdb,$woocommerce;
        $settings = $this->get_settings(); 
         
        /**
         *  Option enabled and Master coupon added 
         */          
        if(!wc_string_to_bool($settings['enable_abandonment_coupon']) || empty($settings['abandonment_master_coupon']))
        {
            return false;
        }

        $master_coupon = new WC_Coupon($settings['abandonment_master_coupon']); //for checking the existence of master coupon

        if(is_user_logged_in() && is_a($master_coupon, 'WC_Coupon') && 0 < $master_coupon->get_id())
        {
            $current_time = current_time('timestamp');
            $cart_ignored = 0;
            $recovered_cart = 0;

            $cart_cut_off_time = absint($settings['cut_of_time'])*60; 

            $compare_time = $current_time - $cart_cut_off_time;

            $user_id = get_current_user_id();                
            $cart_data = array();
            $cart_data['cart'] = !is_null( WC()->session ) ? WC()->session->cart : array();
            $cart_data_str = json_encode( $cart_data );
            $table_name = $wpdb->prefix.$this->tb;


            $user_abandoment_data = $this->get_abandoment_data_by_user($user_id);

            if(!$user_abandoment_data || ! is_object($user_abandoment_data))
            {
                $inserted = $this->insert_abandoment_data( $cart_data_str, 0,$user_id );

            }elseif($user_abandoment_data->time && $compare_time > $user_abandoment_data->time)
            {
                //  check cart item data updated.
                if($this->check_is_cart_item_updated( $user_id, $user_abandoment_data->cart_info ))
                {
                    $data = array(
                        'is_cart_ignored' =>1
                    );

                    $where = array(
                        'id' => $user_abandoment_data->id
                    );
                    $wpdb->update( $table_name,$data,$where );
                    $user = get_user_by('ID',$user_id);
                    $arguments = array(
                        'user_id'       => (int) $user_id,
                        'abandoment_id' => (int) $user_abandoment_data->id,
                        'email'         => $user->user_email
                    );
                    as_unschedule_action( 'wt_check_and_create_abandonment_item', $arguments,'wt-smart-coupon-abandonment-cart' );

                    $inserted = $this->insert_abandoment_data( $cart_data_str, 0,$user_id );

                }else
                {
                    // no need to update the cart item.
                }
                
            }else
            {  
                // update the cart item data
                $data = array(
                    'cart_info' => $cart_data_str 
                );

                $where = array(
                    'id' => $user_abandoment_data->id
                );
                $wpdb->update( $table_name,$data,$where );
            }
        }
    }

    /**
     * Check is customer updated the cart
     * @since 1.2.8
     */
    public function check_is_cart_item_updated($user_id, $cart_info)
    {
        global $woocommerce;       
        $abd_cart_info = json_decode( $cart_info,true );

        $abd_cart_info = ( isset( $abd_cart_info['cart'] ) ) ? $abd_cart_info['cart'] : ''; 
        if( '' == $abd_cart_info  ) {
            return true;
        }

        $woocommerce_persistent_cart = version_compare( $woocommerce->version, '3.1.0', ">=" ) ? '_woocommerce_persistent_cart_' . get_current_blog_id() : '_woocommerce_persistent_cart' ;
        $current_cart_info   = get_user_meta( $user_id, $woocommerce_persistent_cart, true );
        if( isset($current_cart_info['cart'] ) &&  !empty( $current_cart_info['cart'] )) {
            foreach( $current_cart_info['cart'] as $key => $cart_item ) {
                if( 
                        !isset( $abd_cart_info[$key]['product_id'] ) || $abd_cart_info[$key]['product_id'] != $cart_item['product_id']
                    ||  !isset( $abd_cart_info[$key]['variation_id'] ) || $abd_cart_info[$key]['variation_id'] != $cart_item['variation_id']
                    ||  !isset( $abd_cart_info[$key]['quantity'] ) || $abd_cart_info[$key]['quantity'] != $cart_item['quantity']
                ) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check cart is recovered or customer completed the checkout before sending the coupon
     * @since 1.2.8
     */
    public function check_successfull_order_is_recovered( $order_id, $old_status,$new_status ,$order)
    {

        $successful_statuses = apply_filters('wt_abandonment_coupon_order_success_statuses', array('completed', 'processing') );

        if( !in_array( $new_status,$successful_statuses ) ) {
            // nothing to do
            return;
        }

        $user_id = $order->get_user_id();
        if( !$user_id  ) {
            // guest chekcout will handle later version.
            return false;
        }
        
        $abd_info = $this->get_abandoment_data_by_user( $user_id );
        if( !$abd_info  || ! $abd_info->id ) {
            // the order have no curresponding abd data. nothing to do
            return false;
        }
        $user = get_user_by('ID', $user_id);
        $user_email = $user->user_email;
        $arguments = array(
            'user_id'       => (int)$user_id,
            'abandoment_id' => (int)$abd_info->id,
            'email'         => $user_email
        );
        // un schedule on sucessfull order.
        as_unschedule_action('wt_check_and_create_abandonment_item', $arguments, 'wt-smart-coupon-abandonment-cart');

    }
}
Wt_Smart_Coupon_Cart_Abandonment_Public::get_instance();
