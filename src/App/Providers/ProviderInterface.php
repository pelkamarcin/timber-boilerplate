<?php

namespace Site\App\Providers;

interface ProviderInterface {
    public function register(): void;

    public function boot(): void;
}

