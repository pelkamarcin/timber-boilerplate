<?php

namespace Site\Shortcodes;

class SfyShortcodes {
    public array $blocks =
        [
            SfyExampleShortcode::class,
        ];

    public function __construct() {
        $this->register_shortcodes();
    }

    private function register_shortcodes(): void {
        foreach ( $this->blocks as $block ) {
            new $block();
        }
    }
}
