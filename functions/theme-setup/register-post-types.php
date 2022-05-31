<?php
/**
 * Registering various content types
 */

function themeprefix_register_post_types() {
    //    register_post_type('portfolio',
    //                       array(
    //                           'labels' => array(
    //                               'name' => __('Portfolio', 'themeprefix'),
    //                               'singular_name' => __('Portfolio', 'themeprefix'),
    //                           ),
    //                           'public' => true,
    //                           'has_archive' => false,
    //                           'show_in_rest' => true,
    //                           'supports' => array('title', 'editor', 'thumbnail',),
    //                           'taxonomies' => array('states'),
    //                           'capability_type' => 'post'
    //                       )
    //    );
}

add_action( 'init', 'themeprefix_register_post_types' );
