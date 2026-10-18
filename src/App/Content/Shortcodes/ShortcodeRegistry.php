<?php

namespace Site\App\Content\Shortcodes;

class ShortcodeRegistry {

    /**
     * @param array<int, class-string> $shortcodes Lista klas shortcodow z config/content.php
     */
    public function __construct( private array $shortcodes = [] ) {
        $this->register_shortcodes();
    }

    private function register_shortcodes(): void {
        foreach ( $this->shortcodes as $shortcode ) {
            new $shortcode();
        }
    }
}
