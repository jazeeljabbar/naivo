<?php
/**
 * Checkout options admin
 *
 * @link       
 * @since 2.0.9   
 *
 * @package  Wt_Smart_Coupon  
 */

if(!defined('ABSPATH'))
{
    exit;
}

if(!class_exists('Wt_Smart_Coupon_Checkout_Options')) /* common module class not found so return */
{
    return;
}

class Wt_Smart_Coupon_Checkout_Options_Admin extends Wt_Smart_Coupon_Checkout_Options
{
    public $module_base = 'checkout_options';
    public $module_id = '';
    public static $module_id_static = '';
    private static $instance = null;
    
    public function __construct()
    {
        $this->module_id = Wt_Smart_Coupon::get_module_id($this->module_base);
        self::$module_id_static = $this->module_id;

        add_filter('woocommerce_coupon_data_tabs', array($this, 'add_checkout_options_tab'), 20, 1);
        add_action('woocommerce_coupon_data_panels', array($this, 'checkout_options_tab_content'), 10, 0);
        add_action('woocommerce_process_shop_coupon_meta', array($this, 'process_shop_coupon_meta'), 10, 2);
    }


    /**
     *  Get Instance
     * 
     *  @since 2.0.9
     */
    public static function get_instance()
    {
        if(is_null(self::$instance))
        {
            self::$instance = new Wt_Smart_Coupon_Checkout_Options_Admin();
        }

        return self::$instance;
    }


    /**
     *  Add tabs to the coupon data section.
     * 
     *  @since  2.0.9
     *  @param  array    $tabs   Array of tabs
     *  @return array    $tabs   Array of tabs
     */
    public function add_checkout_options_tab($tabs)
    {

        $tabs['wt_coupon_checkout_options'] = array(
            'label'     => __('Checkout options', 'wt-smart-coupons-for-woocommerce-pro'),
            'target'    => 'webtoffee_coupondata_checkout1',
            'class'     => 'webtoffee_coupondata_checkout1',
        );

        return $tabs;
    }


    /**
     *  Tab content for checkout options tab. 
     * 
     *  @since 2.0.9
     */
    public function checkout_options_tab_content()
    {
        global $thepostid, $post;

        $post_id = (empty($thepostid) ? $post->ID : $thepostid);
        $available_roles = wp_roles()->roles;
        $available_roles['wbte_sc_guest'] = array(
            'name' => __( 'Guest', 'wt-smart-coupons-for-woocommerce-pro' )
        );
        
        ?>
        <div id="webtoffee_coupondata_checkout1" class="panel woocommerce_options_panel">
            
            <!-- Shipping methods:start -->
            <p class="form-field">
                <label for="_wt_sc_shipping_methods"><?php _e('Shipping methods', 'wt-smart-coupons-for-woocommerce-pro'); ?></label>
                <select id="_wt_sc_shipping_methods" name="_wt_sc_shipping_methods[]" style="width:50%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e('Any shipping method', 'wt-smart-coupons-for-woocommerce-pro'); ?>">
                    <?php
                    $shipping_methods = WC()->shipping->load_shipping_methods();

                    if ( ! empty( $shipping_methods ) ) {

                        $shipping_method_ids = self::get_processed_coupon_meta_value( $post_id, '_wt_sc_shipping_methods' );
                        foreach ( $shipping_methods as $shipping_method ) {

                            // Skip disabled items.
                            if ( 'yes' !== $shipping_method->enabled ) {
                                continue;
                            }

                            $method_title = $shipping_method->method_title;
                            $method_id    = $shipping_method->id;

                            if ( 'pickup_location' === $method_id ) {
                                $method_title .= __( ' (Only for block checkout)', 'wt-smart-coupons-for-woocommerce-pro' );
                            }

                            ?>
                            <option value="<?php echo esc_attr( $method_id ); ?>"
                                                        <?php
                                                        echo selected( in_array( $method_id, $shipping_method_ids ), true, false );
                                                        ?>
                            >
                                <?php echo esc_html( $method_title ); ?>
                            </option>
                            <?php
                        }
                    }
                    ?>
                </select>
               <?php echo wc_help_tip( __('Coupon will be applicable if any of these shipping methods are selected.', 'wt-smart-coupons-for-woocommerce-pro') ); ?> 
            </p>
            <!-- Shipping methods:end -->


            <!-- Payment methods:start -->
            <p class="form-field">
                <label for="_wt_sc_payment_methods"><?php _e('Payment methods', 'wt-smart-coupons-for-woocommerce-pro'); ?></label>
                <select id="webtoffee_payment_methods" name="_wt_sc_payment_methods[]" style="width:50%;" class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e('Any payment method', 'wt-smart-coupons-for-woocommerce-pro'); ?>">
                    <?php  
                    $payment_methods = WC()->payment_gateways->payment_gateways();
    
                    if(!empty($payment_methods))
                    {
                        $payment_method_ids = self::get_processed_coupon_meta_value($post_id, '_wt_sc_payment_methods');

                        foreach($payment_methods as $payment_method)
                        {
                            if(wc_string_to_bool($payment_method->enabled))
                            {
                                echo '<option value="' . esc_attr($payment_method->id) . '" ' . selected(in_array($payment_method->id, $payment_method_ids), true, false) . '>' . esc_html($payment_method->title) . '</option>';
                            }
                        }
                    }
                    ?>
                </select>
                <?php echo wc_help_tip( __('Coupon will be applicable if any of these payment methods are selected.', 'wt-smart-coupons-for-woocommerce-pro') ); ?>
            </p>
            <!-- Payment methods:end -->

            
            <!-- User roles:start -->
            <p class="form-field">
                <label for="_wt_sc_user_roles"><?php _e('Applicable Roles', 'wt-smart-coupons-for-woocommerce-pro'); ?></label>
                <select id="_wt_sc_user_roles" name="_wt_sc_user_roles[]" style="width:50%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e('Any role', 'wt-smart-coupons-for-woocommerce-pro'); ?>">
                    <?php
                    if(!empty($available_roles))
                    {
                        $user_roles = self::get_processed_coupon_meta_value($post_id, '_wt_sc_user_roles');

                        foreach($available_roles as $role_id => $role)
                        {
                            echo '<option value="' . esc_attr($role_id) . '"' . selected(in_array($role_id, $user_roles), true, false) . '>' . esc_html(translate_user_role($role['name'])) . '</option>';
                        }
                    }
                    ?>
                </select> 
                <?php echo wc_help_tip( __('Coupon will be applicable if customer belongs to any of these User Roles.', 'wt-smart-coupons-for-woocommerce-pro') ); ?>
            </p>
            <!-- User roles:end -->

            
            <!-- Exclude user roles:start -->
            <?php
            /**
             *  Exclude user roles form field
             * 
             *  @since 2.0.7
             */
            ?>
            <p class="form-field">
                <label for="_wt_sc_exclude_user_roles"><?php _e('Exclude roles', 'wt-smart-coupons-for-woocommerce-pro'); ?></label>
                <select id="_wt_sc_exclude_user_roles" name="_wt_sc_exclude_user_roles[]" style="width:50%;" class="wc-enhanced-select" multiple="multiple">
                    <?php
                    if(!empty($available_roles))
                    {
                        $exclude_user_roles = self::get_processed_coupon_meta_value($post_id, '_wt_sc_exclude_user_roles');

                        foreach($available_roles as $role_id => $role)
                        {
                            echo '<option value="' . esc_attr($role_id) . '" ' . selected(in_array($role_id, $exclude_user_roles), true, false) . '>' . esc_html(translate_user_role($role['name'])) . '</option>';
                        }
                    }                  
                    ?>
                </select> 
                <?php echo wc_help_tip( __('Coupon will not be applicable if the user belongs to any of these roles', 'wt-smart-coupons-for-woocommerce-pro') ); ?>
            </p>
            <!-- Exclude user roles:end -->

            <!-- Country/state :start -->
            <p class="form-field">         
                <label for="_wt_coupon_available_location"><?php _e('Country/State restriction', 'wt-smart-coupons-for-woocommerce-pro'); ?></label> 
                <?php 
                /**
                 *  Country/state include/exclude
                 * 
                 *  @since 2.0.9
                 */
                $available_location_inc_exc = self::get_coupon_meta_value($post_id, '_wt_coupon_available_location_inc_exc');
                ?>
                <span class="wt_sc_option_radios">
                    <span><input type="radio" name="_wt_coupon_available_location_inc_exc" id="_wt_coupon_available_location_inc_exc_include" value="include" <?php checked($available_location_inc_exc, 'include'); ?>> <label for="_wt_coupon_available_location_inc_exc_include"><?php esc_html_e('Include', 'wt-smart-coupons-for-woocommerce-pro'); ?></label></span> 
                    <span><input type="radio" name="_wt_coupon_available_location_inc_exc" id="_wt_coupon_available_location_inc_exc_exclude" value="exclude" <?php checked($available_location_inc_exc, 'exclude'); ?>> <label for="_wt_coupon_available_location_inc_exc_exclude"><?php esc_html_e('Exclude', 'wt-smart-coupons-for-woocommerce-pro'); ?></label></span>
                </span>

                <select id="_wt_coupon_available_location" name="_wt_coupon_available_location[]" style="width:50%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e('Any Location', 'wt-smart-coupons-for-woocommerce-pro'); ?>">
                    <?php
                    $countries = WC()->countries->get_countries();

                    if(!empty($countries))
                    {
                        $available_locations = self::get_processed_coupon_meta_value($post_id, '_wt_coupon_available_location');

                        foreach($countries as $country_code => $country)
                        {
                            echo '<option value="'.esc_attr($country_code).'" ' . selected(in_array($country_code , $available_locations), true, false) . '>' . esc_html($country) . '</option>';

                            $states = WC()->countries->get_states( $country_code );
                            if($states)
                            {
                                echo '<optgroup label="' . esc_attr( $country ) . '">';
                                
                                foreach($states as $state_code => $state)
                                {
                                    $option_value = esc_attr($country_code . ':' . $state_code);
                                    echo '<option value="' . $option_value . '" ' . selected(in_array($option_value , $available_locations), true, false) . '>' . esc_html($country) . ' &mdash; ' . esc_html($state) . '</option>';
                                }

                                echo '</optgroup>';

                            }          
                        }
                    }
                    ?>
                </select> 
                <?php echo wc_help_tip(__("If 'Include' is selected, the coupon will only be valid for users in the selected Country/State. If 'Exclude' is selected, the coupon will not be valid for users in the selected Country/State", 'wt-smart-coupons-for-woocommerce-pro')); ?>
            </p>

            <div class="options_group" style="border:none;">
                <?php
                woocommerce_wp_radio(
                    array(
                        'id'        => '_wt_need_check_location_in',
                        'value'     => self::get_coupon_meta_value($post_id, '_wt_need_check_location_in'),
                        'class'     => 'wt_need_check_location_in',
                        'label'     => __('Identify restriction based on', 'wt-smart-coupons-for-woocommerce-pro'),
                        'options'   => array(
                                'billing'   => __('Billing Address', 'wt-smart-coupons-for-woocommerce-pro'),
                                'shipping'  => __('Shipping Address', 'wt-smart-coupons-for-woocommerce-pro')
                            ),
                        'description' => __('The selected address will set the Country/State restriction.', 'wt-smart-coupons-for-woocommerce-pro'),
                        'desc_tip'    => true,
                    )
                );
                ?>
            </div>
            <!-- Country/state :end -->

            <style type="text/css">
            #select2-_wt_coupon_available_location-results .select2-results__group{ display:none;}
            .woocommerce_options_panel .options_group fieldset._wt_need_check_location_in_field { padding-top:0px !important; }
            ._wt_need_check_location_in_field legend{ padding-left:15px; box-sizing:border-box; }
            </style>

            <?php
            do_action('webtoffee_coupon_metabox_checkout', $post_id, $post);
            ?>
        </div>
        <?php
    }


    /**
     *  Save the checkout options meta data
     * 
     *  @param $post_id     int     post/coupon id
     *  @param $post     object     post object
     */
    public function process_shop_coupon_meta($post_id, $post)
    {
        if( !class_exists( 'Wt_Smart_Coupon_Security_Helper' ) || !method_exists( 'Wt_Smart_Coupon_Security_Helper', 'check_user_has_capability' ) || !Wt_Smart_Coupon_Security_Helper::check_user_has_capability() ) 
        {
            wp_die(__('You do not have sufficient permission to perform this operation', 'wt-smart-coupons-for-woocommerce-pro'));
        }

        foreach(self::$meta_arr as $mata_key => $meta_info)
        {
            if(isset($_POST[$mata_key]) && !empty($_POST[$mata_key]))
            {
                if(isset($meta_info['type']))
                {
                    if('text_arr' === $meta_info['type'])
                    {
                        $val = Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST[$mata_key], 'text_arr');
                    }else
                    {
                        $val = sanitize_text_field($_POST[$mata_key]);
                    }
                }else
                {
                    $val = sanitize_text_field($_POST[$mata_key]);
                }

            }else
            {
                $val = (isset($meta_info['default']) ? $meta_info['default'] : '');
            }

            /**
             *  Some array values we need to save as strings for giving backward compatibility
             */
            if(isset($meta_info['save_as']) && is_array($val) && 'text' === $meta_info['save_as'])
            {
                $val = implode(",", $val);
            }

            //save the post meta
            update_post_meta($post_id, $mata_key, $val);
        }

    }

    private function get_countries_and_states(){
        $countries = WC()->countries->get_countries();
        if ( ! $countries ) {
            return array();
        }
        $output = array();
        foreach ( $countries as $key => $value ) {
            $states = WC()->countries->get_states( $key );

            if ( $states ) {
                foreach ( $states as $state_key => $state_value ) {
                    $output[ $key . ':' . $state_key ] = $value . ' - ' . $state_value;
                }
            } else {
                $output[ $key ] = $value;
            }
        }
        return $output;
    }
}

Wt_Smart_Coupon_Checkout_Options_Admin::get_instance();