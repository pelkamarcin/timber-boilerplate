<?php

namespace Site;

class SfyPlugins {
    public function __construct() {
        $this->polylang();
        $this->contact_form7();
        $this->acf();
    }

    private function polylang() {
        add_filter( 'default_title', [ $this, 'polylang_editor_title' ] );
        add_filter( 'default_content', [ $this, 'polylang_editor_content' ] );

    }


// Make sure Polylang copies the title when creating a translation
    public function polylang_editor_title( $title ) {
        // Polylang sets the 'from_post' parameter
        if ( isset( $_GET['from_post'] ) ) {
            $my_post = get_post( $_GET['from_post'] );
            if ( $my_post ) {
                return $my_post->post_title;
            }
        }

        return $title;
    }

    public function polylang_editor_content( $content ) {
        // Polylang sets the 'from_post' parameter
        if ( isset( $_GET['from_post'] ) ) {
            $my_post = get_post( $_GET['from_post'] );
            if ( $my_post ) {
                return $my_post->post_content;
            }
        }

        return $content;
    }

    private function contact_form7() {

        define( 'WPCF7_AUTOP', false ); // phpcs:ignore
        add_filter( 'wpcf7_autop_or_not', '__return_false' );

    }

    private function acf() {


        add_action( 'acf/init', [ $this, 'acf_init' ] );
        add_filter( 'timber/context', [ $this, 'acf_timber' ] );
    }

    public function acf_timber( $context ) {

        if ( function_exists( 'get_fields' ) ) {
            // make ACF options page fields available in every Twig file
            $context['options'] = get_fields( 'option' );
        }
        return $context;
    }

    public function acf_init() {
        if ( function_exists( 'acf_add_options_page' ) ) {

            acf_add_options_page(
                [
                    'page_title' => __( 'Site Settings', 'sfy' ),
                    'menu_slug' => 'site-settings',
                    'capability' => 'edit_posts',
                    'redirect' => false,
                ]
            );

        }
        if ( function_exists( 'acf_update_setting' ) ) {
            acf_update_setting( 'l10n_textdomain', 'sfy' );
        }
    }

}
