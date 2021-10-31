<?php
/**
 * Hooks related to content structure and everything displayed on admin side
 */

//Enable background updates even if there is a git in the root (and there is!)
add_filter( 'automatic_updates_is_vcs_checkout', '__return_false', 1 );


// Remove unnecessary sections from the theme customizer
function themeprefix_edit_customizer( $wp_customize ) {
  $wp_customize->remove_section( 'colors' );
  $wp_customize->remove_section( 'custom_css' );
  // $wp_customize->remove_section( 'static_front_page' );
}

add_action( 'customize_register', 'themeprefix_edit_customizer' );


function themeprefix_add_custom_gutenberg_color_palette() {

  global $common_config;
  add_theme_support(
    'editor-color-palette',
    [

      [
        'name'  => esc_html__( 'White', 'themeprefix' ),
        'slug'  => 'white',
        'color' => $common_config->color_white,
      ],
      [
        'name'  => esc_html__( 'Black', 'themeprefix' ),
        'slug'  => 'black',
        'color' => '#000000',
      ]
    ]
  );
}

//add_action( 'after_setup_theme', 'themeprefix_add_custom_gutenberg_color_palette' );
