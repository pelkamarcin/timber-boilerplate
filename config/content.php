<?php

return [
    'post_types' => [
        \Site\App\Content\PostTypes\ExamplePostType::class,
    ],
    'taxonomies' => [
        \Site\App\Content\Taxonomies\ExampleTaxonomy::class,
    ],
    'shortcodes' => [
        \Site\App\Content\Shortcodes\ExampleShortcode::class,
    ],
    'blocks' => [
        'example-block',
    ],
];
