<?php
define( 'WP_CACHE', true );


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
define( 'DB_NAME', 'portfolio' );

/** Database username */
define( 'DB_USER', 'wp' );

/** Database password */
define( 'DB_PASSWORD', 'wp' );

/** Database hostname */
define( 'DB_HOST', 'mysql:3309' );

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
define( 'AUTH_KEY',          '?ttm0nXzA9~44:%3kAc _BP59m|<.RA74=I3&NSZS:D6eYzmtSW= -w?bEyiqPtb' );
define( 'SECURE_AUTH_KEY',   'N 2;Pbi0t+g-C K3] *qs/x~Zl`o`B^&CJF{%o6A)B tYjk93HWp:kL^,V<#>0?g' );
define( 'LOGGED_IN_KEY',     '45#{X0p;|)R7,q;_|;<IeK|zE=~Uk0x?_s/!ZOQih:(Fe_6aEFc(*%-.|Nsvks#g' );
define( 'NONCE_KEY',         '[7a AF4~h&i^h]?Mp%<1x#kZ% +$OU=C`5eu0>Q[#FsX9jdOm=CnL]dJB;*M1GKr' );
define( 'AUTH_SALT',         '`=;52C! { :`X,y`eKeVIqh@!I<?4FU>x~T#RSSfWAPX@zOBY)]fUdbCJ@M?69>5' );
define( 'SECURE_AUTH_SALT',  '&)>5UatY$iD1dPu4GI5z`|,pJ|:-h.Z,40uoGj~5zACQe[P:7Mz^!,2?[:PxLW==' );
define( 'LOGGED_IN_SALT',    '~F(@=S=<Z;)|u_w$uO*T1ZA,?u{@NBL^|v_@%cK[oSr}c}j+7Xn!,~a;+B+gGooo' );
define( 'NONCE_SALT',        '5$_J{-.N0n.Xh2slYP!NI$MvhM,O/5}B$LCjgTl+7nz`+z85q|i)hj)]GbZ@CAeX' );
define( 'WP_CACHE_KEY_SALT', ';7qO:(MeGkUPdEW#l3m;M)Sxy|Wo,F[P/I{Kd7f^([(?rG<na$`K~CwLR<@J3L)x' );


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
	define( 'WP_DEBUG', true );
	define( 'WP_DEBUG_LOG', true );
	define( 'WP_DEBUG_DISPLAY', false );
}

define( 'FS_METHOD', 'direct' );
define( 'COOKIEHASH', '2ca5c61c21a5101cc0d1c45ac52a92bf' );
define( 'WP_AUTO_UPDATE_CORE', 'minor' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
