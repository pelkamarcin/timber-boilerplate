<?php

return [
    'providers' => [
        \Site\App\AppServiceProvider::class,
    ],

    'assets' => [
        \Site\App\Assets\Scripts::class,
        \Site\App\Assets\ImageSizes::class,
        \Site\App\Assets\FontLoader::class,
    ],

    'features' => [
        \Site\App\Features\Ajax::class,
        \Site\App\Features\MaintenanceMode::class,
        \Site\App\Features\Menus::class,
    ],

    'support' => [
        \Site\App\Support\ThemeSupport::class,
        \Site\App\Support\Plugins::class,
        \Site\App\Support\WooCommerce::class,
    ],
];
