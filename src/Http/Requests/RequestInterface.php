<?php
namespace Clicalmani\Foundation\Http\Requests;

interface RequestInterface extends \Psr\Http\Message\ServerRequestInterface
{
    /**
     * Get or set the current request
     * 
     * @param ?\Clicalmani\Foundation\Http\Requests\RequestInterface $request
     * @return ?\Clicalmani\Foundation\Http\Requests\RequestInterface
     */
    public static function current(?RequestInterface $request = null) : ?\Clicalmani\Foundation\Http\Requests\RequestInterface;

    /**
     * (non-PHPDoc)
     * @override
     * 
     * Request signatures
     * @return void
     */
    public function signatures() : void;

    /**
     * (non-PHPDoc)
     * @override
     * 
     * Validate
     * @return void
     */
    public function validate() : void;

    /**
     * (non-PHPDoc)
     * @override
     * 
     * Prepare for validation
     * @return void
     */
    public function prepareForValidation() : void;

    /**
     * (non-PHPDoc)
     * @override
     * 
     * Authorize
     * @return bool
     */
    public function authorize() : bool;

    /**
     * Create request parameters hash
     * 
     * @param array $params
     * @return string
     */
    public function createParametersHash(array $params) : string;

    /**
     * Verify request parameters validity.
     * 
     * @return bool
     */
    public function verifyParameters() : bool;

    /**
     * Return authorization bearer header value
     * 
     * @return string
     */
    public function getToken() : string;

    /**
     * Alias of getToken() method
     * 
     * @return string
     */
    public function bearerToken() : string;

    /**
     * Get authenticated user
     * 
     * @return mixed
     */
    public function user() : mixed;

    /**
     * Request parameter value
     * 
     * @param ?string $param Parameter to request the value. If omitted all the parameters will be returned.
     * @return mixed
     */
    public function request(?string $param = null) : mixed;

    /**
     * Associate each request parameter to its value with an egal sign. Useful for filtering.
     * 
     * @param array $exclude List of parameters to exclude
     * @return array
     */
    public function where(?array $exclude = []) : array;

    /**
     * Serialize.
     * 
     * @deprecated
     * @param array $exclude List of parameters to exclude
     * @return string
     */
    public function serialize(?array $exclude = []) : string;

    /**
     * Route request
     * 
     * @return ?\Clicalmani\Routing\Route
     */
    public function route() : ?\Clicalmani\Routing\Route;

    /**
     * Request URL
     * 
     * @return string
     */
    public function url() : string;

    /**
     * Request full url
     * 
     * @return string
     */
    public function fullUrl() : string;

    /**
     * Get the full URL with query parameters
     * 
     * @param ?array $query_parameters
     * @return string
     */
    public function fullUrlWithQuery(?array $query_parameters = []) : string;

    /**
     * Parse the full URL and remove specified query parameters.
     * Otherwise all query parameters will be removed.
     * 
     * @param ?array $query_parameters
     * @return string
     */
    public function fullUrlWithoutQuery(?array $query_parameters = []) : string;

    /**
     * Get all attributes
     * 
     * @return array
     */
    public function all(): array;

    /**
     * Get the host name from the current request.
     * 
     * @return string
     */
    public function getHost() : string;

    /**
     * Get the port from the current request.
     * 
     * @return string
     */
    public function getPort() : string;

    /**
     * Check if the request method matches the given pattern.
     * 
     * @param string $pattern
     * @return bool
     */
    public function isMethod(string $pattern) : bool;

    /**
     * Get the value of a specific input parameter.
     * 
     * @param ?string $name The name of the input parameter.
     * @param ?string $default The default value to return if the parameter is not found.
     * @return mixed
     */
    public function input(?string $name = null, ?string $default = null) : mixed;

    /**
     * Collect input data as an array.
     * 
     * @param string $name The name of the input parameter.
     * @return \Clicalmani\Foundation\Collection\CollectionInterface
     */
    public function collect(string $name) : \Clicalmani\Foundation\Collection\CollectionInterface;

    /**
     * Get the value of a specific query parameter.
     * 
     * @param string $name The name of the query parameter.
     * @return mixed
     */
    public function query(string $name) : mixed;

    /**
     * Get the request data as a JSON collection.
     * 
     * @return \Clicalmani\Foundation\Collection\CollectionInterface
     */
    public function json() : \Clicalmani\Foundation\Collection\CollectionInterface;

    /**
     * Check if the request has a specific parameter.
     * 
     * @param string|array $name The name of the parameter.
     * @return bool
     */
    public function has(string|array $name) : bool;

    /**
     * Check if the request has any of the specified parameters.
     * 
     * @param array $names The names of the parameters.
     * @return bool
     */
    public function hasAny(array $names) : bool;

    /**
     * Execute a callback if the request has a specific parameter.
     * 
     * @param string|array $name The name of the parameter.
     * @param callable $callback The callback to execute if success.
     * @param callable $callback2 The callback to execute if failure.
     * @return mixed
     */
    public function whenHas(string|array $name, callable $callback, ?callable $callback2) : mixed;

    /**
     * Get only the specified parameters from the request.
     * 
     * @param array $keys The keys of the parameters to retrieve.
     * @return array
     */
    public function only(array $keys) : array;

    /**
     * Get all parameters except the specified ones.
     * 
     * @param array $keys The keys of the parameters to exclude.
     * @return array
     */
    public function except(array $keys) : array;

    /**
     * Check if the request has a specific parameter and it is not empty.
     * 
     * @param string|array $name The name of the parameter.
     * @return bool
     */
    public function filled(string|array $name) : bool;

    /**
     * Check if the request does not have a specific parameter or it is empty.
     * 
     * @param string|array $name The name of the parameter.
     * @return bool
     */
    public function isNotFilled(string|array $name) : bool;

    /**
     * Check if any of the specified parameters are filled.
     * 
     * @param array $names The names of the parameters.
     * @return bool
     */
    public function anyFilled(array $names) : bool;

    /**
     * Execute a callback if the request has a specific parameter and it is not empty.
     * 
     * @param string|array $name The name of the parameter.
     * @param callable $callback The callback to execute if success.
     * @param callable|null $callback2 The callback to execute if failure.
     * @return mixed
     */
    public function whenFilled(string|array $name, callable $callback, ?callable $callback2 = null) : mixed;

    /**
     * Check if the request is missing a specific parameter.
     * 
     * @param string|array $name The name of the parameter.
     * @return bool
     */
    public function missing(string|array $name) : bool;

    /**
     * Execute a callback if the request is missing a specific parameter.
     * 
     * @param string|array $name The name of the parameter.
     * @param callable $callback The callback to execute if success.
     * @param callable|null $callback2 The callback to execute if failure.
     * @return mixed
     */
    public function whenMissing(string|array $name, callable $callback, ?callable $callback2 = null) : mixed;

    /**
     * Extend the request with additional data.
     * 
     * @param array $data The data to extend the request with.
     * @return void
     */
    public function extend(array $data) : void;

    /**
     * Extend the request with additional data if the keys are missing.
     * 
     * @param array $data The data to extend the request with.
     * @return void
     */
    public function extendIfMissing(array $data) : void;

    /**
     * Flush all request data to the session.
     * 
     * @return void
     */
    public function flush() : void;

    /**
     * Flush only the specified request data to the session.
     * 
     * @param array $keys The keys of the parameters to flush.
     * @return void
     */
    public function flushOnly(array $keys) : void;

    /**
     * Flush all request data to the session except the specified keys.
     * 
     * @param array $keys The keys of the parameters to exclude.
     * @return void
     */
    public function flushExcept(array $keys) : void;

    /**
     * Manage the session instance.
     * 
     * @return \Clicalmani\Foundation\Http\Session\SessionInterface
     */
    public function session(?string $key = null, ?string $value = null) : \Clicalmani\Foundation\Http\Session\SessionInterface;

    /**
     * Check if the request is trustworthy.
     * 
     * @return bool
     */
    public function isTrustworthy() : bool;

    /**
     * Gest request signatures
     * 
     * @return array
     */
    public function getSignatures() : array;
}