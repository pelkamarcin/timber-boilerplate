<?php

namespace PostTypes;


class SfyPostTypeBase {
    public array  $labels;
    public string $prepend        = 'sfy-';
    public string $post_type_name = 'base-post-type';

    public array $options = [
        'public' => true,
        'has_archive' => true,
    ];

    public function __construct() {
        $this->register();
    }

    public function register() {
        $this->options['labels'] = $this->labels;
        register_post_type( $this->prepend . $this->post_type_name, $this->options );
    }
}
