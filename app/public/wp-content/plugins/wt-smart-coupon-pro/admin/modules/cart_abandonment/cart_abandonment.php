<?php
/**
 * Cart abandonment admin section.
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

class Wt_Smart_Coupon_Cart_Abandonment_Admin extends Wt_Smart_Coupon_Cart_Abandonment
{
    public $module_base='cart_abandonment';
    public $module_id='';
    public static $module_id_static='';
    private static $instance = null;
    public function __construct()
    {
        $this->module_id=Wt_Smart_Coupon::get_module_id($this->module_base);
        self::$module_id_static=$this->module_id;

        add_filter('wt_sc_admin_menu', array($this, 'add_admin_pages'));

        /**
         *  Alter master coupon status based on settings
         *  @since 2.0.6
         */
        add_filter('wt_sc_intl_alter_modules_list_with_master_coupon_option', array($this, 'get_module_id_for_master_coupon_enabled_module_list'));
        add_filter('wt_sc_intl_master_coupon_option_name', array($this, 'get_master_coupon_option_name'), 10, 2);

        /**
         *  Set empty default value for master coupon js select field
         *  
         *  @since 2.0.7
         */
        add_filter('wt_sc_intl_default_val_needed_fields',array($this, 'default_val_needed_fields'), 10, 2);
    }

    /**
     * Get Instance
     */
    public static function get_instance()
    {
        if(self::$instance==null)
        {
            self::$instance=new Wt_Smart_Coupon_Cart_Abandonment_Admin();
        }
        return self::$instance;
    }

    /**
     *  Admin page
     */
    public function add_admin_pages($menus)
    {
        $menus[]=array(
            'submenu',
            WT_SC_PLUGIN_NAME,
            __('Abandoned cart','wt-smart-coupons-for-woocommerce-pro'),
            __('Abandoned cart','wt-smart-coupons-for-woocommerce-pro'),
            'manage_woocommerce',
            $this->module_id,
            array($this, 'cart_abandonment_page_content')
        );
        return $menus;
    }

    public function cart_abandonment_page_content()
    {
        $settings  = $this->get_settings();
        include_once plugin_dir_path( __FILE__ ).'views/general_settings.php';
    }

    /**
     *  @since 2.0.6
     */
    public function get_module_id_for_master_coupon_enabled_module_list($module_list)
    {
        $module_list[] = $this->get_module_id();
        return $module_list;
    }

    /**
     *  Get master coupon option name
     *  @since 2.0.6
     */
    public function get_master_coupon_option_name($option_name, $base_id)
    { 
        return $base_id===$this->module_id ? 'abandonment_master_coupon' : $option_name; 
    }


    /**
     *  Set empty default value for master coupon js select field
     *  
     *  @since 2.0.7
     *  @param $default_val_needed_fields   array   Array of default value needed fields
     *  @param $base_id                     string  Module Id                
     *  @return $default_val_needed_fields  array   Array of default value needed fields for the current module               
     */
    public function default_val_needed_fields($default_val_needed_fields, $base_id)
    {
        if($base_id !== $this->module_id)
        {
            return $default_val_needed_fields;
        }

        return array(
            'abandonment_master_coupon' => '',
        );
    }
}
Wt_Smart_Coupon_Cart_Abandonment_Admin::get_instance();