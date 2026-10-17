<?php

declare(strict_types=1);

namespace Clicalmani\Core\Tests\Fakes;

use Psr\Cache\CacheItemInterface;

/**
 * Item PSR-6 minimal qui enregistre les appels à expiresAfter()/expiresAt().
 * Utilisé pour vérifier que CacheStore propage bien le TTL, sans dépendre
 * d'une horloge réelle.
 */
final class RecordingCacheItem implements CacheItemInterface
{
    /** @var list<array{0: string, 1: mixed}> */
    public array $calls = [];

    private mixed $value = null;

    private bool $hit = false;

    private ?int $expiry = null;

    public function __construct(
        private readonly string $key,
        private readonly ?RecordingCachePool $pool = null,
    ) {}

    public function getKey(): string
    {
        return $this->key;
    }

    public function get(): mixed
    {
        return $this->value;
    }

    public function isHit(): bool
    {
        return $this->hit;
    }

    public function set(mixed $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function expiresAt(?\DateTimeInterface $expiration): static
    {
        $this->expiry = $expiration?->getTimestamp();
        $this->record('expiresAt', $this->expiry);

        return $this;
    }

    public function expiresAfter(int|\DateInterval|null $time): static
    {
        $this->expiry = $time === null
            ? null
            : ($time instanceof \DateInterval
                ? (new \DateTimeImmutable)->add($time)->getTimestamp()
                : time() + $time);

        $this->record('expiresAfter', $time);

        return $this;
    }

    public function getExpiry(): ?int
    {
        return $this->expiry;
    }

    /**
     * Hydrate l'item depuis le stockage du pool.
     */
    public function hydrate(mixed $value, bool $hit, ?int $expiry): static
    {
        $this->value  = $value;
        $this->hit    = $hit;
        $this->expiry = $expiry;

        return $this;
    }

    private function record(string $method, mixed $argument): void
    {
        $this->calls[] = [$method, $argument];

        $this->pool?->recordItemCall($this->key, [$method, $argument]);
    }
}
