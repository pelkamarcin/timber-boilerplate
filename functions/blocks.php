<?php
// Check if function exists and hook into setup.
if ( function_exists( 'acf_register_block_type' ) ) {
  add_action( 'acf/init', 'themeprefix_register_acf_block_types' );
}

function themeprefix_register_acf_block_types() {

//  acf_register_block_type( array(
//    'name'            => 'hero',
//    'title'           => __( 'Hero', 'themeprefix' ),
//    'render_callback' => 'themeprefix_hero_block_render_callback',
//    'category'        => 'themeprefix-blocks',
//    'icon'            => 'slides',
//    'keywords'        => array( 'hero', 'slider', 'image', 'movie' ),
////    'supports'        => array(
////      'align' => false,
////      'mode'  => false,
////      'jsx'   => true,
////    ),
//  ) );


}

function themeprefix_base_block_render_callback( $block, $content = '', $is_preview = false ) {
  $context = Timber::context();

  // Store block values.
  $context['block'] = $block;

  // Store field values.
  $context['fields'] = get_fields();

  // Store $is_preview value.
  $context['is_preview'] = $is_preview;

  return $context;
}

function themeprefix_hero_block_render_callback( $block, $content = '', $is_preview = false ) {
  $context = themeprefix_base_block_render_callback( $block, $content, $is_preview );

  Timber::render( 'block/hero.twig', $context );
}
