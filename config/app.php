<?php

return [
    'providers' => [
        \Site\App\AppServiceProvider::class,
    ],

    'assets' => [
        \Site\App\Assets\Scripts::class,
        \Site\App\Assets\ImageSizes::class,
    ],

    'features' => [
        \Site\App\Features\Ajax::class,
        \Site\App\Features\Menus::class,
    ],

    'support' => [
        \Site\App\Support\ThemeSupport::class,
        \Site\App\Support\Plugins::class,
    ],
];
