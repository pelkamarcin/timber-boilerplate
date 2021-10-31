<?php
/**
 * Registering theme support
 */

add_theme_support( 'post-thumbnails' );
add_theme_support( 'menus' );
add_theme_support( 'widgets' );
add_theme_support( 'custom-header' );
add_theme_support( 'title-tag' );
add_theme_support( 'html5', [ 'search-form', 'caption', 'gallery' ] );


//Load localization domain
function themeprefix_load_localisation() {
  load_theme_textdomain( 'themeprefix', get_template_directory() . '/languages' );
}

add_action( 'after_setup_theme', 'themeprefix_load_localisation' );

function themeprefix_mime_types( $mimes ) {
  $mimes['svg'] = 'image/svg+xml';

  return $mimes;
}

add_filter( 'upload_mimes', 'themeprefix_mime_types' );
