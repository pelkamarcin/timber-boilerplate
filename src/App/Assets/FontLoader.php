<?php

namespace Site\App\Assets;

/**
 * Ładowanie fontów z różnych źródeł: local, Google Fonts, Typekit.
 * Konfiguracja w config/fonts.php (jedno miejsce).
 *
 * - Local:   WordPress generuje @font-face z theme.json fontFace. Ta klasa dodaje <link rel="preload">.
 * - Google:  Dodaje <link rel="preconnect"> + <link rel="stylesheet"> do Google Fonts CSS.
 * - Typekit: Dodaje <link rel="preconnect"> + <link rel="stylesheet"> do Adobe Fonts kit.
 */
class FontLoader {

    private array $config;

    public function __construct() {
        $this->config = require get_theme_file_path( 'config/fonts.php' );

        add_action( 'wp_head', [ $this, 'output_preconnect' ], 1 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_fonts' ], 5 );

        // Preload fontów lokalnych - musi być w wp_head, przed innymi stylami
        if ( $this->config['source'] === 'local' ) {
            add_action( 'wp_head', [ $this, 'preload_local_fonts' ], 1 );
        }
    }

    /**
     * Preconnect do zewnętrznych domen (fonty + custom).
     */
    public function output_preconnect(): void {
        $domains = [];

        switch ( $this->config['source'] ) {
            case 'google':
                $domains[] = 'https://fonts.googleapis.com';
                $domains[] = 'https://fonts.gstatic.com';
                break;
            case 'typekit':
                $domains[] = 'https://use.typekit.net';
                break;
        }

        // Dodatkowe preconnect z configa
        $domains = array_merge( $domains, $this->config['preconnect'] ?? [] );

        foreach ( $domains as $url ) {
            printf(
                '<link rel="preconnect" href="%s" crossorigin>' . "\n",
                esc_url( $url )
            );
        }

        // DNS prefetch
        foreach ( $this->config['dns_prefetch'] ?? [] as $url ) {
            printf(
                '<link rel="dns-prefetch" href="%s">' . "\n",
                esc_url( $url )
            );
        }
    }

    /**
     * Enqueueing zewnętrznych arkuszy fontów (Google / Typekit).
     */
    public function enqueue_fonts(): void {
        switch ( $this->config['source'] ) {
            case 'google':
                $url = $this->config['google_url'] ?? '';
                if ( ! empty( $url ) ) {
                    wp_enqueue_style( 'sfy-fonts', $url, [], null );
                }
                break;

            case 'typekit':
                $kit_id = $this->config['typekit_id'] ?? '';
                if ( ! empty( $kit_id ) ) {
                    wp_enqueue_style(
                        'sfy-fonts',
                        'https://use.typekit.net/' . sanitize_key( $kit_id ) . '.css',
                        [],
                        null
                    );
                }
                break;
        }
    }

    /**
     * Preload plików fontów lokalnych na podstawie theme.json fontFace.
     */
    public function preload_local_fonts(): void {
        $theme_json_path = get_theme_file_path( 'theme.json' );

        if ( ! file_exists( $theme_json_path ) ) {
            return;
        }

        $theme_json    = json_decode( file_get_contents( $theme_json_path ), true );
        $font_families = $theme_json['settings']['typography']['fontFamilies'] ?? [];
        $variants      = $this->config['preload_variants'] ?? [];

        foreach ( $font_families as $family ) {
            foreach ( $family['fontFace'] ?? [] as $face ) {
                if ( ! $this->should_preload( $face, $variants ) ) {
                    continue;
                }

                $src = $face['src'][0] ?? '';
                if ( empty( $src ) ) {
                    continue;
                }

                $font_url = str_replace( 'file:./', get_theme_file_uri( '/' ), $src );

                printf(
                    '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
                    esc_url( $font_url )
                );
            }
        }
    }

    private function should_preload( array $face, array $variants ): bool {
        foreach ( $variants as $variant ) {
            if (
                ( $face['fontWeight'] ?? '' ) === $variant['fontWeight'] &&
                ( $face['fontStyle'] ?? '' ) === $variant['fontStyle']
            ) {
                return true;
            }
        }
        return false;
    }
}
