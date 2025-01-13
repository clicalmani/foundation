<?php
namespace Clicalmani\Foundation\Http\Controllers;

use ReflectionClass;
use ReflectionEnum;
use ReflectionNamedType;
use ReflectionUnionType;

class Reflector
{
    /**
     * @var \ReflectionParameter[]
     */
    protected $parameters = [];

    public function __construct(protected \ReflectionMethod|\ReflectionFunction|null $reflect = null)
    {
        $this->parameters = $reflect?->getParameters();
    }

    /**
     * Get the class name of the given parameter's type, if possible.
     *
     * @param  \ReflectionParameter  $parameter
     * @return string|null
     */
    public static function getParameterClassName(\ReflectionParameter $parameter) : string|null
    {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        return static::getTypeName($parameter, $type);
    }

    /**
     * Get the class names of the given parameter's type, including union types.
     *
     * @param  \ReflectionParameter  $parameter
     * @return array
     */
    public static function getParameterClassNames(\ReflectionParameter $parameter) : array
    {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionUnionType) {
            return array_filter([static::getParameterClassName($parameter)]);
        }

        $unionTypes = [];

        foreach ($type->getTypes() as $listedType) {
            if (! $listedType instanceof ReflectionNamedType || $listedType->isBuiltin()) {
                continue;
            }

            $unionTypes[] = static::getTypeName($parameter, $listedType);
        }

        return array_filter($unionTypes);
    }

    /**
     * Get the given type's class name.
     *
     * @param  \ReflectionParameter  $parameter
     * @param  \ReflectionNamedType  $type
     * @return string
     */
    protected static function getTypeName(\ReflectionParameter $parameter, \ReflectionNamedType $type) : string
    {
        $name = $type->getName();

        if (! is_null($class = $parameter->getDeclaringClass())) {
            if ($name === 'self') {
                return $class->getName();
            }

            if ($name === 'parent' && $parent = $class->getParentClass()) {
                return $parent->getName();
            }
        }

        return $name;
    }

    /**
     * Determine if the parameter's type is a subclass of the given type.
     *
     * @param  \ReflectionParameter  $parameter
     * @param  string  $className
     * @return bool
     */
    public static function isParameterSubclassOf(\ReflectionParameter $parameter, string $className) : bool
    {
        $paramClassName = static::getParameterClassName($parameter);

        return $paramClassName
            && (class_exists($paramClassName) || interface_exists($paramClassName))
            && (new ReflectionClass($paramClassName))->isSubclassOf($className);
    }

    /**
     * Determine if the parameter's type is a Backed Enum with a string backing type.
     *
     * @param  \ReflectionParameter  $parameter
     * @return bool
     */
    public static function isParameterBackedEnumWithStringBackingType(\ReflectionParameter $parameter) : bool
    {
        if (! $parameter->getType() instanceof ReflectionNamedType) {
            return false;
        }

        $backedEnumClass = $parameter->getType()?->getName();

        if (is_null($backedEnumClass)) {
            return false;
        }

        if (enum_exists($backedEnumClass)) {
            $reflectionBackedEnum = new ReflectionEnum($backedEnumClass);

            return $reflectionBackedEnum->isBacked()
                && $reflectionBackedEnum->getBackingType()->getName() == 'string';
        }

        return false;
    }

    /**
     * Get the function parameters.
     * 
     * @return array
     */
    public function getParameters() : array
    {
        return $this->parameters;
    }

    /**
     * Get the resource class name if the function has a resource parameter.
     * 
     * @return array|null
     */
    public function getResource() : array|null
    {
        foreach ($this->parameters as $index => $parameter) {
            foreach ($this->getParameterClassNames($parameter) as $class) {
                if (is_subclass_of($class, \Clicalmani\Database\Factory\Models\Model::class)) return ['name' => $class, 'pos' => $index];
            }
        }

        return null;
    }

    /**
     * Get the request class name if the function has a request parameter.
     * 
     * @return array|null
     */
    public function getRequest() : array|null
    {
        foreach ($this->parameters as $index => $parameter) {
            foreach ($this->getParameterClassNames($parameter) as $class) {
                if (is_subclass_of($class, \Clicalmani\Foundation\Http\Requests\Request::class) ||
                    $class === \Clicalmani\Foundation\Http\Requests\Request::class) return ['name' => $class, 'pos' => $index];
            }
        }

        return null;
    }
}
