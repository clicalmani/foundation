<?php
namespace Clicalmani\Foundation\Collection;

use Clicalmani\Foundation\Support\Facades\Func;
use TypeError;

/**
 * Class Collection
 * 
 * @package clicalmani/collection 
 * @author @clicalmani
 */
class Collection extends SPLCollection implements CollectionInterface
{
    public function __construct(iterable $elements = [])
    {
        $this->add( ...$elements );
    }

    public function add(mixed ...$elements) : \Clicalmani\Foundation\Collection\CollectionInterface
    {
        foreach ($elements as $element) $this[] = $element;

        return $this;
    }

    public function append(mixed $value): void
    {
        $this->add($value);
    }

    public function get(int|string $index) : mixed
    {
        return @ $this[$index];
    }

    public function index(mixed $value) : int
    {
        foreach ($this as $k => $v) {
            if (is_callable($value) && FALSE === is_callable($value) && Func::isInternal($value) && FALSE != $value($v, $k)) return $k;
            elseif ($value === $v) return $k;
        }

        return -1;
    }

    public function first() : mixed
    {
        return $this->get(0);
    }

    public function all() : array
    {
        return $this->toArray();
    }

    public function last() : mixed
    {
        return $this->count() ? $this[$this->count() - 1]: null;
    }

    public function map(callable $closure) : \Clicalmani\Foundation\Collection\CollectionInterface
    {
        foreach ($this as $key => $value) {
            $this[$key] = $closure($value, $key);
        }
        
        return $this;
    }

    public function each(callable $closure) : \Clicalmani\Foundation\Collection\CollectionInterface
    {
        $arr = $this->toArray();
        array_walk($arr, $closure);
        return $this;
    }

    public function filter(callable $closure) : \Clicalmani\Foundation\Collection\CollectionInterface
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

    public function merge(mixed $value) : \Clicalmani\Foundation\Collection\CollectionInterface
    {
        if ( $value instanceof \Clicalmani\Foundation\Collection\CollectionInterface ) $value = $value->toArray();
        elseif ( !is_array($value) ) $value = [$value];

        $this->exchange(
            array_merge((array) $this, $value)
        );

        return $this;
    }

    public function isEmpty() : bool
    {
        return $this->count() === 0;
    }

    public function exists(int $index) : bool
    {
        return isset($this[$index]);
    }

    public function copy() : array
    {
        return $this->getArrayCopy();
    }

    public function exchange(array $new_elements) : \Clicalmani\Foundation\Collection\CollectionInterface
    {
        $this->exchangeArray($new_elements);

        return $this;
    }

    public function unique(mixed $closure = null) : \Clicalmani\Foundation\Collection\CollectionInterface
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

    public function uniqueBy(string $key) : \Clicalmani\Foundation\Collection\CollectionInterface
    {
        $stack  = [];
        $filter = [];

        foreach ($this as $key => $value)
        {
            if (is_array($value) && isset($value[$key])) {
                $v = $value[$key];
            } elseif (is_object($value) && isset($value->{$key})) {
                $v = $value->{$key};
            } else {
                continue;
            }

            if (!in_array($v, $filter)) {
                $stack[] = $value;
                $filter[] = $v;
            }
        }

        return $this->exchange($stack);
    }

    public function find(callable $callback) : mixed
    {
        foreach ($this as $key => $value) {
            if (false != $callback($value, $key)) return $value;
        }

        return null;
    }

    public function has($element) : bool
    {
        return !!$this->find(fn($value) => $value === $element);
    }

    public function sort(callable $closure) : \Clicalmani\Foundation\Collection\CollectionInterface
    {
        $this->uasort($closure);
        return $this;
    }

    public function join(string $delimiter = ',') : string
    {
        return join($delimiter, $this->toArray());
    }

    public function sum() : int|float
    {
        return array_sum($this->toArray());
    }
    
    public function toArray() : array
    {
        return (array) $this;
    }

    public function toObject() : \Clicalmani\Foundation\Collection\CollectionInterface
    {
        $this->setFlags(parent::ARRAY_AS_PROPS);
        return $this;
    }

    public function asSet() : Set
    {
        return new Set;
    }

    public function asMap() : Map
    {
        return new Map;
    }

    public function pluck(string $key) : Map
    {
        $map = new Map;

        foreach ($this as $index => $item) {
            if (is_array($item) && isset($item[$key])) {
                $map[$item[$key]] = $item;
            } elseif (is_object($item) && isset($item->{$key})) {
                $map[$item->{$key}] = $item;
            } else {
                if ($index !== $key) $map[$index] = $item;
            }
        }

        return $map;
    }

    public function extends(iterable $elements, ?callable $callback = null) : self
    {
        foreach ($elements as $element) {
            if ($callback && !$callback($element)) continue;
            $this->add($element);
        }

        return $this;
    } 

    public function sortBy(string $key) : \Clicalmani\Foundation\Collection\CollectionInterface
    {
        $this->uasort(function ($a, $b) use ($key) { 
            if ((is_array($a) && is_array($b)) || (is_object($a) && is_object($b))) return $a[$key] <=> $b[$key];
            throw new TypeError("Both elements must be arrays or objects to sort by key '$key'.");
        });

        return $this;
    }

    public function sortByDesc(string $key) : \Clicalmani\Foundation\Collection\CollectionInterface
    {
        $this->uasort(function ($a, $b) use ($key) { 
            if ((is_array($a) && is_array($b)) || (is_object($a) && is_object($b))) return -1*($a[$key] <=> $b[$key]);
            throw new TypeError("Both elements must be arrays or objects to sort by key '$key'.");
        });

        return $this;
    }

    public function isNotEmpty() : bool
    {
        return !$this->isEmpty();
    }

    public function isEmptyOrNull() : bool
    {
        return $this->isEmpty() || $this->firstOrNull() === null;
    }

    public function isNotEmptyOrNull() : bool
    {
        return !$this->isEmptyOrNull();
    }

    public function isNotEmptyAndNull() : bool
    {
        return !$this->isEmpty() && $this->firstOrNull() !== null;
    }

    public function contains(mixed $value) : bool
    {
        return $this->index($value) !== -1;
    }

    public function containsKey(mixed $key) : bool
    {
        return isset($this[$key]);
    }

    /**
     * Clears the collection by removing all elements.
     * 
     * @return void
     */
    public function clear() : void
    {
        $this->exchange([]);
    }

    public function firstOrNull() : mixed
    {
        return $this->count() ? $this->first() : null;
    }

    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        $result = $initial;
        foreach ($this as $key => $value) {
            $result = $callback($result, $value, $key);
        }
        return $result;
    }

    public function __toString() : string
    {
        return json_encode($this->toArray());
    }
}
