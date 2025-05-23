<?php
namespace Clicalmani\Foundation\Support\Facades;

class Facade 
{
    /**
     * PHP magic __callStatic
     * 
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public static function __callStatic($method, $args) : mixed
    {
        try {
            $class = self::getClass();
            
            if ( method_exists($class, "$method") ) {
                return with( new $class )->{"$method"}( ...$args );
            }

            throw new \Exception(
                sprintf("Method %s does not exists on class %s. Called at line %d in %s", $method, get_called_class(), __LINE__, __CLASS__)
            );

        } catch (\ArgumentCountError $e) {
            throw new \ArgumentCountError($e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * PHP function get_called_class() wrapper
     * 
     * @return string
     */
    private static function getClass() : string
    {
        $class = get_called_class();
        
        if ( $class === \Clicalmani\Foundation\Routing\Route::class )
            return \Clicalmani\Routing\Routing::class;

        if ( is_subclass_of($class, \Clicalmani\Foundation\Http\Requests\RequestController::class) || $class === \Clicalmani\Foundation\Http\Requests\RequestController::class) {
            return \Clicalmani\Foundation\Http\Controllers\RequestController::class;
        } 

        if ( $class === \Inertia\Inertia::class ) {
            return \Inertia\Response::class;
        }

        $class = "Clicalmani\Foundation\Maker\Logic\\" . substr($class, strrpos($class, "\\") + 1);

        if ( class_exists($class) ) return $class;

        throw new \Exception(sprintf("Facade class %s does not exists.", $class));
    }
}