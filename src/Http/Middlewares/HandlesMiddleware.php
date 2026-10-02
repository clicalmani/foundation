<?php
namespace Clicalmani\Core\Http\Middlewares;

use Clicalmani\Core\Http\Request;
use Clicalmani\Core\Http\RequestInterface;
use Clicalmani\Core\Providers\ServiceProvider;
use Clicalmani\Core\Exceptions\MiddlewareException;
use Clicalmani\Core\Http\Response;

/**
 * Trait HandlesMiddleware
 *
 * Provides methods for resolving, instantiating, and executing HTTP middleware.
 *
 * @package Clicalmani\Core\Http\Middlewares
 * @author clicalmani
 */
trait HandlesMiddleware
{
    /**
     * Resolves a middleware alias or class name into an instantiable class name.
     *
     * @param string $name_or_class The middleware alias or fully qualified class name.
     * @return MiddlewareInterface
     * @throws MiddlewareException If the middleware cannot be resolved.
     */
    private function resolveMiddleware(string $name_or_class) : MiddlewareInterface
    {
        if ($middleware = MiddlewareAccessor::getMiddleware($name_or_class)) {
            return $middleware;
        }

        throw new MiddlewareException(
            sprintf("Can not find a global or inline middleware named %s", $name_or_class)
        );
    }

    /**
     * Instantiates and runs a resolved middleware, returning the resulting HTTP status code.
     *
     * @param MiddlewareInterface $middleware The fully qualified middleware class name.
     * @param RequestInterface|null $request The incoming HTTP request instance.
     * @return int The HTTP status code returned by the middleware stack.
     */
    private function runMiddleware(MiddlewareInterface $middleware, ?RequestInterface $request = null) : int
    {
        $request ??= Request::current();

        /** @var \Clicalmani\Core\Http\Response */
        $response = app()->response;

        /** @var \Clicalmani\Psr\Response|\Clicalmani\Core\Http\RedirectInterface */
        $auth = $middleware->handle(
            $request,
            $response,
            function (mixed $req = null, mixed $resp = null) use (&$request, &$response) {
                $response = $resp ?? $response;
                $request  = $req ?? $request;
                return $response->status(http_response_code());
            }
        );

        if ($auth instanceof \Clicalmani\Core\Http\Redirect) {
            die($auth);
        }

        Request::current($request);
        app()->response = $response;

        return $auth->getStatusCode();
    }

    /**
     * Resolves and executes a single middleware by its alias or class name.
     *
     * @param string $name_or_class The middleware alias or fully qualified class name.
     * @param RequestInterface|null $request The incoming HTTP request instance.
     * @return int The HTTP status code resulting from middleware execution.
     * @throws MiddlewareException If the middleware cannot be resolved.
     */
    private function checkMiddleware(string $name_or_class, ?RequestInterface $request = null) : int
    {
        return $this->runMiddleware($this->resolveMiddleware($name_or_class), $request);
    }

    /**
     * Sequentially checks an array of middlewares; halts and returns the status code if any non-200 status is encountered.
     *
     * @param array<int, string> $middlewares List of middleware aliases or class names.
     * @param RequestInterface|null $request The incoming HTTP request instance.
     * @return int The HTTP status code (200 if all pass, or the first non-200 code).
     * @throws MiddlewareException If any middleware in the list cannot be resolved.
     */
    private function checkMiddlewares(array $middlewares, ?RequestInterface $request = null) : int
    {
        foreach ($middlewares as $name_or_class) {
            $statusCode = $this->checkMiddleware($name_or_class, $request);
            if (200 !== $statusCode) return $statusCode;
        }

        return 200;
    }
}