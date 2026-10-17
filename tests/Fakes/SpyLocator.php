<?php

declare(strict_types=1);

namespace Clicalmani\Core\Tests\Fakes;

use Clicalmani\Core\Http\Controllers\InjectionLocator;
use Clicalmani\Core\Http\Controllers\ReflectorInterface;
use Clicalmani\Routing\Route;

/**
 * InjectionLocator concret pour les tests : handle() journalise les types qui
 * lui sont soumis et renvoie la réponse programmée (objet, null ou exception).
 */
final class SpyLocator extends InjectionLocator
{
    /** @var list<string> Types présentés à handle(), dans l'ordre. */
    public array $types = [];

    /** @var list<array{reflector: ?ReflectorInterface, route: ?Route}> */
    public array $context = [];

    public ?object $answer = null;

    public bool $throws = false;

    public function handle(): ?object
    {
        $this->types[] = (string) $this->class;

        $this->context[] = [
            'reflector' => $this->reflector ?? null,
            'route'     => $this->route ?? null,
        ];

        if ($this->throws) {
            throw new \RuntimeException('handle() a échoué');
        }

        return $this->answer;
    }

    /** @return array<string, string> */
    public function supportedTypes(\ReflectionParameter $parameter): array
    {
        return $this->getSupportedTypes($parameter);
    }

    public function makeInstance(string $class): void
    {
        $this->setType($class);
        $this->createInstance();
    }

    public function type(): ?string
    {
        return $this->class;
    }

    public function instance(): ?object
    {
        return $this->instance ?? null;
    }
}
