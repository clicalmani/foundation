<?php
namespace Clicalmani\Foundation\Http\Controllers;

class MethodReflector extends Reflector implements ReflectorInterface
{
    /**
     * Set the method name.
     * 
     * @return void
     */
    public function setName(string $new_name) : void
    {
        $this->reflect->name = $new_name;
    }

    /**
     * Get the method name.
     * 
     * @return string
     */
    public function getName() : string
    {
        return $this->reflect->name;
    }

    /**
     * Get the class name where the method is declared.
     * 
     * @return string
     */
    public function getClass() : string
    {
        return $this->reflect->getDeclaringClass()->getName();
    }

    public function __invoke(object $object, mixed ...$args) : mixed
    {
        if ($middleware = $object->getMiddleware()) {
            foreach ($middleware as $name => $pattern) {
                if (preg_match("/$pattern/", $this->getName())) {
                    $object->handleResponseCode( $object->isAuthorized($name) );
                }
            }
        }

        return $this->reflect->invoke($object, ...$args);
    }
}