<?php
namespace Clicalmani\Core\Support\Facades;

use Clicalmani\Database\Concerns\IsolationLevel;
use Clicalmani\Database\Concerns\IsolationScope;
use Clicalmani\Database\Contracts\PreparedStatementInterface;
use Clicalmani\Database\Contracts\QueryExecutorInterface;
use Clicalmani\Database\QueryInterface;
use Clicalmani\Core\Collection\CollectionInterface;
use PDO;
use PDOStatement;
use stdClass;

/**
 * @method static QueryExecutorInterface executor()
 * @method static string getPrefix() Returns the default database table prefix.
 * @method static QueryInterface getInstance() Returns a single database instance.
 * @method static PDO getPdo() Returns PDO instance.
 * @method static void setPdo(PDO $pdo) Set PDO instance.
 * @method static ?PDOStatement query(string $sql, ?array $options = [], ?array $flags = []) Execute a SQL query.
 * @method static mixed raw(string $expression)
 * @method static void enableQueryLog(int $limit = 1000) Enable query log.
 * @method static void disableQueryLog() Disable query log.
 * @method static void flushQueryLog() Flush query log.
 * @method static array getQueryLog() Get query log.
 * @method static bool execute(string $sql, array $params = []) Execute a SQL query.
 * @method static mixed fetch(PDOStatement $statement, int $flag = PDO::FETCH_BOTH) Fetch a result set.
 * @method static mixed fetchAll($statement, int $flag = PDO::FETCH_BOTH) Fetch all rows from a result set.
 * @method static mixed getRow($statement, ?int $flag = PDO::FETCH_NUM) Fetch the first row from a result set.
 * @method static int numRows(PDOStatement $statement) Returns the number of rows in the result set.
 * @method static int foundRows() Returns rows count for CALC_FOUND_ROWS enabled statements.
 * @method static PreparedStatementInterface prepare(string $sql, array $options = []) Prepare a statement.
 * @method static string error() Fetch extended error information.
 * @method static int errno() Fetch the SQLSTATE code.
 * @method static int|string|null lastInsertId() Returns the ID of the last inserted row or sequence value.
 * @method static ?bool free(PDOStatement $statement) Destroy a statement.
 * @method static mixed transaction(?\Closure $callback = null) Begins a database transaction.
 * @method static mixed transactionWith(IsolationLevel $level, \Closure $callback)
 * @method static mixed beginTransaction(?\Closure $callback = null) Alias of transaction.
 * @method static mixed deadlock(\Closure $callback, int $attemps = 5, int $sleep = 100) Handle transaction deadlock.
 * @method static bool commit() Validate a transaction.
 * @method static bool rollback() Abort a transaction.
 * @method static bool inTransaction() Check if inside a transaction.
 * @method static PDOStatement savePoint(string $name) Create a save point.
 * @method static PDOStatement rollbackTo(string $savepoint) Rollback to a specified save point.
 * @method static void isolateTransaction(IsolationLevel $level, IsolationScope $scope = IsolationScope::NEXT_TRANSACTION) Isolate a transaction.
 * @method static void close() Destroy the database connection.
 * @method static QueryInterface table(array|string $tables) Select a database table on which to execute a SQL query.
 * @method static CollectionInterface select(string $sql, ?array $options = [], ?array $flags = []) Select raw SQL query.
 * @method static mixed selectOne(string $sql, ?array $options = [], ?array $flags = []) Select one raw SQL query.
 * @method static PDOStatement statement(string $sql, ?array $options = [], ?array $flags = []) Execute a raw SQL query.
 * @method static PDOStatement unprepared(string $sql) Execute an unprepared raw SQL query.
 * @method static QueryInterface connection(?string $driver = null) Establish a database connection.
 * @method static mixed swap(PDO|QueryExecutorInterface|string $connection)
 * @method static void restore()
 * @method static mixed withConnection(PDO|QueryExecutorInterface|string $connection, callable $callback)
 * @method static void reset()
 * @method static bool hasSwaps()
 * @method static int swapDepth()
 * @method static callable listen(callable $listener) Register a query listener.
 * @method static mixed captureQueries(callable $callback, ?callable $onDone = null)
 * @method static string getDriverName() Returns the name of the database driver.
 */
abstract class DB extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'database';
    }

    /**
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic(string $method, array $arguments): mixed
    {
        return static::getFacadeRoot()?->$method(...$arguments);
    }
}