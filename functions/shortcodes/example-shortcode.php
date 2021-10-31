<?php
//[example-shortcode]
function themeprefix_example_shortcode_func( $atts ) {
  $a = shortcode_atts(
    array( 'app' => 'all' ), $atts, 'example-shortcode' );
  ob_start();
  $context        = Timber::get_context();
  $context['app'] = $a['app'];
  Timber::render( 'shortcodes/example-shortcode.twig', $context );
  $output = ob_get_clean();

  return $output;
}

add_shortcode( 'example-shortcode', 'themeprefix_example_shortcode_func' );
