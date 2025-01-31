<?php
namespace Clicalmani\Foundation\Http;

use Clicalmani\Foundation\Routing\Route;

/**
 * Class Response
 * 
 * @package Clicalmani\Foundation
 * @author @Clicalmani\Foundation
 */
class Response extends \Clicalmani\Psr7\Response
{
    use StatusErrors;

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
     * @return void
     */
    public function sendStatus(?int $code = null) : void
    {
        $this->status = $this->status ?? $code;
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
            $this->sendBody(view($this->status, ['code' => $this->getReasonPhrase()]));
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
     * Send the response headers
     * 
     * @return void
     */
    public function sendHeaders()
    {
        foreach ($this->headers->all() as $name => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }
    }

    /**
     * Send the response body
     * 
     * @param string $content
     * @return void
     */
    public function sendBody(string $content) : void
    {
        $this->body->write($content);
    }

    /**
     * Send a response
     * 
     * @param mixed $content
     * @return void
     */
    public function send(mixed $content = '') : void
    {
        http_response_code($this->status);
        $this->sendHeaders();
        $this->sendBody($content);

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
     * @param array $value
     * @return static
     */
    public function header(string $name, array $value) : static
    {
        $this->headers[$name] = $value;
        return $this;
    }
}