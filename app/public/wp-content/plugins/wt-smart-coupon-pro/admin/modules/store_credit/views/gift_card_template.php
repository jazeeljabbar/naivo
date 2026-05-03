<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}

$templates=Wt_Smart_Coupon_Store_Credit::get_gift_card_templates();
$templates=(!is_array($templates) ? array() : $templates);
$categories=Wt_Smart_Coupon_Store_Credit::get_template_category_from_template_list($templates);
$categories_for_select_box=array_combine($categories, $categories);
$categories_for_select_box['']=__('Add new category', 'wt-smart-coupons-for-woocommerce-pro');
?>
<style type="text/css">
.wt_sc_giftcard_template_main{ width:100%; float:left; clear:both; margin-bottom:10px; }
.wt_sc_giftcard_template_box{ width:18%; margin-right:2%; margin-bottom:2%; float:left; position:relative; padding:0px; box-shadow:2px 2px 3px #333; text-align:center; box-sizing:border-box; }
.wt_sc_giftcard_template_box img{ max-width:100%; float:left; }
.wt_sc_giftcard_template_box .wt_sc_img_overlay{ position:absolute; width:100%; height:100%; top:0; left:0; z-index:10; background:rgba(0, 0, 0, .5); display:none;}
.wt_sc_giftcard_template_box:hover .wt_sc_img_overlay{ display:block;}
.wt_sc_img_overlay_content{ position:relative; top:50%; margin-top:-10px; }
.wt_sc_custom_template_overlay.wt_sc_img_overlay_content{margin-top:-25px;}
.wt_sc_img_delete_btn{ width:40px; height:30px; font-size:30px; color:#fff; cursor:pointer; }
.wt_sc_img_delete_btn:hover{ color:#f00;}
.wt_sc_template_category{ color:#fff; height:20px; float:left; width:100%; font-size:1em; font-weight:bold;}
.wt_sc_giftcard_template_bg{ float:left; width:100%; height:30px; }

.wt_sc_img_add_btn{ width:50px; height:40px; font-size:40px; color:#000; position:absolute; z-index:10; top:50%; left:50%; margin-left:-25px; margin-top:-20px; cursor:pointer; }
@media (max-width:768px) {
    .wt_sc_giftcard_template_box{ width:48%; }
}
.media-modal{ z-index:100000010; }
.wt_sc_giftcard_add_new_template_popup .wt-sc-form-table tr td:nth-child(3){ width:25%; }
.wt_sc_giftcard_add_new_template_popup .wt-sc-form-table tr td:nth-child(2){ width:50%; }
.wt_sc_giftcard_add_new_template_popup .wt_sc_popup_footer{ height:50px; padding:0px 20px; background-color:#f3f3f3; box-sizing:border-box; padding-top:11px; border-top:1px solid #ddd;}
.wbte_sc_storecredit_check_all_box{ float:right; font-size:14px; font-weight:normal; }

/* Custom checkbox */
.wt_sc_checkbox_container{display:block;position:absolute;z-index:11;margin-left:-10px;margin-top:-10px;padding-left:35px;margin-bottom:12px;cursor:pointer;font-size:22px;-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none}
.wt_sc_checkbox_container input{position:absolute;opacity:0;cursor:pointer;height:0;width:0}
.wt_sc_checkbox_checkmark{position:absolute;top:0;left:0;height:25px;width:25px;background-color:#eee;border-radius:20px}
.wt_sc_checkbox_container:hover input~.wt_sc_checkbox_checkmark{background-color:#ccc}
.wt_sc_checkbox_container input:checked~.wt_sc_checkbox_checkmark{background-color:#2196f3}
.wt_sc_checkbox_checkmark:after{content:"";position:absolute;display:none}
.wt_sc_checkbox_container input:checked~.wt_sc_checkbox_checkmark:after{display:block}
.wt_sc_checkbox_container .wt_sc_checkbox_checkmark:after{left:9px;top:5px;width:5px;height:10px;border:solid #fff;border-width:0 3px 3px 0;-webkit-transform:rotate(45deg);-ms-transform:rotate(45deg);transform:rotate(45deg)}
</style>
<!-- Add new template popup -->
<div class="wt_sc_giftcard_add_new_template_popup wt_sc_popup" style="width:900px;">
    <div class="wt_sc_popup_hd">
        <div class="wt_sc_popup_title"><?php _e('Add new template', 'wt-smart-coupons-for-woocommerce-pro');?></div>
        <div class="wt_sc_popup_close">X</div>
    </div>
    <div class="wt_sc_popup_body" style="text-align:left;">
        <form method="post" class="wt_sc_store_credit_giftcard_template_form">
            <table class="wt-sc-form-table">
                <?php
                Wt_Smart_Coupon_Admin::generate_form_field(array(
                    array(
                        'label'=>__("Template image", 'wt-smart-coupons-for-woocommerce-pro'),
                        'type'=>"uploader",                 
                        'option_name'=>"wt_sc_choose_gift_card_template",
                        'uploader_title'=>__("Template image", 'wt-smart-coupons-for-woocommerce-pro'),
                        'uploader_button_text'=>__("Select image", 'wt-smart-coupons-for-woocommerce-pro'),
                        'allowed_file_types'=>"image",
                        'help_text'=>__("Recommended dimension for the image is 852x400px. Different dimensions may break your Gift card purchase page/Gift card email.", 'wt-smart-coupons-for-woocommerce-pro'),
                    ),
                    array(
                        'label'=>__("Category", 'wt-smart-coupons-for-woocommerce-pro'),
                        'type'=>"custom_preset",                   
                        'option_name'=>"wt_sc_choose_gift_card_template_category",
                        'select_fields'=>$categories_for_select_box,
                        'help_text'=>__("Gift card category.", 'wt-smart-coupons-for-woocommerce-pro'),
                        'trigger_val'=>'',
                    ),
                    array(
                        'label'=>__("Top background color", 'wt-smart-coupons-for-woocommerce-pro'),
                        'type'=>"color",                   
                        'option_name'=>"wt_sc_choose_gift_card_template_top_bg",
                        'help_text'=>__("Background color for the top portion of Gift card.", 'wt-smart-coupons-for-woocommerce-pro'),
                    ),
                    array(
                        'label'=>__("Bottom background color", 'wt-smart-coupons-for-woocommerce-pro'),
                        'type'=>"color",                   
                        'option_name'=>"wt_sc_choose_gift_card_template_bottom_bg",
                        'help_text'=>__("Background color for the bottom portion of Gift card.", 'wt-smart-coupons-for-woocommerce-pro'),
                    ),
                ), $this->module_id);
                ?>
            </table>
            <div class="wt_sc_popup_footer">
                <button class="button button-primary wt_sc_giftcard_add_new_template_submitbtn"><?php _e('Add new', 'wt-smart-coupons-for-woocommerce-pro');?></button>
            </div>
        </form>   
    </div>
</div>
<!-- Add new template popup -->

<div class="wt-sc-tab-content wt-sc-gift-template-container" data-id="<?php echo $target_id;?>">	
    <h3 class="wt-sc-form-settings-group-heading">
        <?php _e('Templates', 'wt-smart-coupons-for-woocommerce-pro'); ?>
        <span class="wbte_sc_storecredit_check_all_box"><input type="checkbox" class="wbte_sc_storecredit_check_all" id="wbte_sc_storecredit_check_all"> <label for="wbte_sc_storecredit_check_all"><?php esc_html_e( 'Check all', 'wt-smart-coupons-for-woocommerce-pro' ); ?></label></span>
    </h3>
    <div class="wt_sc_giftcard_template_main">
           
    </div>  
    <?php
    Wt_Smart_Coupon_Admin::add_settings_footer(__("Update the template list", 'wt-smart-coupons-for-woocommerce-pro'));
    ?>
</div>