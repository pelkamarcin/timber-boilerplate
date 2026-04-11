<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Site\App\Container\Container;
use RuntimeException;

class SimpleService {
    public string $value = 'hello';
}

class DependentService {
    public function __construct( public SimpleService $simple ) {}
}

class DefaultParamService {
    public function __construct( public string $name = 'default' ) {}
}

class ContainerTest extends TestCase {
    private Container $container;

    protected function setUp(): void {
        $this->container = new Container();
    }

    public function test_resolves_simple_class(): void {
        $instance = $this->container->make( SimpleService::class );
        $this->assertInstanceOf( SimpleService::class, $instance );
        $this->assertEquals( 'hello', $instance->value );
    }

    public function test_returns_singleton(): void {
        $first  = $this->container->make( SimpleService::class );
        $second = $this->container->make( SimpleService::class );
        $this->assertSame( $first, $second );
    }

    public function test_resolves_dependencies(): void {
        $instance = $this->container->make( DependentService::class );
        $this->assertInstanceOf( DependentService::class, $instance );
        $this->assertInstanceOf( SimpleService::class, $instance->simple );
    }

    public function test_uses_default_parameter_values(): void {
        $instance = $this->container->make( DefaultParamService::class );
        $this->assertEquals( 'default', $instance->name );
    }

    public function test_throws_on_nonexistent_class(): void {
        $this->expectException( RuntimeException::class );
        $this->container->make( 'NonExistent\\ClassName' );
    }
}
