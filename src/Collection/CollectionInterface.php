<?php
namespace Clicalmani\Foundation\Collection;

interface CollectionInterface
{
    /**
     * Store one or more elements
     * 
     * @param mixed $elements 
     * @return self
     */
    public function add(mixed ...$elements) : self;

    /**
     * @override
     */
    public function append(mixed $value): void;

    /**
     * Gets element at the specified index
     * 
     * @param int|string $index Element index
     * @return mixed 
     */
    public function get(int|string $index) : mixed;

    /**
     * Find element index
     * 
     * @param mixed $value
     * @return int
     */
    public function index(mixed $value) : int;

    /**
     * Get the first element
     * 
     * @return mixed
     */
    public function first() : mixed;

    /**
     * Get all elements
     * 
     * @return array
     */
    public function all() : array;

    /**
     * Get the last element
     * 
     * @return mixed
     */
    public function last() : mixed;

    /**
     * Manipulate elements through a callback function which receive element value as its first argument 
     * and element index as its second argument.
     * 
     * @param callable $closure
     * @return self
     */
    public function map(callable $closure) : self;

    /**
     * Iterate through elements
     * 
     * @param callable $closure A closure function which receive element value as its first argument and 
     * element index as its second argument.
     * @return self
     */
    public function each(callable $closure) : self;

    /**
     * Filter elements
     * 
     * @param callable $closure A callback function which receive element value as its first argument and 
     * element index as its second argument.
     * @return self
     */
    public function filter(callable $closure) : self;

    /**
     * Merges provided elements to the existing ones.
     * 
     * @param mixed $value A single element or an array of elements
     * @return self
     */
    public function merge(mixed $value) : self;

    /**
     * Detect if there is no elements in the storage.
     * 
     * @return bool true on success, or false otherwise.
     */
    public function isEmpty() : bool;

    /**
     * Verify wether an element exists at the given index.
     * 
     * @param int $index element index to check
     * @return bool true on success, or false otherwise.
     */
    public function exists(int $index) : bool;

    /**
     * Do a shallow copy of the storage.
     * 
     * @return array The copy
     */
    public function copy() : array;

    /**
     * Populate storage with new elements by replacing the old ones.
     * 
     * @param array $new_elements New elements to be used
     * @return self
     */
    public function exchange(array $new_elements) : self;

    /**
     * Removes duplicated elements and maintain the indexes.
     * 
     * @param mixed $closure [optional] an optional callback function to define the uniqueness of an element.
     * @return self
     */
    public function unique(mixed $closure = null) : self;

    /**
     * Returns a new collection with unique elements based on a specific key.
     * 
     * @param string $key The key to check for uniqueness
     * @return self
     */
    public function uniqueBy(string $key) : self;

    /**
     * Find element
     * 
     * @param callable $callback
     * @return mixed
     */
    public function find(callable $callback) : mixed;

    /**
     * Sort down elements by mainting the associated indexes.
     * 
     * @param callable $closure a comparison function
     * @return self
     */
    public function sort(callable $closure) : self;

    /**
     * Joins elements by separating them with a separator specified as first argument. Which means elements should be joinable.
     * 
     * @param string $delimiter Separator
     * @return string
     */
    public function join(string $delimiter = ',') : string;

    /**
     * Calculate the sum of values in the collection
     * 
     * @return int|float
     */
    public function sum() : int|float;

    /**
     * Returns the array representation of the stored elements.
     * 
     * @return array 
     */
    public function toArray() : array;

    /**
     * Convert the current collection to array object
     * 
     * @return self
     */
    public function toObject() : self;

    /**
     * Create a new set
     * 
     * @return \Clicalmani\Foundation\Collection\Set
     */
    public function asSet() : Set;

    /**
     * Create a new map
     * 
     * @return \Clicalmani\Foundation\Collection\Map
     */
    public function asMap() : Map;

    /**
     * Pluck a specific key from each element in the collection
     * 
     * @param string $key
     * @return \Clicalmani\Foundation\Collection\Map
     */
    public function pluck(string $key) : Map;

    /**
     * Get the number of public properties in the ArrayObject
     * When the <b>ArrayObject</b> is constructed from an array all properties are public.
     * @link https://php.net/manual/en/arrayobject.count.php
     * @return int The number of public properties in the ArrayObject.
     */
    public function count(): int;

    /**
     * Check if the specified element exists in the items list.
     * 
     * @param mixed $element
     * @return bool
     */
    public function has($element) : bool;

    /**
     * Conditionally extends the list.
     * 
     * @param iterable $elements
     * @param ?callable $callback
     * @return self
     */
    public function extends(iterable $elements, ?callable $callback = null) : self;

    /**
     * Sorts the collection by a specific key.
     * 
     * @param string $key The key to sort by
     * @return self
     */
    public function sortBy(string $key) : self;

    /**
     * Sorts the collection by a specific key in descending order.
     * 
     * @param string $key The key to sort by
     * @return self
     */
    public function sortByDesc(string $key) : self;

    /**
     * Checks if the collection is not empty.
     * 
     * @return bool
     */
    public function isNotEmpty() : bool;

    /**
     * Checks if the collection is empty or contains a null value.
     * 
     * @return bool
     */
    public function isEmptyOrNull() : bool;

    /**
     * Checks if the collection is not empty and does not contain a null value.
     * 
     * @return bool
     */
    public function isNotEmptyOrNull() : bool;

    /**
     * Checks if the collection is not empty and the first element is not null.
     * 
     * @return bool
     */
    public function isNotEmptyAndNull() : bool;

    /**
     * Checks if the collection contains a specific value.
     * 
     * @param mixed $value The value to check for
     * @return bool
     */
    public function contains(mixed $value) : bool;

    /**
     * Checks if the collection contains a specific key.
     * 
     * @param mixed $key The key to check for
     * @return bool
     */
    public function containsKey(mixed $key) : bool;

    /**
     * Clears the collection by removing all elements.
     * 
     * @return void
     */
    public function clear() : void;

    /**
     * Returns the first element or null if the collection is empty.
     * 
     * @return mixed
     */
    public function firstOrNull() : mixed;

    /**
     * Returns the last element or null if the collection is empty.
     * 
     * @return mixed
     */
    public function __toString() : string;

    /**
     * Applies a callback function against an accumulator and each element in the collection (from left to right) to reduce it to a single value.
     *
     * @param callable $callback The callback function to apply. It receives the accumulator, the current value, and the current key.
     * @param mixed $initial The initial value to start the reduction.
     * @return mixed The reduced value.
     */
    public function reduce(callable $callback, mixed $initial = null): mixed;
}