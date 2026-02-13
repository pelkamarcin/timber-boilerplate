<?php

namespace Site\App\Container;

use ReflectionClass;
use ReflectionException;
use RuntimeException;

class Container {
    private array $instances = [];

    /**
     * @template T
     * @param class-string<T> $class
     * @return T
     */
    public function make( string $class ) {
        if ( isset( $this->instances[ $class ] ) ) {
            return $this->instances[ $class ];
        }

        try {
            $reflection = new ReflectionClass( $class );
        } catch ( ReflectionException $exception ) {
            throw new RuntimeException( "Unable to resolve {$class}", 0, $exception );
        }

        $constructor = $reflection->getConstructor();

        if ( !$constructor ) {
            $instance = new $class();
        } else {
            $dependencies = [];

            foreach ( $constructor->getParameters() as $parameter ) {
                $type = $parameter->getType();
                if ( $type && !$type->isBuiltin() ) {
                    /** @var class-string $dependencyClass */
                    $dependencyClass = $type->getName();
                    $dependencies[]  = $this->make( $dependencyClass );
                } elseif ( $parameter->isDefaultValueAvailable() ) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new RuntimeException( "Unable to resolve parameter " . $parameter->getName() . " for {$class}" );
                }
            }

            $instance = $reflection->newInstanceArgs( $dependencies );
        }

        $this->instances[ $class ] = $instance;

        return $instance;
    }
}

