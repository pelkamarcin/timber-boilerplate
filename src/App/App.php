<?php

namespace Site\App;

use Site\App\Container\Container;
use Site\App\Providers\ProviderInterface;

class App {
    private Container $container;

    /** @var class-string<ProviderInterface>[] */
    private array $providers = [];

    public function __construct( array $providers = [] ) {
        $this->container = new Container();
        $this->providers = $providers;
    }

    public function boot(): void {
        array_walk( $this->providers, function ( string $providerClass ): void {
            $provider = $this->container->make( $providerClass );
            $provider->register();
            $provider->boot();
        } );
    }

    public function container(): Container {
        return $this->container;
    }
}

