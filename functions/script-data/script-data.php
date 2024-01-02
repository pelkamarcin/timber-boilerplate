<?php
/**
 * Returns an array with various PHP/Timber data for inserting into JS
 */


function themeprefix_script_data() {
    $script_data = [
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'theme_link' => get_template_directory_uri(),
        // 'svg_icon' => Timber::compile_string( "{% include 'partials/image-svg-sprite.twig' with {'id': 'icon_id', 'classes': ['c-icon'], 'theme_link': theme_link} %}", [ 'theme_link' => get_template_directory_uri() ] ),
    ];
    if ( function_exists( 'get_fields' ) ) {
        $script_data['options'] = get_fields( 'option' );
    }
    return $script_data;
}
