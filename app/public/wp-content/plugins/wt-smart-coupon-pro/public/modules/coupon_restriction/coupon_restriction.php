<?php
/**
 * Coupon usage restriction public section
 *
 * @link       
 * @since 2.0.2     
 *
 * @package  Wt_Smart_Coupon  
 */
if (!defined('ABSPATH')) {
    exit;
}
if(!class_exists ( 'Wt_Smart_Coupon_Restriction' ) ) /* common module class not found so return */
{
    return;
}
class Wt_Smart_Coupon_Restriction_Public extends Wt_Smart_Coupon_Restriction
{
    public $module_base='coupon_restriction';
    public $module_id='';
    public static $module_id_static='';
    private static $instance = null;
    public static $bogo_applicable_count_session_id = 'wt_sc_bogo_applicable_count'; /* how much times the coupon passed the BOGO restriction condition */
    
    private $disqualified = array(); /* an array to store item_ids of disqualified products that doesn't satisty product quantity restriction[min or max] */

    /**
     *  Cart/Order items
     *  
     *  @since 2.0.7
     */
    private static $items = array();

    /**
     *  WC_Discounts Object
     *  
     *  @since 2.0.8
     */
    private static $discounts_obj = null;


    /**
     *  Coupon matching cart items array
     *  
     *  @since 2.0.8
     */
    public static $matching_items = array(); 

    public function __construct()
    {
        $this->module_id=Wt_Smart_Coupon::get_module_id($this->module_base);
        self::$module_id_static=$this->module_id;
       
        add_filter('woocommerce_coupon_is_valid', array($this, 'wt_woocommerce_coupon_is_valid'), 10, 2);
        
        /**
         *  Exclude products that are not satisfying the coupon validation condition on `any(or)` product condition
         *  @since 2.0.5
         */
        add_filter('woocommerce_coupon_is_valid_for_product', array($this, 'exclude_disqualified_products'), 10, 4);
    }

    /**
     * Get Instance
    */
    public static function get_instance()
    {
        if(self::$instance==null)
        {
            self::$instance=new Wt_Smart_Coupon_Restriction_Public();
        }
        return self::$instance;
    }

    /**
     *  Prepare min/max quantity data from restriction configuration data
     *  @since 2.0.4
     */
    private function process_qty_from_restriction_data($item_id, $type, $wt_sc_items_data)
    {
        $qty=absint(isset($wt_sc_items_data[$item_id][$type]) ? $wt_sc_items_data[$item_id][$type] : 0);
        return ($qty==0 && $type=='min' ? 1 : $qty);
    }

    public function get_individual_min_max_quantity_validation_message($item_name, $qty, $coupon_code, $type='no_valid_products')
    {
        if('min' === $type)
        {
            $msg = $this->get_customized_text('individual_min_quantity', array('coupon_code' => $coupon_code, 'cart_item_name' => $item_name, 'required_quantity' => $qty));
        }elseif('max' === $type)
        {
            $msg = $this->get_customized_text('individual_max_quantity', array('coupon_code' => $coupon_code, 'cart_item_name' => $item_name, 'required_quantity' => $qty));
        }else
        {
            $msg = $this->get_customized_text('individual_min_max_quantity', array('coupon_code' => $coupon_code));
        }

        return apply_filters('wt_sc_alter_individual_min_max_quantity_validation_message', $msg, array('item_name'=>$item_name, 'quantity'=>$qty, 'type'=>$type, 'coupon_code'=>$coupon_code));
    }

    
    /**
    * 
    *   @since      2.0.6   [Bug fix] When multiple coupon with same product restriction but different quantity restriction is used,
    *                       validation error occurring is fixed by taking each disqualified item (min and max)into 'disqualified' array based on individual coupon code.
    *   @since      2.0.8   Implemented customized messages option
    */
    private function individual_min_max_quantity_validation($coupon_code, $item_id, $wt_sc_items_data, $items_to_check_qty, $items_to_check_name, &$valid, $throw_exception=true, $is_category=false)
    {
        global $wt_sc_coupon_eligibility_contribution; /* this using for giveaway functionality, If currently added product and giveaway product are same for the newly added coupon. This is currently applicable for `Specific category`, `Any product from store` options */
        
        $wt_sc_coupon_eligibility_contribution=(!is_array($wt_sc_coupon_eligibility_contribution) ? array() : $wt_sc_coupon_eligibility_contribution);

        $coupon_code=wc_format_coupon_code($coupon_code);
        
        /* min quantity */
        $min_qty=$this->process_qty_from_restriction_data($item_id, 'min', $wt_sc_items_data);
        if($min_qty>0 && $items_to_check_qty[$item_id]<$min_qty)
        {
            if(!isset($this->disqualified[$coupon_code]))
            {
                $this->disqualified[$coupon_code] = array();
            }

            $this->disqualified[$coupon_code][] = $item_id; //stores disqualified items below min qty restriction in an array 
            $this->remove_bogo_applicable_count_session($coupon_code);
            $valid = false;

            
            if($throw_exception)
            {
                if("" !== ($msg = $this->get_individual_min_max_quantity_validation_message($items_to_check_name[$item_id], $min_qty, $coupon_code, 'min')))
                {   
                    throw new Exception($msg, 110);
                }
            }
        }

        /**
         *  This is for finding the eligibility contribution of the current product/category
         */
        if($valid)
        {
            if(!isset($wt_sc_coupon_eligibility_contribution[$coupon_code]))
            {
                $wt_sc_coupon_eligibility_contribution[$coupon_code]=array();
            }

            if($is_category)
            {
                if(!isset($wt_sc_coupon_eligibility_contribution[$coupon_code]['category']))
                {
                    $wt_sc_coupon_eligibility_contribution[$coupon_code]['category']=array();
                }

                $wt_sc_coupon_eligibility_contribution[$coupon_code]['category'][$item_id]=array($min_qty, $items_to_check_qty[$item_id]);
            
            }else
            {
                $wt_sc_coupon_eligibility_contribution[$coupon_code][$item_id]=array($min_qty, $items_to_check_qty[$item_id]);
            }
        }

        /* max quantity */
        $max_qty=$this->process_qty_from_restriction_data($item_id, 'max', $wt_sc_items_data);
        if($max_qty>0 && $items_to_check_qty[$item_id]>$max_qty)
        {             
            if(!isset($this->disqualified[$coupon_code]))
            {
                $this->disqualified[$coupon_code] = array();
            }

            $this->disqualified[$coupon_code][] = $item_id; //stores disqualified item that exceeds max qty restriction in an array

            $this->remove_bogo_applicable_count_session($coupon_code);       
            $valid = false;                
            
            if($throw_exception)
            {
                if("" !== ($msg = $this->get_individual_min_max_quantity_validation_message($items_to_check_name[$item_id], $max_qty, $coupon_code, 'max')))
                {   
                    throw new Exception($msg, 111);
                }
            }
        }
    }

    
    /**
    * Get Quantity of matching products - Used for Coupon validation when global quantity restriction enabled.
    * 
    * @since 1.0.0
    * @since 1.3.5      Excluded free products from calculating total quantity of matching products
    * @since 2.0.2      [Bug fix] Incorrect calculation when variable products are in the cart
    * @since 2.0.6      [Bug fix] Incorrect quantity when excluded product/category exists
    * @since 2.0.7      Added compatibility for order items along with cart items
    * @since 2.0.8      Added new argument `$args`[optional] for adding extra arguments
    *                   Functionality moved to a new function: `get_matching_product_property`                 
    * 
    * @return int Total quantity of matching product
    */
    public function get_quantity_of_matching_product($coupon, $coupon_products, $coupon_categories, $coupon_exclude_products = array(), $coupon_exclude_categories = array(), $args = array())
    {
        $args['coupon_products']            = $coupon_products; 
        $args['coupon_categories']          = $coupon_categories; 
        $args['coupon_exclude_products']    = $coupon_exclude_products; 
        $args['coupon_exclude_categories']  = $coupon_exclude_categories; 
        $args['property']                   = 'quantity';

        return $this->get_matching_product_property($coupon, $args);
    }

    
    /**
     * Get sub total for matching product - used for coupon validation
     * 
     *  @since 1.0.0
     *  @since 2.0.7    Added compatibility for order items along with cart items
     *  @since 2.0.8    Added new argument `$args`[optional] for adding extra arguments
     *                  Functionality moved to a new function: `get_matching_product_property`
     */
    public function get_subtotal_of_matching_products($coupon, $coupon_products, $coupon_categories, $coupon_exclude_products = array(), $coupon_exclude_categories = array(), $args = array())
    {     
        $args['coupon_products']            = $coupon_products; 
        $args['coupon_categories']          = $coupon_categories; 
        $args['coupon_exclude_products']    = $coupon_exclude_products; 
        $args['coupon_exclude_categories']  = $coupon_exclude_categories;  
        $args['property']                   = 'subtotal';

        return $this->get_matching_product_property($coupon, $args);
    }


    /**
     *  Get properties like Subtotal, Quantity of matching products
     * 
     *  @since  2.0.8
     *  @param  WC_Coupon       $coupon     Coupon object
     *  @param  array           $args       Array of arguments. Eg: coupon_products, coupon_categories etc
     *  @return int|float       Currently there are only `subtotal` and `quantity` so the output values will be in integer or float
     */
    public function get_matching_product_property($coupon, $args)
    {
        $coupon_tags                = (isset($args['coupon_tags']) && is_array($args['coupon_tags']) ? $args['coupon_tags'] : array());
        $coupon_attributes          = (isset($args['coupon_attributes']) && is_array($args['coupon_attributes']) ? $args['coupon_attributes'] : array());
        $coupon_products            = (isset($args['coupon_products']) && is_array($args['coupon_products']) ? $args['coupon_products'] : array());
        $coupon_categories          = (isset($args['coupon_categories']) && is_array($args['coupon_categories']) ? $args['coupon_categories'] : array());      
        $coupon_exclude_products    = (isset($args['coupon_exclude_products']) && is_array($args['coupon_exclude_products']) ? $args['coupon_exclude_products'] : array());
        $coupon_exclude_categories  = (isset($args['coupon_exclude_categories']) && is_array($args['coupon_exclude_categories']) ? $args['coupon_exclude_categories'] : array());
        
        $is_product_restriction_enabled     = count($coupon_products) > 0;
        $is_category_restriction_enabled    = count($coupon_categories) > 0;
        $is_tag_restriction_enabled         = count($coupon_tags) > 0;
        $is_attribute_restriction_enabled   = count($coupon_attributes) > 0;

        $property = (isset($args['property']) && is_string($args['property']) ? $args['property'] : 'quantity'); //which property have to take. Eg: quantity, subtotal

        $items = self::$items;
        $total = 0; //the total property value to be returned

        $coupon_code = $coupon->get_code();
        $is_exclude_sale_items = $coupon->get_exclude_sale_items();
        $matching_items = isset(self::$matching_items[$coupon_code]) ? self::$matching_items[$coupon_code] : array();

        foreach($items as $item_key => $item)
        {
            // Skip giveaway products
            if (
                ( 
                    isset( $item['free_product'] ) && "wt_give_away_product" === $item['free_product'] 
                )
                || ( 
                    isset( $item['wbte_sc_free_product'] ) && "wbte_sc_giveaway_product" === $item['wbte_sc_free_product'] 
                ) 
            ) {
                continue;
            }

            // Skip sale items
            if ( $is_exclude_sale_items && $item['data']->is_on_sale() ) {
                continue;
            }

            $is_a_matching_product = false;

            if(!isset($matching_items[$item_key])) //not already validated
            {
                //product restriction
                if($is_product_restriction_enabled) 
                {
                    if(in_array($item['product_id'], $coupon_products) || in_array($item['variation_id'], $coupon_products))
                    {
                        $is_a_matching_product = true;
                    }
                }

                //category restriction
                if(!$is_a_matching_product && $is_category_restriction_enabled) 
                {
                    $product_cats = Wt_Smart_Coupon_Common::get_product_cat_ids($item['product_id']);

                    if(0 < count(array_intersect($coupon_categories, $product_cats)))
                    { 
                        if(0 === count(array_intersect($coupon_exclude_categories, $product_cats)))
                        {
                            $is_a_matching_product = true;
                        }     
                    }
                }

                //tag restriction
                if(!$is_a_matching_product && $is_tag_restriction_enabled) 
                {
                    $product_tags = get_the_terms($item['product_id'], 'product_tag');
                    $product_tags = ($product_tags && is_array($product_tags) ? array_column($product_tags, 'term_id') : array());

                    if(0 < count(array_intersect($coupon_tags, $product_tags)))
                    { 
                        $is_a_matching_product = true;    
                    }
                }

                //attribute restriction
                if(!$is_a_matching_product && $is_attribute_restriction_enabled) 
                {
                    if( isset($item['variation_id']) && $item['variation_id']>0){

                        $product = $item['data'];
                        $attributes_array = $product->get_attributes();

                        if( 0 < count(array_intersect(array_keys($attributes_array),$coupon_attributes)) )
                        {
                            $is_a_matching_product = true;
                        }

                    }
                }

                if(!$is_product_restriction_enabled && !$is_category_restriction_enabled && !$is_tag_restriction_enabled && !$is_attribute_restriction_enabled)
                {
                    if(!empty($coupon_exclude_categories) || !empty($coupon_exclude_products)) //product/category exclude conditions are there
                    {
                        $product_cats = Wt_Smart_Coupon_Common::get_product_cat_ids($item['product_id']);

                        if(
                            !in_array($item['product_id'], $coupon_exclude_products) 
                            && !in_array($item['variation_id'], $coupon_exclude_products) 
                            && 0 === count(array_intersect($coupon_exclude_categories, $product_cats))
                        ) //not included in product/category exclude conditions
                        {
                            $is_a_matching_product = true;
                        }

                    }else //no exclude conditions are there
                    {
                        $is_a_matching_product = true;
                    }
                }

                $matching_items[$item_key] = $is_a_matching_product;
            }else
            {
                $is_a_matching_product = $matching_items[$item_key]; //take the previously checked value
            }


            if($is_a_matching_product) //this product satisfies the matching criteria
            {
                if('subtotal' === $property)
                {
                    $total += ((float) $item['data']->get_price() * (int) $item['quantity']);

                }elseif('quantity' === $property)
                {
                    $total += $item['quantity'];
                }
            }
        }

        self::$matching_items[$coupon_code] = $matching_items;

        return $total;
    }


    /**
     *  Prepare term name for validation error message 
     */
    private function prepare_term_name_for_validation_error_message($category_id, &$items_to_check_name)
    {
        $items_to_check_name[$category_id]=__('The category', 'wt-smart-coupons-for-woocommerce-pro');
        $term = get_term_by('id', $category_id, 'product_cat'); 
        if($term)
        {
            $items_to_check_name[$category_id].=" '".$term->name."'";
        }
    }

    /** 
     *  Remove BOGO applicable count session when the corresponding coupon was removed
     *  @since 2.0.4
     *  @param coupon code
     */
    public static function remove_bogo_applicable_count_session($coupon_code)
    {
        $coupon_code=wc_format_coupon_code($coupon_code);
        $bogo_applicable_count=self::get_bogo_applicable_count_session();
        if(isset($bogo_applicable_count[$coupon_code]))
        {
            unset($bogo_applicable_count[$coupon_code]);
            WC()->session->set(self::$bogo_applicable_count_session_id, $bogo_applicable_count);
        }
    }

    /**
     *  Get BOGO applicable count sessions if exists
     *  @since 2.0.4
     *  @return     array   Empty array if not exists, otherwise an array with the session info
     */
    public static function get_bogo_applicable_count_session()
    {
        $bogo_applicable_count = !is_null( WC()->session ) ? WC()->session->get( self::$bogo_applicable_count_session_id ) : array();
        return (is_null($bogo_applicable_count) ? array() : $bogo_applicable_count);
    }

    /**
     *  This function will take the minimum applicable count from all applicable count
     *  @since 2.0.4
     */
    public function prepare_final_applicable_count($coupon_code)
    {
        $coupon_code=wc_format_coupon_code($coupon_code);
        $bogo_applicable_count=self::get_bogo_applicable_count_session();

        if(isset($bogo_applicable_count[$coupon_code]))
        {
            $bogo_applicable_count[$coupon_code]=min($bogo_applicable_count[$coupon_code]);
            WC()->session->set(self::$bogo_applicable_count_session_id, $bogo_applicable_count);
        }
    }


    public function set_applicable_count_by_subtotal($restriction_type, $coupon_code, $min_subtotal, $subtotal)
    {
        $coupon_code=wc_format_coupon_code($coupon_code);
        $bogo_applicable_count=$this->get_applicable_qty_by_coupon_and_type($coupon_code, $restriction_type);

        $total_applicable_count=floor($subtotal/$min_subtotal);
        $bogo_applicable_count[$coupon_code][$restriction_type]=$total_applicable_count;

        WC()->session->set(self::$bogo_applicable_count_session_id, $bogo_applicable_count);
    }

    private function get_applicable_qty_by_coupon_and_type($coupon_code, $restriction_type)
    {
        $coupon_code=wc_format_coupon_code($coupon_code);
        $bogo_applicable_count=self::get_bogo_applicable_count_session();
        if(!isset($bogo_applicable_count[$coupon_code]))
        {
            $bogo_applicable_count[$coupon_code]=array();
        }

        if(!isset($bogo_applicable_count[$coupon_code][$restriction_type]))
        {
            $bogo_applicable_count[$coupon_code][$restriction_type]=0;
        }

        return $bogo_applicable_count;
    }

    public function set_applicable_count_by_global_qty($restriction_type, $coupon_code, $item_quantity, $wt_min_matching_product_qty)
    {
        $coupon_code=wc_format_coupon_code($coupon_code);
        $bogo_applicable_count=$this->get_applicable_qty_by_coupon_and_type($coupon_code, $restriction_type);
        $total_applicable_count=floor($item_quantity/$wt_min_matching_product_qty); 

        $bogo_applicable_count[$coupon_code][$restriction_type]+=$total_applicable_count;

        WC()->session->set(self::$bogo_applicable_count_session_id, $bogo_applicable_count);
    }


    /**
     *  Prepare an array with global individual matching quantity. Later we will take the minimum number from this array as eligible count
     */
    public function prepare_applicable_count_by_global_individual_qty($total_valid_arr, $item_quantity, $wt_min_matching_product_qty)
    {
        if($wt_min_matching_product_qty>0)
        {
            $total_valid_arr[]=floor($item_quantity/$wt_min_matching_product_qty); /* store the quantity to an array to find min qty */
        }
        return $total_valid_arr;
    }

    /**
     *  Find minimum value from global individual matching quantity array, This will be the eligibility count.
     */
    public function process_applicable_count_by_global_individual_qty($restriction_type, $coupon_code, $total_valid_arr)
    {
        $coupon_code=wc_format_coupon_code($coupon_code);
        $bogo_applicable_count=$this->get_applicable_qty_by_coupon_and_type($coupon_code, $restriction_type);

        $bogo_applicable_count[$coupon_code][$restriction_type]=min($total_valid_arr);
        WC()->session->set(self::$bogo_applicable_count_session_id, $bogo_applicable_count);
    }

    public function set_applicable_count_by_qty($restriction_type, $coupon_code, $product_id, $items_to_check_qty, $wt_sc_products_data)
    {
        $coupon_code=wc_format_coupon_code($coupon_code);
        $min_qty=$this->process_qty_from_restriction_data($product_id, 'min', $wt_sc_products_data);
        $cart_qty=$items_to_check_qty[$product_id];

        $bogo_applicable_count=$this->get_applicable_qty_by_coupon_and_type($coupon_code, $restriction_type);

        $total_applicable_count=floor($cart_qty/$min_qty);
        $bogo_applicable_count[$coupon_code][$restriction_type]+=$total_applicable_count;

        WC()->session->set(self::$bogo_applicable_count_session_id, $bogo_applicable_count);
    }

    public function prepare_applicable_count_by_qty_for_and_condtion($total_valid_arr, $item_id, $wt_sc_items_data, $items_to_check_qty)
    {
        $min_qty=$this->process_qty_from_restriction_data($item_id, 'min', $wt_sc_items_data);
        if($min_qty>0)
        {
            $total_valid_arr[]=floor($items_to_check_qty[$item_id]/$min_qty); /* store the quantity to an array to find min qty */
        }
        return $total_valid_arr;
    }

    /**
     *  This function will process the applicable count if the product/category condition is `all from below`
     */
    public function process_applicable_count_by_qty_for_and_condition($restriction_type, $coupon_code, $total_valid_arr)
    {
        $coupon_code=wc_format_coupon_code($coupon_code);
        $bogo_applicable_count=$this->get_applicable_qty_by_coupon_and_type($coupon_code, $restriction_type);

        $bogo_applicable_count[$coupon_code][$restriction_type]=min($total_valid_arr);
        WC()->session->set(self::$bogo_applicable_count_session_id, $bogo_applicable_count);
    }

    /**
     *  @since 2.0.4
     *  @since 2.0.5    [Bug fix] Causing error on YITH POS custom add to cart
     *  @since 2.0.6    [Bug fix] Validation fails when global quantity restriction with exclude product/category exists
     *  @since 2.0.7    Added compatibility for backend coupon applying
     *  @since 2.0.8    Cart/order items preparing moved to a new function.
     *                  Implemented customized messages option                  
     */
    public function wt_woocommerce_coupon_is_valid($valid, $coupon)
    {
        global $woocommerce, $wt_sc_coupon_eligibility_contribution;
        
        if(!$valid) //already invalid so no need to validate here.
        {
            return false;
        }

        $items = $this->get_items(); //Cart/order items
        
        if(empty($items))
        {
            return $valid;
        }
        
        self::$items = $items; //store the value for sub functions. Eg: subtotal, quantity functions
        
        $applicable_count = 0; //how many times the validity conditions passed

        $coupon_id      = $coupon->get_id();
        $coupon_code    = wc_format_coupon_code($coupon->get_code());
        $wt_product_condition = $this->get_coupon_meta_value($coupon_id, '_wt_product_condition');        
        $use_individual_min_max = $this->get_coupon_meta_value($coupon_id, '_wt_use_individual_min_max');
        $wt_enable_product_category_restriction = $this->get_coupon_meta_value($coupon_id, '_wt_enable_product_category_restriction');        

        if(!isset($wt_sc_coupon_eligibility_contribution[$coupon_code]))
        {
            $wt_sc_coupon_eligibility_contribution[$coupon_code] = array();
        }

        $giveaway_obj = null;
        
        if(Wt_Smart_Coupon_Public::module_exists('giveaway_product'))
        {
            $giveaway_obj = Wt_Smart_Coupon_Giveaway_Product_Public::get_instance();
        }
        
        $coupon_products = array();
        $coupon_categories = array();
        $coupon_excluded_products = array();
        $coupon_excluded_categories = array();
        $coupon_tags = array();
        $coupon_attributes = array();

        /**
         *  Clear applicable session data
         */
        $this->remove_bogo_applicable_count_session($coupon_code);

        if('yes'==$wt_enable_product_category_restriction) /* Product/category restriction enabled */
        {
            // Usage restriction "AND" for products       
            if('and'==$wt_product_condition || 'or'==$wt_product_condition)
            {
                $valid = true;
                $coupon_products = $coupon->get_product_ids();
                if(count($coupon_products)>0)
                {
                    $wt_sc_coupon_products = self::get_coupon_meta_value($coupon_id, '_wt_sc_coupon_products');
                    $wt_sc_products_data = self::prepare_items_data($coupon_products, $wt_sc_coupon_products);

                    $items_to_check = array();
                    $items_to_check_qty= array();
                    $items_to_check_name= array();
                    
                    foreach($items as $item)
                    {
                        /* is free item check */
                        if(!is_null($giveaway_obj) && $giveaway_obj->is_a_free_item($item))
                        {
                            continue;
                        }

                        $product_name=(is_object($item['data']) && method_exists($item['data'], 'get_name') ?  ("'".$item['data']->get_name()."'")  : __('the product', 'wt-smart-coupons-for-woocommerce-pro'));
                        array_push($items_to_check, $item['product_id']);
                        
                        if(isset($item['variation_id']) && $item['variation_id']>0) /* add variation id, if its a variable product */
                        {
                            array_push($items_to_check, $item['variation_id']);
                            $items_to_check_qty[$item['variation_id']] = $item['quantity'];
                            $items_to_check_name[$item['variation_id']] = $product_name;
                        }

                        $items_to_check_qty[$item['product_id']] = (isset($items_to_check_qty[$item['product_id']]) ? $items_to_check_qty[$item['product_id']]+$item['quantity'] : $item['quantity']);
                        $items_to_check_name[$item['product_id']] = $product_name;
                    }

                    /** 
                     * or condition, already validated by WC and here we are checking the min/max quantity, 
                     * If individual quantity validation enabled. 
                     * And also preparing eligibility count for both individual and non individual quantity restriction 
                     **/
                    if('or'==$wt_product_condition) 
                    {
                        $valid_products=0;
                        foreach($coupon_products as $product_id) /* loop through the coupon products */
                        {
                            if(in_array($product_id, $items_to_check) && isset($wt_sc_products_data[$product_id])) /* coupon product found, product min/max data available in meta value array */ 
                            {                               
                                if('yes'==$use_individual_min_max) /* individual quantity validation enabled */
                                {
                                    $valid=true; /* reset the valid value, may be the previous item is not a valid item */
                                    $this->individual_min_max_quantity_validation($coupon_code, $product_id, $wt_sc_products_data, $items_to_check_qty, $items_to_check_name, $valid, false);
                                    if($valid)
                                    {
                                        $valid_products++;
                                        $this->set_applicable_count_by_qty('product', $coupon_code, $product_id, $items_to_check_qty, $wt_sc_products_data);
                                    }
                                }else
                                {
                                    /* just for apply repeatedly functionality */
                                    $this->set_applicable_count_by_qty('product', $coupon_code, $product_id, $items_to_check_qty, $wt_sc_products_data);
                                }                               
                            }
                        }

                        if('yes' === $use_individual_min_max) /* individual quantity validation enabled */
                        {
                            if(0 === $valid_products) /* no products have valid quantity */
                            {
                                if("" !== ($msg = $this->get_individual_min_max_quantity_validation_message('', '', $coupon_code)))
                                {   
                                    throw new Exception($msg, 112);
                                }

                                $valid=false;
                            }else
                            {
                                $valid=true;
                            }
                        }

                    }else
                    {    
                        $total_valid_arr=array();           
                        foreach($coupon_products as $product_id)
                        {
                            if(!in_array($product_id, $items_to_check))
                            {
                                //clear coupon applicable session data
                                $this->remove_bogo_applicable_count_session($coupon_code);
                                
                                $valid = false;
                                break;

                            }else /* product found */
                            {
                                if('yes'==$use_individual_min_max) /* do quantity check for individual product */
                                {
                                    if(isset($wt_sc_products_data[$product_id])) /* product min/max data available in meta value array */
                                    {
                                        $this->individual_min_max_quantity_validation($coupon_code, $product_id, $wt_sc_products_data, $items_to_check_qty, $items_to_check_name, $valid);
                                        if(!$valid)
                                        {
                                            break;
                                        }
                                    }

                                }

                                if($valid)
                                {
                                    $total_valid_arr=$this->prepare_applicable_count_by_qty_for_and_condtion($total_valid_arr, $product_id, $wt_sc_products_data, $items_to_check_qty);
                                }
                            }
                        }

                        //condition is `and` so need to match all conditions
                        if($valid && !empty($total_valid_arr))
                        {
                            $this->process_applicable_count_by_qty_for_and_condition('product', $coupon_code, $total_valid_arr);
                        }
                    }
                        
                    if(!$valid)
                    {
                        if("" !== ($msg = $this->get_customized_text('product_validation', array('coupon_code' => $coupon_code))))
                        {   
                            throw new Exception($msg, 109);
                        }else
                        {
                            return $valid;
                        }
                    }
                }
            }


            $wt_category_condition = get_post_meta($coupon_id, '_wt_category_condition', true);
            if('and'==$wt_category_condition || 'or'==$wt_category_condition)
            {
                $valid = true;

                $coupon_categories = $coupon->get_product_categories();
                if(count($coupon_categories)>0)
                {
                    $categories_data = self::get_categories_data($coupon);

                    $items_to_check = array();
                    $items_to_check_qty = array();
                    $items_to_check_name = array();
                    foreach($items as $item)
                    {                   
                        /* is free item check */
                        if(!is_null($giveaway_obj) && $giveaway_obj->is_a_free_item($item))
                        {
                            continue;
                        }

                        $product_cats = Wt_Smart_Coupon_Common::get_product_cat_ids($item['product_id']);
                        $matching_cats = array_intersect($product_cats, $coupon_categories);
                        
                        if(empty($matching_cats))
                        {
                            continue;
                        }
                        
                        /** prepare quantity */
                        foreach($matching_cats as $product_cat)
                        {
                            if(!isset($items_to_check_qty[$product_cat]))
                            {
                                $items_to_check_qty[$product_cat]=$item['quantity'];
                            }else{
                                $items_to_check_qty[$product_cat]+=$item['quantity'];
                            }
                        }
                        
                        $items_to_check = array_merge($items_to_check, $matching_cats);
                    }
                    

                    if(empty($items_to_check)) /* no products from the given category in the cart */
                    {
                        //clear coupon applicable session data
                        $this->remove_bogo_applicable_count_session($coupon_code);
                        $valid=false;
                    }
 
                    if($valid)
                    {
                        /**
                         *  OR condition, already validated by WC, here we are checking the min/max quantity, if individual quantity validation enabled.
                         *  And also preparing eligibility count for both individual and non individual quantity restriction
                         */
                        if('or'==$wt_category_condition) 
                        {
                            $valid_cats = 0;
                            foreach($coupon_categories as $category_id) /* loop through the coupon categories */
                            {
                                if(in_array($category_id, $items_to_check) && isset($categories_data[$category_id])) /* coupon category found, category min/max data available in meta value array */ 
                                {         
                                    if('yes'==$use_individual_min_max) /* individual quantity validation enabled */
                                    {
                                        /* prepare term name for error message */
                                        $this->prepare_term_name_for_validation_error_message($category_id, $items_to_check_name);

                                        $valid = true;

                                        $this->individual_min_max_quantity_validation($coupon_code, $category_id, $categories_data, $items_to_check_qty, $items_to_check_name, $valid, false, true);

                                        if( $valid )
                                        {
                                            $valid_cats++;
                                            $this->set_applicable_count_by_qty( 'category', $coupon_code, $category_id, $items_to_check_qty, $categories_data ); 
                                        }
                                    }else{
                                        $this->set_applicable_count_by_qty( 'category', $coupon_code, $category_id, $items_to_check_qty, $categories_data ); 
                                    }
                                             
                                }
                            }

                            if( 'yes' === $use_individual_min_max ) /* individual quantity validation enabled */
                            {
                                if( 0 === $valid_cats ) /* no products have valid quantity */
                                {
                                    if( "" !== ( $msg = $this->get_individual_min_max_quantity_validation_message('', '', $coupon_code ) ) )
                                    {   
                                        throw new Exception( $msg, 112 );
                                    }

                                    $valid = false;
                                }else
                                {
                                    $valid = true;
                                }
                            }

                        }else
                        {
                            $total_valid_arr=array();
                            foreach($coupon_categories as $category_id)
                            {              
                                if(!in_array($category_id, $items_to_check))
                                {
                                    //clear coupon applicable session data
                                    $this->remove_bogo_applicable_count_session($coupon_code);

                                    $valid = false;
                                    break;
                                }else  /* category found */
                                {
                                    if('yes'==$use_individual_min_max) /* category min/max data available in meta value array */
                                    {
                                        if(isset($categories_data[$category_id])) /* category min/max data available in meta value array */
                                        {
                                            /* prepare term name for error message */
                                            $this->prepare_term_name_for_validation_error_message($category_id, $items_to_check_name);

                                            $this->individual_min_max_quantity_validation($coupon_code, $category_id, $categories_data, $items_to_check_qty, $items_to_check_name, $valid, true, true);
                                            if(!$valid)
                                            {
                                                break;
                                            }
                                        }
                                    }

                                    if($valid)
                                    {
                                        $total_valid_arr=$this->prepare_applicable_count_by_qty_for_and_condtion($total_valid_arr, $category_id, $categories_data, $items_to_check_qty); 
                                    }

                                }
                            } 
                            
                            //condition is `and` so need to match all conditions
                            if($valid && !empty($total_valid_arr))
                            {
                                $this->process_applicable_count_by_qty_for_and_condition('category', $coupon_code, $total_valid_arr);
                            }

                        }
                    }

                    if(!$valid)
                    {

                        $msg = sprintf(__('Sorry, the coupon %s is not applicable for selected products.', 'wt-smart-coupons-for-woocommerce-pro'), $coupon_code);

                        if("" !== $msg)
                        {   
                            throw new Exception($msg, 109);
                        }else
                        {
                            return $valid;
                        }
                    }
                                  
                }
            }
           
            $coupon_products =  $coupon->get_product_ids();
            $coupon_categories = $coupon->get_product_categories(); 
            $coupon_excluded_categories = $coupon->get_excluded_product_categories();
            $coupon_excluded_products = $coupon->get_excluded_product_ids();

            
            /**
             *  Coupon tags
             * 
             *  @since 2.0.8
             */
            $coupon_tags = self::get_coupon_meta_value($coupon_id, '_wt_sc_product_tags');

            if(!empty($coupon_tags))
            {
                $coupon_tag_valid = false;

                foreach($items as $item)
                {
                    $product_tags = get_the_terms($item['product_id'], 'product_tag');
                    $product_tags = ($product_tags && is_array($product_tags) ? array_column($product_tags, 'term_id') : array());

                    if(count(array_intersect($product_tags, $coupon_tags)) > 0)
                    {
                        $coupon_tag_valid = true;
                        break;
                    }
                }

                if(!$coupon_tag_valid)
                {
                    if("" !== ($msg = $this->get_customized_text('tag_validation', array('coupon_code' => $coupon_code))))
                    {   
                        throw new Exception($msg, 109);
                    }else
                    {
                        return $coupon_tag_valid;
                    }
                }
            }   
            
            /**
             * Coupon Attributes
             * 
             *  @since 2.0.8
             */
            $coupon_attributes = self::get_coupon_meta_value($coupon_id, '_wt_sc_product_attributes');

            if(!empty($coupon_attributes))
            {
                $coupon_attribute_valid = false;

                foreach($items as $item)
                {
                    if( isset($item['variation_id']) && $item['variation_id']>0){

                        $product = $item['data'];
                        $attributes_array = $product->get_attributes();

                        if( count(array_intersect(array_keys($attributes_array),$coupon_attributes)) > 0)
                        {
                            $coupon_attribute_valid = true;
                            break;   
                        }
                    }
                }

                if(!$coupon_attribute_valid)
                {
                    if("" !== ($msg = $this->get_customized_text('attribute_validation', array('coupon_code' => $coupon_code))))
                    {   
                        throw new Exception($msg, 109);
                    }else
                    {
                        return $coupon_attribute_valid;
                    }
                }

            }
        }


        /**
         *  [Fix] Incorrect eligibility calculation when a user enabled `product and category restriction` and `individual quantity` but not added any products or category.
         *  
         *  @since 2.3.0 
         */
        if ( empty( $coupon_products ) && empty( $coupon_categories ) && 'yes' === $use_individual_min_max ) {
            $use_individual_min_max = 'no';
        }


        /**
         *  Quantity of matching Products
         */
        $wt_min_matching_product_qty = absint($this->get_coupon_meta_value($coupon_id, '_wt_min_matching_product_qty'));
        $wt_max_matching_product_qty = absint($this->get_coupon_meta_value($coupon_id, '_wt_max_matching_product_qty'));   
        
        // When bogo_customer_gets is 'any_product_from_category_in_the_cart' and catergory restriction is empty
        if( 'any_product_from_category_in_the_cart' === $this->get_coupon_meta_value( $coupon_id, '_wt_sc_bogo_customer_gets' ) ){
            $wt_min_matching_product_qty = absint( $this->get_coupon_meta_value( $coupon_id, '_wt_min_cat_qty' ) );
            $wt_max_matching_product_qty = absint($this->get_coupon_meta_value( $coupon_id, '_wt_max_cat_qty' ) );   
        }
        
        $wt_min_matching_product_qty = (0 === $wt_min_matching_product_qty ? 1 : $wt_min_matching_product_qty);
        
        if(0 < $wt_min_matching_product_qty || 0 < $wt_max_matching_product_qty)
        {
            if('no' === $wt_enable_product_category_restriction)
            {
                $quantity_of_matching_product = 0;
                $total_valid_arr = array();
                
                foreach($items as $item)
                {
                    /* is free item check */
                    if(!is_null($giveaway_obj) && $giveaway_obj->is_a_free_item($item))
                    {
                        continue;
                    }

                    if('yes' === $use_individual_min_max)
                    {
                        if(0 < $wt_min_matching_product_qty && $item['quantity']<$wt_min_matching_product_qty)
                        {
                            $valid = false;                         
                            $this->remove_bogo_applicable_count_session($coupon_code);
                            
                            if("" !== ($msg = $this->get_quantity_restriction_messages($coupon_code, $wt_min_matching_product_qty, false)))
                            {
                                throw new Exception($msg, 110);
                            }else
                            {
                                return $valid;
                            }

                            break;
                        }

                        
                        /**
                         *  This is for finding the eligibility contribution of the current product/category
                         */
                        if($valid)
                        {
                            $item_id=(0 < $item['variation_id'] ? $item['variation_id'] : $item['product_id']);
                            $wt_sc_coupon_eligibility_contribution[$coupon_code][$item_id]=array($wt_min_matching_product_qty, $item['quantity']);
                        }


                        if(0 < $wt_max_matching_product_qty && $item['quantity']>$wt_max_matching_product_qty)
                        {            
                            $valid = false;
                            $this->remove_bogo_applicable_count_session($coupon_code);               
                            
                            if("" !== ($msg = $this->get_quantity_restriction_messages($coupon_code, $wt_max_matching_product_qty, false, 'max')))
                            {
                                throw new Exception($msg, 111);
                            }else
                            {
                                return $valid;
                            }

                            break;
                        }

                        //if code reached here, then it must be a valid product
                        $total_valid_arr = $this->prepare_applicable_count_by_global_individual_qty($total_valid_arr, $item['quantity'], $wt_min_matching_product_qty);

                    }else //global quantity, so calculate total quantity
                    {
                        $quantity_of_matching_product += $item['quantity'];
                    }
                }

                if('no' === $use_individual_min_max) //global quantity
                {
                    $valid = $this->validate_min_max_global_qty($coupon_code, $valid, $quantity_of_matching_product, $wt_min_matching_product_qty, $wt_max_matching_product_qty);

                    if($valid)
                    {
                        $this->set_applicable_count_by_global_qty('quantity', $coupon_code, $quantity_of_matching_product, $wt_min_matching_product_qty);

                        /**
                         *  This is for finding the eligibility contribution of the current product/category
                         */
                        $wt_sc_coupon_eligibility_contribution[$coupon_code]['global_min_quantity']=$wt_min_matching_product_qty;
                        $wt_sc_coupon_eligibility_contribution[$coupon_code]['global_cart_quantity']=$quantity_of_matching_product;

                    }
                }else
                {
                    if($valid && !empty($total_valid_arr))
                    {   
                        $this->process_applicable_count_by_global_individual_qty('quantity', $coupon_code, $total_valid_arr);
                    }
                }

            }else
            {
                if('no' === $use_individual_min_max) /* Only check if global quantity check is enabled */
                {
                    $quantity_of_matching_product = $this->get_quantity_of_matching_product($coupon, $coupon_products, $coupon_categories, $coupon_excluded_products, $coupon_excluded_categories, array('coupon_tags' => $coupon_tags , 'coupon_attributes' => $coupon_attributes));
                    $valid = $this->validate_min_max_global_qty($coupon_code, $valid, $quantity_of_matching_product, $wt_min_matching_product_qty, $wt_max_matching_product_qty);

                    if($valid)
                    {
                        /**
                         *  This is for finding the eligibility contribution of the current product/category
                         */
                        $wt_sc_coupon_eligibility_contribution[$coupon_code]['global_min_quantity']=$wt_min_matching_product_qty;
                        $wt_sc_coupon_eligibility_contribution[$coupon_code]['global_cart_quantity']=$quantity_of_matching_product;

                        $this->set_applicable_count_by_global_qty('quantity', $coupon_code, $quantity_of_matching_product, $wt_min_matching_product_qty);
                    }
                }
            }
        }       

        // Subtotal of matching products
        $wt_min_matching_product_subtotal = $this->get_coupon_meta_value($coupon_id, '_wt_min_matching_product_subtotal');
        $wt_max_matching_product_subtotal = $this->get_coupon_meta_value($coupon_id, '_wt_max_matching_product_subtotal');
        
        $subtotal_of_matching_product = $this->get_subtotal_of_matching_products( $coupon, $coupon_products, $coupon_categories, $coupon_excluded_products, $coupon_excluded_categories, array( 'coupon_tags' => $coupon_tags , 'coupon_attributes' => $coupon_attributes ) );
        
        if($wt_min_matching_product_subtotal>0)
        {
            if($subtotal_of_matching_product<$wt_min_matching_product_subtotal)
            {
                if(in_array($coupon->get_code(), $woocommerce->cart->get_applied_coupons()))
                {
                    if($subtotal_of_matching_product < $wt_min_matching_product_subtotal )
                    {
                        $valid = false;
                        $this->remove_bogo_applicable_count_session($coupon_code);                       
                        $msg = $this->get_customized_text('minimum_subtotal', array('coupon_code' => $coupon_code, 'minimum_subtotal' => Wt_Smart_Coupon_Admin::get_formatted_price($wt_min_matching_product_subtotal)));

                        if("" !== $msg)
                        {
                            throw new Exception($msg, 112);
                        }
                    }
                }else
                {
                    $valid = false;
                    $this->remove_bogo_applicable_count_session($coupon_code);
                    
                    $msg = $this->get_customized_text('minimum_subtotal', array('coupon_code' => $coupon_code, 'minimum_subtotal' => Wt_Smart_Coupon_Admin::get_formatted_price($wt_min_matching_product_subtotal)));

                    if("" !== $msg)
                    {
                        throw new Exception($msg, 112);
                    }else
                    {
                        return $valid;
                    }
                }
            }

            //if code reached here, then it must be a valid coupon
            $this->set_applicable_count_by_subtotal('subtotal', $coupon_code, $wt_min_matching_product_subtotal, $subtotal_of_matching_product);

            /**
             *  This is for finding the eligibility contribution of the current product/category
             */
            $wt_sc_coupon_eligibility_contribution[$coupon_code]['min_subtotal']=$wt_min_matching_product_subtotal;
            $wt_sc_coupon_eligibility_contribution[$coupon_code]['cart_subtotal']=$subtotal_of_matching_product;

        }

        if($wt_max_matching_product_subtotal>0 && $subtotal_of_matching_product>$wt_max_matching_product_subtotal)
        {            
            $valid = false;
            $this->remove_bogo_applicable_count_session($coupon_code);               
            $msg = $this->get_customized_text('maximum_subtotal', array('coupon_code' => $coupon_code, 'maximum_subtotal' => Wt_Smart_Coupon_Admin::get_formatted_price($wt_max_matching_product_subtotal)));

            if("" !== $msg)
            {
                throw new Exception($msg, 113);
            }else
            {
                return $valid;
            }
        }


        /* How many times the user can avail the coupon benefits */
        if($valid)
        {
            $this->prepare_final_applicable_count($coupon_code);
        }else
        {
            $this->remove_bogo_applicable_count_session($coupon_code);
        }

        return $valid;
    }

    public static function get_eligibility_contribution($coupon_code, $quantity, $cart_item_price, $product_id, $variation_id)
    {
        global $wt_sc_coupon_eligibility_contribution;

        if(!isset($wt_sc_coupon_eligibility_contribution[$coupon_code]))
        {
            $wt_sc_coupon_eligibility_contribution[$coupon_code]=array();
        }

        $coupon_info=$wt_sc_coupon_eligibility_contribution[$coupon_code];

        $eligibility_qty_arr=array();
        if(isset($coupon_info[$product_id]) || isset($coupon_info[$variation_id]))
        {
            $product_qty=0;
            $variation_qty=0;
            if(isset($coupon_info[$product_id]))
            {
                $product_qty=($coupon_info[$product_id][0]-($coupon_info[$product_id][1]-$quantity));
            }

            if(isset($coupon_info[$variation_id]))
            {
                $variation_qty=($coupon_info[$variation_id][0]-($coupon_info[$variation_id][1]-$quantity));
            }
            $eligibility_qty_arr[]=max($product_qty, $variation_qty); /* in some cases parent product and variable products are in the restriction section */
        }

        if(isset($coupon_info['category'])) /* category restriction is there, then check current product is included? */
        {
            $cat_ids=array_keys($coupon_info['category']);
            $product_cats = Wt_Smart_Coupon_Common::get_product_cat_ids($product_id);
            $matching_cats=array_intersect($cat_ids, $product_cats);
            if(!empty($matching_cats)) //product included in the category restriction
            {
                foreach($matching_cats as $cat_id) //may be the product included in multiple categories
                {
                    $eligibility_qty_arr[]=($coupon_info['category'][$cat_id][0]-($coupon_info['category'][$cat_id][1]-$quantity));
                }
            }
        }

        /* empty means indvidual product/category restriction is not available, so check for global qty*/
        if(empty($eligibility_qty_arr) && isset($coupon_info['global_min_quantity']) && isset($coupon_info['global_cart_quantity'])) 
        {
            $eligibility_qty_arr[] = ($coupon_info['global_min_quantity'] - ($coupon_info['global_cart_quantity']-$quantity));
        }

        // subtotal 
        if(isset($coupon_info['min_subtotal']) && isset($coupon_info['cart_subtotal']))
        {
            $eligibility_subtotal = ($coupon_info['min_subtotal'] - ($coupon_info['cart_subtotal'] - ($quantity * $cart_item_price)));
            $eligibility_qty_arr[] = ($eligibility_subtotal / $cart_item_price);
        }

        return (!empty($eligibility_qty_arr) ? max($eligibility_qty_arr) : 1);
    }

    private function validate_min_max_global_qty($coupon_code, $valid, $quantity_of_matching_product, $wt_min_matching_product_qty, $wt_max_matching_product_qty)
    {
        if($wt_min_matching_product_qty>0 && $quantity_of_matching_product<$wt_min_matching_product_qty)
        {
            $valid = false;
            
            //clear coupon applicable session data
            $this->remove_bogo_applicable_count_session($coupon_code);

            if("" !== ($msg = $this->get_quantity_restriction_messages($coupon_code, $wt_min_matching_product_qty, true)))
            {
                throw new Exception($msg, 110);
            }else
            {
                return $valid;
            }
        }
        
        if($wt_max_matching_product_qty>0 && $quantity_of_matching_product>$wt_max_matching_product_qty)
        {            
            $valid = false;

            //clear coupon applicable session data
            $this->remove_bogo_applicable_count_session($coupon_code);

            if("" !== ($msg = $this->get_quantity_restriction_messages($coupon_code, $wt_max_matching_product_qty, true, 'max')))
            {
                throw new Exception($msg, 111);
            }else
            {
                return $valid;
            }

        }

        return $valid;
    }

    
    /**
     *  Exclude the products from applying discount that are not satisfying the min/max quatity restriction.
     *  This is applicable when product condition is `any(or)`
     * 
     *  @since    2.0.5
     *  @since    2.0.6     [Bug fix] When multiple coupons with same product restriction and different quantitiy restriction occurs,
     *                       items in disqualified array is checked for individual coupon code instead of one single array to avoid the product quantity eligibility issues.
     *  @since    3.0.0     Check if the product category is in the disqualified array.
     * 
     */
    public function exclude_disqualified_products($valid, $product, $coupon, $values)
    {   
        $coupon_code = $coupon->get_code();

        if(isset($this->disqualified[$coupon_code]) && !empty($this->disqualified[$coupon_code])) //only proceeds if any item in the array of disqualified items is present in cart
        {
            $disqualified_products = $this->disqualified[$coupon_code];
            $product_id = ($product->get_parent_id()>0 ? $product->get_parent_id() : $product->get_id());
            $variation_id = ($product->get_parent_id()>0 ? $product->get_id() : 0); 
            
            $product_cat_ids = Wt_Smart_Coupon_Common::get_product_cat_ids( $product_id );
        
            if( in_array( $product_id, $disqualified_products, true ) || in_array( $variation_id, $disqualified_products, true ) || !empty( array_intersect( $product_cat_ids, $disqualified_products ) ) )
            {
                $valid = false;
            }     
        }

        return $valid;
    }

    private function get_quantity_restriction_messages($coupon_code, $qty, $is_global, $type='min')
    {
        $out='';
        
        if('min' === $type)
        {
            if($is_global)
            {
                $out = $this->get_customized_text('global_min', array('coupon_code' => $coupon_code, 'required_quantity' => $qty));
            }else
            {
                $out = $this->get_customized_text('global_individual_min', array('coupon_code' => $coupon_code, 'required_quantity' => $qty));
            }
        }else
        {
            if($is_global)
            {
                $out = $this->get_customized_text('global_max', array('coupon_code' => $coupon_code, 'required_quantity' => $qty));
            }else
            {
                $out = $this->get_customized_text('global_individual_max', array('coupon_code' => $coupon_code, 'required_quantity' => $qty));
            }
        }

        return apply_filters('wt_sc_alter_quantity_restriction_messages', $out, $coupon_code, $qty, $is_global, $type); 
    }

    /**
     *  Checks current page is order edit page or backend coupon applying via ajax
     * 
     *  @since 2.0.7
     *  @since 2.0.8        Added HPOS Compatibility
     *  @return int|bool    Order id/true on success otherwise false
     */
    private function is_order_edit_page()
    {
        $basename = basename(parse_url($_SERVER['PHP_SELF'], PHP_URL_PATH));

        if('post.php' === $basename)
        {
            $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;

            if(0 < $post_id && 'shop_order' === get_post_type($post_id))
            {
                return $post_id;
            }

        }elseif('post-new.php' === $basename)
        {
            $post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : '';

            if('shop_order' === $post_type)
            {
                return true;   
            }

        }elseif('admin.php' === $basename)
        {
            $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : ''; 
            $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : ''; 
            $order_id = isset($_GET['id']) ? absint($_GET['id']) : 0; 

            if('wc-orders' === $page && ('edit' === $action || 'new' === $action))
            {
                return ($order_id ? $order_id : true);  //id for order edit otherwise true 
            }

        }else
        {
            //ajax apply coupon
            $action = isset($_POST['action']) ? sanitize_text_field($_POST['action']) : '';

            if('woocommerce_add_coupon_discount' === $action)
            {
                return isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
            }
        }

        return false;
    }


    /**
     *  Prepare cart/order items from WC_Discounts items
     * 
     *  @since 2.0.8
     *  @since 2.4.2 Moved hook 'wt_sc_bypass_is_admin_check' to seperate function in main public file.
     * 
     *  @return array     Get items in cart/order, return empty array if not found any
     */
    public function get_items()
    {
        if( Wt_Smart_Coupon_Public::is_admin() ) //admin page
        {   
            $order_id = $this->is_order_edit_page(); //check order edit page or backend coupon applying via ajax
            
            if(false === $order_id) //not order edit page
            {
                return array();
            }

            $order = wc_get_order($order_id);

            if(!$order) //unable to get order object
            {
                return array();
            }
            
            self::$discounts_obj = new WC_Discounts($order);
            /**
             *  Convert order items like cart items. 
             */
            $items = Wt_Smart_Coupon_Common::convert_order_item_like_cart_item(self::$discounts_obj->get_items_to_validate());
            
            
        }else
        {
            $cart = WC()->cart;

            if(is_null($cart))
            {
                return array();
            }

            $items = array();
            self::$discounts_obj = new WC_Discounts($cart);

            /**
             *  Preparing items from WC_Discounts items
             */
            foreach(self::$discounts_obj->get_items_to_validate() as $key => $discount_obj_cart_item)
            {
                $items[$key] = $discount_obj_cart_item->object;
            }

        }

        return $items;
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
     *  Category restriction data. Includes min/max quantity
     *       
     * 
     *  @since  2.0.8
     *  @param  WC_Coupon   $coupon     Coupon object
     *  @return array       Category data array
     *                      array(
     *                          [(int) category_id] => 
     *                              array(
     *                                  'min' => (int) minimum quantity, 
     *                                  'max' => (int) maximum quantity,
     *                              )
     *                        )
     */
    public static function get_categories_data($coupon)
    {
        $categories_data = array();
        $coupon_categories = $coupon->get_product_categories();
        
        if(count($coupon_categories) > 0)
        {
            $coupon_id = $coupon->get_id();
            $wt_sc_coupon_categories = self::get_coupon_meta_value($coupon_id, '_wt_sc_coupon_categories');
            $categories_data = self::prepare_items_data($coupon_categories, $wt_sc_coupon_categories);
        }

        return $categories_data;
    }
}
Wt_Smart_Coupon_Restriction_Public::get_instance();