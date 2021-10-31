<?php
/**
 * Registering scripts and styles
 */

function themeprefix_script_enqueuer() {
  $scriptdeps_site = [ 'jquery' ];
  wp_enqueue_script( 'jquery' );

  wp_register_script( 'site', get_template_directory_uri() . '/js/scripts.min.js', $scriptdeps_site,
    '1.0.' . filemtime( get_template_directory() . '/js/scripts.min.js' ), true );
  wp_enqueue_script( 'site' );

  wp_localize_script( 'site', 'localized_strings', themeprefix_localized_strings() );
  wp_localize_script( 'site', 'script_data', themeprefix_script_data() );

  $style_path = get_stylesheet_directory_uri() . '/style.css';
  wp_register_style( 'screen', $style_path, '', '1.0.' . filemtime( get_template_directory() . '/style.css' ),
    'screen' );
  wp_enqueue_style( 'screen' );
}

add_action( 'wp_enqueue_scripts', 'themeprefix_script_enqueuer', 10 );


function themeprefix_defer_scripts( $tag, $handle, $src ) {

  // The handles of the enqueued scripts we want to defer
  $defer_scripts = [];

  if ( in_array( $handle, $defer_scripts ) ) {
    return '<script src="' . $src . '" defer="defer" type="text/javascript"></script>\n';
  }

  return $tag;
}

add_filter( 'script_loader_tag', 'themeprefix_defer_scripts', 10, 3 );
