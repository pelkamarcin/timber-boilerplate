<?php

namespace Site\App;

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

class AppServiceProvider implements ProviderInterface {
    public function register(): void {
        add_action( 'after_setup_theme', function (): void {
            new ThemeSupport();
        } );

        add_action( 'init', function (): void {
            new PostTypeRegistry();
            new TaxonomyRegistry();
        },          9 );

        add_action( 'acf/init', function (): void {
            new BlockRegistry();
        } );
    }

    public function boot(): void {
        new Scripts();
        new ImageSizes();
        new Ajax();
        new Plugins();
        new ShortcodeRegistry();
        new Menus();
    }
}
