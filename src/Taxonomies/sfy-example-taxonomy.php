<?php

namespace Site\Taxonomies;

class SfyExampleTaxonomy extends SfyTaxonomyBase {

    public function __construct() {
        $this->labels             = [
            'name' => __( 'Categories', TEXT_DOMAIN ),
            'singular_name' => __( 'Category', TEXT_DOMAIN ),
        ];
        $this->taxonomy_name      = 'example-taxonomy';
        $this->related_post_types = [ 'sfy-example-name' ];
        $this->options            = [
            'hierarchical' => true,
            'show_in_rest' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array( 'slug' => 'state' ),
        ];
        parent::__construct();
    }
}
