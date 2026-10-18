<?php

namespace Site\App\Content\Taxonomies;

class TaxonomyRegistry {

    /**
     * @param array<int, class-string> $taxonomies Lista klas taksonomii z config/content.php
     */
    public function __construct( private array $taxonomies = [] ) {
        $this->register_taxonomies();
    }

    private function register_taxonomies(): void {
        foreach ( $this->taxonomies as $taxonomy ) {
            new $taxonomy();
        }
    }
}
