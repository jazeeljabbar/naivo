<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}
?>
<div class="wt_sc_giftcard_template_box">
    <label class="wt_sc_checkbox_container">
      <input type="checkbox" name="wt_sc_visible_gift_template[]" class="wt_sc_visible_template_checkbox" value="<?php echo esc_attr($template_k);?>" <?php echo ($is_hidden ? '' : 'checked="checked"');?>>
      <span class="wt_sc_checkbox_checkmark"></span>
    </label>   
    <div class="wt_sc_img_overlay">
        <div class="wt_sc_img_overlay_content <?php echo ($is_custom ? 'wt_sc_custom_template_overlay' : ''); ?>">
            <?php
            if($is_custom)
            {
            ?>
                <span class="dashicons dashicons-trash wt_sc_img_delete_btn" title="<?php echo $delete_btn_tooltip;?>"></span>
            <?php
            }
            ?>
            <span class="wt_sc_template_category"><?php echo esc_html($category);?></span>
        </div>
    </div>
    <div class="wt_sc_giftcard_template_bg" style="<?php echo $top_bg;?>"></div>
    <img src="<?php echo esc_attr($img_url);?>">
    <div class="wt_sc_giftcard_template_bg" style="<?php echo $bottom_bg;?>"></div>
</div> 