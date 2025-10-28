<?php
namespace Clicalmani\Foundation\Http\Controllers;

use Clicalmani\Foundation\Acme\Container;
use Clicalmani\Foundation\Http\Request;
use Clicalmani\Foundation\Http\RequestInterface;
use Clicalmani\Foundation\Mail\Mailer;
use Clicalmani\Foundation\Mail\MailerInterface;
use Clicalmani\Validation\AsValidator;
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
    protected static function handleNamedType(\ReflectionParameter $parameter) : string|null
    {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        return static::getTypeName($parameter, $type);
    }

    /**
     * Handle intersection types (PHP 8.1+)
     *
     * @param ReflectionIntersectionType $type
     * @return array
     */
    protected static function handleIntersectionType(\ReflectionIntersectionType $type): array
    {
        $types = [];
        
        foreach ($type->getTypes() as $intersectionType) {
            if ($intersectionType instanceof ReflectionNamedType) {
                $types[] = $intersectionType->getName();
            }
        }
        
        return $types;
    }

    /**
     * Handle union types
     *
     * @param ReflectionUnionType $type
     * @return array
     */
    protected static function handleUnionType(ReflectionUnionType $type): array
    {
        $types = [];
        
        foreach ($type->getTypes() as $unionType) {
            if ($unionType instanceof \ReflectionNamedType) {
                $types[] = $unionType->getName();
            } elseif ($unionType instanceof \ReflectionIntersectionType) {
                $types = array_merge(self::handleIntersectionType($unionType));
            }
        }

        return $types;
    }

    /**
     * Get the class names of the given parameter's type, including union types.
     *
     * @param  \ReflectionParameter  $parameter
     * @return array
     */
    public static function listTypes(\ReflectionParameter $parameter) : array
    {
        $type = $parameter->getType();

        if ($type instanceof \ReflectionNamedType) {
            return array_filter([static::handleNamedType($parameter)]);
        }

        if ($type instanceof \ReflectionUnionType) {
            return self::handleUnionType($type);
        }

        if ($type instanceof \ReflectionIntersectionType) {
            return self::handleIntersectionType($type);
        }

        return [];
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
        $paramClassName = static::handleNamedType($parameter);

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
     * @return iterable|null
     */
    public function getResources() : iterable|null
    {
        foreach ($this->parameters as $index => $parameter) {
            foreach ($this->listTypes($parameter) as $class) {
                if (is_subclass_of($class, \Clicalmani\Database\Factory\Models\Elegant::class)) yield ['name' => $class, 'pos' => $index];
            }
        }

        return null;
    }

    /**
     * Get the resource class name if the function has a resource parameter.
     * 
     * @return array|null
     */
    public function getResource() : array|null
    {
        return $this->getResources()->current();
    }

    /**
     * Get the resource class name if the function has a resource parameter.
     * 
     * @return array|null
     */
    public function getNestedResource() : array|null
    {
        $iterator = $this->getResources();
        $iterator->next();
        return $iterator->current();
    }

    /**
     * Get the request class name if the function has a request parameter.
     * 
     * @return array|null
     */
    public function getRequest() : array|null
    {
        foreach ($this->parameters as $index => $parameter) {
            foreach (self::listTypes($parameter) as $class) {
                if (is_subclass_of($class, \Clicalmani\Foundation\Http\Request::class) ||
                    $class === \Clicalmani\Foundation\Http\Request::class) return ['name' => $class, 'pos' => $index];
            }
        }

        return null;
    }

    public function handleRequest(object $instance) : ?RequestInterface
    {
        if (is_subclass_of($instance, \Clicalmani\Foundation\Http\Request::class) ||
					$instance::class === \Clicalmani\Foundation\Http\Request::class) {

            $request = isConsoleMode() ? Request::current() : new Request; // Fallback to default request
		
            $data = $request->all();
            /** @var \Clicalmani\Foundation\Http\Request */
            $request = $instance;
            $request->extend($data);

            if ($this instanceof MethodReflector) {
                if ($attribute = (new \ReflectionMethod($this->getClass(), $this->getName()))->getAttributes(AsValidator::class)) {
                    $request->merge($attribute[0]->newInstance()->args);
                }
            }
            
            Request::current($request);

            return $request;
        }
        
        return null;
    }

    public function handleMailer(string $class) : ?MailerInterface
    {
        if (is_subclass_of($class, Mailer::class) || $class === MailerInterface::class) {

            $mailers = app()->config('mail.mailers', []);
            $container = Container::getInstance();

            foreach ($mailers as $name => $mailer) {
                if ($container->has("$name.mailer")) {

                    $instance = $container->get("$name.mailer");

                    if ($instance instanceof $class) {
                        return $instance;
                    }

                    break;
                }
            }
        }

        return null;
    }
}
