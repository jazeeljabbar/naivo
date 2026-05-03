<?php
/**
 * Signup coupon admin/public section.
 *
 * @link       
 * @since 2.0.1     
 *
 * @package  Wt_Smart_Coupon  
 */
if (!defined('ABSPATH')) {
    exit;
}

class Wt_Smart_Coupon_Signup_Coupon
{
    public $module_base='signup_coupon';
    public $module_id='';
    public static $module_id_static='';
    private static $instance = null;
    public function __construct()
    {
        $this->module_id=Wt_Smart_Coupon::get_module_id($this->module_base);
        self::$module_id_static=$this->module_id;

        add_filter('wt_sc_module_default_settings', array($this, 'default_settings'), 10, 2);

        add_action('woocommerce_email_classes', array($this, 'add_signup_coupon_email'), 10, 1);

        add_action('user_register', array($this, 'add_coupon_for_new_user'), 10, 1);

    }

    /**
     * Get Instance
     */
    public static function get_instance()
    {
        if(self::$instance==null)
        {
            self::$instance=new Wt_Smart_Coupon_Signup_Coupon();
        }
        return self::$instance;
    }

    /**
     *  Default settings
    */
    public function default_settings($settings, $base_id)
    {
        if($base_id!=$this->module_id)
        {
            return $settings;
        }

        self::migrate_settings(); /* migrate old settings. If exists */

        return array(
            'enable_signup_coupon'      =>  false,
            'wt_signup_master_coupon'   =>  '',
            'use_master_coupon_as_is'   =>  true,
            'signup_coupon_prefix'      =>  '',
            'signup_coupon_suffix'      =>  '',
            'signup_coupon_length'      =>  12,
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
        if(isset($smart_coupon_option['wt_signup_coupon_settings']) && !empty($smart_coupon_option['wt_signup_coupon_settings'])) /* old data exists */
        {
            Wt_Smart_Coupon::update_settings($smart_coupon_option['wt_signup_coupon_settings'], self::$module_id_static);

            //remove old option
            unset($smart_coupon_option['wt_signup_coupon_settings']);
            update_option('wt_smart_coupon_options', $smart_coupon_option);
        }
    }

    /**
     * Add Signup Email Class into woocommerce Email Class
     * @since 1.2.8
     */
    public function add_signup_coupon_email( $email_classes )
    {
        require_once(dirname(__FILE__).'/classes/class-wt-smart-coupon-signup-coupon-email.php');
        $email_classes['WT_smart_Coupon_Signup_Coupon_Email'] = new WT_smart_Coupon_Signup_Coupon_Email();
        return $email_classes;
    }

    /**
     * Add coupon for newly registered user
     * 
     *  @since 1.2.8
     *  @since 2.3.0    Coupon status changing to publish, on master coupon `as is` condition.
     * 
     *  @param $user_id - newly register user ID
     */
    public function add_coupon_for_new_user( $user_id )
    {
        $settings = $this->get_settings();
        
        if(!$user_id || !$settings['enable_signup_coupon'])
        {
            return false;
        }
        $user = get_user_by('ID',$user_id);
        $user_email = $user->user_email;
        $coupon_id = $settings['wt_signup_master_coupon'];
        if( !$coupon_id ) {
            return false;
        }

        if($settings['use_master_coupon_as_is'])
        { // no need to create random coupon.
            $coupon_obj = new WC_Coupon( $coupon_id );
            $coupon_code = $coupon_obj->get_code();
            $email_restrictions = $coupon_obj->get_email_restrictions();
            $email_restrictions[] = $user_email;
            $coupon_obj->set_email_restrictions(array_unique($email_restrictions));
            $coupon_obj->save();
            $randon_coupon = $coupon_id;

            /** @since 2.3.0  Changing the status to publish */
            wp_update_post( array( 'ID' => $coupon_id, 'post_status' => 'publish' ) );
        } else
        {
            
            $randon_coupon = Wt_Smart_Coupon_Admin::clone_coupon($coupon_id, $settings['signup_coupon_prefix'], $settings['signup_coupon_suffix'], $settings['signup_coupon_length']);
            if( $randon_coupon ) {
                $coupon_obj = new WC_Coupon( $randon_coupon );
                $coupon_code = $coupon_obj->get_code();
                $coupon_obj->set_email_restrictions(  $user_email );
                $coupon_obj->save();
            }
        }
        WC()->mailer();
        do_action('wt_signup_coupon_created', $randon_coupon, $user, $coupon_obj);
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
Wt_Smart_Coupon_Signup_Coupon::get_instance();