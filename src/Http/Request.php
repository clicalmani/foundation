<?php
namespace Clicalmani\Foundation\Http;

use Clicalmani\Foundation\Auth\EncryptionServiceProvider;
use Clicalmani\Foundation\Collection\Collection;
use Clicalmani\Foundation\Http\Requests\Cookie;
use Clicalmani\Foundation\Http\Requests\HttpOutputStream;
use Clicalmani\Foundation\Http\Requests\HttpRequest;
use Clicalmani\Foundation\Http\Requests\Redirect;
use Clicalmani\Foundation\Http\Requests\RequestInterface;
use Clicalmani\Foundation\Http\Session;
use Clicalmani\Foundation\Providers\AuthServiceProvider;
use Clicalmani\Foundation\Support\Facades\Arr;
use Clicalmani\Psr7\Headers;
use Clicalmani\Psr7\Stream;
use Clicalmani\Psr7\Uri;

class Request extends HttpRequest implements RequestInterface, \ArrayAccess, \JsonSerializable 
{
    use HttpOutputStream;
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
     * @return ?static
     */
    public static function currentRequest(?self $request = null) : ?static
    {
        if ($request) return self::$current_request = $request;
        return self::$current_request;
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
        $this->validator = new \Clicalmani\Validation\Validator;
        
        parent::__construct(
            $this->getMethod(),
            new Uri((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http', $this->getHost()),
            Headers::fromArray( isConsoleMode() ? []: apache_request_headers()),
            $_COOKIE,
            $_SERVER,
            Stream::createFromResource(fopen('php://input', 'r'))
        );
    }

    /**
     * (non-PHPDoc)
     * @override
     */
    public function __get($property)
    {
        $this->validator->sanitize($this->attributes, $this->signatures ?? []);
        return @ $this->attributes[$property];
    }

    /**
     * (non-PHPDoc)
     * @override 
     */
    public function __set($property, $value)
    {
        $this->attributes[$property] = $value;
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
            unset($this->attributes[$property]);
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
        $token = null;
        
        if ($this->hasHeader('X-Inertia')) return true;
        
        if ($this->hasHeader('X-CSRF-Token')) {
            $token = $this->header('X-CSRF-Token');
        } else $token = $this->{'csrf_token'};
        
        return @ $token === csrf_token();
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
            fn(string $hash) => $this->attributes[\Clicalmani\Foundation\Auth\EncryptionServiceProvider::hashParameter()] = $hash
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
            if ( isConsoleMode() ) $user_id = $this->test_user_id;
            
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
        return $this->attributes;
    }

    /**
     * Make request parameters
     * 
     * @param array $params Parameters
     * @return void
     */
    public function make(array $params = []) : void
    {
        $this->attributes = $params;
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
        if ( isConsoleMode() ) return '@';
        return $_SERVER['REQUEST_URI'];
    }

    /**
     * Request full url
     * 
     * @return string
     */
    public function fullUrl() : string
    {
        return rtrim(app()->getUrl(), '/').$this->url();
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
     * Alias of getAttributes
     * 
     * @return array
     */
    public function all(): array
    {
        return $this->getAttributes();
    }

    /**
     * Get the host name from the current request.
     * 
     * @return string
     */
    public function getHost() : string
    {
        return parse_url($this->fullUrl(), PHP_URL_HOST) ?? '';
    }

    /**
     * Get the port from the current request.
     * 
     * @return string
     */
    public function getPort() : string
    {
        return parse_url($this->fullUrl(), PHP_URL_PORT) ?? '80';
    }

    /**
     * Check if the request method matches the given pattern.
     * 
     * @param string $pattern
     * @return bool
     */
    public function isMethod(string $pattern) : bool
    {
        return preg_match("/^$pattern$/", $this->getMethod()) === 1;
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
        return Arr::get($this->attributes, $name, $default);
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
            $this->attributes,
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
            $this->attributes,
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
        $this->attributes = array_merge($this->attributes, $data);
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
                $this->attributes[$key] = $value;
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
        foreach ($this->attributes as $key => $value) $this->session($key, $value);
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
            if (isset($this->attributes[$key])) {
                $this->session($key, $this->attributes[$key]);
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
        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, $keys)) {
                $this->session($key, $value);
            }
        }
    }

    /**
     * Manage the session instance.
     * 
     * @return \Clicalmani\Foundation\Http\Session
     */
    public function session(?string $key = null, ?string $value = null) : Session
    {
        $session_instance = new Session($key, $value);

        if ( isset($key) ) $session_instance->{$key};
        if ( isset($value) && isset($key) ) $session_instance->{$key} = $value;
        return $session_instance;
    }

    /**
     * Check if the request is trustworthy.
     * 
     * @return bool
     */
    public function isTrustworthy() : bool
    {
        if ($trustedIps = array_shift(static::$trustedProxies) AND is_array($trustedIps)) {

            $headers = @static::$trustedProxies[0] ?? [];

            if (array_intersect($this->ips(), $trustedIps)) {

                if ( isset(static::$trustedProxies[0]) && static::HEADER_X_FORWARDED_ALL === static::$trustedProxies[0]) {
                    $headers = [
                        'X-Forwarded-For',
                        'X-Forwarded-Client-IP',
                        'X-Forwarded-Host',
                        'X-Forwarded-Proto',
                        'X-Forwarded-Port',
                    ];
                }

                foreach ($headers as $header) {
                    if (FALSE === $this->hasHeader($header)) return false;
                }

            } else return false;
        }

        return in_array($this->getHost(), static::$trustedHosts);
    }
}