<?php
/**
 * Nth order coupon
 *
 * @link       
 * @since 2.0.1     
 *
 * @package  Wt_Smart_Coupon  
 */
if (!defined('ABSPATH')) {
    exit;
}

if(!class_exists('Wt_Smart_Coupon_Nth_Order')) /* common module class not found so return */
{
    return;
}

class Wt_Smart_Coupon_Nth_Order_Admin extends Wt_Smart_Coupon_Nth_Order
{
    public $module_base='nth_order';
    public $module_id='';
    public static $module_id_static='';
    private static $instance = null;
    public function __construct()
    {
        $this->module_id=Wt_Smart_Coupon::get_module_id($this->module_base);
        self::$module_id_static=$this->module_id;

        add_filter('woocommerce_coupon_data_tabs', array($this, 'add_nth_coupon_tab'), 22, 1);

        add_action('woocommerce_coupon_data_panels', array($this, 'nth_coupon_tab_content'), 10, 1);

        add_action('woocommerce_process_shop_coupon_meta', array($this, 'save_nth_order_coupon_meta'), 10, 2);

        add_action( 'wt_sc_before_bogo_coupon_save', array( $this, 'save_nth_order_data_for_bogo_coupon' ), 10, 2 );
    }

    /**
     * Get Instance
     */
    public static function get_instance()
    {
        if(self::$instance==null)
        {
            self::$instance=new Wt_Smart_Coupon_Nth_Order_Admin();
        }
        return self::$instance;
    }

    public function add_nth_coupon_tab($tabs)
    {
        $tabs['wt_nth_coupon_tab'] = array(
            'label'  => __('Purchase history', 'wt-smart-coupons-for-woocommerce-pro'),
            'target' => 'wt_nth_order_coupon',
            'class'  => '',
        );

        return $tabs;
    }

    /**
     * Add nth coupon tab content 
     * @since 1.2.8
     * @since 1.3.5     Minimum order number count updated to 0
     * @since 2.0.9     Order date/days field
     *                  Specific products purchased
     * @since 2.1.0     Added `less than or equal` option
     */
    public function nth_coupon_tab_content($post_id)
    {
        ?>
        <div id="wt_nth_order_coupon" class="panel woocommerce_options_panel">
            <div class="options_group">
                
                <!-- No. of orders -->
                <?php
                    $number_of_orders = get_post_meta($post_id, 'wt_nth_order_no_of_orders', true);
                ?>
                <fieldset class="form-field">
                    <legend><?php _e('Number of orders', 'wt-smart-coupons-for-woocommerce-pro'); ?></legend>
                    <?php
                        $nth_coupon_no_of_coupon_condition = get_post_meta( $post_id, 'nth_coupon_no_of_coupon_condition', true );                              
                    ?>                           
                    <select id="nth_coupon_no_of_coupon_condition" name="nth_coupon_no_of_coupon_condition" style="width:190px; height:30px;"  class="wc-enhanced-select" data-placeholder="<?php _e('Please select', 'wt-smart-coupons-for-woocommerce-pro'); ?>">
                        <option value="please_select" <?php selected( $nth_coupon_no_of_coupon_condition, "please_select" ); ?>><?php _e('- Select -','wt-smart-coupons-for-woocommerce-pro'); ?></option>
                        <option value="equals"  <?php selected( $nth_coupon_no_of_coupon_condition, "equals" ); ?> > <?php _e('equals','wt-smart-coupons-for-woocommerce-pro'); ?></option>
                        <option value="greater_or_equal" <?php selected( $nth_coupon_no_of_coupon_condition, "greater_or_equal" ); ?> > <?php _e('greater than or equal to','wt-smart-coupons-for-woocommerce-pro'); ?> </option>
                        <option value="less_than_or_equal" <?php selected( $nth_coupon_no_of_coupon_condition, "less_than_or_equal" ); ?> > <?php _e('less than or equal to','wt-smart-coupons-for-woocommerce-pro'); ?> </option>
                    </select> 

                    <input type="number" step="1" min="0" name="wt_nth_order_no_of_orders" value="<?php echo esc_attr($number_of_orders); ?>" placeholder="" style="width:100px; height:30px; margin-right:5px; margin-left:5px;"> 
                    <?php echo wc_help_tip( __( 'For the coupon to be valid, the specified number of orders must have been placed by the user in the past.', 'wt-smart-coupons-for-woocommerce-pro' ) ); ?> 

                </fieldset>

                
                <?php 
                /**
                 *  Order date/days field
                 * 
                 *  @since 2.0.9
                 */
                $order_date_from = $this->get_coupon_meta_value($post_id, '_wt_sc_nth_order_date_from');
                $order_date_to = $this->get_coupon_meta_value($post_id, '_wt_sc_nth_order_date_to');
                $order_date_or_days = $this->get_coupon_meta_value($post_id, '_nth_coupon_order_date_or_days');
                $order_within_days = absint($this->get_coupon_meta_value($post_id, '_wt_sc_nth_order_within_days'));
                ?>
                <!-- Order date/days field -->
                <fieldset class="form-field">
                    <legend>
                        <?php _e('Order date', 'wt-smart-coupons-for-woocommerce-pro'); ?>
                    </legend>
                    <div class="wt_sc_nth_order_date_field_box">
                        
                        <!--  Date or days -->
                        <div class="nth_coupon_order_date_or_days wt_sc_option_radios">
                            <span><input type="radio" name="_nth_coupon_order_date_or_days" value="date" <?php checked($order_date_or_days, 'date') ?>> <?php _e('in between', 'wt-smart-coupons-for-woocommerce-pro'); ?></span> 
                            <span><input type="radio" name="_nth_coupon_order_date_or_days" value="days" <?php checked($order_date_or_days, 'days') ?>> <?php _e('within the last', 'wt-smart-coupons-for-woocommerce-pro'); ?></span>
                        </div>


                        <!--  From and To date fields -->
                        <div class="nth_coupon_order_date_fields_box">
                            <?php $date_pattern = apply_filters('woocommerce_date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])'); ?>                       
                            <div style="float:left;"><?php _e('From', 'wt-smart-coupons-for-woocommerce-pro'); ?> <input type="text" name="_wt_sc_nth_order_date_from" class="nth_coupon_order_date nth_coupon_order_date_from" value="<?php echo esc_attr($order_date_from); ?>" pattern="<?php echo esc_attr($date_pattern);?>" placeholder="<?php esc_attr_e('YYYY-MM-DD', 'wt-smart-coupons-for-woocommerce-pro'); ?>"> </div>
                            <div style="float:left; margin-left:5px;"> <?php _e('To', 'wt-smart-coupons-for-woocommerce-pro'); ?>  <input type="text" name="_wt_sc_nth_order_date_to" class="nth_coupon_order_date nth_coupon_order_date_to" value="<?php echo esc_attr($order_date_to); ?>" pattern="<?php echo esc_attr($date_pattern);?>" placeholder="<?php esc_attr_e('YYYY-MM-DD', 'wt-smart-coupons-for-woocommerce-pro'); ?>"> </div>
                        </div>

                        <!--  Days number field -->
                        <div class="nth_coupon_order_days_field_box"> <input type="number" step="1" min="0" name="_wt_sc_nth_order_within_days" value="<?php echo esc_attr($order_within_days); ?>"> <?php _e('day(s)', 'wt-smart-coupons-for-woocommerce-pro'); ?></div>
                    </div>
                    <?php echo wc_help_tip( __("For the coupon to be valid, the selected number of orders in 'Number of orders' should be placed on the specified time frame.", 'wt-smart-coupons-for-woocommerce-pro') ); ?>
                </fieldset>


                <!-- Order statuses -->
                <?php
                    $coupon_statuses = wc_get_order_statuses();
                    $status_selected = get_post_meta( $post_id, 'wt_order_Status_need_to_count', true );        
                ?>
                <p class="form-field">
                    <legend><?php _e('Order status', 'wt-smart-coupons-for-woocommerce-pro'); ?></legend>
                    <select id="wt_order_Status_need_to_count" name="wt_order_Status_need_to_count[]" multiple="multiple" style="width:300px;"  class="wc-enhanced-select" data-placeholder="<?php _e('Please select', 'wt-smart-coupons-for-woocommerce-pro'); ?>">
                        <?php 
                            foreach($coupon_statuses  as $coupon_status => $status_text)
                            {
                                $selected = '';
                                if(is_array($status_selected) && in_array($coupon_status,$status_selected))
                                {
                                    $selected = ' selected="selected"';
                                }
                                
                                echo '<option value="'.esc_attr($coupon_status).'" '.$selected.'>'.esc_html($status_text).'</option>';
                            }
                        ?>
                    </select>
                    <?php echo wc_help_tip( __('The status of all identified orders(as per the number specified) should match the chosen for eligibility.', 'wt-smart-coupons-for-woocommerce-pro') ); ?>
                </p>

                <!-- Order total -->
                <?php 
                    $wt_nth_order_order_total = get_post_meta( $post_id, 'wt_nth_order_order_total', true );
                ?>
                <fieldset class="form-field">
                    <legend><?php _e('Total amount', 'wt-smart-coupons-for-woocommerce-pro'); ?></legend>
                    <input type="number" min="0" step="0.01" name="wt_nth_order_order_total" value="<?php echo esc_attr($wt_nth_order_order_total); ?>" placeholder="<?php echo esc_attr__( 'Order total', 'wt-smart-coupons-for-woocommerce-pro' ); ?>" style="width:300px; height:30px;">
                    <?php echo wc_help_tip(__('The minimum total amount of all identified orders together(as per the number specified) should match the provided for eligibility.', 'wt-smart-coupons-for-woocommerce-pro' ) ); ?>
                </fieldset>

                
                <!-- Products purchased -->
                <?php 
                /**
                 *  Specific products purchased
                 * 
                 *  @since 2.0.9
                 */
                $product_ids = $this->get_coupon_meta_value($post_id, '_wt_sc_nth_order_products');
                ?>
                <p class="form-field">
                    <legend><?php _e('Product purchased', 'wt-smart-coupons-for-woocommerce-pro'); ?></legend>
                    <select id="_wt_sc_nth_order_products" name="_wt_sc_nth_order_products[]" multiple="multiple" style="width:300px;"  class="wc-product-search" data-placeholder="<?php esc_attr_e('No product selected', 'wt-smart-coupons-for-woocommerce-pro'); ?>">
                    <?php
                    if(!empty($product_ids))
                    {
                        foreach ($product_ids as $product_id)
                        {
                            $product = wc_get_product($product_id);
                            
                            if(is_object($product))
                            {
                                echo '<option value="'.esc_attr($product_id).'"'.selected(true, true, false).'>' . wp_kses_post($product->get_formatted_name()) . '</option>';
                            }
                        }
                    }                   
                    ?>
                    </select>
                    <?php echo wc_help_tip( __('For the coupon to be valid, one of the selected product(s) must have been previously purchased by the user.', 'wt-smart-coupons-for-woocommerce-pro') ); ?>
                </p>


                <!-- Exclude already awarded -->
                <?php
                $exclude_if_already_awarded = get_post_meta( $post_id, 'nth_coupon_exclude_already_awarded', true );               
                 woocommerce_wp_checkbox(
                    array(
                        'id'          => 'nth_coupon_exclude_already_awarded',
                        'label'       => __('Exclude already awarded customers', 'wt-smart-coupons-for-woocommerce-pro'),
                        'description' => __('Enabling this option excludes customers who have already been awarded this coupon previously.', 'wt-smart-coupons-for-woocommerce-pro'),
                        'value'       => wc_bool_to_string($exclude_if_already_awarded),
                        'desc_tip'    => true,
                    )
                );
                ?>

            </div>
        </div>

        <?php 
        /**
         *  CSS/JS for order date/days field
         * 
         *  @since 2.0.9
         */
        ?>
        <style type="text/css">
            .wt_sc_nth_order_date_field_box{ float:left; width:300px; margin-right:0px; }
            .nth_coupon_order_date_fields_box, .nth_coupon_order_days_field_box{ width:100%;  display:none; }
            .wt_sc_nth_order_date_field_box input[type="text"], .wt_sc_nth_order_date_field_box input[type="number"]{ width:100px; display:inline; float:none; }
            .wt_sc_nth_order_date_field_box input::placeholder{ font-size:12px; }
            .wt_sc_nth_order_date_field_box input.nth_coupon_order_date{ width:107px; }
        </style>
        <script type="text/javascript">
            jQuery(document).ready(function(){
                
                /**
                 *  Hide/show the date/days field based on the radio button checked.
                 * 
                 *  @since 2.0.9
                 */
                function wt_sc_toggle_nth_order_date_field_toggle()
                {
                    if('days' === jQuery('[name="_nth_coupon_order_date_or_days"]:checked').val())
                    {
                        jQuery('.nth_coupon_order_days_field_box').show();
                        jQuery('.nth_coupon_order_date_fields_box').hide();
                    }else
                    {
                        jQuery('.nth_coupon_order_days_field_box').hide();
                        jQuery('.nth_coupon_order_date_fields_box').show();
                    } 
                }

                jQuery('[name="_nth_coupon_order_date_or_days"]').on('click', function(){
                    wt_sc_toggle_nth_order_date_field_toggle(); /* on page checkbox click */
                });

                wt_sc_toggle_nth_order_date_field_toggle(); /* on page load */



                /**
                 *  Add date range picker for `order date` fields
                 */
                jQuery(".nth_coupon_order_date_from").datepicker({
                    dateFormat: "yy-mm-dd",
                    showOtherMonths: true,
                    selectOtherMonths: true,
                    changeMonth: true,
                    changeYear: true,
                    onSelect: function() {
                        jQuery(this).trigger('input');
                        jQuery(".nth_coupon_order_date_to").datepicker( "option", "minDate", jQuery(this).datepicker('getDate') );
                    }
                });

                jQuery(".nth_coupon_order_date_to").datepicker({
                    dateFormat: "yy-mm-dd",
                    showOtherMonths: true,
                    selectOtherMonths: true,
                    changeMonth: true,
                    changeYear: true,
                    onSelect: function() {
                        jQuery(this).trigger('input');
                        jQuery(".nth_coupon_order_date_from").datepicker( "option", "maxDate", jQuery(this).datepicker('getDate') );
                    }
                });

            });
        </script>
        <?php
    }

    
    /**
     *  Save nth coupon meta values
     * 
     *  @since 2.0.9 Code updated similar to other modules
     *  @param int      $post_id    Post id
     *  @param WP_Post  $post       Post object
     */
    public function save_nth_order_coupon_meta($post_id, $post)
    {      

        /* fields to skip from below meta data update loop. Because they are alreay updated. In case of checkbox or multiselect */
        $skip_post_arr = array(); 

        foreach(self::$meta_arr as $meta_key => $meta_info)
        {
            if(in_array($meta_key, $skip_post_arr))
            {
                continue; // already updated via above code block
            }

            if(isset($_POST[$meta_key]) && !empty($_POST[$meta_key]))
            {
                if(isset($meta_info['type']))
                {
                    $val = Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST[$meta_key], $meta_info['type']);

                }else
                {
                    $val = sanitize_text_field($_POST[$meta_key]);
                }

                update_post_meta($post_id, $meta_key, $val);

            }else
            {
                $default = (isset($meta_info['default']) ? $meta_info['default'] : '');
                update_post_meta($post_id, $meta_key, $default);
            }
        }
    }

    /**
     * Save nth order data for bogo coupon.
     * 
     * @since 3.2.0
     * @param int   $post_id    Post id
     * @param array $post_data  Arrat POST data
     */
    public static function save_nth_order_data_for_bogo_coupon( $post_id, $post_data )
    {
        if( ! $post_id || ! is_array( $post_data ) || empty( $post_data ) )
        {
            return;
        }
        
        $update_post = array();
        if( isset( $post_data['wbte_sc_bogo_purchase_history'] ) && 'wbte_sc_bogo_puchase_history_first_time' === $post_data['wbte_sc_bogo_purchase_history'] ){
            $update_post = array(
                'nth_coupon_no_of_coupon_condition' => 'equals',
                'wt_nth_order_no_of_orders' => 0,
            );
        }

        foreach( $update_post as $key => $value )
        {
            update_post_meta( $post_id, $key, $value );
        }

    }
}
Wt_Smart_Coupon_Nth_Order_Admin::get_instance();