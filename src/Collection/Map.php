<?php 
namespace Clicalmani\Foundation\Collection;

/**
 * Class Map
 * 
 * A Map is a collection of key-value pairs, where each key is unique.
 * 
 * @package Clicalmani\Foundation\Collection
 * @author @clicalmani
 */
class Map extends Collection
{
    /**
     * Map size
     */
    public int $size = 0;

    public function __construct(iterable $elements = [])
    {
        foreach ($elements as $key => $value) $this[$key] = $value;
    }
    /**
     * (non-PHPdoc)
     * @override
     * 
     * @param mixed $key
     * @param mixed $value
     * @return static
     */
    public function set(mixed $key, mixed $value) : static
    {
        $this[$key] = $value;

        // Increase size if it was set
        if (isset($this->size) && !isset($this[$key])) {
            $this->size = $this->count();
        }

        return $this;
    }

    /**
     * Get entry by key
     * 
     * @param mixed $key
     * @return mixed
     */
    public function get(mixed $key = null) : mixed
    {
        return @$this[$key];
    }

    /**
     * Delete entry by key
     * 
     * @param mixed $key
     * @return static
     */
    public function delete(mixed $key) : static
    {
        unset($this[$key]);

        // Decrease size if it was set
        if (isset($this->size) && $this->size > 0) {
            $this->size = $this->count();
        }

        return $this;
    }

    /**
     * Check if the map has a key
     * 
     * @param mixed $key
     * @return bool
     */
    public function has($key): bool
    {
        return isset($this[$key]);
    }

    /**
     * Get Map keys
     * 
     * @return array
     */
    public function keys() : array
    {
        return array_keys($this->toArray());
    }

    /**
     * Get Map values
     * 
     * @return array
     */
    public function values() : array
    {
        return array_values($this->toArray());
    }

    public function entries() : array
    {
        return $this->toArray();
    }

    public function forEach(callable $callback) : static
    {
        foreach ($this->toArray() as $key => $value) {
            $callback($value, $key, $this);
        }
        return $this;
    }

    public function filter(callable $closure) : \Clicalmani\Foundation\Collection\CollectionInterface
    {
        return $this->exchange(
            array_filter($this->toArray(), $closure, ARRAY_FILTER_USE_BOTH)
        );
    }

    public function map(callable $closure): CollectionInterface
    {
        return $this->exchange(
            array_map($closure, $this->toArray(), array_keys($this->toArray()))
        );
    }

    public function each(callable $closure): CollectionInterface
    {
        $arr = $this->toArray();
        array_walk($arr, $closure);
        return $this->exchange($arr);
    }

    public function contains(mixed $key): bool
    {
        return $this->has($key);
    }

    public function __toString() : string
    {
        return json_encode($this->toArray());
    }
}
