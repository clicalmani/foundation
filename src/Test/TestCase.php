<?php 
namespace Clicalmani\Core\Test;

use Clicalmani\Database\Factory\Sequence;
use Clicalmani\Core\Auth\EncryptionServiceProvider;
use Clicalmani\Core\Http\Controllers\FunctionReflector;
use Clicalmani\Core\Http\Controllers\MethodReflector;
use Clicalmani\Core\Http\Request;
use Clicalmani\Core\Test\TestInterface;
use Clicalmani\Core\Maker\Application;
use Clicalmani\Core\Support\Facades\DB;
use Clicalmani\Core\Support\Facades\Config;
use Clicalmani\Routing\Registry;
use Clicalmani\Validation\AsValidator;
use Clicalmani\Validation\Validator;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * TestController
 * 
 * This file is part of the Tonka Framework.
 * 
 * @package    clicalmani/tonka
 * @subpackage Test\Controllers
 * @author     clicalmani <clicalmani@gmail.com>
 * @license    MIT License
 * @link       https://github.com/clicalmani/foundation
 */
abstract class TestCase extends BaseTestCase implements TestInterface
{
    /**
     * Holds the pending request headers.
     *
     * @var array
     */
    protected array $httpHeaders = [];

    /**
     * Holds the pending session data.
     *
     * @var array
     */
    protected array $httpSession = [];

    /**
     * The fake user ID for the next request.
     *
     * @var int|null
     */
    protected ?int $httpUserId = null;

    /**
     * Request controller
     * 
     * @var \Clicalmani\Core\Http\RequestController
     */
    protected $controller;

    /**
     * Faker instance
     * 
     * @var \Clicalmani\Database\Faker\Faker
     */
    protected \Clicalmani\Database\Faker\Faker $faker;

    protected $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->faker = new \Clicalmani\Database\Faker\Faker;
        $this->request = Request::current() ?? new Request;
        Request::current($this->request);
    }

    protected function callDriftQL(
        ?string $action = null,
        ?array $params = [],
        ?string $method = 'POST'
    ): array {
        $hash = sha1($action);
        $config = app()->config->get();
        $url = $config['driftql']['bridge_public_key'];
        
        if (isset($action)) {
            $url .= '/' . sha1($action);
        }
        
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $url;
        
        $routeBuilderClass = $config['route']['default_builder'];
        
        $routeBuilder = new $routeBuilderClass;

        if ($route = $routeBuilder->build()) {
            $controller = $route->action[0];
            $action = $route->action[1];
            
            $request = new Request;
            $request->make(array_merge($params, $this->parameters));
            Request::current($request);
            
            if ($attribute = (new \ReflectionMethod($controller, $action))->getAttributes(AsValidator::class)) {
                $signatures = $attribute[0]->newInstance()->args;
                $request->merge($signatures);
                $validator = Validator::make(
                    $signatures,
                    $params
                );
                
                if ($validator->hasErrors()) {
                    throw new \Exception("Validation failed: " . json_encode($validator->errors()));
                }
            }
            
            $response = call_user_func(new $controller, $request);
            
            return json_decode($response->getBody()->getContents(), true);
        }

        return [];
    }

    /**
     * Provides a user for the test.
     * 
     * @param int|\Clicalmani\Database\Factory\Sequence $param
     * @return void
     */
    public function actingAs(int $user_id) : static
    {
        $this->request->test_user_id = $user_id;
        return $this;
    }

    /**
     * Update start
     */

    /**
     * Set custom headers for the next request.
     */
    public function withHeaders(array $headers): static
    {
        $this->httpHeaders = array_merge($this->httpHeaders, $headers);
        return $this;
    }

    /**
     * Set the Authorization header using a bearer token.
     */
    public function withToken(string $token): static
    {
        return $this->withHeaders(['Authorization' => "Bearer {$token}"]);
    }

    /**
     * Set session data for the next request.
     */
    public function withSession(array $session): static
    {
        $this->httpSession = array_merge($this->httpSession, $session);
        return $this;
    }

    /**
     * Simulate an authenticated user for the next request.
     */
    public function actingAsUser(int $userId): static
    {
        $this->httpUserId = $userId;
        return $this;
    }

    // ---------------------------------------------------------------
    // HTTP verb shortcuts
    // ---------------------------------------------------------------

    public function get(string $uri, array $headers = []): TestResponse
    {
        return $this->call('GET', $uri, [], $headers);
    }

    public function getJson(string $uri, array $headers = []): TestResponse
    {
        return $this->json('GET', $uri, [], $headers);
    }

    public function post(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->call('POST', $uri, $data, $headers);
    }

    public function postJson(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->json('POST', $uri, $data, $headers);
    }

    public function put(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->call('PUT', $uri, $data, $headers);
    }

    public function putJson(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->json('PUT', $uri, $data, $headers);
    }

    public function patch(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->call('PATCH', $uri, $data, $headers);
    }

    public function patchJson(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->json('PATCH', $uri, $data, $headers);
    }

    public function delete(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->call('DELETE', $uri, $data, $headers);
    }

    public function deleteJson(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->json('DELETE', $uri, $data, $headers);
    }

    // ---------------------------------------------------------------
    // Core request dispatcher
    // ---------------------------------------------------------------

    /**
     * Dispatch an HTTP request through the application's router
     * and return a testable response.
     */
    protected function call(string $method, string $uri, array $data = [], array $headers = []): TestResponse
    {
        $request = $this->buildHttpRequest($method, $uri, $data, $headers);
        
        Request::current($request);

        $controller = container()->getClassIntance($this->controller);
        $action = $controller->getAction();

		if (is_array($action) AND count($action) === 2) {
			$reflector = new MethodReflector(new \ReflectionMethod(container()->getClassIntance($action[0]), $action[1]));
		} elseif( is_string($action) ) {
			$reflector = new MethodReflector(new \ReflectionMethod(container()->getClassIntance($action), '__invoke'));
		} elseif (is_callable($action)) {
			$reflector = new FunctionReflector(new \ReflectionFunction($action));
		}

        $route = Registry::currentRoute();

        $response = $controller->fakeCall($route, $reflector, $request);
        
        return new TestResponse($response);
    }

    /**
     * Build a Request instance from the given parameters.
     */
    protected function buildHttpRequest(
        string $method,
        string $uri,
        array $data = [],
        array $headers = []
    ): Request {
        $request = new Request;

        // Simulate the server 
        $_SERVER['REQUEST_METHOD'] = strtoupper($method);
        $_SERVER['REQUEST_URI']    = $uri;
        $_SERVER['HTTP_HOST']      = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // Inject headers
        foreach (array_merge($this->httpHeaders, $headers) as $name => $value) {
            $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
            $_SERVER[$key] = $value;
        }

        // Inject body data
        $request->make($data);
        
        // Inject session
        if (!empty($this->httpSession)) {
            $request->session = $this->httpSession;
        }

        // Inject fake authenticated user
        if ($this->httpUserId !== null) {
            $request->test_user_id = $this->httpUserId;
        }

        return $request;
    }

    /**
     * Same as call(), but sets JSON headers and serializes the body as JSON.
     */
    protected function json(string $method, string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->call($method, $uri, $data, array_merge([
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ], $headers));
    }
}
