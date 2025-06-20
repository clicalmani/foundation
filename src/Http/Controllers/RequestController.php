<?php
namespace Clicalmani\Foundation\Http\Controllers;

use Clicalmani\Foundation\Http\Request;
use Clicalmani\Foundation\Exceptions\ModelNotFoundException;
use Clicalmani\Database\Factory\Models\Elegant;
use Clicalmani\Foundation\Http\Response;
use Clicalmani\Foundation\Providers\RouteServiceProvider;
use Clicalmani\Foundation\Routing\Exceptions\RouteNotFoundException;
use Clicalmani\Foundation\Support\Facades\Route;
use Clicalmani\Foundation\Test\Controllers\TestController;
use Clicalmani\Validation\AsValidator;
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
	 * This method returns an instance of the given class.
	 * 
	 * @param string $class
	 * @return object
	 */
    public function getInstance(string $class) : object
    {
		if ($this->instance instanceof $class) return $this->instance;

        if ( method_exists($class, '__construct') ) {
            $reflector = new MethodReflector( (new \ReflectionClass($class))->getConstructor() );
			$args = collection($reflector->getParameters())
						->map(fn(\ReflectionParameter $parameter) => instance($reflector->getParameterClassNames($parameter)[0]))
						->toArray();

			$this->instance = new $class(...$args);
        } else $this->instance = new $class;

		return $this->instance;
    }

	/**
	 * Render request response
	 * 
	 * @return never
	 */
	public function render() : never
	{
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
	protected function getResponse() : \Psr\Http\Message\ResponseInterface|\Clicalmani\Foundation\Http\RedirectInterface
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
	public function invokeMethod(ReflectorInterface $reflector) : \Psr\Http\Message\ResponseInterface|\Clicalmani\Foundation\Http\RedirectInterface
	{
		$request = isConsoleMode() ? Request::current() : new Request; // Fallback to default request
		/** @var int|null */
		$request_pos = null;
		
		if ($arr = $reflector->getRequest()) {
			$data = $request->all();
			/** @var \Clicalmani\Foundation\Http\Request */
			$request = new $arr['name'];
			$request_pos = $arr['pos'];
			if ($data) $request->extend($data);
			$this->validateRequest($request);
		}
		
		Request::current($request);
		
		$request_parameters = $this->getRequestParameters();
		/** @var \ReflectionParameter[] */
		$parameters = $reflector->getParameters();
		/** @var string */
		$resource = null;
		/** @var int */
		$resource_pos = null;
		/** @var string */
		$nested_resource = null;
		/** @var int */
		$nested_resource_pos = null;

		if ($arr = $reflector->getResource()) {
			try {
				$resources = $this->bindResources($reflector);
				$resource = $resources[0];
				$nested_resource = @$resources[1] ?? null;
				$resource_pos = $arr['pos'];

				if ($arr = $reflector->getNestedResource()) {
					$nested_resource_pos = $arr['pos'];
				}
			} catch(ModelNotFoundException $e) {
				
				if ( $callback = $this->route->missing() ) {
					return $callback($e);
				}

				return new Response;
			}
		}

		if ($reflector instanceof MethodReflector) {
			if ($attribute = (new \ReflectionMethod($reflector->getClass(), $reflector->getName()))->getAttributes(AsValidator::class)) {
				$request->merge($attribute[0]->newInstance()->args);
			}
		}

		$args = collection($parameters)->map(fn() => null)->toArray();
		
		if (NULL !== $request_pos) {
			$args[$request_pos] = $request;
		}

		if (NULL !== $resource) {
			$args[$resource_pos] = $resource;
		}

		if (NULL !== $nested_resource) {
			$args[$nested_resource_pos] = $nested_resource;
		}
		
		foreach ($parameters as $i => $param) {
			$param_reflector = new ParameterReflector($param);
			$arg = @$args[$i] ?? null;
			$param_reflector->setType($arg);
			$args[$i] = $arg;

			if (array_key_exists($param->name, $request_parameters)) {
				$args[$i] = $request_parameters[$param->name];
			}
		}
		
		if ($reflector instanceof MethodReflector) return $reflector($this->getInstance($reflector->getClass()), ...$args);
		
		return $reflector(...$args);
	}

	/**
	 * Validate request
	 * 
	 * @param \Clicalmani\Foundation\Http\Request
	 * @return mixed
	 */
	private function validateRequest(Request $request) : mixed
	{
		if (method_exists($request, 'authorize')) {
			if (false == $request->authorize()) {
				response()->sendStatus(403);		// Forbiden
				die();
			}
		}

		if (method_exists($request, 'prepareForValidation')) {
			$request->prepareForValidation();                    // Call prepareForValidation method
		}
		
		if (method_exists($request, 'signatures')) {
			$request->signatures();                             // Set parameters signatures
		}

		return null;
	}

	/**
	 * Gather request parameters
	 * 
	 * @param \Clicalmani\Foundation\Http\Request
	 * @return array
	 */
	private function getRequestParameters() : array
    {
		/** @var \Clicalmani\Foundation\Http\Request */
		$request = Request::current();

		if ( isConsoleMode() ) return $request->getAttributes();
		
        preg_match_all('/' . config('route.parameter_prefix') . '[^\/]+/', (string) $this->route, $mathes);

        $parameters = [];
        
        if ( count($mathes) ) {

            $mathes = $mathes[0];
            
            foreach ($mathes as $name) {
                $name = substr($name, 1);    				      // Remove prefix
                
                if (preg_match('/@/', $name)) {
                    $name = substr($name, 0, strpos($name, '@')); // Remove validation part
                }
                
                if ($request->{$name}) {
                    $parameters[$name] = $request->{$name};
                }
            }
        }

        return $parameters;
    }

	/**
	 * Bind models resources
	 * 
	 * @param \Clicalmani\Foundation\Http\Controllers\ReflectorInterface $reflector
	 * @return array
	 */
	private function bindResources(ReflectorInterface $reflector) : array
	{
		$resource = $reflector->getResource()['name'];
		$nested_resource = @$reflector->getNestedResource()['name'];
		$request = Request::current();
		
		if ($reflector instanceof MethodReflector) {
			if ($attribute = (new \ReflectionMethod($reflector->getClass(), $reflector->getName()))->getAttributes(AsValidator::class)) {
				$request->merge($attribute[0]->newInstance()->args);
				Request::current($request);
			}
		}
		
		// Check if resource is present
		if ( NULL !== $id = $request->id AND in_array($reflector->getName(), ['create', 'show', 'edit', 'update', 'destroy']) ) {

			$nested_model = null;
			
			/**
			 * Model record key value
			 * 
			 * @var string[]
			 */
			$key_value = explode(',', (string)$id);
			
			if ( count($key_value) ) {
				if ( count($key_value) === 1 ) $key_value = $key_value[0];	// Single primary key
				
				/** @var \Clicalmani\Database\Factory\Models\Elegant */
				$model = new $resource($key_value);
				$this->resolveRouteBinding($model, $resource);

				if ( $model->get()->isEmpty() ) throw new ModelNotFoundException("$resource not found");

			} else throw new ModelNotFoundException("$resource not found");
		} else {
			/** @var \Clicalmani\Database\Factory\Models\Elegant */
			$model = new $resource;
		}

		// Check if nested resource is present
		if ( NULL !== $nid = $request->nid AND $nested_resource ) {
			/** 
			 * Nested model key value
			 * 
			 * @var string[] 
			 */
			$nested_key_value = explode(',', (string)$nid);

			if ( count($nested_key_value) ) {
				if ( count($nested_key_value) === 1 ) $nested_key_value = $nested_key_value[0];	// Single primary key
				
				/** @var \Clicalmani\Database\Factory\Models\Elegant */
				$nested_model = new $nested_resource($nested_key_value);
				$this->resolveRouteBinding($nested_model, $nested_resource);

				if ( $nested_model->get()->isEmpty() ) throw new ModelNotFoundException("$nested_resource not found");

			} else throw new ModelNotFoundException("$nested_resource not found");
		} else {
			/** @var \Clicalmani\Database\Factory\Models\Elegant|null */
			$nested_model = $nested_resource ? new $nested_resource: null;
		}

		/**
		 * Bind resources
		 */
		$this->bindRoutines($model);

		if ( NULL !== $nested_model ) $this->bindRoutines($nested_model);
		
		return [$model, $nested_model];
	}

	/**
	 * Resolve route binding
	 * 
	 * @param \Clicalmani\Database\Factory\Models\Elegant $model
	 * @param string $resource
	 * @return void
	 */
	private function resolveRouteBinding(Elegant $model, string $resource) : void
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
			$callback($model->{$keyName}, $keyName, $resource);
	}

	/**
	 * Bind resource routines
	 * 
	 * @param \Clicalmani\Database\Factory\Models\Elegant $obj
	 * @return void
	 */
	private function bindRoutines(Elegant $model) : void
	{
		/**
		 * Select distinct
		 */
		$this->getResourceDistinct($model);

		/**
		 * Insert ignore
		 */
		$this->createResourceIgnore($model);

		/**
		 * Delete multiple
		 */
		$this->resourceDeleteFrom($model);

		/**
		 * Pagination
		 */
		$this->resourceCalcRows($model);

		/**
		 * Limit rows
		 */
		$this->resourceLimit($model);

		/**
		 * Row order by
		 */
		$this->resourceOrderBy($model);
	}

	/**
	 * Distinct rows
	 * 
	 * @param \Clicalmani\Database\Factory\Models\Elegant $obj
	 * @return void
	 */
	private function getResourceDistinct(Elegant $obj) : void
	{
		if ( $distinct = $this->route?->distinctResult() ) {
			$obj->distinct($distinct);
		}
	}

	/**
	 * Ignore duplicates
	 * 
	 * @param \Clicalmani\Database\Factory\Models\Elegant $obj
	 * @return void
	 */
	private function createResourceIgnore(Elegant $obj) : void
	{
		if ( $ignore = $this->route?->ignoreKeyWarning() ) {
			$obj->ignore($ignore);
		}
	}

	/**
	 * Delete from
	 * 
	 * @param \Clicalmani\Database\Factory\Models\Elegant $obj
	 * @return void
	 */
	private function resourceDeleteFrom(Elegant $obj) : void
	{
		if ( $from = $this->route?->deleteFrom() ) {
			$obj->from($from);
		}
	}

	/**
	 * Calc rows
	 * 
	 * @param \Clicalmani\Database\Factory\Models\Elegant $obj
	 * @return void
	 */
	private function resourceCalcRows(Elegant $obj) : void
	{
		if ( $enable = $this->route?->calcFoundRows() ) {
			$obj->calcFoundRows($enable);
		}
	}

	/**
	 * Limit rows
	 * 
	 * @param \Clicalmani\Database\Factory\Models\Elegant $obj
	 * @return void
	 */
	private function resourceLimit(Elegant $obj) : void
	{
		if ( $arr = $this->route?->limitResult() ) {
			$obj->limit($arr['offset'], $arr['count']);
		}
	}

	/**
	 * Order by
	 * 
	 * @param \Clicalmani\Database\Factory\Models\Elegant $obj
	 * @return void
	 */
	private function resourceOrderBy(Elegant $obj) : void
	{
		if ( $order_by = $this->route?->orderResultBy() ) {
			$obj->orderBy($order_by);
		}
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
}
