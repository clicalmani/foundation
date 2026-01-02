<?php
namespace Clicalmani\Foundation\Http\Controllers;

use Clicalmani\Foundation\Http\Request;
use Clicalmani\Foundation\Exceptions\ModelNotFoundException;
use Clicalmani\Database\Factory\Models\Elegant;
use Clicalmani\Foundation\Acme\Container;
use Clicalmani\Foundation\Providers\RouteServiceProvider;
use Clicalmani\Foundation\Routing\Exceptions\RouteNotFoundException;
use Clicalmani\Foundation\Support\Facades\Route;
use Clicalmani\Foundation\Test\Controllers\TestController;
use Clicalmani\Routing\Memory;

/**
 * RequestController class
 * 
 * @package Clicalmani\Foundation/flesco 
 * @author @Clicalmani\Foundation
 */
class RequestController
{
	/**
	 * Current route
	 * 
	 * @var \Clicalmani\Routing\Route
	 */
	private $route;

	/**
	 * Controller action
	 * 
	 * @var mixed
	 */
	private $action;

	/**
	 * Unique class instance
	 * 
	 * @var object
	 */
	protected $instance;

	/**
	 * Application container instance
	 * 
	 * @var \Clicalmani\Foundation\Acme\Container
	 */
	protected Container $container;

	/**
	 * This method returns an instance of the given class.
	 * 
	 * @param string $class
	 * @return object
	 */
    public function getInstance(string $class) : object
    {
		if ($this->instance instanceof $class) return $this->instance;
		
		$this->container->set($class);
		return $this->instance = $this->container->get($class);
    }

	/**
	 * Render request response
	 * 
	 * @return never
	 */
	public function render() : never
	{
		$this->container = Container::getInstance();
		$response = $this->getResponse();
		
		/**
		 * |-----------------------------------------------------------------------------------
		 * |                                After Hook
		 * |-----------------------------------------------------------------------------------
		 * A hook to run after the request has been processed and before the response is sent.
		 * A hook can be used to modify the response before it is sent.
		 */
		if ($hook = $this->route->afterHook()) $response = $hook($response);

		/**
		 * |-----------------------------------------------------------------------------------
		 * |                                Response
		 * |-----------------------------------------------------------------------------------
		 * 
		 * Fire route service providers before sending response. A service provider can be used to
		 * redirect the route to a different location or set response headers.
		 */
		RouteServiceProvider::fireTPS($response, 1);

		die($response);
	}

	/**
	 * Get the action for the current request
	 * 
	 * @return mixed
	 */
    private function getAction() : mixed
	{
		if ( isset( $this->action ) ) {
			return $this->action;
		}
		
		$request = new Request;
		$builder = \Clicalmani\Foundation\Support\Facades\Config::route('default_builder');
		
		/** @var \Clicalmani\Routing\Route $route */
		if ($route = (new $builder)->build()) {
			
			$this->route = $route;
			
			// Do Redirect
			if ($route->redirect) $this->redirect();

			$this->action = $this->route->action;
			
			Memory::currentRoute($route);
			
			if ( $response_code = $this->route->isAuthorized($request) ) {
				$this->handleResponseCode($response_code);
			}
			
			return $this->action;
		}
		
		throw new RouteNotFoundException( client_uri() );
    }
	
	/**
	 * Get request response
	 * 
	 * @return \Psr\Http\Message\ResponseInterface|\Clicalmani\Foundation\Http\RedirectInterface
	 */
	protected function getResponse() : \Psr\Http\Message\ResponseInterface|\Clicalmani\Foundation\Http\ResponseInterface|\Clicalmani\Foundation\Http\RedirectInterface
	{
		$action = $this->getAction();
		
		/**
		 * Checks for action
		 */
		if (is_array($action) AND count($action) === 2) {
			$reflector = new MethodReflector(new \ReflectionMethod($this->getInstance($action[0]), $action[1]));
		} elseif( is_string($action) ) {
			$reflector = new MethodReflector(new \ReflectionMethod($this->getInstance($action), '__invoke'));
		} elseif (is_callable($action)) {
			$reflector = new FunctionReflector(new \ReflectionFunction($action));
		}
		
		return $this->invokeMethod($reflector);
	}

	/**
	 * Invoke the method with the given reflector.
	 * 
	 * @param \Clicalmani\Foundation\Http\Controllers\ReflectorInterface $reflector
	 * @return \Psr\Http\Message\ResponseInterface|\Clicalmani\Foundation\Http\RedirectInterface
	 */
	public function invokeMethod(ReflectorInterface $reflector) : \Psr\Http\Message\ResponseInterface|\Clicalmani\Foundation\Http\ResponseInterface|\Clicalmani\Foundation\Http\RedirectInterface
	{
		$request = isConsoleMode() ? Request::current() : new Request; // Fallback to default request
		Request::current($request);
		
		/** @var \ReflectionParameter[] */
		$parameters = $reflector->getParameters();
		$route_parameters = collection($this->route->getParameters())->map(fn($segment) => $segment->value)->toArray();
		
		$args = collection($parameters)->map(fn() => null)->toArray();
		$services = array_filter($this->container->builder()->getServiceIds(), fn(string $id) => str_starts_with($id, '*.'));
		
		foreach ($parameters as $i => $param) {
			$param_type = $param->getType();

			if ($param_type?->isBuiltin()) {
				$args[$i] = array_shift($route_parameters);
				continue;
			}

			foreach (Reflector::listTypes($param) as $listedType) {

				try {
					foreach ($services ?? [] as $id) {
						/** @var \Clicalmani\Foundation\Http\Controllers\InjectionLocator */
						$obj = $this->container->get($id);
						$obj->setType($listedType);
						$obj->setReflection($reflector);
						$obj->setRoute($this->route);
						if ($inst = $obj->handle()) {
							$obj->inject($args, $inst, $i);
							break 2;
						}
					}
				} catch(ModelNotFoundException $e) {
					return $this->handleMissingResource($e->getMessage());
				}
			}
		}
		
		try {
			if ($reflector instanceof MethodReflector) return $reflector($this->container->getClassIntance($reflector->getClass()), ...$args);
			return $reflector(...$args);
		} catch (ModelNotFoundException $e) {
			return $this->handleMissingResource($e->getMessage());
		}
	}

	/**
	 * Resolve route binding
	 * 
	 * @param \Clicalmani\Database\Factory\Models\Elegant $model
	 * @param string $resource
	 * @return void
	 */
	private function resolveRouteBinding(Elegant $model) : void
	{
		if ($scope = $this->route->scoped()) {
			$scope_name = collection(explode('_', $model))->map(fn(string $part) => ucfirst($part))->join('');
			$keyName = $scope[$scope_name];
		} else $keyName = $model->getKey();

		// Resolve route binding inside the model
		$reflector = new MethodReflector(new \ReflectionMethod($model, 'resolveRouteBinding'));
		$reflector($model, $model->{$keyName}, $keyName);

		// Global route binding
		if (NULL !== $callback = \App\Providers\RouteServiceProvider::routeBindingCallback())
			$callback($model->{$keyName}, $keyName, $model);
	}

	/**
	 * Controller test
	 * 
	 * @param string $action Test action
	 * @return \Clicalmani\Foundation\Test\Controllers\TestController
	 */
	public function test(string $action) : \Clicalmani\Foundation\Test\Controllers\TestController
	{
		return with( new TestController )->new($action);
	}

	private function redirect()
	{
		$this->sendStatus($this->route->redirect);
	}

	private function handleResponseCode(int $response_code) : void
	{
		if (200 !== $response_code) {
			$this->sendStatus($response_code);
			EXIT;
		}
	}

	private function sendStatus(int $code)
	{
		if (Route::isApi()) response()->sendStatus($code);
		else {
			http_response_code($code);
			view($code);
		}

		exit;
	}

	private function handleMissingResource(string $resource) : mixed
	{
		if ( $callback = $this->route->missing() ) {
			return $callback();
		}
		$this->resolveRouteBinding(new $resource);
		return response()->notFound();
	}
}
