<?php
namespace Clicalmani\Foundation\Http;

use Clicalmani\Foundation\Auth\EncryptionServiceProvider;
use Clicalmani\Foundation\Collection\Collection;
use Clicalmani\Foundation\Collection\CollectionInterface;
use Clicalmani\Foundation\Http\Requests\Cookie;
use Clicalmani\Foundation\Http\Requests\HttpOutputStream;
use Clicalmani\Foundation\Http\Requests\HttpRequest;
use Clicalmani\Foundation\Http\Requests\Redirect;
use Clicalmani\Foundation\Http\RequestInterface;
use Clicalmani\Foundation\Http\Session;
use Clicalmani\Foundation\Providers\AuthServiceProvider;
use Clicalmani\Foundation\Support\Facades\Arr;
use Clicalmani\Psr\Headers;
use Clicalmani\Psr\Stream;
use Clicalmani\Psr\Uri;

class Request extends HttpRequest implements RequestInterface, \ArrayAccess, \JsonSerializable 
{
    use HttpOutputStream;
    use Cookie;
    use Redirect;

    /**
     * Current request object
     * 
     * @var \Clicalmani\Foundation\Http\RequestInterface
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
     * @param ?\Clicalmani\Foundation\Http\RequestInterface $request
     * @return ?\Clicalmani\Foundation\Http\RequestInterface
     */
    public static function current(?RequestInterface $request = null) : ?\Clicalmani\Foundation\Http\RequestInterface
    {
        if ($request) return self::$current_request = $request;
        return self::$current_request;
    }

    public function signatures() : void { /** TODO: override */ }

    public function prepareForValidation() : void {
        // TODO: override
    }

    public function authorize() : bool
    {
        return true;
    }

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

    public function createParametersHash(array $params) : string
    {
        return tap(
            EncryptionServiceProvider::createParametersHash($params), 
            fn(string $hash) => $this->attributes[\Clicalmani\Foundation\Auth\EncryptionServiceProvider::hashParameter()] = $hash
        );
    }

    public function verifyParameters() : bool
    {
        return EncryptionServiceProvider::verifyParameters();
    }

    public static function getcurrent() : mixed
    {
        return static::$current_request;
    }

    public function getToken() : string
    {
        $authorization = $this->header('Authorization');
        
        if ($authorization) {
            return preg_replace('/^(Bearer )/i', '', $authorization);
        }

        return '';
    }

    public function bearerToken() : string
    {
        return $this->getToken();
    }

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

    public function jsonSerialize() : mixed
    {
        return $this->attributes;
    }

    public function make(array $params = []) : void
    {
        $this->attributes = $params;
    }

    public function request(?string $param = null) : mixed
    {
        return isset($param) ? request($param): request();
    }

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

    public function serialize(?array $exclude = []) : string
    {
        return join('&', $this->where($exclude));
    }

    public function route() : ?\Clicalmani\Routing\Route
    {
        return \Clicalmani\Foundation\Support\Facades\Route::current();
    }

    public function url() : string
    {
        if ( isConsoleMode() ) return '@';
        return $_SERVER['REQUEST_URI'];
    }

    public function fullUrl() : string
    {
        return rtrim(app()->getUrl(), '/').$this->url();
    }

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

    public function all(): array
    {
        return $this->getAttributes();
    }

    public function getHost() : string
    {
        return parse_url($this->fullUrl(), PHP_URL_HOST) ?? '';
    }

    public function getPort() : string
    {
        return parse_url($this->fullUrl(), PHP_URL_PORT) ?? '80';
    }

    public function isMethod(string $pattern) : bool
    {
        return preg_match("/^$pattern$/", $this->getMethod()) === 1;
    }

    public function input(?string $name = null, ?string $default = null) : mixed
    {
        return Arr::get($this->attributes, $name, $default);
    }

    public function collect(string $name) : CollectionInterface
    {
        $value = $this->input($name);
        return is_array($value) ? collection($value) : collection([$value]);
    }

    public function query(string $name) : mixed
    {
        return $_GET[$name] ?? null;
    }

    public function json() : Collection
    {
        return new Collection(json_decode(file_get_contents('php://input'), true));
    }

    public function has(string|array $name) : bool
    {
        if (is_string($name)) return !!$this->{$name};

        foreach ($name as $n) {
            if (!$this->has($n)) return false;
        }

        return true;
    }

    public function hasAny(array $names) : bool
    {
        foreach ($names as $name) {
            if ($this->has($name)) {
                return true;
            }
        }

        return false;
    }

    public function whenHas(string|array $name, callable $callback, ?callable $callback2) : mixed
    {
        if ($this->has($name)) {
            return $callback($this);
        }

        if ($callback2) return $callback2($this);

        return null;
    }

    public function only(array $keys) : array
    {
        return array_filter(
            $this->attributes,
            fn($key) => in_array($key, $keys),
            ARRAY_FILTER_USE_KEY
        );
    }

    public function except(array $keys) : array
    {
        return array_filter(
            $this->attributes,
            fn($key) => !in_array($key, $keys),
            ARRAY_FILTER_USE_KEY
        );
    }

    public function filled(string|array $name) : bool
    {
        if (is_string($name)) return !empty($this->{$name});

        foreach ($name as $n) {
            if (!$this->filled($n)) return false;
        }

        return true;
    }

    public function isNotFilled(string|array $name) : bool
    {
        if (is_string($name)) return empty($this->{$name});

        foreach ($name as $n) {
            if (!$this->isNotFilled($n)) return false;
        }

        return true;
    }

    public function anyFilled(array $names) : bool
    {
        foreach ($names as $name) {
            if ($this->filled($name)) {
                return true;
            }
        }

        return false;
    }

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

    public function missing(string|array $name) : bool
    {
        return !$this->has($name);
    }

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

    public function extend(array $data) : void
    {
        $this->attributes = array_merge($this->attributes, $data);
    }

    public function extendIfMissing(array $data) : void
    {
        foreach ($data as $key => $value) {
            if ($this->missing($key)) {
                $this->attributes[$key] = $value;
            }
        }
    }

    public function flush() : void
    {
        foreach ($this->attributes as $key => $value) $this->session($key, $value)->set();
    }

    public function flushOnly(array $keys) : void
    {
        foreach ($keys as $key) {
            if (isset($this->attributes[$key])) {
                $this->session($key, $this->attributes[$key])->set();
            }
        }
    }

    public function flushExcept(array $keys) : void
    {
        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, $keys)) {
                $this->session($key, $value);
            }
        }
    }

    public function session(?string $key = null, ?string $value = null) : \Clicalmani\Foundation\Http\Session\SessionInterface
    {
        $session_instance = new Session($key, $value);

        if ( isset($key) ) $session_instance->{$key};
        if ( isset($value) && isset($key) ) $session_instance->{$key} = $value;
        return $session_instance;
    }

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

    public function getSignatures() : array
    {
        return $this->signatures;
    }
}