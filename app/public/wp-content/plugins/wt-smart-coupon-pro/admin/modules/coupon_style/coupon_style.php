<?php
/**
 * Coupon style admin section
 *
 * @link       
 * @since 1.3.5     
 *
 * @package  Wt_Smart_Coupon  
 */
if (!defined('ABSPATH')) {
    exit;
}
if(! class_exists ( 'Wt_Smart_Coupon_Style' ) ) /* common module class not found so return */
{
	return;
}
if(! class_exists ( 'Wt_Smart_Coupon_Style_Admin' ) ) {

	class Wt_Smart_Coupon_Style_Admin extends Wt_Smart_Coupon_Style{
		public $module_base='coupon_style';
		public $module_id='';
		public static $module_id_static='';
		private static $instance = null;
		public function __construct()
		{
			$this->module_id=Wt_Smart_Coupon::get_module_id($this->module_base);
			self::$module_id_static=$this->module_id;

			add_action('wp_ajax_wt_sc_customize_save', array($this, 'customize_save'),1);
			add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'), 10 );
			
			add_filter("wt_sc_plugin_settings_tabhead", array($this, 'settings_tabhead'),1);
			
			add_filter("wt_sc_plugin_out_settings_form", array($this, 'out_settings_form'),1);
		}

		/**
         * Get Instance
         * @since 1.3.5
         */
        public static function get_instance()
        {
            if(self::$instance==null)
            {
                self::$instance=new Wt_Smart_Coupon_Style_Admin();
            }
            return self::$instance;
        }

        /**
         * Save customization
         * @since 1.3.5
         */
        public function customize_save()
        {
        	$out=array(
				'status'=>0,
				'msg'=>__("Error", 'wt-smart-coupons-for-woocommerce-pro'),
			);

        	if(Wt_Smart_Coupon_Security_Helper::check_write_access($this->module_id, WT_SC_PLUGIN_NAME))
        	{
        		$style_settings=Wt_Smart_Coupon_Security_Helper::sanitize_item($_POST['wt_coupon_styles'], 'text_arr');
        		Wt_Smart_Coupon::update_settings($style_settings, $this->module_id);
        		$out=array(
					'status'=>	1,
					'msg'	=>	__("Success", 'wt-smart-coupons-for-woocommerce-pro'),
				);
        	}
        	
        	echo json_encode($out);
			exit();
        }

		public function enqueue_scripts()
		{
			if(isset($_GET['page']) && $_GET['page'] == WT_SC_PLUGIN_NAME)
			{
				wp_enqueue_script($this->module_id, plugin_dir_url(__FILE__) . 'assets/js/main.js', array('jquery', 'wp-color-picker', 'jquery-tiptip'), WEBTOFFEE_SMARTCOUPON_VERSION, false);
                wp_localize_script($this->module_id, 'wt_sc_customizer_params', array(
                	'ajax_url'=>admin_url('admin-ajax.php'),
                	'msgs'=>array(
                		'settings_error'=>__("Unable to save settings", 'wt-smart-coupons-for-woocommerce-pro'),
                	),
                ));
			}
		}

		/**
		 *  @since 1.3.5
		 * 	Tab head for plugin settings page
		 **/
		public function settings_tabhead($arr)
		{
			$added=0;
			$out_arr=array();
			foreach($arr as $k=>$v)
			{
				$out_arr[$k]=$v;
				if($k=='wt-sc-general' && $added==0)
				{				
					$out_arr['wt-sc-'.$this->module_base]=__('Customize coupon', 'wt-smart-coupons-for-woocommerce-pro');
					$added=1;
				}
			}
			if($added==0){
				$out_arr['wt-sc-'.$this->module_base]=__('Customize coupon', 'wt-smart-coupons-for-woocommerce-pro');
			}
			return $out_arr;
		}

		/**
		 * @since 1.3.5
		 * Customize tab content
		 **/
		public function out_settings_form($args)
		{
			$view_file=plugin_dir_path( __FILE__ ).'views/_customize.php';

			/* coupon dummy data for preview */
			$coupon_data_dummy=array(
                'coupon_amount'	=> '10',
                'coupon_type'	=> __('Cart discount', 'wt-smart-coupons-for-woocommerce-pro'),
                'coupon_code'	=> __('coupon-code', 'wt-smart-coupons-for-woocommerce-pro'),
                'preview_mode'	=> true,
            );

			$view_params=array(
				'coupon_types' 			=> self::get_coupon_types(),
				'current_coupon_style' 	=> self::get_current_coupon_style(),
				'coupon_styles' 		=> self::coupon_styles(),
				'coupon_data_dummy' 	=> $coupon_data_dummy,
			);
			Wt_Smart_Coupon_Admin::envelope_settings_tabcontent('wt-sc-'.$this->module_base, $view_file, '', $view_params, 0);
		}
	}
	Wt_Smart_Coupon_Style_Admin::get_instance();
}