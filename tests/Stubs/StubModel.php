<?php

declare(strict_types=1);

namespace Clicalmani\Core\Tests\Stubs;

/**
 * Simple POPO utilisé comme type « classe » dans les signatures du stub de
 * contrôleur (RequestReflectionControllerStub).
 */
class StubModel
{
    public function __construct(public string $name = '')
    {
    }
}
