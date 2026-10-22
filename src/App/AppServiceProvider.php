<?php

namespace Site\App;

use Site\App\Assets\FontLoader;
use Site\App\Assets\ImageSizes;
use Site\App\Assets\Scripts;
use Site\App\Content\Blocks\BlockCategories;
use Site\App\Content\Blocks\BlockPreviewGuard;
use Site\App\Content\Blocks\BlockRegistry;
use Site\App\Content\PostTypes\PostTypeRegistry;
use Site\App\Content\Shortcodes\ShortcodeRegistry;
use Site\App\Content\Taxonomies\TaxonomyRegistry;
use Site\App\Features\Ajax;
use Site\App\Features\MaintenanceMode;
use Site\App\Features\Menus;
use Site\App\Providers\ProviderInterface;
use Site\App\Support\Plugins;
use Site\App\Support\ThemeSupport;
use Site\App\Support\WooCommerce;

class AppServiceProvider implements ProviderInterface {
    public function __construct( private array $contentConfig = [] ) {
        $this->contentConfig = empty( $contentConfig )
            ? require get_theme_file_path( 'config/content.php' )
            : $contentConfig;
    }

    public function register(): void {
        $contentConfig = $this->contentConfig;

        add_action( 'after_setup_theme', function (): void {
            new ThemeSupport();
        } );

        add_action( 'init', function () use ( $contentConfig ): void {
            new PostTypeRegistry( $contentConfig['post_types'] ?? [] );
            new TaxonomyRegistry( $contentConfig['taxonomies'] ?? [] );
        }, 9 );

        add_action( 'acf/init', function () use ( $contentConfig ): void {
            new BlockRegistry( $contentConfig['blocks'] ?? [] );
        } );
    }

    public function boot(): void {
        $contentConfig = $this->contentConfig;

        new Scripts();
        new ImageSizes();
        new FontLoader();
        new Ajax();
        new MaintenanceMode();
        new Plugins();
        new WooCommerce();
        new ShortcodeRegistry( $contentConfig['shortcodes'] ?? [] );
        new Menus();
        new BlockCategories();
        // Hookowane bezwarunkowo: callbacki same wychodza natychmiast, gdy
        // $is_preview jest falsy, wiec render frontowy nie przechodzi przez
        // procesor ani o bajt. Gate na is_admin() bylby zawodny - podglady
        // blokow ACF jada tez przez REST (is_admin() === false).
        new BlockPreviewGuard();
    }
}
