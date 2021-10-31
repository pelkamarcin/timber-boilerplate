<?php
/**
 * The template for displaying 404 pages (Not Found)
 */

$context = Timber::get_context();
// $context['title'] = __( 'Error','themeprefix' ) . ' 404 (' . __( 'page not found', 'themeprefix' ) . ')';
// $context['subtitle'] = sprintf( __( 'Sorry, we couldn\'t find what you\'re looking for', 'themeprefix' ), '<a href="' . get_bloginfo( 'url' ) . '">', '</a>' );
Timber::render( 'message.twig', $context );
