<?php

namespace Site;

use Timber\Timber;

class SfyMenus {

    private array $menus;

    public function __construct() {

        $this->menus = [
            'mainmenu' => __( 'Menu in the header', 'sfy' ),
        ];

        add_filter( 'after_setup_theme', [ $this, 'register_menus' ] );
        add_filter( 'timber/context', [ $this, 'add_to_context' ] );
    }

    public function add_to_context( $context ) {
        foreach ( array_keys( get_registered_nav_menus() ) as $location ) {
            // Bail out if menu has no location.
            if ( !has_nav_menu( $location ) ) {
                continue;
            }

            $menu = Timber::get_menu( $location );

            $context[ $location ] = $menu;
        }
        return $context;
    }

    public function register_menus() {
        register_nav_menus( $this->menus );
    }
}
