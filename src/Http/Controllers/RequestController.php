<?php
namespace Clicalmani\Core\Http\Controllers;

use Clicalmani\Core\Http\Request;
use Clicalmani\Core\Exceptions\ModelNotFoundException;
use Clicalmani\Database\Factory\Models\Elegant;
use Clicalmani\Core\Acme\Container;
use Clicalmani\Core\Http\Events\HttpRequest;
use Clicalmani\Core\Http\Middlewares\MiddlewareAccessor;
use Clicalmani\Core\Providers\RouteServiceProvider;
use Clicalmani\Core\Routing\Exceptions\RouteNotFoundException;
use Clicalmani\Core\Support\Facades\Route;
use Clicalmani\Core\Test\Controllers\TestController;
use Clicalmani\Routing\Registry;
use Inertia\Inertia;

/**
 * RequestController class
 * 
 * @package Clicalmani\Core/flesco 
 * @author @Clicalmani\Core
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
	 * @var \Clicalmani\Core\Acme\Container
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
		 * | Redirect
		 * |-----------------------------------------------------------------------------------
		 * A hook to run after the request has been processed and before the response is sent.
		 * A hook can be used to modify the response before it is sent.
		 */
		if ($hook = $this->route->afterHook()) $response = $hook($response);

		/**
		 * |-----------------------------------------------------------------------------------
		 * | Terminable Middleware
		 * |-----------------------------------------------------------------------------------
		 * 
		 * Call route terminable middlewares
		 */
		foreach ($this->route->getMiddlewares() as $name_or_class) {
			if ($middleware = MiddlewareAccessor::fromName($name_or_class) AND method_exists($middleware, 'terminate')) {
				$middleware->terminate(Request::current(), $response);
			}
		}

		// Session cookie
		if ($this->route->isProtected()) {
			$params = array_merge(
				...collect($this->route->getParameters())
					->map(fn($seg) => [ltrim($seg->name, '?:') => $seg->value])
					->toArray()
			);
			ksort($params);

			$hash = enc()->createParametersHash($params);
			$key  = enc()::storageKey($this->route, $params); // même méthode que verifyParameters

			session($key, $hash)->set();
			cookie($key, $hash, 60 * 60 * 24 * 7)->set();

			logger()->info('RController', ['params' => $params, 'key' => $key, 'hash' => $hash]);
		}
		
		die($response);
	}

	/**
	 * Get the action for the current request
	 * 
	 * @return mixed
	 */
    public function getAction() : mixed
	{
		if ( isset( $this->action ) ) {
			return $this->action;
		}
		
		$request = new Request;
		$builder = \Clicalmani\Core\Support\Facades\Config::route('default_builder');
		
		/** @var \Clicalmani\Routing\Route $route */
		if ($route = (new $builder)->build()) {
			
			$this->route = $route;
			$request->extend($_REQUEST);
			
			// Do Redirect
			if ($route->isDirty()) $this->redirect();

			$this->action = $this->route->action;
			
			Registry::currentRoute($route);
			Request::current($request);
			
			if ( $response_code = $this->route->isAuthorized($request) ) {
				$this->handleResponseCode($response_code);
			}
			
			return $this->action;
		}
		
		throw new RouteNotFoundException( client_url() );
    }
	
	/**
	 * Get request response
	 * 
	 * @return \Psr\Http\Message\ResponseInterface|\Clicalmani\Core\Http\RedirectInterface
	 */
	protected function getResponse() : \Psr\Http\Message\ResponseInterface|\Clicalmani\Core\Http\ResponseInterface|\Clicalmani\Core\Http\RedirectInterface
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
	 * @param \Clicalmani\Core\Http\Controllers\ReflectorInterface $reflector
	 * @return \Psr\Http\Message\ResponseInterface|\Clicalmani\Core\Http\ResponseInterface|\Clicalmani\Core\Http\RedirectInterface
	 */
	public function invokeMethod(
		ReflectorInterface $reflector
	): \Psr\Http\Message\ResponseInterface|\Clicalmani\Core\Http\ResponseInterface|\Clicalmani\Core\Http\RedirectInterface
	{
		$request = Request::current() ?? tap(new Request, fn($r) => Request::current($r));

		/** @var InjectionManager $injection */
		$injection = $this->container->get(InjectionManager::class);
		
		try {
			$args = $injection->resolve($reflector, $this->route);
		} catch (ModelNotFoundException $e) {
			return $this->handleMissingResource($e->getMessage());
		}

		try {
			event($request, 'http.request.started');
			
			$response = $reflector instanceof MethodReflector
				? $reflector($this->container->getClassIntance($reflector->getClass()), ...$args)
				: $reflector(...$args);

			$request->responseSize = method_exists($response, 'getContent')
				? strlen($response->getContent())
				: 0;
			$request->status = $response->getStatusCode();

			event($request, 'http.request.finished');

			return $response;
		} catch (ModelNotFoundException $e) {
			return $this->handleMissingResource($e->getMessage());
		} catch (\Throwable $e) {
			$request->status = 500;
			event($request, 'http.request.finished');
			if ( ! app()->environment('production') ) {
                (new \Clicalmani\Core\Resources\ErrorRenderer)->render($e);
            }
			throw $e;
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
		} else $keyName = $model->getKey()->scalarName();

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
	 * @return \Clicalmani\Core\Test\Controllers\TestController
	 */
	public function test(string $action) : \Clicalmani\Core\Test\Controllers\TestController
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
			if (app()->response instanceof \Inertia\Response) {
				die(app()->response);
			}
			app()->response?->sendStatus($code);
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
