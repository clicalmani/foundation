<?php
namespace Clicalmani\Foundation\Http\Controllers;

class ParameterReflector
{
    private \ReflectionParameter $parameter;

    public function __construct(\ReflectionParameter $parameter)
    {
        $this->parameter = $parameter;
    }

    /**
     * Sets the type of the given value based on the parameter's type.
     * 
     * @param mixed $value The value to set the type for.
     * @throws \TypeError If the value does not match the expected type.
     */
    public function setType(mixed &$value) : void
    {
        /**
         * @var \ReflectionNamedType|\ReflectionUnionType|\ReflectionIntersectionType|null
         */
        $type = $this->parameter->getType();

        if ($type instanceof \ReflectionUnionType) {
            $types = $type->getTypes();
            foreach ($types as $namedType) {
                if ($type instanceof \ReflectionNamedType AND gettype($value) === ($name = $namedType->getName())) {
                    settype($value, $name);
                }
            }
        } elseif ($type->isBuiltin()) {
            settype($value, $type->getName());
        } else {
            $reflector = new Reflector;
            $classes = $reflector->getParameterClassNames($this->parameter);
            $false = false;
            
            foreach ($classes as $class) {
                if ($value instanceof $class) {
                    $false = true;
                    break;
                }
            }

            if ($false === false) 
                throw new \TypeError(
                    sprintf("{$this->parameter->name} must be of type %s; %s given.", join('|', $classes), gettype($value))
                );
        }
    }
}