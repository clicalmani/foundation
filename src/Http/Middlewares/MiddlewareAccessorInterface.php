<?php
namespace Clicalmani\Core\Http\Middlewares;

/**
 * Interface MiddlewareAccessorInterface
 *
 * Defines the contract for parsing, resolving, and instantiating HTTP middleware.
 *
 * @package Clicalmani\Core\Http\Middlewares
 * @author clicalmani
 */
interface MiddlewareAccessorInterface
{
    /**
     * Creates a new instance of the accessor from a raw middleware string directive.
     *
     * @param string $name Raw middleware directive.
     * @return self
     */
    public static function fromName(string $name): self;

    /**
     * Retrieves the middleware alias or class name without parameters.
     *
     * @return string
     */
    public function name(): string;

    /**
     * Extracts and returns the parameter array passed to the middleware directive.
     *
     * @return array<int, string>
     */
    public function params(): array;

    /**
     * Verifies whether the resolved class name exists and extends the base middleware class.
     *
     * @return bool
     */
    public function exists(): bool;

    /**
     * Determines whether the middleware directive refers directly to an inline class name.
     *
     * @return bool
     */
    public function isInline(): bool;

    /**
     * Sets the resolved middleware class name.
     *
     * @param string $className Fully qualified middleware class name.
     * @return void
     */
    public function setClass(string $className): void;

    /**
     * Gets the resolved middleware class name.
     *
     * @return string
     */
    public function getClass(): string;

    /**
     * Resolves and instantiates a middleware object from a given directive string.
     *
     * @param string $name Raw middleware directive string.
     * @return MiddlewareInterface|null Instantiated middleware object, or null if resolution fails.
     */
    public static function getMiddleware(string $name): ?MiddlewareInterface;
}