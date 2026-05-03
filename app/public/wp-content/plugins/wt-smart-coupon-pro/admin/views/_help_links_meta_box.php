<?php
if (!defined('WPINC')) {
    die;
}

/**
 * Help links metabox html
 * @since 1.3.5
 */

$help_links=array(
    array(
        'title'=>__('Create Seasonal Discount offer', 'wt-smart-coupons-for-woocommerce-pro'),
        'link'=>'https://www.webtoffee.com/how-to-create-a-limited-time-coupon-discount-offer/',
    ),
    array(
        'title'=>__('Auto apply coupons on checkout', 'wt-smart-coupons-for-woocommerce-pro'),
        'link'=>'https://www.webtoffee.com/how-to-restrict-auto-apply-coupons-in-woocommerce/',
    ),
    array(
        'title'=>__('Create offers to target customers based on Location/Shipping/Payment method/User role', 'wt-smart-coupons-for-woocommerce-pro'),
        'link'=>'https://www.webtoffee.com/checkout-options-based-woocommerce-coupon-discount/',
    ),
    array(
        'title'=>__('Offer First order and Next order discount', 'wt-smart-coupons-for-woocommerce-pro'),
        'link'=>'https://www.webtoffee.com/how-to-give-nth-order-coupon-for-woocommerce-customers/',
    ),
);
?>
<style type="text/css">
.wt_sc_help_links{width:100%; }
.wt_sc_help_links li{ line-height:12px; box-sizing:border-box; width:100%; padding:3px 7px 3px 7px; margin-left:15px; list-style:square; line-height:16px; }
.wt_sc_help_link_more{ width:100%; text-align:right; margin-top:25px; }
.wt_sc_help_link_more .dashicons{ font-size:16px; line-height:20px; }
</style>
<p>
    <?php esc_html_e("Here are a few links that explains types of offers you can create.", 'wt-smart-coupons-for-woocommerce-pro'); ?> 
</p>
<ul class="wt_sc_help_links">
    <?php
    foreach($help_links as $help_link)
    {
        ?>
        <li>
            <a href="<?php echo esc_attr($help_link['link']);?>" target="_blank">
                <?php esc_html_e($help_link['title'], 'wt-smart-coupons-for-woocommerce-pro'); ?>
            </a>
        </li>
        <?php
    }
    ?>
</ul>
<div class="wt_sc_help_link_more">
    <?php esc_html_e("To know more, read ", 'wt-smart-coupons-for-woocommerce-pro'); ?> 
    <a href="https://www.webtoffee.com/category/documentation/smart-coupons-for-woocommerce/" target="_blank">
        <?php esc_html_e("documentation", "wt-smart-coupons-for-woocommerce-pro"); ?>.
    </a>
</div>