<?php
/**
 * Bulk generate
 *
 * @link       
 * @since 1.3.6     
 *
 * @package  Wt_Smart_Coupon  
 */
if (!defined('ABSPATH')) {
    exit;
}

class Wt_Smart_Coupon_Bulk_Generate_Admin
{
    public $module_base='bulk_generate';
    public $module_id='';
    public static $module_id_static='';
    private static $instance = null;
    public function __construct()
    {
        $this->module_id=Wt_Smart_Coupon::get_module_id($this->module_base);
        self::$module_id_static=$this->module_id;

        add_action('admin_init', array($this, 'generate_bulk_coupon_action'), 10);
        add_filter('wt_sc_admin_menu', array($this, 'add_admin_pages'));
        add_action('woocommerce_email_classes', array($this, 'add_wt_smart_coupon_emails'), 11, 1);
        add_action('admin_enqueue_scripts', array($this, 'generate_coupon_styles_and_scripts'),11,0);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_thirdparty_dependency_scripts'), 10000, 0);
    }

    /**
     * Get Instance
     * @since 1.3.6
     */
    public static function get_instance()
    {
        if(self::$instance==null)
        {
            self::$instance=new Wt_Smart_Coupon_Bulk_Generate_Admin();
        }
        return self::$instance;
    }

    /**
     *  @since 1.3.6
     *  Admin page
     */
    public function add_admin_pages($menus)
    {
        $menus[]=array(
            'submenu',
            WT_SC_PLUGIN_NAME,
            __('Bulk generate','wt-smart-coupons-for-woocommerce-pro'),
            __('Bulk generate','wt-smart-coupons-for-woocommerce-pro'),
            'manage_woocommerce',
            $this->module_id,
            array($this, 'bulk_generate_page_content')
        );
        return $menus;
    }


    /**
     * Function to render bulk generate page content
     * @since 1.3.6
     */
    public function bulk_generate_page_content()
    {
        global $post;

        $woocommerce = function_exists('WC') ? WC() : null;

        if(!$woocommerce) //WC is necessary
        {
            return;
        }

        $this->add_bulk_generate_specific_hooks();

        $reference_post_id = get_option('wt_auto_draft_smart_coupons');
        if( !$reference_post_id ) {
            $args = array(
                'post_status' => 'auto-draft',
                'post_type' => 'shop_coupon'
            );
            $reference_post_id = wp_insert_post( $args );
            update_option('wt_auto_draft_smart_coupons',$reference_post_id );
        }

        $post = get_post( $reference_post_id );
        if ( empty( $post ) ) {
            $args = array(
                        'post_status' => 'auto-draft',
                        'post_type' => 'shop_coupon'
                    );
            $reference_post_id = wp_insert_post( $args );
            update_option( 'wt_auto_draft_smart_coupons', $reference_post_id );
            $post = get_post( $reference_post_id );
        }

        include plugin_dir_path( __FILE__ ).'views/_page_content.php';
    }

    /**
     * Include Woocommcerce Styles on bulk import page.
     * @since 1.3.6
     */
    public function generate_coupon_styles_and_scripts()
    {
        global $pagenow, $wp_scripts;

        $woocommerce = function_exists('WC') ? WC() : null;

        if(!$woocommerce || empty($pagenow) || 'admin.php'!==$pagenow || !isset($_GET['page']) ||  $_GET['page']!=$this->module_id)
        {
            return;
        }
        
        $suffix         = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
        $jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.9.2';

        $locale  = localeconv();
        $decimal = isset( $locale['decimal_point'] ) ? $locale['decimal_point'] : '.';

        wp_enqueue_style( 'woocommerce_admin_menu_styles', $woocommerce->plugin_url() . '/assets/css/menu.css', array(), $woocommerce->version );
        wp_enqueue_style( 'woocommerce_admin_styles', $woocommerce->plugin_url() . '/assets/css/admin.css', array(), $woocommerce->version );
        wp_enqueue_style( 'jquery-ui-style', '//code.jquery.com/ui/' . $jquery_version . '/themes/smoothness/jquery-ui.css', array(), $jquery_version );

        $woocommerce_admin_params = array(
            'i18n_decimal_error'                => sprintf( __( 'Please enter in decimal (%s) format without thousand separators.', 'woocommerce' ), $decimal ),
            'i18n_mon_decimal_error'            => sprintf( __( 'Please enter in monetary decimal (%s) format without thousand separators and currency symbols.', 'woocommerce' ), wc_get_price_decimal_separator() ),
            'i18n_country_iso_error'            => __( 'Please enter in country code with two capital letters.', 'woocommerce' ),
            'i18_sale_less_than_regular_error'  => __( 'Please enter in a value less than the regular price.', 'woocommerce' ),
            'decimal_point'                     => $decimal,
            'mon_decimal_point'                 => wc_get_price_decimal_separator(),
            'strings'                           => array(
                'import_products' => __( 'Import', 'woocommerce' ),
                'export_products' => __( 'Export', 'woocommerce' ),
            ),
            'nonces'                            => array(
                'gateway_toggle' => wp_create_nonce( 'woocommerce-toggle-payment-gateway-enabled' ),
            ),
            'urls'                              => array(
                'import_products' => current_user_can( 'import' ) ? esc_url_raw( admin_url( 'edit.php?post_type=product&page=product_importer' ) ) : null,
                'export_products' => current_user_can( 'export' ) ? esc_url_raw( admin_url( 'edit.php?post_type=product&page=product_exporter' ) ) : null,
            ),
        );

        $woocommerce_admin_meta_boxes_params = array(
            'remove_item_notice'            => __( 'Are you sure you want to remove the selected items? If you have previously reduced this item\'s stock, or this order was submitted by a customer, you will need to manually restore the item\'s stock.', 'woocommerce' ),
            'i18n_select_items'             => __( 'Please select some items.', 'woocommerce' ),
            'i18n_do_refund'                => __( 'Are you sure you wish to process this refund? This action cannot be undone.', 'woocommerce' ),
            'i18n_delete_refund'            => __( 'Are you sure you wish to delete this refund? This action cannot be undone.', 'woocommerce' ),
            'i18n_delete_tax'               => __( 'Are you sure you wish to delete this tax column? This action cannot be undone.', 'woocommerce' ),
            'remove_item_meta'              => __( 'Remove this item meta?', 'woocommerce' ),
            'remove_attribute'              => __( 'Remove this attribute?', 'woocommerce' ),
            'name_label'                    => __( 'Name', 'woocommerce' ),
            'remove_label'                  => __( 'Remove', 'woocommerce' ),
            'click_to_toggle'               => __( 'Click to toggle', 'woocommerce' ),
            'values_label'                  => __( 'Value(s)', 'woocommerce' ),
            'text_attribute_tip'            => __( 'Enter some text, or some attributes by pipe (|) separating values.', 'woocommerce' ),
            'visible_label'                 => __( 'Visible on the product page', 'woocommerce' ),
            'used_for_variations_label'     => __( 'Used for variations', 'woocommerce' ),
            'new_attribute_prompt'          => __( 'Enter a name for the new attribute term:', 'woocommerce' ),
            'calc_totals'                   => __( 'Calculate totals based on order items, discounts, and shipping?', 'woocommerce' ),
            'calc_line_taxes'               => __( 'Calculate line taxes? This will calculate taxes based on the customers country. If no billing/shipping is set it will use the store base country.', 'woocommerce' ),
            'copy_billing'                  => __( 'Copy billing information to shipping information? This will remove any currently entered shipping information.', 'woocommerce' ),
            'load_billing'                  => __( 'Load the customer\'s billing information? This will remove any currently entered billing information.', 'woocommerce' ),
            'load_shipping'                 => __( 'Load the customer\'s shipping information? This will remove any currently entered shipping information.', 'woocommerce' ),
            'featured_label'                => __( 'Featured', 'woocommerce' ),
            'prices_include_tax'            => esc_attr( get_option( 'woocommerce_prices_include_tax' ) ),
            'round_at_subtotal'             => esc_attr( get_option( 'woocommerce_tax_round_at_subtotal' ) ),
            'no_customer_selected'          => __( 'No customer selected', 'woocommerce' ),
            'plugin_url'                    => $woocommerce->plugin_url(),
            'ajax_url'                      => admin_url( 'admin-ajax.php' ),
            'order_item_nonce'              => wp_create_nonce( 'order-item' ),
            'add_attribute_nonce'           => wp_create_nonce( 'add-attribute' ),
            'save_attributes_nonce'         => wp_create_nonce( 'save-attributes' ),
            'calc_totals_nonce'             => wp_create_nonce( 'calc-totals' ),
            'get_customer_details_nonce'    => wp_create_nonce( 'get-customer-details' ),
            'search_products_nonce'         => wp_create_nonce( 'search-products' ),
            'grant_access_nonce'            => wp_create_nonce( 'grant-access' ),
            'revoke_access_nonce'           => wp_create_nonce( 'revoke-access' ),
            'add_order_note_nonce'          => wp_create_nonce( 'add-order-note' ),
            'delete_order_note_nonce'       => wp_create_nonce( 'delete-order-note' ),
            'calendar_image'                => $woocommerce->plugin_url().'/assets/images/calendar.png',
            'post_id'                       => '',
            'base_country'                  => $woocommerce->countries->get_base_country(),
            'currency_format_num_decimals'  => wc_get_price_decimals(),
            'currency_format_symbol'        => get_woocommerce_currency_symbol(),
            'currency_format_decimal_sep'   => esc_attr( wc_get_price_decimal_separator() ),
            'currency_format_thousand_sep'  => esc_attr( wc_get_price_decimal_separator() ),
            'currency_format'               => esc_attr( str_replace( array( '%1$s', '%2$s' ), array( '%s', '%v' ), get_woocommerce_price_format() ) ), // For accounting JS
            'rounding_precision'            => WC_ROUNDING_PRECISION,
            'tax_rounding_mode'             => WC_TAX_ROUNDING_MODE,
            'product_types'                 => array_map( 'sanitize_title', get_terms( 'product_type', array( 'hide_empty' => false, 'fields' => 'names' ) ) ),
            'i18n_download_permission_fail' => __( 'Could not grant access - the user may already have permission for this file or billing email is not set. Ensure the billing email is set, and the order has been saved.', 'woocommerce' ),
            'i18n_permission_revoke'        => __( 'Are you sure you want to revoke access to this download?', 'woocommerce' ),
            'i18n_tax_rate_already_exists'  => __( 'You cannot add the same tax rate twice!', 'woocommerce' ),
            'i18n_product_type_alert'       => __( 'Your product has variations! Before changing the product type, it is a good idea to delete the variations to avoid errors in the stock reports.', 'woocommerce' )
        );
        if ( ! wp_script_is( 'wc-admin-coupon-meta-boxes' ) ) {
            wp_enqueue_script( 'wc-admin-coupon-meta-boxes', $woocommerce->plugin_url() . '/assets/js/admin/meta-boxes-coupon' . $suffix . '.js', array( 'woocommerce_admin', 'wc-enhanced-select', 'wc-admin-meta-boxes' ), $woocommerce->version );
            wp_localize_script(
                'wc-admin-coupon-meta-boxes',
                'woocommerce_admin_meta_boxes_coupon',
                array(
                    'generate_button_text' => esc_html__( 'Generate coupon code', 'woocommerce' ),
                    'characters'           => apply_filters( 'woocommerce_coupon_code_generator_characters', 'ABCDEFGHJKMNPQRSTUVWXYZ23456789' ),
                    'char_length'          => apply_filters( 'woocommerce_coupon_code_generator_character_length', 8 ),
                    'prefix'               => apply_filters( 'woocommerce_coupon_code_generator_prefix', '' ),
                    'suffix'               => apply_filters( 'woocommerce_coupon_code_generator_suffix', '' ),
                )
            );
            wp_localize_script( 'wc-admin-meta-boxes', 'woocommerce_admin_meta_boxes', $woocommerce_admin_meta_boxes_params );
            wp_enqueue_script( 'woocommerce_admin', $woocommerce->plugin_url() . '/assets/js/admin/woocommerce_admin' . $suffix . '.js', array( 'jquery', 'jquery-blockui', 'jquery-ui-sortable', 'jquery-ui-widget', 'jquery-ui-core', 'jquery-tiptip' ), $woocommerce->version );
            wp_localize_script( 'woocommerce_admin', 'woocommerce_admin', $woocommerce_admin_params );
        }
        
    }

    /**
     *  @since 2.0.4 Option for third party plugins to add their scripts
     */
    public function enqueue_thirdparty_dependency_scripts()
    {
        global $pagenow, $wp_scripts;

        if(empty($pagenow) || 'admin.php'!==$pagenow || !isset($_GET['page']) ||  $_GET['page']!=$this->module_id)
        {
            return;
        }


        /**
         *  Hook to inject scripts
         */
        do_action('wt_sc_bulk_generate_enqueue_thirdparty_dependency_scripts');

        /**
         *  @since 2.0.4 Added compatibility for WooCommerce Subscriptions
         * 
         */
        if(class_exists('WC_Subscriptions') && method_exists('WC_Subscriptions', 'is_woocommerce_pre'))
        {
            // Enqueue the metabox script for coupons.
            if(!WC_Subscriptions::is_woocommerce_pre('3.2') &&  !wp_script_is( 'wcs-admin-coupon-meta-boxes'))
            {
                wp_enqueue_script(
                    'wcs-admin-coupon-meta-boxes',
                    plugin_dir_url( WC_Subscriptions::$plugin_file ) . 'assets/js/admin/meta-boxes-coupon.js',
                    array( 'jquery', 'wc-admin-meta-boxes' ),
                    WC_Subscriptions::$version
                );
            }
        }

        /**
         *  @since 2.0.4 Added compatibility for WebToffee Subscriptions
         * 
         */
        if(defined('HF_WC_SUBSCRIPTION_VERSION') && defined('HF_BASE_URL') && defined('HF_SUBSCRIPTION_MAIN_PATH'))
        {
            $script_params = array(
                'BulkTrashWarning' => __("You are about to trash one or more orders which contain a subscription.\n\nTrashing the orders will also trash the subscriptions purchased with these orders.", 'xa-woocommerce-subscription'),
                'TrashWarning' => __('Deleting this order will also delete the subscriptions purchased with the order.', 'xa-woocommerce-subscription'),
                'ajaxURL' => admin_url('admin-ajax.php'),
                    'schedule_errors' => array(
                    'start_date_error'              =>  __('Please enter a start date in the past.', 'xa-woocommerce-subscription') ,
                    'trial_end_error'               =>  __('Please enter a date after the start date.','xa-woocommerce-subscription'),
                    'next_payment'                  =>  __('Please enter a date after the trial end.', 'xa-woocommerce-subscription'),
                    'trial_end_before_next_payment' =>  __('Please enter a date before the next payment.', 'xa-woocommerce-subscription'),
                    'end_date'                      =>  __('Please enter a date after the next payment.', 'xa-woocommerce-subscription'),                
                    'past_date_error'               =>  __('Please enter a date at least one hour into the future.', 'xa-woocommerce-subscription'),                
                ),
            );
            wp_enqueue_script('hf_subscription_admin', HF_BASE_URL.'admin/js/hf-woocommerce-subscription-admin.js', array('jquery'), filemtime(HF_SUBSCRIPTION_MAIN_PATH.'admin/js/hf-woocommerce-subscription-admin.js'));
            wp_enqueue_script('moment-js', HF_BASE_URL . 'admin/js/moment.min.js', array('jquery'), filemtime(HF_SUBSCRIPTION_MAIN_PATH . 'admin/js/moment.min.js'));
            wp_localize_script('hf_subscription_admin', 'HFSubscriptions_OBJ', apply_filters('hf_subscription_admin_script_parameters', $script_params));
        }
    }

    /**
    * WooCommerce coupons options only for bulk generate.
    * @since 1.3.6
    */
    public function add_bulk_generate_specific_hooks()
    {
        add_action('woocommerce_coupon_options', array($this,'bulk_generate_option'),10,2);

    }

    /**
     *  Specific coupon options for bulk generate
     *  @since 1.3.6
     */
    public function bulk_generate_option($coupon_id, $coupon)
    {        
        $coupon_settings=Wt_Smart_Coupon::get_settings();
        include plugin_dir_path( __FILE__ ).'views/_bulk_generate_specific_coupon_options.php';
    }

    /**
     * Add smart coupon email classes into WC_email.
     * @since 1.3.6
     */
    public function add_wt_smart_coupon_emails( $email_classes )
    {
        require_once(WT_SMARTCOUPON_MAIN_PATH.'admin/email/class-wt-smart-coupon-email.php');
        $email_classes['WT_smart_Coupon_Email'] = new WT_smart_Coupon_Email();
        return $email_classes;
    }

    /**
     * Action on generating bulk coupon
     * @since 1.0.0.
     */
    public function generate_bulk_coupon_action()
    {
        if(!isset($_POST['create_bulk_coupon']))
        {
            return;
        }

        if(!Wt_Smart_Coupon_Security_Helper::check_write_access( 'smart_coupons', 'wt_bulk_generate_coupon' ) )
        {
            wp_die(__('You do not have sufficient permission to perform this operation', 'wt-smart-coupons-for-woocommerce-pro'));
        }   

        if(isset( $_POST['_wt_no_of_coupons']) && $_POST['_wt_no_of_coupons'] > 0 )
        {            
            $allowed_email=(isset($_POST['customer_email']) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST['customer_email']) : '');
            if($allowed_email!='')
            {
                $emails=Wt_Smart_Coupon_Security_Helper::sanitize_item(explode(',', $allowed_email), 'email_arr');
                $emails=array_filter($emails);
            }

            $coupon_need_to_generate = Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST['_wt_no_of_coupons'], 'int');
            if(isset($emails) && sizeof($emails) > 0 && sizeof($emails) < $_POST['_wt_no_of_coupons'])
            {
                $coupon_need_to_generate = sizeof($emails) ;
            }

            $coupon_length = Wt_Smart_Coupon::get_option('no_of_characters_for_bulk_generate');
            if(isset($_POST['_wt_coupon_length']))
            {
                $coupon_length_input = Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST['_wt_coupon_length'], 'absint');
                if($coupon_length_input>0)
                {
                    Wt_Smart_Coupon::update_option('no_of_characters_for_bulk_generate', $coupon_length_input);
                    $coupon_length = $coupon_length_input;
                }
            }
            
            $prefix = (isset($_POST['_wt_coupon_prefix']) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST['_wt_coupon_prefix']) : '');
            $suffix = (isset($_POST['_wt_coupon_suffix']) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST['_wt_coupon_suffix']) : '');

            if(isset($_POST['wt_generate_coupon_and']) && ($_POST['wt_generate_coupon_and']=='email_to_recipients' ||  $_POST['wt_generate_coupon_and']=='add_to_store'))
            {
                for($i=0 ; $i<$coupon_need_to_generate; $i++)
                {
                    $coupon_code = Wt_Smart_Coupon_Admin::generate_random_coupon($prefix, $suffix, $coupon_length);                     
                    $coupon_args = array(
                        'post_title'    => $coupon_code,
                        'post_content'  => '',
                        'post_status'   => 'publish',
                        'post_author'   => 1,
                        'post_type'     => 'shop_coupon'
                    );

                    $coupon_id  = wp_insert_post( $coupon_args );
                    update_post_meta($coupon_id, 'wt_bulk_generated_coupon', true);
                    
                    /**
                     *  Filter from URL coupons pro
                     */
                    add_filter('wt_custom_coupon_unique_url', array($this, 'wt_generate_coupon_id'),10,2);
                    
                    $coupon = get_post( $coupon_id );
                    do_action('woocommerce_process_shop_coupon_meta', $coupon_id, $coupon);
                    
                    $coupon_obj = new WC_Coupon( $coupon_id );
                    if( isset( $emails ) && !empty($emails))
                    {
                        $coupon_obj->set_email_restrictions($emails[$i]);
                    }
                    $coupon_obj->save();

                    if($_POST['wt_generate_coupon_and'] == 'email_to_recipients' )
                    {
                        WC()->mailer();
                        do_action('wt_send_coupon_to_customer', $coupon_obj, strtolower($coupon_code), $emails[$i]);
                    }
                }

                wp_safe_redirect(admin_url('edit.php?post_type=shop_coupon'));
                exit();

            }elseif(isset($_POST['wt_generate_coupon_and']) && ($_POST['wt_generate_coupon_and']=='export_as_csv_store'))
            {                   
                $this->export_coupon($coupon_need_to_generate, $coupon_length, $prefix, $suffix);
            }
            wp_safe_redirect(admin_url('admin.php?page='.$this->module_id));
            exit();
        }else
        {
            ?>
            <div class="postbox" id="">
                <div class="error notice">
                    <p><?php _e('Please enter a valid value for Number of Coupons to Generate', 'wt-smart-coupons-for-woocommerce-pro'); ?></p>
                </div>         
            </div>
            <?php
        }
    }

    /**
     * Return coupon code as unique URL for URL coupon
     *
     * @since 1.3.6
     * @param string $unique_url Unique URL from URL options
     * @param string $coupon_code Coupon code 
     * @return string
     */
    public function wt_generate_coupon_id($unique_url, $coupon_code)
    {      
        return $coupon_code;
    }

    /**
     * Prepare CSV header and content data and download the created CSV.
     * 
     * @since 1.0.0
     * @since 2.0.9 Added compatibility for URL coupon By WebToffee. 
     * @param $no_of_coupon_need_to_generate 
     * @param $coupon_length 
     * @param $prefix 
     * @param $suffix 
     */
    public function export_coupon($no_of_coupon_need_to_generate, $coupon_length, $prefix, $suffix) 
    {
        $coupon_posts_headers = array ( 
            'post_title'    => __( 'Coupon Code','wt-smart-coupons-for-woocommerce-pro' ),
            'post_excerpt'  => __( 'Post Excerpt','wt-smart-coupons-for-woocommerce-pro' ),
            'post_status'   => __( 'Post Status','wt-smart-coupons-for-woocommerce-pro' ),
            'post_parent'   => __( 'Post Parent','wt-smart-coupons-for-woocommerce-pro' ),
            'menu_order'    => __( 'Menu Order','wt-smart-coupons-for-woocommerce-pro' ),
            'post_date'     => __( 'Post Date','wt-smart-coupons-for-woocommerce-pro')
        );
        $default_coupon_meta_fields = array(
            '_wt_sc_shipping_methods'           => __( '_wt_sc_shipping_methods','wt-smart-coupons-for-woocommerce-pro' ),
            '_wt_sc_payment_methods'            => __( '_wt_sc_payment_methods','wt-smart-coupons-for-woocommerce-pro' ),
            '_wt_sc_user_roles'                 => __( '_wt_sc_user_roles', 'wt-smart-coupons-for-woocommerce-pro' ),
            '_wt_sc_exclude_user_roles'         => __( '_wt_sc_exclude_user_roles', 'wt-smart-coupons-for-woocommerce-pro' ),
            '_wt_category_condition'            => __( '_wt_category_condition', 'wt-smart-coupons-for-woocommerce-pro' ),
            '_wt_product_condition'             => __( '_wt_product_condition', 'wt-smart-coupons-for-woocommerce-pro' ),
            '_wt_free_product_ids'              => __( '_wt_free_product_ids', 'wt-smart-coupons-for-woocommerce-pro' ),
            '_wt_min_matching_product_qty'      => __( '_wt_min_matching_product_qty','wt-smart-coupons-for-woocommerce-pro' ),
            '_wt_max_matching_product_qty'      => __( '_wt_max_matching_product_qty','wt-smart-coupons-for-woocommerce-pro' ),
            '_wt_min_matching_product_subtotal' => __( '_wt_min_matching_product_subtotal','wt-smart-coupons-for-woocommerce-pro' ),
            '_wt_max_matching_product_subtotal' => __( '_wt_max_matching_product_subtotal','wt-smart-coupons-for-woocommerce-pro' ),
            'discount_type'                     => __( 'discount_type','wt-smart-coupons-for-woocommerce-pro' ),
            'coupon_amount'                     => __( 'coupon_amount','wt-smart-coupons-for-woocommerce-pro' ),
            'individual_use'                    => __( 'individual_use','wt-smart-coupons-for-woocommerce-pro' ),
            'product_ids'                       => __( 'product_ids','wt-smart-coupons-for-woocommerce-pro' ),
            'exclude_product_ids'               => __( 'exclude_product_ids','wt-smart-coupons-for-woocommerce-pro' ),
            '_wt_valid_for_number'              => __( '_wt_valid_for_number','wt-smart-coupons-for-woocommerce-pro' ),
            'minimum_amount'                    => __( 'minimum_amount','wt-smart-coupons-for-woocommerce-pro' ),
            'maximum_amount'                    => __( 'maximum_amount','wt-smart-coupons-for-woocommerce-pro' ),
            'customer_email'                    => __( 'customer_email','wt-smart-coupons-for-woocommerce-pro' ),
            'usage_limit'                       => __( 'usage_limit','wt-smart-coupons-for-woocommerce-pro' ),
            'limit_usage_to_x_items'            => __( 'limit_usage_to_x_items','wt-smart-coupons-for-woocommerce-pro' ),
            'usage_limit_per_user'              => __( 'usage_limit_per_user','wt-smart-coupons-for-woocommerce-pro' ),
        );

        $coupon_meta_headers  = array();
        if(isset($_POST['customer_email']) && ''!=$_POST['customer_email'])
        {
            $coupon_emails = Wt_Smart_Coupon_Security_Helper::sanitize_item(explode(',', $_POST['customer_email']), 'email_arr');
        }

        foreach($_POST as $key => $value)
        {
            if(    $key === '_wpnonce' 
                || $key === '_wp_http_referer' 
                || $key ===  '_wt_no_of_coupons' 
                || $key === 'wt_generate_coupon_and' 
                || $key === 'woocommerce_meta_nonce' 
                || $key === 'create_bulk_coupon' 
                || $key === '_wt_coupon_prefix' 
                || $key === '_wt_coupon_suffix' ) {
            
                continue;
            }

            $key    = Wt_Smart_Coupon_Security_Helper::sanitize_item($key);
            $value  = Wt_Smart_Coupon_Security_Helper::sanitize_arr($value);
            $value  = (is_array($value) ? implode(',', $value) : $value);


            $coupon_meta_headers[$key]  = $key;
            $coupon_meta_values[$key]   = $value;

        }
        
        $coupon_meta_headers = array_unique(array_merge($default_coupon_meta_fields, $coupon_meta_headers));        
        $coupon_csv_header = array_merge($coupon_posts_headers, $coupon_meta_headers);
        $coupon_csv_data = array();
        
        for($i = 0; $i < $no_of_coupon_need_to_generate; $i++)
        {        
            $coupon_meta_values['post_title'] = Wt_Smart_Coupon_Admin::generate_random_coupon($prefix,$suffix,$coupon_length);
            
            if(isset($coupon_emails) && !empty($coupon_emails))
            {
                $coupon_meta_values['customer_email'] = $coupon_emails[$i];
            }

            
            /**
             *  Compatibility for URL coupon By WebToffee
             *  If customer choosed `Use coupon code as unique URL` option (v1.1.2 or greater) OR added any value (below v1.1.2)
             * 
             *  @since 2.0.9
             */
            if(isset($coupon_meta_values['_wt_coupon_unique_url']) && "" !== trim($coupon_meta_values['_wt_coupon_unique_url']))
            {
                $coupon_meta_values['_wt_coupon_unique_url'] = $coupon_meta_values['post_title'];
            }

           
            $coupon_csv_data[$i] = $coupon_meta_values;
        }

        $file_data = $this->export_coupon_csv($coupon_csv_header, $coupon_csv_data);
        
        if(ob_get_level())
        {
            $levels = ob_get_level();
            for($i=0; $i<$levels; $i++)
            {
                @ob_end_clean();
            }
        }else
        {
            @ob_end_clean();
        }
        nocache_headers();
        header( "X-Robots-Tag: noindex, nofollow", true );
        header( "Content-Type: text/x-csv; charset=UTF-8" );
        header( "Content-Description: File Transfer" );
        header( "Content-Transfer-Encoding: binary" );
        header( "Content-Disposition: attachment; filename=\"" . sanitize_file_name( $file_data['file_name'] ) . "\";" );

        echo $file_data['file_content'];
        exit();
    }


    /**
     * Create CSV and get the .csv file
     * @since 1.0.0
     * @param $coupon_csv_header CSV header.
     * @param $coupon_csv_data CSV data.
     */
    public function export_coupon_csv($coupon_csv_header, $coupon_csv_data)
    {

        $getfield = '';
        foreach( $coupon_csv_header as $key => $value )
        {
            $getfield .= $key . ',';
        }

        $fields = substr_replace($getfield, '', -1);

        $each_field = array_keys( $coupon_csv_header );

        $csv_file_name = 'wt_smart_coupons_' . gmdate('d_m_Y_H_i_s') . ".csv";

        foreach( (array) $coupon_csv_data as $row )
        {
            for($i = 0; $i < count ( $coupon_csv_header ); $i++)
            {
                if($i == 0) $fields .= "\n";

                if( array_key_exists($each_field[$i], $row) ){
                    $row_each_field = $row[$each_field[$i]];
                } else {
                    $row_each_field = '';
                }

                $array = str_replace( array("\n", "\n\r", "\r\n", "\r"), "\t", $row_each_field);

                $array = str_getcsv ( $array , ";" , "\"" , "\\");
                $array = str_getcsv ( $row_each_field , ";", "\"" , "\\");

                $str = ( $array && is_array( $array ) ) ? implode( ', ', $array ) : '';
                $fields .= '"'. $str . '",';
            }
            $fields = substr_replace($fields, '', -1);
        }

        $upload_dir = wp_upload_dir();

        $file_data = array();
        $file_data['wp_upload_dir'] = $upload_dir['path'] . '/';
        $file_data['file_name'] = $csv_file_name;
        $file_data['file_content'] = $fields;

        return $file_data;
    }
}
Wt_Smart_Coupon_Bulk_Generate_Admin::get_instance();