<?php
/**
 * Coupon notifications admin section
 *
 * @link       
 * @since 2.0.8
 *
 * @package  Wt_Smart_Coupon  
 */
if (!defined('ABSPATH')) {
    exit;
}
if(!class_exists ('Wt_Smart_Coupon_Notifications')) /* common module class not found so return */
{
	return;
}

class Wt_Smart_Coupon_Notifications_Public extends Wt_Smart_Coupon_Notifications
{
    public $module_base             = 'notifications';
    public $module_id               = '';
    public static $module_id_static = '';
    private static $instance        = null;

    public function __construct()
    {
        $this->module_id        = Wt_Smart_Coupon::get_module_id($this->module_base);
        self::$module_id_static = $this->module_id;

        /** 
        *   Custom coupon message
        *   
        *   @since 2.0.8
        */
        add_filter('woocommerce_coupon_message', array($this, 'custom_coupon_applied_removed_message'), 20, 3);
    }

    /**
     *  Get Instance
     * 
     *  @since 2.0.8
     */
    public static function get_instance()
    {
        if(is_null(self::$instance))
        {
            self::$instance = new Wt_Smart_Coupon_Notifications_Public();
        }

        return self::$instance;
    }


    /**
     *  Show custom coupon applied/removed message. 
     *  Hooked into filter: `woocommerce_coupon_message`
     * 
     *  @since      2.0.8
     *  @param      string      $msg        Existing message
     *  @param      int         $msg_code   Message code, Indicates coupon `applied` or `removed`
     *  @param      WC_Coupon   $coupon     Coupon object
     *  @return     string      Custom applied/removed message.
     */
    public function custom_coupon_applied_removed_message($msg, $msg_code, $coupon)
    {
        $custom_msg = '';

        if($msg_code === WC_Coupon::WC_COUPON_SUCCESS) //success message
        {
            $custom_msg = self::get_coupon_applied_message($coupon->get_code());

        }elseif($msg_code === WC_Coupon::WC_COUPON_REMOVED)
        {
            $custom_msg = $this->get_notification('coupon_removed', array('coupon_code' => $coupon->get_code()));
        }

        if(false === $custom_msg) //disabled
        {
            return '';
        }
        
        $msg = ("" !== $custom_msg ? $custom_msg : $msg);

        return $msg;
    }


    /**
     *  Get custom coupon applied message
     * 
     *  @since      2.0.8
     *  @param      string  $coupon_code        Coupon code
     *  @param      string  $specific_message   Specific message, In some cases there are some specific messages, These messages will be used when custom messages are empty 
     *  @return     string  Custom success message
     */
    public static function get_coupon_applied_message($coupon_code, $specific_message = "")
    {
        $message = self::get_instance()->get_notification('coupon_applied', array('coupon_code' => $coupon_code));

        if(false === $message) //message disabled
        {
            return false;
        }

        $coupon_id = wc_get_coupon_id_by_code($coupon_code);
        
        $coupon_applied_message = get_post_meta($coupon_id , '_wt_sc_coupon_applied_message', true);
        $coupon_applied_message = (is_string($coupon_applied_message) ? trim($coupon_applied_message) : '');
        $coupon_applied_message = ("" !== $coupon_applied_message ? $coupon_applied_message : $message);


        /**
         *  Alter custom coupon applied message
         *  
         *  @since      2.0.8
         *  @param      string  Message
         *  @param      string  Coupon code
         *  @return     string  Message
         */
        return apply_filters('wt_sc_alter_custom_coupon_applied_message', ("" === $coupon_applied_message && "" !== $specific_message ? $specific_message : $coupon_applied_message), $coupon_code);
    }


    /**
     *  Get customized notification messages
     *  
     *  @since  2.0.8
     *  @param  string      $key    Unique key for the message
     *  @param  array       $args   Values for the function: Coupon code, Placeholders etc
     *  @return string      Empty string when message was disabled otherwise the message
     */
    public static function get_customized_text($key, $args = array())
    {
        $filter_args = array();
        $filter_args['placeholders'] = isset($args['placeholders']) ? $args['placeholders'] : $args;
        $filter_args['coupon_code'] = isset($args['coupon_code']) ? $args['coupon_code'] : '';

        return apply_filters('wt_sc_custom_notification_text', '', $key, $filter_args);
    }
}

Wt_Smart_Coupon_Notifications_Public::get_instance();