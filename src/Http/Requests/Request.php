<?php
namespace Clicalmani\Foundation\Http\Requests;

use Clicalmani\Foundation\Auth\EncryptionServiceProvider;
use Clicalmani\Foundation\Collection\Collection;
use Clicalmani\Foundation\Providers\AuthServiceProvider;
use Clicalmani\Foundation\Routing\Route;

class Request extends HttpRequest implements RequestInterface, \ArrayAccess, \JsonSerializable 
{
    use HttpInputStream;
    use HttpOutputStream;
    use Session;
    use Cookie;
    use Redirect;

    /**
     * Current request object
     * 
     * @var static
     */
    protected static $current_request;

    /**
     * Validator
     * 
     * @var \Clicalmani\Foundation\Fundation\Validation\Validator
     */
    private $validator;

    /**
     * Get or set the current request
     * 
     * @param ?self $request
     * @return mixed
     */
    public static function currentRequest(?self $request = null) : mixed
    {
        if ($request) return static::$current_request = $request;
        return static::$current_request;
    }

    /**
     * Prepare for validation
     * 
     * (non-PHPDoc)
     * @override
     */
    public function signatures() { /** TODO: override */ }

    /**
     * Prepare for validation
     * 
     * (non-PHPDoc)
     * @override
     */
    public function prepareForValidation() {
        // TODO: override
    }

    /**
     * (non-PHPDoc)
     * @override
     */
    public function authorize()
    {
        return true;
    }

    /**
     * (non-PHPDoc)
     * @override
     */
    public function validate(?array $signatures = []) : void
    {
        $this->merge($signatures);
    }

    /**
     * Constructor
     * 
     * @param ?array $signatures Request signatures
     */
    public function __construct(private ?array $signatures = []) 
    {
        $this->validator = new \Clicalmani\Foundation\Validation\Validator;

        if (Route::isApi() AND in_array(self::getMethod(), ['patch', 'put'])) {
            
            // Parse input stream
            $params = [];
            new \Clicalmani\Foundation\Http\Requests\ParseInputStream($params);
            
            /**
             * Header application/json
             */
            if ( array_key_exists('parameters', $params) ) $params = $params['parameters'];

            $_REQUEST = array_merge($_REQUEST, $params);
        }
    }

    /**
     * (non-PHPDoc)
     * @override
     */
    public function __get($property)
    {
        $this->validator->sanitize($_REQUEST, $this->signatures ?? []);
        
        return @ $_REQUEST[$property];
    }

    /**
     * (non-PHPDoc)
     * @override 
     */
    public function __set($property, $value)
    {
        $_REQUEST[$property] = $value;
    }

    /**
	 * (non-PHPdoc)
	 * Override
	 */
    public function offsetExists(mixed $property) : bool {
        return ! is_null($this->$property);
    }

    /**
	 * (non-PHPdoc)
	 * Override
	 */
    public function offsetGet(mixed $property) : mixed {
        return $this->$property;
    }

    /**
     * (non-PHPDoc)
     * Override
     */
    public function offsetSet(mixed $property, mixed $value) : void {
        $this->$property = $value;
    }

    /**
	 * (non-PHPdoc)
	 * Override
	 */
    public function offsetUnset(mixed $property) : void {
        if ($this->$property) {
            unset($_REQUEST[$property]);
        }
    }

    /**
     * Merge request signatures
     * 
     * @param ?array $new_signatures New signatures to merge into
     * @return void
     */
    public function merge(?array $new_signatures = []) : void
    {
        $this->signatures = array_merge((array) $this->signatures, $new_signatures);
    }

    /**
     * Check CSRF validity by testing the csrf_token parameter's value.
     * 
     * @return bool
     */
    public function checkCSRFToken() : bool
    {
        return @ $this->{'csrf_token'} === csrf_token();
    }

    /**
     * Create request parameters hash
     * 
     * @param array $params
     * @return string
     */
    public function createParametersHash(array $params) : string
    {
        return tap(
            EncryptionServiceProvider::createParametersHash($params), 
            fn(string $hash) => $_REQUEST[\Clicalmani\Foundation\Auth\EncryptionServiceProvider::hashParameter()] = $hash
        );
    }

    /**
     * Verify request parameters validity.
     * 
     * @return bool
     */
    public function verifyParameters() : bool
    {
        return EncryptionServiceProvider::verifyParameters();
    }

    /**
     * Return current request signature
     * 
     * @return mixed
     */
    public static function getCurrentRequest() : mixed
    {
        return static::$current_request;
    }

    /**
     * Return authorization bearer header value
     * 
     * @return string
     */
    public function getToken() : string
    {
        $authorization = $this->header('Authorization');
        
        if ($authorization) {
            return preg_replace('/^(Bearer )/i', '', $authorization);
        }

        return '';
    }

    /**
     * Alias of getToken() method
     * 
     * @return string
     */
    public function bearerToken() : string
    {
        return $this->getToken();
    }

    /**
     * Get authenticated user
     * 
     * @return mixed
     */
    public function user() : mixed
    {
        if ($authenticatorClass = AuthServiceProvider::userAuthenticator()) {
            /** @var \Clicalmani\Foundation\Auth\Authenticate */
            $authenticator = new $authenticatorClass;
            $user_id = $authenticator->getConnectedUserID($this);
            
            /**
             * |----------------------------------------------------
             * | Test User
             * |----------------------------------------------------
             * | To interact with the app as a normal user when testing, a user ID
             * | may be specified.
             */
            if ( inConsoleMode() ) $user_id = $this->test_user_id;
            
            return $authenticator->createUser($user_id);
        }

        return null;
    }

    /**
     * @override
     * @see jsonSerialize()
     */
    public function jsonSerialize() : mixed
    {
        return $_REQUEST;
    }

    /**
     * Make request parameters
     * 
     * @param array $params Parameters
     * @return void
     */
    public function make(array $params = []) : void
    {
        $_REQUEST = $params;
    }

    /**
     * Request parameter value
     * 
     * @param ?string $param Parameter to request the value. If omitted all the parameters will be returned.
     * @return mixed
     */
    public function request(?string $param = null) : mixed
    {
        return isset($param) ? request($param): request();
    }

    /**
     * Associate each request parameter to its value with an egal sign. Useful for filtering.
     * 
     * @param array $exclude List of parameters to exclude
     * @return array
     */
    public function where(?array $exclude = []) : array
    {
        $exclude[] = \Clicalmani\Foundation\Auth\EncryptionServiceProvider::hashParameter(); // Default
        $filters = [];

        if ( request() ) {
            $filters = collection()->exchange(array_keys(request()))
                            ->filter(function($param) use($exclude) {
                                return ! in_array($param, $exclude);
                            })->map(function($param) {
                                return is_string(request($param)) ? sanitize_attribute($param) . '="' . request($param) . '"': request($param);
                            })->toArray();
        }
        
        return $filters;
    }

    /**
     * Route request
     * 
     * @return \Clicalmani\Routing\Route|null
     */
    public function route() : \Clicalmani\Routing\Route|null
    {
        return \Clicalmani\Foundation\Routing\Route::current();
    }

    /**
     * Request URL
     * 
     * @return string
     */
    public function url() : string
    {
        return $this->route()?->uri;
    }

    /**
     * Request full url
     * 
     * @return string
     */
    public function fullUrl() : string
    {
        return rtrim(app()->getUrl(), '/').$_SERVER['REQUEST_URI'];
    }

    /**
     * Get the full URL with query parameters
     * 
     * @param ?array $query_parameters
     * @return string
     */
    public function fullUrlWithQuery(?array $query_parameters = []) : string
    {
        $url = $this->fullUrl();
        $parsed_url = parse_url($url);
        $url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'];
        parse_str($parsed_url['query'], $output);

        if (!empty($query_parameters)) {
            $output = array_merge($output, $query_parameters);
        }

        $query_string = http_build_query($output);
        $url .= '?' . $query_string;

        return $url;
    }

    /**
     * Parse the full URL and remove specified query parameters.
     * Otherwise all query parameters will be removed.
     * 
     * @param ?array $query_parameters
     * @return string
     */
    public function fullUrlWithoutQuery(?array $query_parameters = []) : string
    {
        $url = $this->fullUrl();
        $parsed_url = parse_url($url);
        $url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'];
        $query_string = '';

        if (!empty($query_parameters)) {
            parse_str($parsed_url['query'], $output);
            $query_string = http_build_query(array_diff(array_keys($output), $query_parameters));
        }

        return $url.($query_string ? "?$query_string": $query_string);
    }

    /**
     * Get the host name from the current request.
     * 
     * @return string
     */
    public function getHost() : string
    {
        return parse_url($this->fullUrl(), PHP_URL_HOST);
    }

    /**
     * Check if the request method matches the given pattern.
     * 
     * @param string $pattern
     * @return bool
     */
    public function isMethod(string $pattern) : bool
    {
        return preg_match($pattern, $this->getMethod()) === 1;
    }

    /**
     * Get the value of a specific input parameter.
     * 
     * @param ?string $name The name of the input parameter.
     * @param ?string $default The default value to return if the parameter is not found.
     * @return mixed
     */
    public function input(?string $name = null, ?string $default = null) : mixed
    {
        if (NULL === $name) return $this->all();

        $arr = preg_split('/\./', $name, -1, PREG_SPLIT_NO_EMPTY);
        
        return match (count($arr)) {
            1 => $this->{$name} ?? $default,
            2 => is_numeric($arr[1]) ? $this->collect($arr[0])->get($arr[1]) ?? $default: $this->collect($arr[0])->map(fn(array $v) => @$v[$arr[1]]),
            3 => $this->collect($arr[0])
                    ->filter(fn($v, $k) => ($arr[1] === '*' ? $v: ($k === (int)$arr[1])))
                    ->pluck($arr[2])
                    ->map(fn(\stdClass $obj) => $obj->value ?? $default)
                    ->toArray(),
            default => null
        };
    }

    /**
     * Collect input data as an array.
     * 
     * @param string $name The name of the input parameter.
     * @return \Clicalmani\Foundation\Collection\Collection
     */
    public function collect(string $name) : Collection
    {
        $value = $this->input($name);
        return is_array($value) ? collection($value) : collection([$value]);
    }

    /**
     * Get the value of a specific query parameter.
     * 
     * @param string $name The name of the query parameter.
     * @return mixed
     */
    public function query(string $name) : mixed
    {
        return $_GET[$name] ?? null;
    }

    /**
     * Get the request data as a JSON collection.
     * 
     * @return \Clicalmani\Foundation\Collection\Collection
     */
    public function json() : Collection
    {
        return new Collection(json_decode(file_get_contents('php://input'), true));
    }

    /**
     * Check if the request has a specific parameter.
     * 
     * @param string|array $name The name of the parameter.
     * @return bool
     */
    public function has(string|array $name) : bool
    {
        if (is_string($name)) return !!$this->{$name};

        foreach ($name as $n) {
            if (!$this->has($n)) return false;
        }

        return true;
    }

    /**
     * Check if the request has any of the specified parameters.
     * 
     * @param array $names The names of the parameters.
     * @return bool
     */
    public function hasAny(array $names) : bool
    {
        foreach ($names as $name) {
            if ($this->has($name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Execute a callback if the request has a specific parameter.
     * 
     * @param string|array $name The name of the parameter.
     * @param callable $callback The callback to execute if success.
     * @param callable $callback2 The callback to execute if failure.
     * @return mixed
     */
    public function whenHas(string|array $name, callable $callback, ?callable $callback2) : mixed
    {
        if ($this->has($name)) {
            return $callback($this);
        }

        if ($callback2) return $callback2($this);

        return null;
    }

    /**
     * Get only the specified parameters from the request.
     * 
     * @param array $keys The keys of the parameters to retrieve.
     * @return array
     */
    public function only(array $keys) : array
    {
        return array_filter(
            $_REQUEST,
            fn($key) => in_array($key, $keys),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Get all parameters except the specified ones.
     * 
     * @param array $keys The keys of the parameters to exclude.
     * @return array
     */
    public function except(array $keys) : array
    {
        return array_filter(
            $_REQUEST,
            fn($key) => !in_array($key, $keys),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Check if the request has a specific parameter and it is not empty.
     * 
     * @param string|array $name The name of the parameter.
     * @return bool
     */
    public function filled(string|array $name) : bool
    {
        if (is_string($name)) return !empty($this->{$name});

        foreach ($name as $n) {
            if (!$this->filled($n)) return false;
        }

        return true;
    }

    /**
     * Check if the request does not have a specific parameter or it is empty.
     * 
     * @param string|array $name The name of the parameter.
     * @return bool
     */
    public function isNotFilled(string|array $name) : bool
    {
        if (is_string($name)) return empty($this->{$name});

        foreach ($name as $n) {
            if (!$this->isNotFilled($n)) return false;
        }

        return true;
    }

    /**
     * Check if any of the specified parameters are filled.
     * 
     * @param array $names The names of the parameters.
     * @return bool
     */
    public function anyFilled(array $names) : bool
    {
        foreach ($names as $name) {
            if ($this->filled($name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Execute a callback if the request has a specific parameter and it is not empty.
     * 
     * @param string|array $name The name of the parameter.
     * @param callable $callback The callback to execute if success.
     * @param callable|null $callback2 The callback to execute if failure.
     * @return mixed
     */
    public function whenFilled(string|array $name, callable $callback, ?callable $callback2 = null) : mixed
    {
        if ($this->filled($name)) {
            return $callback($this);
        }

        if ($callback2) {
            return $callback2($this);
        }

        return null;
    }

    /**
     * Check if the request is missing a specific parameter.
     * 
     * @param string|array $name The name of the parameter.
     * @return bool
     */
    public function missing(string|array $name) : bool
    {
        return !$this->has($name);
    }

    /**
     * Execute a callback if the request is missing a specific parameter.
     * 
     * @param string|array $name The name of the parameter.
     * @param callable $callback The callback to execute if success.
     * @param callable|null $callback2 The callback to execute if failure.
     * @return mixed
     */
    public function whenMissing(string|array $name, callable $callback, ?callable $callback2 = null) : mixed
    {
        if ($this->missing($name)) {
            return $callback($this);
        }

        if ($callback2) {
            return $callback2($this);
        }

        return null;
    }

    /**
     * Extend the request with additional data.
     * 
     * @param array $data The data to extend the request with.
     * @return void
     */
    public function extend(array $data) : void
    {
        $_REQUEST = array_merge($_REQUEST, $data);
    }

    /**
     * Extend the request with additional data if the keys are missing.
     * 
     * @param array $data The data to extend the request with.
     * @return void
     */
    public function extendIfMissing(array $data) : void
    {
        foreach ($data as $key => $value) {
            if ($this->missing($key)) {
                $_REQUEST[$key] = $value;
            }
        }
    }

    /**
     * Flush all request data to the session.
     * 
     * @return void
     */
    public function flush() : void
    {
        foreach ($_REQUEST as $key => $value) $this->session($key, $value);
    }

    /**
     * Flush only the specified request data to the session.
     * 
     * @param array $keys The keys of the parameters to flush.
     * @return void
     */
    public function flushOnly(array $keys) : void
    {
        foreach ($keys as $key) {
            if (isset($_REQUEST[$key])) {
                $this->session($key, $_REQUEST[$key]);
            }
        }
    }

    /**
     * Flush all request data to the session except the specified keys.
     * 
     * @param array $keys The keys of the parameters to exclude.
     * @return void
     */
    public function flushExcept(array $keys) : void
    {
        foreach ($_REQUEST as $key => $value) {
            if (!in_array($key, $keys)) {
                $this->session($key, $value);
            }
        }
    }

    /**
     * Manage the session instance.
     * 
     * @return mixed
     */
    public function session(?string $key = null, ?string $value = null) : mixed
    {
        $session_instance = app()->session();

        if ( isset($key) ) return $session_instance->{$key};
        if ( isset($value) && isset($key) ) return $session_instance->{$key} = $value;
        return $session_instance;
    }

    /**
     * Redirect back with input data.
     * 
     * @param string $url The URL to redirect to.
     * @return void
     */
    public function redirectWithInput(string $url) : void
    {
        $this->session()->flash('_old_input', $_REQUEST);
        $this->redirect($url);
    }
}