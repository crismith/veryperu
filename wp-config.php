<?php
define( 'WP_CACHE', true ) ;
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
define( 'DB_NAME', 'wordpress' );

/** MySQL database username */
define( 'DB_USER', 'wordpress' );

/** MySQL database password */
define( 'DB_PASSWORD', 'd5a9c832d69f50d2c68e93f6aa08ebcfd20c007a3d64eecb' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'X2W$_}Vi@GMPW1QU%]K37;oA@V.HQ.>/;X 4GUM$(b%Sdak-(pvuoS:RR2/=YqGk' );
define( 'SECURE_AUTH_KEY',  'OmGDOgaSE(B_=]-xXpVNU5I4^bT5)uW+OQy-xBS<#0CBKRPr{s?T&P=J[ ^6(==3' );
define( 'LOGGED_IN_KEY',    '_#O$btL TDlj-]KJbJ6>]rkn,CVO#|p7 PN8UwX4}B_3<dpJ([h@LpK~pSHlgA).' );
define( 'NONCE_KEY',        'b=#M;v,SN!vq7pK5kiDRz fXJpsutpE39lP[H<AGzRS{U0dSZxUrYfE Z^zl@X&[' );
define( 'AUTH_SALT',        ' ;@Id3Z!h6jB]AiE?a2Be/UI)o,5{PFU{dV/FT[xcWC5{ti%`:ekiv[&6*Y`fsfb' );
define( 'SECURE_AUTH_SALT', 'aac6YwauN*q;.MSOyq3}9#my8B2H`C}r?Cyhcu}J238u}FYdE{3JMC$@rlFl9} J' );
define( 'LOGGED_IN_SALT',   'jE$caPN!&Qn`bmOX:oM@#3Pq% TjLQ6vADA6uxq<T7[OEbFcdJOg)K}&V#vK zTJ' );
define( 'NONCE_SALT',       'u3~$*kH,c>]H]qpn+t;4xr{~cenF>:gzrjo#.;fZh8G jI1gpxqxMCImQ}P6;iZ[' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

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
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );
