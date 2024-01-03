<?php
/**
 * Blog posts page
 */

$context = Timber::context();

// get queried loop
$timber_posts = Timber::get_posts( false );
$context['posts'] = $timber_posts;

$context['blogpage'] = Timber::get_post( get_option( 'page_for_posts', true ) );

Timber::render( 'blog.twig', $context );
