<?php
namespace Clicalmani\Foundation\Container;

interface ContainerInterface
{
    public function bind(string $abstract, $concrete = null, bool $shared = false): void;
    public function singleton(string $abstract, $concrete = null): void;
    public function make(string $abstract, array $parameters = []): mixed;
    public function has(string $abstract): bool;
    public function instance(string $abstract, $instance): void;
    public function get(string $abstract): mixed;
    public function call($callback, array $parameters = []): mixed;
    public function resolve(string $abstract): mixed;
    public function isShared(string $abstract): bool;
    public function forget(string $abstract): void;
    public function getBindings(): array;
    public function getInstances(): array;
    public function getAlias(string $abstract): string;
    public function getConcrete(string $abstract): mixed;
    public function getExtenders(string $abstract): array;
    public function getReboundCallbacks(string $abstract): array;
    public function rebound(string $abstract): void;
    public function wrap($callback, array $parameters = []): mixed;
    public function extend(string $abstract, $closure): void;
    public function afterResolving(string $abstract, $closure): void;
    public function tag(string $abstracts, array|string $tags): void;
    public function tagged(string $tag): array;
    public function bindIf(string $abstract, $concrete = null, bool $shared = false): void;
    public function when($concrete): mixed;
    public function factory(string $abstract): mixed;
    public function flushInstance(string $abstract): void;
    public function flushResolved(string $abstract): void;
    public function flushShared(string $abstract): void;
    public function flushTagged(string $tag): void;
    public function flushBindings(): void;
    public function flushInstances(): void;
    public function flushExtenders(): void;
    public function flushReboundCallbacks(): void;
    public function flushAfterResolvingCallbacks(): void;
    public function flushTags(): void;
    public function flushWhen(): void;
    public function flush(): void;
}