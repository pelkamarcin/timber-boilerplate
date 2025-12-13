<?php

namespace Site\App\Content\Shortcodes;

class ShortcodeRegistry {
    public array $blocks =
        [
            ExampleShortcode::class,
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
