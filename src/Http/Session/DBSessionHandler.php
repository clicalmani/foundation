<?php
namespace Clicalmani\Foundation\Http\Session;

use Clicalmani\Foundation\Support\Facades\DB;

class DBSessionHandler extends SessionHandler
{
    private $pdo, $driver;

    public function __construct(bool $encrypt, ?array $flags = [])
    {
        parent::__construct($encrypt, $flags);
        $this->driver = $flags['driver'] ?? '';
    }

    public function open(string $path, string $id) : bool
    {
        $this->pdo = DB::connection($this->driver);
        return ($this->pdo !== null);
    }

    #[\ReturnTypeWillChange]
    public function read(string $id): string|false
    {
        $stmt = $this->pdo->prepare("SELECT `data` FROM {$this->table} WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(\PDO::FETCH_NUM);

        return $this->decrypt($result[0] ?? '');
    }

    public function write(string $id, string $data): bool
    {
        $access = time();
        $stmt = $this->pdo->prepare("REPLACE INTO {$this->table} (`id`, `access`, `data`) VALUES (:id, :access, :data)");
        return $stmt->execute([
            ':id' => $id, 
            ':access' => $access, 
            ':data' => $this->encrypt($data)
        ]);
    }

    public function destroy(string $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function close(): bool
    {
        $this->pdo = null;
        return true;
    }

    #[\ReturnTypeWillChange]
    public function gc(int $max_lifetime): int|false
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE `access` < :time");
        $stmt->execute([':time' => time() - $max_lifetime]);
        return $stmt->rowCount();
    }

    #[\ReturnTypeWillChange]
    public function create_sid()
    {
        return (string)session_create_id($this->getIdPrefix());
    }

    public function validate_sid(string $id)
    {
        $maxLifetime = (int) ini_get('session.gc_maxlifetime');

        $stmt = $this->pdo->prepare("
            SELECT 1 
            FROM {$this->table} 
            WHERE id = :id 
            AND access > :time_limit
        ");

        $stmt->execute([
            ':id' => $id, 
            ':time_limit' => time() - $maxLifetime
        ]);
        
        return $stmt->fetch() !== false;
    }

    public function __destruct()
    {
        $this->close();
    }
}
