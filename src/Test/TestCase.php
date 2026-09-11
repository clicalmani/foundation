<?php 
namespace Clicalmani\Foundation\Test;

use Clicalmani\Database\Factory\Sequence;
use Clicalmani\Foundation\Auth\EncryptionServiceProvider;
use Clicalmani\Foundation\Http\Request;
use Clicalmani\Foundation\Test\TestInterface;
use Clicalmani\Foundation\Maker\Application;
use Clicalmani\Foundation\Support\Facades\DB;
use Clicalmani\Foundation\Support\Facades\Config;
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
     * Request controller
     * 
     * @var \Clicalmani\Foundation\Http\RequestController
     */
    protected $controller;

    /**
     * Holds the current action to be tested.
     * 
     * @var string
     */
    private $action;

    private Request $request;

    /**
     * Holds the request parameters to be used for the test.
     * 
     * @var array
     */
    protected $parameters = [];

    /**
     * Holds the fake test user if test should
     * be driven in a user environment.
     * 
     * @var int
     */
    private $user;

    /**
     * Holds the number of time to repeat the test.
     * 
     * @var int
     */
    private $counter = 1;
    
    /**
     * Holds parameters hash.
     * 
     * @var string|\Clicalmani\Database\Factory\Sequence
     */
    private $hash;

    /**
     * Holds the request headers.
     * 
     * @var array|\Clicalmani\Database\Factory\Sequence
     */
    private $headers;

    /**
     * Faker instance
     * 
     * @var \Clicalmani\Database\Faker\Faker
     */
    protected \Clicalmani\Database\Faker\Faker $faker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->faker = new \Clicalmani\Database\Faker\Faker;
        $this->request = Request::current() ?? new Request;
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
     * Merges parameters
     * 
     * @param ?array $parameters
     * @return array 
     */
    private function merge(?array $parameters = []) : array
    {
        return array_merge($this->parameters, $parameters);
    }

    /**
     * Override parameters
     * 
     * @param array $parameters Only specified parameters will be overriden
     * @return array New seed
     */
    private function override(?array $parameters = [])
    {
        $this->parameters = $this->merge($parameters);
        $parameters = $this->{$this->action}();
        
        foreach ($this->parameters as $key => $value) {
            $parameters[$key] = $value;
        }

        return $parameters;
    }

    /**
     * Set request hash
     * 
     * @return void
     */
    private function setHash() : void
    {
        $hash_parameter = EncryptionServiceProvider::hashParameter();
        if ($this->hash instanceof Sequence) {
            $this->override( [$hash_parameter => create_parameters_hash( call($this->hash) )]);
        } else $this->override( [$hash_parameter => $this->hash] );
    }

    /**
     * Set request headers
     * 
     * @return void
     */
    private function setHeaders() : void
    {
        if ($this->headers instanceof Sequence) $this->override( call($this->headers) );
    }

    /**
     * Create a new test
     * 
     * @param string $action Action method
     * @return static
     */
    public function new(string $action) : static
    {
        $this->action = $action;
        return $this;
    }

    /**
     * Manipulate the factory state
     * 
     * @param callable $callback A callable function that receive default attributes and return the 
     * attributes to override by.
     * @return static
     */
    public function state(?callable $callback) : static
    {
        $this->override( $callback( $this->{$this->action}() ) );
        return $this;
    }

    /**
     * Provides a user for the test.
     * 
     * @param int|\Clicalmani\Database\Factory\Sequence $param
     * @return void
     */
    public function actingAs(int $user_id) : void
    {
        $this->request->test_user_id = $user_id;
    }

    /**
     * Repeat the test n times.
     * 
     * @param int $n Counter
     * @return static
     */
    public function repeat($n = 1) : static
    {
        $this->counter = $n;
        return $this;
    }

    /**
     * Make the test
     * 
     * @return void
     */
    public function make($attributes = []) : void
    {
        foreach (range(1, $this->counter) as $num) {

            $request = new Request;

            /**
             * Request hash
             */
            if ($this->hash) $this->setHash();

            /**
             * Headers
             */
            if ($this->headers) $this->setHeaders();
            
            $parameters = $this->override($attributes);
            
            /**
             * Parameter sequence
             */
            foreach ($parameters as $key => $param) {
                if ($param instanceof Sequence) $parameters[$key] = call( $param );
            }

            $request->make( $attributes );
            Request::current($request);
            
            /**
             * User sequence
             */
            if ($this->user) {
                if ($this->user instanceof Sequence) {
                    $request->test_user_id = call( $this->user );
                } else $request->test_user_id = $this->user;
            }
            
            print_r( $this->controller::invokeMethod(
                    new \Clicalmani\Foundation\Http\Controllers\MethodReflector(
                        new \ReflectionMethod(
                            \Clicalmani\Foundation\Support\Facades\RequestController::getInstance($this->controller), 
                            $this->action
                        )
                    )
                ) 
            );

            if ($num < $this->counter) echo "\n";
        }
    }

    /**
     * Provides a hash parameter for the request.
     * 
     * @param array|\Clicalmani\Database\Factory\Sequence $parameters
     * @return static
     */
    public function hash(array|Sequence $parameters) : static
    {
        if ( is_array($parameters) ) $this->hash = with( new Request )->createParametersHash($parameters);
        elseif ( $parameters instanceof Sequence ) $this->hash = $parameters;
        return $this;
    }

    /**
     * Set a request header.
     * 
     * @param string $name
     * @param string $value
     * @return static
     */
    public function header(string $name, string $value) : static
    {
        $this->override( [$name => $value] );
        return $this;
    }

    /**
     * Set request headers
     * 
     * @param array|\Clicalmani\Database\Factory\Sequence $headers
     * @return static
     */
    public function headers(Sequence|array $headers) : static
    {
        if ( is_array($headers) ) $this->override( $headers );
        elseif ( $headers instanceof Sequence ) $this->headers = $headers;
        return $this;
    }
}
