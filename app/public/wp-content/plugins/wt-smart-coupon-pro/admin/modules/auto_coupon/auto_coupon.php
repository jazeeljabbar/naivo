<?php
/**
 * Auto coupon admin
 *
 * @link       
 * @since 2.0.1   
 *
 * @package  Wt_Smart_Coupon  
 */
if (!defined('ABSPATH')) {
    exit;
}

// Common module class not found so return.
if ( ! class_exists ( 'Wt_Smart_Coupon_Auto_Coupon_Common' ) ) { 
    return;
}

class Wt_Smart_Coupon_Auto_Coupon_Admin extends Wt_Smart_Coupon_Auto_Coupon_Common
{
    public $module_base='auto_coupon';
    public $module_id='';
    public static $module_id_static='';
    private static $instance = null;
    private static $add_coupon_listing_page_js_css = false;
    
    // Current page coupon list and count. May be filter applied.
    private static $auto_coupon_count = 0;
    private static $auto_coupon_ids = null;

    // Total coupon ids and count. 
    private static $total_auto_coupon_count = 0;
    private static $total_auto_coupon_ids = array();

    public function __construct()
    {
        $this->module_id=Wt_Smart_Coupon::get_module_id($this->module_base);
        self::$module_id_static=$this->module_id;

        add_action('woocommerce_coupon_options', array($this, 'add_auto_coupon_options'), 10, 2);
        add_action('woocommerce_process_shop_coupon_meta', array($this, 'process_shop_coupon_meta'), 11, 2);

        
        /**
         *  Add auto coupon view in coupon listing page
         *  
         *  @since 2.3.0
         */
        add_filter( 'views_edit-shop_coupon', array( $this, 'add_auto_coupon_listing_page_link' ) );
        add_filter( 'posts_pre_query', array( $this, 'prepare_auto_coupon_list' ), 10, 2 );
        add_filter( 'admin_footer', array( $this, 'auto_coupon_listing_page_js_css' ) );
        add_filter( 'manage_edit-shop_coupon_columns', array( $this, 'add_auto_coupon_priority_column_head' ), 10, 1 );
        add_action( 'manage_shop_coupon_posts_custom_column', array( $this, 'add_auto_coupon_priority_column_content' ), 10, 2 );
        add_filter( 'post_class', array( $this, 'add_css_class_to_non_activated_coupon_rows' ), 10, 3 );

        
        /**
         *  Add auto coupon priority adding in plugin activation.
         *  Add a priority reset/add button in debug tab. 
         *  Check and execute the priority reset/add on reset/add button click.
         *  Swap the priority.
         *  Add priority meta data while importing.
         * 
         *  @since 2.3.0
         */
        add_action( 'after_wt_smart_coupon_for_woocommerce_is_activated', array( $this, 'check_and_add_auto_coupon_priority' ) );
        add_action( 'wt_sc_module_settings_debug', array( $this, 'auto_coupon_priority_reset_button_in_debug_tab' ) );
        add_action( 'admin_init', array( $this, 'check_and_update_auto_coupon_priority' ) );
        add_action( 'wp_ajax_wbte_sc_swap_auto_coupon_priority', array( $this, 'swap_auto_coupon_priority' ) );
        add_action( 'wt_sc_import_alter_coupon_meta_data', array( $this, 'add_priority_meta_data_on_import' ), 10, 2 );
    

        /**
         *  Add auto coupon settings field in general settings tab.
         *  Save settings.
         * 
         *  @since 2.3.0
         */
        add_action( 'wbte_sc_after_my_coupons_page_settings', array( $this, 'add_settings_fields' ) );
        add_filter( 'wt_sc_alter_tooltip_data', array( $this, 'register_tooltips' ), 1 );
        add_action( 'wt_sc_intl_after_setting_update', array( $this, 'save_settings' ) );
    }

    /**
     * Get Instance
     */
    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance=new Wt_Smart_Coupon_Auto_Coupon_Admin();
        }
        return self::$instance;
    }

    /**
     * Add coupon meta field for setting AutoCoupon
     * @since 1.1.0
     */
    public function add_auto_coupon_options($coupon_id, $coupon)
    {

        $_wt_make_auto_coupon = get_post_meta($coupon_id, '_wt_make_auto_coupon', true);

        woocommerce_wp_checkbox(
            array(
                'id' => '_wt_make_auto_coupon',
                'label' => __('Apply coupon automatically', 'wt-smart-coupons-for-woocommerce-pro'),
                'desc_tip' => true,
                'description' => __('Enable to apply coupon automatically without applying code. By default, it works for up to 5 recently created coupons.', 'wt-smart-coupons-for-woocommerce-pro'),
                'wrapper_class' => 'wt_auto_coupon',
                'value' => wc_bool_to_string($_wt_make_auto_coupon),
            )
        );
    }

    
    /**
     *  Save Auto coupon meta
     *  
     *  @since 1.1.0
     *  @since 2.3.0    Priority updating added.
     */
    public function process_shop_coupon_meta( $post_id, $post, $meta_data = array() ) {

        $meta_data = empty( $meta_data ) ? $_POST : $meta_data;
        if ( isset( $meta_data['_wt_make_auto_coupon'] ) && "" !== $meta_data['_wt_make_auto_coupon'] ) {
            update_post_meta( $post_id, '_wt_make_auto_coupon', true );

            $priority  = absint( get_post_meta( $post_id, '_wbte_sc_auto_coupon_priority', true ) );
            if ( ! $priority ) {
                update_post_meta( $post_id, '_wbte_sc_auto_coupon_priority', $post_id );
            }
        } else {
            update_post_meta( $post_id, '_wt_make_auto_coupon', false );
            // Here we are not clearing the priority number, this is to retain the position when they re-enables the auto-apply
        }
    }


    /**
     *  Add auto coupons link in coupon listing page
     *  Hooked into: `views_edit-shop_coupon`
     *  
     *  @since  2.3.0
     *  @param  array       $views      Views menu array
     *  @return array       $views      Views menu array
     */
    public function add_auto_coupon_listing_page_link( $views ) {
        
        $this->prepare_all_auto_coupon_ids();  

        // Add auto apply menu to `views` list
        $views['wbte_sc_auto_apply'] = sprintf(
            '<a href="%1$s" %2$s>%3$s <span class="count">(%4$s)</span></a>',
            add_query_arg( array(
                'wbte_sc_auto_apply'    => '1',
                'post_type'             => 'shop_coupon',
                'post_status'           => 'all',
            ), 'edit.php' ),
            ( isset( $_GET['wbte_sc_auto_apply'] ) ? ' class="current" aria-current="page" ' : '' ),
            __( 'Auto-apply coupons', 'wt-smart-coupons-for-woocommerce-pro' ),
            self::$auto_coupon_count
        );

        return $views;
    }


    /**
     *  Check current page is auto coupon listing page.
     *  
     *  @since  2.3.0
     *  @return bool    True when the current page is auto coupon page.
     */
    private function is_auto_coupon_listing_page() {       
        if ( function_exists( 'get_current_screen' ) ) {
            $screen = get_current_screen();
            return ( is_admin() && isset( $_GET['wbte_sc_auto_apply'] ) && ! is_null( $screen ) && 'edit-shop_coupon' === $screen->id );
        } else {
            return false;
        }    
    }


    /**
     *  Prepare auto coupon list for coupon listing page.
     *  Hooked into: `posts_pre_query`
     * 
     *  @since  2.3.0
     *  @param  WP_Post[]|int[]|null    $posts      Array of post/post id or null
     *  @param  WP_Query                $query      WP_Query object
     *  @return WP_Query                $query      WP_Query object
     */
    public function prepare_auto_coupon_list( $posts, $query ) {

        if ( $this->is_auto_coupon_listing_page() && $query->is_main_query() ) {
              
            $this->prepare_all_auto_coupon_ids();
    
            // Prepare the post ids for the current page.
            $page   = max( 1, absint( $query->query_vars['paged'] ) );
            $offset = ( $page - 1 ) * $query->query['posts_per_page'];
            $limit  = $query->query['posts_per_page'];
            $posts  = array_slice( self::$auto_coupon_ids, $offset, $limit );
            
          
            // For pagination.
            $query->found_posts     = self::$auto_coupon_count;
            $query->max_num_pages   = $limit > 0 ? ceil( self::$auto_coupon_count / $limit ) : 0;

            // To add JS.
            self::$add_coupon_listing_page_js_css = true;

            // Prepare total coupon ids and count. This is for validation and priority column listing.
            global $wpdb;
            $lookup_tb = Wt_Smart_Coupon::get_lookup_table_name();
            $is_auto_coupon_value = 1;
            $post_status = 'trash';
            self::$total_auto_coupon_ids    = $wpdb->get_col( $wpdb->prepare( "SELECT coupon_id AS ID FROM {$lookup_tb} WHERE post_status != '%s' AND is_auto_coupon = %d ORDER BY auto_coupon_priority DESC", $post_status, $is_auto_coupon_value ) );
            self::$total_auto_coupon_ids    = is_array( self::$total_auto_coupon_ids ) ? self::$total_auto_coupon_ids : array();
            self::$total_auto_coupon_count  = count( self::$total_auto_coupon_ids );
        }

        return $posts;
    }


    /**
     *  Prepare post ids based on the current filter (If exists)
     * 
     *  @since  2.3.0
     */
    public function prepare_all_auto_coupon_ids() {
        
        if ( ! is_null( self::$auto_coupon_ids ) ) { // Already preared.
            return;
        }

        global $wpdb; 
        $lookup_tb = Wt_Smart_Coupon::get_lookup_table_name();
        $posts_tb = $wpdb->prefix . 'posts';

        // Prepare the filters.
        $filter = array();
        $coupon_type = isset( $_GET['coupon_type'] ) ? sanitize_text_field( wp_unslash( $_GET['coupon_type'] ) ) : '';
        $search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

        if ( $coupon_type ) {
            $filter['discount_type'] = $coupon_type;
        }

        if ( $search ) {
            $filter['search'] = $search;
        }
        
        if ( ! empty( $filter ) ) {

            $sql = "SELECT c.coupon_id AS ID FROM {$lookup_tb} AS c LEFT JOIN {$posts_tb} AS p ON( c.coupon_id = p.ID ) WHERE c.post_status != 'trash' AND c.is_auto_coupon = 1 ";
            $sql_palceholder_val = array();
            
            if ( isset( $filter['discount_type'] ) ) {
                $sql .= " AND c.discount_type = %s ";
                $sql_palceholder_val[] = $filter['discount_type'];
            }

            if ( isset( $filter['search'] ) ) {
                $sql .= " AND p.post_title LIKE %s ";
                $sql_palceholder_val[] = '%' . $wpdb->esc_like( $filter['search'] ) . '%';
            }

            $sql .= " ORDER BY c.auto_coupon_priority DESC";
            self::$auto_coupon_ids = $wpdb->get_col( $wpdb->prepare( $sql, $sql_palceholder_val ) );
            
        } else {
            self::$auto_coupon_ids = $wpdb->get_col( "SELECT coupon_id AS ID FROM {$lookup_tb} WHERE post_status != 'trash' AND is_auto_coupon = 1 ORDER BY auto_coupon_priority DESC" );   
        }

        self::$auto_coupon_ids      = is_array( self::$auto_coupon_ids ) ? self::$auto_coupon_ids : array();
        self::$auto_coupon_count    = count( self::$auto_coupon_ids );
    }


    /**
     *  Add JS for auto coupon listing.
     *  Hooked into: `admin_footer`
     *  
     *  @since 2.3.0
     * 
     */
    public function auto_coupon_listing_page_js_css() {

        if ( self::$add_coupon_listing_page_js_css ) {
            ?>
            <script type="text/javascript">
                var wbte_sc_auto_coupon_swap_prg = false;
                var wbte_sc_auto_coupon_settings_link_text = '<?php esc_html_e( 'Auto-apply coupon settings', 'wt-smart-coupons-for-woocommerce-pro' );?>';
                var wbte_sc_settings_link = '<?php echo esc_url( admin_url( 'admin.php?page=' . WT_SC_PLUGIN_NAME ) );?>';
                var wbte_sc_swaping_msg = '<?php esc_html_e( 'Swaping...', 'wt-smart-coupons-for-woocommerce-pro' );?>';
                var wbte_sc_swap_success_msg = '<?php esc_html_e( 'Success', 'wt-smart-coupons-for-woocommerce-pro' );?>';
                var wbte_sc_swap_error_msg = '<?php esc_html_e( 'Error', 'wt-smart-coupons-for-woocommerce-pro' );?>';

                function wbte_sc_hide_unwanted_columns_in_auto_coupon_listing_page() {
                    if(jQuery('.wp-list-table').length) {
                        let not_hide_columns = ['coupon_code', 'type', 'amount', 'description', 'wbte_sc_auto_coupon_priority'];
                        jQuery('.wp-list-table thead th').each(function() {
                            let id = jQuery(this).attr('id');
                            
                            if(-1 === jQuery.inArray(id, not_hide_columns)) {
                                jQuery(this).hide();
                                jQuery('.wp-list-table tbody td.column-'+id).hide();
                                jQuery('.wp-list-table tfoot th.column-'+id).hide();
                                jQuery('.hide-column-tog[value="'+id+'"]').parent('label').hide();
                            }
                        });

                        jQuery('.wp-list-table .no-items .colspanchange').attr({'colspan': not_hide_columns.length + 1});
                    }

                    if ( ! jQuery('[name="wbte_sc_auto_apply"]').length ) {
                        jQuery('.tablenav.top').append('<input type="hidden" value="1" name="wbte_sc_auto_apply" />');
                    }

                    if ( ! jQuery('.wbte_sc_auto_coupon_settings_link').length ) {
                        jQuery('.tablenav.top .alignleft.actions:last').append('<a href="' + wbte_sc_settings_link + '" class="wbte_sc_auto_coupon_settings_link" target="_blank"> <span class="dashicons dashicons-external"></span> ' + wbte_sc_auto_coupon_settings_link_text + '</a>');
                    }
                }  

                jQuery(document).ready(function(){
                    
                    wbte_sc_hide_unwanted_columns_in_auto_coupon_listing_page();

                    jQuery(document).on('click', '.wbte_sc_auto_coupon_priority_swap_btn', function(){
                        
                        if( wbte_sc_auto_coupon_swap_prg ){
                            return false;
                        }

                        let popup = jQuery('.wbte_sc_auto_coupon_priority_popup');
                        let pos = jQuery(this).offset();
                        let wid = popup.outerWidth();
                        let lef = ( pos.left - wid ) + jQuery(this).outerWidth() + 10;
                        let top = pos.top + 10;
                        if(popup.is(':visible')) {
                            popup.animate({'top':top, 'left':lef});
                        }else{
                            popup.css({'top':top, 'left':lef, 'opacity': 0}).animate({'opacity': 1}).show();
                        }

                        jQuery('.wbte_sc_swap_warn').hide();  
                        jQuery('.wbte_sc_auto_coupon_priority_input').val('');

                        let from = parseInt(jQuery(this).siblings('.wbte_sc_auto_coupon_priority_number').text().trim());
                        let id   = parseInt(jQuery(this).attr('data-id'));
                        jQuery('.wbte_sc_swap_confirm_btn').attr({'data-priority' : from, 'data-id': id});
                    });
                    jQuery('.wbte_sc_swap_cancel_btn').on('click', function(){
                        if(wbte_sc_auto_coupon_swap_prg){
                            return false;
                        }
                        jQuery('.wbte_sc_auto_coupon_priority_popup').hide();
                    });
                    jQuery('.wbte_sc_swap_confirm_btn').on('click', function(){
                        
                        if(wbte_sc_auto_coupon_swap_prg){
                            return false;
                        }

                        /* Priority is not a number. Validation message is already triggered by below function */
                        let vl = parseInt(jQuery('.wbte_sc_auto_coupon_priority_input').val());
                        if ( isNaN( vl ) ) {
                           return false;  
                        }

                        /* Priority is less than or equal to zero */
                        if (0 >= vl) {
                            jQuery('.wbte_sc_swap_warn').html('<?php esc_html_e( 'Minimum value is 1.', 'wt-smart-coupons-for-woocommerce-pro' );?>').show();
                            return false;
                        }

                        /* Exceeds the maximum priority */
                        let max = parseInt(jQuery('.wbte_sc_auto_coupon_priority_input').attr('max'));
                        if ( max < vl ) {
                            jQuery('.wbte_sc_swap_warn').html('<?php echo esc_html( sprintf( __( 'Maximum value is %d', 'wt-smart-coupons-for-woocommerce-pro' ), self::$total_auto_coupon_count ) );?>').show();
                            return false;
                        }

                        /* Current priority not available */
                        let from = parseInt(jQuery(this).attr('data-priority'));
                        let id   = parseInt(jQuery(this).attr('data-id'));
                        if ( isNaN( from ) || isNaN( id ) ) {
                            jQuery('.wbte_sc_swap_warn').html('<?php esc_html_e( 'Something went wrong.', 'wt-smart-coupons-for-woocommerce-pro' );?>').show();
                            return false;  
                        }

                        /* Same priority */
                        if ( vl === from ) {
                            jQuery('.wbte_sc_swap_warn').html('<?php esc_html_e( 'Please enter different priority.', 'wt-smart-coupons-for-woocommerce-pro' );?>').show();
                            return false;
                        }

                        jQuery('.wbte_sc_swap_confirm_btn, .wbte_sc_swap_cancel_btn, .wbte_sc_auto_coupon_priority_swap_btn').css({'opacity': .5});
                        wbte_sc_auto_coupon_swap_prg = true;
                        var wbte_sc_prg_elm = wt_sc_notify_msg.progress( wbte_sc_swaping_msg );

                        /* Do the swap */
                        jQuery.ajax({
                            url: WTSmartCouponAdminOBJ.ajaxurl,
                            type: 'POST',
                            dataType: 'json',
                            data: {action: 'wbte_sc_swap_auto_coupon_priority', wbte_sc_swap_from: from, wbte_sc_swap_from_id: id, wbte_sc_swap_to: vl, _wpnonce: WTSmartCouponAdminOBJ.nonce},
                            success:function(data){
                                wbte_sc_auto_coupon_swap_prg = false;
                                jQuery('.wbte_sc_swap_confirm_btn, .wbte_sc_swap_cancel_btn, .wbte_sc_auto_coupon_priority_swap_btn').css({'opacity': 1});
                                
                                /* Refresh the page */
                                if ( data.status ) {
                                    
                                    jQuery('.wbte_sc_auto_coupon_priority_popup').hide();
                                    wt_block_node(jQuery('.wp-list-table'));
                                    
                                    jQuery.ajax({
                                        success:function(data){
                                            let temp_elm = jQuery('<div>').html(data);
                                            if ( temp_elm.find('.wp-list-table').length ) {
                                                /* Update the new HTML */
                                                jQuery('.wp-list-table').html(temp_elm.find('.wp-list-table').html());
                                                temp_elm.remove();                                            
                                                wbte_sc_hide_unwanted_columns_in_auto_coupon_listing_page();
                                                wt_unblock_node(jQuery('.wp-list-table'));

                                                wt_sc_notify_msg.progress_complete( wbte_sc_prg_elm, wbte_sc_swap_success_msg );

                                            }else{
                                                wt_sc_notify_msg.progress_error( wbte_sc_prg_elm, wbte_sc_swap_error_msg, false );
                                                window.location.reload(); 
                                            }
                                        },
                                        error:function() { /* Unable to do ajax refresh, so reload */
                                            wt_sc_notify_msg.progress_error( wbte_sc_prg_elm, wbte_sc_swap_error_msg, false );
                                            window.location.reload();
                                        }
                                    });
                                }
                            },
                            error:function(){
                                wbte_sc_auto_coupon_swap_prg = false;
                                jQuery('.wbte_sc_swap_confirm_btn, .wbte_sc_swap_cancel_btn, .wbte_sc_auto_coupon_priority_swap_btn').css({'opacity': 1});
                                wt_sc_notify_msg.progress_error( wbte_sc_prg_elm, wbte_sc_swap_error_msg, false );
                            }
                        });
                    });

                    jQuery('.wbte_sc_auto_coupon_priority_input').on('input', function(){
                        let vl = parseInt(jQuery(this).val());
                        if ( isNaN( vl ) ) {
                            jQuery('.wbte_sc_swap_warn').html('<?php esc_html_e( 'Please enter valid number', 'wt-smart-coupons-for-woocommerce-pro' );?>').show();
                        }else{
                            jQuery('.wbte_sc_swap_warn').hide();
                        }
                    });
                });
            </script>
            <style type="text/css">
                .wbte_sc_auto_coupon_settings_link{ text-decoration:none; display:inline-block; line-height:20px; margin-top:5px;}
                #the-list tr.type-shop_coupon.status-draft, #the-list tr.type-shop_coupon.wbte_sc_coupon_not_activated{ opacity:.5; }
                #the-list tr.type-shop_coupon.status-publish:not(.wbte_sc_coupon_not_activated, .wbte_sc_coupon_not_able_to_apply) td.column-coupon_code .row-title:after{ content:"<?php esc_html_e( 'Active', 'wt-smart-coupons-for-woocommerce-pro' );?>"; position:absolute; background:#26a243; color:#fff; font-size:10px; font-weight:400; padding:1px 5px; margin-left:5px; border-radius:5px; }
                .wbte_sc_auto_coupon_priority_number{ float:left; height:30px; box-sizing:border-box; padding:2px 5px; margin-right:10px; } 
                .wbte_sc_auto_coupon_priority_swap_btn{ float:left; width:30px; height:30px; background: url('<?php echo esc_url( plugin_dir_url( __FILE__ ) ); ?>assets/images/swap_icon.svg') no-repeat center; cursor:pointer;} 
                .wbte_sc_auto_coupon_priority_swap_btn:hover{ box-shadow:0px 0px 2px 1px #ddd; border-radius:2px; } 
                .wbte_sc_auto_coupon_priority_popup{ background:#fff; width:400px; padding:20px 40px; box-sizing:border-box; height:220px; border-radius:12px; position:absolute; box-shadow: 0px 3px 60px 0px rgba(43, 69, 88, 0.2); top:100px; display:none; }
                .wbte_sc_auto_coupon_priority_popup_hd{ width:100%; height:40px; line-height:40px; font-size:16px; font-weight:500; }
                .wbte_sc_auto_coupon_priority_popup_con{ width:100%; font-size:14px; font-weight:400; padding:10px 0px; }
                .wbte_sc_auto_coupon_priority_input{ width:100px; height:40px; padding:5px; box-sizing:border-box; border-radius:6px; border:solid 1.5px #bdc1c6; margin-top:10px; }
                .wbte_sc_swap_warn{ color:red; display:none; }
                .wbte_sc_auto_coupon_priority_popup_foot{ width:100%; height:50px; margin-top:10px; }
                .wbte_sc_auto_coupon_priority_popup_foot button{ width:calc(50% - 5px); height:40px; border-radius:4px; font-size:14px; cursor:pointer; }
                .wbte_sc_auto_coupon_priority_popup_foot .wbte_sc_swap_cancel_btn{ background:#fff; border:solid 1px #fff; color:#056be7; float:left;}
                .wbte_sc_auto_coupon_priority_popup_foot .wbte_sc_swap_confirm_btn{ background:#3157a6; border:solid 1px #fff; color:#fff; float:right;}
                .wbte_sc_auto_coupon_priority_popup:before { content:""; width:0; height:0; border-left:10px solid transparent; border-right:10px solid transparent; border-bottom:10px solid #fff; position:absolute; top:-10px; right:15px; }
                select#shop_coupon_cat{ display:none; }
            </style>
            <div class="wbte_sc_auto_coupon_priority_popup">
                <div class="wbte_sc_auto_coupon_priority_popup_hd"><?php esc_html_e( 'Swap priority', 'wt-smart-coupons-for-woocommerce-pro' );?></div>
                <div class="wbte_sc_auto_coupon_priority_popup_con">
                    <label><?php esc_html_e( 'Swap coupon with another coupon of the priority', 'wt-smart-coupons-for-woocommerce-pro' ); ?> </label>
                    <input type="number" class="wbte_sc_auto_coupon_priority_input" max="<?php echo esc_attr( absint( self::$total_auto_coupon_count ) );?>" min="1" placeholder="<?php esc_attr_e( 'e.g. 2', 'wt-smart-coupons-for-woocommerce-pro' ); ?>"> <span class="wbte_sc_swap_warn"></span>
                </div>
                <div class="wbte_sc_auto_coupon_priority_popup_foot">
                    <button class="wbte_sc_swap_cancel_btn"><?php esc_html_e( 'Cancel', 'wt-smart-coupons-for-woocommerce-pro' );?></button>
                    <button class="wbte_sc_swap_confirm_btn"><?php esc_html_e( 'Confirm', 'wt-smart-coupons-for-woocommerce-pro' );?></button>
                </div>
                <div class="arrow-up"></div>
            </div>
            <?php
        }
    }


    /**
     *  Add auto coupon priority column head in coupon listing page table
     *  Hooked into: `manage_edit-shop_coupon_columns`
     * 
     *  @since  2.3.0
     *  @param  array    $columns      Table columns
     *  @return array    $columns      Table columns
     */
    public function add_auto_coupon_priority_column_head( $columns ) {

        // Check the current page is auto coupon listing page
        if ( ! $this->is_auto_coupon_listing_page() ) {
            return $columns;
        }

        $out = array();
        foreach( $columns as $column_key => $column_title ) {
            
            $out[ $column_key ] = $column_title;

            // After description column
            if( "description" === $column_key ) {
                $out['wbte_sc_auto_coupon_priority'] = __( 'Priority', 'wt-smart-coupons-for-woocommerce-pro' );
            }        
        } 

        return $out;
    }


    /**
     *  Column content for auto coupon priority column in coupon listing page.
     *  Hooked into: `manage_shop_coupon_posts_custom_column`
     * 
     *  @since  2.3.0
     *  @param  string  $column_name    Column name
     *  @param  int     $post_ID        Post ID
     */
    public function add_auto_coupon_priority_column_content( $column_name, $post_ID ) {
        
        if ( 'wbte_sc_auto_coupon_priority' === $column_name ) { 

            $sl_no = array_search( $post_ID, self::$total_auto_coupon_ids );
            ?>
            <span class="wbte_sc_auto_coupon_priority_number">
                <?php 
                    if ( false !== $sl_no ) {
                        echo esc_html( absint( $sl_no ) + 1 );
                    }
                ?>    
            </span>
            <span class="wbte_sc_auto_coupon_priority_swap_btn" data-id="<?php echo esc_attr( absint( $post_ID ) );?> " title="<?php esc_attr_e( 'Click to swap the priority', 'wt-smart-coupons-for-woocommerce-pro' ) ?>"></span>
            <?php
        }
    }

    
    /**
     *  Check and update priority on plugin activation
     * 
     *  @since 2.3.0
     */
    public function check_and_add_auto_coupon_priority() {
        if ( ! get_option( 'wbte_sc_auto_coupon_priority_added', false ) ) { // Priority not already added
            $this->reset_add_auto_coupon_priority(); // Add priority
            update_option( 'wbte_sc_auto_coupon_priority_added', 1 );
        }
    }


    /**
     *  Add a priority reset button in debug tab
     * 
     *  @since 2.3.0
     */
    public function auto_coupon_priority_reset_button_in_debug_tab() {
        
        $reset_url = wp_nonce_url( admin_url( 'admin.php?page=' . WT_SC_PLUGIN_NAME . '&debug&wbte_sc_reset_auto_coupon_priority' ), 'wt_smart_coupons_admin_nonce' );
        $add_url = wp_nonce_url( admin_url( 'admin.php?page=' . WT_SC_PLUGIN_NAME . '&debug&wbte_sc_add_auto_coupon_priority' ), 'wt_smart_coupons_admin_nonce' );
        ?>
        <div style="margin-top:40px; margin-bottom:50px;">
            <div style="margin-bottom:15px;">
                <label><?php esc_html_e('Reset auto coupon priority', 'wt-smart-coupons-for-woocommerce-pro');?></label> :
                <a class="button button-primary" onclick="return wbte_sc_confirm_auto_coupon_priority_reset('<?php echo esc_url( $reset_url ) ?>')">
                    <?php esc_html_e('Reset now', 'wt-smart-coupons-for-woocommerce-pro');?>
                </a>
            </div>
            <div>
                <label><?php esc_html_e('Set priority to missing auto coupons', 'wt-smart-coupons-for-woocommerce-pro');?></label> :
                <a class="button button-primary" href="<?php echo esc_url($add_url);?>">
                    <?php esc_html_e('Set now', 'wt-smart-coupons-for-woocommerce-pro');?>
                </a>
            </div>
            <script type="text/javascript">
                function wbte_sc_confirm_auto_coupon_priority_reset( url ) {
                    if ( confirm( '<?php esc_html_e( 'Are you sure? All custom priorities will be reset.', 'wt-smart-coupons-for-woocommerce-pro' );?>' ) ) {
                        window.location.href = url + '#wt-sc-debug';
                    }
                }
            </script>
        </div>
        <?php
    }


    /**
     *  Check and verify the current URL, and reset the priority
     * 
     *  @since 2.3.0
     */
    public function check_and_update_auto_coupon_priority() {
        if ( isset( $_GET['wbte_sc_reset_auto_coupon_priority'] ) || isset( $_GET['wbte_sc_add_auto_coupon_priority'] ) ) {
            
            // Nonce verification.
            $nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
            $nonce = ( is_array( $nonce ) ? reset( $nonce ) : $nonce );

            if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wt_smart_coupons_admin_nonce' ) || !class_exists( 'Wt_Smart_Coupon_Security_Helper' ) || !method_exists( 'Wt_Smart_Coupon_Security_Helper', 'check_user_has_capability' ) || !Wt_Smart_Coupon_Security_Helper::check_user_has_capability() ) {
                return;
            }

            $this->reset_add_auto_coupon_priority( isset( $_GET['wbte_sc_reset_auto_coupon_priority'] ) ); // Reset/Add the priority

            esc_html_e( 'Success !!!',  'wt-smart-coupons-for-woocommerce-pro' );
            ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . WT_SC_PLUGIN_NAME ) ); ?>"><?php esc_html_e( 'Goto dashboard',  'wt-smart-coupons-for-woocommerce-pro' ); ?></a>
            <?php
            exit();
        } 
    }


    /**
     *  Reset/add the priority to `post_id`
     *  
     *  @since 2.3.0
     */
    private function reset_add_auto_coupon_priority( $reset = true ) {    
        global $wpdb;
        
        if ( $reset ) {
            $product_data_arr = $wpdb->get_results( "SELECT `post_id` FROM {$wpdb->postmeta} WHERE `meta_key` = '_wt_make_auto_coupon' AND ( `meta_value` = '1' OR `meta_value` = 'yes' ) ");
        } else {
           // List of coupons with priority meta missing
            $product_data_arr = $wpdb->get_results( "SELECT DISTINCT a.`post_id` FROM {$wpdb->postmeta} AS a LEFT JOIN {$wpdb->postmeta} AS b ON (a.post_id = b.post_id AND b.`meta_key` = '_wbte_sc_auto_coupon_priority') WHERE ( a.`meta_key` = '_wt_make_auto_coupon' AND ( a.`meta_value` = '1' OR a.`meta_value` = 'yes' ) ) AND ( b.`meta_value` IS NULL )"); 
        }
        if ( is_array( $product_data_arr ) ) {
            foreach( $product_data_arr as $product_data ) {
                update_post_meta( $product_data->post_id, '_wbte_sc_auto_coupon_priority', $product_data->post_id );
            }
        }
    }


    /**
     *  Swap the auto coupon priority. 
     *  Ajax callback.
     * 
     *  @since 2.3.0
     */
    public function swap_auto_coupon_priority() {
        // Nonce verification.
        $nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
        $nonce = ( is_array( $nonce ) ? reset( $nonce ) : $nonce );

        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wt_smart_coupons_admin_nonce' ) || !class_exists( 'Wt_Smart_Coupon_Security_Helper' ) || !method_exists( 'Wt_Smart_Coupon_Security_Helper', 'check_user_has_capability' ) || !Wt_Smart_Coupon_Security_Helper::check_user_has_capability() ) {
            echo wp_json_encode( array( 'status' => false, 'message' => __( 'Unauthorized !!!',  'wt-smart-coupons-for-woocommerce-pro' ) ) );
            exit();
        }

        $from       = ( isset( $_POST['wbte_sc_swap_from'] ) ? absint( wp_unslash( $_POST['wbte_sc_swap_from'] ) ) : 0 );
        $from_id    = ( isset( $_POST['wbte_sc_swap_from_id'] ) ? absint( wp_unslash( $_POST['wbte_sc_swap_from_id'] ) ) : 0 );
        $to         = ( isset( $_POST['wbte_sc_swap_to'] ) ? absint( wp_unslash( $_POST['wbte_sc_swap_to'] ) ) : 0 );
        
        if ( ! $from || ! $from_id || ! $to ) {
            echo wp_json_encode( array( 'status' => false, 'message' => __( 'Expected values are missing.',  'wt-smart-coupons-for-woocommerce-pro' ) ) );
            exit();
        }

        global $wpdb;
        $lookup_tb  = Wt_Smart_Coupon::get_lookup_table_name();
        $from_prio  = absint( get_post_meta( $from_id, '_wbte_sc_auto_coupon_priority', true ) );
        $to_offset  = max( 0, ( $to - 1 ) ); // Substract one to get the offset, minimum value is 0.
        $to_data    = $wpdb->get_row( $wpdb->prepare( "SELECT coupon_id AS ID, auto_coupon_priority FROM {$lookup_tb} WHERE post_status != 'trash' AND is_auto_coupon = 1 ORDER BY auto_coupon_priority DESC LIMIT %d, 1", $to_offset), ARRAY_A );
        $to_id      = 0;
        $to_prio    = 0;

        // Take priority of `to` item, if `to` item exists.
        if ( $to_data && is_array( $to_data ) && ! empty( $to_data ) ) { 
            $to_id      = isset( $to_data['ID'] ) ? absint( $to_data['ID'] ) : 0;
            $to_prio    = isset( $to_data['auto_coupon_priority'] ) ? absint( $to_data['auto_coupon_priority'] ) : 0;
        }
    
        // Unable to fetch the priorities.
        if ( ! $from_prio || ! $to_prio ) { 
            echo wp_json_encode( array( 'status' => false, 'message' => __( 'Error !!!',  'wt-smart-coupons-for-woocommerce-pro' ) ) );
            exit();  
        }

        // All okay, do the swapping.
        update_post_meta( $to_id, '_wbte_sc_auto_coupon_priority', $from_prio );
        update_post_meta( $from_id, '_wbte_sc_auto_coupon_priority', $to_prio );
        
        echo wp_json_encode( array( 'status' => true, 'message' => __( 'Success !!!',  'wt-smart-coupons-for-woocommerce-pro' ) ) );
        exit();
    }


    /**
     *  Add auto coupon settings field in general settings tab.
     *  Hooked into `wbte_sc_after_my_coupons_page_settings`.
     * 
     *  @since 2.3.0
     */
    public function add_settings_fields() {
        ?>
        <h3 class="wt-sc-form-settings-group-heading">
            <?php esc_html_e('Auto-apply coupons', 'wt-smart-coupons-for-woocommerce-pro'); ?>           
        </h3>
        <div class="wbte_sc_total_auto_coupons" style="background:#f2f3f4; display:inline-block; padding:10px 15px; border-radius:4px;">
            <?php 
            $coupon_count   = $this->get_total_auto_coupon_count();
            $default_limit  = $this->get_default_auto_coupon_limit();

            /* translators: %d: Coupon count. */
            echo esc_html( sprintf( __( 'Total auto-apply coupons: %d', 'wt-smart-coupons-for-woocommerce-pro' ), $coupon_count ) );
            ?>
        </div>
        <table class="wt-sc-form-table">
            <?php
            Wt_Smart_Coupon_Admin::generate_form_field(array(
                array(
                    'label'         =>  __( "Max auto-apply coupons per cart", 'wt-smart-coupons-for-woocommerce-pro' ),
                    'option_name'   =>  "max_auto_coupons_to_apply",
                    'type'          =>  "number",
                    'attr'          =>  'placeholder="' . absint( $default_limit ) . '" min="0"',
                ),
                array(
                    'label'         =>  __( "No of coupons that will be checked for auto apply eligibility", 'wt-smart-coupons-for-woocommerce-pro' ),
                    'option_name'   =>  "max_auto_coupons_to_check",
                    'type'          =>  "number",
                    'attr'          =>  'placeholder="' . absint( $coupon_count ) . '" min="0"',
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
     *   
     */
    public function register_tooltips($tooltip_arr)
    {
        $tooltip_arr[ $this->module_id ] = array(
            'max_auto_coupons_to_apply' => __( 'The cart will apply the selected number of auto apply coupons based on priority', 'wt-smart-coupons-for-woocommerce-pro' ),
            'max_auto_coupons_to_check' => __( 'The chosen quantity of coupons will undergo eligibility verification among the entire pool of auto-apply coupons based on priority. If the limit is set to 100, any coupon with a priority of 101 or lower, will not be considered to apply to the cart, even if eligible', 'wt-smart-coupons-for-woocommerce-pro' ),
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

            // Save the settings.
            Wt_Smart_Coupon::update_settings( $the_options, $this->module_id );
        }
    }

    


    /**
     *  Add a CSS class to the listing table row of non actiavted coupon.
     *  This is to show the coupon was not activated for usage. 
     *  Hooked into: `post_class`
     * 
     *  @since 2.3.0
     *
     *  @param  string[] $classes   An array of post class names.
     *  @param  string[] $css_class An array of additional class names added to the post.
     *  @param  int      $post_id   The post ID.
     *  @return string[] $classes   An array of post class names.
     */
    public function add_css_class_to_non_activated_coupon_rows( $classes, $css_class, $post_ID ) {
        if ( 'shop_coupon' === get_post_type( $post_ID ) ) {          
            $coupon = new WC_Coupon( $post_ID );

            if ( ! Wt_Smart_Coupon_Common::is_activated_coupon( $coupon ) ) {
                $classes[] = 'wbte_sc_coupon_not_activated';
            }

            if ( ( $coupon->get_date_expires() && time() > $coupon->get_date_expires()->getTimestamp() ) // WC default expiry.
                || ( Wt_Smart_Coupon_Admin::module_exists('coupon_lifespan') 
                    && ( Wt_Smart_Coupon_Lifespan::get_instance()->is_coupon_expired( $coupon )  // Expired.
                        || ! Wt_Smart_Coupon_Lifespan::get_instance()->is_coupon_started( $coupon ) // Not started.
                        || ! Wt_Smart_Coupon_Lifespan::get_instance()->is_coupon_available_today( $post_ID ) // Not available today.
                        )
                    )
                || ( $coupon->get_usage_limit() && $coupon->get_usage_limit() <= $coupon->get_usage_count() ) // Usage limit exceeded.
            ) {
                $classes[] = 'wbte_sc_coupon_not_able_to_apply';
            }           
        }

        return $classes;
    }


    /**
     *  Add auto coupon priority value as coupon id while importing.
     * 
     *  @since  2.3.0
     *  @param  array   $coupon_meta_data   Associative array of coupon meta data.
     *  @param  int     $coupon_id          Id of coupon.
     *  @return array   $coupon_meta_data   Associative array of coupon meta data.
     */
    public function add_priority_meta_data_on_import( $coupon_meta_data, $coupon_id ) {
        
        // Check this is an auto coupon.
        if ( isset( $coupon_meta_data['_wt_make_auto_coupon'] ) && "" !== trim( $coupon_meta_data['_wt_make_auto_coupon'] ) ) {
           $coupon_meta_data['_wbte_sc_auto_coupon_priority'] = $coupon_id;
        }

        return $coupon_meta_data;
    }
}
Wt_Smart_Coupon_Auto_Coupon_Admin::get_instance();