<?php 
namespace Clicalmani\Core\Collection;

class Set extends Collection
{
    public function __construct(iterable $elements = [])
    {
        parent::__construct([]);

        $values = is_array($elements) ? $elements : iterator_to_array($elements);
        $this->add(...array_values($values));
    }

    /**
     * (non-PHPdoc)
     * @override
     * 
     * @param mixed ...$items
     * @return static
     */
    public function add(mixed ...$items) : static
    {
        foreach ($items as $item) {
            if (null !== $this->type && gettype($item) !== $this->type) continue;
            $this[] = $item;
        }

        $this->exchange(
            array_values(array_unique($this->toArray(), SORT_REGULAR))
        );

        return $this;
    }
}
