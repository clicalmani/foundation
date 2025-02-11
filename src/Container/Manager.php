<?php
namespace Clicalmani\Foundation\Container;

abstract class Manager
{
    /**
     * Current class to load
     * 
     * @var string
     */
    protected string $current_class;

    /**
     * Custom bindings
     * 
     * @var array
     */
    private $bindings = [
                            'App/' => 'app/',
                            'Database/' => 'database/',
                            'Factories/' => 'factories/',
                            'Seeders/' => 'seeders/',
                        ];

    /**
     * Resolved classes
     * 
     * @var array
     */
    protected static array $resolved_classes = [];

    /**
     * Constructor
     * 
     * @param ?string $root_path
     */
    public function __construct(protected ?string $root_path = null)
    {
        // Registers the autoloader
        spl_autoload_register(function(?string $classname) {
            $this->current_class = $classname;

            $filename = $this->find();
            static::$resolved_classes[$this->current_class] = $filename;
            $this->require( $filename );
        });
    }

    /**
     * Find a class
     * 
     * @return string
     */
    public function find() : string
    {
        if ( $this->isCached() ) return static::$resolved_classes[$this->current_class];
        
        $file_name = str_replace("\\", DIRECTORY_SEPARATOR, $this->getNamespace()) . DIRECTORY_SEPARATOR . $this->getClassName() . '.php';
        $file_name = $this->bind($this->root_path . DIRECTORY_SEPARATOR . $file_name);

        return $file_name;
    }

    /**
     * Returns class namespace
     * 
     * @return string
     */
    public function getNamespace() : string
    {
        if (false !== ($lastNsPos = $this->lastNameSpacePos())) return substr($this->current_class, 0, $lastNsPos);

        return '';
    }

    /**
     * Returns the class file name.
     * 
     * @return string
     */
    public function getClassName() : string
    {
        if (false !== ($lastNsPos = $this->lastNameSpacePos())) return substr($this->current_class, $lastNsPos + 1);

        return '';
    }

    /**
     * Last namespace position
     * 
     * @return int|false
     */
    public function lastNameSpacePos() : int|false
    {
        return strripos($this->current_class, '\\');
    }

    /**
     * Bind dependence
     * 
     * @param string $fullFileName
     * @return string
     */
    public function bind(string $fullFileName) : string
    {
        foreach ($this->bindings as $key => $value) {
            $fullFileName = str_replace($key, $value, $fullFileName);
        }

        return $fullFileName;
    }

    /**
     * Require a file
     * 
     * @param string $file_name
     * @return mixed
     */
    protected function require(string $file_name) : mixed
    {
        if ( @ file_exists($file_name) ) return require_once $file_name;
        return null;
    }

    /**
     * Cache a class
     * 
     * @return void
     */
    protected function cache(string $classname) : void
    {
        $this->current_class = $classname;
        static::$resolved_classes[$classname] = $this->find();
    }

    /**
     * Verify if the given class is cached.
     * 
     * @return bool
     */
    private function isCached() : bool
    {
        if ( array_key_exists($this->current_class, static::$resolved_classes) ) return true;

        return false;
    }

    /**
     * Inject method dependencies
     * 
     * @param object $object
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function injectMethodDependencies(object|string $object_or_class, string $method, array $parameters = [])
    {
        $reflectionMethod = new \ReflectionMethod($object_or_class, $method);
        $dependencies = [];

        foreach ($reflectionMethod->getParameters() as $parameter) {
            $dependency = $parameter->getType();
            if ($dependency) {
                $dependencies[] = $this->resolve($dependency->getName());
            } else {
                if (array_key_exists($parameter->name, $parameters)) {
                    $dependencies[] = $parameters[$parameter->name];
                } else {
                    $dependencies[] = $parameter->getDefaultValue();
                }
            }
        }

        return $reflectionMethod->invokeArgs($object_or_class, $dependencies);
    }

    /**
     * Inject constructor dependencies
     * 
     * @param string $class
     * @return object
     */
    public function injectConstructorDependencies(string $class) : object
    {
        $reflectionClass = new \ReflectionClass($class);
        $constructor = $reflectionClass->getConstructor();

        if (is_null($constructor)) {
            return new $class();
        }

        $dependencies = [];
        foreach ($constructor->getParameters() as $parameter) {
            $dependency = $parameter->getType();
            if ($dependency) {
                $dependencies[] = $this->resolve($dependency->getName());
            } else {
                $dependencies[] = $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
            }
        }

        return $reflectionClass->newInstanceArgs($dependencies);
    }

    /**
     * Resolve a class instance
     * 
     * @param string $class
     * @return mixed
     */
    protected function resolve(string $class)
    {
        if ($this->isCached()) {
            return new $class();
        }

        $filename = $this->find();
        $this->require($filename);

        return new $class();
    }
}
