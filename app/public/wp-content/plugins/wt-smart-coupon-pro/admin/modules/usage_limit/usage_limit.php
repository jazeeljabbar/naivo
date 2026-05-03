<?php
/**
 * Coupon usage limit admin area
 *
 * @link       
 * @since 2.1.0
 *
 * @package  Wt_Smart_Coupon  
 */
if (!defined('ABSPATH')) {
    exit;
}

class Wt_Smart_Coupon_Usage_Limit_Admin
{
    public $module_base = 'usage_limit';
    public $module_id = '';
    public static $module_id_static = '';
    private static $instance = null;
    
    public function __construct()
    {
        $this->module_id = Wt_Smart_Coupon::get_module_id($this->module_base);
        self::$module_id_static = $this->module_id;

        /**
         *  Add form fields on coupon usage limit tab in coupon edit page. 
         */
        add_action('woocommerce_coupon_options_usage_limit',array($this, 'coupon_usage_limit_fields'), 10, 2);

        /**
         *  Toggle the visibility of `Max discount field`. (JS code)
         */
        add_action('admin_print_scripts', array($this, 'add_field_toggle_js'), 100);

        /**
         *  Save usage limit field values
         */
        add_action('woocommerce_process_shop_coupon_meta', array($this, 'process_shop_coupon_meta'), 10, 2);


        add_action('woocommerce_order_after_calculate_totals', array($this, 'wt_woocommerce_order_after_calculate_totals'), 100, 2);

    }

    
    /**
     *  Get Instance
     * 
     *  @since 2.1.0
     */
    public static function get_instance()
    {
        if(is_null(self::$instance))
        {
            self::$instance = new Wt_Smart_Coupon_Usage_Limit_Admin();
        }
        return self::$instance;
    }

    
    /**
     *  Add form fields in usage limit tab
     *  
     *  @since 2.1.0
     *  @param int      $post_id    Post ID.
     *  @param object   $post       Post object.
     */
    public function coupon_usage_limit_fields($post_id, $post)
    {
        
        /**
         *  Maximum discount field
         */
        $coupon = new WC_Coupon($post_id);
        $style = ($coupon->is_type('percent') || $coupon->is_type('fixed_product') ? '' : 'style="display:none"');
        $max_discount =  get_post_meta($post_id, '_wt_max_discount', true);

        echo '<div id="wt_max_discount" ' . $style . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped      
            woocommerce_wp_text_input( array(
                'id'                => '_wt_max_discount',
                'label'             => __('Maximum discount value', 'wt-smart-coupons-for-woocommerce-pro' ),
                'placeholder'       => esc_attr__( 'Unlimited discount', 'wt-smart-coupons-for-woocommerce-pro' ),
                'description'       => __( 'Use this option to set a cap on the discount value especially for percentage discounts. e.g, you may provide a 5 percentage discount coupon for a product but with a maximum discount upto $10.', 'wt-smart-coupons-for-woocommerce-pro' ),
                'type'              => 'number',
                'desc_tip'          => true,
                'class'             => 'short',
                'custom_attributes' => array(
                    'step'  => 1,
                    'min'   => 0,
                ),
                'value' => ($max_discount ? $max_discount : ''),
            ) );
        echo '</div>';


        /**
         *  Allow once per product checkbox.
         *  
         *  @since 2.1.0
         */
        woocommerce_wp_checkbox(
            array(
                'id'          => '_wt_sc_allow_once_per_product',
                'label'       => __('Restrict usage to, once per product', 'wt-smart-coupons-for-woocommerce-pro'),
                'description' => __('Enable to restrict coupon usage if the coupon has been used to purchase the same product before.', 'wt-smart-coupons-for-woocommerce-pro'),
                'value'       => get_post_meta($post_id, '_wt_sc_allow_once_per_product', true),
            )
        );
    
    }
    
    
    /**
     *  Add JS code block to toggle `Max discount amount` field.
     *  Hooked into: admin_print_scripts 
     * 
     *  @since 2.1.0
     */
    public function add_field_toggle_js()
    {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function(){
                function wt_sc_toggle_max_discount_amount_field(elm)
                {
                    if('percent' === elm.val() || 'fixed_product' === elm.val())
                    {
                        jQuery('#wt_max_discount').show();
                    }else
                    {
                        jQuery('#wt_max_discount').hide();
                    }
                }

                jQuery('#discount_type').on('change',function(){
                    wt_sc_toggle_max_discount_amount_field(jQuery(this));
                });

                wt_sc_toggle_max_discount_amount_field(jQuery('#discount_type'));
            });
        </script>
        <?php
    }

    
    /**
     *  Save maximum discount meta.
     *  Hooked into: woocommerce_process_shop_coupon_meta
     * 
     *  @since 2.1.0
     */
    public function process_shop_coupon_meta( $coupon_id, $coupon )
    { 
        update_post_meta($coupon_id, '_wt_max_discount', wc_format_decimal($_POST['_wt_max_discount']));  
        
        $allow_once_per_product = isset($_POST['_wt_sc_allow_once_per_product']) ? sanitize_text_field($_POST['_wt_sc_allow_once_per_product']) : 'no';
        update_post_meta($coupon_id, '_wt_sc_allow_once_per_product', $allow_once_per_product);  
    }

    
    /**
     *  Control the percentage coupon max limit when applying coupon via backend.
     * 
     *  @since 2.1.0
     */
    public function wt_woocommerce_order_after_calculate_totals($taxes, $order)
    {
        if(!is_admin())
        {
            return;
        }        

        if(!empty($order_coupons = $order->get_coupons()))
        {
            $total_discount = 0;
            $order_discount_total = $order->get_discount_total();
            $order_discount_total_backup = $order_discount_total; //take a backup for comparing

            foreach($order_coupons as $key => $order_coupon) //loop through the order coupons
            {

                $coupon_code = $order_coupon->get_code();
                $coupon_id   = wc_get_coupon_id_by_code($coupon_code);

                if(0 === $coupon_id) //coupon not exists
                {
                    continue;
                }

                $coupon = new WC_Coupon($coupon_id);
            
                if(!$coupon->is_type('percent')) //not a percentage coupon
                {
                    continue;
                }

                $max_discount    = get_post_meta($coupon_id, '_wt_max_discount', true);
                $coupon_discount = $order_coupon->get_discount();

                if(!empty($max_discount) && $max_discount < $coupon_discount) //max restriction enabled.
                {
                    $order_discount_total -= ($coupon_discount - $max_discount); //deduct the extra calculated amount
                }
            
            }

            if($order_discount_total_backup > $order_discount_total) //An extra amount is found. So need to update
            {
                $order->set_discount_total($order_discount_total); //set order discount total

                $order_total = $order->get_total();
                $order->set_total($order_total + ($order_discount_total_backup - $order_discount_total)); //set order total
                
            }
        }
    }
}
Wt_Smart_Coupon_Usage_Limit_Admin::get_instance();