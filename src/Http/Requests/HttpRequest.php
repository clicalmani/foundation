<?php
namespace Clicalmani\Foundation\Http\Requests;

use Clicalmani\Foundation\Collection\Collection;
use Clicalmani\Foundation\Collection\CollectionInterface;
use Clicalmani\Foundation\Support\Facades\Log;
use Clicalmani\Psr7\Header;
use Clicalmani\Psr7\HeadersInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Handles the HTTP request.
 *
 * This class is responsible for managing and processing HTTP requests
 * within the application. It provides methods to retrieve request data,
 * validate input, and handle various request types.
 *
 * @package Clicalmani\Foundation\Http
 */
abstract class HttpRequest extends \Clicalmani\Psr7\Request
{
    const HEADER_X_FORWARDED_ALL = 'X-Forwarded-*';

    /**
     * Trusted hosts.
     *
     * @var string[]
     */
    protected static $trustedHosts = [];

    /**
     * Trusted proxies.
     *
     * @var array
     */
    protected static $trustedProxies = [];

    public function __construct(
        $method,
        UriInterface $uri,
        HeadersInterface $headers,
        array $cookies,
        array $serverParams,
        StreamInterface $body
    ) {
        $this->method = $method;
        $this->uri = $uri;
        $this->headers = $headers;
        $this->cookies = $cookies;
        $this->serverParams = $serverParams;
        $this->attributes = $_REQUEST;
        $this->body = $body;
        $this->uploadedFiles = $_FILES;
        
        if (isset($serverParams['SERVER_PROTOCOL'])) {
            $this->protocolVersion = str_replace('HTTP/', '', $serverParams['SERVER_PROTOCOL']);
        }

        if (!isset($this->headers['HTTP_HOST']) || $this->uri->getHost() !== '') {
            $this->headers[] = new Header('HTTP_HOST', (array)$this->uri->getHost());
        }
        
        $input = $this->body->getContents();
        
        if (in_array($this->method, ['put', 'patch'])) {

            parse_str(urldecode($input), $stream);
            
            if ($stream_boundary = $this->getStreamBoundary()) {

                $records = tap(
                    preg_split("/-+$stream_boundary/", $input, -1, PREG_SPLIT_NO_EMPTY), 
                    fn(array &$parts) => array_pop($parts)
                );

                $attrs = [];
                
                foreach($records as $record) {
                    $this->retrieveAttributes($attrs, $record);
                    $this->retrieveFiles($record);
                }
            } else {
                $this->attributes = array_merge($this->attributes, $stream);
            }
        }
        
        if ( isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== FALSE ) {
            $this->attributes = json_decode($input, true) ?? $_REQUEST;
        }
    }

    public function getHeaders() : array
    {
        if ( isConsoleMode() ) return $this->attributes;
        return $this->headers->all();
    }
    
    public function getHeader(string $name) : array
    {
        return $this->__getHeader($name)?->value ?? [];
    }

    private function __getHeader(string $name) : ?Header
    {
        /** @var \Clicalmani\Psr7\Header */
        foreach ($this->headers as $header) {
            if ($header->name === strtolower($name)) return $header;
        }

        return null;
    }

    /**
     * Returns the specified request header if present. Otherwise
     * default value will be returned.
     * 
     * @param string $name Header name
     * @param ?string $default Default value
     * @return ?string
     */
    public function header(string $name, ?string $default = null) : ?string
    {
        if (NULL !== $header = $this->__getHeader($name)) return $header->line();

        return $default;
    }

    /**
     * Current request method
     * 
     * @return string
     */
    public function getMethod() : string
    { 
        if ( isConsoleMode() ) return '@console';
        return strtolower( (string) @ $_SERVER['REQUEST_METHOD'] );
    }

    /**
     * Get the host name from the request
     * 
     * @return string
     */
    public function getHost() : string
    {
        return $this->uri->getHost();
    }

    /**
     * Verify if file has been provided
     * 
     * @param string $name File name
     * @return bool
     */
    public function hasFile(string $name) : bool
    {
        return array_key_exists($name, $this->uploadedFiles);
    }

    public function file(string $name) : FileInterface|CollectionInterface|null
    {
        if ($this->hasFile($name)) {
            $request_signatures = \Clicalmani\Foundation\Http\Request::current()->getSignatures();

            $file = $this->uploadedFiles[$name];
            
            if (array_key_exists($name, $request_signatures)) {
                $validator = new \Clicalmani\Validation\Validator;
                $inputs = [$name => $file];
                $validator->sanitize($inputs, [$name => $request_signatures[$name]]);
            }

            if ( is_string($file['name'])) {
                return new File(
                    $file['tmp_name'],
                    $file['name'],
                    $file['type'],
                    $file['size'],
                    $file['error']
                );
            }

            $files = new Collection;

            for ($i=0; $i < count($file['name']); $i++) {
                $files->add(
                    new File(
                        $file['tmp_name'][$i],
                        $file['name'][$i],
                        $file['type'][$i],
                        $file['size'][$i],
                        $file['error'][$i]
                    )
                );
            }

            return $files;
        }

        return null;
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
        return $this->uri->getScheme() . '://' . $this->getHttpHost();
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

    public function getProtocolVersion(): string
    {
        return $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
    }

    public function hasHeader(string $name): bool
    {
        return !!$this->getHeaderLine($name);
    }

    public function getHeaderLine(string $name): string
    {
        return $this->__getHeader($name)?->line() ?? '';
    }

    public static function setTrustedHosts(array $trustedHosts): void
    {
        static::$trustedHosts = $trustedHosts;
    }

    public static function setTrustedProxies(array $trustedProxies): void
    {
        static::$trustedProxies = $trustedProxies;
    }

    public static function setTrustedHeaderNames(array $headers_names)
    {
        $ips = [];

        if ( isset(static::$trustedProxies[0]) ) {
            $ips = array_shift(static::$trustedProxies[0]) ?? [];
        }

        static::$trustedProxies = [
            $ips, ...$headers_names
        ];
    }

    /**
     * Get stream boundary
     * 
     * @return mixed Stream boundary if success, null if failure.
     */
    private function getStreamBoundary() : mixed
    {
        preg_match('/boundary=(.*)$/', @ $_SERVER['CONTENT_TYPE'] ?? '', $matches);
        return @$matches[1] ?? null;
    }

    /**
     * Retrieve attributes from the stream
     * 
     * @param string $block
     * @return void
     */
    private function retrieveAttributes(array &$params, string $block) : void
    {
        if (strpos($block, 'application/octet-stream') !== FALSE) {
            preg_match('/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s', $block, $matches);
            $params[$matches[1]] = @$matches[2] ?? '';
        } elseif (strpos($block, 'filename') === FALSE AND preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches)) {

            $value = @$matches[2] ?? '';
            $name = $matches[1]; 
            
            if (preg_match('/^(.*)\[\]$/i', $name, $tmp)) { 
                $params[$tmp[1]][] = @$value;
            } else {
                $params[$name] = @$value;
            }
        }
        
        foreach ($params as $key => $value) {
            $this->parseParameter($params, $key, $value);
        }

        $this->attributes = array_merge($this->attributes, $params);
    }

    /**
     * Retrieve files from the stream
     * 
     * @param string $block
     * @return void
     */
    private function retrieveFiles(string $block) : void
    {
        $result = [];

        if (strpos($block, 'filename') !== FALSE) {
            
            $data = ltrim($block);
            
            if ($idx = strpos($data, "\r\n\r\n")) {
                $headers = substr( $data, 0, $idx );
                $content = substr( $data, $idx + 4, -2 ); // Skip the leading \r\n and strip the final \r\n
                
                $name = '-unknown-';
                $filename = '-unknown-';
                $filetype = 'application/octet-stream';

                $header = strtok( $headers, "\r\n" );
                
                while ($header !== FALSE) {
                    if ( substr($header, 0, strlen("Content-Disposition: ")) == "Content-Disposition: " ) {
                        if ( preg_match('/name=\"([^\"]*)\"/', $header, $nmatch ) ) {
                            $name = $nmatch[1];
                        }
                        if ( preg_match('/filename=\"([^\"]*)\"/', $header, $nmatch ) ) {
                            $filename = $nmatch[1];
                        }
                    } elseif ( substr($header, 0, strlen("Content-Type: ")) == "Content-Type: " ) {
                        $filetype = trim( substr($header, strlen("Content-Type: ")) );
                    } else {
                        Log::notice( "PARSEINPUTSTREAM: Skipping Header: " . $header );
                    }

                    $header = strtok("\r\n");
                }
                
                if (substr($data, -2) === "\r\n") {
                    $data = substr($data, 0, -2);
                }

                if ( $ext = substr($filename, strrpos($filename, '.') + 1) ) {
                    $tmp_name = "php-" . substr( sha1(rand()), 0, 6 ) . ".$ext";
                    $path = sys_get_temp_dir() . "/$tmp_name";
                    $bytes = file_put_contents( $path, $content );
                    $this->addFile($name, $filename, $path, $bytes, $filetype);
                }
            } else {
                Log::warning("ParseInputStream: Could not locate header separator in data:");
                Log::warning($data);
            }
        }

        $files = [];

        foreach ($result as $key => $value) {
            $this->parseParameter($files, $key, $value);
        }

        $this->uploadedFiles = array_merge($this->uploadedFiles, $files);
    }

    /**
     * Parse parameters
     * 
     * @param array &$params 
     * @param string $parameter
     * @param mixed $value
     */
    private function parseParameter(array &$params, string $parameter, mixed $value) 
    {
		if (array_key_exists($parameter, $params) && is_array($params[$parameter])) {
            $params[$parameter] = array_unique(array_merge($params[$parameter], $value));
        } else {
            $params[$parameter] = $value;
        }
	}

    /**
     * Add uploaded file
     * 
     * @param string $name
     * @param string $filename File name
     * @param string $path File temp path
     * @param string $size File size
     * @param string $type File mimetype
     * @return void
     */
    private function addFile(string $name, string $filename, string $path, int $size, string $type) : void
    {
        if (FALSE === array_key_exists($name, $this->uploadedFiles)) {
            $this->uploadedFiles[$name] = [
                'name'      => $filename,
                'full_path' => $filename,
                'type'      => $type,
                'tmp_name'  => $path,
                'error'     => !$size ? 1: 0,
                'size'      => $size,
                'time'      => time()
            ];
        } elseif ($file = (object)$this->uploadedFiles[$name] AND property_exists($file, 'name') && is_array( $file?->name )) {
            $this->uploadedFiles[$name]['name'][]      = $filename;
            $this->uploadedFiles[$name]['full_path'][] = $filename;
            $this->uploadedFiles[$name]['type'][]      = $type;
            $this->uploadedFiles[$name]['tmp_name'][]  = $path;
            $this->uploadedFiles[$name]['error'][]     = !$size ? 1: 0;
            $this->uploadedFiles[$name]['size'][]      = $size;
            $this->uploadedFiles[$name]['time'][]      = time();
        }
    }
}
