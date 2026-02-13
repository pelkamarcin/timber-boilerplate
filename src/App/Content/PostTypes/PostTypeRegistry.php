<?php

namespace Site\App\Content\PostTypes;

class PostTypeRegistry {

    public function __construct( private array $post_types = [] ) {
        $this->register_post_types();
    }

    private function register_post_types() {
        foreach ( $this->post_types as $post_type ) {
            new $post_type();
        }
    }
}
