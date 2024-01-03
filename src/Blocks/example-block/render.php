<?php

use Timber\Timber;

$context = Timber::context();

// Support custom "anchor" values.
$context['anchor'] = '';
if ( !empty( $block['anchor'] ) ) {
    $context['anchor'] = 'id="' . esc_attr( $block['anchor'] ) . '" ';
}

// Create class attribute allowing for custom "className" and "align" values.
$context['class_name'] = '';
if ( !empty( $block['className'] ) ) {
    $context['class_name'] .= ' ' . $block['className'];
}
if ( !empty( $block['align'] ) ) {
    $context['class_name'] .= ' u-align-' . $block['align'];
}
$context['block'] = $block;

Timber::render( 'blocks/' . $block['name'] . '.twig', $context );
