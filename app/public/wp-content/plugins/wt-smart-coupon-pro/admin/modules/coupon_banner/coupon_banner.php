<?php
/**
 * Coupon banner
 *
 * @link       
 * @since 1.3.5     
 *
 * @package  Wt_Smart_Coupon  
 */
if (!defined('ABSPATH')) {
    exit;
}

if(!class_exists ( 'Wt_Smart_Coupon_Banner' ) ) /* common module class not found so return */
{
    return;
}

class Wt_Smart_Coupon_Banner_Admin extends Wt_Smart_Coupon_Banner{
    public $module_base='coupon_banner';
    public $module_id='';
    public static $module_id_static='';
    private static $instance = null;
    public function __construct()
    {
        $this->module_id=Wt_Smart_Coupon::get_module_id($this->module_base);
        self::$module_id_static=$this->module_id;

        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'), 10 );
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'), 10 );
        
        add_filter("wt_sc_plugin_settings_tabhead", array($this, 'settings_tabhead'),1);
        
        add_filter("wt_sc_plugin_out_settings_form", array($this, 'out_settings_form'),1);

        add_filter('wt_sc_alter_tooltip_data',array($this, 'register_tooltips'),1);

    }

    /**
     * Get Instance
     * @since 1.3.5
     */
    public static function get_instance()
    {
        if(self::$instance==null)
        {
            self::$instance=new Wt_Smart_Coupon_Banner_Admin();
        }
        return self::$instance;
    }

    /**
    *   @since 1.3.5
    *   Hook the tooltip data to main tooltip array
    */
    public function register_tooltips($tooltip_arr)
    {
        include(plugin_dir_path( __FILE__ ).'data/data.tooltip.php');
        $tooltip_arr[$this->module_id]=$arr;
        return $tooltip_arr;
    }

    public function enqueue_scripts()
    {
        if(isset($_GET['page']) && $_GET['page']==WT_SC_PLUGIN_NAME)
        {
            wp_enqueue_script($this->module_id, plugin_dir_url(__FILE__) . 'assets/js/main.js', array('jquery', 'wp-color-picker', 'jquery-tiptip'), WEBTOFFEE_SMARTCOUPON_VERSION, false);
            wp_localize_script($this->module_id, 'wt_sc_coupon_banner_params', array(
                'ajax_url'=>admin_url('admin-ajax.php'),
                'msgs'=>array(
                    'settings_error'=>__("Unable to save settings", 'wt-smart-coupons-for-woocommerce-pro'),
                    'minimize_sidebar'=>__('Click to minimize the sidebar', 'wt-smart-coupons-for-woocommerce-pro'),
                    'maximize_sidebar'=>__('Click to maximize the sidebar', 'wt-smart-coupons-for-woocommerce-pro'),
                ),
            ));
        }
    }

    public function enqueue_styles()
    {
        self::enqueue_banner_style();
    }

    /**
     *  @since 1.3.5
     *  Tab head for plugin settings page
     **/
    public function settings_tabhead($arr)
    {
        $added=0;
        $out_arr=array();
        foreach($arr as $k=>$v)
        {
            $out_arr[$k]=$v;
            if($k=='wt-sc-coupon_style' && $added==0) /* after customize */
            {               
                $out_arr['wt-sc-'.$this->module_base]=__('Coupon banner', 'wt-smart-coupons-for-woocommerce-pro');
                $added=1;
            }
        }
        if($added==0){
            $out_arr['wt-sc-'.$this->module_base]=__('Coupon banner', 'wt-smart-coupons-for-woocommerce-pro');
        }
        return $out_arr;
    }

    /**
     * @since 1.3.5
     * Coupon banner tab content
     **/
    public function out_settings_form($args)
    {
        $view_file=plugin_dir_path( __FILE__ ).'views/_banner_settings.php';

        /* coupon dummy data for preview */
        $coupon_data_dummy=array(
            'coupon_expiry'=>null,
            'coupon_id'=>1234,
            'coupon_code'=>__('coupon-code', 'wt-smart-coupons-for-woocommerce-pro'),
        );
        $banner_data=self::get_current_banner_settings();
 
        $view_params=array(
            'coupon_data_dummy'=>$coupon_data_dummy,
            'banner_data'=>$banner_data,
            'default_banner_data'=>self::get_default_banner_settings(),
            'module_id'=>$this->module_id,
            'module_base'=>$this->module_base,
            'display_types'=>self::display_types(),
            'banner_display_positions'=>self::banner_display_positions(),
            'widget_display_positions'=>self::widget_display_positions(),
            'module_img_path'=>plugin_dir_url(__FILE__).'assets/images/',
        );
        Wt_Smart_Coupon_Admin::envelope_settings_tabcontent('wt-sc-'.$this->module_base, $view_file, '', $view_params, 0);
    }
}
Wt_Smart_Coupon_Banner_Admin::get_instance();
