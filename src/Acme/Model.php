<?php
namespace Clicalmani\Foundation\Acme;

/**
 * @method static void resolveRouteBindingUsing(\Closure $callback) Resolve route binding using a callback.
 * @method static void preventSilentlyDiscardingAttributes() Prevent silent discard attribute setting
 * @method static bool destroy() Destroy all records in the table
 * @method static static create(array $attributes = [], bool $update = false) Create a new record and return the instance.
 * @method static createOrFail(array $fields = [], ?bool $replace = false) Create a new record or fail.
 * @method static static|null find(string|array|null $id) Returns a specified row defined by a specified primary key.
 * @method static \Clicalmani\Database\Factory\Models\ModelInterface findOrFail(string|array|null $id) Returns a specified row defined by a specified primary key or fail.
 * @method static \Clicalmani\Foundation\Collection\CollectionInterface all() Returns all rows from the query statement result.
 * @method static \Clicalmani\Foundation\Collection\CollectionInterface filter(array $exclude = [], array $options = []) Filter the query result by using the request parameters.
 * @method static \Clicalmani\Database\Factory\FactoryInterface seed() Override: Create a seed for the model.
 * @method static \Clicalmani\Database\Factory\Models\ModelInterface on(?string $connection = null) Switch model connection.
 * @method static static where(mixed ...$args) Add a where clause to the query. 
 */
abstract class Model extends \Clicalmani\Database\Factory\Models\Elegant
{
    /**
     * Prefix conventionnel identifiant une méthode de scope.
     * 
     * @var string
     */
    protected const SCOPE_PREFIX = 'scope';

    /**
     * Handle dynamic static calls that correspond to local query scopes.
     * 
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        if (static::isScope($name)) {
            return static::callScope($name, $arguments);
        }

        throw new \BadMethodCallException(
            sprintf('Call to undefined method %s::%s()', static::class, $name)
        );
    }

    public function __call($name, $arguments)
    {
        return static::__callStatic($name, $arguments);
    }

    /**
     * Verify if the given static call name maps to a scope method.
     * 
     * @param string $name
     * @return bool
     */
    protected static function isScope(string $name) : bool
    {
        return method_exists(static::class, static::getScopeMethodName($name));
    }

    /**
     * Instantiate the calling model and forward the call to its scope method.
     * 
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    protected static function callScope(string $name, array $arguments) : mixed
    {
        $instance = new static();
        $method = static::getScopeMethodName($name);

        return $instance->{$method}(...$arguments);
    }

    /**
     * Build the real method name behind a scope call.
     * 
     * @param string $name
     * @return string
     */
    protected static function getScopeMethodName(string $name) : string
    {
        return static::SCOPE_PREFIX . ucfirst($name);
    }
}