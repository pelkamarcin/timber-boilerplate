<?php

namespace Site\App;

use Site\App\Assets\FontLoader;
use Site\App\Assets\ImageSizes;
use Site\App\Assets\Scripts;
use Site\App\Content\Blocks\BlockRegistry;
use Site\App\Content\PostTypes\PostTypeRegistry;
use Site\App\Content\Shortcodes\ShortcodeRegistry;
use Site\App\Content\Taxonomies\TaxonomyRegistry;
use Site\App\Features\Ajax;
use Site\App\Features\Menus;
use Site\App\Providers\ProviderInterface;
use Site\App\Support\Plugins;
use Site\App\Support\ThemeSupport;
use Site\App\Support\WooCommerce;

class AppServiceProvider implements ProviderInterface {
    public function __construct( private array $contentConfig = [] ) {
        $this->contentConfig = $contentConfig ?: require get_theme_file_path( 'config/content.php' );
    }

    public function register(): void {
        $contentConfig = $this->contentConfig;

        add_action( 'after_setup_theme', function (): void {
            new ThemeSupport();
        } );

        add_action( 'init', function () use ( $contentConfig ): void {
            new PostTypeRegistry( $contentConfig['post_types'] );
            new TaxonomyRegistry( $contentConfig['taxonomies'] );
        },          9 );

        add_action( 'acf/init', function () use ( $contentConfig ): void {
            new BlockRegistry( $contentConfig['blocks'] );
        } );
    }

    public function boot(): void {
        $contentConfig = $this->contentConfig;

        new Scripts();
        new ImageSizes();
        new FontLoader();
        new Ajax();
        new Plugins();
        new WooCommerce();
        new ShortcodeRegistry( $contentConfig['shortcodes'] );
        new Menus();
    }
}
