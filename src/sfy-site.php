<?php

namespace Site;

use PostTypes\SfyPostTypes;
use Site\Blocks\SfyBlocks;
use Site\Shortcodes\SfyShortcodes;
use Site\Taxonomies\SfyTaxonomies;
use Timber\Site;
use Timber\Timber;

/**
 * Class SfySite
 */
class SfySite extends Site {
    public function __construct() {
        $this->bootstrap();

        parent::__construct();
    }

    public function bootstrap() {

        add_action( 'init', array( $this, 'register_post_types' ) );
        add_filter( 'timber_context', [ $this, 'add_to_context_global' ] );
        add_filter( 'timber_context', [ $this, 'add_to_context_header' ] );
        add_filter( 'login_head', [ $this, 'custom_login_logo' ] );
        add_filter( 'body_class', [ $this, 'add_body_classes' ] );
        add_filter( 'automatic_updates_is_vcs_checkout', '__return_false', 1 );
        $this->remove_emojis();
        new SfyThemeSupport();
        new SfyScripts();
        new SfyBlocks();
        new SfyThumbnails();
        new SfyAjax();
        new SfyPlugins();
        new SfyShortcodes();
        new SfyMenus();
    }

    public function register_post_types() {
        new SfyPostTypes();
        new SfyTaxonomies();
    }


    public function add_to_context_global( $context ) {

        $context['site'] = $this;
        if ( function_exists( 'pll_current_language' ) ) {
            $context['lang'] = pll_current_language( 'slug' );
        }

        return $context;
    }


    public function add_to_context_header( $context ) {
        // add menus
        $context['mainmenu'] = Timber::get_menu( 'mainmenu' );

        return $context;
    }

    public function custom_login_logo() {
        $url = get_theme_file_uri( 'favicon.svg' );

        $styles = [
            sprintf( 'background-image: url(%s)', $url ),
            'width: 200px',
            'background-position: center',
            'background-size: contain',
        ];

        printf(
            '<style> .login h1 a { %s } </style>',
            implode( ';', $styles )
        );
    }

    private function remove_emojis() {
        // Remove WP emoji code
        remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
        remove_action( 'wp_print_styles', 'print_emoji_styles' );

        remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
        remove_action( 'admin_print_styles', 'print_emoji_styles' );
    }

    function add_body_classes( $classes ) {
        global $post;

        if ( is_singular() ) {
            $classes[] = sanitize_html_class( $post->post_name );
        };
        if ( is_array( $classes ) ) {
            foreach ( $classes as $k => $v ) {
                $classes[ $k ] = 'p-' . $v;
            }
        }

        return $classes;
    }

}
