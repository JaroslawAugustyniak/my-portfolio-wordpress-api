<?php
/**
 * better functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package better
 */
add_filter('show_admin_bar', '__return_false');
if ( ! defined( '_S_VERSION' ) ) {
	// Replace the version number of the theme on each release.
	define( '_S_VERSION', '1.0.2' );
}


function pr($data) {
	echo '<pre>';
	print_r($data);
	echo '</pre>';
}


function moje_theme_setup() {
    // Włącza obsługę obrazków wyróżniających
    add_theme_support( 'post-thumbnails' );

    // Włącza obsługę logo w personalizatorze (Tożsamość witryny)
    add_theme_support( 'custom-logo', array(
        'height'      => 250,
        'width'       => 250,
        'flex-width'  => true,
        'flex-height' => true,
    ) );
}

add_action( 'after_setup_theme', 'moje_theme_setup' );
