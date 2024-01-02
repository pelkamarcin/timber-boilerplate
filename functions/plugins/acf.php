<?php
/**
 * Functions related to ACF
 */

// Add options pages

function themeprefix_add_options_pages() {
    if ( function_exists( 'acf_add_options_page' ) ) {

        acf_add_options_page(
            [
                'page_title' => __( 'Site Settings', 'themeprefix' ),
                'menu_slug' => 'site-settings',
                'capability' => 'edit_posts',
                'redirect' => false,
            ]
        );

    }
}

add_action( 'acf/init', 'themeprefix_add_options_pages' );


function themeprefix_acf_global_settings_context( $context ) {

    if ( function_exists( 'get_fields' ) ) {
        // make ACF options page fields available in every Twig file
        $context['options'] = get_fields( 'option' );
    }
    return $context;
}

add_filter( 'timber_context', 'themeprefix_acf_global_settings_context' );


// Wraps field definitions in __() function when exporting to PHP via ACF admin UI
function themeprefix_acf_localize_fields_when_exporting() {
    acf_update_setting( 'l10n_textdomain', 'themeprefix' );
}

add_action( 'acf/init', 'themeprefix_acf_localize_fields_when_exporting' );


//function themeprefix_acf_init() {
//    acf_update_setting( 'google_api_key', get_field( 'google_maps_key', 'option' ) );
//}
//
//add_action( 'acf/init', 'themeprefix_acf_init' );
//
//add_filter( 'acf/settings/google_api_key', function () {
//    return get_field( 'google_maps_key', 'option' );
//} );


// Disable "Custom fields" menu in admin
// You need to load environment to WP in order for this to work
// Eg. by adding autoload.php from a site' vendor folder to wp-config.php
//add_filter(
//  'acf/settings/show_admin',
//  function() {
//    return getenv( 'ENV' ) === 'dev' ? true : false;
//  }
//);
