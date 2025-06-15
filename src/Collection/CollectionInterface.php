<?php
namespace Clicalmani\Foundation\Collection;

interface CollectionInterface
{
    /**
     * Store one or more elements
     * 
     * @param mixed $elements 
     * @return \Clicalmani\Foundation\Collection\CollectionInterface
     */
    public function add(mixed ...$elements) : \Clicalmani\Foundation\Collection\CollectionInterface;

    /**
     * @override
     */
    public function append(mixed $value): void;

    /**
     * Gets element at the specified index
     * 
     * @param mixed $index Element index
     * @return mixed 
     */
    public function get(mixed $index = null) : mixed;

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
     * @return \Clicalmani\Foundation\Collection\CollectionInterface
     */
    public function map(callable $closure) : \Clicalmani\Foundation\Collection\CollectionInterface;

    /**
     * Iterate through elements
     * 
     * @param callable $closure A closure function which receive element value as its first argument and 
     * element index as its second argument.
     * @return \Clicalmani\Foundation\Collection\CollectionInterface
     */
    public function each(callable $closure) : \Clicalmani\Foundation\Collection\CollectionInterface;

    /**
     * Filter elements
     * 
     * @param callable $closure A callback function which receive element value as its first argument and 
     * element index as its second argument.
     * @return \Clicalmani\Foundation\Collection\CollectionInterface
     */
    public function filter(callable $closure) : \Clicalmani\Foundation\Collection\CollectionInterface;

    /**
     * Merges provided elements to the existing ones.
     * 
     * @param mixed $value A single element or an array of elements
     * @return \Clicalmani\Foundation\Collection\CollectionInterface
     */
    public function merge(mixed $value) : \Clicalmani\Foundation\Collection\CollectionInterface;

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
     * @return \Clicalmani\Foundation\Collection\CollectionInterface
     */
    public function exchange(array $new_elements) : \Clicalmani\Foundation\Collection\CollectionInterface;

    /**
     * Removes duplicated elements and maintain the indexes.
     * 
     * @param mixed $closure [optional] an optional callback function to define the uniqueness of an element.
     * @return \Clicalmani\Foundation\Collection\CollectionInterface
     */
    public function unique(mixed $closure = null) : \Clicalmani\Foundation\Collection\CollectionInterface;

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
     * @return \Clicalmani\Foundation\Collection\CollectionInterface
     */
    public function sort(callable $closure) : \Clicalmani\Foundation\Collection\CollectionInterface;

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
     * @return \Clicalmani\Foundation\Collection\CollectionInterface
     */
    public function toObject() : \Clicalmani\Foundation\Collection\CollectionInterface;

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
}