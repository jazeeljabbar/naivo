<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', getenv('DB_NAME') ?: 'local' );

/** Database username */
define( 'DB_USER', getenv('DB_USER') ?: 'root' );

/** Database password */
define( 'DB_PASSWORD', getenv('DB_PASSWORD') ?: 'root' );

/** Database hostname */
define( 'DB_HOST', getenv('DB_HOST') ?: 'db' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          'nAo{_*^2&ArJS?O/{Zd!?^v*o;K~f&C{sv/<%z+LRM~y+%0h]%jD l3+zbs/Z-lq' );
define( 'SECURE_AUTH_KEY',   '<>=gcz,M[PD5v>zBq,N_Ce^t#>2D|X3blNK?99DUhp5t_%J`/YJvsCrhxGU+;QIW' );
define( 'LOGGED_IN_KEY',     'whRR:$i7z<9zd|`RClx%.I9S.4$R=jwnTx8)<-/8C=Niw-V8Vc6Ijhj&4A`=}FDD' );
define( 'NONCE_KEY',         '7Iq.KvJs:b,Uq`FhwZN=;p|uDax?N1]hf[ /[r4F+9g_5{y?a2cserip>m~%~qM*' );
define( 'AUTH_SALT',         'YX8 i]cXwEE|!`H73=q7z,e!=O#<ur <+GT0[~|/R9chb}q9= ?3.q3L;(0h&0mF' );
define( 'SECURE_AUTH_SALT',  '+`S^%o4cgQgTVC9AIcJ7FW2~~|{p}~U(M_-{}.F2,.]cGu@9wn`iOFwOA7a}rAV@' );
define( 'LOGGED_IN_SALT',    '4,02$>)M76^U8j1WO+~2uqyHPHGar{jQ!FUUa+aJI$:3U*LjR#g1$a^Y nwsM )3' );
define( 'NONCE_SALT',        ')hMbFd-qo1 |vWx},DZ$5]Qzd*WPjU2pi_G# zog|{ GUDgTAKRy}WjM<AkgflAY' );
define( 'WP_CACHE_KEY_SALT', 'B:?{ClE1-;UWgC.g)u36+Zm+ pxlt=7^7eq+V9y,F1|8_IpM:UWROM|4@g2=sE3Y' );

define('WP_DEBUG', false);
define('SCRIPT_DEBUG', false);

// Performance Optimizations
define('FS_METHOD', 'direct');
define('WP_MEMORY_LIMIT', '512M');
define('WP_MAX_MEMORY_LIMIT', '1024M');





/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'WP_ENVIRONMENT_TYPE', getenv('WP_ENVIRONMENT_TYPE') ?: 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
