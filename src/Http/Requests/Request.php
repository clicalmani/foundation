<?php
namespace Clicalmani\Foundation\Http\Requests;

use Clicalmani\Foundation\Auth\EncryptionServiceProvider;
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
}
