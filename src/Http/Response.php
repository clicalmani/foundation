<?php
namespace Clicalmani\Foundation\Http;

use Clicalmani\Foundation\Exceptions\ResourceNotFoundException;
use Clicalmani\Foundation\Support\Facades\Route;
use Clicalmani\Psr\Header;
use Clicalmani\Psr\NonBufferedBody;
use Clicalmani\Psr\Stream;
use Psr\Http\Message\StreamInterface;

/**
 * Class Response
 * 
 * @package Clicalmani\Foundation
 * @author @Clicalmani\Foundation
 */
class Response extends \Clicalmani\Psr\Response implements ResponseInterface
{
    use StatusErrors;
    use JsonResponse;

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

    public function sendStatus(?int $code = null)
    {
        http_response_code($this->status = $code ?: $this->status);
        
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
                $this->sendBody(view($this->status, ['code' => $this->getReasonPhrase()])->render());
            } catch (ResourceNotFoundException $e) {
                ;
            }
        }
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

    public function setMessage(string $message) : static
    {
        $this->message = $message;
        return $this;
    }
    
    public function sendHeaders() : void
    {
        /** @var \Clicalmani\Psr\Header */
        foreach ($this->headers as $header) {
            $header->send();
        }
    }

    public function sendBody(?string $content = null) : never
    {
        if ($this->body instanceof NonBufferedBody) {
            if ($content) {
                $this->body->write($content);
            }
        } elseif ($this->body instanceof \Clicalmani\Psr\Stream) {

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

    public function status(int $code) : \Clicalmani\Foundation\Http\ResponseInterface
    {
        http_response_code($this->status = $code);
        return $this;
    }

    public function header(string $name, string|array $value) : \Clicalmani\Foundation\Http\ResponseInterface
    {
        $this->headers[] = new Header($name, (array)$value);
        return $this;
    }

    public function sendFile(string $file, ?string $name = null, ?string $type = null) : never
    {
        $this->withHeader('Content-Type', $type ?? mime_content_type($file))
            ->withHeader('Content-Disposition', "attachment; filename=\"$name\"")
            ->withBody(\Clicalmani\Psr\Stream::createFromFile($file, 'r'))
            ->send();
    }

    public function redirect(string $uri = '/', int $status = 302) : RedirectInterface
    {
        return new Redirect($uri, $status);
    }

    public function stream(string $file, int $status = 200, array $headers = []) : never
    {
        $this->status = $status;
        $this->withHeaders($headers);

        $stream = \Clicalmani\Psr\Stream::createFromFile($file, 'r');
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

    public function cookie(
        string $name, 
        string $value, 
        int $expires = 0, 
        string $path = '', 
        string $domain = '', 
        bool $secure = false, 
        bool $httponly = false
    ) : \Clicalmani\Foundation\Http\ResponseInterface {
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

    public function deleteCookie(string $name, string $path = '', string $domain = '') : \Clicalmani\Foundation\Http\ResponseInterface
    {
        setcookie($name, '', time() - 3600, $path, $domain);
        return $this;
    }

    public function view(string $view, array $data = []) : never
    {
        $this->send(view($view, $data)->render());
    }

    public function withHeaders(array $headers) : \Clicalmani\Foundation\Http\ResponseInterface
    {
        foreach ($headers as $name => $value) {
            $this->header($name, $value);
        }
        return $this;
    }

    public function createStream(string $path, array $context = []) : StreamInterface 
    {
        $this->sendHeaders();
        return new Stream(fopen($path, 'r', false, stream_context_create($context)));
    }
}