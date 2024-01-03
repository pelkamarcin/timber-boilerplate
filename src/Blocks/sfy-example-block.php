<?php

namespace Site\Blocks;

use Timber\Timber;

class SfyExampleBlock extends SfyBlockBase {


    public function __construct() {

        $this->name     = 'example-block';
        $this->title    = __( 'Example block', TEXT_DOMAIN );
        $this->supports = [
            //      'align' => false,
            //      'mode'  => false,
            //      'jsx'   => true,
        ];

        parent::__construct();
    }

    public function callback( $block, $content = '', $is_preview = false ): void {

        $context = $this->base_callback( $block, $content, $is_preview );

        Timber::render( 'block/' . $this->name . '.twig', $context );

    }
}
