<?php

namespace Site\Taxonomies;
class SfyTaxonomies {

    public array $taxonomies = [
//        SfyExampleTaxonomy::class,
    ];

    public function __construct() {
        $this->register_taxonomies();
    }

    private function register_taxonomies() {
        foreach ( $this->taxonomies as $taxonomy ) {
            new $taxonomy();
        }
    }
}
