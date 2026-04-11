<?php

namespace Site\App\Assets;

class Scripts {
    public function __construct() {

        add_action( 'wp_enqueue_scripts', [ $this, 'script_enqueue' ], 10 );
        add_filter( 'script_loader_tag', [ $this, 'scripts_as_modules' ], 10, 3 );
        add_action( 'init', [ $this, 'redirect_assets_in_dev' ] );
        add_action( 'enqueue_block_editor_assets', [ $this, 'editor_styles' ] );
    }

    public function redirect_assets_in_dev(): void {
        if ( ! $this->is_vite_dev() ) {
            return;
        }

        // Jeśli URL zawiera /assets/ - redirect na theme assets
        if ( strpos( $_SERVER['REQUEST_URI'], '/assets/' ) === 0 ) {
            $requested_path   = substr( $_SERVER['REQUEST_URI'], 1 );
            $theme_asset_path = get_template_directory_uri() . '/' . $requested_path;

            wp_redirect( $theme_asset_path, 301 );
            exit;
        }
    }

    /**
     * Sprawdza czy Vite dev server działa.
     * Używa wp_get_environment_type() żeby nie robić HTTP requestów na produkcji.
     */
    private function is_vite_dev(): bool {
        if ( wp_get_environment_type() !== 'local' ) {
            return false;
        }

        static $is_running = null;
        if ( $is_running !== null ) {
            return $is_running;
        }

        $vite_check = wp_remote_get( 'https://localhost:5173/', array(
            'sslverify' => false,
            'timeout'   => 1,
        ) );

        $is_running = is_array( $vite_check );
        return $is_running;
    }

    public function script_enqueue(): void {
        $manifest_path = get_theme_file_path( 'dist/.vite/manifest.json' );

        if ( $this->is_vite_dev() ) {
            wp_enqueue_script( 'vite', 'http://localhost:5173/@vite/client', [], time() );
            wp_enqueue_script( 'sfy', 'http://localhost:5173/resources/js/index.ts', [], time() );

        } elseif ( file_exists( $manifest_path ) ) {
            $manifest     = json_decode( file_get_contents( $manifest_path ), true );
            $js_file      = 'dist/' . $manifest['resources/js/index.ts']['file'];
            $js_file_path = get_theme_file_path( $js_file );
            $js_file_uri  = get_theme_file_uri( $js_file );

            wp_enqueue_script( 'sfy', $js_file_uri, [], '1.0.' . filemtime( $js_file_path ) );
            if ( isset( $manifest['resources/js/index.ts']['css'] ) ) {
                $css_file      = 'dist/' . $manifest['resources/js/index.ts']['css'][0];
                $css_file_path = get_theme_file_path( $css_file );
                $css_file_uri  = get_theme_file_uri( $css_file );
                wp_enqueue_style( 'sfy', $css_file_uri, [], '1.0.' . filemtime( $css_file_path ) );
            }
        }
        wp_localize_script( 'sfy', 'localized_strings', $this->localized_strings() );
        wp_localize_script( 'sfy', 'script_data', $this->script_data() );
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

    public function editor_styles(): void {
        $manifest_path = get_theme_file_path( 'dist/.vite/manifest.json' );

        if ( ! file_exists( $manifest_path ) ) {
            return;
        }

        $manifest = json_decode( file_get_contents( $manifest_path ), true );

        if ( isset( $manifest['resources/scss/editor.scss']['file'] ) ) {
            $css_file      = 'dist/' . $manifest['resources/scss/editor.scss']['file'];
            $css_file_path = get_theme_file_path( $css_file );
            $css_file_uri  = get_theme_file_uri( $css_file );
            wp_enqueue_style( 'sfy-editor', $css_file_uri, [], '1.0.' . filemtime( $css_file_path ) );
        }
    }

    public function scripts_as_modules( string $tag, string $handle, string $src ) {
        if ( in_array( $handle, [ 'vite', 'sfy' ] ) ) {
            return '<script type="module" src="' . esc_url( $src ) . '"></script>';
        }

        return $tag;
    }

}


