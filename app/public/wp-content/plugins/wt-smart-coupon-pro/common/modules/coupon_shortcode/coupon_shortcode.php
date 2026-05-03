<?php
/**
 * Coupon shortcode admin/public section.
 *
 * @link       
 * @since 2.0.1     
 *
 * @package  Wt_Smart_Coupon  
 */
if (!defined('ABSPATH')) {
    exit;
}

class WT_Smart_Coupon_Shortcode
{
    public $module_base='coupon_shortcode';
    public $module_id='';
    public static $module_id_static='';
    private static $instance = null;
    private static $coupon_css_added = false;
    
    public function __construct()
    {
        $this->module_id=Wt_Smart_Coupon::get_module_id($this->module_base);
        self::$module_id_static=$this->module_id;
        add_shortcode('wt-smart-coupon', array($this, 'display_coupon'));

        /**
         *  @since 2.0.5
         *  Shortcode for user available coupons
         */
        add_shortcode('wt_sc_user_available_coupons', array($this, 'display_user_available_coupons'));
    }

    /**
     * Get Instance
     */
    public static function get_instance()
    {
        if(self::$instance==null)
        {
            self::$instance=new WT_Smart_Coupon_Shortcode();
        }
        return self::$instance;
    }

    /**
     *  Display coupon by shortcode
     * 
     *  @since 1.2.1
     *  @since 2.0.9    Code updated
     */
    public function display_coupon($atts)
    {

        if(!$atts['id'] || 'publish' !== get_post_status($atts['id']) || 'shop_coupon' !== get_post_type($atts['id']))
        {
            return __('Invalid coupon', 'wt-smart-coupons-for-woocommerce-pro');
        }
        

        $coupon_title = get_the_title($atts['id']);
        $coupon = new WC_Coupon($atts['id']); 
        
        $coupon_data  = Wt_Smart_Coupon_Public::get_coupon_meta_data($coupon);
        $coupon_data['display_on_page'] = 'by_shortcode';

        $coupon_html = '';
        $include_coupon_css = false;

        if(!self::$coupon_css_added) //css not added
        {
            ob_start();
            Wt_Smart_Coupon_Public::print_coupon_default_css();
            $coupon_html = ob_get_clean(); //add css

            $include_coupon_css = true;  //add template css along with HTML

            self::$coupon_css_added = true; //mark it as added to avoid duplicate
        }

        $coupon_type = "available_coupon";

        if( $user_id = get_current_user_id() ){
            $data_store  = $coupon->get_data_store();
            $usage_count = $data_store->get_usage_by_user_id( $coupon, $user_id );
            if( 0 < $usage_count ){
                $coupon_type = "used_coupon";
            }
        }

        if(Wt_Smart_Coupon_Admin::module_exists('coupon_lifespan')){
            $expired = Wt_Smart_Coupon_Lifespan::get_instance()->is_coupon_expired($coupon);

            if($expired){
                $coupon_type = "expired_coupon";
            }

        }

        $coupon_html .= Wt_Smart_Coupon_Public::get_coupon_html($coupon, $coupon_data, $coupon_type, $include_coupon_css);
        return $coupon_html;
    }

    
    /**
     *  Display available coupons by shortcode
     *  @since 2.0.5
     */
    public function display_user_available_coupons($atts)
    {
        if(is_null(WC()->cart))
        {
            return '';
        }

        $default_sort_order=Wt_Smart_Coupon_Public::get_available_coupons_sort_order();

        $attributes = shortcode_atts(array(
            'section' =>'',
            'coupons_per_page' =>20,
            'orderby' =>$default_sort_order,
        ), $atts);

        $offset=(isset($_GET['wt_sc_available_coupons_offset']) ? absint($_GET['wt_sc_available_coupons_offset']) : 0);
        if($attributes['orderby']!==$default_sort_order)
        {
            $_GET['wt_sc_available_coupons_orderby']=$attributes['orderby'];
        }
        
        ob_start();
        Wt_Smart_Coupon_Public::print_user_available_coupon('', $attributes['section'], $offset, $attributes['coupons_per_page'], true, true);
        return ob_get_clean();
    }

}

WT_Smart_Coupon_Shortcode::get_instance();