<?php
/**
 * Template name: Example custom template
 */

$context         = Timber::context();
$timber_post = Timber::get_post( false );
$context['post'] = $timber_post;

Timber::render( 'custom-templates/example.twig', $context );
