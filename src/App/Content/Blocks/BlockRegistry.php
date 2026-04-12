<?php

namespace Site\App\Content\Blocks;

class BlockRegistry {
    public function __construct( private array $blocks = [ 'example-block' ] ) {
        // Wywolujemy bezposrednio - klasa jest tworzona wewnatrz acf/init
        // (ktory odpala sie podczas init), wiec hookowanie na init jest za pozno
        $this->register_blocks();
    }

    public function register_blocks() {
        foreach ( $this->blocks as $block ) {
            register_block_type( __DIR__ . '/' . $block );
        }
    }
}
