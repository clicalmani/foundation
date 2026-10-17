<?php

declare(strict_types=1);

namespace Clicalmani\Core\Tests\Http\Controllers;

use Clicalmani\Core\Http\Controllers\FunctionReflector;
use Clicalmani\Core\Http\Controllers\InjectionManager;
use Clicalmani\Core\Http\Controllers\ReflectorInterface;
use Clicalmani\Core\Support\Facades\Route as RouteFacade;
use Clicalmani\Core\Tests\Fakes\SpyInjectionResolver;
use Clicalmani\Core\Tests\Stubs\AcceptingResolver;
use Clicalmani\Core\Tests\Stubs\Injectable;
use Clicalmani\Core\Tests\Stubs\InjectableInterface;
use Clicalmani\Core\Tests\TestCase;
use Clicalmani\Routing\Factory\LaravelStyleBuilder;
use Clicalmani\Routing\Registry;
use Clicalmani\Routing\Route;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class InjectionManagerTest extends TestCase
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

        RouteFacade::get('/users/{id}/posts/{post}', fn () => 'ok');

        $this->route = Registry::getRoutes()['get'][0];

        $parameters = $this->route->getParameters();

        // Les segments ne portent une valeur qu'après appariement d'une URI :
        // on les fixe à la main pour rendre la résolution déterministe.
        $parameters[0]->value = '42';
        $parameters[1]->value = '7';
    }

    protected function tearDown(): void
    {
        Registry::reset();

        parent::tearDown();
    }

    private function manager(?ContainerBuilder $container = null): InjectionManager
    {
        return new InjectionManager($container ?? new ContainerBuilder);
    }

    private function resolve(\Closure $signature, ?InjectionManager $manager = null): array
    {
        $reflector = new FunctionReflector(new \ReflectionFunction($signature));

        return ($manager ?? $this->manager())->resolve($reflector, $this->route);
    }

    /** @param array<int, mixed> $args */
    private function readable(array $args): array
    {
        return array_map(fn ($value) => is_object($value) ? $value::class : $value, $args);
    }

    /* -----------------------------------------------------------------
     |  Paramètres scalaires
     | -----------------------------------------------------------------
     */

    public function test_scalar_parameters_consume_the_route_segments_in_order(): void
    {
        $this->assertSame(['42', '7'], $this->resolve(fn (string $id, string $post) => null));
    }

    public function test_scalars_keep_their_declared_order_whatever_their_type(): void
    {
        $args = $this->resolve(fn (int $id, string $post) => null);

        $this->assertSame('42', $args[0]);
        $this->assertSame('7', $args[1]);
    }

    public function test_missing_segments_leave_the_argument_null(): void
    {
        $this->assertSame(['42', '7', null], $this->resolve(fn (string $a, string $b, string $c) => null));
    }

    public function test_a_route_without_parameters_leaves_every_scalar_null(): void
    {
        Registry::reset();

        RouteFacade::get('/plain', fn () => 'ok');

        $route = Registry::getRoutes()['get'][0];

        $reflector = new FunctionReflector(new \ReflectionFunction(fn (string $a, int $b) => null));

        $this->assertSame([null, null], $this->manager()->resolve($reflector, $route));
    }

    /**
     * FIXME : les valeurs par défaut de la signature ne sont jamais lues.
     * Un paramètre sans segment correspondant reçoit null, pas son défaut —
     * ce qui provoque un TypeError à l'invocation sur un scalaire non nullable.
     */
    public function test_default_values_are_ignored(): void
    {
        $this->assertSame(['42', '7'], $this->resolve(fn (int $a = 5, string $b = 'x') => null));

        Registry::reset();

        RouteFacade::get('/plain', fn () => 'ok');

        $route = Registry::getRoutes()['get'][0];

        $this->assertSame(
            [null],
            $this->manager()->resolve(new FunctionReflector(new \ReflectionFunction(fn (int $a = 5) => null)), $route)
        );
    }

    /**
     * Un paramètre sans type n'est ni un scalaire (pas de segment consommé)
     * ni une classe (pas de resolver) : il reste null.
     */
    public function test_an_untyped_parameter_stays_null_and_keeps_the_segments(): void
    {
        $this->assertSame([null, '42'], $this->resolve(fn ($anything, string $id) => null));
    }

    public function test_a_signature_without_parameters_resolves_to_an_empty_list(): void
    {
        $this->assertSame([], $this->resolve(fn () => null));
    }

    /**
     * Un variadique n'occupe qu'une seule position : il ne reçoit que le
     * premier segment disponible.
     */
    public function test_a_variadic_parameter_gets_a_single_slot(): void
    {
        $this->assertSame(['42'], $this->resolve(fn (string ...$rest) => null));
    }

    /* -----------------------------------------------------------------
     |  Paramètres typés par classe
     | -----------------------------------------------------------------
     */

    public function test_class_parameters_do_not_consume_route_segments(): void
    {
        $args = $this->resolve(fn (string $id, Injectable $service, int $post) => null);

        $this->assertSame(['42', null, '7'], $this->readable($args));
    }

    public function test_the_container_serves_as_a_fallback_for_class_parameters(): void
    {
        $container = new ContainerBuilder;
        $service   = new Injectable('service');

        $container->set(Injectable::class, $service);

        $args = $this->resolve(fn (Injectable $injected) => null, $this->manager($container));

        $this->assertSame($service, $args[0]);
    }

    public function test_an_unresolvable_class_is_left_null_instead_of_failing(): void
    {
        $args = $this->resolve(fn (Injectable $service, \DateTimeImmutable $date) => null);

        $this->assertSame([null, null], $args);
    }

    public function test_an_interface_is_resolved_only_if_the_container_knows_it(): void
    {
        $this->assertSame([null], $this->resolve(fn (InjectableInterface $contract) => null));

        $container = new ContainerBuilder;
        $container->set(InjectableInterface::class, new Injectable);

        $args = $this->resolve(fn (InjectableInterface $contract) => null, $this->manager($container));

        $this->assertInstanceOf(Injectable::class, $args[0]);
    }

    /**
     * FIXME : même cause que dans InjectionLocator — isBuiltin() n'existe pas
     * sur ReflectionUnionType, donc resolve() s'effondre au lieu de déléguer
     * aux resolvers.
     */
    public function test_a_union_type_breaks_the_resolution(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Call to undefined method ReflectionUnionType::isBuiltin()');

        $this->resolve(fn (int|Injectable $mixed) => null);
    }

    /* -----------------------------------------------------------------
     |  Resolvers
     | -----------------------------------------------------------------
     */

    public function test_the_first_resolver_that_accepts_wins(): void
    {
        $first  = new SpyInjectionResolver(accepts: true, value: 'premier');
        $second = new SpyInjectionResolver(accepts: true, value: 'second');

        $manager = $this->manager()->register($first)->register($second);

        $this->assertSame(['premier'], $this->resolve(fn (Injectable $service) => null, $manager));

        $this->assertCount(1, $first->calls);
        $this->assertSame([], $second->calls);
    }

    public function test_a_resolver_that_declines_leaves_the_slot_to_the_next_one(): void
    {
        $first  = new SpyInjectionResolver(accepts: false);
        $second = new SpyInjectionResolver(accepts: true, value: 'second');

        $manager = $this->manager()->register($first)->register($second);

        $this->assertSame(['second'], $this->resolve(fn (Injectable $service) => null, $manager));

        $this->assertSame([['parameter' => 'service', 'position' => 0]], $first->calls);
        $this->assertSame([['parameter' => 'service', 'position' => 0]], $second->calls);
    }

    public function test_resolvers_never_see_the_scalar_parameters(): void
    {
        $resolver = new SpyInjectionResolver(accepts: false);

        $this->resolve(fn (string $id, int $post) => null, $this->manager()->register($resolver));

        $this->assertSame([], $resolver->calls);
    }

    public function test_the_container_fallback_runs_only_after_every_resolver_declined(): void
    {
        $resolver  = new SpyInjectionResolver(accepts: false);
        $container = new ContainerBuilder;

        $container->set(Injectable::class, new Injectable('du-conteneur'));

        $args = $this->resolve(fn (Injectable $service) => null, $this->manager($container)->register($resolver));

        $this->assertCount(1, $resolver->calls);
        $this->assertSame('du-conteneur', $args[0]->label);
    }

    public function test_register_class_instantiates_the_resolver(): void
    {
        $manager = $this->manager()->registerClass(AcceptingResolver::class);

        $this->assertSame(
            [AcceptingResolver::MARKER],
            $this->resolve(fn (Injectable $service) => null, $manager)
        );
    }

    /**
     * FIXME : registerClass() accepte n'importe quelle chaîne. Une classe
     * inexistante est stockée telle quelle dans une liste typée
     * « InjectionResolverInterface[] » et l'erreur n'apparaît qu'à resolve().
     */
    public function test_register_class_with_an_unknown_class_breaks_resolve(): void
    {
        $manager = $this->manager()->registerClass('Clicalmani\Core\Tests\Stubs\Absent');

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Call to a member function resolve() on string');

        $this->resolve(fn (Injectable $service) => null, $manager);
    }

    public function test_register_resolvers_refuses_anything_that_is_not_a_resolver(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Resolver must implement %s, %s given.',
            \Clicalmani\Core\Http\Controllers\InjectionResolverInterface::class,
            Injectable::class
        ));

        $this->manager()->registerResolvers([new Injectable]);
    }

    public function test_register_resolvers_appends_to_the_already_registered_ones(): void
    {
        $declining = new SpyInjectionResolver(accepts: false);
        $accepting = new SpyInjectionResolver(accepts: true, value: 'par-lot');

        $manager = $this->manager()->register($declining)->registerResolvers([$accepting]);

        $this->assertSame(['par-lot'], $this->resolve(fn (Injectable $service) => null, $manager));
        $this->assertCount(1, $declining->calls);
    }

    public function test_register_resolvers_keeps_the_entries_validated_before_the_failure(): void
    {
        $manager = $this->manager();

        try {
            $manager->registerResolvers([
                new SpyInjectionResolver(accepts: true, value: 'accepte'),
                new Injectable,
            ]);

            $this->fail('Un resolver invalide aurait dû être refusé.');
        } catch (\InvalidArgumentException) {
            $this->assertSame(['accepte'], $this->resolve(fn (Injectable $service) => null, $manager));
        }
    }

    public function test_the_registration_methods_are_fluent(): void
    {
        $manager = $this->manager();

        $this->assertSame($manager, $manager->register(new SpyInjectionResolver));
        $this->assertSame($manager, $manager->registerClass(AcceptingResolver::class));
        $this->assertSame($manager, $manager->registerResolvers([]));
    }

    /* -----------------------------------------------------------------
     |  Réflecteur
     | -----------------------------------------------------------------
     */

    public function test_the_reflector_decides_the_parameter_list(): void
    {
        $reflector = $this->createStub(ReflectorInterface::class);
        $reflector->method('getParameters')
            ->willReturn((new \ReflectionFunction(fn (string $id) => null))->getParameters());

        $this->assertSame(['42'], $this->manager()->resolve($reflector, $this->route));
    }
}
