<?php
/**
 * Coupon Expiry days, Start date functionality. Admin/Public
 *
 * @link       
 * @since 2.0.1     
 *
 * @package  Wt_Smart_Coupon  
 */
if (!defined('ABSPATH')) {
    exit;
}

class Wt_Smart_Coupon_Lifespan
{
    public $module_base='coupon_lifespan';
    public $module_id='';
    public static $module_id_static='';
    private static $instance = null;
    private static $days = array();

    public function __construct()
    {
        $this->module_id=Wt_Smart_Coupon::get_module_id($this->module_base);
        self::$module_id_static=$this->module_id;

        add_action( 'init', array( $this,'init' ) );

        /**
         *  Field for coupon expiry days, Coupon start date
         */
        add_action('woocommerce_coupon_options', array($this, 'add_admin_fields'), 6, 2);

        
        /**
         *  Save admin field data
         */
        add_action('woocommerce_process_shop_coupon_meta', array($this, 'save_settings'), 11, 2);


        /**
         *  Validate coupon start date and time
         */
        add_filter('woocommerce_coupon_is_valid', array($this, 'is_valid'), 10, 2);
        
        
        /**
         *  Validate coupon expiry date and time
         */
        add_filter('woocommerce_coupon_validate_expiry_date', array($this, 'validate_expiry'), 10, 3);


        /**
         *  Add start time/expiry time on import
         * 
         *  @since 2.4.0
         */
        add_action( 'wbte_sc_after_coupon_imported', array( $this, 'process_start_expiry_time_update_on_import' ), 10, 3 );
        add_action( 'wbte_sc_update_start_expiry_time_on_import', array( $this, 'update_start_expiry_time_on_import' ), 10, 3 );

        add_action( 'wt_sc_before_bogo_coupon_save', array( $this, 'save_start_expiry_data_for_bogo_coupon' ), 10, 2 );
    }

    /** 
     * Week days for coupon available days option
     * 
     * @since 2.1.1
     * @since 3.1.0 Moved from __construct to init to avoid issues with translating text before init.
     */
    public function init() {

        self::$days = array(
            'sun' => __( 'Sun', 'wt-smart-coupons-for-woocommerce-pro' ), 
            'mon' => __( 'Mon', 'wt-smart-coupons-for-woocommerce-pro' ), 
            'tue' => __( 'Tue', 'wt-smart-coupons-for-woocommerce-pro' ), 
            'wed' => __( 'Wed', 'wt-smart-coupons-for-woocommerce-pro' ), 
            'thu' => __( 'Thu', 'wt-smart-coupons-for-woocommerce-pro' ), 
            'fri' => __( 'Fri', 'wt-smart-coupons-for-woocommerce-pro' ), 
            'sat' => __( 'Sat', 'wt-smart-coupons-for-woocommerce-pro' ),
        );
    }

    /**
     * Get Instance
     */
    public static function get_instance()
    {
        if(self::$instance==null)
        {
            self::$instance=new Wt_Smart_Coupon_Lifespan();
        }
        return self::$instance;
    }

    /**
     *  Display field for adding expiry in days, Coupon start date
     *  
     *  @since 2.0.7    Added coupon expiry and start time.
     *  @param integer   $coupon_id The coupon id.
     *  @param WC_Coupon $coupon    The coupon object.
     */
    public function add_admin_fields($coupon_id = 0, $coupon = null)
    {
        ?>
        <script type="text/javascript">
            jQuery(function() {
                jQuery(document).ready(function(){

                    /* move start date just above expiry date */
                    jQuery('._wt_coupon_start_date_field').insertBefore('.expiry_date_field');                 
                    jQuery('.expiry_date_field .woocommerce-help-tip').attr({'data-wt-sc-tip': '<?php echo esc_attr(__('The coupon will expire at the given time of this date.', 'wt-smart-coupons-for-woocommerce-pro'));?>'}).tipTip({'attribute': 'data-wt-sc-tip'});

                    jQuery('._wt_coupon_enable_days_field').insertAfter('._wt_coupon_start_date_field');
                    jQuery('._wt_coupon_expiry_in_days_field').insertAfter('._wt_coupon_enable_days_field');

                    jQuery('.wt_sc_coupon_expiry_time_form_field_group').insertAfter('.expiry_date_field #expiry_date');
                    jQuery('.expiry_date_field #expiry_date').insertBefore('.wt_sc_coupon_expiry_time_form_field_group .wt_sc_coupon_time_field_group');
                    
                    jQuery('.wt_sc_coupon_start_time_form_field_group').insertAfter('._wt_coupon_start_date_field #_wt_coupon_start_date');
                    jQuery('._wt_coupon_start_date_field #_wt_coupon_start_date').insertBefore('.wt_sc_coupon_start_time_form_field_group .wt_sc_coupon_time_field_group');

                    jQuery('.expiry_date_field .woocommerce-help-tip').insertAfter('.expiry_date_field .wt_sc_coupon_time_field_group').css({'margin-top':'7px'});

                    let expiry_date = jQuery('#expiry_date').val();
                    let expiry_time_hour = jQuery('[name="_wt_sc_coupon_expiry_time_hour"]').val();
                    let expiry_time_min = jQuery('[name="_wt_sc_coupon_expiry_time_minute"]').val();
                    
                    let show_hide_expiry_in_days_field = function() {
                        let enabled = jQuery('#_wt_coupon_enable_days').is(":checked");
                        if (true === enabled) {
                            jQuery('._wt_coupon_expiry_in_days_field').show();
                            jQuery('.expiry_date_field').hide();
                            jQuery('#expiry_date, [name="_wt_sc_coupon_expiry_time_hour"], [name="_wt_sc_coupon_expiry_time_minute"]').val('');

                        } else {
                            jQuery('._wt_coupon_expiry_in_days_field').hide();
                            jQuery('.expiry_date_field').show();
                            jQuery('#expiry_date').val(expiry_date);
                            jQuery('[name="_wt_sc_coupon_expiry_time_hour"]').val(expiry_time_hour);
                            jQuery('[name="_wt_sc_coupon_expiry_time_minute"]').val(expiry_time_min);
                        }
                    }
                    
                    show_hide_expiry_in_days_field();
                    
                    jQuery('#_wt_coupon_enable_days').on('change', function() {
                        show_hide_expiry_in_days_field();
                    });

                    jQuery('.wt_sc_coupon_available_in_specific_days').on('click', function(){
                        var checkbox = jQuery(this).find('input[type="checkbox"]');                      
                        
                        if(checkbox.is(':checked')){
                            checkbox.prop('checked', false);  
                            jQuery(this).removeClass('wt_sc_selected');
                        }else{
                            checkbox.prop('checked', true);
                            jQuery(this).addClass('wt_sc_selected');
                        }                       
                    });

                    var wt_sc_min_max_validation = function(elm) {
                        vl = parseInt(elm.val());
                        var min = parseInt(elm.attr('min'));
                        var max = parseInt(elm.attr('max'));

                        if( vl < min || vl > max ) {
                            elm.val('');
                        }
                    }

                    /**
                     *  Number validation in time fields
                     */
                    jQuery('.wt_sc_coupon_time_field').on('input', function() {
                        var vl = jQuery( this ).val();
                        var reg = /^[0-9]{0,2}$/;
                        
                        if( !reg.test( vl ) ) {
                            var new_vl = '';
                            vl = String( vl );
                            for ( var i = 0; i < vl.length; i++ ) {
                                
                                if ( 2 === new_vl.length ) {
                                    break;
                                }

                                if ( reg.test( vl[i] ) ) {
                                    new_vl += vl[i];
                                }
                            }

                            jQuery(this).val( new_vl );
                            wt_sc_min_max_validation( jQuery( this ) );

                        } else {
                            wt_sc_min_max_validation( jQuery( this ) );
                        }
                    });


                    /**
                     *  Move Schedule field group to last
                     *  Add `General` heading on top for other fields
                     */
                    jQuery('.wt_sc_field_group_hd:not(.wt_sc_coupon_general_settings_hd), .wt_sc_field_group_content').appendTo('#general_coupon_data');
                    jQuery('.wt_sc_coupon_general_settings_hd').prependTo('#general_coupon_data');
                    

                    /**
                     *  Add fields to field group
                     */
                    jQuery('._wt_coupon_start_date_field, ._wt_sc_coupon_on_selected_days_field, .expiry_date_field, ._wt_coupon_enable_days_field, ._wt_coupon_expiry_in_days_field').attr({'data-field-group': "wt_sc_coupon_schedule_fields"}).addClass('wt_sc_field_group_children');

                    wt_sc_field_group.Set();
                });
            });
        </script>
        <style type="text/css">
            .wt_sc_coupon_life_span_form_field_group{ width:calc(100% - 25px); float:left; display:flex; gap:5px; }
            .woocommerce_options_panel .wt_sc_coupon_life_span_form_field_group .wt_sc_coupon_time_field{ width:35px;  }
            .woocommerce_options_panel .wt_sc_coupon_life_span_form_field_group .wt_sc_coupon_time_field:focus{ border-width:0px; box-shadow:none; }
            .woocommerce_options_panel .wt_sc_coupon_life_span_form_field_group .date-picker{ width:calc(50% + 13px); }
            .wt_sc_coupon_available_in_specific_days{ padding:1px 12px; background:#fff; border:solid 1px #6E7681; border-radius:15px; cursor:pointer; margin-bottom:3px; text-align:center; }
            .wt_sc_coupon_available_in_specific_days.wt_sc_selected{ color:#056BE7; background:#F1F8FE; border:solid 1px #056BE7; }
            .wt_sc_coupon_available_in_specific_days input[type="checkbox"]{ visibility:hidden; width:0px; height:0px; margin:0px; min-width:0px; border:solid 0px #fff; margin-left:-4px; }
            .wt_sc_coupon_time_field_group{ border:solid 1px #8c8f94; border-radius:4px; }
            .wt_sc_coupon_time_field_group::after{ content:":"; position:absolute; z-index:0; margin-left:-37px; margin-top:2px; }
            p.expiry_date_field input.wt_sc_coupon_time_field, p._wt_coupon_start_date_field input.wt_sc_coupon_time_field{ border-width:0px; }
        </style>
        <?php
        /**
         *  Enable coupon in selected days.
         * 
         *  @since 2.1.1
         */
        $coupon_available_days = get_post_meta($coupon_id, '_wt_sc_coupon_on_selected_days', true);
        $coupon_available_days = is_array($coupon_available_days) ? $coupon_available_days : array();        
        ?>
        <p class="form-field _wt_sc_coupon_on_selected_days_field">
            <label for="_wt_sc_coupon_on_selected_days"><?php esc_html_e('Coupon active on', 'wt-smart-coupons-for-woocommerce-pro'); ?></label>
            <span class="wt_sc_coupon_life_span_form_field_group" style="width:auto;">
                <?php
                foreach( self::$days as $day_key => $day_name ) {
                    ?>
                    <span class="wt_sc_coupon_available_in_specific_days <?php echo esc_attr(in_array($day_key, $coupon_available_days) ? 'wt_sc_selected' : ''); ?>">
                        <input type="checkbox" name="_wt_sc_coupon_on_selected_days[]" value="<?php echo esc_attr($day_key); ?>" <?php echo esc_attr(in_array($day_key, $coupon_available_days) ? 'checked' : ''); ?>> <?php echo esc_html($day_name); ?>
                    </span>
                    <?php
                }
                ?>
            </span>
            <?php 
            echo wc_help_tip( __( 'The coupon will be applicable only on the specified days of the week. If no options are selected, the coupon will be valid throughout the entire week.', 'wt-smart-coupons-for-woocommerce-pro' ) );
            ?>         
        </p>
        <?php


        /* field for coupon start date */
        $start_date = '';
        if(metadata_exists('post', $coupon_id, '_wt_coupon_start_date'))
        {
            $start_date = get_post_meta($coupon_id, '_wt_coupon_start_date', true);
            if($start_date)
            {
                $start_date=date('Y-m-d', strtotime($start_date));
            }
        }
        woocommerce_wp_text_input(
            array(
                'id'                => '_wt_coupon_start_date',
                'value'             => esc_attr( $start_date ),
                'label'             => __( 'Coupon start date', 'wt-smart-coupons-for-woocommerce-pro'),
                'placeholder'       => 'YYYY-MM-DD',
                'description'       => '',
                'class'             => 'date-picker',
                'custom_attributes' => array(
                    'pattern' => apply_filters('woocommerce_date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])'),
                ),
            )
        );

        
        /**
         *  Coupon start time
         * 
         *  @since 2.0.7
         */
        $start_time = get_post_meta($coupon_id, '_wt_coupon_start_time', true);
        $start_time_arr = ($start_time ? explode(':', $start_time) : array());
        $start_time_hour = (isset($start_time_arr[0]) ? $this->format_start_expiry_time($start_time_arr[0]) : 00);
        $start_time_minute = (isset($start_time_arr[1]) ? $this->format_start_expiry_time($start_time_arr[1]) : 00);

        if ( "" === $start_time ) {
            $start_time_hour = '';
            $start_time_minute = '';
        }

        ?>      
            <span class="wt_sc_coupon_life_span_form_field_group wt_sc_coupon_start_time_form_field_group">
                <span class="wt_sc_coupon_time_field_group" title="<?php esc_attr_e( '24 hour format', 'wt-smart-coupons-for-woocommerce-pro' ); ?>">
                    <input type="text" class="wt_sc_coupon_time_field" name="_wt_sc_coupon_start_time_hour" min="0" max="23" value="<?php echo esc_attr($start_time_hour);?>" placeholder="00" title="<?php esc_attr_e('Hour', 'wt-smart-coupons-for-woocommerce-pro'); ?>">
                    <input type="text" class="wt_sc_coupon_time_field" name="_wt_sc_coupon_start_time_minute" min="0" max="59" value="<?php echo esc_attr($start_time_minute);?>" placeholder="00" title="<?php esc_attr_e('Minute', 'wt-smart-coupons-for-woocommerce-pro'); ?>"> 
                </span>
            </span>   
        <?php

        
        /* coupon expiry days */
        $_wt_coupon_enable_days = get_post_meta($coupon_id, '_wt_coupon_enable_days', true);
        $_wt_coupon_expiry_in_days = Wt_Smart_Coupon_Security_Helper::sanitize_item(get_post_meta($coupon_id, '_wt_coupon_expiry_in_days', true), 'int');

        woocommerce_wp_text_input(
            array(
                'id'                => '_wt_coupon_expiry_in_days',
                'value'             => $_wt_coupon_expiry_in_days,
                'label'             => __('Coupon expiry (in days)', 'wt-smart-coupons-for-woocommerce-pro'),
                'type'              => 'number',
                'custom_attributes' => array(
                    'step' => 'any',
                    'min'  => 0,
                ),
            )
        );


        /**
         *  Coupon expiry time
         * 
         *  @since 2.0.7
         */
        $expiry_time = get_post_meta($coupon_id, '_wt_coupon_expiry_time', true);
        $expiry_time_arr = ($expiry_time ? explode(':', $expiry_time) : array());
        $expiry_time_hour = (isset($expiry_time_arr[0]) ? $this->format_start_expiry_time($expiry_time_arr[0]) : '');
        $expiry_time_minute = (isset($expiry_time_arr[1]) ? $this->format_start_expiry_time($expiry_time_arr[1]) : '');

        if("" === $expiry_time)
        {
            $expiry_time_hour = '';
            $expiry_time_minute = '';
        }

        ?>
            <span class="wt_sc_coupon_life_span_form_field_group wt_sc_coupon_expiry_time_form_field_group">              
                <span class="wt_sc_coupon_time_field_group" title="<?php esc_attr_e( '24 hour format', 'wt-smart-coupons-for-woocommerce-pro' ); ?>">
                    <input type="text" class="wt_sc_coupon_time_field" name="_wt_sc_coupon_expiry_time_hour" min="0" max="23" value="<?php echo esc_attr($expiry_time_hour);?>" placeholder="23" title="<?php esc_attr_e('Hour', 'wt-smart-coupons-for-woocommerce-pro'); ?>">
                    <input type="text" class="wt_sc_coupon_time_field" name="_wt_sc_coupon_expiry_time_minute" min="0" max="59" value="<?php echo esc_attr($expiry_time_minute);?>" placeholder="59" title="<?php esc_attr_e('Minute', 'wt-smart-coupons-for-woocommerce-pro'); ?>">
                </span>
            </span>
        <?php

        woocommerce_wp_checkbox(array(
            'id'        => '_wt_coupon_enable_days',
            'value'     => wc_bool_to_string($_wt_coupon_enable_days),
            'label'     => __('Enter coupon expiry in days', 'wt-smart-coupons-for-woocommerce-pro'),
            'description' => __('Use this option if you want the expiry date to be calculated dynamically(as and when the coupon is created) based on the number of days provided.', 'wt-smart-coupons-for-woocommerce-pro'),
            'desc_tip'  => 'true',
        ));


        /**
         *  General and Schedule field groups
         * 
         */
        ?>
        <div class="wt_sc_field_group_hd wt_sc_coupon_general_settings_hd" style="margin-bottom:15px; cursor:default;">
            <?php esc_html_e('General', 'wt-smart-coupons-for-woocommerce-pro');?> 
        </div>

        <div class="wt_sc_field_group_hd" style="margin-bottom:15px;">
            <?php esc_html_e('Schedule', 'wt-smart-coupons-for-woocommerce-pro');?> <div class="wt_sc_field_group_toggle_btn" data-visibility="1" data-id="wt_sc_coupon_schedule_fields"><span class="dashicons dashicons-arrow-down"></span></div>
        </div>
        <div class="wt_sc_field_group_content" data-field-group="wt_sc_coupon_schedule_fields"></div>
        <?php 
       
    }


    /**
     * Save admin field info
     */
    public function save_settings($post_id, $post)
    {
        // Save coupon start date meta
        if(isset($_POST['_wt_coupon_start_date'] ) && "" !== $_POST['_wt_coupon_start_date'])
        {
            $start_date = Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST['_wt_coupon_start_date']);
            update_post_meta( $post_id, '_wt_coupon_start_date', $start_date );

            update_post_meta( $post_id, '_wt_coupon_start_date_timestamp', Wt_Smart_Coupon_Admin::wt_sc_get_date_prop($start_date)->getTimestamp());

        }else
        {
            update_post_meta($post_id, '_wt_coupon_start_date', '' );
            update_post_meta($post_id, '_wt_coupon_start_date_timestamp', '' );
        }

        //save coupon expiration days fields meta
        if (isset($_POST['_wt_coupon_enable_days']) && "" !== $_POST['_wt_coupon_enable_days']) {
            update_post_meta($post_id, '_wt_coupon_enable_days',  true);
        } else {
            update_post_meta($post_id, '_wt_coupon_enable_days', false);
        }

        if (isset($_POST['_wt_coupon_expiry_in_days']) && "" !== $_POST['_wt_coupon_expiry_in_days']) {
            $_wt_coupon_expiry_in_days = Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST['_wt_coupon_expiry_in_days'], 'int');
            update_post_meta($post_id, '_wt_coupon_expiry_in_days', $_wt_coupon_expiry_in_days);
        } else {
            update_post_meta($post_id, '_wt_coupon_expiry_in_days', '');
        }

        
        /**
         *  Save coupon start time, Deletes when start date is empty
         *  
         *  @since 2.0.7
         */ 
        $this->save_coupon_start_expiry_time($post_id, '_wt_coupon_start_date', '_wt_sc_coupon_start_time_hour', '_wt_sc_coupon_start_time_minute', '_wt_coupon_start_time');

        /**
         *  Save coupon expiry time, Deletes when expiry date is empty
         *  
         *  @since 2.0.7
         */
        $this->save_coupon_start_expiry_time($post_id, 'expiry_date', '_wt_sc_coupon_expiry_time_hour', '_wt_sc_coupon_expiry_time_minute', '_wt_coupon_expiry_time');


        /**
         *  Save coupon enabled days.
         * 
         *  @since 2.1.1
         */
        $available_days = isset( $_POST['_wt_sc_coupon_on_selected_days'] ) ? wc_clean( $_POST['_wt_sc_coupon_on_selected_days'] ) : array();
        $available_days = array_intersect( array_keys( self::$days ), $available_days );
        update_post_meta( $post_id, '_wt_sc_coupon_on_selected_days', $available_days );
    }


    /**
     *  Save coupon expiry/start time, Deletes when expiry/start date is empty
     *  
     *  @since 2.0.7
     *  @since 2.4.0        Added $meta_data array as optional argument. This will be helpfull for import actions.
     *                      Added compatibility for `date_expires` meta key.
     * 
     *  @param int          $post_id            Id of the post
     *  @param string       $date_post_key      Date data key
     *  @param string       $hour_post_key      Hour data key
     *  @param string       $minute_post_key    Minute data key
     *  @param string       $minute_post_key    Minute data key
     *  @param string       $meta_key           Post meta key to save the data
     *  @param array        $meta_data          Optional. Meta data array. If provided, check data in this array otherwise uses $_POST
     */
    private function save_coupon_start_expiry_time( $post_id, $date_post_key, $hour_post_key, $minute_post_key, $meta_key, $meta_data = array() ) {
        
        $meta_data = empty( $meta_data ) ? $_POST : $meta_data;

        if ( isset( $meta_data[ $date_post_key ] ) && "" !== $meta_data[ $date_post_key ] ) { //time is valid only when date is set
        
            if ( 'expiry_date' ===  $date_post_key || 'date_expires' ===  $date_post_key ) {
                $hour = ( isset( $meta_data[ $hour_post_key ] ) && "" !== $meta_data[ $hour_post_key ] ? absint( sanitize_text_field( $meta_data[ $hour_post_key ] ) ) : 23 );
                $minute = ( isset( $meta_data[ $minute_post_key ] ) && "" !== $meta_data[ $minute_post_key ] ? absint( sanitize_text_field( $meta_data[ $minute_post_key ] ) ) : 59 );
            } else {
                $hour = ( isset( $meta_data[ $hour_post_key ] ) ? absint( sanitize_text_field( $meta_data[ $hour_post_key ] ) ) : 00 );
                $minute = ( isset( $meta_data[ $minute_post_key ] ) ? absint( sanitize_text_field( $meta_data[ $minute_post_key ] ) ) : 00 ); 
            }

            $expiry_time = min( $hour, 23 ) . ':' . min( $minute, 59 );
            update_post_meta( $post_id, $meta_key, $expiry_time );

        } else {
            update_post_meta( $post_id, $meta_key, '' );
        }
    }

    
    /**
     *  Check coupon is valid W.R.T expiry in days
     * 
     *  @since 1.3.2
     *  @since 2.0.7    Added start time
     */
    public function is_valid($valid, $coupon)
    {
        if(!$valid  || empty($coupon) || !is_a($coupon, 'WC_Coupon'))
        {
            return $valid;
        }

        $coupon_id = $coupon->get_id();
        
        if(!empty($coupon_id))
        {
            /** 
             *  Coupon start date validation 
             * 
             *  @since 2.0.7 Added start time along with start date
             */
            if ( ! $this->is_coupon_started( $coupon ) ) {
                $valid =  false;
                $start_date_text = Wt_Smart_Coupon_Public::get_coupon_starts( $coupon, false ); // Date and time text

                throw new Exception(sprintf(__('Sorry, this coupon only available after %s', 'wt-smart-coupons-for-woocommerce-pro'), $start_date_text), 109);
                return $valid;
            }


            /**
             *  Coupon enabled days validation.
             *  If no days are configured the coupon is valid for all days.
             * 
             *  @since 2.1.1
             */
            if( ! $this->is_coupon_available_today( $coupon_id ) ) {
                
                $coupon_available_days = get_post_meta( $coupon_id, '_wt_sc_coupon_on_selected_days', true );
                
                /**
                 *  Alter coupon not available for the day message.
                 *  
                 *  @since 2.1.1
                 *  @param string       $msg                    Default error message
                 *  @param array        $coupon_available_days  Array of coupon available days. Days are in the 3 letter form eg: `mon`, `tue`, `wed` etc.
                 *  @param WC_Coupon    $coupon                 Coupon object
                 */
                $coupon_not_valid_msg = (string) apply_filters('wt_sc_alter_coupon_not_available_for_the_current_day_msg', __( 'Sorry, this coupon is not valid for today.', 'wt-smart-coupons-for-woocommerce-pro' ), $coupon_available_days, $coupon );

                throw new Exception($coupon_not_valid_msg, 109);
                return false;
            }     

            /**
             *  Coupon expiry in days validation.
             * 
             *  @since 2.4.0
             */
            if( $this->is_coupon_expired( $coupon ) )
            {
                $valid = false;
                throw new Exception( __( 'This coupon has expired.', 'wt-smart-coupons-for-woocommerce-pro' ), 109 );         
            }
        }

        return $valid;
    }


    /** 
     *  Coupon expiry validation 
     * 
     *  @since  2.0.7
     *  @param  bool             $not_valid      Is not valid
     *  @param  WC_Coupon        $coupon         Coupon
     *  @param  WC_Discounts     $discounts_obj  Discounts object
     *  @return bool
     */
    public function validate_expiry($not_valid, $coupon, $discounts_obj)
    {

        if(empty($coupon) || !is_a($coupon, 'WC_Coupon'))
        {
            return $not_valid;
        }

        if($this->is_coupon_expired($coupon))
        {
            $not_valid = true;
            throw new Exception(__('This coupon has expired.', 'wt-smart-coupons-for-woocommerce-pro'), 109);         
        }else
        {
            $not_valid = false;
        }

        return $not_valid;
    }

    /**
     *  Check coupon expiry
     * 
     *  @since  2.1.1
     *  @param  WC_Coupon   $coupon     Coupon object
     *  @return bool        True when coupon expired
     */
    public function is_coupon_expired($coupon)
    {
        $expiry_date = Wt_Smart_Coupon_Public::get_coupon_expires($coupon); //offset timestamp

        return (!is_null($expiry_date) &&  !apply_filters('wt_sc_validate_coupon_expiry_date', (current_time('timestamp') < $expiry_date), $expiry_date, $coupon));
    }

    /**
     *  Convert the time value to two digit number when value is not empty. 
     *  Adding a 0 to the left if number is lesser than 10
     * 
     *  @since  2.1.2
     *  @param  string|int   $time     Time value
     *  @return string       Time value
     */
    public function format_start_expiry_time( $time ) {
        return ( "" !== $time ? str_pad( $time, 2, '0', STR_PAD_LEFT ) : '' );
    }


    /**
     *  Check coupon start date
     * 
     *  @since  2.4.0
     *  @param  WC_Coupon   $coupon     Coupon object
     *  @return bool        True when coupon started
     */
    public function is_coupon_started( $coupon ) {
        $start_date = Wt_Smart_Coupon_Public::get_coupon_starts( $coupon ); // Offset timestamp.

        if ( ! is_null( $start_date ) ) {
            return apply_filters( 'wt_smartcoupon_validate_start_date', ( current_time('timestamp') >= $start_date ), $start_date, $coupon );
        } else {
            return true;
        }
    }


    /**
     *  Check coupon available today
     * 
     *  @since  2.4.0
     *  @param  int         $coupon_id     Coupon id
     *  @return bool        True when coupon available today
     */
    public function is_coupon_available_today( $coupon_id ) {
        $coupon_available_days = get_post_meta( $coupon_id, '_wt_sc_coupon_on_selected_days', true );
        $coupon_available_days = is_array($coupon_available_days) ? $coupon_available_days : array();

        if ( ! empty( $coupon_available_days ) ) {
            return in_array( strtolower( wp_date('D') ), $coupon_available_days );
        } else {
           return true; 
        }
    }


    /**
     *  This function will process the request from import module and initiate an action hook to trigger the update process.
     *  In the similar way, other import plugins can trigger the action hook and update the coupon time meta data.
     *  Hooked into : wbte_sc_after_coupon_imported
     * 
     * 
     *  @since  2.4.0
     *  @param  int         $post_id            Coupon id
     *  @param  array       $coupon_data        Coupon data
     *  @param  array       $coupon_meta_data   Coupon meta data
     */
    public function process_start_expiry_time_update_on_import( $post_id, $coupon_data, $coupon_meta_data ) {
        
        $actions = array();

        if ( isset( $coupon_meta_data['_wt_coupon_start_date'] ) && "" !== sanitize_text_field( $coupon_meta_data['_wt_coupon_start_date'] )
            && isset( $coupon_meta_data['_wt_sc_coupon_start_time_hour'] ) && "" !== sanitize_text_field( $coupon_meta_data['_wt_sc_coupon_start_time_hour'] ) 
            && isset( $coupon_meta_data['_wt_sc_coupon_start_time_minute'] ) && "" !== sanitize_text_field( $coupon_meta_data['_wt_sc_coupon_start_time_minute'] ) 
        ) { // Start data is available.

            $actions[] = 'start';
        }


        // In some cases the expiry date meta comes like this.
        if ( isset( $coupon_meta_data['date_expires'] ) && "" !== sanitize_text_field( $coupon_meta_data['date_expires'] ) ) {
            $coupon_meta_data['expiry_date'] = $coupon_meta_data['date_expires'];
        }


        if ( isset( $coupon_meta_data['expiry_date'] ) && "" !== sanitize_text_field( $coupon_meta_data['expiry_date'] ) 
            && isset( $coupon_meta_data['_wt_sc_coupon_expiry_time_hour'] ) && "" !== sanitize_text_field( $coupon_meta_data['_wt_sc_coupon_expiry_time_hour'] ) 
            && isset( $coupon_meta_data['_wt_sc_coupon_expiry_time_minute'] ) && "" !== sanitize_text_field( $coupon_meta_data['_wt_sc_coupon_expiry_time_minute'] ) 
        ) { // Expiry data is available.

            $actions[] = 'expiry';
        }


        foreach ( $actions as $action ) {
            /**
             *  Hook to trigger coupon start/expiry time meta update.
             *  Start/expiry must not be empty to perform the update.
             *  
             *  @since  2.4.0
             *  @param  int         $post_id            Coupon id
             *  @param  array       $coupon_meta_data   Coupon meta data
             *  @param  string      $action             Action type. Expected values: `start` for start time, `expiry` for expiry time.
             */
            do_action( 'wbte_sc_update_start_expiry_time_on_import', $post_id, $coupon_meta_data, $action );
        }
    }


    /**
     *  Callback function to proccess expiry/start time request from import processess.
     *  
     *  @since  2.4.0
     *  @param  int         $post_id            Coupon id
     *  @param  array       $coupon_meta_data   Coupon meta data
     *  @param  string      $action             Action type. Expected values: `start` for start time, `expiry` for expiry time.
     */
    public function update_start_expiry_time_on_import( $post_id, $coupon_meta_data, $type ) {
        if ( 'start' === $type ) {
            $this->save_coupon_start_expiry_time( $post_id, '_wt_coupon_start_date', '_wt_sc_coupon_start_time_hour', '_wt_sc_coupon_start_time_minute', '_wt_coupon_start_time', $coupon_meta_data );
        }elseif( 'expiry' === $type ) {
            
            $expiry_date_key = ( isset( $coupon_meta_data['date_expires'] ) && "" !== $coupon_meta_data['date_expires'] ? 'date_expires' : 'expiry_date' );
            $this->save_coupon_start_expiry_time( $post_id, $expiry_date_key, '_wt_sc_coupon_expiry_time_hour', '_wt_sc_coupon_expiry_time_minute', '_wt_coupon_expiry_time', $coupon_meta_data );
        }
    }

    /**
     * Save start and expiry data for BOGO coupon
     * 
     * @since 3.2.0
     * @param int   $post_id    Post id
     * @param array $post_data  Arrat POST data
     */
    public function save_start_expiry_data_for_bogo_coupon( $post_id, $post_data )
    {
        if( ! $post_id || ! is_array( $post_data ) || empty( $post_data ) )
        {
            return;
        }
        
        $update_post = array();

        if ( isset( $post_data['_wt_coupon_start_date'] ) && '' !== $post_data['_wt_coupon_start_date'] ) {
            $start_date = Wt_Smart_Coupon_Security_Helper::sanitize_item( $post_data['_wt_coupon_start_date'] );
            $update_post['_wt_coupon_start_date'] = $start_date;
            $update_post['_wt_coupon_start_date_timestamp'] = Wt_Smart_Coupon_Admin::wt_sc_get_date_prop( $start_date )->getTimestamp();

        } else {
            $update_post['_wt_coupon_start_date'] = '';
            $update_post['_wt_coupon_start_date_timestamp'] = '';
        }

        if ( isset( $post_data['expiry_date'] ) && '' !== $post_data['expiry_date'] ) {
            $expiry_date = Wt_Smart_Coupon_Security_Helper::sanitize_item( $post_data['expiry_date'] );
            
            $update_post['_wbte_sc_bogo_expiry_date'] = $expiry_date;
            $update_post['date_expires'] = Wt_Smart_Coupon_Admin::wt_sc_get_date_prop( $expiry_date )->getTimestamp();

        } else {
            $update_post['_wbte_sc_bogo_expiry_date'] = '';
            $update_post['date_expires'] = '';
        }

        // Adjust start time for PM hours, excluding 12 PM, and set to 0 for 12 AM.
        if (
            isset( $post_data['wbte_sc_bogo_start_meridiem'], $post_data['_wt_sc_coupon_start_time_hour'] ) &&
            ! empty( $post_data['_wt_sc_coupon_start_time_hour'] )
        ) {
            if ( 'PM' === $post_data['wbte_sc_bogo_start_meridiem'] && '12' !== $post_data['_wt_sc_coupon_start_time_hour'] ) {
                $post_data['_wt_sc_coupon_start_time_hour'] += 12;
            } elseif ( 'AM' === $post_data['wbte_sc_bogo_start_meridiem'] && '12' === $post_data['_wt_sc_coupon_start_time_hour'] ) {
                $post_data['_wt_sc_coupon_start_time_hour'] = 0;
            }
        }

        // Adjust expiry time for PM hours, excluding 12 PM, and set to 0 for 12 AM.
        if (
            isset( $post_data['wbte_sc_bogo_expire_meridiem'], $post_data['_wt_sc_coupon_expiry_time_hour'] ) &&
            ! empty( $post_data['_wt_sc_coupon_expiry_time_hour'] )
        ) {
            if ( 'PM' === $post_data['wbte_sc_bogo_expire_meridiem'] && '12' !== $post_data['_wt_sc_coupon_expiry_time_hour'] ) {
                $post_data['_wt_sc_coupon_expiry_time_hour'] += 12;
            } elseif ( 'AM' === $post_data['wbte_sc_bogo_expire_meridiem'] && '12' === $post_data['_wt_sc_coupon_expiry_time_hour'] ) {
                $post_data['_wt_sc_coupon_expiry_time_hour'] = 0;
            }
        }

        foreach ( $update_post as $meta_key => $meta_value ) {
            update_post_meta( $post_id, $meta_key, $meta_value );
        }

        $this->update_start_expiry_time_on_import( $post_id, $post_data, 'start' );
        $this->update_start_expiry_time_on_import( $post_id, $post_data, 'expiry' );
    }
}
Wt_Smart_Coupon_Lifespan::get_instance();