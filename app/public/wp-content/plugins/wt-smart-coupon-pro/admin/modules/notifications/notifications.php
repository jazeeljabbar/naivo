<?php
/**
 * Coupon notifications admin section
 *
 * @link       
 * @since 2.0.8
 *
 * @package  Wt_Smart_Coupon  
 */
if (!defined('ABSPATH')) {
    exit;
}
if(!class_exists ('Wt_Smart_Coupon_Notifications')) /* common module class not found so return */
{
	return;
}

class Wt_Smart_Coupon_Notifications_Admin extends Wt_Smart_Coupon_Notifications
{
    public $module_base             = 'notifications';
    public $module_id               = '';
    public static $module_id_static = '';
    private static $instance        = null;
    private $state_icons            = array();

    public function __construct()
    {
        $this->module_id        = Wt_Smart_Coupon::get_module_id($this->module_base);
        self::$module_id_static = $this->module_id;

        add_action( 'init', array( $this, 'init' ) );

        /**
         *  General notification hooks
         */
        add_action('wp_ajax_wt_sc_notification_save', array($this, 'save_notification'),1);

        add_filter('wt_sc_alter_tooltip_data', array($this, 'register_tooltips'), 1);
              
        add_filter("wt_sc_plugin_settings_tabhead", array($this, 'settings_tabhead'), 1);
        
        add_filter("wt_sc_plugin_out_settings_form", array($this, 'out_settings_form'), 1);

        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'), 10);


        /**
         *  Individual notification hooks
         */
        add_action('woocommerce_coupon_options', array($this, 'add_individual_message_field'), 10, 2);
        add_action('woocommerce_process_shop_coupon_meta', array($this, 'save_individual_message'), 10, 2);
    }

    /**
     * Initialize the state icons.
     * 
     * @since 3.1.0 Moved from __construct to init to avoid issues with translating text before init.
     */
    public function init() {

        $this->state_icons = array(
            'default' => '<span class="wt_sc_badge wt_sc_notif_def_badge">'.esc_html__( 'default', 'wt-smart-coupons-for-woocommerce-pro' ).'</span>',
            'custom' => '<span class="wt_sc_badge wt_sc_notif_cus_badge">'.esc_html__( 'custom', 'wt-smart-coupons-for-woocommerce-pro' ).'</span>',
            'hidden' => '<span class="wt_sc_badge wt_sc_notif_hid_badge">'.esc_html__( 'hidden', 'wt-smart-coupons-for-woocommerce-pro' ).'</span>',
        );
    }

    /**
     *  Get Instance
     * 
     *  @since 2.0.8
     */
    public static function get_instance()
    {
        if(is_null(self::$instance))
        {
            self::$instance = new Wt_Smart_Coupon_Notifications_Admin();
        }

        return self::$instance;
    }

    
    /**
     *  Hook the tooltip data to main tooltip array
     * 
     *  @since  2.0.8
     *  @param  array    $tooltip_arr    Array of tooltip texts
     *  @return array    Array of tooltip texts
     */
    public function register_tooltips($tooltip_arr)
    {
        include(plugin_dir_path( __FILE__ ).'data/data.tooltip.php');
        
        $tooltip_arr[$this->module_id]=$arr;
        
        return $tooltip_arr;
    }


    /**
     *  Tab head for plugin settings page
     * 
     *  @since  2.0.8
     *  @param  array    $arr    Array of tab head data 
     *  @return array    Array of tab head data 
     */
    public function settings_tabhead($arr)
    {
        $added      = 0;
        $out_arr    = array();
        
        foreach($arr as $k => $v)
        {
            $out_arr[$k] = $v;
            if(false !== strpos($k, 'coupon_banner') && 0 === $added) 
            {               
                $out_arr['wt-sc-' . $this->module_base] = __('Customize messages', 'wt-smart-coupons-for-woocommerce-pro');
                $added = 1;
            }
        }

        if(0 === $added)
        {
            $out_arr['wt-sc-'.$this->module_base] = __('Customize messages', 'wt-smart-coupons-for-woocommerce-pro');
        }

        return $out_arr;
    }


    /**
     *  Notifications tab content
     * 
     *  @since  2.0.8
     *  @param  array    $args    Array of arguments
     */
    public function out_settings_form($args)
    {
        $view_file = plugin_dir_path( __FILE__ ) . 'views/_settings.php';
       
        $message_list = $this->get_notifications();

        $view_params = array(
            'message_list'  => $message_list,
            'module_id'     => $this->module_id,
            'state_icons'   => $this->state_icons,
        );     

        Wt_Smart_Coupon_Admin::envelope_settings_tabcontent('wt-sc-'.$this->module_base, $view_file, '', $view_params, 0);
    }


    /**
     *  Enqueue JS for tab
     * 
     *  @since  2.0.8
     */
    public function enqueue_scripts()
    {
        if(isset($_GET['page']) && $_GET['page'] === WT_SC_PLUGIN_NAME)
        {
            wp_enqueue_script($this->module_id, plugin_dir_url(__FILE__) . 'assets/js/main.js', array('jquery'), WEBTOFFEE_SMARTCOUPON_VERSION, false);

            $params = array(
                'msgs' => array(
                    'no_avail_values'   => __('No values available', 'wt-smart-coupons-for-woocommerce-pro'),
                    'no_avail_filters'  => __('No filters available', 'wt-smart-coupons-for-woocommerce-pro'),
                    'no_cus_message'    => __('No custom message', 'wt-smart-coupons-for-woocommerce-pro'),
                ),
            );

            $params = array_merge($params, $this->state_icons);

            wp_localize_script($this->module_id, 'wt_sc_notifications_params', $params);
        }
    }


    /**
     *  Save the custom notification
     *  
     *  1. If status is zero and message is empty then default message will be displayed
     *  2. If status is one and message is empty then no messages will be shown
     *  3. If status is one and message is not empty then custom messages will be shown
     * 
     *  @since  2.0.8
     */
    public function save_notification()
    {
        $out = array(
            'status'    => 0,
            'msg'       => __("Error", 'wt-smart-coupons-for-woocommerce-pro'),
        );

        if(Wt_Smart_Coupon_Security_Helper::check_write_access('smart_coupons', 'wt_smart_coupons_admin_nonce'))
        {
            $msg_key = (isset($_POST['wt_sc_notif_msg_key']) ? sanitize_text_field($_POST['wt_sc_notif_msg_key']) : '');
            $status = (isset($_POST['wt_sc_notif_status']) ? absint($_POST['wt_sc_notif_status']) : 0); 
            $msg = (isset($_POST['wt_sc_notif_msg']) ? trim(sanitize_textarea_field($_POST['wt_sc_notif_msg'])) : '');

            if("" !== $msg_key)
            {
                $msg_list       = $this->get_notifications();            

                if(isset($msg_list[$msg_key])) //registered one
                {
                    //remove `sprintf` placeholder characters 
                    $msg = $this->clean_custom_msg($msg);

                    //block status changing of status locked items
                    $status = (isset($msg_list[$msg_key]['status_locked']) ? $msg_list[$msg_key]['status'] : $status);

                    $custom_list    = $this->get_customized_notifications();
                    
                    $custom_list[$msg_key] = array(
                        'status'    => $status,
                        'message'   => $msg,
                    );

                    $this->set_customized_notifications($custom_list);

                    $out = array(
                        'status' => 1,
                        'msg'    => __("Success", 'wt-smart-coupons-for-woocommerce-pro'),
                    );
                }
            }

        }else
        {
            $out['msg'] = __("Security check failed. Please reload the page and try again.", 'wt-smart-coupons-for-woocommerce-pro');
        }

        echo json_encode($out);
        exit();
    }


    /**
     *  Add custom message textarea field in coupon edit page
     * 
     *  @since 2.0.8
     *  @param int          $coupon_id      ID of coupon
     *  @param WC_Coupon    $coupon         Coupon object
     */
    public function add_individual_message_field($coupon_id, $coupon)
    {
        $wt_sc_coupon_applied_message = get_post_meta($coupon_id , '_wt_sc_coupon_applied_message', true);
        ?>
            <p class="form-field">
                <label for="_wt_sc_coupon_applied_message"><?php esc_html_e('Coupon applied message', 'wt-smart-coupons-for-woocommerce-pro'); ?></label>
                <textarea id="_wt_sc_coupon_applied_message" name="_wt_sc_coupon_applied_message" style="width: 50%;" placeholder="<?php esc_attr_e('Default message', 'wt-smart-coupons-for-woocommerce-pro'); ?>"><?php echo esc_textarea($wt_sc_coupon_applied_message); ?></textarea>       
                <?php echo wc_help_tip(esc_html__('Add custom coupon applied message for this coupon. Leave it empty for default message.', 'wt-smart-coupons-for-woocommerce-pro')); ?>
            </p>          
        <?php
    }


    /**
     *  Save custom coupon applied message
     * 
     *  @since 2.0.8
     *  @param int          $post_id      ID of coupon
     *  @param WP_Post      $post         Post object
     */
    public function save_individual_message($post_id, $post)
    {
        $coupon_applied_message = (isset($_POST['_wt_sc_coupon_applied_message']) ? sanitize_textarea_field($_POST['_wt_sc_coupon_applied_message']) : '');
            
        if("" !== $coupon_applied_message)
        {
            update_post_meta($post_id, '_wt_sc_coupon_applied_message', $coupon_applied_message);
        }else
        {
            delete_post_meta($post_id, '_wt_sc_coupon_applied_message');
        }
    }
}

Wt_Smart_Coupon_Notifications_Admin::get_instance();