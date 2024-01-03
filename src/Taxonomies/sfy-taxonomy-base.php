<?php

namespace Site\Taxonomies;

class SfyTaxonomyBase {
    public array  $labels;
    public string $prepend            = 'sfy-';
    public array  $related_post_types = [];
    public string $taxonomy_name      = 'base-post-type';

    public array $options = [
        'hierarchical' => true,
        'show_in_rest' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array( 'slug' => 'state' ),
    ];

    public function __construct() {
        $this->register();
    }

    public function register() {
        $this->options['labels'] = $this->labels;
        register_taxonomy( $this->prepend . $this->taxonomy_name, $this->related_post_types, $this->options );
    }
}
