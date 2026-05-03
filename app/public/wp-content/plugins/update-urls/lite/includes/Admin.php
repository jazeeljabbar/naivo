<?php

/**
 * The dashboard-specific functionality of the plugin.
 *
 * @link       https://kaizencoders.com
 * @since      1.2
 *
 * @package    UpdateURLS
 * @subpackage UpdateURLS/admin
 */

namespace KaizenCoders\UpdateURLS;

/**
 * The dashboard-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @package    UpdateURLS
 * @subpackage UpdateURLS/admin
 * @author     KaizenCoders <hello@kaizencoders.com>
 */
class Admin {

	/**
	 * The plugin's instance.
	 *
	 * @since  1.2
	 * @access private
	 * @var    Plugin $plugin This plugin's instance.
	 */
	private $plugin;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param Plugin $plugin This plugin's instance.
	 *
	 * @since 1.2
	 *
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Register the stylesheets for the Dashboard.
	 *
	 * @since    1.2
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in UpdateURLS_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The UpdateURLS_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		if ( Helper::is_plugin_admin_screen() ) {

			\wp_enqueue_style(
				'update-urls-main',
				\plugin_dir_url( dirname( __FILE__ ) ) . 'dist/styles/app.css',
				array(),
				$this->plugin->get_version(),
				'all' );
			\wp_enqueue_style(
				$this->plugin->get_plugin_name(),
				\plugin_dir_url( dirname( __FILE__ ) ) . 'dist/styles/update-urls-admin.css',
				array(),
				$this->plugin->get_version(),
				'all' );
		}

	}

	/**
	 * Register the JavaScript for the dashboard.
	 *
	 * @since    1.2
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in UpdateURLS_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The UpdateURLS_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		if ( Helper::is_plugin_admin_screen() ) {

			\wp_enqueue_script(
				'uu-app',
				\plugin_dir_url( dirname( __FILE__ ) ) . 'dist/scripts/app.js',
				array( 'jquery' ),
				$this->plugin->get_version(),
				true );

			\wp_enqueue_script(
				'update-urls-admin',
				\plugin_dir_url( dirname( __FILE__ ) ) . 'dist/scripts/update-urls-admin.js',
				array( 'jquery' ),
				$this->plugin->get_version(),
				true );
		}

		wp_localize_script(
			'update-urls-admin',
			'uuParams',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' )
			)
		);

	}

	/**
	 * Add admin menu
	 *
	 * @since 1.0.0
	 */
	public function add_admin_menu() {
		add_management_page( 'Update URLs', 'Update URLs', 'manage_options', 'update-urls', array(
			$this,
			'render_UpdateURLS_page'
		) );
	}

	function render_UpdateURLS_page() {
        include KC_UU_ADMIN_TEMPLATES_DIR . '/update-urls.php';
	}

	/**
	 * Remove all admin notices
	 *
	 * @since 1.2
	 */
	public function handle_admin_notices() {
		global $wp_filter;

		if ( ! Helper::is_plugin_admin_screen() ) {
			return;
		}

		$allow_display_notices = array(
			'show_review_notice',
			'kc_uu_fail_php_version_notice',
			'kc_uu_show_admin_notice',
			'show_custom_notices',
			'handle_promotions',
			'_admin_notices_hook'
		);

		$filters = array(
			'admin_notices',
			'user_admin_notices',
			'all_admin_notices'
		);

		foreach ( $filters as $filter ) {

			if ( ! empty( $wp_filter[ $filter ]->callbacks ) && is_array( $wp_filter[ $filter ]->callbacks ) ) {

				foreach ( $wp_filter[ $filter ]->callbacks as $priority => $callbacks ) {

					foreach ( $callbacks as $name => $details ) {

						if ( is_object( $details['function'] ) && $details['function'] instanceof \Closure ) {
							unset( $wp_filter[ $filter ]->callbacks[ $priority ][ $name ] );
							continue;
						}

						if ( ! empty( $details['function'][0] ) && is_object( $details['function'][0] ) && count( $details['function'] ) == 2 ) {
							$notice_callback_name = $details['function'][1];
							if ( ! in_array( $notice_callback_name, $allow_display_notices ) ) {
								unset( $wp_filter[ $filter ]->callbacks[ $priority ][ $name ] );
							}
						}

						if ( ! empty( $details['function'] ) && is_string( $details['function'] ) ) {
							if ( ! in_array( $details['function'], $allow_display_notices ) ) {
								unset( $wp_filter[ $filter ]->callbacks[ $priority ][ $name ] );
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Update admin footer text
	 *
	 * @param $footer_text
	 *
	 * @return string
	 *
	 * @since 1.2
	 */
	public function update_admin_footer_text( $footer_text ) {

		// Update Footer admin only on Update URLs pages
		if ( Helper::is_plugin_admin_screen() ) {


			$wordpress_url = 'https://www.wordpress.org';
			$website_url   = 'https://www.kaizencoders.com';

			$UpdateURLS_plugin_name = ( UU()->is_pro() ) ? 'Update URLs PRO' : 'Update URLs';

			$footer_text = sprintf( __( '<span id="footer-thankyou">Thank you for creating with <a href="%1$s" target="_blank">WordPress</a> | %2$s <b>%3$s</b>. Developed by team <a href="%4$s" target="_blank">KaizenCoders</a></span>', 'update-urls' ), $wordpress_url, $UpdateURLS_plugin_name, KC_UU_PLUGIN_VERSION, $website_url );
		}

		return $footer_text;
	}


	/**
	 * Update plugin notice
	 *
	 * @param $data
	 * @param $response
	 *
	 * @since 1.2
	 */
	public function in_plugin_update_message( $data, $response ) {

		if ( isset( $data['upgrade_notice'] ) ) {
			printf(
				'<div class="update-message">%s</div>',
				wpautop( $data['upgrade_notice'] )
			);
		}
	}


}
