<?php
/**
 * Singular pages (pages and posts of any type)
 */

$context         = Timber::context();
$timber_post = Timber::get_post( false );
$context['post'] = $timber_post;

if ( post_password_required( $timber_post->ID ) ) {
    $context['password_form'] = get_the_password_form();
    Timber::render( 'templates/pages/single-password.twig', $context );
} elseif ( get_post_type( $timber_post ) === 'page' ) {
    Timber::render( [ 'templates/pages/page.twig', 'templates/pages/singular.twig' ], $context );
} else {
    Timber::render( [
                        'templates/pages/single-' . $timber_post->ID . '.twig',
                        'templates/pages/single-' . $timber_post->post_type . '.twig',
                        'templates/pages/single.twig',
                        'templates/pages/singular.twig',
                    ], $context );
}
