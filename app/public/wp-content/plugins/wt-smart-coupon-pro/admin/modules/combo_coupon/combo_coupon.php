<?php
/**
 * Combo coupon admin/public
 *
 * @link       
 * @since 2.0.1   
 *
 * @package  Wt_Smart_Coupon  
 */
if (!defined('ABSPATH')) {
    exit;
}
class Wt_Smart_Coupon_Combo_Coupon_Admin
{
    public $module_base='combo_coupon';
    public $module_id='';
    public static $module_id_static='';
    private static $instance = null;

    public function __construct()
    {
        $this->module_id=Wt_Smart_Coupon::get_module_id($this->module_base);
        self::$module_id_static=$this->module_id;

        add_action('woocommerce_coupon_options_usage_restriction', array($this, 'admin_coupon_usage_restrictions'), 10, 1);
        add_action('woocommerce_process_shop_coupon_meta', array($this, 'save_combo_coupon_meta'), 10, 2);
        add_filter('woocommerce_coupon_is_valid', array($this, 'validate_coupon_with_combo_coupon'), 10, 2);

        /**
         *  Register the messages that are customizable via admin panel
         *  @since 3.0.0
         */
        add_filter('wt_sc_intl_add_notifications', array($this, 'register_customized_texts'));

    }

    /**
     * Get Instance
     */
    public static function get_instance()
    {
        if(self::$instance==null)
        {
            self::$instance=new Wt_Smart_Coupon_Combo_Coupon_Admin();
        }
        return self::$instance;
    }

    /**
     * Create Combo coupon fields.
     * @since 1.2.0
     */
    public function admin_coupon_usage_restrictions($post)
    {
        $coupon    = new WC_Coupon( $post );

        $all_discount_types = wc_get_coupon_types();
        $coupon_ids = get_post_meta( $post, '_wt_combo_coupon_can_use_with', true );
        $coupon_ides = array_filter( explode( ',', $coupon_ids ));

        $individual_use = $coupon->get_individual_use();
        
        if( $individual_use  ) {
            $style = 'display:none';
        } else {
            $style = '';
        }

        ?>
            <div class="wt_combo_coupon_fields" style="<?php echo $style; ?>">
            
            <p class="form-field _wt_combo_coupon_field" id="sc-field">
            <label for="_wt_combo_coupon_can_use_with"><?php echo esc_html__( 'Coupon can be used with', 'wt-smart-coupons-for-woocommerce-pro'); ?></label>

                <select class="wt-coupon-search" style="width:50%;" multiple="multiple" id="_wt_combo_coupon_can_use_with" name="_wt_combo_coupon_can_use_with[]" data-placeholder="<?php echo esc_attr__( 'Search for a coupon...', 'wt-smart-coupons-for-woocommerce-pro' ); ?>" data-action="wt_json_search_coupons" data-security="<?php echo esc_attr( wp_create_nonce( 'search-coupons' ) ); ?>" data-postid="<?php echo esc_attr($post); ?>">
                    <?php
                    if ( ! empty( $coupon_ides ) ) {
                        foreach ( $coupon_ides as $coupon_id ) {
                            $coupon_title = get_the_title( $coupon_id );

                            $coupon = new WC_Coupon( $coupon_title );

                            $discount_type = $coupon->get_discount_type();

                            if ( ! empty( $discount_type ) ) {
                                $discount_type = sprintf( __( ' ( %1$s: %2$s )', 'wt-smart-coupons-for-woocommerce-pro' ), __( 'Type', 'wt-smart-coupons-for-woocommerce-pro' ), $all_discount_types[ $discount_type ] );
                            }
                            
                            /**
                             * BOGO name instead of coupon code.
                             * @since 3.0.0
                             */
                            if( 'wbte_sc_bogo' === get_post_meta( $coupon_id, 'discount_type', true ) )
                            {
                                $coupon_title = get_post_meta( $coupon_id, 'wbte_sc_bogo_coupon_name', true );
                                $discount_type = wp_kses_post( '( ' . __( 'Type', 'wt-smart-coupons-for-woocommerce-pro') . ': ' . __('BOGO', 'wt-smart-coupons-for-woocommerce-pro') . __( ', ID', 'wt-smart-coupons-for-woocommerce-pro') . ': ' . $coupon_id . ' )' );
                            }

                            echo '<option value="' . esc_attr( $coupon_id ) . '"' . selected( true, true, false ) . '>' . esc_html( $coupon_title . $discount_type ) . '</option>';
                        }
                    }
                    ?>

                </select>
                <?php echo wc_help_tip( __('Configure the list of coupons that can be redeemed together with the specified.','wt-smart-coupons-for-woocommerce-pro') ); ?>

            </p>

            <?php

                $coupon_ids = get_post_meta( $post, '_wt_combo_coupon_cannot_use_with', true );
                $coupon_ides = array_filter( explode( ',', $coupon_ids ) );

            ?>

            <p class="form-field _wt_combo_coupon_field" id="sc-field">
            <label for="_wt_combo_coupon_cannot_use_with"><?php echo esc_html__( 'Coupon can\'t be used with', 'wt-smart-coupons-for-woocommerce-pro'); ?></label>

                <select class="wt-coupon-search" style="width: 50%;" multiple="multiple" id="_wt_combo_coupon_cannot_use_with" name="_wt_combo_coupon_cannot_use_with[]" data-placeholder="<?php echo esc_attr__( 'Search for a coupon&hellip;', 'wt-smart-coupons-for-woocommerce-pro' ); ?>" data-action="wt_json_search_coupons" data-security="<?php echo esc_attr( wp_create_nonce( 'search-coupons' ) ); ?>" data-postid= <?php echo $post; ?> >
                    <?php

                    if ( ! empty( $coupon_ides ) ) {
                        foreach ( $coupon_ides as $coupon_id ) {
                            $coupon_title = get_the_title( $coupon_id );

                            $coupon = new WC_Coupon( $coupon_title );

                            $discount_type = $coupon->get_discount_type();

                            if ( ! empty( $discount_type ) ) {
                                $discount_type = sprintf( __( ' ( %1$s: %2$s )', 'wt-smart-coupons-for-woocommerce-pro' ), __( 'Type', 'wt-smart-coupons-for-woocommerce-pro' ), $all_discount_types[ $discount_type ] );
                            }

                            /**
                             * BOGO name instead of coupon code.
                             * @since 3.0.0
                             */
                            if( 'wbte_sc_bogo' === get_post_meta( $coupon_id, 'discount_type', true ) )
                            {
                                $coupon_title = get_post_meta( $coupon_id, 'wbte_sc_bogo_coupon_name', true );
                                $discount_type = wp_kses_post( '( ' . __( 'Type', 'wt-smart-coupons-for-woocommerce-pro') . ': ' . __('BOGO', 'wt-smart-coupons-for-woocommerce-pro') . __( ', ID', 'wt-smart-coupons-for-woocommerce-pro') . ': ' . $coupon_id . ' )' );
                            }

                            echo '<option value="' . esc_attr( $coupon_id ) . '"' . selected( true, true, false ) . '>' . esc_html( $coupon_title . $discount_type ) . '</option>';
                        }
                    }
                    ?>

                </select>
                <?php echo wc_help_tip( __('Configure the list of coupons that cannot be redeemed together with the specified.','wt-smart-coupons-for-woocommerce-pro') ); ?>

            </p>
            </div>

        <?php
    }

    /**
     * Save combo coupon meta values
     * @since 1.2.0
     */
    public function save_combo_coupon_meta($post_id, $post)
    {
        
        if( ! isset( $_POST['individual_use'] ) ||  $_POST['individual_use'] == '' ) {
            if( isset( $_POST['_wt_combo_coupon_can_use_with'] ) && !empty(  $_POST['_wt_combo_coupon_can_use_with'] )  ) {
                $_wt_combo_coupon_can_use_with = Wt_Smart_Coupon_Security_Helper::sanitize_item( $_POST['_wt_combo_coupon_can_use_with'], 'int_arr' );
                update_post_meta($post_id, '_wt_combo_coupon_can_use_with',  implode(',', $_wt_combo_coupon_can_use_with ) );
            } else {
                update_post_meta($post_id, '_wt_combo_coupon_can_use_with', '' );

            }

            if( isset( $_POST['_wt_combo_coupon_cannot_use_with'] ) && !empty(  $_POST['_wt_combo_coupon_cannot_use_with'] )  ) {
                $_wt_combo_coupon_cannot_use_with = Wt_Smart_Coupon_Security_Helper::sanitize_item( $_POST['_wt_combo_coupon_cannot_use_with'], 'int_arr' );
                update_post_meta($post_id, '_wt_combo_coupon_cannot_use_with',  implode(',', $_wt_combo_coupon_cannot_use_with ) );
            } else {
                update_post_meta($post_id, '_wt_combo_coupon_cannot_use_with', '' );
            }
        }
    }


    /**
     * Validate combo coupon option.
     * @since 1.2.0
     */
    public function validate_coupon_with_combo_coupon($valid, $coupon)
    {
        // return false;
        if (! $valid) {
            return false;
        }

        
        global $woocommerce;
        $coupon_id  = $coupon->get_id();
        $applied_coupons = $woocommerce->cart->applied_coupons;

        if( empty( $applied_coupons )) {
            return $valid;
        }
        $applied_coupon_ids = array();
        foreach( $applied_coupons as $applied_coupon ) {
            $ap_coupon_id = wc_get_coupon_id_by_code( $applied_coupon );
            if($ap_coupon_id == $coupon_id ) {
                continue;
            }
            $applied_coupon_ids[] = $ap_coupon_id;
        }
        
        $coupon_cannot_use_with = get_post_meta( $coupon_id, '_wt_combo_coupon_cannot_use_with', true );
        $cannot_ids = explode( ',',$coupon_cannot_use_with );

        if( ! empty( $cannot_ids ) && ! empty(array_intersect($cannot_ids, $applied_coupon_ids) ) ) {
            $valid = false;
            $msg = $this->get_customized_text('coupon_combination_conjunction', array('coupon_code' => $coupon->get_code())); // Updated to use the new key
            throw new Exception($msg ?: __('Sorry, this coupon cannot be used in conjunction with the applied coupon', 'wt-smart-coupons-for-woocommerce-pro'), 109);
        }


        $coupon_can_use_with = get_post_meta( $coupon_id, '_wt_combo_coupon_can_use_with', true );
        $can_ids = array_filter( explode( ',',$coupon_can_use_with ) );
        $array_diff = array_diff( $applied_coupon_ids,$can_ids );
        if( ! empty( $can_ids ) &&  ! empty(  $array_diff ) ) {
            $valid = false;
            $msg = $this->get_customized_text('coupon_combination_conjunction', array('coupon_code' => $coupon->get_code())); // Updated to use the new key
            throw new Exception($msg ?: __('Sorry, this coupon cannot be used in conjunction with the applied coupon', 'wt-smart-coupons-for-woocommerce-pro'), 109);
        }
       

        return $valid;
        
    }
    
    /**
     *  Register the messages that are customizable via admin panel
     *  
     *  @since  3.0.0
     *  @param  array    $notifications  Array of message info
     *  @return array    Array of message info
     */
    public function register_customized_texts($notifications)
    {
        $notifications['coupon_combination_conjunction'] = array(
            'message'           => __('Sorry, this coupon cannot be used in conjunction with the applied coupon', 'wt-smart-coupons-for-woocommerce-pro'),
            'description'       => __('Displays when a coupon is applied that cannot be used in conjunction with another coupon.', 'wt-smart-coupons-for-woocommerce-pro'),
            'status'            => 1, 
            'supported_placeholders' => array(
                'coupon_code' => __('Current coupon code', 'wt-smart-coupons-for-woocommerce-pro'),
            ),
            'available_filters' => array(),
            'module'   => 'combo_coupon',
            'group'         => 'warning',
            'initiater'     => 'sc', //smart coupon
        );

        return $notifications;
    }

    /**
     * Get customized notification messages
     *  
     * @since  3.0.0
     * @param  string      $key    Unique key for the message
     * @param  array       $args   Values for the function: Coupon code, Placeholders etc
     * @return string      Empty string when message was disabled otherwise the message
     */
    public function get_customized_text($key, $args = array())
    {
        return Wt_Smart_Coupon_Public::get_customized_text($key, $args);
    }
    
}
Wt_Smart_Coupon_Combo_Coupon_Admin::get_instance();