<?php
namespace Clicalmani\Core\Http\Middlewares;

use Clicalmani\Core\Providers\ServiceProvider;

/**
 * Class MiddlewareAccessor
 *
 * Provides utility methods to parse middleware directives, extract parameters, 
 * validate class implementations, and instantiate middleware objects.
 *
 * @package Clicalmani\Core\Http\Middlewares
 * @author clicalmani
 */
final class MiddlewareAccessor implements MiddlewareAccessorInterface
{
    /**
     * Stores the parsed parts of the middleware string directive (name and parameters).
     *
     * @var array<int, string>
     */
    private static array $pairs = [];
    
    /**
     * The fully qualified class name of the resolved middleware.
     *
     * @var string
     */
    private string $className;

    /**
     * Creates a new instance of the accessor from a raw middleware string directive.
     *
     * @param string $name Raw middleware directive (e.g., 'auth:admin,user' or 'App\Middlewares\CustomMiddleware').
     * @return self
     */
    public static function fromName(string $name): self
    {
        self::$pairs = explode(':', $name);
        return new self();
    }

    /**
     * Retrieves the middleware alias or class name without parameters.
     *
     * @return string
     */
    public function name(): string
    {
        return self::$pairs[0] ?? '';
    }

    /**
     * Extracts and returns the parameter array passed to the middleware directive.
     *
     * @return array<int, string>
     */
    public function params(): array
    {
        return explode(',', self::$pairs[1] ?? '');
    }

    /**
     * Determines whether the middleware directive refers directly to an inline class.
     *
     * @return bool
     */
    public function isInline(): bool
    {
        return class_exists($this->name());
    }

    /**
     * Verifies that the resolved class name inherits from the base Middleware class.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return is_subclass_of($this->className, Middleware::class);
    }

    /**
     * Sets the resolved middleware class name.
     *
     * @param string $className Fully qualified middleware class name.
     * @return void
     */
    public function setClass(string $className): void
    {
        $this->className = $className;
    }

    /**
     * Gets the resolved middleware class name.
     *
     * @return string
     */
    public function getClass(): string
    {
        return $this->className;
    }

    /**
     * Resolves and instantiates a middleware object from a given directive string.
     *
     * @param string $name Raw middleware directive string.
     * @return MiddlewareInterface|null Instantiated middleware object, or null if resolution fails.
     */
    public static function getMiddleware(string $name): ?MiddlewareInterface
    {
        $accessor = self::fromName($name);

        if ($accessor->isInline()) {
            $accessor->setClass($name);
            if ($accessor->exists()) {
                return new $name(...$accessor->params());
            }
        }

        $className = ServiceProvider::getProvidedMiddleware(
            \Clicalmani\Core\Support\Facades\Route::gateway(),
            $accessor->name()
        );
        
        if (null !== $className) {
            $accessor->setClass($className);
            if ($accessor->exists()) return new $className(...$accessor->params());
        }

        return null;
    }
}