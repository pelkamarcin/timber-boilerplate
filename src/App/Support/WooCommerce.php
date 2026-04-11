<?php

namespace Site\App\Support;

/**
 * Minimalny WooCommerce support.
 *
 * - Deklaruje theme support
 * - Wyłącza domyślny wrapper WC (bo mamy własny w woocommerce.php + Twig)
 * - Domyślne style WC zostają (general, layout, smallscreen) - są dobre
 * - Nadpisania stylów robimy w resources/scss/components/_woocommerce.scss
 */
class WooCommerce {

    public function __construct() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        add_action( 'after_setup_theme', [ $this, 'theme_support' ] );

        // Wyłącz domyślny wrapper WC - mamy własny w woocommerce.php + Twig
        remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
        remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );

        // Wyłącz domyślny sidebar WC
        remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
    }

    public function theme_support(): void {
        add_theme_support( 'woocommerce', [
            'product_grid' => [
                'default_rows'    => 3,
                'min_rows'        => 1,
                'default_columns' => 3,
                'min_columns'     => 1,
                'max_columns'     => 4,
            ],
        ] );
        add_theme_support( 'wc-product-gallery-zoom' );
        add_theme_support( 'wc-product-gallery-lightbox' );
        add_theme_support( 'wc-product-gallery-slider' );
    }
}
