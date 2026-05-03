<?php 
if (!defined('ABSPATH')) {
    exit;
}
$dummy_min_max=self::get_dummy_min_max();
?>
<style type="text/css">
#woocommerce-coupon-data .woocommerce_options_panel fieldset._wt_product_condition_field .wc-radios li, #woocommerce-coupon-data .woocommerce_options_panel fieldset._wt_category_condition_field .wc-radios li{ padding-bottom:3px; }
fieldset._wt_product_condition_field .description, fieldset._wt_category_condition_field .description{ display:inline-block; line-height:18px; font-size:12px; }
.wt_sc_coupon_restriction_help{ display:block; clear:both; line-height:18px; }
</style>

<!-- Products:start -->

<fieldset class="form-field wt_sc_coupon_products_fieldset wt_sc_coupon_fieldset">
    <legend><?php _e('Products', 'wt-smart-coupons-for-woocommerce-pro'); ?></legend>
    <table class="wt_sc_coupon_meta_item_table" id="wt_sc_coupon_products">
        <thead>
            <tr>
                <th><?php _e('Product', 'wt-smart-coupons-for-woocommerce-pro');?></th>
                <th><?php _e('Min. Quantity', 'wt-smart-coupons-for-woocommerce-pro');?></th>
                <th><?php _e('Max. Quantity', 'wt-smart-coupons-for-woocommerce-pro');?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php  
            $product_ids = $coupon->get_product_ids('edit');
            
            $wt_sc_coupon_products = self::get_coupon_meta_value($post_id, '_wt_sc_coupon_products'); 
            
            $products_data=self::prepare_items_data($product_ids, $wt_sc_coupon_products);
            
            $products_data['--']=$dummy_min_max; /* add a dummy item */

            $field_index=0;
            foreach($products_data as $product_id=>$product_data)
            {
                if('--'!==$product_id) /* not a dummy item */
                {
                    $product = wc_get_product($product_id);
                    if(!is_object($product))
                    {
                        continue;
                    }
                }
                ?>
                <tr>
                    <td class="wt_sc_meta_item_tb_item">
                        <select class="wt_sc_product_search wt_sc_select2" data-default-val="" name="_wt_sc_coupon_product_ids[<?php echo esc_attr($field_index);?>]" data-placeholder="<?php esc_attr_e( 'Search for a product...', 'wt-smart-coupons-for-woocommerce-pro' ); ?>">
                        <?php 
                        if('--'!==$product_id) /* not a dummy item */
                        {
                            echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . esc_html( wp_strip_all_tags( $product->get_formatted_name() ) ) . '</option>';
                        }
                        ?>
                        </select>
                    </td>
                    <td class="wt_sc_meta_item_tb_qty wt_sc_meta_item_tb_other wt_sc_coupon_restriction_min_max">
                        <input type="number" name="_wt_sc_coupon_product_min_qty[<?php echo esc_attr($field_index);?>]" value="<?php echo esc_attr($product_data['min']);?>" data-default-val="" min="0">
                    </td>
                    <td class="wt_sc_meta_item_tb_qty wt_sc_meta_item_tb_other wt_sc_coupon_restriction_min_max">
                        <input type="number" name="_wt_sc_coupon_product_max_qty[<?php echo esc_attr($field_index);?>]" value="<?php echo esc_attr($product_data['max']);?>" data-default-val="" placeholder="&#8734;" min="1">
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
                <td colspan="4" class="wt_sc_add_new_row_btn_td">
                    <button type="button" class="wt_sc_meta_item_tb_add_row" title="<?php _e('Add new row', 'wt-smart-coupons-for-woocommerce-pro');?>">+</button>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
    foreach(self::discount_type_help_arr() as $help_k => $help_v)
    {
        ?>
        <p class="wt_sc_help_text wt_sc_conditional_help_text" style="margin:0px;" data-sc-help-condition="[discount_type=<?php echo esc_attr($help_k);?>]"><?php echo esc_html($help_v);?></p>
        <?php
    }
    ?>
</fieldset>

<!-- Exclude product help text -->
<?php
foreach(self::discount_type_help_arr('exclude_product') as $help_k => $help_v)
{
    ?>
    <p class="wt_sc_help_text wt_sc_exclude_product_help wt_sc_conditional_help_text" style="margin:0px;" data-sc-help-condition="[discount_type=<?php echo esc_attr($help_k);?>]"><?php echo esc_html($help_v);?></p>
    <?php
}
?>
<!-- Products:end -->


<!-- Categories:start -->

<fieldset class="form-field wt_sc_coupon_categories_fieldset wt_sc_coupon_fieldset">
    <legend><?php _e('Products categories', 'wt-smart-coupons-for-woocommerce-pro'); ?></legend>
    <table class="wt_sc_coupon_meta_item_table">
        <thead>
            <tr>
                <th><?php _e('Category', 'wt-smart-coupons-for-woocommerce-pro');?></th>
                <th><?php _e('Min. Quantity', 'wt-smart-coupons-for-woocommerce-pro');?></th>
                <th><?php _e('Max. Quantity', 'wt-smart-coupons-for-woocommerce-pro');?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $category_ids = $coupon->get_product_categories('edit'); 

            $wt_sc_coupon_categories = self::get_coupon_meta_value($post_id, '_wt_sc_coupon_categories');
            $categories_data=self::prepare_items_data($category_ids, $wt_sc_coupon_categories); 

            $categories_data['--']=$dummy_min_max; /* add a dummy item */

            $field_index=0;
            foreach($categories_data as $category_id=>$category_data)
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
                        <select class="wt_sc_category_search wt_sc_select2" data-default-val="" name="_wt_sc_coupon_category_ids[<?php echo esc_attr($field_index);?>]" data-placeholder="<?php esc_attr_e( 'Search for a category...', 'wt-smart-coupons-for-woocommerce-pro' ); ?>">
                        <?php 
                        if('--'!==$category_id) /* not a dummy item */
                        {
                            echo '<option value="' . esc_attr($category_id) . '"' . selected( true, true, false ) . '>' . esc_html( wp_strip_all_tags( $category->name ) ) . '</option>';
                        }
                        ?>
                        </select>
                    </td>
                    <td class="wt_sc_meta_item_tb_qty wt_sc_meta_item_tb_other wt_sc_coupon_restriction_min_max">
                        <input type="number" name="_wt_sc_coupon_category_min_qty[<?php echo esc_attr($field_index);?>]" value="<?php echo esc_attr($category_data['min']);?>" data-default-val="" min="0">
                    </td>
                    <td class="wt_sc_meta_item_tb_qty wt_sc_meta_item_tb_other wt_sc_coupon_restriction_min_max">
                        <input type="number" name="_wt_sc_coupon_category_max_qty[<?php echo esc_attr($field_index);?>]" value="<?php echo esc_attr($category_data['max']);?>" data-default-val="" placeholder="&#8734;" min="1">
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
                <td colspan="4" class="wt_sc_add_new_row_btn_td">
                    <button type="button" class="wt_sc_meta_item_tb_add_row" title="<?php _e('Add new row', 'wt-smart-coupons-for-woocommerce-pro');?>">+</button>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
    foreach(self::discount_type_help_arr('category') as $help_k => $help_v)
    {
        ?>
        <p class="wt_sc_help_text wt_sc_conditional_help_text" style="margin:0px;" data-sc-help-condition="[discount_type=<?php echo esc_attr($help_k);?>]"><?php echo esc_html($help_v);?></p>
        <?php
    }
    ?>
</fieldset>

<!-- Exclude category help text -->
<?php
foreach(self::discount_type_help_arr('exclude_category') as $help_k => $help_v)
{
    ?>
    <p class="wt_sc_help_text wt_sc_exclude_category_help wt_sc_conditional_help_text" style="margin:0px;" data-sc-help-condition="[discount_type=<?php echo esc_attr($help_k);?>]"><?php echo esc_html($help_v);?></p>
    <?php
}
?>
<!-- Categories:end -->


<!-- Tags:start -->
<fieldset class="form-field wt_sc_product_tags_fieldset wt_sc_coupon_fieldset">
    <legend for="_wt_sc_product_tags"><?php _e('Products tags', 'wt-smart-coupons-for-woocommerce-pro'); ?></legend>
    <select id="_wt_sc_product_tags" name="_wt_sc_product_tags[]" style="width:90%; min-height:40px;"  class="wc-taxonomy-term-search" data-taxonomy="product_tag" data-return_id="id" multiple="multiple" data-placeholder="<?php esc_attr_e('No tags', 'wt-smart-coupons-for-woocommerce-pro'); ?>">
        <?php
        $wt_sc_coupon_tag_ids = self::get_coupon_meta_value($post_id, '_wt_sc_product_tags');

        if(is_array($wt_sc_coupon_tag_ids) & !empty($wt_sc_coupon_tag_ids))
        {
            foreach($wt_sc_coupon_tag_ids as $wt_sc_coupon_tag_id)
            {
                $term = get_term($wt_sc_coupon_tag_id);

                if(is_a($term, 'WP_Term'))
                {
                    echo '<option value="' . esc_attr( $term->term_id ) . '" selected="selected">' . esc_html( $term->name ) . '</option>';
                }
            }
        }
        ?>
    </select>
    <p class="wt_sc_help_text" style="margin:0px;"><?php esc_html_e('Product tags that the coupon will be applied to.', 'wt-smart-coupons-for-woocommerce-pro');?></p>
</fieldset>
<!-- Tags:end -->

<!-- Attribute start -->
<fieldset class="form-field wt_sc_product_attributes_fieldset wt_sc_coupon_fieldset">
    <legend for="_wt_sc_product_attributes"><?php _e('Products attributes', 'wt-smart-coupons-for-woocommerce-pro'); ?></legend>
    <select id="_wt_sc_product_attributes" name="_wt_sc_product_attributes[]" style="width:90%; min-height:40px;"  class="wc-attribute-search attribute_taxonomy" data-action="woocommerce_json_search_product_attributes"  multiple="multiple" data-placeholder="<?php esc_attr_e('No attributes', 'wt-smart-coupons-for-woocommerce-pro'); ?>">
        <?php

        $wt_sc_product_attribute_names = self::get_coupon_meta_value($post_id, '_wt_sc_product_attributes');

        if(is_array($wt_sc_product_attribute_names) & !empty($wt_sc_product_attribute_names))
        {
            foreach($wt_sc_product_attribute_names as $wt_sc_product_attribute_name)
            {
                if(taxonomy_exists($wt_sc_product_attribute_name))
                {
                    $wt_sc_product_attribute_label = wc_attribute_label($wt_sc_product_attribute_name);

                    echo '<option value="' . esc_attr( $wt_sc_product_attribute_name ) . '" selected="selected">' . esc_html( $wt_sc_product_attribute_label ) . '</option>';

                }
            }
        }

        ?>
    </select>
    <p class="wt_sc_help_text" style="margin:0px;"><?php esc_html_e('Product attributes that the coupon will be applied to.', 'wt-smart-coupons-for-woocommerce-pro');?></p>
</fieldset>
<!-- Attribute end -->