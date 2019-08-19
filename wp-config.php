<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'a0217392_app_wordpress_1');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', '');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         '@MOhB^UMdnjxg33aWr2EMa*OR8MqjTX9&5og2mUCOv6oPuUg7v)diF&1#@kJMkK8');
define('SECURE_AUTH_KEY',  'W2CKo6S96z3eMqkIcFI^8SGaiq0xibPlSCb*qucM2(2S(wZtX6tbsXModv@dk096');
define('LOGGED_IN_KEY',    'mS8J9d@&G&H#u#W9PA)%i5lLzRtYjzr)K*xEggoYvii8(S1&^o%21*sqg1l%OR2t');
define('NONCE_KEY',        ')(^vJ2SWOpW(s^Wf1BxzEAoj4SlSVRZlALSocwvfSfPF3Ey3mJIdh(HxFmeNa#yi');
define('AUTH_SALT',        'J*V6(Gs^%f7AZ#^SiMDC(dXuz6rQUNdrtic(8WY9)ieuxFav#rh*a6paLuz@G2WE');
define('SECURE_AUTH_SALT', 'BpTwEbeFhEmtLOyuOhCe#2U9fq*Y7yCpKa2w5EUNVlcUgRMnYbN63jHDDs7TZ7AT');
define('LOGGED_IN_SALT',   'YODBfluch5tu&*yh(&H&PHpbg&XBnW9R96dG)##^m8vusSQsR^rQcvDThuE0uT2w');
define('NONCE_SALT',       'Pi#P)Ebc()Cipa6PcmUiYe@2rW1sRVQmjQcOn&LWeNeaGyELxa@CsbpD^V5Ay94u');
/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');

define( 'WP_ALLOW_MULTISITE', true );

define ('FS_METHOD', 'direct');
// remove x-pingback HTTP header
add_filter('wp_headers', function($headers) {
  unset($headers['X-pingback']);
  return $headers;
});
// disable pingbacks
  add_filter( 'xmlrpc_methods', function( $methods ) {
  unset( $methods['pingback.ping'] );
  return $methods;
});
