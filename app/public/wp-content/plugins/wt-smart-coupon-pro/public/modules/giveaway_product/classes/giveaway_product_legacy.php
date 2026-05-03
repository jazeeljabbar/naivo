<?php
/**
 * Legacy codes for Giveaway Product public section.
 *
 * @link       
 * @since 2.1.1     
 *
 * @package  Wt_Smart_Coupon  
 */
if (!defined('ABSPATH')) {
    exit;
}

if ( ! class_exists( 'Wt_Smart_Coupon_Giveaway_Product_Public' ) ) {
    return;
}

class Wt_Smart_Coupon_Giveaway_Product_Public_Legacy extends Wt_Smart_Coupon_Giveaway_Product_Public
{
    private static $instance = null;

    
    /**
     *  Get Instance
     * 
     *  @since 2.1.1
     */
    public static function get_instance() {
        
        if ( is_null( self::$instance ) ) {
            self::$instance = new Wt_Smart_Coupon_Giveaway_Product_Public_Legacy();
        }
        return self::$instance;
    }

    
    /**
     *  Convert to giveaway for `any_product_from_category`.
     *  This function is only using for `all` category condition.
     * 
     *  @since 2.1.1
     */
    public function set_as_giveaway__any_product_from_category($coupon_code, $coupon_id, $item_id, $cart_items, $matching_cats, $bogo_free_categories, &$quantity) 
    {
        $cat_qty_arr = array();

        foreach($cart_items as $item_key => $cart_item)
        {
            if(self::is_a_free_item($cart_item, $coupon_code)) /* a free item under the given coupon */
            {
                if(isset($cart_item['free_category']) && in_array($cart_item['free_category'], $matching_cats))
                {
                    if(isset($cat_qty_arr[$cart_item['free_category']]))
                    {
                        $cat_qty_arr[$cart_item['free_category']] += $cart_item['quantity'];
                    }else
                    {
                        $cat_qty_arr[$cart_item['free_category']] = $cart_item['quantity'];
                    }
                }
            }
        }


        $product = wc_get_product($item_id);

        $matching_cat_data = $this->sort_category_by_profit($matching_cats, $bogo_free_categories, $product, $coupon_id); /* sort the category based on discount, update quantity based on `Apply repeatedly` option */

        $total_allowed_free_qty_for_cats = array_sum(array_column($matching_cat_data, 'qty')); //maximum free products allowed for the categories. 
        $total_free_qty_for_cats_in_the_cart = array_sum($cat_qty_arr);
        $total_qty_for_free = 0;

        if($total_allowed_free_qty_for_cats > $total_free_qty_for_cats_in_the_cart) //balance qty exists in allowed maximum
        {
            $balance_discount_qty = $total_allowed_free_qty_for_cats - $total_free_qty_for_cats_in_the_cart;
            $total_qty_for_free = min($quantity, $balance_discount_qty);
            $quantity -= $total_qty_for_free; //any balance will added to next coupon, in the next iteration
        }

        foreach($matching_cat_data as $matching_cat => $cat_data)
        {
            if(0 === (int) $cat_data['qty']) //no qty added by admin, so skip
            {
                continue;
            }
            if(0 >= $total_qty_for_free)
            {
                break;
            }

            $total_qty_in_cart = isset($cat_qty_arr[$matching_cat]) ? $cat_qty_arr[$matching_cat] : 0; /* total quantity for the category in the cart */  
            
            if($total_qty_in_cart < $cat_data['qty']) /* total quantity in the cart is lesser than the maximum allowed */
            {
                $qty_for_current_cat = min($total_qty_for_free, ($cat_data['qty'] - $total_qty_in_cart));
                $this->add_item_to_cart($item_id, $qty_for_current_cat, $coupon_code, $matching_cat);
                $total_qty_for_free -= $qty_for_current_cat;
            }
        }


        /* check and set is bogo fully availed or not */
        self::set_bogo_fully_availed($coupon_id, $coupon_code, $total_allowed_free_qty_for_cats, ($total_free_qty_for_cats_in_the_cart + $total_qty_for_free));

    }

    
    /**
     *  Adjust the giveaway count when eligibility changed.
     *  This function is only using for `all` category condition.
     * 
     *  @since 2.1.1
     */
    public function adjust_giveaway_count_when_eligibility_changed__any_product_from_category($coupon_code, $coupon_id, $cart_items)
    {
        $bogo_free_categories = $this->get_coupon_meta_value($coupon_id, '_wt_sc_bogo_free_categories');
        $bogo_free_category_ids = array_keys($bogo_free_categories);

        $cat_qty_arr = array();
        
        foreach($cart_items as $item_key => $cart_item)
        {
            if(self::is_a_free_item($cart_item, $coupon_code)) /* a free item under the given coupon */
            {
                if(isset($cart_item['free_category']))
                {
                    $product_cats = Wt_Smart_Coupon_Common::get_product_cat_ids($cart_item['product_id']);
                    $matching_cats = array_intersect($bogo_free_category_ids, $product_cats); /* $coupon_categories must be the first argument, because its in the order of product direct category then parent category. To maintain the order we have to use $coupon_categories as first argument */
                    if(empty($matching_cats)) /* this item is not belongs to the current coupon */
                    {
                        WC()->cart->remove_cart_item($item_key);
                        continue;
                    }

                    if(!isset($cat_qty_arr[$cart_item['free_category']]))
                    {
                        $cat_qty_arr[$cart_item['free_category']] = array();
                    }
                    $cat_qty_arr[$cart_item['free_category']][$item_key] = $cart_item['quantity'];
                }else
                {
                    WC()->cart->remove_cart_item($item_key); //no category information so this item may not belongs to current coupon
                }
            }
        }


        $fully_availed = true; /* here giving giveaway for all categories, so check for any of the category's giveaway was pending */
                    
        foreach($bogo_free_categories as $cat_id => $cat_data)
        {
            if(isset($cat_qty_arr[$cat_id]))
            {
                /* prepare max quantity for category with apply repeatedly */
                $max_qty_allowed = $this->prepare_quantity_based_on_apply_frequency($coupon_id, $cat_data['qty']);             
                $total_qty_in_cart = array_sum($cat_qty_arr[$cat_id]); /* total quantity for the category in the cart */                               

                if($max_qty_allowed < $total_qty_in_cart)
                {
                    foreach($cat_qty_arr[$cat_id] as $cart_item_key => $qty)
                    {
                        if(0 >= $max_qty_allowed)
                        {
                            WC()->cart->remove_cart_item($cart_item_key);
                            break;
                        }

                        if($qty <= $max_qty_allowed)
                        {
                            $max_qty_allowed = $max_qty_allowed - $qty;
                        }else
                        {
                            $this->update_cart_qty($cart_item_key, $max_qty_allowed);
                            $max_qty_allowed = 0;
                        }
                    }
                }elseif($max_qty_allowed > $total_qty_in_cart)
                {
                    $fully_availed = false;
                }

            }else
            {
               $fully_availed = false; 
            }
        }

        //trigger the function based on the $fully_availed
        $this->trigger_dummy_bogo_fully_availed($coupon_id, $coupon_code, $fully_availed);

    }


    /**
     *  Convert cheapest item as giveaway if the coupon giveaway option is `any_product_from_category`
     * 
     *  @since 2.1.1
     *  @param $coupon          WC_Coupon   WC_Coupon object
     *  @param $coupon_id       int         Coupon id
     *  @param $coupon_code     string      Coupon code  
     */
    public function apply_cheapest_giveaway_for__any_product_from_category($coupon, $coupon_id, $coupon_code) 
    {
        $bogo_free_categories   = $this->get_coupon_meta_value($coupon_id, '_wt_sc_bogo_free_categories');
        $bogo_free_category_ids = array_keys($bogo_free_categories);
               
        $already_converted_as_giveaway = array(); //cart item keys of giveaway items
        $price_of_eligibility_item_with_lowest_price = $this->get_price_of_eligibility_item_having_lowest_price($coupon, $coupon_id, $coupon_code);
        $frequency = $this->get_coupon_applicable_count($coupon_id, $coupon_code);

        foreach($bogo_free_category_ids as $category_id)
        {         
            category_loop_start: //we have to re-start from here in some cases

            $temp_arr   = array(); //temp cart items array
            
            //for sorting purpose
            $price_arr  = array();
            $coupon_arr = array();

            $cart_items = WC()->cart->get_cart(); //take a fresh list everytime.

            /**
             *  Prepare cart item list under the current category
             */
            foreach($cart_items as $cart_item_key => $cart_item)
            {
                if(in_array($cart_item_key, $already_converted_as_giveaway)) //skip the items that are already converted as giveaway for previous category
                {
                    continue;
                }

                $product_cats = Wt_Smart_Coupon_Common::get_product_cat_ids($cart_item['product_id']);
                
                if(in_array($category_id, $product_cats)) /* this item is belongs to the current coupon category */
                {
                    $item_id = ($cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id']);
                    $product = wc_get_product($item_id);
                    $product_price = self::get_product_price($product);

                    $cart_item['wt_price']      = $product_price;
                    $temp_arr[$cart_item_key]   = $cart_item;
                    $price_arr[]                = $product_price;
                    $coupon_arr[]               = (isset($cart_item['free_gift_coupon']) ? $cart_item['free_gift_coupon'] : '');       
                    
                }
            }  

            /**
             *  Check and convert as giveaway or normal items
             */
            if(!empty($temp_arr)) //items present in the current category
            {
                //sort the item by price descending first then reverse the array.
                array_multisort($price_arr, SORT_DESC, SORT_REGULAR, $temp_arr, $coupon_arr);              
                $temp_arr = array_reverse($temp_arr);

                //this is used to run multiple iteration when required
                $old_giveaway_count = 0;
                $new_giveaway_count = 0;

                $cat_data = $bogo_free_categories[$category_id];
                $eligibility_qty = $this->prepare_quantity_based_on_apply_frequency($coupon_id, $cat_data['qty'], $frequency);
                $eligibility_qty_back   = $eligibility_qty; //value backup
 
                //loop through the sorted cart items
                foreach($temp_arr as $cart_item_key => $cart_item)
                {
                    $item_id = ($cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id']);
                    $product_price = $cart_item['wt_price'];

                    /**
                     *  Do not add giveaway that has price higher than the lowest priced eligible item. 
                     */
                    if(!is_null($price_of_eligibility_item_with_lowest_price) && $price_of_eligibility_item_with_lowest_price < $product_price)
                    {
                        if(self::is_a_free_item($cart_item))
                        {
                            //convert as normal item
                            $this->convert_giveaway_cartitem_as_normal_cartitem($cart_item_key);
                            $old_giveaway_count += $cart_item['quantity'];
                        }

                        continue; //no need to do further checks
                    }

                    /**
                     *  Check the item is available for giveaway or already giveaway
                     */
                    if(self::is_a_free_item($cart_item)) //already a free item
                    {
                        $old_giveaway_count += $cart_item['quantity'];

                        if(0 === $eligibility_qty) //check eligibility is remaining
                        {
                            //convert as normal item
                            $this->convert_giveaway_cartitem_as_normal_cartitem($cart_item_key);

                        }else
                        {
                            //deduct eligibility
                            $to_deduct = $cart_item['quantity'];

                            //this is to skip this item in the next category loop
                            $already_converted_as_giveaway[] = $cart_item_key; 

                            if($cart_item['quantity'] > $eligibility_qty)
                            {
                                $to_deduct = $eligibility_qty;

                                //the specified quantity will convert as normal item
                                $this->convert_giveaway_cartitem_as_normal_cartitem($cart_item_key, ($cart_item['quantity'] - $eligibility_qty));
                            }

                            $new_giveaway_count += $to_deduct;
                            $eligibility_qty -= $to_deduct;
                        }

                    }else
                    {
                        $qty_available_for_giveaway = 0;
                        $cart_item_quantity = $cart_item['quantity'];

                        //loop through the eligibility qty
                        for($i = 0; $i < min($eligibility_qty, $cart_item_quantity); $i++)
                        {
                            $cart_items = WC()->cart->get_cart(); //need to take the cart list again to get the refreshed list.
                            
                            $cart_item = $cart_items[$cart_item_key];
                            $new_qty = $cart_item['quantity'] - 1;

                            $this->update_cart_qty($cart_item_key, $new_qty);

                            if(!$coupon->is_valid() || $frequency > $this->get_coupon_applicable_count($coupon_id, $coupon_code)) //coupon eligibility gone, or eligibility count reduced.
                            {                      
                                if(0 === $new_qty)
                                {
                                    $this->set_as_normal_cartitem($cart_item['product_id'], $cart_item['variation_id'], 1);

                                }else
                                {
                                   $this->update_cart_qty($cart_item_key, ($new_qty + 1)); 
                                }

                                break;  //break the loop
                            }else
                            {
                                $qty_available_for_giveaway++;
                            }

                        }

                        if($qty_available_for_giveaway > 0) //we got some quantity to convert as giveaway
                        {
                            $new_cart_item_key = $this->add_item_to_cart($item_id, $qty_available_for_giveaway, $coupon_code, $category_id); //add the quantity as giveaway
                            
                            if(false !== $new_cart_item_key)
                            {
                                $eligibility_qty = $eligibility_qty - $qty_available_for_giveaway; //deduct the currently converted quantity
                                $new_giveaway_count += $qty_available_for_giveaway;

                                //this is to skip this item in the next category loop
                                $already_converted_as_giveaway[]= $new_cart_item_key;
                            }
                        }
                    }
                }

                if(1 > self::$cheapest_giveaway_loop_count && $old_giveaway_count > $new_giveaway_count) //we have to recheck the items.
                {
                    self::$cheapest_giveaway_loop_count = 1; //to prevent indefinite loop

                    //repeat the check, because new giveaway count is lesser than old giveaway count
                    goto category_loop_start;
                }

                if(1 === self::$cheapest_giveaway_loop_count)
                {
                    self::$cheapest_giveaway_loop_count = 0; //reset the loop counter for next category. Otherwise second iteration check for next category will fail 
                }
            }         
        }
    }
}