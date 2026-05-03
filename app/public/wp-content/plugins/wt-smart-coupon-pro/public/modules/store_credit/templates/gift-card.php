<?php 
/**
 * Gift card product page template
 * Version: 1.0.1
 * 
 * @package  Wt_Smart_Coupon  
 */
if ( ! defined( 'WPINC' ) ) {
    die;
}
?>
<div class="wt_customise_gift_coupon_wrapper <?php echo esc_attr( apply_filters( 'wt_sc_add_gift_card_product_page_css_class', '' ) ); ?>">
    <div class="wt_gift_coupon_title">
        <h1><?php _e('Customise your store credit coupon', 'wt-smart-coupons-for-woocommerce-pro'); ?></h1>
    </div>
    <div class="wt_gift_coupn_designs">
        <h2><?php _e('Store credit design', 'wt-smart-coupons-for-woocommerce-pro'); ?> </h2>    
        <?php
        $i = 0;
        $first_template_key='general';
        $category='';
        foreach($templates as $template_key => $template)
        {
            $class='';
            if($i==0)
            {
                $class='active';
                $first_template_key=$template_key; 
            }
            
            $caption=Wt_Smart_Coupon_Store_Credit::get_gift_card_caption($template_key);
            $templates[$template_key]['caption']=$caption;

            if($templates_by_category)
            {
                if($i===0 || $template['category']!=$category)
                {
                    if($i>0)
                    {
                        echo '</ul>'; /* closing of previous ul */
                    }
                    echo '<div class="wt_sc_gift_coupn_categories">'.esc_html($template['category']).'</div>'; /* category title */
                    echo '<ul>'; /* opening of new ul */

                    $category=$template['category'];
                }
            }else
            {
                if($i==0)
                {
                    echo '<ul>'; /* opening of new ul */
                }
            }

            echo '<li class='.esc_attr($class).'>';
                echo '<img design="'.esc_attr($template_key).'" src="'.esc_attr($template['image_url']).'" alt="'.esc_attr($template_key).'" top_bg="'.esc_attr($template['top_bg_color']).'" bottom_bg="'.esc_attr($template['bottom_bg_color']).'" />';
                echo '<span class="wt_sc_gift_card_caption_hidden">';
                    echo esc_html($caption);
                echo '</span>';
            echo '</li>';

            $i++;
        }
        if($i>0)
        {
            echo '</ul>'; //closing of last ul
        }
        ?>
    </div>
    <div class="wt_gift_coupon_preview">
        <div class="wt_gift_coupon_preview_wrapper">
            <h2><?php _e('Preview', 'wt-smart-coupons-for-woocommerce-pro'); ?></h2>
            <div class="store_credit_preview">
                <div class="store_credit_preview_wrapper">
                    <div class="wt_gift_coupon_preview_caption" style="background-color:<?php echo esc_attr($templates[$first_template_key]['top_bg_color']); ?>">
                       <?php echo esc_html($templates[$first_template_key]['caption']); ?> 
                    </div>
                    <div class="wt_gift_coupon_preview_image">
                        <?php echo '<img src="'.esc_attr($templates[$first_template_key]['image_url']).'" alt="general" />'; ?>
                    </div>
                    <div class="wt_coupon-code-block">
                        <div class="coupon-code">
                            XXXX-XXXX-XXXX
                        </div>
                        <div class="coupon_price">
                            <?php
                            $amount_html='<span>0</span>';
                            if($currency_positon=='left')
                            {
                                $amount_html  = $currency_symbol.$amount_html;
                            } else {
                                $amount_html  = $amount_html.$currency_symbol;
                            }
                            echo $amount_html;
                            ?>
                        </div>
                    </div>
                    <div class="coupon-message-block" style="background-color:<?php echo esc_attr($templates[$first_template_key]['bottom_bg_color']); ?>">
                        <div class="coupon-message"><?php _e('A gift awaiting you', 'wt-smart-coupons-for-woocommerce-pro'); ?></div>
                        <div class="coupon-from"><?php _e('FROM:', 'wt-smart-coupons-for-woocommerce-pro'); ?> <span><?php _e('Your Name', 'wt-smart-coupons-for-woocommerce-pro') ?></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="wt_gift_coupon_setup">
        <h2><?php _e('Store credit details', 'wt-smart-coupons-for-woocommerce-pro'); ?></h2>
        <?php do_action('wt_gift_coupon_setup_form'); ?>
    </div>
</div>