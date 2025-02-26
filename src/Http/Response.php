<?php
namespace Clicalmani\Foundation\Http;

use Clicalmani\Foundation\Exceptions\ResourceNotFoundException;
use Clicalmani\Foundation\Routing\Route;
use Clicalmani\Psr7\Header;
use Clicalmani\Psr7\NonBufferedBody;
use Clicalmani\Psr7\Stream;
use Psr\Http\Message\StreamInterface;

/**
 * Class Response
 * 
 * @package Clicalmani\Foundation
 * @author @Clicalmani\Foundation
 */
class Response extends \Clicalmani\Psr7\Response
{
    use StatusErrors;

    public function __construct(
        private string $message = '',
        protected int $status = 200
    )
    {
        parent::__construct();
        if ($this->message) {
            $this->body = new NonBufferedBody();
        }
    }

    private function __json(mixed $data)
    {
        return json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK |
            JSON_UNESCAPED_SLASHES
            | JSON_THROW_ON_ERROR /** Enable strict mode */,
            512
        );
    }

    /**
     * Send a json response
     * 
     * @param mixed $data
     * @return static
     */
    public function json(mixed $data) : static
    {
        $this->sendBody($this->__json($data));
        return $this;
    }

    /**
     * Send a status response
     * 
     * @param ?int $code Status code
     * @return never
     */
    public function sendStatus(?int $code = null)
    {
        $this->status = $code ?: $this->status;
        http_response_code($this->status);
        
        if (Route::isApi()) 
            $this->sendBody(
                json_encode(
                    [
                        'status_code' => $this->status, 
                        'error_code' => $this->getReasonPhrase()
                    ]
                )
            );

        else {
            try {
                $this->sendBody(view($this->status, ['code' => $this->getReasonPhrase()]));
            } catch (ResourceNotFoundException $e) {
                ;
            }
        }
    }

    /**
     * Send a success status
     * 
     * @param mixed $message
     * @return static
     */
    public function success(mixed $message = null) : static
    {
        $this->body->write(
            $this->__json([
                'success' => true,
                'data'    => $message
            ])
        );
        return $this;
    }

    /**
     * Send an error status
     * 
     * @param mixed $message
     * @return static
     */
    public function error(mixed $message = null) : static
    {
        $this->body->write(
            $this->__json([
                'success' => false,
                'data'    => $message
            ])
        );
        return $this;
    }

    /**
     * Convert the response to string
     * 
     * @return string
     */
    public function __toString() : string
    {
        return (string) $this->body;
    }

    /**
     * Set the message
     * 
     * @param string $message
     * @return static
     */
    public function setMessage(string $message) : static
    {
        $this->message = $message;
        return $this;
    }
    
    /**
     * Send the response headers
     * 
     * @return void
     */
    public function sendHeaders()
    {
        /** @var \Clicalmani\Psr7\Header */
        foreach ($this->headers as $header) {
            $header->send();
        }
    }

    /**
     * Send the response body
     * 
     * @param ?string $content
     * @return never
     */
    public function sendBody(?string $content = null) : never
    {
        if ($this->body instanceof NonBufferedBody) {
            if ($content) {
                $this->body->write($content);
            }
        } elseif ($this->body instanceof \Clicalmani\Psr7\Stream) {

            $size = $this->body->getSize();
            $length = $size;

            if (isset($_SERVER['HTTP_RANGE'])) {
                $range = $_SERVER['HTTP_RANGE'];
                $range = str_replace('bytes=', '', $range);
                $range = explode('-', $range);
                $range = array_map('intval', $range);
                $length = $range[1] - $range[0] + 1;

                $this->header('Accept-Ranges', 'bytes')
                    ->header('Content-Range', sprintf('bytes %d-%d/%d', $range[0], $range[1], $this->body->getSize()))
                    ->header('Content-Length', $length)
                    ->status(206);
            }

            $ini_limit = ini_get('memory_limit');
            $unit = strtoupper(substr($ini_limit, -1));
    
            $memory_limit = (int) $ini_limit * match ( $unit ) {
                'B' => 1,
                'KB' => 1024,
                'MB' => 1024 * 1024,
                'GB' => 1024 * 1024 * 1024,
                'TB' => 1024 * 1024 * 1024 * 1024,
                'PB' => 1024 * 1024 * 1024 * 1024 * 1024,
                'EB' => 1024 * 1024 * 1024 * 1024 * 1024 * 1024,
                'ZB' => 1024 * 1024 * 1024 * 1024 * 1024 * 1024 * 1024,
                'YB' => 1024 * 1024 * 1024 * 1024 * 1024 * 1024 * 1024 * 1024,
                default => 0
            };

            if ($length > $memory_limit) {
                $this->body->rewind();
                while (! $this->body->eof()) {
                    echo $this->body->read($memory_limit);
                }
            } else {
                echo $this->body->read($length);
            }
        }

        exit;
    }

    /**
     * Send a response
     * 
     * @param string $content
     * @return never
     */
    public function send(string $content = '') : never
    {
        $content = $content ?: $this->message;
        
        if ( !headers_sent() ) {
            http_response_code($this->status);
            $this->sendHeaders();
            $this->sendBody($content);
        }

        exit;
    }

    /**
     * Set status
     * 
     * @param int $code
     * @return static
     */
    public function status(int $code) : static
    {
        $this->status = $code;
        http_response_code($code);
        return $this;
    }

    /**
     * Set header
     * 
     * @param string $name
     * @param string|array $value
     * @return static
     */
    public function header(string $name, string|array $value) : static
    {
        $this->headers[] = new Header($name, (array)$value);
        return $this;
    }

    /**
     * Send a file for download
     * 
     * @param string $file
     * @param ?string $name
     * @param ?string $type
     * @return never
     */
    public function sendFile(string $file, string $name = null, string $type = null) : never
    {
        $this->withHeader('Content-Type', $type ?? mime_content_type($file))
            ->withHeader('Content-Disposition', "attachment; filename=\"$name\"")
            ->withBody(\Clicalmani\Psr7\Stream::createFromFile($file, 'r'))
            ->send();
    }

    /**
     * Redirect to a url
     * 
     * @param string $url
     * @param int $status
     * @return \Clicalmani\Foundation\Http\RedirectInterface
     */
    public function redirect(string $uri = '/', int $status = 302) : RedirectInterface
    {
        return new Redirect($uri, $status);
    }

    /**
     * Stream a file
     * 
     * @param string $file
     * @param int $status
     * @param array $headers
     * @return never
     */
    public function stream(string $file, int $status = 200, array $headers = []) : never
    {
        $this->status = $status;
        $this->withHeaders($headers);

        $stream = \Clicalmani\Psr7\Stream::createFromFile($file, 'r');
        $size = $stream->getSize();
        $length = $size;

        if (isset($_SERVER['HTTP_RANGE'])) {
            $range = $_SERVER['HTTP_RANGE'];
            $range = str_replace('bytes=', '', $range);
            $range = explode('-', $range);
            $range = array_map('intval', $range);
            $length = $range[1] - $range[0] + 1;

            $stream->seek($range[0]);

            $this->header('Accept-Ranges', 'bytes')
                ->header('Content-Range', sprintf('bytes %d-%d/%d', $range[0], $range[1], $this->body->getSize()))
                ->header('Content-Length', $length)
                ->status(206);
        }

        $this->withHeader('Content-Type', mime_content_type($file))
            ->withBody($stream)
            ->send();
    }

    /**
     * Set a cookie
     * 
     * @param string $name
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     * @return static
     */
    public function cookie(
        string $name, 
        string $value, 
        int $expires = 0, 
        string $path = '', 
        string $domain = '', 
        bool $secure = false, 
        bool $httponly = false
    ) : static {
        (new \Clicalmani\Cookie\Cookie(
            $name,
            $value,
            $expires,
            $path,
            $domain,
            $secure,
            $httponly
        ))->set();
        return $this;
    }

    /**
     * Delete a cookie
     * 
     * @param string $name
     * @param string $path
     * @param string $domain
     * @return static
     */
    public function deleteCookie(string $name, string $path = '', string $domain = '') : static
    {
        setcookie($name, '', time() - 3600, $path, $domain);
        return $this;
    }

    /**
     * Set a view
     * 
     * @param string $view
     * @param array $data
     * @return never
     */
    public function view(string $view, array $data = []) : never
    {
        $this->send(view($view, $data)->render());
    }

    /**
     * Set multiple headers
     * 
     * @param string $view
     * @param array $data
     * @return never
     */
    public function withHeaders(array $headers) : static
    {
        foreach ($headers as $name => $value) {
            $this->header($name, $value);
        }
        return $this;
    }

    /**
     * Create a stream from a path and context
     * 
     * @param string $path
     * @param array $context
     * @return static
     */
    public function createStream(string $path, array $context = []) : StreamInterface 
    {
        $this->sendHeaders();
        return new Stream(fopen($path, 'r', false, stream_context_create($context)));
    }
}