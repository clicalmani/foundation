<?php
namespace Clicalmani\Foundation\Http\Requests;

use Clicalmani\Foundation\Exceptions\MiddlewareException;
use Clicalmani\Foundation\Http\Response\Response;
use Clicalmani\Foundation\Providers\ServiceProvider;
use Clicalmani\Foundation\Support\Facades\Facade;

/**
 * Handles HTTP requests for the application.
 * 
 * This controller is responsible for processing incoming HTTP requests
 * and returning appropriate responses.
 * 
 * @package Clicalmani\Foundation/flesco 
 * @author @Clicalmani\Foundation
 * 
 * @method static never render()
 * @method static mixed invokeMethod(\Clicalmani\Foundation\Http\Controllers\ReflectorInterface $reflector)
 * @method static \Clicalmani\Foundation\Test\Controllers\TestController test(string $action)
 * @method static object getInstance(string $class)
 */
class RequestController extends Facade
{
    /**
	 * Controller middleware
	 * 
	 * @var array<string, string>
	 */
	protected $middleware = [];

    /**
     * Set controller middleware.
     * 
     * @return void
     */
    protected function setMiddleware(string $name, string $pattern) : void
	{
		$this->middleware[$name] = $pattern;
	}

    /**
     * Get controller middleware
     * 
     * @return array
     */
    public function getMiddleware() : array
    {
        return $this->middleware;
    }

    /**
     * Check if the request is authorized based on the provided middleware.
     * 
     * @param string $name_or_class
     * @return int
     * @throws \Clicalmani\Foundation\Exceptions\MiddlewareException
     */
    public function isAuthorized(string $name_or_class) : int
    {
        $middleware = null;

        if ($middleware = ServiceProvider::getProvidedMiddleware(\Clicalmani\Foundation\Routing\Route::gateway(), $name_or_class)) ;
        else {
            if ( class_exists($name_or_class) ) $middleware = $name_or_class;
            else throw new MiddlewareException(
                sprintf("Can not find a global middleware named %s", $name_or_class)
            );
        }
        
        if ( NULL !== $middleware )
            with( new $middleware )->handle(
                Request::currentRequest(),
                new Response,
                fn() => http_response_code()
            );

        $response_code = http_response_code();
        
        if (200 !== $response_code) return $response_code;

        return 200; // Authorized
    }

    /**
     * Handle the response code and send appropriate status.
     * 
     * @param int $response_code
     * @return void
     */
    public function handleResponseCode(int $response_code) : void
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

    /**
     * Send a status response.
     * 
     * @param int $code
     * @param string $status_code
     * @param string $message
     * @return never
     */
    private function sendStatus(int $code, string $status_code, string $message) : never
	{
		if (\Clicalmani\Foundation\Routing\Route::isApi()) response()->status($code, $status_code, $message);
		else response()->send($code);

		exit;
	}
}
