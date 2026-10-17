<?php

namespace Clicalmani\Core\Cache\Adapters;

use Psr\Cache\CacheItemInterface;

class DatabaseCacheItem implements CacheItemInterface
{
    /**
     * Valeur de l'item (null si miss).
     */
    protected mixed $value = null;

    /**
     * True si l'item a été trouvé en base et n'est pas expiré.
     */
    protected bool $isHit = false;

    /**
     * Timestamp d'expiration (null = permanent).
     */
    protected ?int $expiration = null;

    /**
     * True si la valeur a été modifiée (pour décider d'un INSERT vs UPDATE).
     */
    protected bool $dirty = false;

    public function __construct(protected string $key) {}

    public function getKey(): string
    {
        return $this->key;
    }

    public function get(): mixed
    {
        return $this->isHit ? $this->value : null;
    }

    public function isHit(): bool
    {
        return $this->isHit;
    }

    public function set(mixed $value): static
    {
        $this->value = $value;
        $this->dirty = true;
        return $this;
    }

    public function expiresAt(?\DateTimeInterface $expiration): static
    {
        $this->expiration = $expiration?->getTimestamp();
        $this->dirty = true;
        return $this;
    }

    public function expiresAfter(int|\DateInterval|null $time): static
    {
        if ($time === null) {
            $this->expiration = null;
        } elseif ($time instanceof \DateInterval) {
            $this->expiration = (new \DateTimeImmutable())->add($time)->getTimestamp();
        } else {
            $this->expiration = time() + $time;
        }

        $this->dirty = true;
        return $this;
    }

    /* -----------------------------------------------------------------
     |  Méthodes internes (utilisées uniquement par DatabaseAdapter)
     | -----------------------------------------------------------------
     */

    /**
     * Marque l'item comme « hit » et hydrate sa valeur + expiration.
     * Appelée par l'adapter après un SELECT réussi.
     */
    public function markHit(mixed $value, ?int $expiration): static
    {
        $this->value = $value;
        $this->expiration = $expiration;
        $this->isHit = true;
        $this->dirty = false;

        return $this;
    }

    /**
     * Marque l'item comme « miss » (absent ou expiré).
     */
    public function markMiss(): static
    {
        $this->value = null;
        $this->expiration = null;
        $this->isHit = false;
        $this->dirty = false;

        return $this;
    }

    public function isDirty(): bool
    {
        return $this->dirty;
    }

    public function getExpiration(): ?int
    {
        return $this->expiration;
    }
}