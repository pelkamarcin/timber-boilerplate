<?php

namespace Site;

class SfyMenus {
    public function __construct() {

        $this->registerMenus();
    }

    private function registerMenus() {
        register_nav_menus(
            [
                'mainmenu' => __( 'Menu in the header', TEXT_DOMAIN ),
            ]
        );
    }
}
