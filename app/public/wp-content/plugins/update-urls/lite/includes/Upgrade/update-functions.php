<?php
/**
 * Functions for updating data, used by the background updater.
 */

defined( 'ABSPATH' ) || exit;

use KaizenCoders\UpdateURLS\Option;

/* --------------------- 1.0.0 (Start)--------------------------- */

/**
* Update DB version
 *
 * @since 1.0.0
 */
function kc_uu_update_123_add_installed_on_option() {
	Option::add( 'installed_on', time(), true );
}

/* --------------------- 1.0.0 (End)--------------------------- */