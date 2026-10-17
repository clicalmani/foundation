<?php

declare(strict_types=1);

namespace Clicalmani\Core\Tests\Stubs;

/**
 * Classe fille utilisée pour vérifier l'ordre de listTypes() :
 * classe, parents, interfaces, puis joker « * ».
 */
class InjectableChild extends Injectable implements \JsonSerializable
{
    public function jsonSerialize(): mixed
    {
        return $this->label;
    }
}
