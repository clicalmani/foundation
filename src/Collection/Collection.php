<?php
namespace Clicalmani\Foundation\Collection;

/**
 * Class Collection
 * 
 * @package clicalmani/collection 
 * @author @clicalmani
 */
class Collection extends SPLCollection
{
    public function __construct($elements = [])
    {
        $this->add( ...$elements );
    }

    /**
     * Store one or more elements
     * 
     * @param mixed $elements 
     * @return static
     */
    public function add(mixed ...$elements) : static
    {
        foreach ($elements as $element) $this[] = $element;

        return $this;
    }

    /**
     * @override
     */
    public function append(mixed $value): void
    {
        $this->add($value);
    }

    /**
     * Gets element at the specified index
     * 
     * @param mixed $index Element index
     * @return mixed 
     */
    public function get(mixed $index = null) : mixed
    {
        return @ $this[$index];
    }

    /**
     * Find element index
     * 
     * @param mixed $value
     * @return int
     */
    public function index(mixed $value) : int
    {
        foreach ($this as $k => $v) {
            if (is_callable($value) && false != $value($v, $k)) return $k;
            elseif ($value === $v) return $k;
        }

        return -1;
    }

    /**
     * Get the first element
     * 
     * @return mixed
     */
    public function first() : mixed
    {
        return $this->get(0);
    }

    /**
     * Get all elements
     * 
     * @return array
     */
    public function all() : array
    {
        return $this->toArray();
    }

    /**
     * Get the last element
     * 
     * @return mixed
     */
    public function last() : mixed
    {
        return $this->count() ? $this[$this->count() - 1]: null;
    }

    /**
     * Manipulate elements through a callback function which receive element value as its first argument 
     * and element index as its second argument.
     * 
     * @param callable $closure
     * @return static
     */
    public function map(callable $closure) : static
    {
        foreach ($this as $key => $value) {
            $this[$key] = $closure($value, $key);
        }
        
        return $this;
    }

    /**
     * Iterate through elements
     * 
     * @param callable $closure A closure function which receive element value as its first argument and 
     * element index as its second argument.
     * @return static
     */
    public function each(callable $closure) : static
    {
        $arr = $this->toArray();
        array_walk($arr, $closure);
        return $this;
    }

    /**
     * Filter elements
     * 
     * @param callable $closure A callback function which receive element value as its first argument and 
     * element index as its second argument.
     * @return static
     */
    public function filter(callable $closure) : static
    {
        // return $this->exchange(array_values(array_filter($this->toArray(), $closure)));
        $new = [];
        foreach ($this as $key => $value)
        {
            if ($closure($value, $key)) {
                $new[] = $value;
            }
        }

        return $this->exchange($new);
    }

    /**
     * Merges provided elements to the existing ones.
     * 
     * @param mixed $value A single element or an array of elements
     * @return static
     */
    public function merge(mixed $value) : static
    {
        if ( $value instanceof static ) $value = $value->toArray();
        elseif ( !is_array($value) ) $value = [$value];

        $this->exchange(
            array_merge((array) $this, $value)
        );

        return $this;
    }

    /**
     * Detect if there is no elements in the storage.
     * 
     * @return bool true on success, or false otherwise.
     */
    public function isEmpty() : bool
    {
        return $this->count() === 0;
    }

    /**
     * Verify wether an element exists at the given index.
     * 
     * @param int $index element index to check
     * @return bool true on success, or false otherwise.
     */
    public function exists(int $index) : bool
    {
        return isset($this[$index]);
    }

    /**
     * Do a shallow copy of the storage.
     * 
     * @return array The copy
     */
    public function copy() : array
    {
        return $this->getArrayCopy();
    }

    /**
     * Populate storage with new elements by replacing the old ones.
     * 
     * @param array $new_elements New elements to be used
     * @return static
     */
    public function exchange(array $new_elements) : static
    {
        $this->exchangeArray($new_elements);

        return $this;
    }

    /**
     * Removes duplicated elements and maintain the indexes.
     * 
     * @param mixed $closure [optional] an optional callback function to define the uniqueness of an element.
     * @return static
     */
    public function unique(mixed $closure = null) : static
    {
        if (!isset($closure)) return $this->exchange(array_unique( $this->toArray() ));

        $stack  = [];
        $filter = [];

        foreach ($this as $key => $value)
        {
            $v = $closure($value, $key);

            if (!in_array($v, $filter)) {
                $stack[] = $value;
                $filter[] = $v;
            }
        }

        return $this->exchange($stack);
    }

    /**
     * Find element
     * 
     * @param callable $callback
     * @return mixed
     */
    public function find(callable $callback) : mixed
    {
        foreach ($this as $key => $value) {
            if (false != $callback($value, $key)) return $value;
        }

        return null;
    }

    /**
     * Sort down elements by mainting the associated indexes.
     * 
     * @param callable $closure a comparison function
     * @return static
     */
    public function sort(callable $closure) : static
    {
        $this->uasort($closure);
        return $this;
    }

    /**
     * Joins elements by separating them with a separator specified as first argument. Which means elements should be joinable.
     * 
     * @param string $delimiter Separator
     * @return string
     */
    public function join(string $delimiter = ',') : string
    {
        return join($delimiter, $this->toArray());
    }

    /**
     * Calculate the sum of values in the collection
     * 
     * @return int|float
     */
    public function sum() : int|float
    {
        return array_sum($this->toArray());
    }
    
    /**
     * Returns the array representation of the stored elements.
     * 
     * @return array 
     */
    public function toArray() : array
    {
        return (array) $this;
    }

    /**
     * Convert the current collection to array object
     * 
     * @return static
     */
    public function toObject() : static
    {
        $this->setFlags(parent::ARRAY_AS_PROPS);
        return $this;
    }

    /**
     * Create a new set
     * 
     * @return \Clicalmani\Foundation\Collection\Set
     */
    public function asSet() : Set
    {
        return new Set;
    }

    /**
     * Create a new map
     * 
     * @return \Clicalmani\Foundation\Collection\Map
     */
    public function asMap() : Map
    {
        return new Map;
    }

    /**
     * Pluck a specific key from each element in the collection
     * 
     * @param string $key
     * @return \Clicalmani\Foundation\Collection\Map
     */
    public function pluck(string $key) : Map
    {
        $map = new Map;
        foreach ($this as $element) {
            $map->put($key, @$element[$key]);
        }

        return $map;
    }
}
