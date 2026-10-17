<?php

declare(strict_types=1);

namespace Clicalmani\Core\Tests\Stubs;

/**
 * Contrat dont le nom ne suit PAS la convention « *Interface » : la dérivation
 * de Acme\Container::getImplementingClassInstance() (substr -9) produit une
 * classe inexistante, ce qui doit remonter en ContainerDepencyException.
 */
interface OddContract
{
}
