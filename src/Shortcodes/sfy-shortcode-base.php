<?php

namespace Site\Shortcodes;

use Timber\Timber;
use Twig\Error\Error;

class SfyShortcodeBase {
    public string $tag;

    /**
     * @throws Error
     */
    public function __construct() {
        if ( !isset( $this->tag ) ) {
            throw new Error( 'Shortcode needs to have a tag' );
        }
        add_shortcode( $this->tag, [ $this, 'render' ] );
    }

    public function extract_atts( $atts ) {
        return shortcode_atts(
            array( 'app' => 'all' ), $atts, $this->tag );
    }

    public function render( $atts ): string {
        $context         = Timber::context();
        $a               = $this->extract_atts( $atts );
        $context['atts'] = $a;
        ob_start();
        Timber::render( 'shortcodes/' . $this->tag . '.twig', $context );
        $output = ob_get_clean();
        return $output;
    }


}
