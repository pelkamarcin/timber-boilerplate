<?php

namespace Site\Shortcodes;

class SfyExampleShortcode extends SfyShortcodeBase {


    public function __construct() {

        $this->tag = 'example-shortcode';

        parent::__construct();
    }

    public function extract_atts( $atts ) {
        return shortcode_atts(
            array( 'example' => 'default' ), $atts, $this->tag );
    }
}
