<?php

declare(strict_types=1);

namespace Clicalmani\Core\Tests\Http\Controllers;

use Clicalmani\Core\Exceptions\ContainerDepencyException;
use Clicalmani\Core\Http\Controllers\FunctionReflector;
use Clicalmani\Core\Http\Controllers\ReflectorInterface;
use Clicalmani\Core\Support\Facades\Route as RouteFacade;
use Clicalmani\Core\Tests\Fakes\SpyLocator;
use Clicalmani\Core\Tests\Stubs\Injectable;
use Clicalmani\Core\Tests\Stubs\InjectableChild;
use Clicalmani\Core\Tests\Stubs\InjectableInterface;
use Clicalmani\Core\Tests\Stubs\OddContract;
use Clicalmani\Core\Tests\TestCase;
use Clicalmani\Routing\Factory\LaravelStyleBuilder;
use Clicalmani\Routing\Registry;
use Clicalmani\Routing\Route;

final class InjectionLocatorTest extends TestCase
{
    private Route $route;

    protected function setUp(): void
    {
        parent::setUp();

        Registry::reset();

        $this->setConfig('route', [
            'parameter_prefix' => ':',
            'default_builder'  => LaravelStyleBuilder::class,
        ]);

        RouteFacade::get('/users/{id}', fn () => 'ok');

        $this->route = Registry::getRoutes()['get'][0];
    }

    protected function tearDown(): void
    {
        Registry::reset();

        parent::tearDown();
    }

    private function parameter(\Closure $signature, int $index = 0): \ReflectionParameter
    {
        return (new \ReflectionFunction($signature))->getParameters()[$index];
    }

    private function reflector(\Closure $signature): ReflectorInterface
    {
        return new FunctionReflector(new \ReflectionFunction($signature));
    }

    /* -----------------------------------------------------------------
     |  getSupportedTypes()
     | -----------------------------------------------------------------
     */

    public function test_supported_types_walk_the_class_its_parents_and_its_interfaces(): void
    {
        $locator = new SpyLocator;

        $types = $locator->supportedTypes($this->parameter(fn (InjectableChild $child) => null));

        $this->assertSame(
            [InjectableChild::class, Injectable::class, InjectableInterface::class, \JsonSerializable::class, '*'],
            array_values($types)
        );
    }

    public function test_supported_types_always_end_with_the_wildcard(): void
    {
        $locator = new SpyLocator;

        $types = $locator->supportedTypes($this->parameter(fn (Injectable $service) => null));

        $this->assertSame([Injectable::class, InjectableInterface::class, '*'], array_values($types));
        $this->assertSame('*', array_key_last($types));
    }

    public function test_supported_types_is_empty_for_a_scalar_parameter(): void
    {
        $this->assertSame([], (new SpyLocator)->supportedTypes($this->parameter(fn (int $count) => null)));
    }

    public function test_supported_types_is_empty_for_an_untyped_parameter(): void
    {
        $this->assertSame([], (new SpyLocator)->supportedTypes($this->parameter(fn ($anything) => null)));
    }

    public function test_a_nullable_class_yields_the_same_types(): void
    {
        $locator = new SpyLocator;

        $this->assertSame(
            [Injectable::class, InjectableInterface::class, '*'],
            array_values($locator->supportedTypes($this->parameter(fn (?Injectable $service) => null)))
        );
    }

    /**
     * FIXME : getSupportedTypes() appelle isBuiltin() avant de tester le type
     * de réflexion, or ReflectionUnionType ne connaît pas cette méthode. La
     * branche ReflectionUnionType écrite juste en dessous est donc du code
     * mort : toute signature en union type casse la résolution.
     */
    public function test_a_union_type_crashes_before_reaching_the_union_branch(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Call to undefined method ReflectionUnionType::isBuiltin()');

        (new SpyLocator)->supportedTypes($this->parameter(fn (int|Injectable $mixed) => null));
    }

    /* -----------------------------------------------------------------
     |  resolve()
     | -----------------------------------------------------------------
     */

    public function test_resolve_stops_at_the_first_type_handled(): void
    {
        $locator = new SpyLocator;
        $locator->answer = new Injectable;

        $args = [];

        $this->assertTrue($locator->resolve(
            $this->parameter(fn (InjectableChild $child) => null),
            $this->reflector(fn (InjectableChild $child) => null),
            $this->route,
            $args,
            0
        ));

        $this->assertSame([InjectableChild::class], $locator->types);
        $this->assertSame($locator->answer, $args[0]);
    }

    public function test_resolve_writes_at_the_requested_position(): void
    {
        $locator = new SpyLocator;
        $locator->answer = new Injectable;

        $args = ['a', 'b'];

        $locator->resolve(
            $this->parameter(fn (Injectable $service) => null),
            $this->reflector(fn (Injectable $service) => null),
            $this->route,
            $args,
            1
        );

        $this->assertSame('a', $args[0]);
        $this->assertSame($locator->answer, $args[1]);
    }

    public function test_resolve_tries_every_type_before_giving_up(): void
    {
        $locator = new SpyLocator;

        $args = [];

        $this->assertFalse($locator->resolve(
            $this->parameter(fn (InjectableChild $child) => null),
            $this->reflector(fn (InjectableChild $child) => null),
            $this->route,
            $args,
            0
        ));

        $this->assertSame(
            [InjectableChild::class, Injectable::class, InjectableInterface::class, \JsonSerializable::class, '*'],
            $locator->types
        );
        $this->assertSame([], $args);
    }

    /**
     * Une exception dans handle() n'est pas propagée : elle laisse simplement
     * la place au type suivant, puis aux resolvers suivants.
     */
    public function test_resolve_swallows_the_exceptions_of_handle(): void
    {
        $locator = new SpyLocator;
        $locator->throws = true;

        $args = [];

        $this->assertFalse($locator->resolve(
            $this->parameter(fn (Injectable $service) => null),
            $this->reflector(fn (Injectable $service) => null),
            $this->route,
            $args,
            0
        ));

        $this->assertCount(3, $locator->types);
        $this->assertSame([], $args);
    }

    public function test_resolve_ignores_a_scalar_parameter_without_calling_handle(): void
    {
        $locator = new SpyLocator;
        $locator->answer = new Injectable;

        $args = [];

        $this->assertFalse($locator->resolve(
            $this->parameter(fn (int $count) => null),
            $this->reflector(fn (int $count) => null),
            $this->route,
            $args,
            0
        ));

        $this->assertSame([], $locator->types);
        $this->assertSame([], $args);
    }

    public function test_resolve_hands_the_reflector_and_the_route_to_handle(): void
    {
        $locator  = new SpyLocator;
        $reflector = $this->reflector(fn (Injectable $service) => null);

        $args = [];

        $locator->resolve(
            $this->parameter(fn (Injectable $service) => null),
            $reflector,
            $this->route,
            $args,
            0
        );

        $this->assertSame($reflector, $locator->context[0]['reflector']);
        $this->assertSame($this->route, $locator->context[0]['route']);
    }

    /* -----------------------------------------------------------------
     |  inject()
     | -----------------------------------------------------------------
     */

    public function test_inject_writes_the_value_at_the_given_position(): void
    {
        $args = ['avant', 'apres'];

        (new SpyLocator)->inject($args, 'valeur', 1);

        $this->assertSame(['avant', 'valeur'], $args);
    }

    public function test_inject_extends_the_array_when_the_position_is_out_of_range(): void
    {
        $args = [];

        (new SpyLocator)->inject($args, 'valeur', 2);

        $this->assertSame([2 => 'valeur'], $args);
    }

    /* -----------------------------------------------------------------
     |  createInstance()
     | -----------------------------------------------------------------
     */

    public function test_create_instance_resolves_a_class_through_the_container(): void
    {
        $locator = new SpyLocator;

        $locator->makeInstance(Injectable::class);

        $this->assertInstanceOf(Injectable::class, $locator->instance());
    }

    /**
     * Convention du conteneur : la classe d'un contrat est son nom amputé des
     * 9 derniers caractères (« Interface »).
     */
    public function test_create_instance_derives_the_class_from_the_interface_name(): void
    {
        $locator = new SpyLocator;

        $locator->makeInstance(InjectableInterface::class);

        $this->assertInstanceOf(Injectable::class, $locator->instance());
    }

    public function test_create_instance_fails_when_the_derived_class_does_not_exist(): void
    {
        $locator = new SpyLocator;

        $this->expectException(ContainerDepencyException::class);
        $this->expectExceptionMessage(
            'Interface ' . OddContract::class . ' is not instaciable; try resolving to class Clicalmani\Core\Tests\Stubs\Od'
        );

        $locator->makeInstance(OddContract::class);
    }

    /* -----------------------------------------------------------------
     |  Configuration
     | -----------------------------------------------------------------
     */

    public function test_the_constructor_seeds_the_type(): void
    {
        $this->assertNull((new SpyLocator)->type());
        $this->assertSame(Injectable::class, (new SpyLocator(Injectable::class))->type());
    }

    public function test_set_type_replaces_the_seeded_type(): void
    {
        $locator = new SpyLocator(Injectable::class);

        $locator->setType(InjectableChild::class);

        $this->assertSame(InjectableChild::class, $locator->type());
    }

    public function test_the_setters_are_fluent(): void
    {
        $locator = new SpyLocator;

        $this->assertSame($locator, $locator->setType(Injectable::class));
        $this->assertSame($locator, $locator->setRoute($this->route));
        $this->assertSame($locator, $locator->setReflection($this->reflector(fn () => null)));
    }
}
