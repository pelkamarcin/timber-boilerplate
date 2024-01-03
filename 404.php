<?php
/**
 * Singular pages (pages and posts of any type)
 */

$context         = Timber::context();
$timber_post = Timber::get_post( false );
$context['post'] = $timber_post;

Timber::render( '404.twig', $context );
