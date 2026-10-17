<?php

declare(strict_types=1);

namespace Clicalmani\Core\Tests\Stubs;

/**
 * Classe injectable simple, utilisée comme cible de résolution par le
 * conteneur dans les tests d'injection.
 */
class Injectable implements InjectableInterface
{
    public function __construct(public string $label = 'injectable')
    {
    }
}
