<?php

namespace Site;

class SfyThumbnails {
    public function __construct() {
        $this->register_image_sizes();
    }

    private function register_image_sizes() {
        add_image_size( 'fullhd', 1920 );
        add_image_size( 'square', 700, 700, true );
    }
}
