<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="options_group wt_sc_bogo_coupon_giveaway_tab_content">
    <fieldset class="form-field" style="padding-bottom:0px !important;">
        <legend><?php _e('Customer gets', 'wt-smart-coupons-for-woocommerce-pro'); ?></legend>
        <select name="_wt_sc_bogo_customer_gets" class="wt_sc_form_toggle" wt_sc_form_toggle-target="_wt_sc_bogo_customer_gets" style="width:60%;">
            <?php
            foreach(self::customer_gets_data_arr() as $customer_get_k => $customer_get_v)
            {
                ?>
                <option <?php echo ($customer_get_k=="" ? 'disabled="disabled"' : ''); ?> value="<?php echo esc_attr($customer_get_k);?>" <?php selected($customer_get_k, $bogo_customer_gets);?>><?php echo esc_html($customer_get_v);?></option>
                <?php
            }
            ?>
        </select>
        
        <?php
        foreach(self::customer_gets_help_arr() as $customer_get_k => $customer_get_v)
        {
            ?>
            <p class="wt_sc_help_text wt_sc_conditional_help_text" style="margin:0px" data-sc-help-condition="[_wt_sc_bogo_customer_gets=<?php echo esc_attr($customer_get_k);?>]"><?php echo wp_kses_post($customer_get_v);?></p>
            <?php
        }
        ?>

    </fieldset>

    <!-- Specific products -->
    <fieldset class="form-field" wt_sc_form_toggle-id="_wt_sc_bogo_customer_gets" wt_sc_form_toggle-val="specific_product" wt_sc_form_toggle-level="1">
        <legend><?php _e('Product condition', 'wt-smart-coupons-for-woocommerce-pro'); ?></legend>                   
        <ul class="wc-radios wt_sc_coupon_meta_radios">
            <li><label><input name="_wt_sc_bogo_product_condition" value="or" type="radio" <?php checked('or', $bogo_product_condition);?>><?php _e('Any from below selection', 'wt-smart-coupons-for-woocommerce-pro'); ?></label></li>
            <li><label><input name="_wt_sc_bogo_product_condition" value="and" type="radio" <?php checked('and', $bogo_product_condition);?>><?php _e('All from below selection', 'wt-smart-coupons-for-woocommerce-pro'); ?></label></li>
        </ul>
    </fieldset>

    <fieldset class="form-field wt_sc_bogo_products_fieldset wt_sc_coupon_fieldset" wt_sc_form_toggle-id="_wt_sc_bogo_customer_gets" wt_sc_form_toggle-val="specific_product" wt_sc_form_toggle-level="1">
        <legend><?php _e('Products', 'wt-smart-coupons-for-woocommerce-pro'); ?></legend>
        <table class="wt_sc_coupon_meta_item_table" id="wt_sc_bogo_customer_gets_products">
            <thead>
                <tr>
                    <th><?php _e('Product', 'wt-smart-coupons-for-woocommerce-pro');?></th>
                    <th><?php _e('Quantity', 'wt-smart-coupons-for-woocommerce-pro');?></th>
                    <th colspan="2"><?php _e('Discount', 'wt-smart-coupons-for-woocommerce-pro');?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php
                if(empty($bogo_products_data)) /* add a dummy item for first time use */
                {
                    $bogo_products_data=array();
                    $bogo_products_data['--']=$dummy_qty_price;
                }
                $field_index=0;
                foreach($bogo_products_data as $product_id=>$product_data)
                {
                    $product_title = '';
                    if ( '--' !== $product_id ) { /* not a dummy item */
                        $product = wc_get_product( $product_id );
                        if ( ! is_object( $product ) ) {
                            $product_title = __( 'Product not found !!!', 'wt-smart-coupons-for-woocommerce-pro' );
                        } else {
                            $product_title = wp_strip_all_tags( $product->get_formatted_name() );
                        }
                    }
                    ?>
                    <tr>
                        <td class="wt_sc_meta_item_tb_item">
                            <select class="wt_sc_product_search wt_sc_select2" data-default-val="" name="_wt_sc_bogo_free_product_ids[<?php echo esc_attr($field_index);?>]" data-placeholder="<?php esc_attr_e( 'Search for a product...', 'wt-smart-coupons-for-woocommerce-pro' ); ?>">
                            <?php 
                            if ( '--' !== $product_id ) { /* not a dummy item */                          
                                echo '<option value="' . esc_attr($product_id) . '"' . selected(true, true, false) . '>' . esc_html( $product_title ) . '</option>';
                            }
                            ?>
                            </select>
                        </td>
                        <td class="wt_sc_meta_item_tb_qty wt_sc_meta_item_tb_other">
                            <input type="number" name="_wt_sc_bogo_free_product_qty[<?php echo esc_attr($field_index);?>]" value="<?php echo esc_attr($product_data['qty']);?>" data-default-val="1" min="1" step="1">
                        </td>
                        <td class="wt_sc_meta_item_tb_price">
                            <input type="number" name="_wt_sc_bogo_free_product_price[<?php echo esc_attr($field_index);?>]" value="<?php echo esc_attr($product_data['price']);?>" data-default-val="100" min="0" step="any">
                        </td>
                        <td class="wt_sc_meta_item_tb_discount wt_sc_meta_item_tb_other" style="padding-left:0px;">
                            <select name="_wt_sc_bogo_free_product_price_type[<?php echo esc_attr($field_index);?>]" data-default-val="percent">
                                <option value="percent" <?php selected('percent', $product_data['price_type']);?>>%</option>
                                <option value="flat" <?php selected('flat', $product_data['price_type']);?>><?php echo esc_html(get_woocommerce_currency_symbol()); ?></option>                               
                            </select>
                        </td>
                        <td class="wt_sc_meta_item_tb_action">
                            <span class="dashicons dashicons-dismiss wt_sc_meta_item_tb_delete_row" title="<?php _e('Remove row', 'wt-smart-coupons-for-woocommerce-pro');?>"></span>
                        </td>
                    </tr>
                    <?php 
                    $field_index++;
                }
                ?>
                <tr>
                    <td colspan="5" class="wt_sc_add_new_row_btn_td">
                        <button type="button" class="wt_sc_meta_item_tb_add_row" title="<?php _e('Add new row', 'wt-smart-coupons-for-woocommerce-pro');?>">+</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </fieldset>


    <!-- Specific category -->
    <?php 
    if ( 'any' === $category_condition ) {
        ?>
        <fieldset class="form-field wt_sc_form_field" wt_sc_form_toggle-id="_wt_sc_bogo_customer_gets" wt_sc_form_toggle-val="any_product_from_category" wt_sc_form_toggle-level="1">
            <legend><?php _e('Products category', 'wt-smart-coupons-for-woocommerce-pro'); ?></legend>
            <select class="wt_sc_category_search wt_sc_select2" data-default-val="" name="_wt_sc_bogo_free_category_ids[]" data-placeholder="<?php esc_attr_e( 'Search for a category...', 'wt-smart-coupons-for-woocommerce-pro' ); ?>" multiple="multiple" style="width:60%; height:auto;">
                <?php 
                if ( is_array( $bogo_free_categories ) ) {
                    foreach($bogo_free_categories as $category_id => $category_data)
                    {
                        $category = get_term($category_id, 'product_cat');
                        if(!is_object($category))
                        {
                            continue;
                        }

                        echo '<option value="' . esc_attr($category_id) . '"' . selected( true, true, false ) . '>' . esc_html( wp_strip_all_tags( $category->name ) ) . '</option>';
                    }
                }
                ?>
            </select>
        </fieldset>
        <?php
    } else {
        ?>
        <fieldset class="form-field" wt_sc_form_toggle-id="_wt_sc_bogo_customer_gets" wt_sc_form_toggle-val="any_product_from_category" wt_sc_form_toggle-level="1">
            <legend><?php _e('Products category', 'wt-smart-coupons-for-woocommerce-pro'); ?></legend>
            <table class="wt_sc_coupon_meta_item_table" id="wt_sc_bogo_customer_gets_categories">
                <thead>
                    <tr>
                        <th><?php _e('Category', 'wt-smart-coupons-for-woocommerce-pro');?></th>
                        <th><?php _e('Quantity', 'wt-smart-coupons-for-woocommerce-pro');?></th>
                        <th colspan="2"><?php _e('Discount', 'wt-smart-coupons-for-woocommerce-pro');?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if(empty($bogo_free_categories)) /* add a dummy item for first time use */
                    {
                        $bogo_free_categories=array();
                        $bogo_free_categories['--']=$dummy_qty_price;
                    }

                    $field_index=0;
                    foreach($bogo_free_categories as $category_id=>$category_data)
                    {
                        if('--'!==$category_id) /* not a dummy item */
                        {
                            $category = get_term($category_id, 'product_cat');
                            if(!is_object($category))
                            {
                                continue;
                            }
                        }
                        ?>
                        <tr>
                            <td class="wt_sc_meta_item_tb_item">
                                <select class="wt_sc_category_search wt_sc_select2" data-default-val="" name="_wt_sc_bogo_free_category_ids[<?php echo esc_attr($field_index);?>]" data-placeholder="<?php esc_attr_e( 'Search for a category...', 'wt-smart-coupons-for-woocommerce-pro' ); ?>">
                                <?php 
                                if('--'!==$category_id) /* not a dummy item */
                                {
                                    echo '<option value="' . esc_attr($category_id) . '"' . selected( true, true, false ) . '>' . esc_html( wp_strip_all_tags( $category->name ) ) . '</option>';
                                }
                                ?>
                                </select>
                            </td>
                            <td class="wt_sc_meta_item_tb_qty wt_sc_meta_item_tb_other">
                                <input type="number" name="_wt_sc_bogo_free_category_qty[<?php echo esc_attr($field_index);?>]" value="<?php echo esc_attr($category_data['qty']);?>" data-default-val="1" min="1" step="1">
                            </td>
                            <td class="wt_sc_meta_item_tb_price">
                                <input type="number" name="_wt_sc_bogo_free_category_price[<?php echo esc_attr($field_index);?>]" value="<?php echo esc_attr($category_data['price']);?>" data-default-val="100" min="0" step="any">
                            </td>
                            <td class="wt_sc_meta_item_tb_discount wt_sc_meta_item_tb_other" style="padding-left:0px;">
                                <select name="_wt_sc_bogo_free_category_price_type[<?php echo esc_attr($field_index);?>]" data-default-val="percent">
                                    <option value="percent" <?php selected('percent', $category_data['price_type']);?>>%</option>
                                    <option value="flat" <?php selected('flat', $category_data['price_type']);?>><?php echo esc_html(get_woocommerce_currency_symbol()); ?></option>                               
                                </select>
                            </td>
                            <td class="wt_sc_meta_item_tb_action">
                                <span class="dashicons dashicons-dismiss wt_sc_meta_item_tb_delete_row" title="<?php _e('Remove row', 'wt-smart-coupons-for-woocommerce-pro');?>"></span>
                            </td>
                        </tr>
                        <?php
                        $field_index++;
                    }
                    ?>
                    <tr>
                        <td colspan="5" class="wt_sc_add_new_row_btn_td">
                            <button type="button" class="wt_sc_meta_item_tb_add_row" title="<?php _e('Add new row', 'wt-smart-coupons-for-woocommerce-pro');?>">+</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </fieldset>
        <?php 
    } ?>


    <!-- Any product in store, Same product as in the cart, Any product from the same catgeory as in the cart. And in `any_product_from_category` these fields are available when category condition is `all` -->
    <fieldset class="form-field _wt_sc_bogo_global_qty" wt_sc_form_toggle-id="_wt_sc_bogo_customer_gets" wt_sc_form_toggle-val="any_product_from_store||same_product_in_the_cart||any_product_from_category_in_the_cart<?php echo esc_attr('any' === $category_condition ? '||any_product_from_category' : ''); ?>" wt_sc_form_toggle-level="1">
        <legend><?php _e('Quantity', 'wt-smart-coupons-for-woocommerce-pro'); ?></legend>
        <input type="number" step="1" min="1" name="_wt_product_discount_quantity" value="<?php echo esc_attr($discount_quantity); ?>" placeholder="1" style="width:70px;"> 
    </fieldset>

    <fieldset class="form-field _wt_sc_bogo_global_discount" wt_sc_form_toggle-id="_wt_sc_bogo_customer_gets" wt_sc_form_toggle-val="any_product_from_store||same_product_in_the_cart||any_product_from_category_in_the_cart<?php echo esc_attr('any' === $category_condition ? '||any_product_from_category' : ''); ?>" wt_sc_form_toggle-level="1">
        <legend><?php _e('Discount', 'wt-smart-coupons-for-woocommerce-pro'); ?></legend>
        <input type="text" min="0" name="_wt_product_discount_amount" value="<?php echo esc_attr($discount_amount); ?>" placeholder="00.00" style="width:70px; margin-right:5px;" step="any">
        <select name="_wt_product_discount_type">
            <option value="percent" <?php selected($discount_type, 'percent'); ?>>%</option>
            <option value="flat" <?php selected($discount_type, 'flat'); ?>><?php echo esc_html(get_woocommerce_currency_symbol()); ?></option>
        </select>
    </fieldset>

    <?php

        /**
         *  Convert existing product as giveaway
         * 
         *  @since 2.2.0
         */
        woocommerce_wp_checkbox(
            array(
                'id'          => '_wt_sc_convert_existing_as_giveaway',
                'label'       => __('Convert existing product in the cart to giveaway', 'wt-smart-coupons-for-woocommerce-pro' ),
                'description' => __('Enable to apply coupon to existing eligible giveaway product in cart', 'wt-smart-coupons-for-woocommerce-pro' ) . '<br /><i>' . __("Warning: This option won't work as intended when multiple products are added in the 'Any from below selection' condition.", 'wt-smart-coupons-for-woocommerce-pro' ) . '</i>',
                'value'       => wc_bool_to_string($convert_existing_as_giveaway),
            )
        );

        woocommerce_wp_checkbox(
            array(
                'id'          => 'wt_apply_discount_before_tax_calculation',
                'label'       => __('Apply tax only on discounted value', 'wt-smart-coupons-for-woocommerce-pro' ),
                'description' => __('Enable this option to calculate the tax only on the discounted value. e.g if you are providing a discount of $10 on a $100 product, enabling this option will calculate tax only on $90, which is the product giveaway price(sale price).', 'wt-smart-coupons-for-woocommerce-pro' ),
                'value'       => wc_bool_to_string($wt_apply_discount_before_tax_calculation),
                'desc_tip'    => true,
            )
        );

        woocommerce_wp_checkbox(
            array(
                'id'          => '_wt_sc_cheapest_item_as_giveaway',
                'label'       => __('Apply cheapest item in the cart as giveaway', 'wt-smart-coupons-for-woocommerce-pro'),
                'description' => __('Enable this option to set cheapest item in the cart as giveaway. The coupon will be automatically converted to individual use coupon.', 'wt-smart-coupons-for-woocommerce-pro' ),
                'value'       => wc_bool_to_string($cheapest_item_as_bogo),
            )
        );
    ?>
</div>