<?php

namespace Site\App\Features;

use Timber\Timber;

class Menus {

    public function __construct() {
        // Zadnego __() w konstruktorze - obiekt powstaje w boot() (functions.php),
        // czyli przed after_setup_theme i przed init. Tlumaczenia dopiero w callbacku.
        add_action( 'after_setup_theme', [ $this, 'register_menus' ] );
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

    public function register_menus(): void {
        register_nav_menus( [
            'mainmenu' => __( 'Menu in the header', 'sfy' ),
        ] );
    }
}
