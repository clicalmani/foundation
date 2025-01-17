<?php 
namespace Clicalmani\Foundation\Collection;

/**
 * Class Map
 * 
 * @package Clicalmani\Foundation\Collection
 * @author @clicalmani
 */
class Map extends Collection
{
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
        if (null === $this->get($key)) $this[] = (object) ['key' => $key, 'value' => $value];

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
        foreach ($this as $element) 
            if ($element->key === $key) return $element->value;
            
        return null;
    }

    /**
     * Delete entry by key
     * 
     * @param mixed $key
     * @return static
     */
    public function delete(mixed $key) : static
    {
        $new_map = [];

        foreach ($this as $element)
            if ($element->key !== $key) $new_map[] = $element;

        return $this->exchange($new_map);
    }

    /**
     * Put entry by key
     * 
     * @param mixed $key
     * @param mixed $value
     * @return static
     */
    public function put(mixed $key, mixed $value) : static
    {
        $this[] = (object) ['key' => $key, 'value' => $value];
        return $this;
    }
}
