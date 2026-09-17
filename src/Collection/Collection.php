<?php
namespace Clicalmani\Core\Collection;

use Clicalmani\Core\Support\Facades\Func;
use Override;
use TypeError;

/**
 * Class Collection
 * 
 * @package clicalmani/collection 
 * @author @clicalmani
 */
class Collection extends \Illuminate\Support\Collection
{
    public function extends(iterable $elements, ?callable $callback = null) : self
    {
        foreach ($elements as $element) {
            if ($callback && !$callback($element)) continue;
            $this->add($element);
        }

        return $this;
    } 

    public function find(callable $callback) : mixed
    {
        foreach ($this as $key => $value) {
            if (false != $callback($value, $key)) return $value;
        }

        return null;
    }

    public function exchange(array $new_elements) : self
    {
        return new self($new_elements);
    }
}
