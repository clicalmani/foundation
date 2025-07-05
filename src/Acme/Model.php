<?php
namespace Clicalmani\Foundation\Acme;

/**
 * @method static void resolveRouteBindingUsing(\Closure $callback) Resolve route binding using a callback.
 * @method static void preventSilentlyDiscardingAttributes() Prevent silent discard attribute setting
 * @method static bool destroy() Destroy all records in the table
 * @method static \Clicalmani\Database\Factory\Models\ModelInterface create(array $attributes = [], bool $replace = false) Create a new record and return the instance.
 * @method static createOrFail(array $fields = [], ?bool $replace = false) Create a new record or fail.
 * @method static \Clicalmani\Database\Factory\Models\ModelInterface|\Clicalmani\Database\Factory\Models\SoftDeleteInterface|null find(string|array|null $id) Returns a specified row defined by a specified primary key.
 * @method static \Clicalmani\Database\Factory\Models\ModelInterface findOrFail(string|array|null $id) Returns a specified row defined by a specified primary key or fail.
 * @method static \Clicalmani\Foundation\Collection\CollectionInterface all() Returns all rows from the query statement result.
 * @method static \Clicalmani\Foundation\Collection\CollectionInterface filter(array $exclude = [], array $options = []) Filter the query result by using the request parameters.
 * @method static \Clicalmani\Database\Factory\FactoryInterface seed() Override: Create a seed for the model.
 * @method static \Clicalmani\Database\Factory\Models\ModelInterface on(?string $connection = null) Switch model connection.
 * @method static \Clicalmani\Database\Factory\Models\ModelInterface|\Clicalmani\Database\Factory\Models\SoftDeleteInterface where(?string $criteria = '1', ?array $options = [])
 */
abstract class Model extends \Clicalmani\Database\Factory\Models\Elegant
{}