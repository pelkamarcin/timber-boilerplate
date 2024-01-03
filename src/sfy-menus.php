<?php

namespace Site;

use Timber\Timber;

class SfyMenus {

    private array $menus = [];

    public function __construct() {

        $this->menus = [
            'mainmenu' => __( 'Menu in the header', 'sfy' ),
        ];

        $this->registerMenus();
    }

    public function add_to_context_header( $context ) {
        // add menus
        foreach ( $this->menus as $key => $menu ) {
            $context[ $key ] = Timber::get_menu( $key );
        }
        return $context;
    }

    private function registerMenus() {
        register_nav_menus(
            $this->menus
        );
    }
}
