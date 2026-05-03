<?php
/**
 * SC translation files fetching from server
 *
 * @link
 * @since 3.2.0
 *
 * @package  Wt_Smart_Coupon
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Wbte_Sc_Language_Translation' ) ) {
	class Wbte_Sc_Language_Translation {

		private static $instance   		= null;
		public $text_domain        		= '';
		public $plugin_folder_name 		= '';
		public $plugin_prefix	   		= '';
		public $translation_api_url		= '';
		public $plugin_version			= '';
		public $translation_event_name 	= '';
		public $translation_log_name	= '';
		public $default_language_folder = '';
		public $default_block_language_folder = '';

		/**
         * Constructor function of the class
         */
        public function __construct() {

			if ( true === $this->proceed_the_translation() ) {

				$this->text_domain       		= 'wt-smart-coupons-for-woocommerce-pro'; // text domain slug of the plugin.
				$this->plugin_folder_name 		= 'wt-smart-coupon-pro'; // main folder name of the plugin.
				$this->plugin_prefix			= 'wt_sc_'; // unique plugin prefix used for event, filter and log name. do not use `-`, instead add `_`.
				$this->translation_api_url		= 'https://cdn.webtoffee.com/wt-plugins-translation/translation-api.php';
				$this->translation_event_name	= "{$this->plugin_prefix}update_translation"; // event name for translation. dont forget to delete the event upon deactivation of the plugin.
				$this->translation_log_name		= "{$this->plugin_prefix}translation"; // log name for translation.
				$this->plugin_version			= defined( 'WEBTOFFEE_SMARTCOUPON_VERSION' ) ? WEBTOFFEE_SMARTCOUPON_VERSION : '3.2.0';
				$this->default_language_folder	= WP_CONTENT_DIR . '/languages/plugins/' . $this->plugin_folder_name . '/'; // wp-content/languages/pluginfoldername/
				$this->default_block_language_folder = $this->default_language_folder . '/blocks/'; // wp-content/languages/pluginfoldername/blocks/


				add_action( $this->translation_event_name , array( $this, 'update_translation' ), 10, 1 ); // action hook for updating translation for this plugin from action scheduler.
				add_action( 'upgrader_process_complete', array( $this, 'update_translation_upon_plugin_update' ), 10, 2 ); // to schedule the translation event when doing update of the plugin.
				add_action( 'update_option_WPLANG', array( $this, 'update_translation_upon_switching_language_wplang' ), 10, 2 ); // hook to create the translation schedule event when updating the language in site settings page.
				add_action( 'profile_update', array( $this, 'update_translation_upon_switching_language_in_user_profile' ), 10, 2 ); // hook to create the translation schedule event when updating the language in user profile settings page.
				add_action( 'admin_init', array( $this, 'update_translation_upon_uploading_plugin_through_ftp' ) ); // hook to create the translation schedule event when uploading the file through FTP.
			}
		}

		/**
         * Get Instance
         *
         * @return object Class instance
         */
        public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new Wbte_Sc_Language_Translation();
			}
			return self::$instance;
		}

		/**
		 *  To check if the transation needs to be proceeded
		 *
		 *  - if other translation plugins are active in site, then no need the translation
		 *  - Added the filter to force the translation update, even there is other translation plugin
         * 
         * @since 3.2.0
		 *
		 * @return boolean
		 */
		public function proceed_the_translation() {
			if ( true === apply_filters( $this->plugin_prefix . 'force_transtion_update', true ) ||
			( ! is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) && ! is_plugin_active( 'wpml-string-translation/plugin.php' ) )
			) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * To get the available languages in the format of input to the translation schedule event.
		 *
         * @since 3.2.0
         * 
		 * @param string $locale
		 * @return array
		 */
		public function get_languages( $locale = '' ) {
			if ( ! empty( $locale ) ) {
				$available_languages = array( $locale );
			} else {
				if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) && is_plugin_active( 'wpml-string-translation/plugin.php' ) ) {
					$wpml_languages      = apply_filters( 'wpml_active_languages', null, 'orderby=id&order=desc' );
					$available_languages = array_unique( array_filter( array_column( $wpml_languages, 'default_locale' ) ) );
				} else {
					$available_languages = get_available_languages();
				}
			}
			return $available_languages;
		}

		/**
		 * To schedule the event to fetch the translation files when activating, updating the plugin and when user update the language
		 *
         * @since 3.2.0
         * 
		 * @param object $upgrader_object
		 * @param array $options
		 * @return void
		 */
		public function update_translation_upon_plugin_update( $upgrader_object, $options ) {
			$current_plugin_path_name = $this->plugin_folder_name . '/' . $this->plugin_folder_name . '.php'; // plugin folder and plugin main file name.

			if ( 'update' === $options['action']
			&& 'plugin' === $options['type']
			&& isset( $options['plugins'] )
			&& is_array( $options['plugins'] )
			&& ! empty( $options['plugins'] ) ) {
				if ( in_array( $current_plugin_path_name, $options['plugins'], true ) ) {
					$required_lang	 = array();
					$required_lang[] = $this->get_languages();
					$this->create_event_for_translation( $required_lang );
				}
			}
		}

		/**
		 * To schedule the event to fetch the translation files of choosen language, when changing in site settings
		 *
         * @since 3.2.0
         * 
		 * @param string $old_lang
		 * @param string $new_lang
		 * @return void
		 */
		public function update_translation_upon_switching_language_wplang( $old_lang, $new_lang ) {
			if ( ! empty( $new_lang ) ) {
				$required_lang	 = array();
				$required_lang[] = array( $new_lang );
				$this->create_event_for_translation( $required_lang );
			}
		}

		/**
		 * To schedule the event to fetch the translation files of choosen language, when changing in the user profile settings
		 *
         * @since 3.2.0
         * 
		 * @param int|string $old_lang
		 * @param array $new_lang
		 * @return void
		 */
		public function update_translation_upon_switching_language_in_user_profile( $user_id, $old_user_data ) {
			// Check if language preference is updated.
			if ( isset( $_POST['user_lang'] ) ) {
				$required_lang	 = array();
				// Language preference has been updated.
				$new_lang        = sanitize_text_field( $_POST['user_lang'] );
				$required_lang[] = array( $new_lang );
				$this->create_event_for_translation( $required_lang );
			}
		}

		/**
		 * To schedule the event to fetch the translation files when uploading the plugin files through FTP.
		 *
         * @since 3.2.0
         * 
		 * @return void
		 */
		public function update_translation_upon_uploading_plugin_through_ftp() {
			if ( 
				( $this->plugin_version !== get_option( $this->text_domain . '_translation_version' ) ) 
				|| ( isset( $_GET['page'] ) && 'wt-smart-coupon-for-woo' === sanitize_text_field( $_GET['page'] ) &&  
				isset( $_GET['wbte_sc_force_translation'] )
				) 
			) {
				$required_lang	 = array();
				$required_lang[] = $this->get_languages();
				$this->create_event_for_translation( $required_lang );
			}
		}

		/**
		 * Create the event scheduler for fetching the translation files
		 *
         * @since 3.2.0
         * 
		 * @param array $languages
		 * @return void
		 */
		public function create_event_for_translation( $languages ) {
			if ( function_exists( 'as_next_scheduled_action' ) && function_exists( 'as_schedule_single_action' ) ) {
				if ( false === as_next_scheduled_action( $this->translation_event_name ) ) {
					as_schedule_single_action( time(), $this->translation_event_name, $languages );
				}
			}
		}

		/**
		 * Function to fetch the language file content and put them in local language directory
		 *
         * @since 3.2.0
         * 
		 * @param array $available_languages
		 * @return void
		 */
		public function update_translation( $available_languages ) {

			// Initialize the WordPress filesystem.
			if ( ! function_exists( 'WP_Filesystem' ) || ! function_exists( 'get_filesystem_method' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}

			// Check if the filesystem is already initialized and get_filesystem_method is direct.
			if ( WP_Filesystem() && function_exists( 'get_filesystem_method' ) && 'direct' === get_filesystem_method() ) {
				$wp_upload_method_set	= true;
			} else {
				$wp_upload_method_set	= false;
			}

			global $wp_filesystem;
			$logger_res_array    = array();
			$translation_api_url = $this->translation_api_url;
			$wp_lang_dir         = $this->default_language_folder;
			$wp_block_lang_dir   = $this->default_block_language_folder;

			// create language directory if it is not available.
			if ( ! is_dir( $wp_lang_dir ) ) {
				wp_mkdir_p( $wp_lang_dir );
			}

			// create block language directory if it is not available.
			if ( ! is_dir( $wp_block_lang_dir ) ) {
				wp_mkdir_p( $wp_block_lang_dir );
			}

			// prepare api input values.
			$body = array(
				'plugin_name'    => $this->plugin_folder_name,
				'text_domain'    => $this->text_domain,
				'language_codes' => $available_languages,
				'version'        => $this->plugin_version,
			);

			$logger_res_array[] = array( 'inputs' => $body );
			$track_log          = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) || apply_filters( $this->plugin_prefix . 'enable_translation_log', false );
			if ( true === $track_log ) {
				$body = apply_filters( $this->plugin_prefix . 'translation_api_body', $body );
			}

			// Make the POST request using wp_remote_post().
			$response = wp_remote_post(
				$translation_api_url,
				array(
					'body' => $body,
				)
			);

			// Check if the request was successful.
			if ( ! is_wp_error( $response ) && 200 === $response['response']['code'] ) {

				// Language files retrieved successfully.
				$response = json_decode( wp_remote_retrieve_body( $response ), true );
				if ( ! empty( $response ) ) {
					if ( isset( $response['language_files'] ) && ! empty( $response['language_files'] ) ) {

						if ( is_array( $response['language_files'] ) ) {
							foreach ( $response['language_files'] as $language_code => $file_content ) {
								// po file.
								if ( isset( $file_content['po'] ) && ! empty( $file_content['po'] ) ) {
									$language_file_contents_po	= base64_decode( $file_content['po'] );
									$destination_file_po      	= "{$wp_lang_dir}{$this->text_domain}-{$language_code}.po";
									
                                    $upload_po = ( true === $wp_upload_method_set ) ? $wp_filesystem->put_contents( $destination_file_po, $language_file_contents_po, FS_CHMOD_FILE ) : file_put_contents( $destination_file_po, $language_file_contents_po ) ;

                                    $logger_res_array[] = ( false !== $upload_po ) ? 'Success PO file - ' . esc_html( $language_code ) : 'failed to upload the file for ' . esc_html( $language_code );
								} else {
									$logger_res_array[] = 'empty content in po file of ' . esc_html( $language_code );
								}

								// mo file.
								if ( isset( $file_content['mo'] ) && ! empty( $file_content['mo'] ) ) {
									$language_file_contents_mo = base64_decode( $file_content['mo'] );
									$destination_file_mo       = "{$wp_lang_dir}{$this->text_domain}-{$language_code}.mo";
									
                                    $upload_mo = ( true === $wp_upload_method_set ) ? $wp_filesystem->put_contents( $destination_file_mo, $language_file_contents_mo, FS_CHMOD_FILE ) : file_put_contents( $destination_file_mo, $language_file_contents_mo ) ;

                                    $logger_res_array[] = ( false !== $upload_mo ) ? 'Success MO file - ' . esc_html( $language_code ) : 'failed to upload the file for ' . esc_html( $language_code );
								} else {
									$logger_res_array[] = 'empty content in mo file of ' . esc_html( $language_code );
								}

								// json files.
								if ( isset( $file_content['json'] ) && ! empty( $file_content['json'] ) ) {
									
									// Delete existing JSON files in block language directory.
									if ( is_dir( $wp_block_lang_dir ) ) {
										$files = glob( "{$wp_block_lang_dir}{$this->text_domain}-{$language_code}-*.json" );
										if ( ! empty( $files ) ) {
											foreach ( $files as $file ) {
												if ( true === $wp_upload_method_set ) {
													$wp_filesystem->delete( $file );
												} else {
													unlink( $file );
												}
											}
										}
									}

									if ( is_array( $file_content['json'] ) ) {
										foreach ( $file_content['json'] as $json_data ) {
											if ( ! empty( $json_data ) ) {
												$json_content = json_encode( $json_data['content'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
												$json_filename = $json_data['filename'];
												$destination_file_json = "{$wp_block_lang_dir}{$json_filename}";

												$upload_json = ( true === $wp_upload_method_set ) 
													? $wp_filesystem->put_contents( $destination_file_json, $json_content, FS_CHMOD_FILE ) 
													: file_put_contents( $destination_file_json, $json_content );

												$logger_res_array[] = ( false !== $upload_json )
													? 'Success JSON file - ' . esc_html( $json_filename )
													: 'failed to upload JSON file ' . esc_html( $json_filename );
											}
										}
									}
								}
							}
						}
					}

					if ( isset( $response['message'] ) && ! empty( $response['message'] ) ) {
						$logger_res_array[] = $response['message'];
					}
				} else {
					$logger_res_array[] = $response;
				}
			} else {
				// Error occurred, handle it.
				$error_message      = is_wp_error( $response ) ? $response->get_error_message() : 'Unknown error';
				$logger_res_array[] = $error_message;
			}

			if ( function_exists( 'wc_get_logger' ) && ! empty( $logger_res_array ) && true === $track_log ) {
				$logger = wc_get_logger();
				$logger->info( wc_print_r( $logger_res_array, true ), array( 'source' => $this->translation_log_name ) );
			}

			// update the version of the translation files with current plugin version.
			update_option( $this->text_domain . '_translation_version', esc_html( $this->plugin_version ) );
		}
	}
	new Wbte_Sc_Language_Translation();
}
