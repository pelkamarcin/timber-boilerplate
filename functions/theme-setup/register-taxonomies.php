<?php
/**
 * Registering various taxonomies
 */

function themeprefix_register_taxonomies() {
    //    register_taxonomy('states', array('portfolio'), array(
    //        'hierarchical' => true,
    //        'labels' => array(
    //            'name' => __('States', 'themeprefix'),
    //            'singular_name' => __('State', 'themeprefix')
    //        ),
    //        'show_in_rest' => true,
    //        'show_ui' => true,
    //        'show_admin_column' => true,
    //        'query_var' => true,
    //        'rewrite' => array('slug' => 'state'),
    //    ));
}

add_action( 'init', 'themeprefix_register_taxonomies' );
