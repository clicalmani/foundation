<?php

declare(strict_types=1);

namespace Clicalmani\Core\Tests\Stubs;

use Clicalmani\Core\Http\Controllers\InjectionResolverInterface;
use Clicalmani\Core\Http\Controllers\ReflectorInterface;
use Clicalmani\Routing\Route;

/**
 * Resolver instanciable sans argument, pour exercer registerClass() :
 * il accepte n'importe quel paramètre et y injecte un marqueur.
 */
class AcceptingResolver implements InjectionResolverInterface
{
    public const MARKER = 'resolu-par-classe';

    public function resolve(
        \ReflectionParameter $parameter,
        ReflectorInterface $reflector,
        Route $route,
        array &$args,
        int $position
    ): bool {
        $args[$position] = self::MARKER;

        return true;
    }
}
