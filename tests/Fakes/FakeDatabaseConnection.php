<?php

declare(strict_types=1);

namespace Clicalmani\Core\Tests\Fakes;

/**
 * Connexion SQL factice pour DatabaseAdapter : une table en mémoire par nom,
 * plus un journal des appels. Le SQL de where() est reconnu par correspondance
 * exacte — toute nouvelle clause fait échouer le test au lieu de passer en
 * silence.
 */
final class FakeDatabaseConnection
{
    /** @var array<string, array<string, array{key: string, value: string, expiration: int}>> */
    private array $tables = [];

    /** @var list<array{0: string, 1: mixed}> */
    public array $calls = [];

    public int $transactions = 0;

    public function table(array|string $tables): FakeDatabaseQuery
    {
        $table = is_array($tables) ? (string) reset($tables) : $tables;

        $this->record(['table', $table]);

        return new FakeDatabaseQuery($this, $table);
    }

    public function transaction(?\Closure $callback = null): mixed
    {
        ++$this->transactions;

        $this->record(['transaction', null]);

        return $callback === null ? null : $callback();
    }

    /* -----------------------------------------------------------------
     |  API interne (utilisée par FakeDatabaseQuery et par les tests)
     | -----------------------------------------------------------------
     */

    public function record(array $call): void
    {
        $this->calls[] = $call;
    }

    /** @return array<int, array{key: string, value: string, expiration: int}> */
    public function select(string $table, string $sql, array $bindings): array
    {
        $rows = array_values($this->tables[$table] ?? []);

        if ($sql === '`key` = ?') {
            return array_values(array_filter($rows, fn (array $row) => $row['key'] === $bindings[0]));
        }

        if (preg_match('/^`key` IN \((?:\?(?:, ?)*)+\)$/', $sql) === 1) {
            return array_values(array_filter($rows, fn (array $row) => in_array($row['key'], $bindings, true)));
        }

        if ($sql === 'expiration > 0 AND expiration < ?') {
            return array_values(array_filter(
                $rows,
                fn (array $row) => $row['expiration'] > 0 && $row['expiration'] < $bindings[0]
            ));
        }

        throw new \LogicException("FakeDatabaseConnection: clause SQL non reconnue [{$sql}].");
    }

    public function seed(string $table, string $key, string $value, int $expiration = 0): void
    {
        $this->tables[$table][$key] = compact('key', 'value', 'expiration');
    }

    /** @return array<string, array{key: string, value: string, expiration: int}> */
    public function rows(string $table): array
    {
        return $this->tables[$table] ?? [];
    }

    /** @return array{key: string, value: string, expiration: int}|null */
    public function row(string $table, string $key): ?array
    {
        return $this->tables[$table][$key] ?? null;
    }

    /** @param array<string, mixed> $values */
    public function insertRow(string $table, array $values): void
    {
        $this->tables[$table][$values['key']] = [
            'key'        => $values['key'],
            'value'      => $values['value'],
            'expiration' => $values['expiration'] ?? 0,
        ];
    }

    /** @param array<string, mixed> $values */
    public function patch(string $table, string $key, array $values): void
    {
        foreach ($values as $column => $value) {
            $this->tables[$table][$key][$column] = $value;
        }
    }

    /** @param array<int, string> $keys */
    public function forget(string $table, array $keys): void
    {
        foreach ($keys as $key) {
            unset($this->tables[$table][$key]);
        }
    }

    public function truncateTable(string $table): void
    {
        $this->tables[$table] = [];
    }
}
