<?php
/**
 * Cart abandonment admin/public section.
 *
 * @link       
 * @since 2.0.1     
 *
 * @package  Wt_Smart_Coupon  
 */
if (!defined('ABSPATH')) {
    exit;
}

class Wt_Smart_Coupon_Cart_Abandonment
{
    public $module_base='cart_abandonment';
    public $module_id='';
    public static $module_id_static='';
    private static $instance = null;
    public $tb='wt_abandonment_coupon';
    public function __construct()
    {
        $this->module_id=Wt_Smart_Coupon::get_module_id($this->module_base);
        self::$module_id_static=$this->module_id;

        add_filter('wt_sc_module_default_settings', array($this, 'default_settings'), 10, 2);

        // Create tables required for 
        add_action('after_wt_smart_coupon_for_woocommerce_is_activated', array($this, 'add_abandonment_coupon_tables'));

        add_action('woocommerce_email_classes', array($this, 'add_abandonment_coupon_email'), 10, 1);
    }

    /**
     * Get Instance
     */
    public static function get_instance()
    {
        if(self::$instance==null)
        {
            self::$instance=new Wt_Smart_Coupon_Cart_Abandonment();
        }
        return self::$instance;
    }

    /**
     *  Default settings
     * 
     * @since 2.0.8 [Bug fix] Change value of enable_abandonment_coupon to false
     */
    public function default_settings($settings, $base_id)
    {
        if($base_id!=$this->module_id)
        {
            return $settings;
        }

        self::migrate_settings(); /* migrate old settings. If exists */

        return array(
            'enable_abandonment_coupon'     =>  false,
            'abandonment_master_coupon'     =>  '',
            'cut_of_time'                   =>  20, // in minutes
            'email_send_after'              =>  60, // in minutes
            'use_master_coupon_as_is'       =>  true,
            'abandonment_coupon_prefix'     =>  '',
            'abandonment_coupon_suffix'     =>  '',
            'abandonment_coupon_length'     =>  12,
        );
    }

    public function get_settings()
    {
        return Wt_Smart_Coupon::get_settings($this->module_id);
    }

    /**
     *  Migrate old settings, If exists
     */
    protected static function migrate_settings()
    {
        $smart_coupon_option = get_option('wt_smart_coupon_options');
        if(isset($smart_coupon_option['wt_abandonment_coupon_settings']) && !empty($smart_coupon_option['wt_abandonment_coupon_settings'])) /* old data exists */
        {
            Wt_Smart_Coupon::update_settings($smart_coupon_option['wt_abandonment_coupon_settings'], self::$module_id_static);

            //remove old option
            unset($smart_coupon_option['wt_abandonment_coupon_settings']);
            update_option('wt_smart_coupon_options', $smart_coupon_option);
        }
    }

    /**
     * Add Abandoment Email Class into woocommerce Email Class
     * @since 1.2.8
     */
    public function add_abandonment_coupon_email( $email_classes )
    {
        require_once(dirname(__FILE__).'/classes/class-wt-smart-coupon-abandonment-coupon-email.php');
        $email_classes['WT_smart_Coupon_Abandonment_Coupon_Email'] = new WT_smart_Coupon_Abandonment_Coupon_Email();
        return $email_classes;
    }

    /**
     * Get abandoment data of a user
     * @since 1.2.8
     * @param $user_id - user id to featch data
     */
    public function get_abandoment_data_by_user($user_id = '')
    {
        global $wpdb;
        if( ! $user_id ) {
            $user_id = get_current_user_id();
        }
        if( ! $user_id ) {
            return false;
        }
        $table_name = $wpdb->prefix.$this->tb;
        return $wpdb->get_row( $wpdb->prepare  (  "SELECT * FROM {$table_name}  WHERE user_id = %d AND is_cart_ignored = %s AND is_cart_recovered  = %s " ,$user_id,0,0 ) );
    }

    /**
     * Get abandoment from ID
     * @since 1.2.8
     */
    public function get_abandonment_data($abandoment_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix.$this->tb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name}  WHERE id = %d " , $abandoment_id));

    }

    /**
     * Insert Abandoment Data into table
     * @since 1.2.8
     * @param $cart_data - 
     * @param $is_cart_ignored - 
     * @param $user_id - 
     * @param $cart_session_id - 
     */
    public function insert_abandoment_data( $cart_data,$is_cart_ignored = 0 ,$user_id = '',$cart_session_id='' )
    {
        global $wpdb;
        if( !$user_id && ! $cart_session_id ) {
            return false;
        }
        if( !$user_id ) {
            $user_id = 0;
        }

        $current_time   = strtotime('now');

        $data = array(
            'user_id'           => $user_id,
            'time'              => $current_time,
            'is_cart_ignored'   => $is_cart_ignored,
            'cart_info'         =>  $cart_data
        );
        $table_name = $wpdb->prefix . $this->tb;
        if( $wpdb->insert( $table_name,$data ) ) {
            
            $settings = $this->get_settings();
            $email_send_on = ( isset( $settings['email_send_after'] ) )? $settings['email_send_after'] : 120;
            $cut_of_time = ( isset( $settings['cut_of_time'] ) )? $settings['cut_of_time'] : 60;

            $crone_schedule_time = $current_time + ( 60 *  ( absint( $cut_of_time )  +  absint( $email_send_on ) )  ) ;
            $user = get_user_by('ID',$user_id);

            if( ! $is_cart_ignored ) {
                $arguments = array(
                    'user_id' => $user_id,
                    'abandoment_id' => $wpdb->insert_id,
                    'email' => $user->user_email
                );
                as_schedule_single_action( $crone_schedule_time, 'wt_check_and_create_abandonment_item',$arguments,'wt-smart-coupon-abandonment-cart' );

            } else {
                // exception on table insertion.
            }
            
            return $wpdb->insert_id;
        }
        return false;
    }

    /**
     * Create Abandonment coupon tables
     * @since 1.2.8
     */
    public function add_abandonment_coupon_tables()
    {
        global $wpdb;
     
            $table_name = $wpdb->prefix.$this->tb;
            $charset_collate = '';
            if($wpdb->has_cap('collation'))
            {
                $charset_collate = $wpdb->get_charset_collate();
            }
            $sql = "CREATE TABLE `$table_name` (
                `id` mediumint(9) NOT NULL AUTO_INCREMENT,
                `user_id` mediumint(9),
                `time` int(11)  NOT NULL,
                `cart_info` text COLLATE utf8_unicode_ci NOT NULL,
                `is_cart_ignored` enum('0','1') COLLATE utf8_unicode_ci NOT NULL,
                `is_cart_recovered` enum('0','1') COLLATE utf8_unicode_ci NOT NULL,
                `session_id` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
                PRIMARY KEY  (id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta($sql);
    }


    /**
     *  Get module ID
     *  @since 2.0.6
     */
    public function get_module_id()
    { 
        return $this->module_id; 
    }

}
Wt_Smart_Coupon_Cart_Abandonment::get_instance();