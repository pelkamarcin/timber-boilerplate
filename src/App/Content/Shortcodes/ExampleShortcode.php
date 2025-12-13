<?php

namespace Site\App\Content\Shortcodes;

class ExampleShortcode extends ShortcodeBase {


    public function __construct() {

        $this->tag = 'example-shortcode';

        parent::__construct();
    }

    public function extract_atts( $atts ) {
        return shortcode_atts(
            array( 'example' => 'default' ), $atts, $this->tag );
    }
}
