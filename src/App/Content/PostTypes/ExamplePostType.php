<?php

namespace Site\App\Content\PostTypes;

class ExamplePostType extends PostTypeBase {

    public function __construct() {
        $this->post_type_name = 'example-name';

        $this->labels         = [
            'name' => __( 'Products', 'sfy' ),
            'singular_name' => __( 'Product', 'sfy' ),
        ];

        $this->options = [
            'public' => true,
            'has_archive' => true,
            'supports' => array( 'title', 'editor', 'thumbnail', ),
            //                           'taxonomies' => array('states'),
        ];
        parent::__construct();
    }
}
