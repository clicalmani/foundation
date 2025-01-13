<?php
namespace Clicalmani\Foundation\Http\Controllers;

use Clicalmani\Foundation\Http\Requests\Request;
use Clicalmani\Foundation\Exceptions\ModelNotFoundException;
use Clicalmani\Database\Factory\Models\Model;
use Clicalmani\Foundation\Providers\RouteServiceProvider;
use Clicalmani\Foundation\Routing\Exceptions\RouteNotFoundException;
use Clicalmani\Foundation\Routing\Route;
use Clicalmani\Foundation\Test\Controllers\TestController;
use Clicalmani\Foundation\Validation\AsValidator;
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
		$request = new Request;

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
		
		$request = new Request([]);
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
	 * @return mixed
	 */
	protected function getResponse()
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
	 * @return mixed
	 */
	public function invokeMethod(ReflectorInterface $reflector) : mixed
	{
		$request = new Request;							  // Fallback to default request
		/** @var int|null */
		$request_pos = null;
		
		if ($arr = $reflector->getRequest()) {
			/** @var \Clicalmani\Foundation\Http\Requests\Request */
			$request = new $arr['name'];
			$request_pos = $arr['pos'];
			$this->validateRequest($request);
		}

		Request::currentRequest($request);

		$request_parameters = $this->getRequestParameters();
		/** @var \ReflectionParameter[] */
		$parameters = $reflector->getParameters();
		/** @var string */
		$resource = null;
		/** @var int */
		$resource_pos = null;

		if ($arr = $reflector->getResource()) {
			try {
				$resource = $this->bindResource($reflector);
				$resource_pos = $arr['pos'];
			} catch(ModelNotFoundException $e) {
				
				if ( $callback = $this->route->missing() ) {
					return $callback();
				}

				return response()->status(404, 'NOT_FOUND', $e->getMessage());		// Not Found
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
		
		foreach ($parameters as $i => $param) {
			$param_reflector = new ParameterReflector($param);
			$arg = @$args[$i] ?? null;
			$param_reflector->setType($arg);
			$args[$i] = $arg;
		}
		
		$args = collection(array_merge($args, array_values($request_parameters)))
					->filter(fn($arg) => $arg)
					->toArray();
		
		if ($reflector instanceof MethodReflector) return $reflector($this->getInstance($reflector->getClass()), ...$args);

		return $reflector(...$args);
	}

	/**
	 * Validate request
	 * 
	 * @param \Clicalmani\Foundation\Http\Requests\Request
	 * @return mixed
	 */
	private function validateRequest(Request $request) : mixed
	{
		if (method_exists($request, 'authorize')) {
			if (false == $request->authorize()) {
				response()->status(403, 'FORBIDEN', '403 Forbiden');		// Forbiden
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
	 * @param \Clicalmani\Foundation\Http\Requests\Request
	 * @return array
	 */
	private function getRequestParameters() : array
    {
		/** @var \Clicalmani\Foundation\Http\Requests\Request */
		$request = Request::currentRequest();

		if ( inConsoleMode() ) return $request->all();
		
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
	 * Bind a model resource
	 * 
	 * @param \Clicalmani\Foundation\Http\Controllers\ReflectorInterface $reflector
	 * @return mixed
	 */
	private function bindResource(ReflectorInterface $reflector) : mixed
	{
		$resource = $reflector->getResource()['name'];
		$request = Request::currentRequest();

		if ($reflector instanceof MethodReflector) {
			if ($attribute = (new \ReflectionMethod($reflector->getClass(), $reflector->getName()))->getAttributes(AsValidator::class)) {
				$request->merge($attribute[0]->newInstance()->args);
				Request::currentRequest($request);
			}
		}
		
		if ( NULL !== $id = $request->id AND in_array($reflector->getName(), ['create', 'show', 'update', 'destroy']) ) {

			// Request parameters
			$parameters = explode(',', (string) $id);
			
			if ( count($parameters) ) {
				if ( count($parameters) === 1 ) $parameters = $parameters[0];	// Single primary key
				
				/** @var \Clicalmani\Database\Factory\Models\Model */
				$model = new $resource($parameters);

				if ( $model->get()->isEmpty() ) throw new ModelNotFoundException($resource);

			} else throw new ModelNotFoundException($resource);
		} else {
			
			/** @var \Clicalmani\Database\Factory\Models\Model */
			$model = new $resource;
		}

		/**
		 * Bind resources
		 */
		$this->bindRoutines($model);
		
		return $model;
	}

	/**
	 * Bind resource routines
	 * 
	 * @param \Clicalmani\Database\Factory\Models\Model $obj
	 * @return void
	 */
	private function bindRoutines(Model $model) : void
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
	 * @param \Clicalmani\Database\Factory\Models\Model $obj
	 * @return void
	 */
	private function getResourceDistinct(Model $obj) : void
	{
		if ( $distinct = $this->route?->distinctResult() ) {
			$obj->distinct($distinct);
		}
	}

	/**
	 * Ignore duplicates
	 * 
	 * @param \Clicalmani\Database\Factory\Models\Model $obj
	 * @return void
	 */
	private function createResourceIgnore(Model $obj) : void
	{
		if ( $ignore = $this->route?->ignoreKeyWarning() ) {
			$obj->ignore($ignore);
		}
	}

	/**
	 * Delete from
	 * 
	 * @param \Clicalmani\Database\Factory\Models\Model $obj
	 * @return void
	 */
	private function resourceDeleteFrom(Model $obj) : void
	{
		if ( $from = $this->route?->deleteFrom() ) {
			$obj->from($from);
		}
	}

	/**
	 * Calc rows
	 * 
	 * @param \Clicalmani\Database\Factory\Models\Model $obj
	 * @return void
	 */
	private function resourceCalcRows(Model $obj) : void
	{
		if ( $enable = $this->route?->calcFoundRows() ) {
			$obj->calcFoundRows($enable);
		}
	}

	/**
	 * Limit rows
	 * 
	 * @param \Clicalmani\Database\Factory\Models\Model $obj
	 * @return void
	 */
	private function resourceLimit(Model $obj) : void
	{
		if ( $arr = $this->route?->limitResult() ) {
			$obj->limit($arr['offset'], $arr['count']);
		}
	}

	/**
	 * Order by
	 * 
	 * @param \Clicalmani\Database\Factory\Models\Model $obj
	 * @return void
	 */
	private function resourceOrderBy(Model $obj) : void
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
		/** @var int */
		$redirect_code = $this->route->redirect;

		switch($redirect_code) {
			case 302: $this->sendStatus($redirect_code, 'FOUND', 'Temporary Unavailable'); break;
			case 301: $this->sendStatus($redirect_code, 'PAGE_MOVED_PERMANENTLY', 'Page Moved Permenently'); break;
			case 308: $this->sendStatus($redirect_code, 'PERMANENT_REDIRECT', 'Permanent Redirect'); break;
			case 303: $this->sendStatus($redirect_code, 'SEE_OTHER', 'Redirect'); break;
			case 307: $this->sendStatus($redirect_code, 'PERMANENTLY_REDIRECT', 'Temporary Unavailable'); break;
			case 300: $this->sendStatus($redirect_code, 'MULTIPLE_CHOICES', 'Multiple Choices'); break;
			case 304: $this->sendStatus($redirect_code, 'NOT_MODIFIED', 'Not Modified'); break;
			default: $this->sendStatus($redirect_code, 'UNKNOW', 'Unknow'); break;
		}
		
		exit;
	}

	private function handleResponseCode(int $response_code) : void
	{
		if (200 !== $response_code) {
			switch($response_code) {
				case 401: $this->sendStatus($response_code, 'UNAUTHORIZED_REQUEST_ERROR', 'Request Unauthorized'); break;
				case 403: $this->sendStatus($response_code, 'FORBIDEN', '403 Forbiden'); break;
				case 404: $this->sendStatus($response_code, 'NOT FOUND', 'Not Found'); break;
				default: $this->sendStatus($response_code, 'UNKNOW', 'Unknow'); break;
			}

			EXIT;
		}
	}

	private function sendStatus(int $code, string $status_code, string $message)
	{
		if (Route::isApi()) response()->status($code, $status_code, $message);
		else response()->send($code);

		exit;
	}
}