<?php
namespace Clicalmani\Foundation\Support\Facades;

/**
 * @method static string getPrefix() Returns the default database table prefix.
 * @method static void setConnection(string $driver = '') Returns a database connection by specifying the driver as argument.
 * @method static \Clicalmani\Database\Interfaces\QueryInterface getInstance() Returns a single database instance.
 * @method static \PDO getPdo() Returns PDO instance.
 * @method static void setPdo(\PDO $pdo) Set PDO instance.
 * @method static \PDOStatement query(string $sql, ?array $options = [], ?array $flags = []) Execute a SQL query.
 * @method static void enableQueryLog() Enable query log.
 * @method static int|false execute(string $sql) Execute a SQL query.
 * @method static mixed fetch(\PDOStatement $statement, int $flag = \PDO::FETCH_BOTH) Fetch a result set by returning an associative array.
 * @method static mixed fetchAll(\PDOStatement $statement, int $flag = \PDO::FETCH_BOTH) Fetch all rows from a result set.
 * @method static mixed getRow(\PDOStatement $statement, int $flag = \PDO::FETCH_NUM) Fetch the first row from a result set.
 * @method static int numRows(\PDOStatement $statement) Returns the number of rows in the result set
 * @method static int foundRows() Returns rows count for CALC_FOUND_ROWS enabled statements.
 * @method static \PDOStatement prepare(string $sql, ?array $options = []) 
 * @method static array error()  Fetch extended error information associated with the last operation on the database handle.
 * @method static string errno() Fetch the SQLSTATE associated with the last operation on the database handle.
 * @method static string|false lastInsertId() Returns the ID of the last inserted row or sequence value.
 * @method static ?bool free(\PDOStatement $statement) Destroy a statement.
 * @method static mixed transaction(?callable $callback = null) Begins a database transaction.
 * @method static mixed beginTransaction(?callable $callback = null) Alias of transaction
 * @method static mixed deadlock(callable $callback, int $attemps = 5, int $sleep = 100) Handle transaction deadlock.
 * @method static bool commit() Validate a transaction.
 * @method static bool rollback() Abort a transaction.
 * @method static bool inTransaction() Check if inside a transaction.
 * @method static \PDOStatement savePoint(string $name) Create a save point. 
 * @method static \PDOStatement rollbackTo(string $savepoint) Rollback to a specified save point.
 * @method static \PDOStatement isolateTransaction(int $isolation_lavel, string $scope = '') Isolate a transaction. 
 * @method static void close() Destroy the database connection. 
 * @method static \Clicalmani\Database\Interfaces\QueryInterface table(array|string $tables) Select a database table on which to execute a SQL query. 
 * @method static array select(string $sql, array $options = [], array $flags = []) Select raw SQL query. 
 * @method static array selectOne(string $sql, array $options = [], array $flags = []) Select one raw SQL query.
 * @method static \PDOStatement statement(string $sql, array $options = [], array $flags = []) Execute a raw SQL query.
 * @method static \PDOStatement unprepared(string $sql) Execute a raw SQL query
 * @method static \Clicalmani\Database\Interfaces\QueryInterface connection(string $driver = '') Establish a database connection.
 * @method static void listen(string $event, callable $callback) Listen for database query cumulative time. 
 */
abstract class DB extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'database';
    }
}