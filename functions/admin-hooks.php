<?php
/**
 * Hooks related to content structure and everything displayed on admin side
 */

//Enable background updates even if there is a git in the root (and there is!)
add_filter( 'automatic_updates_is_vcs_checkout', '__return_false', 1 );


// Remove unnecessary sections from the theme customizer
function themeprefix_edit_customizer( $wp_customize ) {
    $wp_customize->remove_section( 'colors' );
    $wp_customize->remove_section( 'custom_css' );
    // $wp_customize->remove_section( 'static_front_page' );
}

add_action( 'customize_register', 'themeprefix_edit_customizer' );


function themeprefix_add_custom_gutenberg_color_palette() {
    global $common_config;
    if ( $common_config->colors ) {
        $items = [];
        foreach ( $common_config->colors as $key => $color ) {
            array_push( $items, [
                'name' => esc_html__( ucfirst( $key ), 'themeprefix' ), // phpcs:ignore
                'slug' => $key,
                'color' => $color,
            ] );
        }

        add_theme_support(
            'editor-color-palette',
            $items
        );
    }
}

add_action( 'after_setup_theme', 'themeprefix_add_custom_gutenberg_color_palette' );
