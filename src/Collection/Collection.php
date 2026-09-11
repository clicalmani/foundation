<?php
namespace Clicalmani\Foundation\Collection;

use Clicalmani\Foundation\Support\Facades\Func;
use Override;
use TypeError;

/**
 * Class Collection
 * 
 * @package clicalmani/collection 
 * @author @clicalmani
 */
class Collection extends SPLCollection implements CollectionInterface, \JsonSerializable
{
    public function __construct(iterable $elements = [])
    {
        $this->add( ...$elements );
    }

    public function add(mixed ...$elements): CollectionInterface
    {
        foreach ($elements as $element) {
            $this[] = $element;
        }
        return $this;
    }

    public function append(mixed $value): void
    {
        $this->add($value);
    }

    public function get(int|string $index) : mixed
    {
        return $this[$index] ?? null;
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

    public function filter(?callable $closure = null) : \Clicalmani\Foundation\Collection\CollectionInterface
    {
        if (null === $closure) return $this->exchange(array_filter($this->toArray(), fn($value) => !!$value));

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

    public function push(mixed $value) : self
    {
        $this[] = $value;
        return $this;
    }

    public function isEmpty() : bool
    {
        return $this->count() === 0;
    }

    public function exists(mixed $index) : bool
    {
        if (is_int($index)) return isset($this[$index]);

        if (is_callable($index)) {
            foreach ($this as $key => $element) {
                if (call_user_func($index, $element, $key)) return true;
            }
            return false;
        }

        foreach ($this as $element) {
            if (is_array($element) && array_key_exists($index, $element)) return true;
            if (is_object($element) && isset($element->{$index})) return true;
        }

        return false;
    }

    public function copy() : self
    {
        return new self($this->getArrayCopy());
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

    public function pluck(string $key): self
    {
        $map = collect();

        foreach ($this as $index => $item) {
            if (is_array($item) && isset($item[$key])) {
                $map->add($item[$key]);
            } elseif (is_object($item) && isset($item->{$key})) {
                $map->add($item->{$key});
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

    public function sortBy(string $key): CollectionInterface
    {
        $this->uasort(function ($a, $b) use ($key) {
            $va = $this->getItemValue($a, $key);
            $vb = $this->getItemValue($b, $key);
            
            if ($va === null || $vb === null) {
                return 0;
            }
            
            return $va <=> $vb;
        });

        return $this;
    }

    public function sortByDesc(string $key) : CollectionInterface
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

    public function slice(int $offset, ?int $length = null) : iterable
    {
        return $this->exchange(array_slice($this->toArray(), $offset, $length))->toArray();
    }

    public function remove(mixed $element) : mixed
    {
        if (-1 !== $index = $this->index($element)) {
            return array_splice($this->toArray(), $index, 1)[0];
        }

        return null;
    }

    public function values(): self
    {
        return new self(array_values($this->toArray()));
    }

    public function search(mixed $value, bool $strict = true): int|string|false
    {
        // 1. Si c'est un callback, on l'utilise pour la recherche
        if (is_callable($value) && !is_string($value)) {
            foreach ($this as $key => $item) {
                if (call_user_func($value, $item, $key)) {
                    return $key;
                }
            }
            return false;
        }
        
        // 2. Recherche standard avec comparaison
        foreach ($this as $key => $item) {
            if ($strict) {
                // Comparaison stricte (===)
                if ($value === $item) {
                    return $key;
                }
            } else {
                // Comparaison non stricte (==)
                if ($value == $item) {
                    return $key;
                }
            }
        }

        return false;
    }

    public function flatMap(callable $callback, int $depth = 1): self
    {
        $result = [];
        
        foreach ($this as $key => $value) {
            $mapped = $callback($value, $key);
            
            // Si le résultat est une collection, on la convertit en tableau
            if ($mapped instanceof self) {
                $mapped = $mapped->toArray();
            } elseif ($mapped instanceof \Traversable) {
                $mapped = iterator_to_array($mapped);
            }
            
            // Si c'est un tableau et qu'on doit l'aplatir
            if (is_array($mapped) && $depth > 0) {
                $result = array_merge($result, $this->flattenArray($mapped, $depth));
            } else {
                $result[] = $mapped;
            }
        }
        
        return new self($result);
    }

    #[Override]
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    private function getItemValue(mixed $item, string $key): mixed
    {
        if (is_array($item) && isset($item[$key])) {
            return $item[$key];
        }
        if (is_object($item) && isset($item->{$key})) {
            return $item->{$key};
        }
        return null;
    }

    private function flattenArray(array $array, int $depth): array
    {
        $result = [];
        
        foreach ($array as $value) {
            if (is_array($value) && $depth > 1) {
                $result = array_merge($result, $this->flattenArray($value, $depth - 1));
            } else {
                $result[] = $value;
            }
        }
        
        return $result;
    }
}
