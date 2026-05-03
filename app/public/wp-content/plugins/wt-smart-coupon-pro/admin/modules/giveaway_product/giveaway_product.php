<?php
/**
 * Giveaway products admin section
 *
 * @link       
 * @since 2.0.1     
 *
 * @package  Wt_Smart_Coupon  
 */
if (!defined('ABSPATH')) {
    exit;
}
if(!class_exists('Wt_Smart_Coupon_Giveaway_Product')) /* common module class not found so return */
{
    return;
}
class Wt_Smart_Coupon_Giveaway_Product_Admin extends Wt_Smart_Coupon_Giveaway_Product
{
    public $module_base='giveaway_product';
    public $module_id='';
    public static $module_id_static='';
    private static $instance = null;
    public function __construct()
    {
        $this->module_id=Wt_Smart_Coupon::get_module_id($this->module_base);
        self::$module_id_static=$this->module_id;

        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts_styles'), 10, 0);

        /**
         *  Add fields on coupon general settings
         */
        add_action('woocommerce_coupon_options', array($this, 'add_general_settings_fields'), 6, 2);

        add_filter('woocommerce_coupon_data_tabs', array($this, 'add_give_way_coupon_data_tab'), 21, 1);
        add_action('woocommerce_coupon_data_panels', array($this, 'give_away_free_product_tab_content'), 10, 1);
        add_action('woocommerce_process_shop_coupon_meta', array($this, 'process_shop_coupon_meta_give_away'), 10, 2);
        add_action('wp_ajax_woocommerce_json_search_products_and_variations_without_parent', array($this, 'wt_products_and_variations_no_parent'));
      

        /* Giveaway details into order detail table */
        add_action('woocommerce_admin_order_totals_after_tax', array($this, 'add_giveaway_info_to_order_detail_table'));

        /**
         *  Help text for coupon restriction section
         *  @since  2.0.6
         */
        add_filter('wt_sc_intl_alter_discount_type_help_arr', array($this, 'add_discount_type_help_text'), 10, 2);  
    
        /**
         *  Process BOGO meta data before importing
         *  @since  2.0.7
         */
        add_filter('wt_sc_import_alter_coupon_meta_data', array($this, 'process_meta_data_before_import'));
    
        
        /**
         *  Usage restriction tab fields
         *  
         *  @since 2.0.8
         */
        add_action('wt_sc_intl_after_usage_restriction_tab_content', array($this, 'usage_restriction_tab_fields'));

        
        /**
         *  `any_product_from_category` condition migration
         * 
         *  @since 2.1.1
         */       
        // Check for the `any_product_from_category` condition migrated message is configured or not
        add_action('admin_init', array($this, 'check_any_product_from_category_condition_is_migrated'));

        // Show `any_product_from_category` condition migrated message
        add_action('admin_notices', array($this, 'any_product_from_category_condition_migrated_message'));

        // Ajax hook to hide the message
        add_action('wp_ajax_wt_sc_hide_any_product_from_category_condition_migrated_msg', array($this, 'hide_any_product_from_category_condition_migrated_message'));
    
        
    }

    /**
     * Get Instance
     */
    public static function get_instance()
    {
        if(self::$instance==null)
        {
            self::$instance=new Wt_Smart_Coupon_Giveaway_Product_Admin();
        }
        return self::$instance;
    }


    /**
     *  Save giveaway related meta data
     */
    public function process_shop_coupon_meta_give_away($post_id, $post)
    {
        
        if( !class_exists( 'Wt_Smart_Coupon_Security_Helper' ) || !method_exists( 'Wt_Smart_Coupon_Security_Helper', 'check_user_has_capability' ) || !Wt_Smart_Coupon_Security_Helper::check_user_has_capability() ) 
        {
            wp_die(__('You do not have sufficient permission to perform this operation', 'wt-smart-coupons-for-woocommerce-pro'));
        }

        /* Product data */
        $bogo_free_products=$this->prepare_meta_data_from_post_data('_wt_sc_bogo_free_product_ids', '_wt_sc_bogo_free_product_qty', '_wt_sc_bogo_free_product_price', '_wt_sc_bogo_free_product_price_type');
        update_post_meta($post_id, '_wt_sc_bogo_free_products', $bogo_free_products);

        
        /** 
         * Category data 
         */            
        if( isset($_POST['_wt_sc_bogo_customer_gets']) && 'any_product_from_category' === sanitize_text_field($_POST['_wt_sc_bogo_customer_gets']) ) {
            
            $coupon_code = wc_sanitize_coupon_code( wc_get_coupon_code_by_id($post_id) );
           
            if('any' === $this->get_any_product_from_category_condition($coupon_code))
            {
                // On `any` condition of `any_product_from_category`, we are not providing category specific discount. So we are manually assigning the value from global data.   
                $bogo_cat_ids = isset($_POST['_wt_sc_bogo_free_category_ids']) && is_array($_POST['_wt_sc_bogo_free_category_ids']) ? wc_clean($_POST['_wt_sc_bogo_free_category_ids']) : array();
                $bogo_cat_id_count = count($bogo_cat_ids);

                $quantity = isset($_POST['_wt_product_discount_quantity']) ? absint($_POST['_wt_product_discount_quantity']) : 1;
                $_POST['_wt_sc_bogo_free_category_qty'] = array_fill(0, $bogo_cat_id_count, $quantity);

                $price = isset($_POST['_wt_product_discount_amount']) ? floatval($_POST['_wt_product_discount_amount']) : 100;
                $_POST['_wt_sc_bogo_free_category_price'] = array_fill(0, $bogo_cat_id_count, $price);
                
                $price_type = isset($_POST['_wt_product_discount_type']) ? sanitize_text_field($_POST['_wt_product_discount_type']) : 'percent';
                $_POST['_wt_sc_bogo_free_category_price_type'] = array_fill(0, $bogo_cat_id_count, $price_type);
            
            } else { // Updating the first category data as global discount data

                $wt_sc_bogo_free_categories = $this->prepare_meta_data_from_post_data('_wt_sc_bogo_free_category_ids', '_wt_sc_bogo_free_category_qty', '_wt_sc_bogo_free_category_price', '_wt_sc_bogo_free_category_price_type');
                $first_cat_data = reset($wt_sc_bogo_free_categories);
                
                $_POST['_wt_product_discount_quantity'] = isset($first_cat_data['qty']) ? $first_cat_data['qty'] : 1;
                $_POST['_wt_product_discount_amount'] = isset($first_cat_data['price']) ? $first_cat_data['price'] : 100;
                $_POST['_wt_product_discount_type'] = isset($first_cat_data['price_type']) ? $first_cat_data['price_type'] : 'percent';

            }
        }
        
        $wt_sc_bogo_free_categories = !isset($wt_sc_bogo_free_categories) ? $this->prepare_meta_data_from_post_data('_wt_sc_bogo_free_category_ids', '_wt_sc_bogo_free_category_qty', '_wt_sc_bogo_free_category_price', '_wt_sc_bogo_free_category_price_type') : $wt_sc_bogo_free_categories;
        update_post_meta($post_id, '_wt_sc_bogo_free_categories', $wt_sc_bogo_free_categories);


        // Giveaway free Products.

        if(isset($_POST['_wt_free_product_ids']) && $_POST['_wt_free_product_ids']!='')
        {
            $free_product_ids=Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST['_wt_free_product_ids'], 'int_arr');
            update_post_meta($post_id, '_wt_free_product_ids', implode(',', $free_product_ids));
        }else
        {
            update_post_meta($post_id, '_wt_free_product_ids', '');
        }

        if(isset($_POST['wt_apply_discount_before_tax_calculation']) && $_POST['wt_apply_discount_before_tax_calculation'] =='yes')
        {
            update_post_meta($post_id, 'wt_apply_discount_before_tax_calculation', 1);
        }else
        {
            update_post_meta($post_id, 'wt_apply_discount_before_tax_calculation', 0);
        }
        
        /**
         *  Limit the percentage value to max 100
         */
        if(isset($_POST['_wt_product_discount_amount']) && isset($_POST['_wt_product_discount_type']) && 'percent'===$_POST['_wt_product_discount_type'])
        {
            $discount_amount=floatval($_POST['_wt_product_discount_amount']);
            $_POST['_wt_product_discount_amount']=min($discount_amount, 100);
        }

        /**
         *  Set giveaway product condition to or/any for non-bogo coupons
         */
        if( isset( $_POST['discount_type'] ) && ( Wt_Smart_Coupon_Giveaway_Product::$bogo_coupon_type_name !== $_POST['discount_type'] ) && isset( $_POST['_wt_sc_bogo_product_condition'] ) ){
            $_POST['_wt_sc_bogo_product_condition'] = 'or' ;
        }


        $skip_post_arr=array(
            '_wt_sc_bogo_free_categories', '_wt_sc_bogo_free_products', '_wt_free_product_ids', 'wt_apply_discount_before_tax_calculation'
        ); /* fields that skip from below meta data update loop */
        
        foreach(self::$meta_arr as $mata_key=>$meta_info)
        {
            if(in_array($mata_key, $skip_post_arr))
            {
                continue; // already updated via above code block
            }
            if(isset($_POST[$mata_key]) && !empty($_POST[$mata_key]))
            {
                if(isset($meta_info['type']))
                {
                    if('absint'==$meta_info['type'])
                    {
                        $val=absint($_POST[$mata_key]);
                    }elseif('float'==$meta_info['type'])
                    {
                        $val=floatval($_POST[$mata_key]);
                    }elseif('boolean'==$meta_info['type'])
                    {
                        $val=boolval($_POST[$mata_key]);
                    }else
                    {
                        $val=sanitize_text_field($_POST[$mata_key]);
                    }
                }else{
                    $val=sanitize_text_field($_POST[$mata_key]);
                }
                update_post_meta($post_id, $mata_key, $val);

            }else
            {
                $default=(isset($meta_info['default']) ? $meta_info['default'] : '');
                update_post_meta($post_id, $mata_key, $default);
            }
        }
    }

    /**
     *  Enqueue Scripts and Styles
     */
    public function enqueue_scripts_styles()
    {
        $screen    = get_current_screen();
        $screen_id = $screen ? $screen->id : '';

        $screen_id_arr=array('shop_coupon', 'smart-coupons_page_wt-smart-coupon-for-woo_bulk_generate');
        $screen_id_arr=apply_filters('wt_sc_giveaway_admin_assets_screen_ids', $screen_id_arr);
        
        if(in_array($screen_id, $screen_id_arr))
        {
            wp_enqueue_style($this->module_id.'_coupon_edit', plugin_dir_url(__FILE__).'assets/css/main.css', array(), WEBTOFFEE_SMARTCOUPON_VERSION, 'all');
            
            wp_enqueue_script($this->module_id.'_coupon_edit', plugin_dir_url(__FILE__).'assets/js/main.js', array('jquery', WT_SC_PLUGIN_NAME), WEBTOFFEE_SMARTCOUPON_VERSION, false);

            $script_parameters=array(
                'bogo_coupon_type'=>self::$bogo_coupon_type_name,
                'msgs'=>array(  
                )
            );
            wp_localize_script($this->module_id.'_coupon_edit', 'wt_sc_giveaway_params', $script_parameters);
        }
    }

    public function add_general_settings_fields($post_id = 0, $coupon = null)
    {
        echo '<div class="options_group" style="border:none;">';
        woocommerce_wp_checkbox(
            array(
                'id'      => '_wt_sc_bogo_apply_frequency',
                'class'     => 'wt_sc_bogo_apply_frequency',
                'label'     => __('Apply BOGO repeatedly', 'wt-smart-coupons-for-woocommerce-pro'),
                'cbvalue'   => 'repeat',
                'description' => __("Enable for consecutive BOGO deals. When 'Buy One Get Two' is set, it will work for 'Buy 2 Get 4' and onwards.", 'wt-smart-coupons-for-woocommerce-pro'),
            )
        );
        echo '</div>';
    }

    public function add_give_way_coupon_data_tab( $tabs )
    {
        $tabs['wt_give_away_free_product'] = array(
            'label'  => __('Giveaway products', 'wt-smart-coupons-for-woocommerce-pro'),
            'target' => 'wt_give_away_free_products',
            'class'  => '',
        );

        return $tabs;
    }

    /**
     * Giveaway Product tab content
     */
    public function give_away_free_product_tab_content($post_id = 0)
    {
        $free_product_id_arr = self::get_giveaway_products($post_id);
        $coupon_code = wc_sanitize_coupon_code(wc_get_coupon_code_by_id($post_id));

        /**
         * Add Quantity control for giveaway
         * @since 1.2.6
         */
        $discount_quantity  = $this->get_coupon_meta_value($post_id, '_wt_product_discount_quantity');
        $discount_amount    = $this->get_coupon_meta_value($post_id, '_wt_product_discount_amount');
        $discount_type      = $this->get_coupon_meta_value($post_id, '_wt_product_discount_type');
        
        $wt_apply_discount_before_tax_calculation = $this->get_coupon_meta_value($post_id, 'wt_apply_discount_before_tax_calculation');      

        $dummy_qty_price = self::get_dummy_qty_price();
        $bogo_customer_gets = $this->get_coupon_meta_value($post_id, '_wt_sc_bogo_customer_gets');
        $bogo_product_condition = $this->get_coupon_meta_value($post_id, '_wt_sc_bogo_product_condition');
        
        $bogo_free_products = $this->get_coupon_meta_value($post_id, '_wt_sc_bogo_free_products');
        $bogo_products_data = self::prepare_items_data($free_product_id_arr, $bogo_free_products);

        $bogo_free_categories = $this->get_coupon_meta_value($post_id, '_wt_sc_bogo_free_categories'); 
        $category_condition = $this->get_any_product_from_category_condition( $coupon_code );

        if ( 'any' === $category_condition ) { // For backward compatibility. Previously categories have individual quantity
            $discount_data      = is_array( $bogo_free_categories ) ? reset( $bogo_free_categories ) : array(); // Take the first category data
            $discount_quantity  = isset($discount_data['qty']) ? $discount_data['qty'] : $discount_quantity;
            $discount_amount    = isset($discount_data['price']) ? $discount_data['price'] : $discount_amount;
            $discount_type      = isset($discount_data['price_type']) ? $discount_data['price_type'] : $discount_type;
        }

        
        /**
         *  Cheapest item as giveaway
         *  
         *  @since 2.0.7
         */
        $cheapest_item_as_bogo = $this->get_coupon_meta_value($post_id, '_wt_sc_cheapest_item_as_giveaway');


        /** 
         *  Convert existing item as giveaway.
         * 
         *  @since 2.2.0  
         */ 
        $convert_existing_as_giveaway = $this->get_coupon_meta_value($post_id, '_wt_sc_convert_existing_as_giveaway'); 

        include_once plugin_dir_path(__FILE__).'views/giveaway_tab_content.php';       
    }

    /**
     * Alter product search - exclude parent product from list (Only for non BOGO coupons)
     * @since 1.2.4
     */
    public function wt_products_and_variations_no_parent()
    {
        check_ajax_referer( 'search-products', 'security' );
        if ( !class_exists( 'Wt_Smart_Coupon_Security_Helper' ) || !method_exists( 'Wt_Smart_Coupon_Security_Helper', 'check_user_has_capability' ) || !Wt_Smart_Coupon_Security_Helper::check_user_has_capability() ) 
        {
            wp_die(__('You do not have sufficient permission to perform this operation', 'wt-smart-coupons-for-woocommerce-pro'));
        }
        add_filter('woocommerce_json_search_found_products', array($this, 'exclude_parent_product_from_search'), 10, 1);
        
        WC_AJAX::json_search_products('', true);        
    }

    /**
     * Exclude Parent Product from product search
     * @since 1.2.4
     */
    public function exclude_parent_product_from_search($products)
    {
        foreach($products as $product_id =>$product)
        {
            $product_obj = wc_get_product($product_id);
            if($product_obj->has_child())
            {
                unset($products[$product_id]);
            }
        }
        return $products;
    }

    public function add_giveaway_info_to_order_detail_table($order_id)
    {
        $order=new WC_Order($order_id);
        $order_items = $order->get_items();
        foreach($order_items as $order_item_id=>$order_item)
        {
            $giveaway_info = $this->prepare_giveaway_info_for_order($order_item_id, $order_item, $order);
            
            if($giveaway_info)
            {
                $coupon_code    = wc_get_order_item_meta($order_item_id, 'free_gift_coupon', true);
                $label_text     = $this->get_customized_text('giveaway_order_summary_label', array('coupon_code' => $coupon_code));
                $label_text     = apply_filters('wt_sc_alter_order_detail_giveaway_info_label', $label_text, $order_item, $order_item_id, $order);
                ?>
                <tr>
                    <td class="label"><?php echo wp_kses_post($label_text); ?></td>
                    <td width="1%"></td>
                    <td class="total"><?php echo wp_kses_post($giveaway_info); // WPCS: XSS ok. ?></td>
                </tr>
                <?php
            }
        }
    }

    /**
     *  Help text for coupon restriction section
     *  @since  2.0.6
     *  @param  array   help text array
     *  @param  string  for which filed, default `product`. 
     *                  Possible values: product, exclude_product, category, exclude_category
     */
    public function add_discount_type_help_text($help_text_arr, $type = 'product')
    { 
        if('product' === $type)
        {
            $help_text_arr[self::$bogo_coupon_type_name] = __('Apply coupon only if the selected product quantity is in the cart. Discounts will be given for those products and not the total cart amount. For example, for setting up Buy X Get Y, choose the product/s X in this section.', 'wt-smart-coupons-for-woocommerce-pro');
        
        }elseif('category' === $type)
        {
            $help_text_arr[self::$bogo_coupon_type_name] = __('Apply coupon only if the selected quantity of products of the chosen category are in the cart. Discounts will be given for those products and not the total cart amount.', 'wt-smart-coupons-for-woocommerce-pro');
        
        }elseif('exclude_product' === $type || 'exclude_category' === $type)
        {
            $out = array();

            foreach($help_text_arr as $help_text_arr_key => $help_text_arr_val)
            {
                if(false !== stristr($help_text_arr_key, 'fixed_cart'))
                {
                    $out[$help_text_arr_key.'|'.self::$bogo_coupon_type_name] = $help_text_arr_val;
                }else
                {
                    $out[$help_text_arr_key] = $help_text_arr_val;
                }
            }

            $help_text_arr = $out;
        }

        return $help_text_arr;
    }

    private function prepare_meta_data_from_post_data($id_key, $qty_key, $price_key, $price_type_key)
    {
        $item_ids=(isset($_POST[$id_key]) && is_array($_POST[$id_key]) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST[$id_key], 'int_arr') : array());
        $item_qty=(isset($_POST[$qty_key]) && is_array($_POST[$qty_key]) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST[$qty_key], 'text_arr') : array()); //use text_arr as validation type
        $item_price=(isset($_POST[$price_key]) && is_array($_POST[$price_key]) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST[$price_key], 'text_arr') : array());
        $item_price_type=(isset($_POST[$price_type_key]) && is_array($_POST[$price_type_key]) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST[$price_type_key], 'text_arr') : array());
        
        return $this->prepare_meta_data_for_db($item_ids, $item_qty, $item_price, $item_price_type);
    }

    /**
     *  Prepare BOGO products/categories meta data for DB saving
     * 
     *  @since 2.0.7
     * 
     */
    private function prepare_meta_data_for_db($item_ids, $item_qty, $item_price, $item_price_type)
    {
        $item_data=array();
        $allowed_price_types = array('percent', 'flat');

        foreach($item_ids as $i=>$item_id)
        {
            $price_type = (isset($item_price_type[$i]) ? $item_price_type[$i] : 'percent');
            $price_type = (in_array($price_type, $allowed_price_types) ? $price_type : 'percent');

            $price = (isset($item_price[$i]) ? (float) $item_price[$i] : '');
            
            if('percent' === $price_type && "" !== $price)
            {
                $price = min($price, 100);
            }

            $item_data[$item_id]=array(
                'qty'=>(isset($item_qty[$i]) && $item_qty[$i] ? $item_qty[$i] : 1),
                'price'=>$price,
                'price_type'=>$price_type,
            );
        }

        return $item_data;
    }

    /**
     *  Prepare and add an associative array of BOGO giveaway products/categories
     * 
     *  @since 2.0.7
     * 
     */
    private function prepare_meta_data_from_csv_data(&$coupon_meta_data, $id_key, $qty_key, $price_key, $price_type_key, $main_data_key)
    {
        if(isset($coupon_meta_data[$id_key]))
        {
           $item_ids = explode(",", $coupon_meta_data[$id_key]);
           $item_id_length = count($item_ids); 
           $item_qty = isset($coupon_meta_data[$qty_key]) ? explode(",", $coupon_meta_data[$qty_key]) : array_fill(0, $item_id_length, 1); 
           $item_price = isset($coupon_meta_data[$price_key]) ? explode(",", $coupon_meta_data[$price_key]) : array_fill(0, $item_id_length, 100); 
           $item_price_type = isset($coupon_meta_data[$price_type_key]) ? explode(",", $coupon_meta_data[$price_type_key]) : array_fill(0, $item_id_length, 'percent');

           /* set value for new meta key */
           $coupon_meta_data[$main_data_key] = $this->prepare_meta_data_for_db($item_ids, $item_qty, $item_price, $item_price_type);

           /* reset values of supporting meta keys */
           unset($coupon_meta_data[$id_key], $coupon_meta_data[$qty_key], $coupon_meta_data[$price_key], $coupon_meta_data[$price_type_key]);
        }
    }

    /**
     *  Process giveaway meta data before importing
     *  
     *  @since 2.0.7
     *  @param array  $coupon_meta_data  An associative array of meta key and data
     *  @return array  $coupon_meta_data  Processed meta data array
     */
    public function process_meta_data_before_import($coupon_meta_data)
    {
        //$coupon_meta_data is a reference variable for below function
        $this->prepare_meta_data_from_csv_data($coupon_meta_data, '_wt_sc_bogo_free_product_ids', '_wt_sc_bogo_free_product_qty', '_wt_sc_bogo_free_product_price', '_wt_sc_bogo_free_product_price_type', '_wt_sc_bogo_free_products');
        $this->prepare_meta_data_from_csv_data($coupon_meta_data, '_wt_sc_bogo_free_category_ids', '_wt_sc_bogo_free_category_qty', '_wt_sc_bogo_free_category_price', '_wt_sc_bogo_free_category_price_type', '_wt_sc_bogo_free_categories');

        return $coupon_meta_data;  
    }


    /**
     *  Add fields to coupon restriction tab.
     *  Hooked into `wt_sc_intl_after_usage_restriction_tab_content`
     * 
     *  @since 2.0.8
     *  @param int  $post_id Coupon id  
     */
    public function usage_restriction_tab_fields($post_id)
    {
        /**
         *  These fields are using on `Any product from same category as in cart` BOGO option
         * 
         *  @since 2.0.8
         */
        
        /* Minimum quantity of products under each category */
        woocommerce_wp_text_input(
            array(
                'id'          => '_wt_min_cat_qty',
                'label'       => __( 'Minimum quantity for each category', 'wt-smart-coupons-for-woocommerce-pro'),
                'placeholder' => __( 'No minimum', 'wt-smart-coupons-for-woocommerce-pro'),
                'description' => __( 'Minimum quantity of products under each category.', 'wt-smart-coupons-for-woocommerce-pro'),
                'data_type'   => 'decimal',
                'desc_tip'    => true,
            )
        );

        /* Maximum quantity of products under each category */
        woocommerce_wp_text_input(
            array(
                'id'          => '_wt_max_cat_qty',
                'label'       => __( 'Maximum quantity for each category', 'wt-smart-coupons-for-woocommerce-pro' ),
                'placeholder' => __( 'No maximum', 'woocommerce' ),
                'description' => __( 'Maximum quantity of products under each category.', 'wt-smart-coupons-for-woocommerce-pro' ),
                'data_type'   => 'decimal',
                'desc_tip'    => true,
            )
        );

    }


    /**
     *  Check for the `any_product_from_category` condition migrated message is configured or not
     *  Hooked into `admin_init` 
     * 
     *  @since 2.1.1
     */
    public function check_any_product_from_category_condition_is_migrated()
    {
        if ( false === get_option( 'wt_sc_show_any_product_from_category_migrated_msg' ) ) { // First time after new update/activation

            global $wpdb;

            if ( 
                $wpdb->get_var( 
                    $wpdb->prepare( 
                        "SELECT
                            COUNT(a.post_id)
                        FROM
                            {$wpdb->postmeta} AS a
                        LEFT JOIN {$wpdb->postmeta} AS b
                        ON
                            (a.post_id = b.post_id)
                        WHERE
                            a.meta_key = %s AND a.meta_value = %s AND b.meta_key = %s AND b.meta_value = %s", 
                            'discount_type',
                            self::$bogo_coupon_type_name,
                            '_wt_sc_bogo_customer_gets', 
                            'any_product_from_category' 
                    ) 
                ) 
            ) { // BOGO coupon with `any_product_from_category` option exists

                add_option( 'wt_sc_show_any_product_from_category_migrated_msg', 1 ); // Set show message flag

            } else { // No BOGO coupon with `any_product_from_category` option exists. So it's not require to show the message.
                
                add_option( 'wt_sc_show_any_product_from_category_migrated_msg', 2 ); // Set hide message flag
            } 

        }
    }


    /**
     *  Show category condition migrated message.
     *  The message only shows in the plugins page, coupon edit page and smart coupon settings page.
     *  
     *  @since 2.1.1
     */
    public function any_product_from_category_condition_migrated_message()
    {
        global $pagenow, $post;
        
        $is_show_migrated_msg = get_option( 'wt_sc_show_any_product_from_category_migrated_msg' );

        if ( ( 
                'plugins.php' === $pagenow // Plugins page
                || ( 'admin.php' === $pagenow && isset($_GET['page']) && WT_SC_PLUGIN_NAME === sanitize_text_field( $_GET['page'] ) )  // Smart coupon settings page
                || ( ! is_null( $post ) && is_a( $post, ' WP_Post' ) && 'shop_coupon' === $post->post_type )  // Coupon edit page
            )  
            && ( 1 === $is_show_migrated_msg || '1' === $is_show_migrated_msg ) // Message show flag is enabled
        ) {

            ?>
            <div class="notice notice-info wt_sc_any_product_from_category_condition_migrated_msg_notice">             
                <h4 style="margin-bottom:10px;"><?php esc_html_e('"Any product from specific category" option of BOGO coupons has been changed!', 'wt-smart-coupons-for-woocommerce-pro');?></h4>
                <p>
                    <?php esc_html_e("To improve performance and reduce conflicts, we've removed the individual quantity and discount controls for categories and added common controls instead. We appreciate your understanding and cooperation.", 'wt-smart-coupons-for-woocommerce-pro');?>
                </p>
                <p>
                    <button type="button" class="button-secondary wt_sc_any_product_from_category_condition_migrated_msg_btn"><?php esc_html_e('I understand', 'wt-smart-coupons-for-woocommerce-pro'); ?></button>
                </p>
            </div>

            <script type="text/javascript">
                jQuery(document).ready(function(){
                    jQuery('.wt_sc_any_product_from_category_condition_migrated_msg_btn').on('click', function() {
                        
                        jQuery('.wt_sc_any_product_from_category_condition_migrated_msg_notice').fadeOut('slow');
                        
                        jQuery.ajax({
                            url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
                            type: 'POST',
                            data: {'action' : 'wt_sc_hide_any_product_from_category_condition_migrated_msg', '_wpnonce': '<?php echo esc_html( wp_create_nonce( 'wt_sc_hide_any_product_from_category_condition_migrated_msg' ) ); ?>'}
                        });
                        
                    });
                });
            </script>
            <?php
        }
    }


    /**
     *  Hide the category condition updated message.
     * 
     *  @since 2.1.1
     */
    public function hide_any_product_from_category_condition_migrated_message()
    {
        $nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( $_POST['_wpnonce'] ) : '';
        
        if ( wp_verify_nonce( $nonce, 'wt_sc_hide_any_product_from_category_condition_migrated_msg' ) ) {
            update_option( 'wt_sc_show_any_product_from_category_migrated_msg', 2 ); // Set hide message flag
        }

        exit();
    }
}
Wt_Smart_Coupon_Giveaway_Product_Admin::get_instance();