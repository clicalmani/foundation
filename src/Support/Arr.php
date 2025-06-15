<?php
namespace Clicalmani\Foundation\Support;

use Clicalmani\Foundation\Collection\Collection;
use Random\Randomizer;

/**
 * @method bool accessible(\ArrayAccess|array $array)
 * @method mixed get(\ArrayAccess|array $array, string|int|null $key, $default = null)
 * @method bool exists(\ArrayAccess|array $array, string|int|float $key)
 * @method void forget(\ArrayAccess|array $array, array|string|int|float $keys)
 * @method bool has(\ArrayAccess|array $array, string|array $keys)
 * @method bool hasAny(\ArrayAccess|array $array, string|array $keys)
 * @method bool isAssoc(\ArrayAccess|array $array)
 * @method bool isList(\ArrayAccess|array $array)
 * @method string join(\ArrayAccess|array $array, string $glue, string $finalGlue = '')
 * @method array mapWithKeys(\ArrayAccess|array $array, callable $callback)
 * @method array map(\ArrayAccess|array $array, callable $callback)
 * @method array explodePluckParameters(\ArrayAccess|array $array, string|array|null $key)
 * @method array pluck(\ArrayAccess|array $array, string|array|int|null $value, string|array|null $key = null)
 * @method array collapse(\ArrayAccess|array $array) Flatten a multi-dimensional array
 * @method array add(\ArrayAccess|array $array, string|int|float $key, mixed $value)
 * @method array set(\ArrayAccess|array $array, string|int|null $key, mixed $value)
 * @method array shuffle(\ArrayAccess|array $array)
 */
class Arr extends Mock
{
    public function __construct(private \ArrayAccess|array &$arr) {}

    /**
     * Determine whether the given value is array accessible.
     *
     * @param  mixed  $value
     * @return bool
     */
    public function _accessible()
    {
        return is_array($this->arr) || $this->arr instanceof \ArrayAccess;
    }

    /**
     * Get an item from an array using "dot" notation.
     * 
     * @param  string|int|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function _get(string|int|null $key, $default = null)
    {
        if (! $this->_accessible($this->arr)) {
            return value($default);
        }

        if (is_null($key)) {
            return $this->arr;
        }

        if ($this->_exists($key)) {
            return $this->arr[$key];
        }

        if (! str_contains($key, '.')) {
            return $this->arr[$key] ?? value($default);
        }

        foreach (explode('.', $key) as $segment) {
            if ($this->_accessible($this->arr) && $this->_exists($segment)) {
                $this->arr = $this->arr[$segment];
            } else {
                return value($default);
            }
        }

        return $this->arr;
    }

    /**
     * Determine if the given key exists in the provided array.
     * 
     * @param  string|int|float  $key
     * @return bool
     */
    public function _exists(string|int|float $key)
    {
        if ($this->arr instanceof \ArrayAccess) {
            return $this->arr->offsetExists($key);
        }

        if (is_float($key)) {
            $key = (string) $key;
        }

        return array_key_exists($key, $this->arr);
    }

    /**
     * Remove one or many array items from a given array using "dot" notation.
     * 
     * @param  array|string|int|float  $keys
     * @return void
     */
    public function _forget(array|string|int|float $keys)
    {
        $original = $this->arr;

        $keys = (array) $keys;

        if (count($keys) === 0) {
            return;
        }

        foreach ($keys as $key) {
            // if the exact key exists in the top-level, remove it
            if ($this->_exists($key)) {
                unset($this->arr[$key]);

                continue;
            }

            $parts = explode('.', $key);

            // clean up before each pass
            $this->arr = &$original;

            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($this->arr[$part])) {
                    $self = new self($this->arr[$part]);
                    if ($self->_accessible()) $this->arr = $this->arr[$part];
                } else {
                    continue 2;
                }
            }

            unset($this->arr[array_shift($parts)]);
        }
    }

    /**
     * Check if an item or items exist in an array using "dot" notation.
     * 
     * @param  string|array  $keys
     * @return bool
     */
    public function _has(string|array $keys)
    {
        $keys = (array) $keys;

        if (! $this->arr || $keys === []) {
            return false;
        }

        foreach ($keys as $key) {
            $subKeyArray = $this->arr;

            if ($this->_exists($key)) {
                continue;
            }

            $self = new self($subKeyArray);

            foreach (explode('.', $key) as $segment) {
                
                if ($self->_accessible() && $self->_exists($segment)) {
                    $subKeyArray = $subKeyArray[$segment];
                } else {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Determine if any of the keys exist in an array using "dot" notation.
     * 
     * @param  string|array  $keys
     * @return bool
     */
    public function _hasAny(string|array $keys)
    {
        if (is_null($keys)) {
            return false;
        }

        $keys = (array) $keys;

        if (! $this->arr) {
            return false;
        }

        if ($keys === []) {
            return false;
        }

        foreach ($keys as $key) {
            if ($this->_has($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determines if an array is associative.
     *
     * An array is "associative" if it doesn't have sequential numerical keys beginning with zero.
     * 
     * @return bool
     */
    public function _isAssoc()
    {
        return ! array_is_list($this->arr);
    }

    /**
     * Determines if an array is a list.
     *
     * An array is a "list" if all array keys are sequential integers starting from 0 with no gaps in between.
     * 
     * @return bool
     */
    public function _isList()
    {
        return array_is_list($this->arr);
    }

    /**
     * Join all items using a string. The final items can use a separate glue string.
     * 
     * @param  string  $glue
     * @param  string  $finalGlue
     * @return string
     */
    public function _join(string $glue, string $finalGlue = '')
    {
        if ($finalGlue === '') {
            return implode($glue, $this->arr);
        }

        if (count($this->arr) === 0) {
            return '';
        }

        if (count($this->arr) === 1) {
            return end($this->arr);
        }

        $finalItem = array_pop($this->arr);

        return implode($glue, $this->arr).$finalGlue.$finalItem;
    }

    /**
     * Run an associative map over each of the items.
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @template TKey
     * @template TValue
     * @template TMapWithKeysKey of array-key
     * @template TMapWithKeysValue
     * 
     * @param  callable(TValue, TKey): array<TMapWithKeysKey, TMapWithKeysValue>  $callback
     * @return array
     */
    public function _mapWithKeys(callable $callback)
    {
        $result = [];

        foreach ($this->arr as $key => $value) {
            $assoc = $callback($value, $key);

            foreach ($assoc as $mapKey => $mapValue) {
                $result[$mapKey] = $mapValue;
            }
        }

        return $result;
    }

    /**
     * Run a map over each of the items in the array.
     * 
     * @param  callable  $callback
     * @return array
     */
    public function _map(callable $callback)
    {
        $keys = array_keys($this->arr);

        try {
            $items = array_map($callback, $this->arr, $keys);
        } catch (\ArgumentCountError) {
            $items = array_map($callback, $this->arr);
        }

        return array_combine($keys, $items);
    }

    /**
     * Explode the "value" and "key" arguments passed to "pluck".
     * 
     * @param  string|array|null  $key
     * @return array
     */
    protected function _explodePluckParameters(string|array|null $key)
    {
        $this->arr = is_string($this->arr) ? explode('.', $this->arr) : $this->arr;

        $key = is_null($key) || is_array($key) ? $key : explode('.', $key);

        return [$this->arr, $key];
    }

    /**
     * Pluck an array of values from an array.
     * 
     * @param  string|array|int|null  $value
     * @param  string|array|null  $key
     * @return array
     */
    public function _pluck(string|array|int|null $value, string|array|null $key = null)
    {
        $results = [];

        $self = new self($value);
        [$value, $key] = $self->_explodePluckParameters($key);

        foreach ($this->arr as $item) {
            $itemValue = data_get($item, $value);

            // If the key is "null", we will just append the value to the array and keep
            // looping. Otherwise we will key the array using the value of the key we
            // received from the developer. Then we'll return the final array form.
            if (is_null($key)) {
                $results[] = $itemValue;
            } else {
                $itemKey = data_get($item, $key);

                if (is_object($itemKey) && method_exists($itemKey, '__toString')) {
                    $itemKey = (string) $itemKey;
                }

                $results[$itemKey] = $itemValue;
            }
        }

        return $results;
    }

    /**
     * Collapse an array of arrays into a single array.
     * 
     * @return array
     */
    public function _collapse()
    {
        $results = [];

        foreach ($this->arr as $values) {
            if ($values instanceof Collection) {
                $values = $values->all();
            } elseif (! is_array($values)) {
                continue;
            }

            $results[] = $values;
        }

        return array_merge([], ...$results);
    }

    /**
     * Add an element to an array using "dot" notation if it doesn't exist.
     * 
     * @param  string|int|float  $key
     * @param  mixed  $value
     * @return array
     */
    public function add(string|int|float $key, mixed $value)
    {
        if (is_null($this->_get($key))) {
            $this->_set($key, $value);
        }

        return $this->arr;
    }

    /**
     * Set an array item to a given value using "dot" notation.
     *
     * If no key is given to the method, the entire array will be replaced.
     * 
     * @param  string|int|null  $key
     * @param  mixed  $value
     * @return array
     */
    public function _set(string|int|null $key, mixed $value)
    {
        if (is_null($key)) {
            return $this->arr = $value;
        }

        $keys = explode('.', $key);

        foreach ($keys as $i => $key) {
            if (count($keys) === 1) {
                break;
            }

            unset($keys[$i]);

            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (! isset($this->arr[$key]) || ! is_array($this->arr[$key])) {
                $this->arr[$key] = [];
            }

            $this->arr = &$this->arr[$key];
        }

        $this->arr[array_shift($keys)] = $value;

        return $this->arr;
    }

    /**
     * Shuffle the given array and return the result.
     * 
     * @return array
     */
    public function _shuffle()
    {
        return (new Randomizer)->shuffleArray($this->arr);
    }
}