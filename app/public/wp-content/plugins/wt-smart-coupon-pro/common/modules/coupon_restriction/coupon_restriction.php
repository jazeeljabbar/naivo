<?php
/**
 * Coupon usage restriction admin/public section
 *
 * @link       
 * @since 2.0.2  
 *
 * @package  Wt_Smart_Coupon  
 */
if (!defined('ABSPATH')) {
    exit;
}
class Wt_Smart_Coupon_Restriction
{
    public $module_base='coupon_restriction';
    public $module_id='';
    public static $module_id_static='';
    private static $instance = null;
    public static $meta_arr=array();
    public function __construct()
    {
        $this->module_id=Wt_Smart_Coupon::get_module_id($this->module_base);
        self::$module_id_static=$this->module_id;

        self::$meta_arr=array(
            '_wt_category_condition'=>array(
                'default'=>'or', /* default value */
                'type'=>'text', /* value type */
            ),'_wt_enable_product_category_restriction'=>array(
                'default'=>'yes',
                'type'=>'text',
            ),'_wt_product_condition'=>array(
                'default'=>'or',
                'type'=>'text',
            ),'_wt_use_individual_min_max'=>array(
                'default'=>'no',
                'type'=>'text',
            ),'_wt_min_matching_product_qty'=>array(
                'default'=>'',
                'type'=>'absint',
            ),'_wt_max_matching_product_qty'=>array(
                'default'=>'',
                'type'=>'absint',
            ),'_wt_min_matching_product_subtotal'=>array(
                'default'=>'',
                'type'=>'float',
            ),'_wt_max_matching_product_subtotal'=>array(
                'default'=>'',
                'type'=>'float',
            ),'_wt_sc_coupon_products'=>array(
                'default'=>array(),
                'type'=>'text_arr',
            ),'_wt_sc_coupon_categories'=>array(
                'default'=>array(),
                'type'=>'text_arr',
            ),'_wt_sc_product_tags' => array( /** @since 2.0.8 */
                'default'   => array(),
                'type'      => 'int_arr',
            ),'_wt_sc_product_attributes' => array( /** @since 2.0.8 */
                'default'   => array(),
                'type'      => 'text_arr',
            ),
            /** @since 2.4.0 */
            '_wt_min_cat_qty'=>array(
                'default' => '', 
                'type' => 'absint',
            ),
            '_wt_max_cat_qty'=>array(
                'default' => '', 
                'type' => 'absint',
            ),
            '_wbte_sc_product_cat_condition' => array( /** @since 3.0.0 */
                'default'   => 'and',
                'type'      => 'text',
            ),
        );

        /**
         *  Register the messages that are customizable via admin panel
         *  @since 2.0.8
         */
        add_filter('wt_sc_intl_add_notifications', array($this, 'register_customized_texts'));
    }

    /**
     * Get Instance
    */
    public static function get_instance()
    {
        if(self::$instance==null)
        {
            self::$instance=new Wt_Smart_Coupon_Restriction();
        }
        return self::$instance;
    }

    /** 
     *  Prepare meta value, If meta not exists, use default value
     *  @since 2.0.4
     *  @param $post_id     int         ID of coupon
     *  @param $post_id     string      Meta key
     *  @param $default     mixed       Default value(Optional). If meta not exists returns the default value
     */
    public static function get_coupon_meta_value($post_id, $meta_key, $default='')
    {
        $default_vl=(isset(self::$meta_arr[$meta_key]) && isset(self::$meta_arr[$meta_key]['default']) ? self::$meta_arr[$meta_key]['default'] : $default);
        return (metadata_exists('post', $post_id, $meta_key) ? get_post_meta($post_id, $meta_key, true) : $default_vl);
    }

    /**
     *  Prepare product/category restriction array. Fills dummy/default data if data is missing for product/catgeory
     *  @since 2.0.4 
     *  @param $item_ids array product/category ids (From WC field)
     *  @param $wt_sc_items_data array An associative array product/category min/max quantity data
     */
    public static function prepare_items_data($item_ids, $wt_sc_items_data)
    {
        $dummy_min_max=self::get_dummy_min_max();
        $items_data=array();
        if(!empty($item_ids)) /* prepare dummy min max data from WC default fields */
        {
            $min_max_dummy=array_fill(0, count($item_ids), $dummy_min_max);
            $items_data=array_combine($item_ids, $min_max_dummy);
        }

        if(!empty($wt_sc_items_data)) /* meta data, merge with WC default product data */
        {
            foreach($items_data as $item_id=>$item_data)
            {
                $items_data[$item_id]=(isset($wt_sc_items_data[$item_id]) ? $wt_sc_items_data[$item_id] : $item_data);
            }
        }

        return $items_data;
    }

    public static function get_dummy_min_max()
    {
        return array('min'=>'', 'max'=>'');
    }

    /**
     *  Register the messages that are customizable via admin panel
     *  
     *  @since  2.0.8
     *  @param  array    $notifications  Array of message info
     *  @return array    Array of message info
     */
    public function register_customized_texts($notifications)
    {
        $notifications['individual_min_quantity'] = array(
            'message'           => sprintf(__('%s requires minimum of %s unit per coupon.', 'wt-smart-coupons-for-woocommerce-pro'), '{cart_item_name}', '{required_quantity}'),
            'description'       => __('Displays when the minimum quantity  requirement for the coupon is not met.', 'wt-smart-coupons-for-woocommerce-pro'),
            'status'            => 1, 
            'supported_placeholders' => array(
                'coupon_code'       => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
                'cart_item_name'    => __('Name of the current cart item', 'wt-smart-coupons-for-woocommerce-pro'),
                'required_quantity' => __('Minimum order quantity', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'available_filters' => array(
                'wt_sc_alter_individual_min_max_quantity_validation_message' => __('Filter to edit individual minimum/maximum quantity validation messages.', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'module'   => 'coupon_restriction',
            'group'         => 'warning',
            'initiater'     => 'sc', //smart coupon
        );


        $notifications['individual_max_quantity'] = array(
            'message'           => sprintf(__('%s is limited to %s unit per coupon.', 'wt-smart-coupons-for-woocommerce-pro'), '{cart_item_name}', '{required_quantity}'),
            'description'       => __('Displays when the product quantity limit per coupon exceeds.', 'wt-smart-coupons-for-woocommerce-pro'),
            'status'            => 1, 
            'supported_placeholders' => array(
                'coupon_code'       => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
                'cart_item_name'    => __('Name of current cart item', 'wt-smart-coupons-for-woocommerce-pro'),
                'required_quantity' => __('Maximum order quantity', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'available_filters' => array(
                'wt_sc_alter_individual_min_max_quantity_validation_message' => __('Filter to edit individual minimum/maximum quantity validation messages.', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'module'   => 'coupon_restriction',
            'group'         => 'warning',
            'initiater'     => 'sc', //smart coupon
        );

        $notifications['individual_min_max_quantity'] = array(
            'message'           => __('Your cart does not meet the quantity criteria for this coupon.', 'wt-smart-coupons-for-woocommerce-pro'),
            'description'       => __('Displays when there are no eligible products for individual minimum/maximum validation in the cart.', 'wt-smart-coupons-for-woocommerce-pro'),
            'status'            => 1, 
            'supported_placeholders' => array(
                'coupon_code'       => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'available_filters' => array(
                'wt_sc_alter_individual_min_max_quantity_validation_message' => __('Filter to edit individual minimum/maximum quantity validation messages.', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'module'   => 'coupon_restriction',
            'group'         => 'warning',
            'initiater'     => 'sc', //smart coupon
        );

        $notifications['product_validation'] = array(
            'message'           => __('Sorry, this coupon is not applicable to selected products.', 'wt-smart-coupons-for-woocommerce-pro'),
            'description'       => __('Displays when there are no eligible products in the cart for the selected coupon code.', 'wt-smart-coupons-for-woocommerce-pro'),
            'status'            => 1, 
            'supported_placeholders' => array(
                'coupon_code'       => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'available_filters' => array(
                
            ),
            'module'   => 'coupon_restriction',
            'group'         => 'warning',
            'initiater'     => 'sc', //smart coupon
        );

        $notifications['tag_validation'] = array(
            'message'           => sprintf(__('Sorry, the coupon %s is not applicable for selected products.', 'wt-smart-coupons-for-woocommerce-pro'), "{coupon_code}"),
            'description'       => __('Displays when the coupon cannot be applied as there are no eligible products with the specified tag in the cart. This is for tag restriction-enabled coupons.', 'wt-smart-coupons-for-woocommerce-pro'),
            'status'            => 1, 
            'supported_placeholders' => array(
                'coupon_code'       => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'available_filters' => array(
                
            ),
            'module'   => 'coupon_restriction',
            'group'         => 'warning',
            'initiater'     => 'sc', //smart coupon
        );

        $notifications['attribute_validation'] = array(
            'message'           => sprintf(__('Sorry, the coupon %s is not applicable for selected products.', 'wt-smart-coupons-for-woocommerce-pro'), "{coupon_code}"),
            'description'       => __('Displays when the coupon cannot be applied as there are no eligible products with the specified attribute in the cart. This is for attribute restriction-enabled coupons.', 'wt-smart-coupons-for-woocommerce-pro'),
            'status'            => 1, 
            'supported_placeholders' => array(
                'coupon_code'       => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'available_filters' => array(
                
            ),
            'module'   => 'coupon_restriction',
            'group'         => 'warning',
            'initiater'     => 'sc', //smart coupon
        );

        $notifications['minimum_subtotal'] = array(
            'message'           => sprintf(__('The minimum subtotal of matching products for this coupon is %s.', 'wt-smart-coupons-for-woocommerce-pro'), '{minimum_subtotal}'),
            'description'       => __('Displays when minimum subtotal value of matching products is not met.', 'wt-smart-coupons-for-woocommerce-pro'),
            'status'            => 1, 
            'supported_placeholders' => array(
                'coupon_code'       => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
                'minimum_subtotal'  => __('Minimum subtotal required', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'available_filters' => array(
                
            ),
            'module'   => 'coupon_restriction',
            'group'         => 'warning',
            'initiater'     => 'sc', //smart coupon
        );

        $notifications['maximum_subtotal'] = array(
            'message'           => sprintf(__('The maximum subtotal of matching products for this coupon is %s.', 'wt-smart-coupons-for-woocommerce-pro'), '{maximum_subtotal}'),
            'description'       => __('Displays when the maximum subtotal value for matching products allowed exceeds.', 'wt-smart-coupons-for-woocommerce-pro'),
            'status'            => 1, 
            'supported_placeholders' => array(
                'coupon_code'       => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
                'maximum_subtotal'  => __('Maximum subtotal required.', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'available_filters' => array(
                'wt_sc_alter_quantity_restriction_messages' => __('Filter to edit global quantity validation messages.', 'wt-smart-coupons-for-woocommerce-pro'),  
            ),
            'module'   => 'coupon_restriction',
            'group'         => 'warning',
            'initiater'     => 'sc', //smart coupon
        );


        $notifications['global_min'] = array(
            'message'           => sprintf(__('Coupon requires a minimum of %s quantity. Please add an eligible number of products to the cart to redeem the coupon.', 'wt-smart-coupons-for-woocommerce-pro'), '{required_quantity}'),
            'description'       => __('Displays when the quantity of eligible products in the cart does not meet the minimum requirement to redeem the coupon.', 'wt-smart-coupons-for-woocommerce-pro'),
            'status'            => 1, 
            'supported_placeholders' => array(
                'coupon_code'       => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
                'required_quantity' => __('Minimum order quantity', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'available_filters' => array(
                'wt_sc_alter_quantity_restriction_messages' => __('Filter to edit global quantity validation messages.', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'module'   => 'coupon_restriction',
            'group'         => 'warning',
            'initiater'     => 'sc', //smart coupon
        );

        $notifications['global_individual_min'] = array(
            'message'           => sprintf(__('To redeem the coupon, each eligible item in the cart requires a minimum %s quantity. Please add more items to the cart.', 'wt-smart-coupons-for-woocommerce-pro'), '{required_quantity}'),
            'description'       => __('Displays when the quantity of individual eligible products in the cart does not meet the minimum requirement to redeem the coupon.', 'wt-smart-coupons-for-woocommerce-pro'),
            'status'            => 1, 
            'supported_placeholders' => array(
                'coupon_code'       => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
                'required_quantity' => __('Minimum order quantity', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'available_filters' => array(
                'wt_sc_alter_quantity_restriction_messages' => __('Filter to edit global quantity validation messages.', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'module'   => 'coupon_restriction',
            'group'         => 'warning',
            'initiater'     => 'sc', //smart coupon
        );


        $notifications['global_max'] = array(
            'message'           => sprintf(__('The maximum quantity of matching products for this coupon is %s.', 'wt-smart-coupons-for-woocommerce-pro'), '{required_quantity}'),
            'description'       => __('Displays when the maximum quantity of matching products allowed exceeds.', 'wt-smart-coupons-for-woocommerce-pro'),
            'status'            => 1, 
            'supported_placeholders' => array(
                'coupon_code'       => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
                'required_quantity' => __('Maximum order quantity', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'available_filters' => array(
                'wt_sc_alter_quantity_restriction_messages' => __('Filter to edit global quantity validation messages.', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'module'   => 'coupon_restriction',
            'group'         => 'warning',
            'initiater'     => 'sc', //smart coupon
        );

        $notifications['global_individual_max'] = array(
            'message'           => sprintf(__('The maximum allowed quantity per eligible item is %s', 'wt-smart-coupons-for-woocommerce-pro'), '{required_quantity}'),
            'description'       => __('Displays when the maximum quantity allowed per eligible item exceeds.', 'wt-smart-coupons-for-woocommerce-pro'),
            'status'            => 1, 
            'supported_placeholders' => array(
                'coupon_code'       => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
                'required_quantity' => __('Maximum order quantity', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'available_filters' => array(
                'wt_sc_alter_quantity_restriction_messages' => __('Filter to edit global quantity validation messages.', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'module'   => 'coupon_restriction',
            'group'         => 'warning',
            'initiater'     => 'sc', //smart coupon
        );

        return $notifications;
    }
}
Wt_Smart_Coupon_Restriction::get_instance();