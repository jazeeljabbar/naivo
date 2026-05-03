<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class Wt_Smart_Coupon_Licence_Manager
{
	public $module_id='';
	public $module_base='licence_manager';
	public $api_url = 'https://licensing.webtoffee.com/';
	public $main_plugin_slug = '';
	public $my_account_url = '';
	public $tab_icons=array(
		'active'=>'<span class="dashicons dashicons-yes" style="color:#03da01; font-size:25px;"></span>',   
	    'inactive'=>'<span class="dashicons dashicons-warning" style="color:#ff1515; font-size:25px;"></span>'
	);

	public $products=array();
	
	/**
	 * Product data to show expired message.
	 * This will be used in the callback method for showing messages.
	 * 
	 * @since 2.4.4
	 * @var array
	 */
	private $product_data_to_show_expired_msg = array();

	/** 
	 * 	Licence data to show expired message.
	 * 	This will be used in the callback method for showing messages.
	 * 
	 * @since 2.4.4
	 * @var array
	 */
	private $licence_data_to_show_expired_msg = array();

	public function __construct()
	{
		$this->module_id 			=Wt_Smart_Coupon::get_module_id($this->module_base);
		$this->my_account_url		=$this->api_url.'my-account';
		$this->main_plugin_slug		='wt-smart-coupon-pro';

		require_once plugin_dir_path(__FILE__).'classes/class-edd.php';	
		require_once plugin_dir_path(__FILE__).'classes/class-wc.php';	

		$this->products=array(
			$this->main_plugin_slug=>array(
				'product_id'			=>	WT_SC_ACTIVATION_ID,
				'product_edd_id'		=>	WT_SC_EDD_ACTIVATION_ID,
				'plugin_settings_url'	=>	admin_url('admin.php?page='.WT_SC_PLUGIN_NAME.'#wt-licence'),
				'product_version'		=>	WEBTOFFEE_SMARTCOUPON_VERSION,
				'product_name'			=>	WT_SC_ACTIVATION_ID,
				'product_slug'			=>	$this->main_plugin_slug,
				'product_display_name'	=>	'Smart Coupons for WooCommerce Pro', 
				/**
				 *  Product page campaign links in the licence expired messages.
				 * 
				 * 	@since 2.4.4
				 */
				'expired_campaign_links'=>	array(
					'wp_plugins_page'     => 'https://www.webtoffee.com/product/smart-coupons-for-woocommerce/?utm_source=plugins_license&utm_medium=activate_page&utm_campaign=RE_Smart_Coupon',
					'plugin_setting_page' => 'https://www.webtoffee.com/product/smart-coupons-for-woocommerce/?utm_source=plugins_license&utm_medium=license_page&utm_campaign=RE_Smart_Coupon',
				),
				'expired_campaign_coupon' => 'REVIVE50SMART',
			)
		);

		add_action('plugins_loaded', array($this, 'init'), 1);

		/**
		*	Add tab to settings section
		*/
		add_filter('wt_sc_plugin_settings_tabhead', array($this, 'licence_tabhead'));
		add_action('wt_sc_plugin_out_settings_form', array($this, 'licence_content'));

		/**
		*	 Main Ajax hook to handle all ajax requests 
		*/
		add_action('wp_ajax_wt_sc_licence_manager_ajax', array($this, 'ajax_main'),11);


		/**
		 *	Check for licence status. Will check once in a day.
		 *	@since 2.4.4 
		 */
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_licence_status' ), 9 );

	}

	public function init()
	{
		/**
		*	Add products to licence manager
		*/
		$this->products=apply_filters('wt_sc_add_licence_manager', $this->products);		
		
		if(!isset($this->products['wt-url-coupons-pro']))
		{
			include_once(ABSPATH.'wp-admin/includes/plugin.php');
			if(is_plugin_active('wt-url-coupons-pro/wt-url-coupons-pro.php'))
			{
				$this->products['wt-url-coupons-pro']=array(
					'product_id'			=>	'wturlcoupon',
					'product_edd_id'		=>	'196727',
					'plugin_settings_url'	=>	admin_url('admin.php?page='.WT_SC_PLUGIN_NAME.'#wt-licence'), //smart coupon settings page
					'product_version'		=>	WT_URL_COUPONS_PRO_VERSION,
					'product_name'			=>	'wturlcoupon',
					'product_slug'			=>	'wt-url-coupons-pro',
					'product_display_name'	=>	'URL coupons for woocommerce', 
					/**
					 *  Product page campaign links in the licence expired messages.
					 * 
					 * 	@since 2.4.4
					 */
					'expired_campaign_links'=>	array(
						'wp_plugins_page'     => 'https://www.webtoffee.com/product/url-coupons-for-woocommerce/?utm_source=plugins_license&utm_medium=activate_page&utm_campaign=RE_URL_Coupons',
						'plugin_setting_page' => 'https://www.webtoffee.com/product/url-coupons-for-woocommerce/?utm_source=plugins_license&utm_medium=license_page&utm_campaign=RE_URL_Coupons',
					),
					'expired_campaign_coupon' => 'REVIVE50URL',
				);
			}			
		}

		$this->migrate_licence_data();
		$this->check_for_licence_status_message();

		// Include and add to EDD plugin updator
		$this->add_to_edd_plugin_updator();
	}


	/**
	 * 	Include the EDD plugin updator class and add the plugin details to the class
	 */
	public function add_to_edd_plugin_updator() {
		
		// EDD plugin updator class
		include_once plugin_dir_path(__FILE__).'classes/EDD_SL_Plugin_Updater.php';

		/**
		 *	Get all licence info
		 */
		$licence_data = $this->get_licence_data();

		foreach ( $this->products as $product_slug => $product ) {

			$status = true;
			if ( ! isset( $licence_data[ $product_slug ] ) ) {
				$status = false;
			} else {
				if ( isset( $licence_data[ $product_slug ]['status'] ) ) {
					if ( '' === $licence_data[ $product_slug ]['status'] || 'inactive' === $licence_data[ $product_slug ]['status'] ) {
						$status = false;
					}					
				} else {
					$status = false;
				}
			}

			$plugin_base_path = "$product_slug/$product_slug.php"; // Plugin base path

			if( $status && function_exists('is_plugin_active') && is_plugin_active( $plugin_base_path ) ) { // Plugin and licence are active
				
				// Initiate plugin updator class
				new Wt_Smart_Coupon\Licence_Manager\EDD_SL_Plugin_Updater(
					$this->api_url,
					WP_PLUGIN_DIR . "/$plugin_base_path",
					array(
						'version' => $product['product_version'], // current version number
						'license' => $licence_data[ $product_slug ]['key'], // license key (used get_option above to retrieve from DB)
						'item_id' => $product['product_edd_id'], // ID of the product
						'author'  => 'WebToffee', // author of this plugin
						'beta'    => false,
					)
				);	
			}
		}
	}


	public function check_for_licence_status_message()
	{
		global $pagenow;

		// Check if we are on the plugins page or the plugin's page.
		if( empty( $pagenow ) || ! in_array( $pagenow, array( 'plugins.php', 'admin.php' ) ) ) {
			return;
		}
		
		// Get all licence info.
		$licence_data = $this->get_licence_data();

		$require_js_block=false;	
		foreach ( $this->products as $product_slug => $product ) {
			
			$product_licence_data = ! empty( $licence_data[ $product_slug ] ) ? $licence_data[ $product_slug ] : array();

			// Check if the licence is not active.
			if ( ! ( isset( $product_licence_data['status'] ) 
		          && $product_licence_data['status'] !== '' 
		          && $product_licence_data['status'] !== 'inactive' ) ) {
				
				$this->product_data_to_show_expired_msg[ $product_slug ] = $product;
				$this->licence_data_to_show_expired_msg[ $product_slug ] = $product_licence_data;

				if ( 'plugins.php' === $pagenow ) { // On the plugins page.
					$require_js_block = true;
					add_action('after_plugin_row_'.("{$product_slug}/{$product_slug}.php"), array($this, 'add_licence_status_message'), 10, 3);
				} else { // On the plugin's page.	
					add_action('admin_notices', array($this, 'add_licence_status_message'));
				}
			}
		}
		
		if ( $require_js_block ) {
			add_action('admin_footer', array($this, 'plugins_page_scripts'));
		}
	}

	public function plugins_page_scripts()
	{
		?>
		<style type="text/css">
			.wt-sc-plugin-notice-tr p:before{ content: "\f534"; }
		</style>
		<script type="text/javascript">
			jQuery(document).ready(function(){
				jQuery('.wt-sc-plugin-notice-tr').each(function(){
					if(jQuery(this).prev().addClass('update').hasClass('active'))
					{
						jQuery(this).addClass('active');
					}
				});
			});
		</script>
		<?php
	}

	/**
	 * Check the licence data and show the message.
	 * Hooked into `admin_notices`, `after_plugin_row_<product-slug>/<after_plugin_row_>.php`.
	 * 
	 * @param string $plugin_file The plugin file.
	 * @param array $plugin_data The plugin data.
	 * @param string $status The status of the plugin.
	 * @return void
	 */
	public function add_licence_status_message( $plugin_file = '', $plugin_data = array(), $status = '' ) {
		
		global $pagenow;
		

		if ( ! empty( $pagenow ) && 'plugins.php' === $pagenow ) {

			$product_slug = basename( dirname( $plugin_file ) );
			$product_data = $this->product_data_to_show_expired_msg[ $product_slug ] ?? array();
			$licence_data = $this->licence_data_to_show_expired_msg[ $product_slug ] ?? array();
			$message = $this->prepare_message_for_licence_renewal( $product_data, $licence_data );
			$campaign_links = isset( $product_data['expired_campaign_links'] ) && is_array( $product_data['expired_campaign_links'] ) ? $product_data['expired_campaign_links'] : array();

			$campaign_link = isset( $campaign_links['wp_plugins_page'] ) ? esc_url( $campaign_links['wp_plugins_page'] ) : 'https://www.webtoffee.com/plugins/';
			$message = str_replace( array( '{{campaign_link}}', '{{campaign_link_class}}', '{{line_break}}' ), array( $campaign_link, '', ''), $message );
			?>
			<tr class="plugin-update-tr installer-plugin-update-tr wt-sc-plugin-notice-tr">
				<td colspan="4" class="plugin-update colspanchange">
					<div class="update-message notice inline">
						<p>
							<?php echo wp_kses_post( $message ); ?>
						</p>
					</div>
				</td>
			</tr>
        	<?php
		} else { // On the `admin.php` page.
			
			$screen = function_exists('get_current_screen') ? get_current_screen()  : null;
			$screen_id = $screen ? $screen->id : '';
			
			// Check if we are on the plugin's page.
			if ( false !== strpos( $screen_id, WT_SC_PLUGIN_NAME ) ) {
				
				foreach( $this->product_data_to_show_expired_msg as $product_slug => $product_data ) {
					$licence_data = $this->licence_data_to_show_expired_msg[ $product_slug ] ?? array();
					$message = $this->prepare_message_for_licence_renewal( $product_data, $licence_data );
					$campaign_links = isset( $product_data['expired_campaign_links'] ) && is_array( $product_data['expired_campaign_links'] ) ? $product_data['expired_campaign_links'] : array();
					$campaign_link = isset( $campaign_links['plugin_setting_page'] ) ? esc_url( $campaign_links['plugin_setting_page'] ) : 'https://www.webtoffee.com/plugins/';
					$message = str_replace( array( '{{campaign_link}}', '{{campaign_link_class}}', '{{line_break}}' ), array( $campaign_link, 'button button-primary', '<br><br>'), $message );

					?>
					<div class="notice notice-warning wt_sc_licence_expired">
						<p><?php echo wp_kses_post( $message ); ?></p>
					</div>
					<?php
				}
			}
		}
	}

	/**
	 *  Prepare message for licence renewal based on the licence expiry date.
	 * 	
	 * 	@since 2.4.4
	 * 	@param array $product_data The product data.
	 * 	@param array $licence_data The licence data.
	 * 	@return string $message The message to show.
	 */
	private function prepare_message_for_licence_renewal( $product_data, $licence_data ) {

		// If no licence data / expiry data available then set the current date as expiry date.
		$current_date 	= strtotime( wp_date('Y-m-d H:i:s') ); // Current date.
		$expired_date 	= ! empty( $licence_data['expires'] ) ? strtotime( $licence_data['expires'] ) : $current_date;
		$five_days_before_date = $current_date - ( 7 * 86400 ); // 7 days before the current date.
		$plugin_name 	= ! empty( $product_data['product_display_name'] ) ? '<b>' . $product_data['product_display_name'] . '</b>' : __( 'The plugin', 'wt-smart-coupons-for-woocommerce-pro' );
		$coupon_code 	= ! empty( $product_data['expired_campaign_coupon'] ) ? '<b>' . $product_data['expired_campaign_coupon'] . '</b>' : '';


		if ( $expired_date <= $five_days_before_date ) { // 7 days ago the licence was expired.
			$message  = sprintf( __( 'Your subscription to the `%s` has expired. Without an active license, you will not receive updates for the latest features, compatibility, and security.', 'wt-smart-coupons-for-woocommerce-pro' ), $plugin_name );
			$message .= '<br>';
			$message .= sprintf( __( 'You can avail a %s discount on your new subscription with coupon code %s.', 'wt-smart-coupons-for-woocommerce-pro' ), '<b>50%</b>', $coupon_code );
			$message .= '{{line_break}}';
			$message .= ' <a href="{{campaign_link}}" target="_blank" class="{{campaign_link_class}}" style="font-weight:bold;">' . __( 'Get Plugin Now', 'wt-smart-coupons-for-woocommerce-pro' ) . '</a>';
		} else {
			$message = sprintf( __( '` %s ` license is not activated. You will not receive compatibility and security updates if the plugin license is not activated. %s Activate now %s', 'wt-smart-coupons-for-woocommerce-pro' ), $plugin_name, '<a href="' . esc_url( admin_url( 'admin.php?page=' . WT_SC_PLUGIN_NAME ) ) . '#wt-licence" target="_blank">', '</a>' );
		}

		return $message;
	}

	/**
	*	Fetch plugin info for update check and update info
	*/
	public function fetch_plugin_info($args)
	{
		$request=$this->remote_get($args);

		if(is_wp_error($request) || wp_remote_retrieve_response_code($request)!=200)
		{
			return false;
		}

		if(isset($args['api_key'])) //WC type. In EDD `license` instead of `api_key`
		{
			$response=maybe_unserialize(wp_remote_retrieve_body($request));
		}else
		{
			$response=json_decode(wp_remote_retrieve_body($request));
		}
				
		if(is_object($response))
		{
			return $response;
		}else
		{
			return false;
		}
	}

	/**
	* Main Ajax hook to handle all ajax requests. 
	*/
	public function ajax_main()
	{
		$allowed_actions=array('activate', 'deactivate', 'delete', 'licence_list');
		$action=(isset($_POST['wt_sc_licence_manager_action']) ? sanitize_text_field($_POST['wt_sc_licence_manager_action']) : '');
		$out=array('status'=>true, 'msg'=>'');
		if(!Wt_Smart_Coupon_Security_Helper::check_write_access(WT_SC_PLUGIN_ID))
		{
			$out['status']=false;

		}else
		{
			if(in_array($action,$allowed_actions))
			{
				if(method_exists($this,$action))
				{
					$out=$this->{$action}($out);
				}
			}
		}
		echo json_encode($out);
		exit();	
	}

	/**
	*	Fetch licence status
	*/
	public function fetch_status($product_data, $licence_data)
	{
		if($this->get_license_type($licence_data)=='WC')
		{
			$args = array(
				'request' 		=> 'status',
				'email'			=> $licence_data['email'],
				'licence_key'	=> $licence_data['key'], 
				'product_id' 	=> $product_data['product_id'],
				'instance' 		=> $licence_data['instance_id'],
				'platform' 		=> home_url(),
				'wc-api'		=> 'am-software-api', //End point
			);
		}else
		{

			$args = array(
				'edd_action' 	=> 'check_license',
				'license'		=> $licence_data['key'], 
				'item_id' 		=> (isset($product_data['product_edd_id']) ? $product_data['product_edd_id'] : 0),
				'url' 			=> urlencode(home_url()),
			);
		}

		$request=$this->remote_get($args);
		
		$response = wp_remote_retrieve_body($request);

		return $response;
	}

	/**
	*	Ajax sub function to delete licence
	*/
	public function delete($out)
	{
		$out['status']=false;
		$er=0;

		$licence_product=trim(isset($_POST['wt_sc_licence_product']) ? sanitize_text_field($_POST['wt_sc_licence_product']) : '');
		if($licence_product=="")
		{
			$er=1;
			$out['msg']=__('Error !!!', 'wt-smart-coupons-for-woocommerce-pro');
		}else
		{
			if(!isset($this->products[$licence_product]))
			{
				$er=1;
				$out['msg']=__('Error !!!', 'wt-smart-coupons-for-woocommerce-pro');
			}
		}
		if($er==0)
		{
			$this->remove_licence_data($licence_product);
            $out['status']=true;
			$out['msg']=__("Successfully deleted.", 'wt-smart-coupons-for-woocommerce-pro');
		}

		return $out;
	}

	/**
	*	Ajax sub function to deactivate licence
	*/
	public function deactivate($out)
	{

		$out['status']=false;
		$er=0;

		$licence_product=trim(isset($_POST['wt_sc_licence_product']) ? sanitize_text_field($_POST['wt_sc_licence_product']) : '');
		if($licence_product=="")
		{
			$er=1;
			$out['msg']=__('Error !!!', 'wt-smart-coupons-for-woocommerce-pro');
		}else
		{
			if(!isset($this->products[$licence_product]))
			{
				$er=1;
				$out['msg']=__('Error !!!', 'wt-smart-coupons-for-woocommerce-pro');
			}
		}

		if($er==0)
		{
			$licence_data=$this->get_licence_data($licence_product);
			if(!$licence_data)
			{
				$er=1;
				$out['msg']=__('Error !!!', 'wt-smart-coupons-for-woocommerce-pro');
			}
		}

		$product_data=$this->products[$licence_product];
		if($er==0)
		{
			$license_type=$this->get_license_type($licence_data);

			if($license_type=='WC')
			{
				$args=array(
					'request' 		=> 'deactivation',
					'email'			=> $licence_data['email'],
					'licence_key'	=> $licence_data['key'],
					'product_id' 	=> $product_data['product_id'],
					'instance' 		=> $licence_data['instance_id'],
					'platform' 		=> home_url(),
					'wc-api'		=> 'am-software-api', //Endpoint
				);
			}else
			{
				$args=array(
					'edd_action'	=> 'deactivate_license',
					'license'		=> $licence_data['key'],
					//'item_name' 	=> $product_data['product_display_name'], //name in EDD
					'item_id' 		=> (isset($product_data['product_edd_id']) ? $product_data['product_edd_id'] : 0), //ID in EDD
					'url' 			=> urlencode(home_url()),
				);
			}
			$response=$this->remote_get($args);
			
			
			
			if(is_wp_error($response) || wp_remote_retrieve_response_code($response)!=200)
			{
				$out['msg']=__("Request failed, Please try again", 'wt-smart-coupons-for-woocommerce-pro');
			}else
	        {
	        	$response=json_decode(wp_remote_retrieve_body($response), true);
	        	$success=false;
	        	if($license_type=='WC')
				{					
		        	if(!isset($response['error']))
		        	{
		        		$success=true;
		        	}
				}else
				{
		        	if(isset($response['success']) && $response['success']===true)
		        	{
		        		$success=true;
		        	}
		        }

		        if($success)
		        {
		        	$this->remove_licence_data($licence_product);
		            $out['status']=true;
					$out['msg']=__("Successfully deactivated.", 'wt-smart-coupons-for-woocommerce-pro'); 
		        }else
		        {
		        	$out['msg']=__('Error', 'wt-smart-coupons-for-woocommerce-pro');
		        }

	        }
		}
		return $out;
	}

	public function remote_get($args)
	{
		global $wp_version;
		$target_url=esc_url_raw($this->create_api_url($args));

		$def_args = array(
		    'timeout'     => 5,
		    'redirection' => 5,
		    'httpversion' => '1.0',
		    'user-agent'  => 'WordPress/' . $wp_version . '; ' . home_url(),
		    'blocking'    => true,
		    'headers'     => array(),
		    'cookies'     => array(),
		    'body'        => null,
		    'compress'    => false,
		    'decompress'  => true,
		    'sslverify'   => false,
		    'stream'      => false,
		    'filename'    => null
		);
		return wp_remote_get($target_url, $def_args);
	}

	/**
	*	Ajax sub function to activate licence
	*/
	public function activate($out)
	{
		global $wp_version;

		$out['status']=false;
		$er=0;

		$licence_product=trim(isset($_POST['wt_sc_licence_product']) ? sanitize_text_field($_POST['wt_sc_licence_product']) : '');
		$licence_key=trim(isset($_POST['wt_sc_licence_key']) ? sanitize_text_field($_POST['wt_sc_licence_key']) : '');
		$licence_email=trim(isset($_POST['wt_sc_licence_email']) ? sanitize_text_field($_POST['wt_sc_licence_email']) : '');

		if($licence_product=="")
		{
			$er=1;
			$out['msg']=__('Please select a product', 'wt-smart-coupons-for-woocommerce-pro');
		}else
		{
			if(!isset($this->products[$licence_product]))
			{
				$er=1;
				$out['msg']=__('Invalid product', 'wt-smart-coupons-for-woocommerce-pro');
			}
		}
		if($er==0 && $licence_key=="")
		{
			$er=1;
			$out['msg']=__('Please enter Licence key', 'wt-smart-coupons-for-woocommerce-pro');
		}
		if($er==0 && $licence_key!="")
		{
			/* check the licence key already applied */
			$licence_data=$this->get_licence_data();
			foreach ($licence_data as $product_slug => $licence_info)
			{
				if($product_slug==$licence_product) /* already one licence exists */
				{
					if($licence_info['status']=='active')
					{
						$er=1;
						$out['msg']=__('The chosen plugin already has an active licence.', 'wt-smart-coupons-for-woocommerce-pro');
						break;
					}
				}

				/* current licence key matches with another product */
				if($licence_key==$licence_info['key'] && $product_slug!=$licence_product && $licence_info['status']=='active')
				{
					$er=1;
					$out['msg']=__('This licence key has already been activated for another product. Please provide another licence key.', 'wt-smart-coupons-for-woocommerce-pro');
					break;
				}
			}
		}

		if($er==0) /* check the entered license belongs to which type */
		{
			$license_type=$this->get_license_type( array('key'=>$licence_key) );
			if($license_type=='WC')
			{
				if($licence_email=="")
				{
					$er=1;
					$out['msg']=__('Please enter Email', 'wt-smart-coupons-for-woocommerce-pro');
				}
			}
		}

		if($er==0)
		{
			$product_data=$this->products[$licence_product];
			if($license_type=='WC')
			{
				require_once plugin_dir_path(__FILE__).'classes/class-wc-api-manager-passwords.php';	
				$password_management = new API_Manager_Password_Management();

				// Generate a unique installation $instance id
				$instance = $password_management->generate_password(12, false);

				$args = array(
					'email'				=> $licence_email,
					'licence_key'		=> $licence_key,
					'request' 			=> 'activation',
					'product_id' 		=> $product_data['product_id'],
					'instance' 			=> $instance,
					'platform' 			=> home_url(),
					'software_version' 	=> $product_data['product_version'],
					'wc-api'			=> 'am-software-api', //End point
				);

			}else
			{
				$args = array(
					'edd_action'		=> 'activate_license',
					'license'			=> $licence_key,
					//'item_name' 		=> $product_data['product_display_name'], //name in EDD
					'item_id' 			=> (isset($product_data['product_edd_id']) ? $product_data['product_edd_id'] : 0), //ID in EDD
					'url' 				=> urlencode(home_url()),
				);
			}
			$response=$this->remote_get($args);

			// Request failed
			if(is_wp_error($response))
			{
				$out['msg']=$response->get_error_message();
			}
			elseif( wp_remote_retrieve_response_code( $response ) != 200 )
			{
				$out['msg']=__("Request failed, Please try again", 'wt-smart-coupons-for-woocommerce-pro');
			}
	        else
	        {	        	
	        	$response_arr=json_decode($response['body'], true);
		        if($license_type=='WC')
				{
		        	if(!isset($response_arr['error']) && isset($response_arr['activated']) && $response_arr['activated']===true)
		        	{
		        		$licence_data=array(
							'key'			=> $licence_key,
							'email'			=> $licence_email,
							'status'		=> 'active',
							'products'		=> $product_data['product_display_name'], 
							'instance_id'	=> $instance,
						);
						$out['status']=true;
		        	}else
		        	{	
		        		$out['msg']=$response_arr['error'];
		        	}

				}else
				{	
		        	if(isset($response_arr['success']) && $response_arr['success']===true) /* success */
		        	{
	        			$licence_data=array(
							'key'			=> $licence_key,
							'email'			=> (isset($response_arr['customer_email']) ? sanitize_text_field($response_arr['customer_email']) : ''), //from EDD
							'status'		=> 'active',
							'products'		=> $product_data['product_display_name'], 
							'instance_id'	=> (isset($response_arr['checksum']) ? sanitize_text_field($response_arr['checksum']) : ''), //from EDD
						);						
						$out['status']=true;	        		
		        	}

		        	if(!$out['status']) /* error */
		        	{	
		        		$out['msg']=$this->process_error_keys( (isset($response_arr['error']) ? $response_arr['error'] : '') );
		        	}

		        }

		        if($out['status']===true) /* success. Save license info */
		        {
		        	$this->add_new_licence_data($licence_product, $licence_data);
		        	$out['msg']=__("Successfully activated.", 'wt-smart-coupons-for-woocommerce-pro');
		        }

	        }
		}
		return $out;
	}

	/**
	 *	Ajax sub function to get license list
	 */
	public function licence_list($out)
	{
		// This method is also hooked into `pre_set_site_transient_update_plugins` filter.
		$this->check_licence_status();

		$licence_data_arr=$this->get_licence_data(); // Taking all license info.
		ob_start();
		include plugin_dir_path(__FILE__).'views/_licence_list.php';
		$out['html']=ob_get_clean();
		return $out;
	}

	/**
	*	Mask licence key
	*/
	public function mask_licence_key($key)
	{
		$total_length=strlen($key);
		$non_mask_length=6; //including both side
		$mask_length=$total_length-$non_mask_length;
		
		if($mask_length>=1) //atleast one character
		{
			$key=substr_replace($key, str_repeat("*", $mask_length), floor($non_mask_length/2), ($total_length-$non_mask_length));
		}else
		{
			$key=str_repeat("*", $total_length); //replace all character
		}
		return $key;		
	}

	/**
	*	Licence tab head
	*/
	public function licence_tabhead($arr)
	{	
		$status=true;
		$licence_data=$this->get_licence_data();
		if(!$licence_data)
		{
			$status=false; //no licence found
		}

		if($status && count($licence_data)!=count($this->products))
		{
			$status=false; //licence missing for some products
		}

		if($status)
		{
			$licence_statuses=array_column($licence_data, 'status');
			if(count($licence_statuses)==0 || in_array('inactive', $licence_statuses) || in_array('', $licence_statuses)) //inactive licence
			{
				$status=false;
			}		
		}
		if($status)
	    {
	        $activate_icon=$this->tab_icons['active'];   
	    }else
	    {
	        $activate_icon=$this->tab_icons['inactive'];
	    }
		$arr['wt-licence']=array(__('Licence','wt-smart-coupons-for-woocommerce-pro'),$activate_icon);
		return $arr;
	} 

	/**
	*	Licence tab content
	*/
	public function licence_content()
	{
		wp_enqueue_script($this->module_id, plugin_dir_url( __FILE__ ).'assets/js/main.js', array('jquery'), WEBTOFFEE_SMARTCOUPON_VERSION);

		$params=array(
	        'ajax_url' => admin_url('admin-ajax.php'),
	        'nonce' => wp_create_nonce(WT_SC_PLUGIN_ID),
	        'tab_icons'=>$this->tab_icons,
	        'msgs'=>array(
	        	'key_mandatory'=>__('Please enter Licence key', 'wt-smart-coupons-for-woocommerce-pro'),
	        	'email_mandatory'=>__('Please enter Email', 'wt-smart-coupons-for-woocommerce-pro'),
	        	'product_mandatory'=>__('Please select a product', 'wt-smart-coupons-for-woocommerce-pro'),
	        	'please_wait'=>__('Please wait...', 'wt-smart-coupons-for-woocommerce-pro'),
	        	'error'=>__('Error', 'wt-smart-coupons-for-woocommerce-pro'),
	        	'success'=>__('Success', 'wt-smart-coupons-for-woocommerce-pro'),
	        	'unable_to_fetch'=>__('Unable to fetch Licence details', 'wt-smart-coupons-for-woocommerce-pro'),
	        	'no_licence_details'=>__('No Licence details found.', 'wt-smart-coupons-for-woocommerce-pro'),
	        	'sure'=>__('Are you sure?', 'wt-smart-coupons-for-woocommerce-pro'),
	        )
		);
		wp_localize_script($this->module_id, 'wt_sc_licence_params', $params);


		$view_file=plugin_dir_path(__FILE__).'views/licence-settings.php';	
		$view_params=array(
			'products'=>$this->products
		);
		Wt_Smart_Coupon_Admin::envelope_settings_tabcontent('wt-licence', $view_file, '', $view_params, 0);
	}

	public function get_status_label($status)
	{
		$color_arr=array(
			'active'=>'#5cb85c',
			'inactive'=>'#ccc',
		);
		$color_css=(isset($color_arr[$status]) ? 'background:'.$color_arr[$status].';' : '');
		return '<span class="wt_sc_badge" style="'.$color_css.'">'.ucfirst($status).'</span>';
	}

	public function get_display_name($product_slug)
	{
		if(isset($this->products[$product_slug]))
		{
			return $this->products[$product_slug]['product_display_name'];
		}
		return '';
	}

	private function create_api_url($args)
	{
		return urldecode(add_query_arg($args, $this->api_url));	
	}


	public function migrate_licence_data()
	{
		foreach($this->products as $product_slug=>$product)
		{	
			$product_id=$product['product_id'];		
			if(
				$this->get_licence_data($product_slug) /* already in new version */
				|| !get_option($product_id.'_'.'licence_key', false) /* no licence activated yet */
			) 
			{
				continue;
			}
			
			$licence_data=array(
				'key'			=> get_option($product_id.'_'.'licence_key', ''),
				'email'			=> get_option($product_id.'_'.'email', ''),
				'status'		=> get_option($product_id.'_'.'activation_status', ''),
				'products'		=> $product['product_display_name'], 
				'instance_id'	=> get_option($product_id.'_'.'instance_id', ''),
			);

			$this->add_new_licence_data($product_slug, $licence_data); /* add licence data as new format */

			/* remove old options */
			delete_option($product_id.'_'.'licence_key');
			delete_option($product_id.'_'.'email');
			delete_option($product_id.'_'.'activation_status');
			delete_option($product_id.'_'.'instance_id');

		}

		/**
		 *  Bug fix: Product slug mismatch
		 */
		$incorrect_slug='wt-smart-coupon-for-woo';
		if($incorrect_licence_data=$this->get_licence_data($incorrect_slug)) /* data exists under incorrect slug */
		{
			$this->remove_licence_data($incorrect_slug);
			$this->add_new_licence_data($this->main_plugin_slug, $incorrect_licence_data);			
		}
	}

	/**
	*	Add new licence info
	*/
	private function add_new_licence_data($product_slug, $licence_data)
	{
		update_option($product_slug.'_licence_data', $licence_data);
	}

	private function remove_licence_data($product_slug)
	{
		delete_option($product_slug.'_licence_data');
	}

	private function update_licence_data($product_slug, $licence_data)
	{
		update_option($product_slug.'_licence_data', $licence_data);
	}

	private function get_licence_data($product_slug="")
	{
		if($product_slug!="")
		{
			$licence_data=get_option($product_slug.'_licence_data', false);
		}else
		{
			$licence_data=array();
			foreach ($this->products as $product_slug => $product)
			{
				$licence_info=get_option($product_slug.'_licence_data', false);
				if($licence_info) //licence exists
				{
					$licence_data[$product_slug]=$licence_info;	
				}
			}
		}
		return $licence_data;
	}

	/**
	*	Check the licence type is EDD or WC
	*/
	private function get_license_type_obj($licence_data)
	{
		if($this->get_license_type($licence_data)=='WC')
		{
			return Wt_Smart_Coupon_Licence_Manager_Wc::get_instance();
		}
		return Wt_Smart_Coupon_Licence_Manager_Edd::get_instance();
	}

	/**
	*	Check the licence type is EDD or WC
	*/
	private function get_license_type($licence_data)
	{
		$key=$licence_data['key'];
		if(strpos($key, 'wc_order_')===0)
		{
			return 'WC';
		}
		return 'EDD';
	}

	private function process_error_keys($key)
	{
		$msg_arr=array(
			"missing" => __("License doesn't exist", 'wt-smart-coupons-for-woocommerce-pro'),
			"missing_url" => __("URL not provided", 'wt-smart-coupons-for-woocommerce-pro'),
			"license_not_activable" => __("Attempting to activate a bundle's parent license", 'wt-smart-coupons-for-woocommerce-pro'),
			"disabled" => __("License key revoked", 'wt-smart-coupons-for-woocommerce-pro'),
			"no_activations_left" => __("No activations left", 'wt-smart-coupons-for-woocommerce-pro'),
			"expired" => __("License has expired", 'wt-smart-coupons-for-woocommerce-pro'),
			"key_mismatch" => __("License is not valid for this product", 'wt-smart-coupons-for-woocommerce-pro'),
			"invalid_item_id" => __("Invalid Product", 'wt-smart-coupons-for-woocommerce-pro'),
			"item_name_mismatch" => __("License is not valid for this product", 'wt-smart-coupons-for-woocommerce-pro'),
		);
		return (isset($msg_arr[$key]) ? $msg_arr[$key] : __("Error", 'wt-smart-coupons-for-woocommerce-pro'));
	}

	/**
	 * Check the licence status. Will check once in a day. Trigger the force update check to check immediately.
	 * 
	 * @param  array $transient
	 * @return array
	 */
	public function check_licence_status( $transient = array() ) {

		$timestamp = time(); // Current timestamp.
		$home_url = urlencode( home_url() );
		$licence_data_arr = $this->get_licence_data(); // Get all licence info.

		foreach ( $licence_data_arr as $product_slug => $licence_data ) {
			
			if ( ! empty ( $licence_data ) && ! empty ( $this->products[ $product_slug ] ) ) {
				
				// Get the last check time.
				$last_check = get_option( $product_slug . '-last-update-check' );
				if ( is_null( $last_check ) ) { // First time so add a 24 hour back time. 
					$last_check = $timestamp - 86402;
					update_option( $product_slug . '-last-update-check', $last_check );
				}

				// Previous check is before 24 hours or `force check`.
				if( ( $timestamp - $last_check ) > 86400 || isset( $_GET['force-check'] ) ) {

					$product_data = $this->products[ $product_slug ];				
					$response = $this->fetch_status( $product_data, $licence_data );
					$response_arr = json_decode( $response, true );
					$new_status = $this->get_license_type_obj( $licence_data )->check_status( $licence_data, $response_arr );

					// Check update needed.
					if ( $licence_data['status'] !== $new_status ) {
						$licence_data['status'] = $new_status;
						$licence_data['expires'] = $response_arr['expires'];
						$this->update_licence_data( $product_slug, $licence_data );
					}

					// Update the last check time with the current time.
					update_option( $product_slug . '-last-update-check', $timestamp );
				}			
			}
		}

		return $transient;
	}
}
new Wt_Smart_Coupon_Licence_Manager();