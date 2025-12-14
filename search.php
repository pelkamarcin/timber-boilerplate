<?php
/**
 * Search results page
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since   Timber 0.1
 */

$templates = array( 'templates/pages/search.twig', 'templates/pages/archive.twig', 'templates/pages/index.twig' );

$context          = Timber::context();
$context['title'] = __( 'Search results for ', 'sfy' ) . get_search_query();
$context['posts'] = Timber::get_posts();

Timber::render( $templates, $context );
