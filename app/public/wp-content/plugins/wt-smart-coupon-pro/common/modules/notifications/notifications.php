<?php
/**
 * Coupon notifications admin/public
 *
 * @link       
 * @since 2.0.8   
 *
 * @package  Wt_Smart_Coupon
 */

if (!defined('ABSPATH')) {
    exit;
}

class Wt_Smart_Coupon_Notifications
{
    public $module_base             = 'notifications';
    public $module_id               = '';
    public static $module_id_static = '';
    private static $instance        = null;
    private static $notifications   = null;
    private static $placeholders    = array();
    
    public function __construct()
    {
        $this->module_id        = Wt_Smart_Coupon::get_module_id($this->module_base);
        self::$module_id_static = $this->module_id;

        add_filter('wt_sc_module_default_settings', array($this, 'default_settings'), 10, 2);
        add_filter('wt_sc_custom_notification_text', array($this, 'custom_notification_text'), 10, 3);
        add_filter('wt_sc_add_placeholder_values', array($this, 'add_placeholder_values'), 10, 3);
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
            self::$instance = new Wt_Smart_Coupon_Notifications();
        }

        return self::$instance;
    }


    /**
     *  Default settings
     * 
     *  @since      2.0.8
     *  @param      array       $settings   Settings array
     *  @param      string      $base_id    Module id
     *  @return     array       Settings array
     */
    public function default_settings($settings, $base_id)
    {
        if($base_id != $this->module_id)
        {
            return $settings;
        }

        return array(
            'notifications' => array(), //By default there is no customizations
        );
    }


    /**
     *  Get notifications registered 
     *  
     *  @since      2.0.8
     *  @return     array       Notifications array 
     */
    public function get_registered_notifications()
    {
        $notifications = array(
            'coupon_applied' => array(
                'message'           => __('Coupon code applied successfully.', 'wt-smart-coupons-for-woocommerce-pro'),
                'description'       => __('Displays when a coupon is successfully applied.', 'wt-smart-coupons-for-woocommerce-pro'),
                'status'            => 1, 
                'supported_placeholders' => array(
                    'coupon_code' => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
                ),
                'available_filters' => array(
                    'woocommerce_coupon_message' => __('An WooCommerce filter hook to edit coupon applied/removed messages.', 'wt-smart-coupons-for-woocommerce-pro'),
                ),
                'module' => 'main',
                'group'     => 'success',
                'initiater'     => 'wc', //message of WC
                'wc_msg_code'   => WC_Coupon::WC_COUPON_SUCCESS, //only applicable for WC messages
            ),
        
        );

        return apply_filters('wt_sc_intl_add_notifications', $notifications);
    }

    
    /**
     *  Get the list of notifications that are customized by admin
     * 
     *  @since      2.0.8
     *  @return     array  List of customized notifications. 
     */
    public function get_customized_notifications()
    {
        $settings  = Wt_Smart_Coupon::get_settings($this->module_id);
        return isset($settings['notifications']) ? $settings['notifications'] : array();
    }

    /**
     *  Save the customized notifications
     * 
     *  @since      2.0.8
     */
    public function set_customized_notifications($notifications)
    {
        $settings  = Wt_Smart_Coupon::get_settings($this->module_id);
        $settings['notifications'] = $notifications;

        Wt_Smart_Coupon::update_settings($settings, $this->module_id);
    }

    
    
    /**
     *  Get notification messages
     *  
     *  @since      2.0.8
     *  @return     array  List of all notifications including customized and default. 
     */
    public function get_notifications()
    {
        if(!is_null(self::$notifications)) //already prepared
        {
            return self::$notifications;
        }

        $default_list   = $this->get_registered_notifications();
        $custom_list    = $this->get_customized_notifications();       

        if(!empty($custom_list)) //only when user has customized anything
        {
            foreach($custom_list as $custom_list_key => $custom_list_item)
            {
                if(isset($default_list[$custom_list_key])) //exists in the list
                {
                    if(isset($custom_list_item['status']) && !isset($default_list[$custom_list_key]['status_locked'])) //not a status locked item
                    {
                        $default_list[$custom_list_key]['status'] = $custom_list_item['status'];
                    }

                    if(isset($custom_list_item['message']) && "" !== trim($custom_list_item['message']))
                    {
                        $default_list[$custom_list_key]['custom_message'] = trim($custom_list_item['message']);
                    }
                }
            }
        }

        self::$notifications = $default_list; //save for future use

        return $default_list;
    }

    
    /**
     *  Get a specific notification text by key
     * 
     *  @since  2.0.8
     *  @param  string                  $key    Unique message key
     *  @return string|boolean          False when message status is disabled
     *                                  Empty string when customization not enabled for this message
     *                                  Custom message, if exists otherwise default message
     *                                  
     */
    public function get_notification($key, $args)
    {
        $notifications = $this->get_notifications();

        if(isset($notifications[$key]))
        {
            $notification = $notifications[$key];

            if(0 === absint($notification['status'])) //disabled
            {
                return false;
            }else
            {
                $coupon_code = (isset($args['coupon_code']) ? $args['coupon_code'] : '');
                $def_message = (isset($notification['initiater']) && 'wc' === $notification['initiater'] ? '' : $this->process_message($notification['message'], $key, $coupon_code)); //set default message as empty for WC messages. This is to show message from WC when customized message not exists. 

                return (isset($notification['custom_message']) && "" !== $notification['custom_message'] ? $this->process_message($notification['custom_message'], $key, $coupon_code) : $def_message);
            }

        }else
        {
            return '';
        }
    }

    
    /**
     *  Process placeholder values in the custom coupon message
     * 
     *  @since  2.0.8
     *  @param  string   $msg                Coupon message    
     *  @param  string   $key                Unique message key
     *  @param  string   $coupon_code        Code of the coupon
     *  @return string   Processed message
     */
    protected function process_message($msg, $key, $coupon_code = "")
    {
        $msg = apply_filters('wt_sc_alter_custom_message_before_processing', $msg, $key, $coupon_code);
        $msg = $this->clean_custom_msg($msg); //remove sprintf placeholders

        $notification = self::$notifications[$key];

        $placeholders = array();

        if(is_array($notification['supported_placeholders']))
        {
            foreach($notification['supported_placeholders'] as $placeholder_name => $placeholder_desc)
            {
                $val = ('coupon_code' === $placeholder_name ? $coupon_code : ''); //setting value for known placeholder and empty value for others

                $placeholders['{'.$placeholder_name.'}'] = $val; //add value and envelope the names with braces
            }
        }

        $placeholders = apply_filters('wt_sc_add_placeholder_values', $placeholders, $key, $coupon_code); //hook to add placeholder values

        /* before replacing placeholders with real value, we have to give the string for translation */
        $msg = $this->process_for_translation($msg, $placeholders);

        $msg = str_replace(array_keys($placeholders), array_values($placeholders), $msg); //replace placholders with real values

        return apply_filters('wt_sc_alter_custom_message_after_processing', $msg, $key, $coupon_code);
    }


    /**
     *  Process and give the custom message string to translation function
     * 
     *  @since  2.0.8
     *  @param  string  $msg            Message
     *  @param  array   $placeholders   Available placeholders array
     *  @return string                  Message after translation 
     */
    private function process_for_translation($msg, $placeholders)
    {
        $replaced_placeholders = array();

        foreach($placeholders as $placeholder_key => $placeholder_value)
        {
            $len = strlen($placeholder_key);

            while(false !== ($pos = strpos($msg, $placeholder_key)))
            {
                $replaced_placeholders[$pos] = $placeholder_key; //save the placholders based on the position found in the string
                $msg = substr_replace($msg , '%s', $pos, $len);
            }
        }

        $msg = __($msg, 'wt-smart-coupons-for-woocommerce-pro'); //for translators

        $msg = $this->clean_custom_msg($msg, array('%s')); //remove extra placeholders added while translating

        if(substr_count($msg, '%s') === count($replaced_placeholders)) //otherwise vsprintf will throw an error
        {
            ksort($replaced_placeholders);
            $msg = vsprintf($msg, $replaced_placeholders); //re-add the removed placeholders
        }else
        {
            $msg = $this->clean_custom_msg($msg); //remove all placeholders because this message is malformed while translating
        }

        return $msg;
    }


    /**
     *  Add placeholder values to corresponding placeholders. And also add braces to placeholder name for directly replace the placeholder values from the message string
     * 
     *  @since  2.0.8
     *  @param  array   $placeholders   Available placeholders array
     *  @param  string  $key            Message key
     *  @param  string  $coupon_code    Code of the current coupon
     *  @return array                   Associative array of placeholders with value. 
     */
    public function add_placeholder_values($placeholders, $key, $coupon_code)
    {
        $placeholder_values = $this->get_placeholder_values($key, array('coupon_code' => $coupon_code));

        if(!empty($placeholder_values))
        {
            foreach($placeholder_values as $key => $value)
            {
                if(isset($placeholders['{'.$key.'}']))
                {
                    $placeholders['{'.$key.'}'] = $value; //assign the new value
                }
            }
        }

        return $placeholders;
    }


    /**
     *  Get placeholder values of a given message from a static variable.
     * 
     *  @since  2.0.8
     *  @param  string  $key        Message key
     *  @param  array   $args       Arguments array. Including coupon code etc
     *  @return array               Associative array of placeholders, Empty array when no placholders found. 
     */
    private function get_placeholder_values($key, $args)
    {  
        $coupon_code = (isset($args['coupon_code']) ? $args['coupon_code'] : '');

        if("" !== $coupon_code) //coupon specific
        {
            return (
                isset(self::$placeholders[$coupon_code]) 
                && isset(self::$placeholders[$coupon_code][$key]) 
                && is_array(self::$placeholders[$coupon_code][$key]) ? self::$placeholders[$coupon_code][$key] : array());

        }else //not for a specific coupon
        {
            return (isset(self::$placeholders[$key]) && is_array(self::$placeholders[$key]) ? self::$placeholders[$key] : array());
        }
    }


    /**
     *  Save placeholder values to a static variable for future usage
     * 
     *  @since  2.0.8
     *  @param  string  $key        Message key
     *  @param  array   $args   Arguments array. Including placeholders, coupon code etc
     */
    private function reg_placeholder_values($key, $args)
    {
        $coupon_code = (isset($args['coupon_code']) ? $args['coupon_code'] : '');
        $placeholders = (isset($args['placeholders']) && is_array($args['placeholders']) ? $args['placeholders'] : array());

        if("" !== $coupon_code)
        {
            if(!isset(self::$placeholders[$coupon_code]))
            {
                self::$placeholders[$coupon_code] = array();
            }
                
            self::$placeholders[$coupon_code][$key] = $placeholders; 

        }else
        {
            self::$placeholders[$key] = $placeholders;
        }
    }


    /**
     *  Clean sprintf placeholders from string. This is to avoid sprintf errors
     * 
     *  @since  2.0.8
     *  @param  string  $msg        Message
     *  @param  array   $exclude    Exclude items
     *  @return string              Filtered string
     */
    protected function clean_custom_msg($msg, $exclude = array())
    {
        $to_replace = array_diff(array('%s', '%d', '%f', '%b', '%o', '%x', '%X'), $exclude);
    
        return str_replace($to_replace, '', $msg);
    }


    /**
     *  Filter callback function to give customized message for a specific message key
     * 
     *  @since  2.0.8
     *  @param  string  $msg    Message
     *  @param  string  $key    Message key
     *  @param  array   $args   Arguments array. Including placeholders, coupon code etc
     *  @return string|bool     String when message is enabled, False when message is disabled
     */
    public function custom_notification_text($msg, $key, $args)
    {
        //register placeholder values. This will use when replacing placeholders with real values
        $this->reg_placeholder_values($key, $args);

        return $this->get_notification($key, $args);
    }

}

Wt_Smart_Coupon_Notifications::get_instance();
