<?php
namespace Clicalmani\Foundation\Http;

use Psr\Http\Message\StreamInterface;

interface ResponseInterface extends Responses\StatusErrorInterface, Responses\JsonResponseInterface
{
    /**
     * Send a json response
     * 
     * @param mixed $data
     * @return self
     */
    public function json(mixed $data) : self;

    /**
     * Send a status response
     * 
     * @param ?int $code Status code
     * @return never
     */
    public function sendStatus(?int $code = null);

    /**
     * Send a success status
     * 
     * @param mixed $message
     * @return self
     */
    public function success(mixed $message = null) : self;

    /**
     * Send an error status
     * 
     * @param mixed $message
     * @return self
     */
    public function error(mixed $message = null) : self;

    /**
     * Set the message
     * 
     * @param string $message
     * @return self
     */
    public function setMessage(string $message) : self;

    /**
     * Send the response headers
     * 
     * @return void
     */
    public function sendHeaders() : void;

    /**
     * Send the response body
     * 
     * @param ?string $content
     * @return never
     */
    public function sendBody(?string $content = null) : never;

    /**
     * Send a response
     * 
     * @param string $content
     * @return never
     */
    public function send(string $content = '') : never;

    /**
     * Set status
     * 
     * @param int $code
     * @return self
     */
    public function status(int $code) : self;

    /**
     * Set header
     * 
     * @param string $name
     * @param string|array $value
     * @return self
     */
    public function header(string $name, string|array $value) : self;

    /**
     * Send a file for download
     * 
     * @param string $file
     * @param ?string $name
     * @param ?string $type
     * @return never
     */
    public function sendFile(string $file, ?string $name = null, ?string $type = null) : never;

    /**
     * Redirect to a url
     * 
     * @param string $url
     * @param int $status
     * @return \Clicalmani\Foundation\Http\RedirectInterface
     */
    public function redirect(string $uri = '/', int $status = 302) : RedirectInterface;

    /**
     * Stream a file
     * 
     * @param string $file
     * @param int $status
     * @param array $headers
     * @return never
     */
    public function stream(string $file, int $status = 200, array $headers = []) : never;

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
     * @return self
     */
    public function cookie(
        string $name, 
        string $value, 
        int $expires = 0, 
        string $path = '', 
        string $domain = '', 
        bool $secure = false, 
        bool $httponly = false
    ) : self;

    /**
     * Delete a cookie
     * 
     * @param string $name
     * @param string $path
     * @param string $domain
     * @return self
     */
    public function deleteCookie(string $name, string $path = '', string $domain = '') : self;

    /**
     * Set a view
     * 
     * @param string $view
     * @param array $data
     * @return never
     */
    public function view(string $view, array $data = []) : never;

    /**
     * Set multiple headers
     * 
     * @param string $view
     * @param array $data
     * @return self
     */
    public function withHeaders(array $headers) : self;

    /**
     * Create a stream from a path and context
     * 
     * @param string $path
     * @param array $context
     * @return \Psr\Http\Message\StreamInterface
     */
    public function createStream(string $path, array $context = []) : StreamInterface;
}