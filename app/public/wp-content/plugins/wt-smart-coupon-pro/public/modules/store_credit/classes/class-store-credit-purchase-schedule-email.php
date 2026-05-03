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

class Wt_Smart_Coupon_Store_Credit_Purchase_Schedule_Email extends Wt_Smart_Coupon_Store_Credit_Purchase
{
    private static $instance = null;
    public function __construct()
    {
        parent::init_vars();

        /**
         * Create action scheduler on specified time
         */
        add_action('wt_smart_coupon_schedule_credit', array($this, 'schedule_send_credit_coupon'), 10, 1);

        /**
         *  Run action scheduler to send credit coupon.
         */
        add_action('wt_send_coupon_email_as_per_schedule', array($this, 'send_credit_coupon'), 10, 6);
       
        /**
         * Disable the email sending of store credit when a schedule exist.
         */
        add_filter('wt_send_credit_coupon_on_order_success_status', array($this, 'disable_sending_store_credit_immediately'), 10, 3);
    }

    /**
     * Get Instance
     * @since 2.0.0
     */
    public static function get_instance()
    {
        if(self::$instance==null)
        {
            self::$instance=new Wt_Smart_Coupon_Store_Credit_Purchase_Schedule_Email();
        }
        return self::$instance;
    }
    
    /**
     * Send Credit coupon on Run action scheduler.
     */
    public function send_credit_coupon($send_to, $coupon_id, $message, $order_id, $template, $from_name)
    {
        $this->gift_card_email_trigger_type = 'schedule_reached'; /** @since 2.1.0 For customized order notes */
        $this->do_send_mail($order_id, $coupon_id);
    }


    /**
     * Create action scheduler on specified time
     */
    public function schedule_send_credit_coupon($coupon_email_args)
    {
        $email_args =  $coupon_email_args;
        unset($email_args['schedule']);
        as_schedule_single_action($coupon_email_args['schedule'], 'wt_send_coupon_email_as_per_schedule', $email_args, 'wt-smart-coupon-store-credit');
    }


    /**
     *  Disable the email of store credit when a schedule exist.
     * 
     *  @since 2.0.8   Added HPOS Compatibility  
     */
    public function disable_sending_store_credit_immediately($enable, $order_id, $coupon_details=array())
    {
        if(empty($coupon_details)) //older versions only
        {
            $coupon_schedule = Wt_Smart_Coupon_Common::get_order_meta($order_id, 'wt_smart_coupon_schedule');
            if(isset($coupon_schedule) && '' !== $coupon_schedule ) {
                return false;
            }
        }
        return $enable;
    }
}