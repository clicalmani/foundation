<?php
namespace Clicalmani\Foundation\Http\Requests;

/**
 * Handles the HTTP request.
 *
 * This class is responsible for managing and processing HTTP requests
 * within the application. It provides methods to retrieve request data,
 * validate input, and handle various request types.
 *
 * @package Clicalmani\Foundation\Http\Requests
 */
abstract class HttpRequest implements \Psr\Http\Message\RequestInterface
{
    /**
     * The request target.
     *
     * @var string
     */
    protected $requestTarget;

    /**
     * The request method.
     *
     * @var string
     */
    protected $method;

    /**
     * The request headers.
     *
     * @var array
     */
    protected $headers = [];

    /**
     * The protocol version.
     *
     * @var string
     */
    protected $protocolVersion;

    /**
     * The request body.
     *
     * @var \Psr\Http\Message\StreamInterface
     */
    protected $body;

    public function getHeaders() : array
    {
        if ( inConsoleMode() ) return $this->all();
        return apache_request_headers();
    }
    
    public function getHeader(string $name) : array
    {
        $headers = $this->getHeaders();
        $name = strtolower($name);
        $result = [];

        foreach ($headers as $key => $value) {
            if (strtolower($key) === $name) {
                $result[] = $value;
            }
        }

        return $result;
    }

    /**
     * Returns the specified request header if present. Otherwise
     * default value will be returned.
     * 
     * @param string $name Header name
     * @param ?string $default Default value
     * @return string
     */
    public function header(string $name, ?string $default = null) : string
    {
        $headers = $this->getHeader($name);
        if (empty($headers)) {
            return $default ?? '';
        }
        return $headers[0];
    }

    /**
     * Set response header
     * 
     * @param string $header
     * @param string $value
     * @return void
     */
    public function setHeader($header, $value) : void
    {
        header("$header: $value");
    }

    /**
     * Current request method
     * 
     * @return string
     */
    public function getMethod() : string
    { 
        if ( inConsoleMode() ) return '@console';
        return strtolower( (string) @ $_SERVER['REQUEST_METHOD'] );
    }

    /**
     * Get the host name from the request
     * 
     * @return string
     */
    public function getHost() : string
    {
        return $_SERVER['HTTP_HOST'] ?? '';
    }

    /**
     * Gather request parameters
     * 
     * @return array
     */
    public static function all() : array
    {
        return $_REQUEST;
    }

    /**
     * Verify if file has been provided
     * 
     * @param string $name File name
     * @return bool
     */
    public function hasFile(string $name) : bool
    {
        return !!@ $_FILES[$name];
    }

    /**
     * Check if the request is secure (HTTPS).
     * 
     * @return bool
     */
    public function isSecure() : bool
    {
        return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    }

    /**
     * Get the HTTP host from the current request.
     * 
     * @return string
     */
    public function getHttpHost() : string
    {
        return $_SERVER['HTTP_HOST'] ?? '';
    }

    /**
     * Get the scheme and HTTP host from the current request.
     * 
     * @return string
     */
    public function getSchemeAndHttpHost() : string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $scheme . '://' . $this->getHttpHost();
    }

    /**
     * Get the client's IP address.
     * 
     * @return string
     */
    public function ip() : string
    {
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    /**
     * Get all the client's IP addresses.
     * 
     * @return array
     */
    public function ips() : array
    {
        $ips = [];
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ips[] = $_SERVER['HTTP_CLIENT_IP'];
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = array_merge($ips, explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
        }
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $ips[] = $_SERVER['REMOTE_ADDR'];
        }
        return array_map('trim', $ips);
    }

    /**
     * Get the acceptable content types from the request.
     * 
     * @return array
     */
    public function getAcceptableContentTypes() : array
    {
        $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
        return array_map('trim', explode(',', $acceptHeader));
    }

    /**
     * Determine if the request accepts any of the given content types.
     * 
     * @param array $content_types
     * @return bool
     */
    public function accepts(array $content_types) : bool
    {
        $acceptableTypes = $this->getAcceptableContentTypes();
        foreach ($content_types as $type) {
            if (in_array($type, $acceptableTypes)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determine which content type out of an array the request prefers.
     * 
     * @param array $content_types
     * @return string|null
     */
    public function prefers(array $content_types) : ?string
    {
        $acceptableTypes = $this->getAcceptableContentTypes();
        foreach ($acceptableTypes as $type) {
            if (in_array($type, $content_types)) {
                return $type;
            }
        }
        return null;
    }

    /**
     * Determine if the request expects a JSON response.
     * 
     * @return bool
     */
    public function expectsJson() : bool
    {
        return $this->accepts(['application/json', 'text/json']);
    }
    
    public function getRequestTarget(): string
    {
        return $this->requestTarget;
    }
    
    public function withRequestTarget(string $requestTarget): \Psr\Http\Message\RequestInterface
    {
        $new = clone $this;
        $new->requestTarget = $requestTarget;
        return $new;
    }

    public function withMethod(string $method): \Psr\Http\Message\RequestInterface
    {
        $new = clone $this;
        $new->method = $method;
        return $new;
    }

    public function getUri(): \Psr\Http\Message\UriInterface
    {
        $uri = new \GuzzleHttp\Psr7\Uri($this->getRequestTarget());
        return $uri;
    }

    public function withUri(\Psr\Http\Message\UriInterface $uri, bool $preserveHost = false): \Psr\Http\Message\RequestInterface
    {
        $new = clone $this;
        $new->requestTarget = (string) $uri;

        if (!$preserveHost || !$this->getHost()) {
            $new->headers['Host'] = [$uri->getHost()];
        }

        return $new;
    }

    public function getProtocolVersion(): string
    {
        return $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
    }

    public function withProtocolVersion(string $version): \Psr\Http\Message\MessageInterface
    {
        $new = clone $this;
        $new->protocolVersion = $version;
        return $new;
    }

    public function hasHeader(string $name): bool
    {
        $headers = $this->getHeaders();
        $name = strtolower($name);

        foreach ($headers as $key => $value) {
            if (strtolower($key) === $name) {
                return true;
            }
        }

        return false;
    }

    public function getHeaderLine(string $name): string
    {
        $headers = $this->getHeader($name);
        return implode(', ', $headers);
    }

    public function withHeader(string $name, $value): \Psr\Http\Message\MessageInterface
    {
        $new = clone $this;
        $new->headers[$name] = [$value];
        return $new;
    }

    public function withAddedHeader(string $name, $value): \Psr\Http\Message\MessageInterface
    {
        $new = clone $this;
        $new->headers[$name][] = $value;
        return $new;
    }

    public function withoutHeader(string $name): \Psr\Http\Message\MessageInterface
    {
        $new = clone $this;
        $name = strtolower($name);

        foreach ($new->headers as $key => $value) {
            if (strtolower($key) === $name) {
                unset($new->headers[$key]);
            }
        }

        return $new;
    }

    public function getBody(): \Psr\Http\Message\StreamInterface
    {
        $body = fopen('php://temp', 'r+');
        fwrite($body, file_get_contents('php://input'));
        rewind($body);
        return new \GuzzleHttp\Psr7\Stream($body);
    }

    public function withBody(\Psr\Http\Message\StreamInterface $body): \Psr\Http\Message\MessageInterface
    {
        $new = clone $this;
        $new->body = $body;
        return $new;
    }
}
