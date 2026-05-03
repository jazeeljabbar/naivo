<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="options_group wt_sc_normal_coupon_giveaway_tab_content">
    <p class="form-field"><label><?php _e('Free Products', 'wt-smart-coupons-for-woocommerce-pro' ); ?></label>
        <select class="wc-product-search" multiple="multiple" style="width: 50%;" name="_wt_free_product_ids[]" data-placeholder="<?php esc_attr_e( 'Search for a product...', 'wt-smart-coupons-for-woocommerce-pro' ); ?>" data-action="woocommerce_json_search_products_and_variations_without_parent">
            <?php
            if(!empty($free_product_id_arr))
            {
                foreach ($free_product_id_arr as $product_id)
                {
                    $product = wc_get_product($product_id);
                    if(is_object($product))
                    {
                        echo '<option value="'.esc_attr($product_id).'"'.selected(true, true, false).'>'.wp_kses_post($product->get_formatted_name()) . '</option>';
                    }
                }
            }                   
            ?>
        </select>
        <?php echo wc_help_tip(__('Specified quantity of the selected free product/s is added to the customer cart when the coupon is applied successfully. In case of multiple products the customer will have to choose one from among the list.', 'wt-smart-coupons-for-woocommerce-pro')); ?>
    </p>

    <p class="form-field"><label><?php _e( 'Quantity', 'wt-smart-coupons-for-woocommerce-pro' ); ?></label>
        <input type="number" step="1" min="1" name="_wt_product_discount_quantity" value="<?php echo esc_attr($discount_quantity); ?>" placeholder="1" style="width: 5em;"> 
        <?php echo wc_help_tip(__('Specified quantity of the product will be added to the cart.', 'wt-smart-coupons-for-woocommerce-pro')); ?>
    </p>

    <div class = "give_away_product_discount">
        <p class="form-field">
            <label><?php echo esc_html__( 'Giveaway discount ', 'wt-smart-coupons-for-woocommerce-pro' ); ?></label>
            <input type="text" min="0" name="_wt_product_discount_amount" value="<?php echo esc_attr($discount_amount); ?>" placeholder="00.00" style="width: 5em;">
            
            <select name="_wt_product_discount_type">
                <option value="percent" <?php selected($discount_type, 'percent'); ?>>%</option>
                <option value="flat" <?php selected($discount_type, 'flat'); ?>><?php echo esc_html(get_woocommerce_currency_symbol()); ?></option>
            </select>
            <?php echo wc_help_tip(esc_html__('Indicates the discount percentage/value of the giveaway product.e.g, you can giveaway a cap at 10 percentage discount upon purchase of a T-shirt', 'wt-smart-coupons-for-woocommerce-pro')); ?>
        </p>
    </div>

    <?php
        woocommerce_wp_checkbox(
            array(
                'id'          => 'wt_apply_discount_before_tax_calculation',
                'label'       => __('Apply tax only on discounted value', 'wt-smart-coupons-for-woocommerce-pro' ),
                'description' =>  __('Enable this option to calculate the tax only on the discounted value. e.g if you are providing a discount of $10 on a $100 product, enabling this option will calculate tax only on $90, which is the product giveaway price(sale price).', 'wt-smart-coupons-for-woocommerce-pro' ),
                'value'       => wc_bool_to_string($wt_apply_discount_before_tax_calculation),
                'desc_tip'    => true,
            )
        );
    ?>
</div>