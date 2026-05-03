<?php
/**
 * Coupon banner public facing
 *
 * @link       
 * @since 2.0.0     
 *
 * @package  Wt_Smart_Coupon  
 */
if (!defined('ABSPATH')) {
    exit;
}
if(!class_exists('Wt_Smart_Coupon_Banner')) /* common module class not found so return */
{
    return;
}
class Wt_Smart_Coupon_Banner_Public extends Wt_Smart_Coupon_Banner{
    public $module_base='coupon_banner';
    public $module_id='';
    public static $module_id_static='';
    private static $instance = null;

    protected $banner_option;
    protected $coupon_banner_count;
    public function __construct()
    {
        $this->module_id=Wt_Smart_Coupon::get_module_id($this->module_base);
        self::$module_id_static=$this->module_id;

        /**
         *  Add scripts and styles for coupon banner
         */
        add_action('wp_enqueue_scripts', array($this, 'register_scripts'));

        add_action('wp_footer', array($this, 'inject_banner'));

        $coupon_banner_count = 0;
        add_shortcode('wt_smart_coupon_banner', array($this, 'banner_shortcode'));
    }

    /**
     * Get Instance
     * @since 2.0.0
     */
    public static function get_instance()
    {
        if(self::$instance==null)
        {
            self::$instance=new Wt_Smart_Coupon_Banner_Public();
        }
        return self::$instance;
    }

    /**
     * Register required scripts/styles
     */
    public function register_scripts()
    {   
        wp_register_script('wt-smart-coupon-banner', plugin_dir_url(__FILE__).'assets/js/wt-coupon-banner.js', array('jquery'), WEBTOFFEE_SMARTCOUPON_VERSION, false);
        wp_register_style('wt-smart-coupon-banner', plugin_dir_url(__FILE__).'assets/css/wt-coupon-banner.css', array(), WEBTOFFEE_SMARTCOUPON_VERSION, 'all');
    }

    public function enqueue_scripts()
    {
        $banner_settings  = self::get_current_banner_settings();
        $timer_labels=self::timer_labels();
        $script_parameters=array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wt_smart_coupons_apply_coupon'),
            'banner_expired_text' =>(isset($timer_labels['expired']) ? $timer_labels['expired'] : ''),
            'banner_settings_expire_action' => (isset($banner_settings['coupon_timer']["action_on_expiry"]) ? $banner_settings['coupon_timer']["action_on_expiry"] : ''),
            'banner_settings_expire_text' => (isset($banner_settings['coupon_timer']["expiry_text"]) ? $banner_settings['coupon_timer']["expiry_text"] : ''),
        );
        wp_enqueue_script('wt-smart-coupon-banner');
        wp_enqueue_style('wt-smart-coupon-banner');
        wp_localize_script('wt-smart-coupon-banner', 'WTSmartCouponBannerOBJ', $script_parameters);       
    }

    /**
     * Display on specified page automatically
     */
    public function inject_banner()
    {
        $banner_coupon_settings  = self::get_current_banner_settings();
        $banner_coupon_options   = $banner_coupon_settings['inject_coupon'];
        if($banner_coupon_options['enable_inject_coupon'] && !empty($banner_coupon_options['inject_coupon']) && !empty($banner_coupon_options['inject_into_pages']))
        {
            $inject_on_pages = (is_string($banner_coupon_options['inject_into_pages']) ? explode(',', $banner_coupon_options['inject_into_pages']) : $banner_coupon_options['inject_into_pages']);
            
            $current_page_id =  get_the_ID();
            if(is_shop())
            {
                $current_page_id=wc_get_page_id('shop');
            }
            
            $enable_on_this_page = false;
            if((is_front_page() && in_array(0, $inject_on_pages)) || in_array($current_page_id, $inject_on_pages))
            {
                $enable_on_this_page = true;
                $this->enqueue_scripts();
                $banner_coupon_settings['coupon_id']=$banner_coupon_options['inject_coupon'];
                $display_banner_options = $banner_coupon_settings['display_banner'];
                if('banner' == $display_banner_options['banner_type'] && 'custom' == $display_banner_options['banner_postion'])
                {
                    $banner_coupon_settings['display_banner']['banner_postion'] = 'bottom';
                }elseif('widget' == $display_banner_options['banner_type'] && 'custom' == $display_banner_options['widget_postion'])
                {
                    $banner_coupon_settings['display_banner']['widget_postion'] = 'top_left';
                }
                echo self::prepare_banner($banner_coupon_settings);
            }
        }
    }

    /**
     * Banner shortcode callback and arguments validation
     * @since 2.0.0
     */
    public function banner_shortcode($atts)
    {
        if(!isset($atts['coupon_id']))
        {
            return '';
        }

        if(0===absint($atts['coupon_id']))
        {
            return '';
        }
        
        $banner_settings  = self::get_current_banner_settings();
        
        $banner_settings['coupon_id']=(int) $atts['coupon_id'];
        $banner_settings['display_banner']['banner_type']=(isset($atts['banner_type']) ? $atts['banner_type'] : $banner_settings['display_banner']['banner_type']);
        $banner_settings['display_banner']['bg_color']=(isset($atts['bg_color']) ? $atts['bg_color'] : '');
        $banner_settings['display_banner']['border_color']=(isset($atts['border_color']) ? $atts['border_color'] : '');
        $banner_settings['display_banner']['allow_dismissable']=(isset($atts['is_dismissable']) ? $atts['is_dismissable'] : true);
        $banner_settings['display_banner']['action_on_click']=(isset($atts['action_on_click']) ? $atts['action_on_click'] : '');
        $banner_settings['display_banner']['redirect_url']=(isset($atts['redirect_url']) ? $atts['redirect_url'] : '');
        $banner_settings['display_banner']['url_open_in_another_tab']=(isset($atts['url_open_in_another_tab']) ? $atts['url_open_in_another_tab'] : true);
        if('banner'==$banner_settings['display_banner']['banner_type'])
        {
            $banner_settings['display_banner']['banner_postion']=(isset($atts['position']) ? $atts['position'] : $banner_settings['display_banner']['banner_postion']);
        }else{
            $banner_settings['display_banner']['widget_postion']=(isset($atts['position']) ? $atts['position'] : $banner_settings['display_banner']['widget_postion']);
        }

        $banner_settings['banner_title']['enable_title']=(isset($atts['enable_title']) ? $atts['enable_title'] : true);
        $banner_settings['banner_title']['title']=(isset($atts['title']) ? $atts['title'] : '');
        
        $banner_settings['banner_description']['enable_description']=(isset($atts['enable_description']) ? $atts['enable_description'] : true);
        $banner_settings['banner_description']['title']=(isset($atts['description']) ? $atts['description'] : '');
        
        $banner_settings['coupon_section']['enable_coupon_section']=(isset($atts['enable_coupon']) ? $atts['enable_coupon'] : true);
        
        $banner_settings['coupon_timer']['enable_coupon_timer']=(isset($atts['enable_coupon_timer']) ? $atts['enable_coupon_timer'] : true);
        $banner_settings['coupon_timer']['action_on_expiry']=(isset($atts['action_on_expiry']) ? $atts['action_on_expiry'] : '');
        $banner_settings['coupon_timer']['expiry_text']=(isset($atts['expiry_text']) ? $atts['expiry_text'] : '');
        

        $this->enqueue_scripts();     
        return self::prepare_banner($banner_settings);
    }

}
Wt_Smart_Coupon_Banner_Public::get_instance();