<?php
/**
 * Exclude Product from coupon admin section
 *
 * @link       
 * @since 2.0.1     
 *
 * @package  Wt_Smart_Coupon  
 */
if (!defined('ABSPATH')) {
    exit;
}
if(!class_exists('Wt_Smart_Coupon_Exclude_Product')) /* common module class not found so return */
{
	return;
}
if(!class_exists('Wt_Smart_Coupon_Exclude_Product_Admin'))
{

	class Wt_Smart_Coupon_Exclude_Product_Admin extends Wt_Smart_Coupon_Exclude_Product
    {
        public $module_base='exclude_product';
        public $module_id='';
        public static $module_id_static='';
        private static $instance = null;
        public function __construct()
        {
            $this->module_id=Wt_Smart_Coupon::get_module_id($this->module_base);
            self::$module_id_static=$this->module_id;

            add_action('woocommerce_product_options_general_product_data', array($this, 'add_exclude_product_check_box'));
        
            add_action('woocommerce_process_product_meta', array($this, 'save_exclude_product_data'), 10, 1);
        }

        /**
         * Get Instance
         * @since 2.0.1
         */
        public static function get_instance()
        {
            if(self::$instance==null)
            {
                self::$instance=new Wt_Smart_Coupon_Exclude_Product_Admin();
            }
            return self::$instance;
        }

        /**
         * Add exclude for coupon settings under product general settings.
         * @since 1.2.1
         */
        public function add_exclude_product_check_box()
        {
            global $post;
            echo '<div class="wt-exclude-product-from-coupon">';
            woocommerce_wp_checkbox( array(
                'id'        => '_wt_disabled_for_coupons',
                'label'     => __('Exclude  from coupons', 'wt-smart-coupons-for-woocommerce-pro'),
                'description' => __('Exclude this product from coupon discounts', 'wt-smart-coupons-for-woocommerce-pro'),
                'desc_tip'  => 'true',
            ) );

            woocommerce_wp_checkbox( array(
                'id'        => '_wt_disabled_for_store_credit',
                'label'     => __('Exclude from store credit', 'wt-smart-coupons-for-woocommerce-pro'),
                'description' => __('Exclude this product from store credit purchases', 'wt-smart-coupons-for-woocommerce-pro'),
                'desc_tip'  => 'true',
            ) );   
            echo '</div>';;
        }


        /**
         * Save exclude Product meta. 
         * @since 1.2.1
         */
        public function save_exclude_product_data($post_id)
        {      
            $current_disabled       = isset($_POST['_wt_disabled_for_coupons']) ? 'yes' : 'no';
            $exclude_store_credit   = isset($_POST['_wt_disabled_for_store_credit']) ? 'yes' : 'no';
            $meta_disabled          = get_post_meta($post_id, '_wt_disabled_for_coupons', true);
            $meta_store_credit      = get_post_meta($post_id, '_wt_disabled_for_store_credit', true);
            $excluded_product       = $this->get_current_settings();

            if(empty($meta_disabled) && $current_disabled == "no" )
            {
                goto saveStoreCredit;
            }
            
            $disabled_products = (is_array($excluded_product['disabled_products']) ? $excluded_product['disabled_products'] : array());
            
            // Save disabled coupons
            if(empty($disabled_products))
            {
                if($current_disabled == 'yes')
                {
                    $disabled_products = array( $post_id );
                }
            }else
            {
                if($current_disabled == 'yes')
                {
                    $disabled_products[] = $post_id;
                    $disabled_products = array_unique( $disabled_products );
                }else
                {
                    if(($key = array_search( $post_id, $disabled_products ) ) !== false )
                    {
                        unset( $disabled_products[$key] );
                    }
                }
            }
            update_post_meta( $post_id, '_wt_disabled_for_coupons', $current_disabled );
            $this->set_disabled_products( $disabled_products );

            saveStoreCredit:

            $disabled_store_credit = (is_array($excluded_product['disabled_store_credits']) ? $excluded_product['disabled_store_credits'] : array());

            /**
             *  Already in `no` and new POST value is also `no` so need to check again, 
             */
            if(empty($meta_store_credit) && "no" === $exclude_store_credit)
            {
                return;
            }

            // Save disabled for Store Credits data
            if("yes" === $exclude_store_credit)
            {
                if(!in_array($post_id, $disabled_store_credit))
                {
                    $disabled_store_credit[] = $post_id;
                }

            }else //`no` condition
            {
                if(($key = array_search($post_id, $disabled_store_credit)) !== false)
                {
                    unset($disabled_store_credit[$key]);
                }
            }
            
            $this->set_disabled_store_credit($disabled_store_credit);
            update_post_meta($post_id, '_wt_disabled_for_store_credit', $exclude_store_credit);
            
        }

    }
    Wt_Smart_Coupon_Exclude_Product_Admin::get_instance();
}