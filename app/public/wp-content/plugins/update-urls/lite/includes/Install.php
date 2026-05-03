<?php

namespace KaizenCoders\UpdateURLS;

class Install {

	/**
	 * DB updates and callbacks that need to be run per version.
	 *
	 * @since 1.2
	 * @var array
	 *
	 */
	private static $db_updates = array(

		'1.2.3' => array(
			'kc_uu_update_123_add_installed_on_option',
		),

	);

	/**
	 * Init Install/ Update Process
	 *
	 * @since 1.2
	 */
	public static function init() {

		if ( ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {

			add_action( 'admin_init', array( __CLASS__, 'check_version' ), 5 );
			add_action( 'admin_init', array( __CLASS__, 'install_actions' ) );
		}
	}

	/**
	 * Install if required
	 *
	 * @since 1.2
	 */
	public static function check_version() {

		$current_db_version = Option::get( 'db_version', '0.0.1' );

		// Get latest available DB update version
		$latest_db_version_to_update = self::get_latest_db_version_to_update();

		if ( version_compare( $current_db_version, $latest_db_version_to_update, '<' ) ) {
			self::install();
		}

	}

	/**
	 * Update
	 *
	 * @since 1.2
	 */
	public static function install_actions() {
		if ( ! empty( $_GET['do_update_kc_uu'] ) ) {
			check_admin_referer( 'kc_uu_db_update', 'kc_uu_db_update_nonce' );
			$from_db_version = ! empty( $_GET['from_db_version'] ) ? $_GET['from_db_version'] : '';

			self::delete_update_transient();

			if ( ! empty( $from_db_version ) ) {
				self::update_db_version( $from_db_version );
			}

			self::update( true );

		}

		if ( ! empty( $_GET['force_update_kc_uu'] ) ) {
			check_admin_referer( 'kc_uu_force_db_update', 'kc_uu_force_db_update_nonce' );
			self::update();
			wp_safe_redirect( admin_url( 'tools.php?page=update-urls' ) );
			exit;
		}
	}

	/**
	 * Begin Installation
	 *
	 * @since 1.2
	 */
	public static function install() {

		if ( ! is_blog_installed() ) {
			return;
		}

		// Check if we are not already running this routine.
		if ( 'yes' === Cache::get_transient( 'installing' ) ) {
			return;
		}

		if ( self::is_new_install() ) {
			// If we made it till here nothing is running yet, lets set the transient now.
			Cache::set_transient( 'installing', 'yes', MINUTE_IN_SECONDS * 10 );

			Helper::maybe_define_constant( 'KC_UU_INSTALLING', true );

			// Create Default Option
			self::create_options();

			self::update_db_version( KC_UU_PLUGIN_VERSION );

			Option::add( 'installed_on', time(), true );
		}

		self::maybe_update_db_version();

		Cache::delete_transient( 'installing' );
	}

	/**
	 * Delete Update Transient
	 *
	 * @since 1.2
	 */
	public static function delete_update_transient() {
		global $wpdb;

		Option::delete( 'update_processed_tasks' );
		Option::delete( 'update_tasks_to_process' );

		$transient_like               = $wpdb->esc_like( '_transient_kc_uu_update_' ) . '%';
		$updating_like                = $wpdb->esc_like( '_transient_kc_uu_updating' ) . '%';
		$last_sent_queue_like         = '%' . $wpdb->esc_like( '_last_sending_queue_batch_run' ) . '%';
		$running_migration_queue_like = '%' . $wpdb->esc_like( '_running_migration_for_' ) . '%';
		$db_migration_queue_like      = '%' . $wpdb->esc_like( 'kc_uu_updater_batch_' ) . '%';

		$query = "DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '{$transient_like}' OR option_name LIKE '{$updating_like}' OR option_name LIKE '{$last_sent_queue_like}' OR option_name LIKE '{$running_migration_queue_like}' OR option_name LIKE '{$db_migration_queue_like}'";

		$wpdb->query( $query );

	}

	/**
	 * Is this new Installation?
	 *
	 * @return bool
	 *
	 * @since 1.2
	 */
	public static function is_new_install() {
		/**
		 * We are storing us_db_version if it's new installation.
		 *
		 */
		return is_null( Option::get( 'db_version', null ) );
	}

	/**
	 * Get latest db version based on available updates.
	 *
	 * @return mixed
	 *
	 * @since 1.2
	 */
	public static function get_latest_db_version_to_update() {

		$updates         = self::get_db_update_callbacks();
		$update_versions = array_keys( $updates );
		usort( $update_versions, 'version_compare' );

		return end( $update_versions );
	}

	/**
	 * Require DB updates?
	 *
	 * @return bool
	 *
	 * @since 1.2
	 */
	private static function needs_db_update() {

		$current_db_version = Helper::get_db_version();

		$latest_db_version_to_update = self::get_latest_db_version_to_update();

		return ! is_null( $current_db_version ) && version_compare( $current_db_version, $latest_db_version_to_update, '<' );
	}

	/**
	 * Check whether database update require? If require do update.
	 *
	 * @since 1.2
	 */
	private static function maybe_update_db_version() {
		if ( self::needs_db_update() ) {
			if ( apply_filters( 'kc_uu_enable_auto_update_db', true ) ) {
				self::update();
			}
		}
	}

	/**
	 * Get all database updates
	 *
	 * @return array
	 *
	 * @since 1.2
	 */
	public static function get_db_update_callbacks() {
		return self::$db_updates;
	}

	/**
	 * Do database update.
	 *
	 * @param bool $force
	 *
	 * @since 1.2
	 */
	private static function update( $force = false ) {

		// Check if we are not already running this routine.
		if ( ! $force && 'yes' === Cache::get_transient( 'updating' ) ) {
			return;
		}

		Cache::set_transient( 'updating', 'yes', MINUTE_IN_SECONDS * 5 );

		$current_db_version = Helper::get_db_version();

		$tasks_to_process = Option::get( 'update_tasks_to_process', array() );

		// Get all tasks processed
		$processed_tasks = Option::get( 'update_processed_tasks', array() );

		// Get al tasks to process
		$tasks = self::get_db_update_callbacks();

		if ( count( $tasks ) > 0 ) {

			foreach ( $tasks as $version => $update_callbacks ) {

				if ( version_compare( $current_db_version, $version, '<' ) ) {
					foreach ( $update_callbacks as $update_callback ) {
						if ( ! in_array( $update_callback, $tasks_to_process ) && ! in_array( $update_callback, $processed_tasks ) ) {
							$tasks_to_process[] = $update_callback;
						} else {
						}
					}

					// Update db version on every update run
					self::update_db_version( $version );
				}
			}
		}

		if ( count( $tasks_to_process ) > 0 ) {

			Option::set( 'update_tasks_to_process', $tasks_to_process );

			self::dispatch();

		} else {
			Cache::delete_transient( 'updating' );
		}

	}

	/**
	 * Dispatch database updates.
	 *
	 * @since 1.2
	 */
	public static function dispatch() {

		$batch = Option::get( 'update_tasks_to_process', array() );

		if ( count( $batch ) > 0 ) {

			$current_memory_limit = @ini_get( 'memory_limit' );

			// We may require lots of memory
			@ini_set( 'memory_limit', '-1' );

			// It may take long time to process database update.
			// So, increase execution time
			@set_time_limit( 360 );
			@ini_set( 'max_execution_time', 360 );

			foreach ( $batch as $key => $value ) {

				$is_value_exists = true;

				$processed_tasks = Option::get( 'update_processed_tasks', array() );

				$task = false; // By default it's set to false

				// Check whether the tasks is already processed? If not, process it.
				if ( ! in_array( $value, $processed_tasks ) ) {
					$is_value_exists = false;
					$task            = (bool) self::task( $value );
				} else {
					unset( $batch[ $key ] );
				}

				if ( false === $task ) {

					if ( ! $is_value_exists ) {
						$processed_tasks[] = $value;
						Option::set( 'update_processed_tasks', $processed_tasks );
					}

					unset( $batch[ $key ] );
				}

			}

			Option::set( 'update_tasks_to_process', $batch );

			@ini_set( 'memory_limit', $current_memory_limit );
		}

		//Delete update transient
		Cache::delete_transient( 'updating' );
	}

	/**
	 * Run individual database update.
	 *
	 * @param $callback
	 *
	 * @return bool|callable
	 *
	 * @since 1.2
	 */
	public static function task( $callback ) {

		include_once dirname( __FILE__ ) . '/Upgrade/update-functions.php';

		$result = false;

		if ( is_callable( $callback ) ) {

			$result = (bool) call_user_func( $callback );

			if ( $result ) {
				//$logger->info( sprintf( '%s callback needs to run again', $callback ), self::$logger_context );
			} else {
				//$logger->info( sprintf( '--- Finished Task - %s ', $callback ), self::$logger_context );
			}
		} else {
			//$logger->notice( sprintf( '--- Could not find %s callback', $callback ), self::$logger_context );
		}

		return $result ? $callback : false;
	}

	/**
	 * Update DB Version & DB Update history
	 *
	 * @param null $version
	 *
	 * @since 1.2
	 */
	public static function update_db_version( $version = null ) {

		$latest_db_version_to_update = self::get_latest_db_version_to_update();

		Option::set( 'db_version', is_null( $version ) ? $latest_db_version_to_update : $version );

		if ( ! is_null( $version ) ) {

			$db_update_history_option = 'db_update_history';

			$db_update_history_data = Option::get( $db_update_history_option, array() );

			$db_update_history_data[ $version ] = Helper::get_current_date_time();

			Option::set( $db_update_history_option, $db_update_history_data );
		}
	}

	/**
	 * Create default options while installing
	 *
	 * @since 1.2
	 */
	private static function create_options() {

		$options = self::get_options();

		if ( Helper::is_forechable( $options ) ) {

			foreach ( $options as $option => $values ) {
				Option::add( $option, $values['default'], false );
			}
		}
	}

	/**
	 * Get default options
	 *
	 * @return array
	 *
	 * @since 1.2
	 */
	public static function get_options() {
		return array();
	}

	/**
	 * Create Tables
	 *
	 * @param null $version
	 *
	 * @since 1.2
	 */
	public static function create_tables( $version = null ) {

		global $wpdb;

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		if ( is_null( $version ) ) {
			$schema_fn = 'get_schema';
		} else {
			$v         = str_replace( '.', '', $version );
			$schema_fn = 'get_' . $v . '_schema';
		}

		$wpdb->hide_errors();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( self::$schema_fn( $collate ) );
	}

	/**
	 * @param string $collate
	 *
	 * @return string
	 *
	 * @since 1.2
	 */
	private static function get_schema( $collate = '' ) {

		$tables = self::get_100_schema( $collate );

		return $tables;
	}

	/**
	 * Create files/ directory
	 *
	 * @since 1.2
	 */
	public static function create_files() {

		// Want to bypass creation of files?
		if ( apply_filters( 'kc_uu_install_skip_create_files', false ) ) {
			return;
		}

		$files = array(
			array(
				'base'    => KC_UU_LOG_DIR,
				'file'    => '.htaccess',
				'content' => 'deny from all',
			),
			array(
				'base'    => KC_UU_LOG_DIR,
				'file'    => 'index.html',
				'content' => '',
			),
		);

		foreach ( $files as $file ) {
			if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
				$file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' );
				if ( $file_handle ) {
					fwrite( $file_handle, $file['content'] );
					fclose( $file_handle );
				}
			}
		}
	}

}

Install::init();