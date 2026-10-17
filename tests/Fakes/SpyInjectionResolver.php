<?php

declare(strict_types=1);

namespace Clicalmani\Core\Tests\Fakes;

use Clicalmani\Core\Http\Controllers\InjectionResolverInterface;
use Clicalmani\Core\Http\Controllers\ReflectorInterface;
use Clicalmani\Routing\Route;

/**
 * Resolver espion : il journalise les paramètres qu'on lui présente et répond
 * selon la consigne donnée au constructeur.
 */
final class SpyInjectionResolver implements InjectionResolverInterface
{
    /** @var list<array{parameter: string, position: int}> */
    public array $calls = [];

    public function __construct(
        private readonly bool $accepts = false,
        private readonly mixed $value = null,
    ) {}

    public function resolve(
        \ReflectionParameter $parameter,
        ReflectorInterface $reflector,
        Route $route,
        array &$args,
        int $position
    ): bool {
        $this->calls[] = ['parameter' => $parameter->getName(), 'position' => $position];

        if (!$this->accepts) {
            return false;
        }

        $args[$position] = $this->value;

        return true;
    }
}
