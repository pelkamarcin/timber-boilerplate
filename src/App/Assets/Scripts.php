<?php

namespace Site\App\Assets;

class Scripts {
    public function __construct() {

        add_action( 'wp_enqueue_scripts', [ $this, 'script_enqueue' ], 10 );
        add_filter( 'script_loader_tag', [ $this, 'scripts_as_modules' ], 10, 3 );
        add_action( 'init', [ $this, 'redirect_assets_in_dev' ] );
    }

    public function redirect_assets_in_dev(): void {
        // Sprawdzam czy Vite dev server działa
        $vite_check = wp_remote_get( 'https://localhost:5173/', array( 'sslverify' => FALSE, 'timeout' => 1 ) );

        if ( !is_array( $vite_check ) ) {
            return; // Vite nie działa, nie robimy redirect
        }

        // Jeśli URL zawiera /assets/img/ - redirect na theme assets
        if ( strpos( $_SERVER['REQUEST_URI'], '/assets/' ) === 0 ) {
            $requested_path   = substr( $_SERVER['REQUEST_URI'], 1 ); // Usuń leading slash
            $theme_asset_path = get_template_directory_uri() . '/' . $requested_path;

            wp_safe_remote_get( $theme_asset_path );
            wp_redirect( $theme_asset_path, 301 );
            exit;
        }
    }

    public function script_enqueue(): void {
        $manifest_path = get_theme_file_path( 'dist/.vite/manifest.json' );
//        wp_enqueue_script( 'jquery' );

        if (
//            wp_get_environment_type() === 'local' &&

        is_array( wp_remote_get( 'https://localhost:5173/', array( 'sslverify' => FALSE ) ) ) // is Vite.js running
        ) {
            wp_enqueue_script( 'vite', 'http://localhost:5173/@vite/client', [], time() );
            wp_enqueue_script( 'sfy', 'http://localhost:5173/resources/js/index.js', [], time() );

        } elseif ( file_exists( $manifest_path ) ) {
            $manifest     = json_decode( file_get_contents( $manifest_path ), true );
            $js_file      = 'dist/' . $manifest['resources/js/index.js']['file'];
            $js_file_path = get_theme_file_path( $js_file );
            $js_file_uri  = get_theme_file_uri( $js_file );

            wp_enqueue_script( 'sfy', $js_file_uri, [], '1.0.' . filemtime( $js_file_path ) );
            if ( isset( $manifest['resources/js/index.js']['css'] ) ) {
                $css_file      = 'dist/' . $manifest['resources/js/index.js']['css'][0];
                $css_file_path = get_theme_file_path( $css_file );
                $css_file_uri  = get_theme_file_uri( $css_file );
                wp_enqueue_style( 'sfy', $css_file_uri, [], '1.0.' . filemtime( $css_file_path ) );
            }
        }
        wp_localize_script( 'sfy', 'localized_strings', [ $this, 'localized_strings' ] );
        wp_localize_script( 'sfy', 'script_data', [ $this, 'script_data' ] );
    }

    public function script_data() {
        $script_data = [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'theme_link' => get_template_directory_uri(),
        ];
        if ( function_exists( 'get_fields' ) ) {
            $script_data['options'] = get_fields( 'option' );
        }
        return $script_data;
    }

    public function localized_strings() {
        return [
            'load_more' => __( 'Load more', 'sfy' ),
        ];
    }

    public function scripts_as_modules( string $tag, string $handle, string $src ) {
        if ( in_array( $handle, [ 'vite', 'sfy' ] ) ) {
            return '<script type="module" src="' . esc_url( $src ) . '" defer></script>';
        }

        return $tag;
    }

}


