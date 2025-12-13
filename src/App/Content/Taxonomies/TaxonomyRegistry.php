<?php

namespace Site\App\Content\Taxonomies;

class TaxonomyRegistry {
    public array $taxonomies = [
//        SfyExampleTaxonomy::class,
    ];

    public function __construct() {
        foreach ( $this->taxonomies as $taxonomy ) {
            new $taxonomy();
        }
    }
}
