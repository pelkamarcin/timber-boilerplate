<?php
/**
 * Singular pages (pages and posts of any type)
 */

$context         = Timber::get_context();
$timber_post     = Timber::query_post(false, 'CommonPost');
$context['post'] = $timber_post;

Timber::render('404.twig', $context);
