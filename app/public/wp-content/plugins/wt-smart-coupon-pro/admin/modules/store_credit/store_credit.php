<?php
/**
 * Store credit
 *
 * @link       
 * @since 2.0.0     
 *
 * @package  Wt_Smart_Coupon  
 */
if (!defined('ABSPATH')) {
    exit;
}
if(! class_exists ( 'Wt_Smart_Coupon_Store_Credit' ) ) /* common module class not found so return */
{
    return;
}
class Wt_Smart_Coupon_Store_Credit_Admin extends Wt_Smart_Coupon_Store_Credit
{
    public $module_base='store_credit';
    public $module_id='';
    public static $module_id_static='';
    private static $instance = null;
    private $add_template_form_msgs=array();
    public function __construct()
    {
        $this->module_id=Wt_Smart_Coupon::get_module_id($this->module_base);
        self::$module_id_static=$this->module_id;

        add_action( 'init', array( $this, 'init' ) );

        add_filter('wt_sc_admin_menu', array($this, 'add_admin_pages'));
        add_filter('wt_sc_alter_tooltip_data',array($this, 'register_tooltips'),1);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'), 10 );

        /* send store credit as email (Ajax hook) */
        add_action('wp_ajax_wt_sc_store_credit_email', array($this, 'send_store_credit_email'));
        
        /* get store credit email preview (Ajax hook) */
        add_action('wp_ajax_wt_sc_store_credit_email_preview', array($this, 'get_store_credit_email_preview'));

        /* Show gift card templates (Ajax hook) */
        add_action('wp_ajax_wt_sc_store_credit_show_giftcard_templates', array($this, 'show_giftcard_templates'));

        /* Action to update which templates to be hidden in the frontend page (Ajax hook) */
        add_action('wp_ajax_wt_sc_store_credit_hide_giftcard_templates', array($this, 'hide_giftcard_templates'));

        /* Delete template (Ajax hook) */
        add_action('wp_ajax_wt_sc_store_credit_delete_giftcard_template', array($this, 'delete_giftcard_template'));

        /* Add new store credit gift card template (Ajax hook) */
        add_action('wp_ajax_wt_sc_store_credit_add_giftcard_template', array($this, 'add_giftcard_template'));            

        /* Meta box for store credit details. In store credit order detail page */
        add_action('add_meta_boxes', array($this, 'add_meta_box'));

        /* Resend store credit email on order detail page. Purchased store credit (Ajax hook) */
        add_action('wp_ajax_wt_resend_store_credit_coupon', array($this, 'resend_store_credit_coupon'));
    
        /* Used store credit details into order detail table */
        add_action('woocommerce_admin_order_totals_after_tax', array($this, 'add_credit_info_to_order_detail_table'));

        /**
         *  Save store credit related coupon meta when saving the coupon.
         *  @since 2.0.5
         */
        add_action('woocommerce_process_shop_coupon_meta', array($this, 'process_shop_coupon_meta'), 10, 2);

        add_action('woocommerce_after_order_itemmeta', array($this, 'display_credit_info_in_order_item_row'), 10, 3);
        
        add_action('woocommerce_admin_order_item_thumbnail', array($this, 'display_template_image_in_order_item_row'), 10, 3);
    
        /**
         *  Help text for coupon restriction section
         *  @since  2.0.6
         */
        add_filter('wt_sc_intl_alter_discount_type_help_arr', array($this, 'add_discount_type_help_text'), 10, 2);

        /**
         *  Set empty default value for store credit product js select field
         *  
         *  @since 2.0.8
         */
        add_filter('wt_sc_intl_default_val_needed_fields',array($this, 'default_val_needed_fields'), 10, 2);

        /**
         *  Add Store credit my account tab settings field in general settings tab.
         * 
         *  @since 2.4.0
         */
        add_action( 'wbte_sc_after_my_coupons_page_settings', array( $this, 'add_settings_fields' ) );

        /**
         *  Save empty values to options for checkbox.
         * 
         *  @since 2.4.0
         */
        add_action( 'wt_sc_intl_after_setting_update', array( $this, 'save_settings' ) );
    }

    /**
     * Initialize the template form msgs.
     * 
     * @since 3.1.0 Moved from __construct to init to avoid issues with translating text before init.
     */
    public function init() {
        $this->add_template_form_msgs = array(
            'please_choose_image'       =>__( 'Please choose an image.', 'wt-smart-coupons-for-woocommerce-pro' ),
            'please_choose_category'    =>__( 'Please choose a category for template.', 'wt-smart-coupons-for-woocommerce-pro' ),
            'please_choose_top_bg'      =>__( 'Please choose a color for Gift card top background.', 'wt-smart-coupons-for-woocommerce-pro' ),
            'please_choose_bottom_bg'   =>__( 'Please choose a color for Gift card bottom background.', 'wt-smart-coupons-for-woocommerce-pro' ),
            'unable_to_load_templates'  =>__( 'Unable to load template list.', 'wt-smart-coupons-for-woocommerce-pro' ),
        );
    }

    /**
     * Get Instance
     * @since 2.0.0
     */
    public static function get_instance()
    {
        if(self::$instance==null)
        {
            self::$instance=new Wt_Smart_Coupon_Store_Credit_Admin();
        }
        return self::$instance;
    }
    
    /**
     *  Admin page
     *  @since 2.0.0
     */
    public function add_admin_pages($menus)
    {
        $menus[]=array(
            'submenu',
            WT_SC_PLUGIN_NAME,
            __('Store credit', 'wt-smart-coupons-for-woocommerce-pro'),
            __('Store credit', 'wt-smart-coupons-for-woocommerce-pro'),
            'manage_woocommerce',
            $this->module_id,
            array($this, 'admin_settings_page'),
        );
        return $menus;
    }

    /**
    *   @since 2.0.0
    *   Hook the tooltip data to main tooltip array
    */
    public function register_tooltips($tooltip_arr)
    {
        include(plugin_dir_path( __FILE__ ).'data/data.tooltip.php');
        $tooltip_arr[$this->module_id]=$arr;
        return $tooltip_arr;
    }

    /**
    *  Admin settings page
    *
    */
    public function admin_settings_page()
    {
        $store_credit_settings=self::get_store_credit_settings();
        $img_path=plugin_dir_url(__FILE__).'assets/images/';
        include(plugin_dir_path( __FILE__ ).'views/admin-settings.php');
    }

    public function enqueue_scripts()
    {
        if(isset($_GET['page']) && $_GET['page']==$this->module_id)
        {
            wp_enqueue_script($this->module_id, plugin_dir_url(__FILE__) . 'assets/js/main.js', array('jquery'), WEBTOFFEE_SMARTCOUPON_VERSION, false);
            
            $msgs=array(
                    'hide_preview'                  =>__("Hide preview", 'wt-smart-coupons-for-woocommerce-pro'),
                    'show_preview'                  =>__('Show preview', 'wt-smart-coupons-for-woocommerce-pro'),
                    'unable_to_load_preview'        =>__('Unable to load the preview.', 'wt-smart-coupons-for-woocommerce-pro'),                   
                    'delete_request_onprogress'     =>__('One delete request on progress.', 'wt-smart-coupons-for-woocommerce-pro'),                   
                );
            $msgs=array_merge($msgs, $this->add_template_form_msgs);

            wp_localize_script($this->module_id, 'wt_sc_store_credit_params', array(
                'msgs'=>$msgs,
            ));
        }
    }

    /**
     * Meta box for store credit details. In store credit order detail page
     * @since 2.0.0
     * @since 2.0.8 Added HPOS Compatibility
     * @since 2.1.0 Added extra checking to avoid order object not exists error.
     */
    public function add_meta_box()
    {
        if( ($screen = Wt_Smart_Coupon_Common::is_valid_order_to_show_coupons_metabox('wt_credit_coupons')) )
        {
            add_meta_box('wt-coupons-in-order', __('Store credit purchased', 'wt-smart-coupons-for-woocommerce-pro'), array($this, 'store_credit_meta_box'), $screen, 'normal');
        }
    }

    /**
     * Store credit metabox HTML
     * @since 2.0.0
     * @since 2.0.8 HPOS Compatibility
     */
    public function store_credit_meta_box()
    {
        global $post, $theorder;

        $order_id = (is_object($post) && property_exists($post, 'ID') ? $post->ID : $theorder->get_id());
        $coupon_attached = Wt_Smart_Coupon_Common::get_order_meta($order_id, 'wt_credit_coupons');
        
        if(!empty($coupon_attached))
        {
            $coupons = maybe_unserialize($coupon_attached);
            
            if(!is_array($coupons))
            {
                return; 
            }

            $order = wc_get_order($order_id);
            $order_items = $order->get_items();

            $wt_store_credit_send_from  = Wt_Smart_Coupon_Common::get_order_meta($order_id, 'wt_credit_coupon_send_from');
            $wt_store_credit_send_to    = Wt_Smart_Coupon_Common::get_order_meta($order_id, 'wt_credit_coupon_send_to');
            $wt_store_credit_message    = Wt_Smart_Coupon_Common::get_order_meta($order_id, 'wt_credit_coupon_send_to_message');
            $wt_store_credit_template   = '';

            include(plugin_dir_path( __FILE__ ).'views/_metabox_html.php');
        }
    }

    /**
     *  @since 2.0.0
     *  Ajax action to delete the template.
     */
    public function delete_giftcard_template()
    {
        $out=array(
            'status'=>false,
            'msg'=>__('Error', 'wt-smart-coupons-for-woocommerce-pro'),
        );
        if(Wt_Smart_Coupon_Security_Helper::check_write_access('smart_coupons', 'wt_smart_coupons_admin_nonce')) 
        {
            $delete_template_id=(isset($_POST['wt_sc_store_credit_delete_template_id']) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST['wt_sc_store_credit_delete_template_id'], 'absint') : 0);
            if($delete_template_id>0)
            {
                $custom_templates=self::get_custom_templates();
                if(array_key_exists($delete_template_id, $custom_templates))
                {
                    unset($custom_templates[$delete_template_id]);
                    Wt_Smart_Coupon::update_option('custom_gift_card_template', $custom_templates, $this->module_id);

                    $visible_count_update_needed=false; /* need to update the visible templates count in templates page */
                    $hidden_templates=Wt_Smart_Coupon::get_option('gift_card_template_to_hide', $this->module_id);
                    
                    if(in_array($delete_template_id, $hidden_templates))
                    {
                        unset($hidden_templates[array_search($delete_template_id, $hidden_templates)]);
                        Wt_Smart_Coupon::update_option('gift_card_template_to_hide', $hidden_templates, $this->module_id);
                    }else
                    {
                        $visible_count_update_needed=true;
                    }
                    $out=array(
                        'status'=>true,
                        'visible_count_update_needed'=>$visible_count_update_needed,
                        'msg'=>__('Template successfully removed. You have to delete the associated image manually from your media library.', 'wt-smart-coupons-for-woocommerce-pro'),
                    );
                }
            }
        } 
        echo json_encode($out);
        exit();
    }

    /**
     *  @since 2.0.0
     *  Ajax action to update which templates to be hidden in the frontend page
     */
    public function hide_giftcard_templates()
    {
        $out=array(
            'status'=>false,
            'msg'=>__('Error', 'wt-smart-coupons-for-woocommerce-pro'),
        );
        if(Wt_Smart_Coupon_Security_Helper::check_write_access('smart_coupons', 'wt_smart_coupons_admin_nonce')) 
        {
            $visible_gift_templates=(isset($_POST['wt_sc_visible_gift_template']) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST['wt_sc_visible_gift_template'], 'text_arr') : '');
            $visible_gift_templates=(!is_array($visible_gift_templates) ? array() : $visible_gift_templates);
            
            $hidden_templates=array();
            $templates=Wt_Smart_Coupon_Store_Credit::get_gift_card_templates();
            foreach($templates as $template_k=>$template_v)
            {
                if(!in_array($template_k, $visible_gift_templates))
                {
                    $hidden_templates[]=$template_k;
                }
            }
            $hidden_templates=array_unique($hidden_templates);
            Wt_Smart_Coupon::update_option('gift_card_template_to_hide', $hidden_templates, $this->module_id);

            $out=array(
                'status'=>true,
                'msg'=>__('Successfully updated', 'wt-smart-coupons-for-woocommerce-pro'),
                'total_visible'=>count($visible_gift_templates),
            );
        }
        echo json_encode($out);
        exit();
    }

    /**
     *  @since 2.0.0
     *  Show store credit gift card templates (Ajax hook)
     */
    public function show_giftcard_templates()
    {
        $templates=Wt_Smart_Coupon_Store_Credit::get_gift_card_templates();
        $templates=(!is_array($templates) ? array() : $templates);
        $template_url=Wt_Smart_Coupon_Store_Credit::get_template_location(); 
        $custom_templates=self::get_custom_templates();
        $delete_btn_tooltip=esc_attr(__("Delete template", "wt-smart-coupons-for-woocommerce-pro"));
        $hidden_template_list=self::get_hidden_templates();
        $total_visible=0;
        ?>
        <p style="float:left; width:100%; clear:both; font-size:14px; margin-bottom:30px; margin-top:0px;"><?php _e("Select one or more templates from the available options and add/delete custom templates for store credit. The templates will appear on the store credit purchase page.", "wt-smart-coupons-for-woocommerce-pro"); ?></p>
        <form method="post" class="wt_sc_store_credit_hide_giftcard_template_form">
        <?php       
        foreach($templates as $template_k=>$template)
        {
            $is_hidden=in_array($template_k, $hidden_template_list);
            if(!$is_hidden){ $total_visible++; }
            $this->get_gift_card_template_html($template_k, $template, $custom_templates, $delete_btn_tooltip, $is_hidden);
        }
        ?>
        </form>
        <div class="wt_sc_giftcard_template_box wt_sc_giftcard_add_new_template_btnbox" style="box-shadow:none; border:dashed 1px #ccc;">
            <span class="dashicons dashicons-plus-alt wt_sc_img_add_btn" title="<?php _e("Add new template", "wt-smart-coupons-for-woocommerce-pro");?>"></span>
            <div class="wt_sc_giftcard_template_bg"></div>
            <img src="<?php echo esc_attr($template_url);?>add_new.jpg">
            <div class="wt_sc_giftcard_template_bg"></div>
        </div>
        <p style="float:left; width:100%; clear:both; font-size:14px;">
            <?php echo sprintf(__("Total %s template(s) visible now.", "wt-smart-coupons-for-woocommerce-pro"), '<span class="wt_sc_giftcard_visible_template_count">'.$total_visible.'</span>'); ?>              
        </p>
        <?php
        exit(); 
    }

    /**
     *  @since 2.0.0
     *  Add new store credit gift card template (Ajax hook)
     */
    public function add_giftcard_template()
    {
        $out=array(
            'status'=>false,
            'msg'=>__('Error', 'wt-smart-coupons-for-woocommerce-pro'),
        );
        if(Wt_Smart_Coupon_Security_Helper::check_write_access('smart_coupons', 'wt_smart_coupons_admin_nonce')) 
        {
            $valid=true;
            $gift_card_template=(isset($_POST['wt_sc_choose_gift_card_template']) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST['wt_sc_choose_gift_card_template'], 'url') : '');
            if($gift_card_template=="")
            {
                $out['msg']=$this->add_template_form_msgs['please_choose_image'];
                $valid=false;
            }

            if($valid)
            {
                $template_category=(isset($_POST['wt_sc_choose_gift_card_template_category']) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST['wt_sc_choose_gift_card_template_category']) : '');
                if($template_category=="")
                {
                    $out['msg']=$this->add_template_form_msgs['please_choose_category'];
                    $valid=false;
                }
            }

            if($valid)
            {
                $template_top_bg=(isset($_POST['wt_sc_choose_gift_card_template_top_bg']) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST['wt_sc_choose_gift_card_template_top_bg']) : '');
                if($template_top_bg=="")
                {
                    $out['msg']=$this->add_template_form_msgs['please_choose_top_bg'];
                    $valid=false;
                }
            }

            if($valid)
            {
                $template_bottom_bg=(isset($_POST['wt_sc_choose_gift_card_template_bottom_bg']) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST['wt_sc_choose_gift_card_template_bottom_bg']) : '');
                if($template_bottom_bg=="")
                {
                    $out['msg']=$this->add_template_form_msgs['please_choose_bottom_bg'];
                    $valid=false;
                }
            }
            if($valid)
            {
                $custom_templates=self::get_custom_templates();

                $template_id=time();
                $template_data=array(
                    'image_url'         => $gift_card_template,
                    'top_bg_color'      => $template_top_bg,
                    'bottom_bg_color'   => $template_bottom_bg,                  
                    'category'          => $template_category,
                );
                $custom_templates[$template_id]=$template_data;
                
                Wt_Smart_Coupon::update_option('custom_gift_card_template', $custom_templates, $this->module_id);

                $template_data['category']=__($template_data['category'], 'wt-smart-coupons-for-woocommerce-pro');

                /* prepare HTML for template */
                ob_start();
                $default_templates=Wt_Smart_Coupon_Store_Credit::get_default_gift_card_templates();
                $delete_btn_tooltip=esc_attr(__("Delete template", "wt-smart-coupons-for-woocommerce-pro"));
                $this->get_gift_card_template_html($template_id, $template_data, $custom_templates, $delete_btn_tooltip, false);
                $gift_card_template_html=ob_get_clean();

                $out=array(
                    'status'=>true,
                    'msg'=>__('Successfully added', 'wt-smart-coupons-for-woocommerce-pro'),
                    'template_id'=>$template_id,
                    'template_data'=>$template_data,
                    'gift_card_template_html'=>$gift_card_template_html,
                );
            }
        }
        echo json_encode($out);
        exit();
    }

    /**
     *  @since 2.0.0
     *  Generate store credit email preview. Ajax hook
     */
    public function get_store_credit_email_preview()
    {
        $wc_emails = WC_Emails::instance();
        $emails = $wc_emails->get_emails();

        $current_email =  $emails['WT_smart_Coupon_Store_Credit_Email'];

        /*The Woo Way to Do Things Need Exception Handling Edge Cases*/
        add_filter('woocommerce_email_recipient_'.$current_email->id, '__return_empty_string');

        $credit_email_args = array(
            'send_to'   => '',
            'coupon_id' => 0,
            'message'   => ''
        );
        $current_email->trigger($credit_email_args);

        $content = $current_email->get_content_html();
        echo apply_filters('woocommerce_mail_content', $current_email->style_inline($content));
        exit();
    }


    /**
     *  @since 2.0.0
     *  Send store credit giftcard email. Ajax hook. (Sending via backend.) 
     */
    public function send_store_credit_email()
    {
        $out=array(
            'status'=>false,
            'msg'=>__('Error', 'wt-smart-coupons-for-woocommerce-pro'),
        );
        if(Wt_Smart_Coupon_Security_Helper::check_write_access('smart_coupons', 'wt_smart_coupons_admin_nonce')) 
        {
            $out['status']=true;

            $email=(isset($_POST['wt_sc_send_email_address']) ? array_filter(explode(',', $_POST['wt_sc_send_email_address'])) : array());
            if(!empty($email))
            {
                $email=array_filter(Wt_Smart_Coupon_Security_Helper::sanitize_item($email, 'email_arr'));
            }

            if(empty($email))
            {
                $out['status']=false;
                $out['msg']=__('Please enter valid email address.', 'wt-smart-coupons-for-woocommerce-pro');
            }

            if($out['status'])
            {
                $credit_amount=(isset($_POST['wt_sc_send_email_amount']) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST['wt_sc_send_email_amount'], 'float') : 0);
                if(0>=$credit_amount)
                {
                    $out['status']=false;
                    $out['msg']=__('Please enter valid amount.', 'wt-smart-coupons-for-woocommerce-pro');
                }
            }

            if($out['status'])
            {
                $message=(isset($_POST['wt_sc_send_email_description']) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST['wt_sc_send_email_description'], 'textarea') : '');
                $caption=(isset($_POST['wt_sc_send_email_caption']) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST['wt_sc_send_email_caption']) : '');
                $coupon_individual_use_only=(isset($_POST['wt_sc_send_email_individual']) ? (boolean) Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST['wt_sc_send_email_individual']) : false);

                $store_credit_settings=self::get_store_credit_settings();
                $prefix = $store_credit_settings['store_credit_coupon_prefix'];
                $suffix = $store_credit_settings['store_credit_coupon_suffix'];
                $coupon_length = $store_credit_settings['store_credit_coupon_length'];
                $is_extended_store_credit_enabled = self::is_extended_store_credit_enabled();

                $coupons_created = 0;
                foreach($email as $email_id)
                {
                    $coupon_data=$this->create_store_credit_coupon($credit_amount, $prefix, $suffix, $coupon_length, $message);                    
                    if(!empty($coupon_data))
                    {
                        $coupon_id  =$coupon_data['coupon_id'];
                        $coupon_obj =$coupon_data['coupon_obj'];

                        $coupon_obj->set_email_restrictions($email_id);
                        if($coupon_individual_use_only)
                        {
                            $coupon_obj->set_individual_use(true);
                        }
                        $coupon_obj->save();

                        do_action('wt_smart_coupon_send_store_credit_coupon_added', $coupon_obj);
                        $coupons_created++;

                        /* email arguments */
                        $credit_email_args = array(
                            'send_to'      => $email_id,
                            'coupon_id'    => $coupon_id,
                            'message'      => $message,
                            'caption'      => $caption,
                        );

                        if($is_extended_store_credit_enabled) /* extra arguments for extended store credit */
                        {
                            $credit_email_args['from_name'] = '';
                            $credit_email_args['template'] = 'general';
                            $credit_email_args['extended'] = true;
                        }

                        $credit_email_args=apply_filters('wt_sc_alter_admin_storecredit_email_args', $credit_email_args, $coupon_obj);
                        
                        /* trigger the mail to send */
                        WC()->mailer();
                        do_action('wt_send_store_credit_coupon_to_customer', $credit_email_args);
                        update_post_meta($coupon_id, '_wt_smart_coupon_credit_activated', true);
                        update_post_meta($coupon_id, '_wt_smart_coupon_initial_credit', $coupon_obj->get_amount());
                    }               
                }
                $msg_plural='coupon'.($coupons_created>1 ? 's' : '');
                $out['msg']=sprintf(__('Success. Total %s '.$msg_plural.' created and mailed.', 'wt-smart-coupons-for-woocommerce-pro'), $coupons_created);
            }
        }
        echo json_encode($out);
        exit();
    }

    /**
     *  Resend/send store credit purchased. (Order edit page)
     */
    public function resend_store_credit_coupon()
    {
        $out=array(
            'status'=>false,
            'msg'=>__('Error', 'wt-smart-coupons-for-woocommerce-pro'),
        );
        if(Wt_Smart_Coupon_Security_Helper::check_write_access('smart_coupons', 'wt_smart_coupons_admin_nonce')) 
        {
            $order_id =(isset($_POST['_wt_order_id']) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST['_wt_order_id'], 'absint') : 0);
            $coupon_id =(isset($_POST['_wt_coupon_id']) ? Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST['_wt_coupon_id'], 'absint') : 0);
            
            if(0===$order_id || 0===$coupon_id)
            {
                $out = array(
                    'status' => false,
                    'msg' => __('Something went wrong', 'wt-smart-coupons-for-woocommerce-pro'),
                );
            }else
            {
                $this->gift_card_email_trigger_type = (isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : 'send'); /** @since 2.1.0 For customized order notes */
                $is_success = $this->do_send_mail($order_id, $coupon_id, true);
            }

            if($is_success)
            {
                $out=array(
                    'status'=>true,
                    'msg'=>__('Success', 'wt-smart-coupons-for-woocommerce-pro'),
                );
            }
        }else
        {
            $out=array(
                'status'=>false,
                'msg'=>__('Authentication issue.', 'wt-smart-coupons-for-woocommerce-pro'),
            );
        }
        echo json_encode($out);
        exit();
    }

    /**
     * Add Store credit used in Admin order item dtails.
     */
    public function add_credit_info_to_order_detail_table($order_id)
    {
        $credit = $this->get_total_credit_used_for_an_order( $order_id );     
        if(0 === $credit){
            return;
        }
        ?>
        <tr>
            <td class="label"><?php echo esc_html__('Store Credit Used:', 'wt-smart-coupons-for-woocommerce-pro'); ?></td>
            <td width="1%"></td>
            <td class="total"><?php echo wc_price( $credit ); // WPCS: XSS ok. ?></td>
        </tr>
        <?php
    }

    /**
     *  Get HTML for gift card template in admin section
     */
    private function get_gift_card_template_html($template_k, $template, $custom_templates, $delete_btn_tooltip, $is_hidden)
    {      
        $img_url=(isset($template['image_url']) ? $template['image_url'] : '');
        $top_bg=(isset($template['top_bg_color']) ? esc_attr('background:'.$template['top_bg_color'].';') : '');
        $bottom_bg=(isset($template['bottom_bg_color']) ? esc_attr('background:'.$template['bottom_bg_color'].';') : '');
        $category=(isset($template['category']) ? $template['category'] : '');
        $is_custom=isset($custom_templates[$template_k]);
          
        include(plugin_dir_path( __FILE__ ).'views/_gift_card_template.php');
    }

    /**
     *  Save store credit related coupon meta when saving the coupon.
     *  @since 2.0.5
     */
    public function process_shop_coupon_meta($post_id, $post)
    {   
        if( !class_exists( 'Wt_Smart_Coupon_Security_Helper' ) || !method_exists( 'Wt_Smart_Coupon_Security_Helper', 'check_user_has_capability' ) || !Wt_Smart_Coupon_Security_Helper::check_user_has_capability() ) 
        {
            wp_die(__('You do not have sufficient permission to perform this operation', 'wt-smart-coupons-for-woocommerce-pro'));
        }

        $coupon = new WC_Coupon($post_id);

        if($coupon && $coupon->is_type('store_credit') && !get_post_meta($post_id, 'wt_auto_generated_store_credit_coupon', true)) //non autogenerated store credit
        {
            update_post_meta($post_id, '_wt_smart_coupon_credit_activated', true);
        }
    }
    
    public function display_credit_info_in_order_item_row($item_id, $item, $product)
    {
        $template_data = $item->get_meta('wt_credit_coupon_template_details');
        
        if($template_data && is_array($template_data))
        {
            foreach($template_data as $coupon_id => $data)
            { 
                ?>
                <p>
                    <?php esc_html_e('Coupon', 'wt-smart-coupons-for-woocommerce-pro');?>: <?php echo esc_html(wc_get_coupon_code_by_id($coupon_id));?> <br />
                    <?php esc_html_e('Recipient email','wt-smart-coupons-for-woocommerce-pro');?>: <?php echo isset($data['wt_credit_coupon_send_to']) ? esc_html($data['wt_credit_coupon_send_to']) : '';?> <br />
                    <?php 
                    if($data['wt_smart_coupon_schedule'] && (int) $data['wt_smart_coupon_schedule']>0)
                    {
                        esc_html_e('Scheduled','wt-smart-coupons-for-woocommerce-pro');
                        echo ': ';
                        echo esc_html(Wt_Smart_Coupon_Admin::wt_sc_get_date_prop($data['wt_smart_coupon_schedule'])->date_i18n(wc_date_format()));
                    }
                    ?>
                </p>
                <?php
            }
        }   
    }

    public function display_template_image_in_order_item_row($product_image, $item_id, $item)
    {
        $template_data = $item->get_meta('wt_credit_coupon_template_details');
        
        if($template_data && is_array($template_data))
        {
            foreach($template_data as $coupon_id => $data)
            {
                if(isset($data['wt_smart_coupon_template_image']) && "" !== $data['wt_smart_coupon_template_image'])
                {
                    $template_data=self::get_gift_card_template($data['wt_smart_coupon_template_image']);
                    
                    if(isset($template_data['image_url']) && "" !== $template_data['image_url'])
                    {
                        if($product_image)
                        {
                            $product_image = preg_replace('(src="(.*?)")', 'src="'.$template_data['image_url'].'"', $product_image);
                            $product_image = preg_replace('(srcset="(.*?)")', 'srcset="'.$template_data['image_url'].'"', $product_image);
                        }else
                        {
                            $product_image = '<img src="'.esc_attr($template_data['image_url']).'" srcset="'.esc_attr($template_data['image_url']).'" class="attachment-thumbnail size-thumbnail" alt="" loading="lazy" title="" width="150" height="150">';
                        }
                        break;
                    }
                }
            }
        }

        return $product_image;
    }

    /**
     *  Help text for coupon restriction section
     *  @since  2.0.6
     *  @param  array   help text array
     *  @param  string  for which filed, default `product`. 
     *                  Possible values: product, exclude_product, category, exclude_category
     */
    public function add_discount_type_help_text($help_text_arr, $type = 'product')
    { 
        $out = array();

        foreach($help_text_arr as $help_text_arr_key => $help_text_arr_val)
        {
            if(false !== stristr($help_text_arr_key, 'fixed_cart'))
            {
                $out[$help_text_arr_key.'|store_credit'] = $help_text_arr_val;
            }else
            {
                $out[$help_text_arr_key] = $help_text_arr_val;
            }
        }

        return $out;
    }

    /**
     *  Set empty default value for store credit product js select field
     *  
     *  @since 2.0.8
     *  @param $default_val_needed_fields   array   Array of default value needed fields
     *  @param $base_id                     string  Module Id                
     *  @return $default_val_needed_fields  array   Array of default value needed fields for the current module               
     */
    public function default_val_needed_fields($default_val_needed_fields, $base_id)
    {
        if($base_id !== $this->module_id)
        {
            return $default_val_needed_fields;
        }

        return array(
            'store_credit_purchase_product' => '',
        );
    }

    /**
     *  Add Store credit my account tab settings field in general settings tab.
     *  Hooked into `wbte_sc_after_my_coupons_page_settings`.
     * 
     *  @since 2.4.0
     */
    public static function add_settings_fields(){
        ?>
        <h3 class="wt-sc-form-settings-group-heading">
        <?php _e( 'My store credits page', 'wt-smart-coupons-for-woocommerce-pro' ); 
        ?>           
        </h3>
        <table class="wt-sc-form-table">
            <?php
            Wt_Smart_Coupon_Admin::generate_form_field( array(
                array(
                    'label'         =>  __( 'Enable Store credit page', 'wt-smart-coupons-for-woocommerce-pro' ),
                    'option_name'   =>  'wbte_sc_enable_myaccount_storecredit_page',
                    'type'          =>  'checkbox',
                    'field_vl'      =>  'yes',
                    'form_toggler'  =>  array(
                        'type'      => 'parent',
                        'target'    => 'wbte_sc_enable_myaccount_storecredit_page',
                    ),
                ),
                array(
                    'label'         =>  __( 'URL endpoint','wt-smart-coupons-for-woocommerce-pro' ),
                    'option_name'   =>  'wbte_account_storecredit_endpoint',
                    'form_toggler'  =>  array(
                        'type'      => 'child',
                        'id'        => 'wbte_sc_enable_myaccount_storecredit_page',
                        'val'       => 'yes',
                        'level'     => 2,
                        'check'     => 'true',
                    ),
                    'css_class'     => 'wbte_account_storecredit_endpoint_input_class',
                    'td_additional_class' => 'wbte_sc_account_storecredit_endpoint_td_class',
                    'before_form_field' => '<span class="wbte_sc_account_storecredit_endpoint_site_url">' . __( 'yoursite.com/my-account/', 'wt-smart-coupons-for-woocommerce-pro' ) . '</span>'
                ),
                array(
                    'label'         =>  __( 'Page title','wt-smart-coupons-for-woocommerce-pro' ),
                    'option_name'   =>  'wbte_account_storecredit_page_title',
                    'form_toggler'  =>  array(
                        'type'      => 'child',
                        'id'        => 'wbte_sc_enable_myaccount_storecredit_page',
                        'val'       => 'yes',
                        'level'     => 2,
                        'check'     => 'true',
                    ),
                ),
                array(
                    'label'         =>  __( 'Additionally display', 'wt-smart-coupons-for-woocommerce-pro' ),
                    'option_name'   =>  'wbte_account_storecredit_additional_display',
                    'type'          =>  'checkbox_list',
                    'checkbox_fields'  =>  array(
                        'used_coupons'    => __( 'Used store credit coupons', 'wt-smart-coupons-for-woocommerce-pro' ),
                        'expired_coupons'     => __( 'Expired store credit coupons', 'wt-smart-coupons-for-woocommerce-pro' ),               
                    ),
                    'form_toggler'  =>  array(
                        'type'      => 'child',
                        'id'        => 'wbte_sc_enable_myaccount_storecredit_page',
                        'val'       => 'yes',
                        'level'     => 2,
                        'check'     => 'true',
                    ),
                ),
            ) );
            ?>
        </table>
        <?php
    }

    /**
     *   Save empty admin settings 
     *   @since 2.4.0 
     */
    public function save_settings()
    {
        $base = ( isset( $_POST['wt_sc_settings_base'] ) ? sanitize_text_field( wp_unslash( $_POST['wt_sc_settings_base'] ) ) : 'main' );

        if ( 'main' === $base ) {

            // Nonce verification.
            $nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
            $nonce = ( is_array( $nonce ) ? reset( $nonce ) : $nonce );
            if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wt_smart_coupons_admin_nonce' ) || !class_exists( 'Wt_Smart_Coupon_Security_Helper' ) || !method_exists( 'Wt_Smart_Coupon_Security_Helper', 'check_user_has_capability' ) || !Wt_Smart_Coupon_Security_Helper::check_user_has_capability() ) {
                return;
            }

            // Take existing settings
            $the_options = Wt_Smart_Coupon::get_settings();

            //Checkbox form fields array. (It will not return a $_POST val if it's value is empty so we need to set default value)
            $default_val_needed_fields = array(
                'wbte_sc_enable_myaccount_storecredit_page'   => 'no',
                'wbte_account_storecredit_additional_display' => array()
            );
            
            foreach( $default_val_needed_fields as $option => $value ) {
                if ( ! isset( $_POST[ $option ] ) ) {
                    $the_options[ $option ] = $value;
                }
            }

            // Save the settings.
            Wt_Smart_Coupon::update_settings( $the_options );
        }
    }
    
}
Wt_Smart_Coupon_Store_Credit_Admin::get_instance();