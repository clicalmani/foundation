<?php
namespace Clicalmani\Core\Collection;

use Clicalmani\Core\Support\Facades\Func;
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
        parent::__construct(
            is_array($elements) ? $elements : iterator_to_array($elements)
        );
    }

    protected function newInstance(iterable $elements = []) : static
    {
        return new static($elements);
    }

    public function add(mixed ...$elements) : CollectionInterface
    {
        foreach ($elements as $element) $this[] = $element;

        return $this;
    }

    public function append(mixed $value): void
    {
        $this->add($value);
    }

    public function push(mixed ...$elements) : static
    {
        foreach ($elements as $element) {
            $this[] = $element;
        }

        return $this;
    }

    public function put(mixed $key, mixed $value) : static
    {
        $this[$key] = $value;
        return $this;
    }

    public function get(int|string $index) : mixed
    {
        return $this->offsetExists($index) ? $this[$index] : null;
    }

    public function index(mixed $value) : int
    {
        foreach ($this as $k => $v) {
            if ($value === $v) return $k;
        }

        return -1;
    }

    public function first() : mixed
    {
        $array = $this->toArray();
        $key   = array_key_first($array);
        return $key === null ? null : $array[$key];
    }

    public function all() : array
    {
        return $this->toArray();
    }

    public function last() : mixed
    {
        $array = $this->toArray();
        $key   = array_key_last($array);
        return $key === null ? null : $array[$key];
    }

    public function map(callable $closure) : CollectionInterface
    {
        foreach ($this as $key => $value) {
            $this[$key] = $closure($value, $key);
        }
        
        return $this;
    }

    public function each(callable $closure) : CollectionInterface
    {
        $arr = $this->toArray();
        array_walk($arr, $closure);
        return $this;
    }

    public function filter(callable $closure) : CollectionInterface
    {
        $new = [];

        foreach ($this as $key => $value)
        {
            if ($closure($value, $key)) {
                $new[] = $value;
            }
        }

        return $this->exchange($new);
    }

    public function merge(mixed $value) : CollectionInterface
    {
        if ( $value instanceof CollectionInterface ) $value = $value->toArray();
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

    public function exists(int|string $index) : bool
    {
        return isset($this[$index]);
    }

    public function copy() : array
    {
        return $this->getArrayCopy();
    }

    public function exchange(array $new_elements) : CollectionInterface
    {
        $this->exchangeArray($new_elements);

        return $this;
    }

    public function unique(mixed $closure = null) : CollectionInterface
    {
        if (!isset($closure)) {
            return $this->exchange(array_values(array_unique($this->toArray(), SORT_REGULAR)));
        }

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

    public function uniqueBy(string $key) : CollectionInterface
    {
        $stack  = [];
        $filter = [];

        foreach ($this as $index => $value) {
            if (is_array($value) && array_key_exists($key, $value)) {
                $v = $value[$key];
            } elseif (is_object($value) && isset($value->{$key})) {
                $v = $value->{$key};
            } else {
                continue;
            }

            if (!in_array($v, $filter, true)) {
                $stack[]  = $value;
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

    public function has(mixed $element) : bool
    {
        return $this->contains($element);
    }

    public function sort(callable $closure) : CollectionInterface
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

    public function toObject() : CollectionInterface
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

    public function sortBy(string|callable $key, bool $descending = false) : static
    {
        $extract = static function (mixed $item) use ($key) : mixed {
            if (!is_string($key)) return $key($item);
            if (is_array($item))  return $item[$key] ?? null;
            if (is_object($item)) return $item->{$key} ?? null;
            return null;
        };

        $this->uasort(function ($a, $b) use ($extract, $descending) : int {
            $va = $extract($a);
            $vb = $extract($b);
            if (null === $va) return null === $vb ? 0 : 1;
            if (null === $vb) return -1;
            $cmp = $va <=> $vb;
            return $descending ? -$cmp : $cmp;
        });

        $this->exchange(array_values($this->toArray()));

        return $this;
    }

    public function sortByDesc(string|callable $key) : static
    {
        return $this->sortBy($key, true);
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
        return json_encode($this->toArray()) ?: '';
    }

    public function slice(int $offset, ?int $length = null) : static
    {
        return $this->newInstance(
            array_slice($this->toArray(), $offset, $length)
        );
    }

    public function remove(mixed $element) : mixed
    {
        $index = $this->index($element);
        if ($index === -1) return null;

        $removed = $this[$index];
        $array   = $this->toArray();
        unset($array[$index]);          // suppression par clé, pas par offset
        $this->exchange($array);
        return $removed;
    }

    public function flatten(int $depth = PHP_INT_MAX) : CollectionInterface
    {
        $flat = [];

        $walk = function (iterable $items, int $level) use (&$walk, &$flat) : void {
            foreach ($items as $item) {
                if ($level > 0 && (is_array($item) || $item instanceof \Traversable)) {
                    $walk($item, $level - 1);
                } else {
                    $flat[] = $item;
                }
            }
        };

        $walk($this, $depth);

        return $this->exchange($flat);
    }

    public function flatMap(callable $callback) : self
    {
        return $this->map($callback)->flatten();
    }

    public function tap(callable $callback) : self
    {
        $callback($this);
        return $this;
    }

    public function pipe(callable $callback) : mixed
    {
        return $callback($this);
    }

    public function take(int $n) : static
    {
        return $this->slice(0, $n);
    }

    public function skip(int $n) : self
    {
        return $this->slice($n);
    }

    public function groupBy(string|callable $key) : Map
    {
        $map = new Map;
        foreach ($this as $item) {
            $k = is_callable($key)
                ? $key($item)
                : (is_array($item) ? ($item[$key] ?? null) : ($item->{$key} ?? null));
            if ($k === null) continue;
            if (!isset($map[$k])) $map[$k] = new static;
            $map[$k]->add($item);
        }
        return $map;
    }

    public function chunk(int $size) : static
    {
        if ($size < 1) {
            throw new \InvalidArgumentException('Chunk size must be >= 1.');
        }

        $chunks = [];
        foreach (array_chunk($this->toArray(), $size) as $c) {
            $chunks[] = $this->newInstance($c);
        }

        return $this->newInstance($chunks);
    }

    public function min() : int|float|null
    {
        $v = $this->toArray();
        return $v ? min($v) : null;
    }

    public function max() : int|float|null
    {
        $v = $this->toArray();
        return $v ? max($v) : null;
    }

    public function avg() : int|float|null
    {
        $count = $this->count();
        return $count > 0 ? floatval($this->sum() / $count) : null;
    }
}
