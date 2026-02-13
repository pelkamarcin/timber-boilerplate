<?php

namespace Site\App\Content\Blocks;

class BlockRegistry {
    public function __construct( private array $blocks = [ 'example-block' ] ) {
        add_action( 'init', [ $this, 'register_blocks' ] );
    }

    public function register_blocks() {
        foreach ( $this->blocks as $block ) {
            register_block_type( __DIR__ . '/' . $block );
        }
    }
}
