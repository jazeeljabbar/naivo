<?php
/**
 *  @since 1.3.5
 */
if ( ! defined( 'WPINC' ) ) {
    die;
}
$coupon_data_dummy=(isset($view_params['coupon_data_dummy']) ? $view_params['coupon_data_dummy'] : array());
$banner_data=(isset($view_params['banner_data']) ? $view_params['banner_data'] : array());
$default_banner_data=(isset($view_params['default_banner_data']) ? $view_params['default_banner_data'] : array());
$module_id=(isset($view_params['module_id']) ? $view_params['module_id'] : '');
$module_base=(isset($view_params['module_base']) ? $view_params['module_base'] : '');
$display_types=(isset($view_params['display_types']) ? $view_params['display_types'] : array());
$banner_display_positions=(isset($view_params['banner_display_positions']) ? $view_params['banner_display_positions'] : array());
$widget_display_positions=(isset($view_params['widget_display_positions']) ? $view_params['widget_display_positions'] : array());
$module_img_path=(isset($view_params['module_img_path']) ? $view_params['module_img_path'] : '');
?>
<style type="text/css">
.wt_sc_coupon_banner_customize{ float:left; width:100%; height:auto; box-shadow:0px 0px 4px 2px #ccc; margin-bottom:30px; }
.wt_sc_coupon_banner_left{ float:left; width:calc(100% - 320px); }
.wt_sc_coupon_banner_left_full_screen{ width:100%;}
.wt_sc_coupon_banner_left h3{ height:18px; }
.wt_sc_coupon_banner_right{ float:right; width:300px; margin-bottom:30px; margin-top:52px; position:relative;}
.wt_sc_coupon_banner_right_full_screen{width:0px; min-height:450px;}
.wt_sc_coupon_banner_right_full_screen *:not(.wt_sc_side_panel_minmax):not(.wt_sc_side_panel_minmax *){ display:none; }
.wt_sc_coupon_banner_right_full_screen .wt_sc_side_panel_minmax{ margin-right:-16px; margin-left:-16px; }

.wt_sc_side_panel_minmax{position:absolute; top:50%; height:100px; width:30px; margin-top:-50px; margin-left:-30px; margin-right:300px; border:solid 1px #ddd; border-right:none; border-radius:3px; cursor:pointer; box-shadow:-2px 0px 3px -1px #ccc;}
.wt_sc_side_panel_minmax .dashicons{margin-top: 37px;font-size: 30px; text-align:center;}
.wt_sc_coupon_banner_preview{ float:left; width:100%; height:auto; }

.wt_sc_accord_frmgrp .wp-color-result-text, .wt_sc_accord_frmgrp .wp-picker-clear{ display:none; }
.wt_sc_accord_frmgrp .wp-picker-holder{ position:absolute; z-index:100; margin-left: -100px; }
.wt_sc_accord_frmgrp .wp-picker-container .wp-color-result.button{ margin-bottom:0px; }

.wt_sc_coupon_banner_help{ float:left; margin-left:1%; width:95%; margin-top:30px; }
.wt_banner_shortcode_example{ margin-left:25px; }

.wt_sc_banner_shortcode_arguments .wt_sc_popup_body{ padding:15px; }
.wt_sc_shortcode_arg_list_table{ border:solid 1px #ccc; } 
.wt_sc_shortcode_arg_list_table td, .wt_sc_shortcode_arg_list_table th{ text-align:left; padding:7px 5px; }
</style>

<!-- Shortcode arguments help popup -->
<div class="wt_sc_banner_shortcode_arguments wt_sc_popup" style="width:900px;">
    <div class="wt_sc_popup_hd">
        <div class="wt_sc_popup_title"><?php _e('Shortcode arguments', 'wt-smart-coupons-for-woocommerce-pro');?></div>
        <div class="wt_sc_popup_close">X</div>
    </div>
    <div class="wt_sc_popup_body">
        <?php
        $shortcode_args = array(
            'coupon_id' => array(
                'default'       => '',
                'description'   => __('Coupon ID(coupon post_id) to be displayed in the banner.','wt-smart-coupons-for-woocommerce-pro'),
                'is_required'   => true
            ),
            'banner_type' => array(
                'default'       => 'banner',
                'description'   => __('Displays type. Values{banner,widget}','wt-smart-coupons-for-woocommerce-pro'),
                'is_required'   => false
            ),
            'enable_title' => array(
                'default'       =>'true',
                'description'   => __('Enables title on banner','wt-smart-coupons-for-woocommerce-pro'),
                'is_required'   => false
            ),
            'title' => array(
                'default'       => 'FINAL HOURS',
                'description'   => 'Title',
                'is_required'   => false
            ),
            'enable_description' => array(
                'default'       => 'true',
                'description'   => __('Enables description on banner','wt-smart-coupons-for-woocommerce-pro'),
                'is_required'   => false
            ),
            'description' => array(
                'default'       => '20% OFF',
                'description'   => __('Description','wt-smart-coupons-for-woocommerce-pro'),
                'is_required'   => false
            ),
            'position' => array(
                'default'       => '',
                'description'   => __('Display position. for banner: {top,bottom,custom} for widget {top_left,top_right,bottom_left,bottom_right,custom}','wt-smart-coupons-for-woocommerce-pro'),
                'is_required'   => false
            ),
            'bg_color' => array(
                'default'       => '#3389ff',
                'description'   => __('Background color','wt-smart-coupons-for-woocommerce-pro'),
                'is_required'   => false
            ),
            'border_color' => array(
                'default'       => '',
                'description'   => __('Border color','wt-smart-coupons-for-woocommerce-pro'),
                'is_required'   => false
            ),
            'display_coupon' => array(
                'default'       => 'true',
                'description'   => __('Displays coupon code on banner','wt-smart-coupons-for-woocommerce-pro'),
                'is_required'   => false
            ),
            'enable_coupon_timer' => array(
                'default'       => 'true',
                'description'   => __('Enables coupon timer on banner. Assumes the expiry date of the associated coupon as value.','wt-smart-coupons-for-woocommerce-pro'),
                'is_required'   => false
            ),
            'is_dismissable' => array(
                'default'       => 'true',
                'description'   => __('Provisions a close button on the banner.','wt-smart-coupons-for-woocommerce-pro'),
                'is_required'   => false
            )
        );
        ?>
        <table class="wp-list-table fixed striped wt_sc_shortcode_arg_list_table">
            <thead>
                <tr>
                    <th><?php _e('Argument', 'wt-smart-coupons-for-woocommerce-pro'); ?></th>
                    <th><?php _e('Default value', 'wt-smart-coupons-for-woocommerce-pro'); ?></th>
                    <th><?php _e('Description', 'wt-smart-coupons-for-woocommerce-pro'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach($shortcode_args as $argument=>$arg_info)
                {
                ?>
                    <tr>
                        <td><?php echo $argument;?></td>
                        <td><?php echo $arg_info['default'] ; ?></td>
                        <td><?php echo $arg_info['description'] ; ?></td>
                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>  
    </div>
</div>
<!-- Shortcode arguments help popup -->

<div class="wt-sc-inner-content">
    <p><?php _e("Use the configuration panel to style your coupon banner. You can also keyin the shortcode manually within your pages to display/announce the discounts likewise. Or use the option within the panel so that we can inject it into the respective pages.", 'wt-smart-coupons-for-woocommerce-pro');?>
        <?php echo sprintf(__("To know more, read %sdocumentation%s.", 'wt-smart-coupons-for-woocommerce-pro'), '<a href="https://www.webtoffee.com/how-to-add-sales-countdown-timer-for-woocommerce/" target="_blank">', '</a>');  ?>
    </p>
    <ul class="wt_sc_sub_tab">
        <li data-target="wt-sc-banner-settings"><a><?php _e('Settings', 'wt-smart-coupons-for-woocommerce-pro'); ?></a></li>
        <li data-target="wt-sc-banner-customize"><a><?php _e('Customize', 'wt-smart-coupons-for-woocommerce-pro'); ?></a></li>                      
    </ul>
    <div class="wt_sc_sub_tab_container" style="min-height:230px;">
        <form method="post" class="wt_sc_settings_form">
            <input type="hidden" value="<?php echo esc_attr($module_base);?>" class="wt_sc_settings_base" />
            <?php

            // Set nonce:
            if(function_exists('wp_nonce_field'))
            {
                wp_nonce_field(WT_SC_PLUGIN_NAME);
            }
            ?>
            <!-- Banner settings -->
            <div class="wt_sc_sub_tab_content" data-id="wt-sc-banner-settings">
                <table class="wt-sc-form-table">
                    <?php 
                    
                    /* associated coupon */
                    $coupon_id = $banner_data['inject_coupon']['inject_coupon'];
                    $select_field_data=array();
                    if($coupon_id)
                    {
                        $all_discount_types = wc_get_coupon_types();
                        $coupon_title = get_the_title( $coupon_id );
                        $coupon = new WC_Coupon( $coupon_id );

                        $discount_type = $coupon->get_discount_type();

                        if ( ! empty( $discount_type ) ) {
                            $discount_type = sprintf( __( ' ( %1$s: %2$s )', 'wt-smart-coupons-for-woocommerce-pro' ), __( 'Type', 'wt-smart-coupons-for-woocommerce-pro' ), $all_discount_types[ $discount_type ] );

                            if( 'wbte_sc_bogo' === $coupon->get_discount_type() )
                                {
                                    $coupon_title = get_post_meta( $coupon_id, 'wbte_sc_bogo_coupon_name', true );

                                    $discount_type = wp_kses_post( ' ( ' . __( 'Type', 'wt-smart-coupons-for-woocommerce-pro' ) . ': ' . __( 'BOGO', 'wt-smart-coupons-for-woocommerce-pro' ) . __( ', ID', 'wt-smart-coupons-for-woocommerce-pro' ) . ': ' . $coupon_id . ' )' );
                                }
                        }
                        if( $coupon_title && $discount_type )
                        {
                            $select_field_data[$coupon_id] = "$coupon_title $discount_type";
                        }
                    }


                    $args   =  array(                                    
                        'sort_column'       => 'menu_order',
                        'sort_order'        => 'ASC',
                        'post_status'       => 'publish,private,draft',
                    );
                    $all_pages = get_pages($args);
                
                    $page_array = array( 0 => 'home' );
                    foreach( $all_pages as $page ) {
                        $page_array[$page->ID] = $page->post_title;
                    }


                    self::generate_form_field(array(
                        array(
                            'label'         =>  __("Inject the banner automatically",'wt-smart-coupons-for-woocommerce-pro'),
                            'parent_option' =>  "inject_coupon",
                            'option_name'   =>  "enable_inject_coupon",
                            'type'          =>  "radio",
                            'val_type'      =>  "boolean",
                            'radio_fields'  =>  array(
                                                true   =>__('Yes', 'wt-smart-coupons-for-woocommerce-pro'),
                                                false  =>__('No', 'wt-smart-coupons-for-woocommerce-pro')
                                            ),
                            'help_text'     =>  __("By enabling the option the system will automatically embed a coupon banner in the specified pages. You must associate a coupon and also specify the pages where you need the coupon banner to be displayed. The style/layout will be set as is in the configuration panel.", 'wt-smart-coupons-for-woocommerce-pro'),
                        ),
                        array(
                            'label'         =>  __("Associate a coupon",'wt-smart-coupons-for-woocommerce-pro'),
                            'parent_option' =>  "inject_coupon",
                            'option_name'   =>  "inject_coupon",
                            'type'          =>  "ajax_select",
                            'attr'          =>  'data-allow_clear="true" data-placeholder="'.esc_attr__( 'Search for a coupon...', 'wt-smart-coupons-for-woocommerce-pro' ).'" data-action="wt_json_search_coupons" data-security="'.esc_attr( wp_create_nonce( 'search-coupons' ) ).'"',
                            'css_class'     =>  'wt-coupon-search',
                            'select_fields' =>  $select_field_data,
                            'help_text'     =>  sprintf(__("Select a coupon to display as a banner on your site. The timer on the banner will be the expiry date chosen for the selected coupon. %sView banner%s", 'wt-smart-coupons-for-woocommerce-pro'), '<a class="wt_sc_sub_tab_trigger" data-target="wt-sc-banner-customize">', '</a>'),
                        ),
                        array(
                            'label'         =>  __("Pages to show coupon banner",'wt-smart-coupons-for-woocommerce-pro'),
                            'parent_option' =>  "inject_coupon",
                            'option_name'   =>  "inject_into_pages",
                            'type'          =>  "multi_select",
                            'select_fields' =>  $page_array,
                        ),
                        array(
                            'label'         =>  __("Action on coupon expiry",'wt-smart-coupons-for-woocommerce-pro'),
                            'parent_option' =>  "coupon_timer",
                            'option_name'   =>  "action_on_expiry",
                            'type'          =>  "select",
                            'select_fields' =>  array(
                                'hide_banner'   =>__('Hide banner','wt-smart-coupons-for-woocommerce-pro'),
                                'display_text'  =>__('Display text','wt-smart-coupons-for-woocommerce-pro'),
                            ),
                            'form_toggler'  =>  array(
                                'type'      => 'parent',
                                'target'    => 'wt_expiry_date_text_to_display',
                            )
                        ),
                        array(
                            'label'         =>  __("Text to show on coupon expiry",'wt-smart-coupons-for-woocommerce-pro'),
                            'parent_option' =>  "coupon_timer",
                            'option_name'   =>  "expiry_text",
                            'form_toggler'  =>  array(
                                'type'      => 'child',
                                'id'        => 'wt_expiry_date_text_to_display',
                                'val'       => 'display_text',
                                'level'     => 2,
                            )
                        ),
                        array(
                            'label'         =>  __("Action on banner click",'wt-smart-coupons-for-woocommerce-pro'),
                            'parent_option' =>  "display_banner",
                            'option_name'   =>  "action_on_click",
                            'type'          =>  "select",
                            'select_fields' =>  array(
                                'apply_coupon'      =>__('Apply coupon','wt-smart-coupons-for-woocommerce-pro'),
                                'redirect_to_url'   =>__('Redirect to URL','wt-smart-coupons-for-woocommerce-pro'),
                            ),
                            'form_toggler'  =>  array(
                                'type'      => 'parent',
                                'target'    => 'wt_sc_action_on_coupon_click',
                            )
                        ),
                        array(
                            'label'         =>  __("URL to redirect",'wt-smart-coupons-for-woocommerce-pro'),
                            'parent_option' =>  "display_banner",
                            'option_name'   =>  "redirect_url",
                            'form_toggler'  =>  array(
                                'type'      => 'child',
                                'id'        => 'wt_sc_action_on_coupon_click',
                                'val'       => 'redirect_to_url',
                                'level'     => 2,
                            )
                        ),
                        array(
                            'label'         =>  __("Open in new tab",'wt-smart-coupons-for-woocommerce-pro'),
                            'parent_option' =>  "display_banner",
                            'option_name'   =>  "url_open_in_another_tab",
                            'type'          =>  "radio",
                            'val_type'      =>  "boolean",
                            'radio_fields'  =>  array(
                                                true   =>__('Yes', 'wt-smart-coupons-for-woocommerce-pro'),
                                                false  =>__('No', 'wt-smart-coupons-for-woocommerce-pro')
                                            ),
                            'form_toggler'  =>  array(
                                'type'      => 'child',
                                'id'        => 'wt_sc_action_on_coupon_click',
                                'val'       => 'redirect_to_url',
                                'level'     => 2,
                            )
                        ),
                    ), $module_id);
                    ?>
                </table>
                <?php
                Wt_Smart_Coupon_Admin::add_settings_footer(__("Save", 'wt-smart-coupons-for-woocommerce-pro'));
                ?>
            </div>


            <!-- Banner customize -->
            <div class="wt_sc_sub_tab_content" data-id="wt-sc-banner-customize">
                <div class="wt_sc_coupon_banner_left">
                    <h3><?php _e("Preview", 'wt-smart-coupons-for-woocommerce-pro');?></h3>
                    
                    <div class="wt_sc_coupon_banner_preview">
                        <?php
                        echo Wt_Smart_Coupon_Banner::get_banner_html($banner_data, $coupon_data_dummy, true);
                        ?>
                    </div>

                    <div class="wt_sc_coupon_banner_help">
                        <h3><?php _e("How to use coupon banner shortcode?", 'wt-smart-coupons-for-woocommerce-pro');?></h3>
                        <p><?php _e('You can use shortcodes to set up a coupon banner on your website. This can be done by embedding the shortcode manually into any of your pages or automatically by using the configuration option "Inject coupons". Either way will ensure a coupon banner announcing the offer to your visitors.','wt-smart-coupons-for-woocommerce-pro'); ?> </p>
                        <p><?php  printf( __( 'To achieve this, simply place the shortcode in the prescribed format %s within the respective page to display the default coupon banner. coupon_id is the post id of the coupon(created prior via Woocommerce->Coupons).','wt-smart-coupons-for-woocommerce-pro'),'<b>[wt_smart_coupon_banner coupon_id=xxx]</b>' ); ?></p>
                        <p><?php printf( __('Alternatively, you can pass specific %s along with the shortcode to override the default coupon banner appearance. Some of the predefined arguments that can be used along with shortcodes are defined in the list.','wt-smart-coupons-for-woocommerce-pro'),'<a data-wt_sc_popup="wt_sc_banner_shortcode_arguments" style="cursor:pointer;">'.__('arguments/parameters','wt-smart-coupons-for-woocommerce-pro').'</a>');  ?></p>

                        <p><b><?php _e('Example:','wt-smart-coupons-for-woocommerce-pro') ?></b></p>
                        <ul class="wt_banner_shortcode_example">
                            <li>
                                <p><?php 
                                _e('Shortcode for default banner layout', 'wt-smart-coupons-for-woocommerce-pro'); ?> </p>
                                <p>
                                    <b>[wt_smart_coupon_banner coupon_id=2828]</b>
                                    <a class="wt-sc-form-preview-popover" data-title="<?php esc_attr_e('Banner', 'wt-smart-coupons-for-woocommerce-pro'); ?>" data-width="865" data-url="<?php echo esc_attr($module_img_path.'banner-default.png');?>">[<?php _e('Preview','wt-smart-coupons-for-woocommerce-pro'); ?>]</a>
                                </p>
                                <p><?php _e( 'Displays the banner for the coupon id 2828 with the default coupon specifications.', 'wt-smart-coupons-for-woocommerce-pro'); ?></p>
                            </li>

                            <li>
                                <p><?php _e('Shortcode with arguments','wt-smart-coupons-for-woocommerce-pro'); ?>  </p>
                                <p>
                                    <b>[wt_smart_coupon_banner coupon_id=4545 banner_type="widget" title="End of Season Sale" description="Avail 50%discount" position="bottom_right" bg_color="#8224e3" ]</b>
                                    <a class="wt-sc-form-preview-popover" data-title="<?php esc_attr_e('Widget', 'wt-smart-coupons-for-woocommerce-pro'); ?>" data-width="500" data-url="<?php echo esc_attr($module_img_path.'banner-widget.jpg');?>">[<?php _e('Preview','wt-smart-coupons-for-woocommerce-pro'); ?>]</a>
                                </p>
                                <p> <?php _e('The above shortcode will set the appearance type as a widget with title, description, positioned to bottom right and background color as #8224e3 for a coupon with ID 4545.','wt-smart-coupons-for-woocommerce-pro'); ?> </p>
                            </li>
                        </ul>
                        <p><i><?php _e('Note: The styling will be overridden only for arguments explicitly mentioned within the shortcode, others will follow default settings.','wt-smart-coupons-for-woocommerce-pro'); ?></i></p>
                    </div>

                </div>
                <div class="wt_sc_coupon_banner_right">

                    <div class="wt_sc_side_panel_minmax" data-open="1" title="<?php _e('Click to minimize the sidebar', 'wt-smart-coupons-for-woocommerce-pro');?>">
                        <span class="dashicons dashicons-arrow-right"></span>  
                    </div>
                    <!-- Show as section -->
                    <div class="wt_sc_accord">
                        <div class="wt_sc_accord_hd">
                            <div class="wt_sc_accord_toggle">
                                <span class="dashicons dashicons-arrow-right"></span>
                            </div>
                            <?php _e("Show as", 'wt-smart-coupons-for-woocommerce-pro');?>
                        </div>
                        <div class="wt_sc_accord_content">
                            <div class="wt_sc_accord_info_text"></div>
                            
                            <div class="wt_sc_accord_frmgrp" style="width:47%;">
                                <label><?php _e("Type", 'wt-smart-coupons-for-woocommerce-pro'); ?></label>
                                <select name="display_banner[banner_type]" class="wt_sc_accord_sele wt_sc_form_toggle wt_sc_banner_display_type" wt_sc_form_toggle-target="wt_sc_banner_display_type">
                                    <?php
                                    $current_banner_type=(isset($banner_data['display_banner']) && isset($banner_data['display_banner']['banner_type']) ? $banner_data['display_banner']['banner_type'] : $default_banner_data['display_banner']['banner_type']);
                                    foreach($display_types as $display_type=>$display_type_label)
                                    {
                                        ?>
                                        <option value="<?php echo esc_attr($display_type); ?>" <?php selected($current_banner_type, $display_type); ?>><?php echo esc_html($display_type_label); ?></option>
                                        <?php
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="wt_sc_accord_frmgrp" style="width:47%; float:right;" wt_sc_form_toggle-id="wt_sc_banner_display_type"  wt_sc_form_toggle-val="banner">
                                <label><?php _e("Position", 'wt-smart-coupons-for-woocommerce-pro'); ?></label>
                                <select class="wt_sc_accord_sele" name="display_banner[banner_postion]">
                                    <?php
                                    foreach($banner_display_positions as $display_position=>$display_position_label)
                                    {
                                        ?>
                                        <option value="<?php echo esc_attr($display_position); ?>"><?php echo esc_html($display_position_label); ?></option>
                                        <?php
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="wt_sc_accord_frmgrp" style="width:47%; float:right;" wt_sc_form_toggle-id="wt_sc_banner_display_type"  wt_sc_form_toggle-val="widget">
                                <label><?php _e("Position", 'wt-smart-coupons-for-woocommerce-pro'); ?></label>
                                <select class="wt_sc_accord_sele" name="display_banner[widget_postion]">
                                    <?php
                                    foreach($widget_display_positions as $display_position=>$display_position_label)
                                    {
                                        ?>
                                        <option value="<?php echo esc_attr($display_position); ?>"><?php echo esc_html($display_position_label); ?></option>
                                        <?php
                                    }
                                    ?>
                                </select>
                            </div>
                                              
                            <div class="wt_sc_accord_frmgrp" style="width:47%;" wt_sc_form_toggle-id="wt_sc_banner_display_type"  wt_sc_form_toggle-val="widget">
                                <label><?php _e("Width", 'wt-smart-coupons-for-woocommerce-pro');?></label>
                                <div class="wt_sc_inptgrp">
                                    <input type="text" name="display_banner[width]" class="wt_sc_text_field wt_sc_on_keyup wt_sc_banner_width" data-prop="width" data-element="wt_banner">
                                    <div class="addonblock"><input type="text" value="px" readonly="readonly"></div>
                                </div>
                            </div>

                            <div class="wt_sc_accord_frmgrp" style="width:47%; float:right;" wt_sc_form_toggle-id="wt_sc_banner_display_type"  wt_sc_form_toggle-val="widget">
                                <label><?php _e("Height", 'wt-smart-coupons-for-woocommerce-pro');?></label>
                                <div class="wt_sc_inptgrp">
                                    <input type="text" name="display_banner[height]" class="wt_sc_text_field wt_sc_on_keyup wt_sc_banner_height" data-prop="height" data-element="wt_banner">
                                    <div class="addonblock"><input type="text" value="px" readonly="readonly"></div>
                                </div>
                            </div>

                            <div class="wt_sc_accord_frmgrp" style="width:47%;">
                                <label><?php _e("Background color", 'wt-smart-coupons-for-woocommerce-pro');?></label>
                                <input type="text" name="display_banner[bg_color]" class="wt_sc_color_picker wt_sc_banner_bg_color" value="" data-element="wt_banner" data-prop="background-color">
                            </div>

                            <div class="wt_sc_accord_frmgrp" style="width:47%; float:right;">
                                <label><?php _e("Border color", 'wt-smart-coupons-for-woocommerce-pro');?></label>
                                <input type="text" name="display_banner[border_color]" class="wt_sc_color_picker wt_sc_banner_border_color" value="" data-element="wt_banner" data-prop="border-color">
                            </div>
                        </div>
                    </div>

                    <!-- Title section -->
                    <div class="wt_sc_accord">
                        <div class="wt_sc_accord_hd">
                            <div class="wt_sc_accord_toggle">
                                <span class="dashicons dashicons-arrow-right"></span>
                            </div>
                            <?php _e("Title", 'wt-smart-coupons-for-woocommerce-pro');?>
                            <div class="wt_sc_accord_toggle" style="float:right;">
                                <label class="wt_sc_switch">
                                    <input type="hidden" name="banner_title[enable_banner_title_hidden]" value="1">
                                    <input type="checkbox" name="banner_title[enable_banner_title]" value="1" checked="checked" data-element="wt_banner_title" class="wt_sc_slide_switch wt_sc_banner_item_toggle">                                  
                                    <span class="wt_sc_slider_switch wt_sc_slider_switch_round" style="cursor: pointer;"></span>
                                </label>
                            </div>
                        </div>
                        <div class="wt_sc_accord_content">
                            <div class="wt_sc_accord_frmgrp">
                                <label><?php _e("Title text", 'wt-smart-coupons-for-woocommerce-pro');?></label>
                                <input type="text" name="banner_title[title]" class="wt_sc_text_field wt_sc_on_keyup" data-element="wt_banner_title" data-prop="text">
                            </div>
                            <div class="wt_sc_accord_frmgrp" style="width:47%;">
                                <label><?php _e("Text size", 'wt-smart-coupons-for-woocommerce-pro');?></label>
                                <div class="wt_sc_inptgrp">
                                    <input type="text" name="banner_title[font-size]" class="wt_sc_text_field wt_sc_on_keyup" data-prop="font-size" data-element="wt_banner_title" data-unit="px">
                                    <div class="addonblock"><input type="text" value="px" readonly="readonly"></div>
                                </div>
                            </div>
                            <div class="wt_sc_accord_frmgrp" style="width:47%; float:right;">
                                <label><?php _e("Text color", 'wt-smart-coupons-for-woocommerce-pro');?></label>
                                <input type="text" name="banner_title[font-color]" class="wt_sc_color_picker" data-element="wt_banner_title" data-prop="color">
                            </div>
                        </div>
                    </div>

                    <!-- Description section -->
                    <div class="wt_sc_accord">
                        <div class="wt_sc_accord_hd">
                            <div class="wt_sc_accord_toggle">
                                <span class="dashicons dashicons-arrow-right"></span>
                            </div>
                            <?php _e("Description", 'wt-smart-coupons-for-woocommerce-pro');?>
                            <div class="wt_sc_accord_toggle" style="float:right;">
                                <label class="wt_sc_switch">
                                    <input type="hidden" name="banner_description[enable_banner_description_hidden]" value="1">
                                    <input type="checkbox" name="banner_description[enable_banner_description]" value="1" checked="checked" data-element="banner-description" class="wt_sc_slide_switch wt_sc_banner_item_toggle">                                   
                                    <span class="wt_sc_slider_switch wt_sc_slider_switch_round" style="cursor: pointer;"></span>
                                </label>
                            </div>
                        </div>
                        <div class="wt_sc_accord_content">
                            <div class="wt_sc_accord_frmgrp">
                                <label><?php _e("Description text", 'wt-smart-coupons-for-woocommerce-pro');?></label>
                                <input type="text" name="banner_description[title]" class="wt_sc_text_field wt_sc_on_keyup" data-element="banner-description" data-prop="text">
                            </div>
                            <div class="wt_sc_accord_frmgrp" style="width:47%;">
                                <label><?php _e("Text size", 'wt-smart-coupons-for-woocommerce-pro');?></label>
                                <div class="wt_sc_inptgrp">
                                    <input type="text" name="banner_description[font-size]" class="wt_sc_text_field wt_sc_on_keyup" data-prop="font-size" data-element="banner-description" data-unit="px">
                                    <div class="addonblock"><input type="text" value="px" readonly="readonly"></div>
                                </div>
                            </div>
                            <div class="wt_sc_accord_frmgrp" style="width:47%; float:right;">
                                <label><?php _e("Text color", 'wt-smart-coupons-for-woocommerce-pro');?></label>
                                <input type="text" name="banner_description[font-color]" class="wt_sc_color_picker" data-element="banner-description" data-prop="color">
                            </div>
                        </div>
                    </div>

                    <!-- Timer section -->
                    <div class="wt_sc_accord">
                        <div class="wt_sc_accord_hd">
                            <div class="wt_sc_accord_toggle">
                                <span class="dashicons dashicons-arrow-right"></span>
                            </div>
                            <?php _e("Timer", 'wt-smart-coupons-for-woocommerce-pro');?>
                            <div class="wt_sc_accord_toggle" style="float:right;">
                                <label class="wt_sc_switch">
                                    <input type="hidden" name="coupon_timer[enable_coupon_timer_hidden]" value="1">
                                    <input type="checkbox" name="coupon_timer[enable_coupon_timer]" value="1" checked="checked" data-element="banner-coupon-timer" class="wt_sc_slide_switch wt_sc_banner_item_toggle">                          
                                    <span class="wt_sc_slider_switch wt_sc_slider_switch_round" style="cursor: pointer;"></span>
                                </label>
                            </div>
                        </div>
                        <div class="wt_sc_accord_content">
                            <div class="wt_sc_accord_frmgrp" style="width:47%;">
                                <label><?php _e("Text size", 'wt-smart-coupons-for-woocommerce-pro');?></label>
                                <div class="wt_sc_inptgrp">
                                    <input type="text" name="coupon_timer[font-size]" class="wt_sc_text_field wt_sc_on_keyup" data-prop="font-size" data-element="banner-coupon-timer" data-unit="px">
                                    <div class="addonblock"><input type="text" value="px" readonly="readonly"></div>
                                </div>
                            </div>
                            <div class="wt_sc_accord_frmgrp" style="width:47%; float:right;">
                                <label><?php _e("Text color", 'wt-smart-coupons-for-woocommerce-pro');?></label>
                                <input type="text" name="coupon_timer[font-color]" class="wt_sc_color_picker" data-element="banner-coupon-timer" data-prop="color">
                            </div>
                            <div class="wt_sc_accord_frmgrp" style="width:47%;">
                                <label><?php _e("Background color", 'wt-smart-coupons-for-woocommerce-pro');?></label>
                                <input type="text" name="coupon_timer[bg-color]" class="wt_sc_color_picker" data-element="banner-coupon-timer .wt_time_entry span" data-prop="background-color">
                            </div>
                            <div class="wt_sc_accord_frmgrp" style="width:47%; float:right;">
                                <label><?php _e("Border color", 'wt-smart-coupons-for-woocommerce-pro');?></label>
                                <input type="text" name="coupon_timer[border-color]" class="wt_sc_color_picker" data-element="banner-coupon-timer .wt_time_entry span" data-prop="border-color">
                            </div>
                        </div>
                    </div>

                    <!-- Coupon code section -->
                    <div class="wt_sc_accord">
                        <div class="wt_sc_accord_hd">
                            <div class="wt_sc_accord_toggle">
                                <span class="dashicons dashicons-arrow-right"></span>
                            </div>
                            <?php _e("Coupon code", 'wt-smart-coupons-for-woocommerce-pro');?>
                            <div class="wt_sc_accord_toggle" style="float:right;">
                                <label class="wt_sc_switch">
                                    <input type="hidden" name="coupon_section[enable_coupon_section_hidden]" value="1">
                                    <input type="checkbox" name="coupon_section[enable_coupon_section]" value="1" checked="checked" data-element="banner-coupon-code" class="wt_sc_slide_switch wt_sc_banner_item_toggle">
                                    <span class="wt_sc_slider_switch wt_sc_slider_switch_round" style="cursor: pointer;"></span>
                                </label>
                            </div>
                        </div>
                        <div class="wt_sc_accord_content">
                            <div class="wt_sc_accord_frmgrp" style="width:47%;">
                                <label><?php _e("Text size", 'wt-smart-coupons-for-woocommerce-pro');?></label>
                                <div class="wt_sc_inptgrp">
                                    <input type="text" name="coupon_section[font-size]" class="wt_sc_text_field wt_sc_on_keyup" data-prop="font-size" data-element="banner-coupon-code" data-unit="px">
                                    <div class="addonblock"><input type="text" value="px" readonly="readonly"></div>
                                </div>
                            </div>
                            <div class="wt_sc_accord_frmgrp" style="width:47%; float:right;">
                                <label><?php _e("Text color", 'wt-smart-coupons-for-woocommerce-pro');?></label>
                                <input type="text" name="coupon_section[font-color]" class="wt_sc_color_picker" data-element="banner-coupon-code" data-prop="color">
                            </div>
                            <div class="wt_sc_accord_frmgrp" style="width:47%;">
                                <label><?php _e("Background color", 'wt-smart-coupons-for-woocommerce-pro');?></label>
                                <input type="text" name="coupon_section[bg-color]" class="wt_sc_color_picker" data-element="banner-coupon-code" data-prop="background-color">
                            </div>
                            <div class="wt_sc_accord_frmgrp" style="width:47%; float:right;">
                                <label><?php _e("Border color", 'wt-smart-coupons-for-woocommerce-pro');?></label>
                                <input type="text" name="coupon_section[border-color]" class="wt_sc_color_picker" data-element="banner-coupon-code" data-prop="border-color">
                            </div>
                        </div>  
                    </div>

                    <!-- Close button -->
                    <div class="wt_sc_accord">
                        <div class="wt_sc_accord_hd">
                            <div class="wt_sc_accord_toggle">
                                <span class="dashicons dashicons-arrow-right"></span>
                            </div>
                            <?php _e("Close button", 'wt-smart-coupons-for-woocommerce-pro');?>
                            <div class="wt_sc_accord_toggle" style="float:right;">
                                <label class="wt_sc_switch">
                                    <input type="hidden" name="display_banner[allow_dismissable_hidden]" value="1">
                                    <input type="checkbox" name="display_banner[allow_dismissable]" value="1" checked="checked" data-element="wt_dismissable" class="wt_sc_slide_switch wt_sc_banner_item_toggle">
                                    <span class="wt_sc_slider_switch wt_sc_slider_switch_round" style="cursor: pointer;"></span>
                                </label>
                            </div>
                        </div>
                        <div class="wt_sc_accord_content">
                            <div class="wt_sc_accord_frmgrp" style="width:47%;">
                                <label><?php _e("Button color", 'wt-smart-coupons-for-woocommerce-pro');?></label>
                                <input type="text" name="display_banner[dismissable_color]" class="wt_sc_color_picker" data-element="wt_dismissable" data-prop="color">
                            </div>
                        </div>
                    </div>
                </div>
                <?php
                Wt_Smart_Coupon_Admin::add_settings_footer(__("Save", 'wt-smart-coupons-for-woocommerce-pro'));
                ?>       
            </div>
        </form>
    </div>
</div>