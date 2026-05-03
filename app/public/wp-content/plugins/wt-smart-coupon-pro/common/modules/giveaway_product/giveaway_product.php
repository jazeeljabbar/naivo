<?php
/**
 * Giveaway product admin/public section
 *
 * @link       
 * @since 2.0.2     
 *
 * @package  Wt_Smart_Coupon
 */
if (!defined('ABSPATH')) {
    exit;
}

class Wt_Smart_Coupon_Giveaway_Product
{
    public $module_base='giveaway_product';
    public $module_id='';
    public static $module_id_static='';
    private static $instance = null;
    public static $bogo_coupon_type_name='wt_sc_bogo'; /* bogo coupon type name */
    public static $meta_arr=array();
    public function __construct()
    {
        $this->module_id=Wt_Smart_Coupon::get_module_id($this->module_base);
        self::$module_id_static=$this->module_id;

        self::$meta_arr=array(
            '_wt_free_product_ids'=>array(
                'default'=>'', /* default value */
                'type'=>'text', /* value type */
            ),
            '_wt_product_discount_quantity'=>array(
                'default'=>1,
                'type'=>'absint',
            ),
            '_wt_product_discount_amount'=>array(
                'default'=>'',
                'type'=>'float',
            ),
            '_wt_product_discount_type'=>array(
                'default'=>'percent',
                'type'=>'text',
            ),
            'wt_apply_discount_before_tax_calculation'=>array(
                'default'=>true,
                'type'=>'boolean',
            ),
            '_wt_sc_bogo_apply_frequency'=>array(
                'default'=>'once',
                'type'=>'text',
            ),
            '_wt_sc_bogo_customer_gets'=>array(
                'default'=>'specific_product', 
                'type'=>'text',
            ),
            '_wt_sc_bogo_product_condition'=>array(
                'default'=>'and', 
                'type'=>'text',
            ),
            '_wt_sc_bogo_free_products'=>array(
                'default'=>array(), 
                'type'=>'text_arr',
            ),
            '_wt_sc_bogo_free_categories'=>array(
                'default'=>array(), 
                'type'=>'text_arr',
            ),
            '_wt_sc_cheapest_item_as_giveaway'=>array(
                'default' => false, 
                'type' => 'boolean',
            ),
            '_wt_sc_convert_existing_as_giveaway' => array( /** @since 2.2.0 Convert existing item as giveaway. */
                'default' => false, 
                'type' => 'boolean',
            ),
        );

        add_filter('woocommerce_coupon_discount_types', array($this, 'add_bogo_coupon_type'));
        
        /**
         *  Register the messages that are customizable via admin panel
         *  @since 2.0.8
         */
        add_filter('wt_sc_intl_add_notifications', array($this, 'register_customized_texts'));


        /**
         *  Add settings
         * 
         *  @since 2.1.1
         */
        add_filter('wt_sc_module_default_settings', array($this, 'default_settings'), 10, 2);
    }

    /**
     * Get Instance
    */
    public static function get_instance()
    {
        if(self::$instance==null)
        {
            self::$instance=new Wt_Smart_Coupon_Giveaway_Product();
        }
        return self::$instance;
    }

    /**
     * Register BOGO coupon type
     * @since 2.0.4
     */
    public function add_bogo_coupon_type($discount_types)
    {
        $restricted_pages = ( class_exists( 'Wt_Smart_Coupon_Common' ) && method_exists( 'Wt_Smart_Coupon_Common', 'bogo_restricted_pages' ) ) ?Wt_Smart_Coupon_Common::bogo_restricted_pages() : array();

        if( //If new BOGO is activated, then stop adding old BOGO type in coupon generating page.
            class_exists( 'Wbte_Smart_Coupon_Bogo_Common' ) 
            && method_exists( 'Wbte_Smart_Coupon_Bogo_Common', 'is_new_bogo_activated' ) 
            && Wbte_Smart_Coupon_Bogo_Common::is_new_bogo_activated() 
            && ( 
                ( isset( $_GET['page'] ) 
                    && in_array( $_GET['page'], $restricted_pages, true ) 
                )
                || ( isset( $_GET['post_type'] ) 
                    && 'shop_coupon' === $_GET['post_type'] 
                    && isset( $_SERVER['REQUEST_URI'] )
                    && strpos($_SERVER['REQUEST_URI'], 'post-new.php') !== false
                ) 
            )
        ){
            
            return $discount_types;
        }

        $discount_types[self::$bogo_coupon_type_name] = __('BOGO (Buy X Get X/Y) offer', 'wt-smart-coupons-for-woocommerce-pro');
        return $discount_types;
    }

    /**
     *  Prepare giveaway data for order detail section
     */
    public function prepare_giveaway_info_for_order($order_item_id, $order_item, $order)
    {
        if(wc_get_order_item_meta($order_item_id, 'free_product', true)=='wt_give_away_product')
        {
            $coupon_code = wc_get_order_item_meta($order_item_id,'free_gift_coupon',true);
            $coupon_id=wc_get_coupon_id_by_code($coupon_code) ;
            if($coupon_id && false === $this->is_apply_discount_before_tax_enabled($coupon_id))
            {
                $item_id = ($order_item['variation_id']>0 ? $order_item['variation_id'] : $order_item['product_id']);
                $product = wc_get_product($item_id);
                if(!$product instanceof WC_Product)
                {
                    return false;
                }
                $product_price = (float) self::get_product_price($product)*$order_item['quantity'];
                $giveaway_data=$this->get_product_giveaway_data($item_id, $coupon_code, $order_item);
                $discount= (float) self::get_available_discount_for_giveaway_product($product, $giveaway_data)*$order_item['quantity'];
                $sale_price_after_discount=($product_price - $discount);
                $value = '<del><span>'.Wt_Smart_Coupon_Admin::get_formatted_price( ( number_format((float) $product_price,2,'.','' ) ) ).'</span></del> <span>'.Wt_Smart_Coupon_Admin::get_formatted_price( ( number_format((float) $sale_price_after_discount,2,'.','' ) ) ).'</span>';
                return $value;
            }
        }
        return false;
    }

    public function get_product_giveaway_data($item_id, $coupon_code, $cart_item=array())
    {
        $coupon=new WC_Coupon($coupon_code);
        $coupon_id=$coupon->get_id();
        $product=wc_get_product($item_id);
        
        $product_id=$item_id;
        $variation_id=0;
        if($product->is_type("variation"))
        {
            $product_id=$product->get_parent_id();  
            $variation_id=$item_id;  
        }

        $bogo_customer_gets = $this->get_coupon_meta_value($coupon_id, '_wt_sc_bogo_customer_gets'); 
        if(self::is_bogo($coupon) && ('any_product_from_category'==$bogo_customer_gets || 'specific_product'==$bogo_customer_gets)) /* product/category specific discount options */
        {
            if('specific_product'==$bogo_customer_gets)
            {
                $bogo_products=self::get_all_bogo_giveaway_products($coupon_id);
                if($variation_id>0 && isset($bogo_products[$variation_id]))
                {
                    return $bogo_products[$variation_id];

                }elseif(isset($bogo_products[$product_id]))
                {
                    return $bogo_products[$product_id];
                }
            }else //category
            {
                $bogo_free_categories = $this->get_coupon_meta_value($coupon_id, '_wt_sc_bogo_free_categories');
                
                if(!empty($bogo_free_categories))
                {
                    $coupon_categories  = array_keys($bogo_free_categories);
                    $product_cats       = Wt_Smart_Coupon_Common::get_product_cat_ids($product_id);
                    $matching_cats      = array_values(array_intersect($coupon_categories, $product_cats));
                    
                    if(!empty($matching_cats)) /* this product is under the given categories */
                    { 
                        if('any' === $this->get_any_product_from_category_condition( $coupon_code ))
                        {
                            $qty = $this->get_coupon_meta_value($coupon_id, '_wt_product_discount_quantity');

                            if(0 === $qty) // Assumes here we need a backward compatibility. 
                            {
                                return reset($bogo_free_categories); // return the value of first category data
                            }else
                            {
                                /* global discount options */
                                return $this->get_global_discount_options( $coupon_id );
                            }

                        }else
                        {
                            $category = (isset($cart_item['free_category']) ? absint($cart_item['free_category']) : 0);
                            $category = ($category > 0 ? $category : $matching_cats[0]);
                            return $bogo_free_categories[$category]; 
                        } 
                    }  
                }
            }
        }else
        {
            /* global discount options */
            return $this->get_global_discount_options( $coupon_id );
        }

        return self::get_dummy_qty_price();
    }

    /**
     * Is current coupon is BOGO.
     */
    public static function is_bogo($coupon)
    {
        return $coupon->is_type(self::$bogo_coupon_type_name);
    }

    public static function customer_gets_data_arr()
    {
        $customer_gets=array(
            'specific_product'                      => __('Specific product', 'wt-smart-coupons-for-woocommerce-pro'),
            'any_product_from_category'             => __('Any product from specific category', 'wt-smart-coupons-for-woocommerce-pro'),
            'any_product_from_store'                => __('Any product in store', 'wt-smart-coupons-for-woocommerce-pro'),
            'same_product_in_the_cart'              => __('Same product as in the cart', 'wt-smart-coupons-for-woocommerce-pro'),
            'any_product_from_category_in_the_cart' => __('Any product from the same category as in cart', 'wt-smart-coupons-for-woocommerce-pro'),
        );

        return apply_filters('wt_sc_intl_alter_customer_gets_data_arr', $customer_gets);
    }

    public static function customer_gets_help_arr()
    {
        $customer_gets_help = array(
            'specific_product' => __('Choose what the customers get for free or with a discount if the cart eligibility or conditions are met. Your customers will get below selected product/s for free or with a discount.', 'wt-smart-coupons-for-woocommerce-pro'),
            'any_product_from_category' => __('Choose what the customers get for free or with a discount if the cart eligibility or conditions are met. Your customers will get product/s from below selected category for free or with a discount.', 'wt-smart-coupons-for-woocommerce-pro'),
            'any_product_from_store' => __('Choose what the customers get for free or with a discount if the cart eligibility or conditions are met. Your customers will get any product/s from the store that are eligible for free or with a discount.', 'wt-smart-coupons-for-woocommerce-pro'),
            'same_product_in_the_cart' => __('Choose what the customers get for free or with a discount if the cart eligibility or conditions are met. Your customers will get the same product as in the cart that are configured in product restriction section.', 'wt-smart-coupons-for-woocommerce-pro'),
            'any_product_from_category_in_the_cart' => sprintf(__('Choose what the customers get for free or with a discount if the cart eligibility or conditions are met. Your customers will get a product from the same category as in the cart that are configured in the category restriction section.%s Some usage restrictions are not applicable for this option. For more info please check `Usage restriction` tab %s', 'wt-smart-coupons-for-woocommerce-pro'), '<span style="color:orange;">', '</span>'),
        );

        return apply_filters('wt_sc_intl_alter_customer_gets_help_arr', $customer_gets_help);
    }

    public static function get_dummy_qty_price()
    {
        return array('qty'=>1, 'price'=>100, 'price_type'=>'percent');
    }

    public static function prepare_items_data($item_ids, $wt_sc_items_data)
    {
        $dummy_qty_price=self::get_dummy_qty_price();
        $items_data=array();
        if(!empty($item_ids)) /* prepare dummy quantity and price data from default giveaway fields */
        {
            $qty_price_dummy=array_fill(0, count($item_ids), $dummy_qty_price);
            $items_data=array_combine($item_ids, $qty_price_dummy);
        }

        if(!empty($wt_sc_items_data)) /* meta data, merge with default giveaway product data */
        {
            foreach($items_data as $item_id=>$item_data)
            {
                $items_data[$item_id]=(isset($wt_sc_items_data[$item_id]) ? $wt_sc_items_data[$item_id] : $item_data);
            }
        }
        return $items_data;
    }

    /**
     *  @since 2.0.2
     *  Prepare meta value, If meta not exists, use default value
     */
    public static function get_coupon_meta_value($post_id, $meta_key, $default='')
    {
        $default_vl=(isset(self::$meta_arr[$meta_key]) && isset(self::$meta_arr[$meta_key]['default']) ? self::$meta_arr[$meta_key]['default'] : $default);
        return (metadata_exists('post', $post_id, $meta_key) ? get_post_meta($post_id, $meta_key, true) : $default_vl);
    }

    /**
     *  Get giveaway products id from coupon meta
     *  @return array
     */
    public static function get_giveaway_products($post_id)
    {
        $free_product_ids   = self::get_instance()->get_coupon_meta_value($post_id, '_wt_free_product_ids');
        $free_product_id_arr=array();
        if($free_product_ids && is_string($free_product_ids))
        {
            $free_product_id_arr = explode(',', $free_product_ids);
        }
        return $free_product_id_arr;
    }

    /**
     *  Checks all products are completely free
     *  Only for BOGO with specific product condition
     *  @since 2.0.4
     */
    public static function is_full_free_bogo($post_id)
    {
        $is_completely_free=true;
        $product_data_arr=self::get_all_bogo_giveaway_products($post_id);
        foreach($product_data_arr as $product_id=>$product_data)
        {
            $product = wc_get_product($product_id);
            if(!is_object($product))
            {
                continue; //product not exists, so skip
            }
            
            if('percent'==$product_data['price_type'])
            {
                if($product_data['price']=="" || $product_data['price']==100)
                {
                   continue; //current item is 100% free 
                }else
                {
                    $is_completely_free=false;
                    break; //current item is not completely free, so break the loop
                }
                
            }else //flat type price
            {
                if($product_data['price']==$product->get_price())
                {
                    continue; //current item is 100% free
                }else
                {
                    $is_completely_free=false;
                    break; //current item is not completely free, so break the loop
                }
            }
        }
        return $is_completely_free;
    }

    /**
     *  Function to check is 100% free giveaway item
     *  @since 2.0.4
     */
    public static function is_full_free_item($product, $giveaway_data)
    {
        $product_price=self::get_product_price($product);
        
        $discount = self::get_available_discount_for_giveaway_product($product, $giveaway_data);
        return ($discount===$product_price ? true : false);
    }

    /**
     * Function to get actual discount available for a giveaway item.
     * @since 2.0.2
     */
    public static function get_available_discount_for_giveaway_product($product, $giveaway_data)
    {     
        $product_price=self::get_product_price($product);
        $discount = $product_price;
        if(isset($giveaway_data['price']) && ''!=$giveaway_data['price'] && isset($giveaway_data['price_type']) && ''!=$giveaway_data['price_type'])
        {
            if('percent'==$giveaway_data['price_type'])
            {
                $discount=($product_price * $giveaway_data['price']/100);
            }else
            {
                $discount = min($giveaway_data['price'], $product_price);
            }            
        }

        return $discount;
    }

    
    public static function get_product_price($product)
    {
        if($product->is_on_sale())
        { 
            $product_price = $product->get_sale_price();
        } else {
            $product_price = $product->get_regular_price();
        }

        if("" === $product_price)
        {
            $product_price = $product->get_price();
        }

        $product_price = (float) $product_price;
        
        /**
         *  Alter product price of giveaway item
         * 
         *  @since 2.0.7
         *  @param $product_price   float           Price of the product
         *  @param $product         WC_Product      Product object
         */
        return apply_filters('wt_sc_alter_giveaway_product_price', $product_price, $product); 
    }


    public static function get_all_bogo_giveaway_products($post_id)
    {
        $free_product_id_arr=self::get_giveaway_products($post_id);
        $bogo_free_products = self::get_coupon_meta_value($post_id, '_wt_sc_bogo_free_products');
        return apply_filters('wt_sc_alter_bogo_giveaway_products', self::prepare_items_data($free_product_id_arr, $bogo_free_products), $post_id);
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
        $notifications['non_purchasable_giveaway_varaition'] = array(
            'message'           => __('Sorry! this product is not available for giveaway.', 'wt-smart-coupons-for-woocommerce-pro'),
            'description'       => __('Displays when the user selects a variation of a giveaway item that is not available for purchase.', 'wt-smart-coupons-for-woocommerce-pro'),
            'status'            => 1, 
            'supported_placeholders' => array(),
            'available_filters' => array(),
            'module'   => 'giveaway_product',
            'group'         => 'warning',
            'initiater'     => 'sc', //smart coupon
        );


        $notifications['full_free_giveaway_added_to_cart'] = array(
            'message'           => __("Congratulations! You've got a freebie in your cart!", 'wt-smart-coupons-for-woocommerce-pro'),
            'description'       => __("Displays when a giveaway product with 100% discount is added to the cart.", 'wt-smart-coupons-for-woocommerce-pro'),
            'status'            => 1, 
            'supported_placeholders' => array(
                'coupon_code' => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'available_filters' => array(
                'wt_sc_alter_free_product_added_message' => __('Filter to edit free giveaway added message.', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'module'   => 'giveaway_product',
            'group'         => 'success',
            'initiater'     => 'sc', //smart coupon
        );


        $notifications['add_product_from_the_cat_as_giveaway'] = array(
            'message'           => sprintf(__("Congratulations! You have qualified for a giveaway by applying coupon %s. Add any products from %s category to redeem the offer.", 'wt-smart-coupons-for-woocommerce-pro'), '{coupon_code}', '{giveaway_categories}'),
            'description'       => __("Displays when customer applies a coupon and the customer will be allowed to add any product to the cart from the eligible category. This newly added product will be converted as giveaway.", 'wt-smart-coupons-for-woocommerce-pro'),
            'status'            => 1, 
            'supported_placeholders' => array(
                'coupon_code' => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
                'giveaway_categories' => __('Giveaway is eligible categories(Separate categories with comma).', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'available_filters' => array(
                'wt_sc_alter_giveaway_eligible_message' => __('Filter to edit giveaway eligible message.', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'module'   => 'giveaway_product',
            'group'         => 'info',
            'initiater'     => 'sc', //smart coupon
        );

        $notifications['add_any_product_from_the_store_as_giveaway'] = array(
            'message'           => sprintf(__("Congratulations! You have qualified for a giveaway by applying coupon %s! Simply add any product to your cart to redeem the offer.", 'wt-smart-coupons-for-woocommerce-pro'), "{coupon_code}"),
            'description'       => __("Displays when a customer applies a coupon and the customer is allowed to add a new product that will be converted as giveaway.", 'wt-smart-coupons-for-woocommerce-pro'),
            'status'            => 1, 
            'supported_placeholders' => array(
                'coupon_code' => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'available_filters' => array(
                'wt_sc_alter_giveaway_eligible_message' => __('Filter to edit giveaway eligible message.', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'module'   => 'giveaway_product',
            'group'         => 'info',
            'initiater'     => 'sc', //smart coupon
        );

        $notifications['coupon_max_quantity_reached'] = array(
            'message'           => __("The maximum quantity allowed for the giveaway has been reached.", 'wt-smart-coupons-for-woocommerce-pro'),
            'description'       => __("Displays when customer exceeds the maximum quantity of giveaways allowed.", 'wt-smart-coupons-for-woocommerce-pro'),
            'status'            => 1, 
            'supported_placeholders' => array(
                'coupon_code' => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'available_filters' => array(
                'wt_sc_alter_giveaway_addtocart_messages' => __('Filter to edit giveaway add to cart messages.', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'module'   => 'giveaway_product',
            'group'         => 'warning',
            'initiater'     => 'sc', //smart coupon
        );

        $notifications['already_availed_bogo'] = array(
            'message'           => __("You have already moved all the giveaway products to the cart.", 'wt-smart-coupons-for-woocommerce-pro'),
            'description'       => __("Displays when all giveaway products have been redeemed by the customer.", 'wt-smart-coupons-for-woocommerce-pro'),
            'status'            => 1, 
            'supported_placeholders' => array(
                'coupon_code' => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'available_filters' => array(
                'wt_sc_alter_giveaway_addtocart_messages' => __('Filter to Customize giveaway add to cart messages', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'module'   => 'giveaway_product',
            'group'         => 'warning',
            'initiater'     => 'sc', //smart coupon
        );

        $notifications['partialy_free_giveaway_cart_item'] = array(
            'message'           => sprintf(__("Surprise! You've received a special giveaway product with %s Off!", 'wt-smart-coupons-for-woocommerce-pro'), '{giveaway_product_discount}'),
            'description'       => __("Displays under the product name of partially free giveaway item in the cart to inform that this is a discounted item.", 'wt-smart-coupons-for-woocommerce-pro'),
            'status'            => 1, 
            'supported_placeholders' => array(
                'coupon_code' => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
                'giveaway_product_discount' => __('Giveaway product discount text', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'available_filters' => array(
                'wt_sc_alter_giveaway_cart_lineitem_text' => __('Filter to edit giveaway cart item message.', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'module'   => 'giveaway_product',
            'group'         => 'info',
            'initiater'     => 'sc', //smart coupon
        );

        $notifications['full_free_giveaway_cart_item'] = array(
            'message'           => __("Congratulations! You've got a freebie in your cart!", 'wt-smart-coupons-for-woocommerce-pro'),
            'description'       => __("Displays under the product name of free giveaway item in the cart to inform that this is a free item.", 'wt-smart-coupons-for-woocommerce-pro'),
            'status'            => 1, 
            'supported_placeholders' => array(
                'coupon_code' => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'available_filters' => array(
                'wt_sc_alter_giveaway_cart_lineitem_text' => __('Filter to edit giveaway cart item message.', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'module'   => 'giveaway_product',
            'group'         => 'info',
            'initiater'     => 'sc', //smart coupon
        );


        $notifications['nonbogo_giveaway_cart_summary_label'] = array(
            'message'           => __("Free gift", 'wt-smart-coupons-for-woocommerce-pro'),
            'description'       => __("Displays in the cart summary section when the user applies a non BOGO coupon with a giveaway.", 'wt-smart-coupons-for-woocommerce-pro'),
            'status'            => 1, 
            'supported_placeholders' => array(
                'coupon_code' => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'available_filters' => array(
                'wt_sc_alter_giveaway_cart_summary_label' => __('Filter to alter cart summary label for non BOGO coupon.', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'module'   => 'giveaway_product',
            'group'         => 'label',
            'initiater'     => 'sc', //smart coupon
        );

        $notifications['giveaway_order_summary_label'] = array(
            'message'           => __("Free gift", 'wt-smart-coupons-for-woocommerce-pro'),
            'description'       => __("Displays in the order summary section when the order contains a giveaway item.", 'wt-smart-coupons-for-woocommerce-pro'),
            'status'            => 1, 
            'supported_placeholders' => array(
                'coupon_code' => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'available_filters' => array(
                'wt_sc_alter_order_detail_giveaway_info_label' => __('Filter to alter order summary label of giveaway.', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'module'   => 'giveaway_product',
            'group'         => 'label',
            'initiater'     => 'sc', //smart coupon
        );


        $notifications['bogo_coupon_block_label'] = array(
            'message'           => __("Free products", 'wt-smart-coupons-for-woocommerce-pro'),
            'description'       => __("Displays in the coupon block of BOGO coupon type.", 'wt-smart-coupons-for-woocommerce-pro'),
            'status'            => 1, 
            'supported_placeholders' => array(
                'coupon_code' => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'available_filters' => array(
                'wt_sc_alter_coupon_title_text' => __('Edit coupon block title text', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'module'   => 'giveaway_product',
            'group'         => 'label',
            'initiater'     => 'sc', //smart coupon
        );

        return $notifications;
    }


    /**
     *  Get customized notification messages
     *  
     *  @since  2.0.8
     *  @param  string      $key    Unique key for the message
     *  @param  array       $args   Values for the function: Coupon code, Placeholders etc
     *  @return string      Empty string when message was disabled otherwise the message
     */
    public function get_customized_text($key, $args = array())
    {
        return Wt_Smart_Coupon_Public::get_customized_text($key, $args);
    }
    

    /**
     *  Is apply discount before tax is enabled
     *  
     *  @since  2.0.9
     *  @param  int     $coupon_id Coupon id
     *  @return bool    True when enabled otherwise False
     */
    public function is_apply_discount_before_tax_enabled($coupon_id)
    {
        return wc_string_to_bool($this->get_coupon_meta_value($coupon_id, 'wt_apply_discount_before_tax_calculation'));
    }

    
    /**
     *  Default settings
     *  
     *  @since  2.1.1
     *  @param  array       $settings   Settings array
     *  @param  string      $base_id    Module id
     *  @return array       Settings array
     */
    public function default_settings($settings, $base_id)
    {
        if($base_id !== $this->module_id)
        {
            return $settings;
        }

        return array(
            'any_product_from_category_condition' => 'any', // Applicable values `any`, `all`
        );
    }


    /**
     *  Get global BOGO discount options
     * 
     *  @since  2.1.1
     *  @param  int     $coupon_id  Id of coupon
     *  @return array   Associative array of discount values
     */
    public function get_global_discount_options( $coupon_id )
    {
        return array(
            'qty'           =>  $this->get_coupon_meta_value( $coupon_id, '_wt_product_discount_quantity' ), 
            'price'         =>  $this->get_coupon_meta_value( $coupon_id, '_wt_product_discount_amount' ), 
            'price_type'    =>  $this->get_coupon_meta_value( $coupon_id, '_wt_product_discount_type' )
        );
    }


    /**
     *  Giveaway category condition for `any_product_from_category`
     *  
     *  @since  2.1.1
     *  @param  string  $coupon_code                Coupon code
     *  @return string  Category condition
     */
    public function get_any_product_from_category_condition($coupon_code)
    {
        $cat_condition = Wt_Smart_Coupon::get_option('any_product_from_category_condition', $this->module_id);
        
        /**
         *  Filter to alter the category condition.
         *  Default: any (Products from any of the caregories)
         *  Other value: all (Products from all catgeories)
         *  
         *  @since 2.1.1
         *  @param string   Category condition. Default:`any`. Applicable values `any`, `all`
         *  @param string   $coupon_code    Coupon code
         */
        return apply_filters( 'wt_sc_any_product_from_category_condition', $cat_condition, $coupon_code );
    }
}
Wt_Smart_Coupon_Giveaway_Product::get_instance();