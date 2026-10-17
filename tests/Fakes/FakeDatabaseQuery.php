<?php

declare(strict_types=1);

namespace Clicalmani\Core\Tests\Fakes;

/**
 * Builder de requête factice : il rejoue sur la table en mémoire ce que
 * DatabaseAdapter demande, avec les mêmes types de retour que
 * Clicalmani\Database\QueryInterface (update(): int, delete()/truncate(): bool).
 */
final class FakeDatabaseQuery
{
    /** @var array<int, array{key: string, value: string, expiration: int}>|null */
    private ?array $rows = null;

    public function __construct(
        private readonly FakeDatabaseConnection $db,
        private readonly string $table,
    ) {}

    public function where(string $sql, array $bindings = []): static
    {
        $this->db->record(['where', ['sql' => $sql, 'bindings' => $bindings]]);

        $this->rows = $this->db->select($this->table, $sql, $bindings);

        return $this;
    }

    public function lockForUpdate(): static
    {
        $this->db->record(['lockForUpdate', $this->table]);

        return $this;
    }

    public function first(): mixed
    {
        $this->db->record(['first', $this->table]);

        return $this->selected()[0] ?? null;
    }

    /** @return array<int, array{key: string, value: string, expiration: int}> */
    public function get(string $fields = '*'): array
    {
        $this->db->record(['get', $this->table]);

        return $this->selected();
    }

    public function update(?array $options = []): int
    {
        $rows = $this->selected();

        foreach ($rows as $row) {
            $this->db->patch($this->table, $row['key'], $options ?? []);
        }

        $this->db->record(['update', ['values' => $options, 'affected' => count($rows)]]);

        return count($rows);
    }

    public function insert(array $options = []): object
    {
        $this->db->insertRow($this->table, $options);

        $this->db->record(['insert', $options]);

        return new \stdClass;
    }

    public function delete(): bool
    {
        $keys = array_column($this->selected(), 'key');

        $this->db->forget($this->table, $keys);

        $this->db->record(['delete', count($keys)]);

        return true;
    }

    public function truncate(): bool
    {
        $this->db->truncateTable($this->table);

        $this->db->record(['truncate', $this->table]);

        return true;
    }

    /** @return array<int, array{key: string, value: string, expiration: int}> */
    private function selected(): array
    {
        if ($this->rows === null) {
            throw new \LogicException('FakeDatabaseQuery: where() doit précéder la lecture.');
        }

        return $this->rows;
    }
}
