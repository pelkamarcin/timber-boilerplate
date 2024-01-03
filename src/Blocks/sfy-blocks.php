<?php

namespace Site\Blocks;

class SfyBlocks {
    public array $blocks =
        [
            'example-block',
        ];

    public function __construct() {

        add_action( 'init', [ $this, 'register_blocks' ] );

    }

    public function register_blocks() {
        foreach ( $this->blocks as $block ) {
            register_block_type( __DIR__ . '/' . $block );
        }
    }
}
