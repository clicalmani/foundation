<?php
namespace Clicalmani\Foundation\Support\Facades;

/**
 * @method static never sendStatus(?int $code = null) Send a status response
 * @method static void sendHeaders() Send the response headers
 * @method static never send(string $content = '') Send a text response
 * @method static \Clicalmani\Foundation\Http\ResponseInterface status(int $code) Set response status
 * @method static \Clicalmani\Foundation\Http\ResponseInterface header(string $name, string|array $value) Set response header
 * @method static never sendFile(string $file, ?string $name = null, ?string $type = null) Send a file for download
 * @method static Clicalmani\Foundation\Http\RedirectInterface redirect(string $uri = '/', int $status = 302) Redirect response
 * @method static never stream(string $file, int $status = 200, array $headers = []) Stream a file
 * @method static \Clicalmani\Foundation\Http\ResponseInterface cookie(string $name, string $value, int $expires = 0, string $path = '', string $domain = '', bool $secure = false, bool $httponly = false) Set a cookie
 * @method static \Clicalmani\Foundation\Http\ResponseInterface deleteCookie(string $name, string $path = '', string $domain = '') Delete a cookie
 * @method static never view(string $view, array $data = []) Send a view
 * @method static \Clicalmani\Foundation\Http\ResponseInterface withHeaders(array $headers) Set multiple headers
 */
abstract class Response extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'response';
    }
}