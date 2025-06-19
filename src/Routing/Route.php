<?php
namespace Clicalmani\Foundation\Routing;

use Clicalmani\Foundation\Support\Facades\Facade;

/**
 * @method static string[] all()
 * @method static bool isApi()
 * @method static string getClientVerb()
 * @method static \Clicalmani\Routing\Factory\RouteInterface|null current() Return the current route.
 * @method static \Clicalmani\Routing\Factory\GroupInterface|null group(mixed ...$parameters)
 * @method static void pattern(string $param, string $pattern)
 * @method static \Clicalmani\Routing\Factory\ValidatorInterface|\Clicalmani\Routing\Factory\GroupInterface register(string $method, string $route, mixed $callback, ?bool $bind = true)
 * @method static \Clicalmani\Routing\Factory\GroupInterface controller(string $class)
 * @method static \Clicalmani\Routing\Factory\ValidatorInterface|\Clicalmani\Routing\Factory\GroupInterface get(string $route, mixed $action = null) Method GET
 * @method static \Clicalmani\Routing\Factory\ValidatorInterface|\Clicalmani\Routing\Factory\GroupInterface post(string $route, mixed $action = null) Method POST
 * @method static \Clicalmani\Routing\Factory\ValidatorInterface|\Clicalmani\Routing\Factory\GroupInterface patch(string $route, mixed $action = null) Method PATCH
 * @method static \Clicalmani\Routing\Factory\ValidatorInterface|\Clicalmani\Routing\Factory\GroupInterface delete(string $route, mixed $action = null) Method DELETE
 * @method static \Clicalmani\Routing\Factory\ValidatorInterface|\Clicalmani\Routing\Factory\GroupInterface put(string $route, mixed $action = null) Method PUT
 * @method static \Clicalmani\Routing\Factory\ValidatorInterface|\Clicalmani\Routing\Factory\GroupInterface options(string $route, mixed $action = null) Method OPTIONS
 * @method static \Clicalmani\Routing\Factory\ResourceInterface match(array $matches, string $uri, mixed $action) Method MATCH
 * @method static \Clicalmani\Routing\Factory\ResourceInterface resource(mixed $resource, ?string $controller = null)
 * @method static \Clicalmani\Routing\Factory\ResourceInterface apiResource(mixed $resource, ?string $controller = null, ?array $actions = [])
 * @method static \Clicalmani\Routing\Factory\GroupInterface middleware(string $name_or_class, mixed $callback = null)
 * @method static string uri() Returns the client uri.
 * @method static \Clicalmani\Routing\Factory\ResourceInterface singleton(string $resource, string $controller) Method GET
 * @method static ?\Clicalmani\Routing\Factory\RouteInterface findByName(string $name) Find a route by name
 * @method static mixed resolve(mixed ...$params)
 * @method static bool routeExists(\Clicalmani\Routing\Factory\RouteInterface $route) Verify if route exists
 * @method static bool isGrouping()
 * @method static void validate(string $param, string $constraint)
 * @method static void pattern(string $param, string $pattern)
 * @method static string gateway()
 * @method static string getClientVerb()
 */
class Route extends Facade
{}
