<?php
/**
 * Gift coupon admin
 *
 * @link       
 * @since 2.0.1   
 *
 * @package  Wt_Smart_Coupon  
 */
if (!defined('ABSPATH')) {
    exit;
}
if(! class_exists ( 'Wt_Smart_Coupon_Gift_Coupon' ) ) /* common module class not found so return */
{
    return;
}
class Wt_Smart_Coupon_Gift_Coupon_Admin extends Wt_Smart_Coupon_Gift_Coupon
{
    public $module_base='gift_coupon';
    public $module_id='';
    public static $module_id_static='';
    private static $instance = null;

    public function __construct()
    {
        $this->module_id = Wt_Smart_Coupon::get_module_id($this->module_base);
        self::$module_id_static = $this->module_id;

        /* for simple product */
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_coupon_field_for_product'));
        add_action('save_post_product', array($this, 'save_product_coupon_meta_data'), 11, 1);
    
        /* for variable product */
        add_action('woocommerce_product_after_variable_attributes', array($this, 'add_coupon_field_for_product_variations'), 10, 3 );
        add_action('woocommerce_save_product_variation', array($this, 'save_coupon_field_for_product_variations'), 9, 2 );

        /* admin order detail page */
        add_action('add_meta_boxes', array($this, 'add_coupon_details_into_order'));
        add_action('wp_ajax_wt_send_coupon', array($this, 'send_coupons'));

        /**
         *  Alter master coupon status based on settings.
         *  @since 2.0.6
         *  @since 2.3.0  Shows coupon activation status on coupon column.
         */
        add_filter('manage_shop_coupon_posts_custom_column', array($this, 'coupon_list_page_coupon_info'), 11, 2);
        add_action('save_post_shop_coupon', array($this, 'prevent_master_coupon_from_publishing'), 11, 3);
        add_action('admin_enqueue_scripts', array($this, 'alter_publish_button_for_master_coupon'));
        add_action('post_submitbox_misc_actions', array($this, 'coupon_edit_page_master_coupon_info'));

        
        /**
         *  Add column in product listing page to show linked coupons.
         * 
         *  @since 2.2.0
         */
        add_filter( 'manage_edit-product_columns', array( $this, 'add_linked_coupons_column_head' ), 10, 1 );
        add_action( 'manage_product_posts_custom_column', array( $this, 'add_linked_coupons_column_content' ), 10, 2 );
    

        /**
         *  Add gift coupon settings field in general settings tab.
         *  Save settings.
         * 
         *  @since 2.3.0
         */
        add_action( 'wbte_sc_after_my_coupons_page_settings', array( $this, 'add_settings_fields' ) );
        add_filter( 'wt_sc_alter_tooltip_data', array( $this, 'register_tooltips' ), 1 );
        add_action( 'wt_sc_intl_after_setting_update', array( $this, 'save_settings' ) );

        /**
         *  Storing gift coupons in options after activating.
         * 
         *  @since 2.4.0
         */
        add_action( 'after_wt_smart_coupon_for_woocommerce_is_activated', array( $this, 'store_gift_coupons_in_options' ) );
    }

    /**
     * Get Instance
     */
    public static function get_instance()
    {
        if(self::$instance==null)
        {
            self::$instance=new Wt_Smart_Coupon_Gift_Coupon_Admin();
        }
        return self::$instance;
    }

    /**
     * Add new field into Product meta
     * @since 1.1.0
     */
    public function add_coupon_field_for_product()
    {
        global $post;
        $all_discount_types = wc_get_coupon_types();
        $coupon_ids = get_post_meta($post->ID, '_wt_product_coupon', true );
        $coupon_ids = array_filter( explode( ',', $coupon_ids ) ) ;
        ?>
        <div class="options_group">
            <p class="form-field" id="sc-field">
                <label for="_wt_product_coupon"><?php echo esc_html__( 'Gift a coupon(s)', 'wt-smart-coupons-for-woocommerce-pro'); ?></label>
                <select class="wt-coupon-search" style="width:50%;" multiple="multiple" id="_wt_product_coupon" name="_wt_product_coupon[]" data-placeholder="<?php echo esc_attr__('Search for a coupon...', 'wt-smart-coupons-for-woocommerce-pro' ); ?>" data-action="wt_json_search_coupons" data-security="<?php echo esc_attr(wp_create_nonce('search-coupons')); ?>">
                    <?php
                    if(!empty($coupon_ids))
                    {
                        foreach($coupon_ids as $coupon_id)
                        {
                            $coupon_title = get_the_title( $coupon_id );

                            $discount_type = get_post_meta( $coupon_id, 'discount_type', true ); // Took value directly from post meta, Because WC returns `fixed_cart` for non published coupons.

                            if(!empty($discount_type))
                            {
                                $discount_type=sprintf(__(' ( %1$s: %2$s )', 'wt-smart-coupons-for-woocommerce-pro'), __( 'Type', 'wt-smart-coupons-for-woocommerce-pro'), $all_discount_types[$discount_type]);

                                if( 'wbte_sc_bogo' === get_post_meta( $coupon_id, 'discount_type', true ) )
                                {
                                    $coupon_title = get_post_meta( $coupon_id, 'wbte_sc_bogo_coupon_name', true );

                                    $discount_type = wp_kses_post( ' ( ' . __( 'Type', 'wt-smart-coupons-for-woocommerce-pro' ) . ': ' . __( 'BOGO', 'wt-smart-coupons-for-woocommerce-pro' ) . __( ', ID', 'wt-smart-coupons-for-woocommerce-pro' ) . ': ' . $coupon_id . ' )' );
                                }
                            }
                            if($coupon_title && $discount_type)
                            {
                                echo '<option value="' . esc_attr( $coupon_id ) . '"' . selected( true, true, false ) . '>' . esc_html( $coupon_title . $discount_type ) . '</option>';
                            }
                        }
                    }
                    ?>
                </select>
                <?php echo wc_help_tip(__('To gift a coupon upon product purchase associate here', 'wt-smart-coupons-for-woocommerce-pro') ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Save product meta data
     * 
     * @since 1.1.0
     * @since 2.0.6 New post meta added to selected coupons to identify its a master coupon
     */
    public function save_product_coupon_meta_data( $post_id )
    {
        if(!$post_id)
        {
            return;
        }

        $coupon_attached = (isset($_REQUEST['_wt_product_coupon']) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_REQUEST['_wt_product_coupon'], 'int_arr') : array()); 

        if(is_array($coupon_attached))
        {
            $coupon_attached = implode(',', $coupon_attached);
        }

        /**
         *  Update as master coupon
         *  @since 2.0.6
         */
        $this->set_as_master_coupon($coupon_attached, $post_id);


        update_post_meta($post_id, '_wt_product_coupon', $coupon_attached);
    }

    /**
     * Add coupon fields for variations
     * @since 1.2.4
     */
    public function add_coupon_field_for_product_variations( $loop, $variation_data, $variation )
    {
        $all_discount_types = wc_get_coupon_types();
        $coupon_ids = get_post_meta($variation->ID, '_wt_product_coupon_for_variation', true );
        if($coupon_ids)
        {
            $coupon_ids = array_filter(explode( ',', $coupon_ids)) ;
        }else{
            $coupon_ids=array();
        }
        ?>  
        <p class="form-field" id="sc-field">
            <label for="_wt_product_coupon_variation"><?php echo esc_html__( 'Gift a coupon(s)', 'wt-smart-coupons-for-woocommerce-pro'); ?></label>
            <select class="wt-coupon-search" style="width: 50%;" multiple="multiple" id="_wt_product_coupon_variation<?php echo '[' .esc_attr($loop).']' ?>" name="_wt_product_coupon_variation<?php echo '[' .esc_attr($loop). ']' ?>[]" data-placeholder="<?php echo esc_attr__( 'Search for a coupon...', 'wt-smart-coupons-for-woocommerce-pro' ); ?>" data-action="wt_json_search_coupons" data-security="<?php echo esc_attr( wp_create_nonce( 'search-coupons' ) ); ?>" >
                <?php
                if (!empty($coupon_ids))
                {
                    foreach ( $coupon_ids as $coupon_id )
                    {
                        $coupon_title = get_the_title( $coupon_id );

                        $coupon = new WC_Coupon( $coupon_title );

                        $discount_type = $coupon->get_discount_type();

                        if ( ! empty( $discount_type ) ) {
                            $discount_type = sprintf( __( ' ( %1$s: %2$s )', 'wt-smart-coupons-for-woocommerce-pro' ), __( 'Type', 'wt-smart-coupons-for-woocommerce-pro' ), $all_discount_types[ $discount_type ] );

                            if( 'wbte_sc_bogo' === get_post_meta( $coupon_id, 'discount_type', true ) )
                                {
                                    $coupon_title = get_post_meta( $coupon_id, 'wbte_sc_bogo_coupon_name', true );

                                    $discount_type = wp_kses_post( ' ( ' . __( 'Type', 'wt-smart-coupons-for-woocommerce-pro' ) . ': ' . __( 'BOGO', 'wt-smart-coupons-for-woocommerce-pro' ) . __( ', ID', 'wt-smart-coupons-for-woocommerce-pro' ) . ': ' . $coupon_id . ' )' );
                                }
                        }
                        if( $coupon_title && $discount_type ) {
                            echo '<option value="' . esc_attr( $coupon_id ) . '"' . selected( true, true, false ) . '>' . esc_html( $coupon_title . $discount_type ) . '</option>';
                        }
                    }
                }
                ?>
            </select>
            <?php echo wc_help_tip( __('To gift a coupon upon product purchase associate here', 'wt-smart-coupons-for-woocommerce-pro') ); ?>
        </p>
        <?php
    }

    /**
     * Save variation coupon details
     * 
     * @since 1.2.4
     * @since 2.0.6 New post meta added to selected coupons to identify its a master coupon
     * @since 2.0.7 [Bug fix] Unable to empty gift coupon field 
     */
    public function save_coupon_field_for_product_variations($variation_id, $i)
    {   
        $coupon_attached = '';
        
        if(isset($_POST['_wt_product_coupon_variation'][$i]))
        {
            $coupon_attached = Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST['_wt_product_coupon_variation'][$i], 'int_arr');
            if(is_array($coupon_attached))
            {
                $coupon_attached = implode(',', $coupon_attached);
            }       
        }

        /**
         *  Update as master coupon
         *  @since 2.0.6
         */
        $this->set_as_master_coupon($coupon_attached, $variation_id, '_wt_product_coupon_for_variation');

        update_post_meta($variation_id, '_wt_product_coupon_for_variation', $coupon_attached ); 

    }

    /**
     * Display coupon sent on order in order item meta
     * @since 1.1.0
     * @since 2.0.8 HPOS Compatibility
     * @since 2.1.0 Added extra checking to avoid order object not exists error.
     */
    public function add_coupon_details_into_order()
    {
        if( ($screen = Wt_Smart_Coupon_Common::is_valid_order_to_show_coupons_metabox('wt_coupons')) )
        {
            add_meta_box('wt-gift-coupons-in-order', __('Coupon sent', 'wt-smart-coupons-for-woocommerce-pro'), array($this, 'coupon_meta_box'), $screen, 'normal');
        }
    }

    /**
     * Coupon metabox content
     * @since 1.1.0
     * @since 2.0.8 HPOS Compatibility
     */
    public function coupon_meta_box()
    {
        global $post, $theorder;

        $order_id = (is_object($post) && property_exists($post, 'ID') ? $post->ID : $theorder->get_id());
        $coupon_attached = Wt_Smart_Coupon_Common::get_order_meta($order_id, 'wt_coupons');
        $coupons = maybe_unserialize($coupon_attached);
        
        if(!empty($coupons))
        {
            $enable_send_coupon = true;        
            echo '<div class="wt_order_coupons">';

            //inject CSS for coupon block
            Wt_Smart_Coupon_Public::print_coupon_default_css();

            echo '<div style="display:flex; width:100%; margin-bottom:20px; gap:20px; flex-wrap:wrap;">';            
            foreach($coupons as $coupon_id)
            {
                $coupon_obj = new WC_Coupon($coupon_id);
                $coupon_data  = Wt_Smart_Coupon_Public::get_coupon_meta_data($coupon_obj);
                                                                                                        
                echo Wt_Smart_Coupon_Public::get_coupon_html($coupon_obj, $coupon_data, "available_coupon", true);
                
                if('draft' === get_post_status($coupon_id)) /* may be revoked coupon */
                {
                    $enable_send_coupon=false; 
                }                         
            }              
            echo '</div>';

            echo '<div class="coupon_meta">';
                echo '<span><b>'.__('From: ', 'wt-smart-coupons-for-woocommerce-pro').'</b>' . Wt_Smart_Coupon_Common::get_order_meta($order_id, 'wt_coupon_send_from') . '</span>';
                echo '<span><b>'.__('To: ', 'wt-smart-coupons-for-woocommerce-pro').'</b>' . Wt_Smart_Coupon_Common::get_order_meta($order_id, 'wt_coupon_send_to') . '</span>';                    
            echo '</div>';

            if($enable_send_coupon)
            {
                echo '<div class="wt-send-coupon">';
                    echo '<button order-id='.esc_attr($order_id).' class="btn wt-btn-resend-coupon button-primary button-large" >'.__('Send coupon','wt-smart-coupons-for-woocommerce-pro').' </button>';
                echo '</div>';
            }             

            echo '</div>';
            ?>
            <script type="text/javascript">
                
                jQuery('document').ready(function() {
                    
                    jQuery(document).on('click', '.wt-btn-resend-coupon', function(e){
                        
                        if(!confirm(WTSmartCouponAdminOBJ.msgs.are_you_sure))
                        {
                            return false;
                        }

                        e.preventDefault();
                        var elm = jQuery(this);
                        var metabox_elm = jQuery('#wt-gift-coupons-in-order');

                        
                        var data = {
                            'action'        : 'wt_send_coupon',
                            '_wt_order_id'  : elm.attr('order-id'),
                            '_wpnonce'      : WTSmartCouponAdminOBJ.nonce
                        };
                        
                        wt_block_node(metabox_elm);

                        jQuery.ajax({
                            type: "POST",
                            url: WTSmartCouponAdminOBJ.ajaxurl,
                            data: data,
                            dataType: 'json',
                            success:function(data)
                            {
                                wt_sc_notify_msg.success(data.msg);
                                wt_unblock_node(metabox_elm);

                                if(data.status)
                                {
                                    /** @since 2.1.0 Reload the page via ajax to show updated details */
                                    wt_block_node(metabox_elm);
                                    jQuery.get('', function(data){
                                        
                                        wt_unblock_node(metabox_elm);

                                        let temp_elm = jQuery('<div>').html(data);
                                        let order_notes_temp_elm = temp_elm.find('#woocommerce-order-notes .inside .order_notes');

                                        if(order_notes_temp_elm.length)
                                        {
                                            jQuery('#woocommerce-order-notes .inside .order_notes').html(order_notes_temp_elm.html());
                                        }

                                    });

                                }else
                                {
                                    wt_sc_notify_msg.error(data.msg);
                                }
                            },
                            error:function()
                            {
                                wt_unblock_node(metabox_elm);
                                wt_sc_notify_msg.error(WTSmartCouponAdminOBJ.msgs.error, false);
                            }

                        });

                    });
                });
            </script>
           <?php 
        }
    }

    /**
     * Ajax action for send coupons from admin
     * @since 1.2.6
     * @since 2.0.8     Added HPOS Compatibility.
     * @since 2.1.0     Adding order note after email.
     * @since 2.3.0     Added a post meta to mark the coupon is send to the customer.
     */
    public function send_coupons()
    {       
        $return = array(
            'status'    =>  false,
            'msg'       => __('Something went wrong', 'wt-smart-coupons-for-woocommerce-pro'),
        );

        if(Wt_Smart_Coupon_Security_Helper::check_write_access('smart_coupons', 'wt_smart_coupons_admin_nonce'))
        {
            $order_id   = Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST['_wt_order_id'], 'int');
            $order      = wc_get_order($order_id);
            $coupons    = Wt_Smart_Coupon_Common::get_order_meta($order_id, 'wt_coupons');
            $coupons    = maybe_unserialize($coupons);
            
            if(!empty($coupons ))
            {
                WC()->mailer();
                do_action('wt_send_gift_coupon_to_customer', $order, $coupons);
                
                /**
                 *  Add order note after sending email.
                 *  
                 *  @since 2.1.0
                 */
                $this->add_order_note_after_coupon_email($order, 'resend');

                if( $this->is_unique_giftcoupon_generate_enabled() ){

                    // Add a post meta to mark the coupon is send to the customer.
                    foreach ( $coupons as $coupon_id ) {
                        update_post_meta( $coupon_id, '_wbte_sc_generated_gift_coupon_activated', 1 );

                        self::trigger_after_coupon_generated_meta_added( $coupon_id, '_wbte_sc_generated_gift_coupon_activated' );
                    } 

                }else{

                    $email = Wt_Smart_Coupon_Common::get_order_meta( $order_id, 'wt_coupon_send_to' );
                    foreach ( $coupons as $coupon_id ) {
                        self::set_coupon_email_restriction( $coupon_id, $email );
                        wp_update_post( array( 'ID' => $coupon_id, 'post_status' => 'publish' ) );
                    }

                } 

                $return = array(
                    'status'    =>  true,
                    'msg'       => __('Coupons send successfully', 'wt-smart-coupons-for-woocommerce-pro'),
                );

                echo json_encode($return);
                die();             
            } 
        }

        echo json_encode($return);
        die();
    }

    /**
     *  Show coupon info in coupon listing section
     *  
     *  @since 2.0.6
     *  @since 2.3.0    Added coupon activation status.
     *                  Method name renamed for common purpose.
     *  @param string   $column     Column name.
     *  @param int      $coupon_id  Coupon ID.
     */
    public function coupon_list_page_coupon_info($column, $coupon_id)
    {
        if('coupon_code'!==$column)
        {
            return;
        }

        $is_master_coupon = get_post_meta($coupon_id, Wt_Smart_Coupon_Admin::$master_coupon_meta_key, true); //this contains module id

        if($is_master_coupon && $is_master_coupon === $this->module_id)
        {
            echo '<br /><i><b>';
            echo __('Gift coupon', 'wt-smart-coupons-for-woocommerce-pro');
            echo '</b></i>';
        }

        if( ! Wt_Smart_Coupon_Common::is_activated_coupon( new WC_Coupon( $coupon_id ) ) ) {
            echo '<br /><i><b>';
            esc_html_e( 'Not activated.', 'wt-smart-coupons-for-woocommerce-pro' );
            echo '</b></i>'; 
        }
    }

    /**
     *  Prevent master coupon from publishing.
     *  @since 2.0.6
     *  @since 2.1.1    [FIX] Master coupon status not changing from draft to publish when selecting yes in 'use master code in Signup coupons'
     */
    public function prevent_master_coupon_from_publishing($post_id, $post, $update)
    {     
        if(get_post_meta($post_id, Wt_Smart_Coupon_Admin::$master_coupon_meta_key, true)) //this is a master coupon
        {
           // make master coupon status to draft only when the coupon is master for gift coupon
           if ( ( 'wt-smart-coupon-for-woo_gift_coupon' === get_post_meta( $post_id, '_wt_sc_master_coupon', true ) ) 
                && empty( get_post_meta( $post_id, 'customer_email' ) ) ) 
           {
                // unhook this function so it doesn't loop infinitely
                remove_action('save_post_shop_coupon', array($this, 'prevent_master_coupon_from_publishing'), 11);
                // update the post, which calls save_post again
                wp_update_post(array('ID' => $post_id, 'post_status' => 'draft'));
            }
        }
    }

    /**
     *  This function will add a post meta to new coupons attached and remove meta from old coupons attached.
     *  And also update post status
     *  
     *  @since 2.0.6
     *  @since 2.0.7    [Big fix] Gift coupons for variations are not properly removing
     */
    private function set_as_master_coupon($new_coupons, $post_id, $post_meta_key = '_wt_product_coupon')
    {
        $new_coupons = explode(",", $new_coupons);
        
        $old_coupons = (string) get_post_meta($post_id, $post_meta_key, true);
        $old_coupons = explode(",", $old_coupons);

        $removed = array_filter(array_diff($old_coupons, $new_coupons));
        $added = array_filter(array_diff($new_coupons, $old_coupons));
        
        if(!empty($removed)) //some coupons are removed.
        {
            foreach($removed as $removed_id)
            {
                delete_post_meta($removed_id, Wt_Smart_Coupon_Admin::$master_coupon_meta_key); //remove master coupon meta
                wp_update_post(array('ID' => $removed_id, 'post_status' => 'publish')); //update post status              
            }
        }

        if(!empty($added)) //some coupons are added.
        {
            foreach($added as $added_id)
            {
                if( $this->is_unique_giftcoupon_generate_enabled() || empty( get_post_meta( $added_id, 'customer_email' ) ) ){
                    wp_update_post( array( 'ID' => $added_id, 'post_status' => 'draft' ) ); //update post status
                }
                
                update_post_meta($added_id, Wt_Smart_Coupon_Admin::$master_coupon_meta_key, $this->module_id); // store a post meta in the coupon to identify its a master coupon
            }
        }

        if( !empty( $removed ) || !empty( $added ) ) {
            $giftcoupon_list = (array) get_option( 'wbte_sc_gift_coupons_list', array() );
            
            if( empty( $new_coupons ) || ( array_key_exists( 0, $new_coupons ) && empty( $new_coupons[0] ) ) ){
                unset( $giftcoupon_list[$post_id] );
            }else{
                $giftcoupon_list[$post_id] = $new_coupons;
            }
            update_option( 'wbte_sc_gift_coupons_list', $giftcoupon_list );
        }
    }

    /**
     *  Set publish button text as `Update` for drafted master coupons.
     *  @since 2.0.6
     */
    public function alter_publish_button_for_master_coupon($hook)
    {
        global $post;

        if(('post-new.php' === $hook  || 'post.php' === $hook) && 'shop_coupon' === $post->post_type && get_post_meta($post->ID, Wt_Smart_Coupon_Admin::$master_coupon_meta_key, true))
        {
             ?>
            <script type="text/javascript">
                document.addEventListener("DOMContentLoaded", function(event) { 
                    document.getElementById('publish').value = '<?php esc_html_e('Update', 'wt-smart-coupons-for-woocommerce-pro');?>';
                });
            </script>
            <?php
        }
    }


    /**
     *  Show master coupon info in coupon edit page
     *  @since 2.0.6
     */
    public function coupon_edit_page_master_coupon_info($post)
    {
        $is_master_coupon = get_post_meta($post->ID, Wt_Smart_Coupon_Admin::$master_coupon_meta_key, true); //this contains module id

        if('shop_coupon' === $post->post_type && $is_master_coupon && $is_master_coupon === $this->module_id)
        {
            echo '<div class="notice notice-info notice-alt inline"><p>';
            echo __('Gift coupon', 'wt-smart-coupons-for-woocommerce-pro');
            echo '</p></div>';
        }
    }

    
    /**
     *  Add linked coupons column head in product listing page table
     * 
     *  @since 2.2.0
     */
    public function add_linked_coupons_column_head( $columns ) {

        $out = array();
        foreach( $columns as $column_key => $column_title ) {
            
            $out[ $column_key ] = $column_title;

            // After product tag column
            if( "product_tag" === $column_key ) {
                $out['wt_sc_linked_coupons'] = __( 'Gift Coupon(s)', 'wt-smart-coupons-for-woocommerce-pro' );
            }        
        }

        return $out;
    }


    /**
     *  Column content for linked coupons in product listing page.
     *  
     *  @since 2.2.0
     */
    public function add_linked_coupons_column_content( $column_name, $post_ID ) {
        
        if ( 'wt_sc_linked_coupons' === $column_name ) {
          
            $coupons = get_post_meta( $post_ID, '_wt_product_coupon', true );
            
            if ( ! empty( $coupons ) ) {
                $coupons = explode( ",", $coupons );
                ?>
                <ul class="wc_coupon_list">
                    <?php
                    foreach ( $coupons as $coupon_id ) {
                       
                        $coupon = new WC_Coupon( $coupon_id );
                        
                        if ( (int) $coupon_id === $coupon->get_id() ) { // Verify the coupon exists
                        
                            $edit_url = add_query_arg(
                                array(
                                    'post'   => $coupon_id,
                                    'action' => 'edit',
                                ),
                                admin_url( 'post.php' )
                            );
                            ?>
                            <li class="code">
                                <a href="<?php echo esc_url( $edit_url ); ?>" target="_blank">
                                    <span style="color:#888;">
                                        <?php echo esc_html( $coupon->get_code() ); ?>
                                    </span>
                                </a>
                            </li>
                            <?php
                        }
                    }
                    ?>
                </ul>
                <?php
            }
        }
    }


    /**
     *  Add gift coupon settings field in general settings tab.
     *  Hooked into `wbte_sc_after_my_coupons_page_settings`.
     * 
     *  @since 2.3.0
     */
    public function add_settings_fields() {
        ?>
        <h3 class="wt-sc-form-settings-group-heading">
            <?php esc_html_e('Coupon on product purchase', 'wt-smart-coupons-for-woocommerce-pro'); ?>
            <p class="wt-sc-form-settings-group-heading-desc"><?php esc_html_e( "Applicable to every coupon linked under the 'Gift a coupon(s)' option on the product edit page.", 'wt-smart-coupons-for-woocommerce-pro' ); ?></p>          
        </h3>
        <table class="wt-sc-form-table">
            <?php
            Wt_Smart_Coupon_Admin::generate_form_field(array(
                array(
                    'label'         =>  __( "Email coupon on order status", 'wt-smart-coupons-for-woocommerce-pro' ),
                    'option_name'   =>  "email_gift_coupon_for_order_status",
                    'type'          =>  "select",
                    'select_fields' =>  Wt_Smart_Coupon_Admin::success_order_statuses(),
                    'help_text'     =>  __( "Coupons will be mailed only for the chosen order status.", 'wt-smart-coupons-for-woocommerce-pro' ),
                ),
                array(
                    'label'         =>  __( "Hide 'Who to send?' box", 'wt-smart-coupons-for-woocommerce-pro' ),
                    'option_name'   =>  "email_gift_coupon_to_buyer",
                    'type'          =>  "checkbox",
                    'checkbox_label'   =>  __( "Email coupon to the buyer", 'wt-smart-coupons-for-woocommerce-pro' ),
                    'field_vl'      =>  'yes',
                ),
                array(
                    'label'         =>  __( "Generate unique coupons", 'wt-smart-coupons-for-woocommerce-pro' ),
                    'option_name'   =>  "generate_unique_gift_coupons",
                    'type'          =>  "checkbox",
                    'checkbox_label'   =>  __( "Generate and send new coupon code to the recipient.", 'wt-smart-coupons-for-woocommerce-pro' ),
                    'field_vl'      =>  'yes',
                ),
            ), $this->module_id );
            ?>
        </table>
        <?php
    }


    /**
     *  Hook the tooltip data to main tooltip array
     *   
     *  @since 2.3.0  
     */
    public function register_tooltips( $tooltip_arr ) {
        $tooltip_arr[ $this->module_id ] = array(
            'email_gift_coupon_for_order_status' => __( "The gift coupon will be emailed based on the selected order status of linked product.", 'wt-smart-coupons-for-woocommerce-pro' ),
        );
        return $tooltip_arr;
    }


    /**
     *  Save module settings.
     *  Hooked into `wt_sc_intl_after_setting_update`
     *  
     *  @since  2.3.0
     */
    public function save_settings() {
        $base = ( isset( $_POST['wt_sc_settings_base'] ) ? sanitize_text_field( wp_unslash( $_POST['wt_sc_settings_base'] ) ) : 'main' );

        if ( 'main' === $base ) {

            // Nonce verification.
            $nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
            $nonce = ( is_array( $nonce ) ? reset( $nonce ) : $nonce );
            
            if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wt_smart_coupons_admin_nonce' ) || !class_exists( 'Wt_Smart_Coupon_Security_Helper' ) || !method_exists( 'Wt_Smart_Coupon_Security_Helper', 'check_user_has_capability' ) || !Wt_Smart_Coupon_Security_Helper::check_user_has_capability() ) {
                return;
            }

            // Take existing settings
            $the_options = Wt_Smart_Coupon::get_settings( $this->module_id );
            
            foreach( $the_options as $key => $value ) {
                if ( isset( $_POST[ $key ] ) ) {
                    $the_options[ $key ] = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
                }
            }

            // For `Hide 'Who to send?' box` checkbox.
            if ( ! isset( $_POST[ 'email_gift_coupon_to_buyer' ] ) ) {
                $the_options['email_gift_coupon_to_buyer'] = 'no';
            }

            // For `Generate unique coupons` checkbox.
            if ( ! isset( $_POST[ 'generate_unique_gift_coupons' ] ) ) {
                $the_options['generate_unique_gift_coupons'] = 'no';
                $this->change_giftcoupons_status( 'draft', 'publish' );
            }else{
                $this->change_giftcoupons_status( 'publish', 'draft' );
            }

            // Save the settings.
            Wt_Smart_Coupon::update_settings( $the_options, $this->module_id );
        }
    }

    /**
     *  Convert every gift coupon status
     * 
     *  @since  2.4.0
     *  @param  string    $old_status     Status from which is converting
     *  @param  string    $new_status     Status to which is converting
     */
    public function change_giftcoupons_status( $old_status, $new_status ){

        $giftcoupon_list = (array) get_option( 'wbte_sc_gift_coupons_list', array() );
        foreach( $giftcoupon_list as $post_id => $coupons_array ) {
            foreach( $coupons_array as $coupon_id ){
                if( $old_status === get_post_status( $coupon_id ) ){
                    wp_update_post( array( 'ID' => $coupon_id, 'post_status' => $new_status ) );
                    $this->prevent_master_coupon_from_publishing( $coupon_id, get_post( $coupon_id ), true );
                }
            }
        }
    }

    /**
     *  Storing gift coupons in options after activating.
     * 
     *  @since 2.4.0
     */
    public static function store_gift_coupons_in_options(){

        if( !get_option( 'wbte_sc_gift_coupons_list' ) ){

            global $wpdb;
            $sql = "SELECT p.ID, pm.meta_value
                    FROM {$wpdb->prefix}posts AS p
                    JOIN {$wpdb->prefix}postmeta AS pm ON p.ID = pm.post_id
                    WHERE p.post_type = %s
                    AND pm.meta_key = %s
                    AND pm.meta_value != ''";
            
            $sql = $wpdb->prepare( $sql, 'product', '_wt_product_coupon' );
            $results = $wpdb->get_results( $sql, ARRAY_A );

            $final_results = array();
            foreach( $results as $result ) {
                $final_results[$result['ID']] = explode( ',', $result['meta_value'] );
            }
            update_option( 'wbte_sc_gift_coupons_list', $final_results );
        }

    }
}
Wt_Smart_Coupon_Gift_Coupon_Admin::get_instance();