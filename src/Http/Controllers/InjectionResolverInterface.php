<?php
namespace Clicalmani\Core\Http\Controllers;

use Clicalmani\Routing\Route;

interface InjectionResolverInterface
{
    /**
     * Tente de résoudre le paramètre à la position donnée.
     * 
     * @return bool True si ce resolver a pris en charge ce paramètre.
     */
    public function resolve(
        \ReflectionParameter $parameter,
        ReflectorInterface $reflector,
        Route $route,
        array &$args,
        int $position
    ): bool;
}