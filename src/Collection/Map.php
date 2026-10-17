<?php 
namespace Clicalmani\Core\Collection;

/**
 * Class Map
 * 
 * A Map is a collection of key-value pairs, where each key is unique.
 * 
 * @package Clicalmani\Core\Collection
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
        foreach ($elements as $key => $value) {
            $this->set($key, $value);
        }
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
        $isNew = !isset($this[$key]);
        $this[$key] = $value;
        if ($isNew) $this->size = $this->count();
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
    public function delete(mixed ...$keys) : static
    {
        foreach ($keys as $key) unset($this[$key]);
        $this->size = $this->count();
        return $this;
    }

    public function update(array $entries) : static
    {
        foreach ($entries as $k => $v) $this->set($k, $v);
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

    public function filter(callable $closure) : static
    {
        $result = new static;

        foreach ($this->toArray() as $key => $value) {
            if ($closure($value, $key)) {
                $result->set($key, $value);
            }
        }

        return $result;
    }

    public function map(callable $closure): static
    {
        $result = new static;

        foreach ($this->toArray() as $key => $value) {
            $result->set($key, $closure($value, $key));
        }

        return $result;
    }

    public function each(callable $closure): static
    {
        foreach ($this->toArray() as $key => $value) {
            $closure($value, $key, $this);
        }
        return $this;
    }

    public function contains(mixed $key): bool
    {
        return $this->has($key);
    }

    public function __toString() : string
    {
        return json_encode($this->toArray());
    }

    public function offsetSet(mixed $key, mixed $value) : void
    {
        parent::offsetSet($key, $value);
        $this->size = $this->count();
    }

    public function offsetUnset(mixed $key) : void
    {
        parent::offsetUnset($key);
        $this->size = $this->count();
    }
}
