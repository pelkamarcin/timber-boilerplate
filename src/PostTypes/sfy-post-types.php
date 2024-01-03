<?php

namespace PostTypes;

class SfyPostTypes {

    public array $post_types = [
//        SfyExamplePostType::class,
    ];

    public function __construct() {
        $this->register_post_types();
    }

    private function register_post_types() {
        foreach ( $this->post_types as $post_type ) {
            new $post_type();
        }
    }
}
