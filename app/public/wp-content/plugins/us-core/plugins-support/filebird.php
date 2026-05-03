<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * FileBird Support
 *
 * @link https://wordpress.org/plugins/filebird/
 */

if ( ! function_exists( 'FileBird\\init' ) ) {
	return;
}

if ( ! function_exists( 'usb_filebird_enqueue_scripts' ) ) {
	/**
	 * Add FileBird assets to the USBuilder page
	 * TODO: Find the best asset connection solution
	 */
	function usb_filebird_enqueue_scripts() {
		if ( class_exists( '\\FileBird\\Controller\\Folder' ) ) {
			$instance = \FileBird\Controller\Folder::getInstance();
			if ( method_exists( $instance, 'enqueueAdminScripts' ) ) {
				$instance->enqueueAdminScripts( sprintf( '%s.php', get_current_screen()->id ) );
			}
		}
	}
	add_action( 'usb_enqueue_assets_for_builder', 'usb_filebird_enqueue_scripts', 1 );
}
