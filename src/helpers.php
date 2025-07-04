<?php

use Carbon\Carbon;
use Clicalmani\Foundation\Collection\Collection;
use Clicalmani\Foundation\Resources\ViewInterface;
use Clicalmani\Foundation\Support\Facades\Arr;

if ( ! function_exists('app') ) {
    function app() : \Clicalmani\Foundation\Maker\Application {
        return \Clicalmani\Foundation\Maker\Application::getInstance();
    }
}

if ( ! function_exists( 'root_path' ) ) {

    /**
     * Get root path
     * 
     * @param ?string $path
     * @return string
     */
    function root_path(?string $path = '') {
        return app()->rootPath() . ($path ? DIRECTORY_SEPARATOR . trim($path, '/\\') : $path);
    }
}

if ( ! function_exists( 'app_path' ) ) {

    /**
     * Get App path
     * 
     * @param ?string $path
     * @return string
     */
    function app_path(?string $path = '') : string {
        return app()->appPath( trim($path, '/\\') );
    }
}

if ( ! function_exists( 'public_path' ) ) {

    /**
     * Get publi path
     * 
     * @param ?string $path
     * @return string
     */
    function public_path(?string $path = '') : string {
        return app()->publicPath( trim($path, '/\\') );
    }
}

if ( ! function_exists( 'bootstrap_path' ) ) {

    /**
     * Get bootstrap directory
     * 
     * @param ?string $path
     * @return string
     */
    function bootstrap_path(?string $path = '') : string {
        return app()->bootstrapPath( trim($path, '/\\') );
    }
}

if ( ! function_exists( 'routes_path' ) ) {

    /**
     * Get routes path
     * 
     * @param ?string $path
     */
    function routes_path(?string $path = '') : string {
        return app()->routesPath( trim($path, '/\\') );
    }
}

if ( ! function_exists( 'resources_path' ) ) {

    /**
     * Get resources path
     * 
     * @param ?string $path
     * @return string
     */
    function resources_path(?string $path = '') : string {
        return app()->resourcesPath( trim($path, '/\\') );
    }
}

if ( ! function_exists( 'storage_path' ) ) {

    /**
     * Get storage path
     * 
     * @param ?string $path
     * @return string
     */
    function storage_path(?string $path = '') : string {
        return app()->storagePath( trim($path, '/\\') );
    }
}

if ( ! function_exists( 'config_path' ) ) {

    /**
     * Get config path
     * 
     * @param ?string $path
     * @return string
     */
    function config_path(?string $path = '') : string {
        return app()->configPath( trim($path, '/\\') );
    }
}

if ( ! function_exists( 'database_path' ) ) {

    /**
     * Get database path
     * 
     * @param ?string $path
     * @return string
     */
    function database_path(?string $path = '') : string {
        return app()->databasePath( trim($path, '/\\') );
    }
}

if ( ! function_exists( 'view' ) ) {

    /**
     * Render a template view
     * 
     * @param string $template Template name
     * @param ?array $vars Variables
     * @return \Clicalmani\Foundation\Resources\ViewInterface
     */
    function view(string $template, ?array $vars = []) : \Clicalmani\Foundation\Resources\ViewInterface {
        return new \Clicalmani\Foundation\Resources\View($template, $vars);
    }
}

if ( ! function_exists( 'client_uri' ) ) {

    /**
     * Returns the current route
     * 
     * @return string
     */
    function client_uri() : string {
        return \Clicalmani\Foundation\Support\Facades\Route::uri();
    }
}

if ( ! function_exists( 'csrf_token' ) ) {

    /**
     * Get CSRF token
     * 
     * @return mixed
     */
    function csrf_token() : mixed {
        return isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : null;
    }
}

if ( ! function_exists( 'env' ) ) {

    /**
     * Get env value
     * 
     * @param string $key Env key
     * @param ?string $default Default value if key does not exists.
     * @return mixed
     */
    function env(string $key, ?string $default = '') : mixed {
        return @$_ENV[$key] ?? $default;
    }
}

if ( ! function_exists( 'assets' ) ) {

    /**
     * Get asset
     * 
     * @param ?string $path Asset path
     * @return string
     */
    function assets(?string $path = '/') : string {
        $app_url = app()->getUrl($path);
        $protocol = '';
        if (preg_match('/^http/', $app_url) == false) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || @$_SERVER['SERVER_PORT'] === 443) ? 'https://': 'http://';
        }
        return $protocol.$app_url;
    }
}

if ( ! function_exists( 'password' ) ) {

    /**
     * Create a password hash
     * 
     * @param string $password Password to hash
     * @return string
     */
    function password(string $password) : string {
        return \Clicalmani\Foundation\Auth\EncryptionServiceProvider::password($password);
    }
}

if ( ! function_exists( 'create_parameters_hash' ) ) {

    /**
     * Create parameters hash
     * 
     * @param array $parameters
     * @return string
     */
    function create_parameters_hash(array $parameters) : string {
        return (new \Clicalmani\Foundation\Http\Request)->createParametersHash($parameters);
    }
}

if ( ! function_exists( 'temp_dir' ) ) {

    /**
     * Get temp dir
     * 
     * @param ?string $path
     * @return string
     */
    function temp_dir(?string $path = '/') : string {

        if ( function_exists( 'sys_get_temp_dir' ) ) {

            $temp = sys_get_temp_dir();

            if ( @is_dir( $temp ) ) {
                return $temp . $path;
            }
        }

        $temp = ini_get( 'upload_tmp_dir' );

        if ( @is_dir( $temp ) ) {
            return $temp . $path;
        }

        return "/tmp$path";
    }
}

if ( ! function_exists('request') ) {

    /**
     * Get request param
     * 
     * @param ?string $param
     * @return mixed
     */
    function request(?string $param = '', ?string $value = null) : mixed {

        $request = \Clicalmani\Foundation\Http\Request::current();

        if ('' === $param) {
            return $request->all();
        }

        if ( $request ) {
            if ($value) @$request->{$param} = $value;
            else return @$request->{$param};
        }

        return null;
    }
}

/**
 * Do a redirect
 * 
 * @return \Clicalmani\Foundation\Http\RedirectInterface
 */
function redirect(string $uri = '/', int $status = 302) : \Clicalmani\Foundation\Http\RedirectInterface {
    return new \Clicalmani\Foundation\Http\Redirect($uri, $status);
}

/**
 * Do a redirect back
 * 
 * @return \Clicalmani\Foundation\Http\RedirectInterface
 */
function back() : \Clicalmani\Foundation\Http\RedirectInterface {
    return redirect()->back();
}

if ( ! function_exists('response') ) {

    /**
     * Returns the response instance
     * 
     * @return \Clicalmani\Foundation\Http\ResponseInterface
     */
    function response(?string $message = '', int $status = 200) : \Clicalmani\Foundation\Http\ResponseInterface
    {
        $response = app()->response;

        if ('' !== $message) {
            $response->setMessage($message);
        }

        if (200 !== $status) {
            $response->setStatus($status);
        }

        return $response;
    }
}

if ( ! function_exists('route') ) {

    /**
     * Do route
     * 
     * @param mixed ...$args
     * @return mixed
     */
    function route(mixed ...$args) : mixed {
        return \Clicalmani\Foundation\Support\Facades\Route::resolve(...$args);
    }
}

if ( ! function_exists('collection') ) {

    /**
     * Create a collection
     * 
     * @return \Clicalmani\Foundation\Collection\CollectionInterface
     */
    function collection($items = []) : \Clicalmani\Foundation\Collection\CollectionInterface {
        return new \Clicalmani\Foundation\Collection\Collection( $items );
    }
}

if ( ! function_exists('collect') ) {

    /**
     * Alias of collection
     * 
     * @return \Clicalmani\Foundation\Collection\CollectionInterface
     */
    function collect($items = []) {
        return collection($items);
    }
}

if ( ! function_exists('sanitize_attribute') ) {

    /**
     * Sanitize attribute
     * 
     * @param string $attr
     * @return mixed
     */
    function sanitize_attribute($attr) : mixed {
        return preg_replace('/[^0-9a-z-_]+/', '', \Clicalmani\Foundation\Support\Facades\Str::slug($attr));
    }
}

if ( ! function_exists('now') ) {

    /**
     * Get current date
     * 
     * @param ?string $time_zone
     * @return \Carbon\Carbon
     */
    function now(?string $time_zone = 'Africa/Porto-Novo') : Carbon
    {
        return \Carbon\Carbon::now($time_zone);
    }
}

if ( ! function_exists('slugify') ) {

    /**
     * Slugify a string
     * 
     * @param string $str
     * @return string
     */
    function slugify(string $str, string $default = '') : string {
        return \Clicalmani\Foundation\Support\Facades\Str::slug($str, $default);
    }
}

if ( ! function_exists('recursive_unlink') ) {

    /**
     * Unlink path
     * 
     * @param string $path
     * @return bool True on success, false on failure.
     */
    function recursive_unlink(string $path) : bool {
	
	    if (is_dir($path) === true) {
		
		    $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path), 
                \RecursiveIteratorIterator::CHILD_FIRST
            );
			
			foreach ($files as $file) {
			    
				if (in_array($file->getBaseName(), array('.', '..')) !== true) {
				    
					if ($file->isDir() === true) { 
					    
						@ rmdir($file->getPathName());
					} elseif (($file->isFile() === true) || ($file->isLink() === true)) {
					    
						@ unlink($file->getPathName());
					}
				}
			}
			
			return @ rmdir($path);
		} elseif ((is_file($path) === true) || (is_link($path) === true)) {
		    
			return @ unlink($path);
		}
		
		return false;
	}
}

if ( ! function_exists('mail_smtp') ) {

    /**
     * Send mail through SMTP protocol
     * 
     * @param array $to
     * @param array $from
     * @param string $subject
     * @param string|\Clicalmani\Foundation\Resources\ViewInterface $body
     * @param ?array $options Mail options
     * @return mixed
     */
    function mail_smtp(array $to, array $from, string $subject, string|ViewInterface $body, ?array $options = [])
    {
        $mail = new \Clicalmani\Foundation\MailSMTP;

        if (@ $options['attachments'])
            foreach ($options['attachments'] as $attachment) {

                $name = (string) @ $attachment['name'] ?? '';
                $encoding = array_key_exists('encoding', $attachment) ? (string)$attachment['encoding'] : \PHPMailer\PHPMailer\PHPMailer::ENCODING_BASE64;
                $type = array_key_exists('type', $attachment) ? (string) $attachment['type'] : '';
                $disposition = array_key_exists('disposition', $attachment) ? (string)$attachment['disposition'] : 'attachment';

                switch(@$attachment['method']) {
                    case 'file': 
                        $mail->addAttachment(
                            (string) @ $attachment['path'],
                            $name,
                            $encoding,
                            $type,
                            $disposition
                        ); 
                        break;

                    case 'inline': 
                        $mail->addStringAttachment(
                            (string) @ $attachment['string'],
                            (string) @ $attachment['filename'],
                            $encoding,
                            $type,
                            $disposition
                        ); 
                        break;

                    case 'embed': 
                        $mail->addEmbeddedImage(
                            (string) @ $attachment['path'],
                            (string) @ $attachment['cid'],
                            $name,
                            $encoding,
                            $type,
                            $disposition
                        ); 
                        break;
                }
            }

        if (@ $options['headers'])
            foreach ($options['headers'] as $header) {
                $mail->addCustomHeader((string) @ $header['name'], (string) @ $header['value']);
            }

        $mail->setSubject($subject);
        $mail->setBody($body);
        $mail->setFrom($from['email'], $from['name']);

        foreach ($to as $address) {
            $mail->addAddress($address['email'], $address['name']);
        }

		if (@ $options['cc']) {
            foreach ($options['cc'] as $cc) {
                $mail->addCC($cc['email'], $cc['name']);
            }
		}

		if (@ $options['bcc']) {
            foreach ($options['bcc'] as $bcc) {
                $mail->addBC($bcc['email'], $bcc['name']);
            }
		}

		$mail->isHTML(true);
        
        $success = $mail->send();

        return [
            'success' => $success,
            'message' => $mail->getSentMIMEMessage()
        ];
    }
}

if ( ! function_exists('with') ) {

    /**
     * Return the given value or pass it to a callback.
     * 
     * @param mixed $value
     * @param ?callbable $callback
     * @return mixed
     */
    function with(mixed $value, ?callable $callback = null) : mixed {
        return is_null($callback) ? $value: $callback($value);
    }
}

if ( ! function_exists('instance') ) {
    /**
     * Class instance creator
     * 
     * @param string $class
     * @param ?callable $callback A callback function that receive an instance of the class as it's first argument.
     * @return mixed $class Object
     */
    function instance(string $class, ?callable $callback = null, mixed ...$args)
    {
        $instance = new $class( ...$args );
        if ($callback) $callback($instance);
        return $instance;
    }
}

if ( ! function_exists('isConsoleMode') ) {

    /**
     * Verify if console mode is active
     * 
     * @return bool
     */
    function isConsoleMode() : bool {
        return defined('CONSOLE_MODE_ACTIVE') && CONSOLE_MODE_ACTIVE;
    }
}

if ( ! function_exists('tap') ) {

    /**
     * Call the given closure with the given value and then return the value.
     * 
     * @param mixed $value
     * @param callable $callback
     * @return mixed
     */
    function tap(mixed $value, callable $callback) : mixed {
        $callback($value);
        return $value;
    }
}

if ( ! function_exists('value') ) {

    /**
     * Call a value with given parameter or return the value.
     * 
     * @param mixed $value
     * @param mixed $param
     * @return mixed
     */
    function value(mixed $value, mixed $param = null) {
        if ( ! is_callable($value) ) return $value;
        if ( $param ) return $value($param);
        return $value();
    }
}

if ( ! function_exists('call') ) {

    /**
     * Call a value with specified arguments.
     * 
     * @param callable $value
     * @param mixed ...$args
     * @return mixed
     */
    function call(mixed $value, mixed ...$args) : mixed {
        return $value( ...$args );
    }
}

if ( ! function_exists('nocall') ) {

    /**
     * Return a value without calling it.
     * 
     * @param mixed $value
     * @return mixed
     */
    function nocall(mixed $fn) : mixed {
        return $fn;
    }
}

if ( ! function_exists('faker') ) {
    function faker() {
        return new \Clicalmani\Database\Faker\Faker;
    }
}

if ( ! function_exists('xdt') ) {

    /**
     * Create a new XDT object.
     * 
     * @return \Clicalmani\XPower\XDT
     */
    function xdt() {
        return new \Clicalmani\XPower\XDT;
    }
}

if ( ! function_exists('token') ) {

    /**
     * Generate a token
     * 
     * @param mixed $jti
     * @return string
     */
    function token(mixed $jti) : string {
        $auth = new \Clicalmani\Foundation\Auth\AuthServiceProvider;
        $auth->setJti( $jti );
        return $auth->generateToken();
    }
}

if ( ! function_exists('get_payload') ) {

    /**
     * Get payload
     * 
     * @param string $token
     * @return mixed
     */
    function get_payload(string $token) : mixed {
        return with ( new \Clicalmani\Foundation\Auth\AuthServiceProvider )->verifyToken($token);
    }
}

if ( ! function_exists('bearerToken') ) {

    /**
     * Get payload
     * 
     * @param string $token
     * @return mixed
     */
    function bearerToken() : mixed {
        return with ( new \Clicalmani\Foundation\Http\Request )->bearerToken();
    }
}

if ( ! function_exists('tree') ) {

    /**
     * Flaten a tree
     * 
     * @param iterable $iterable
     * @param callable $callback
     * @return mixed
     */
    function tree(iterable|\Clicalmani\Foundation\Collection\CollectionInterface $iterable, callable $callback) : mixed {
        $ret = [];
        foreach ($iterable as $item) {
            $ret[] = $item;
            $ret = array_merge($ret, [...tree($callback($item), $callback)]);
        }

        return $ret;
    }
}

if ( ! function_exists('jwt') ) {

    /**
     * Create a JWT object. 
     * 
     * @param ?string $jti
     * @param mixed $expiry
     * @return \Clicalmani\Foundation\Auth\JWT
     */
    function jwt(?string $jti = null, mixed $expiry = 1) {
        return new \Clicalmani\Foundation\Auth\AuthServiceProvider($jti, $expiry);
    }
}

if ( ! function_exists('encrypt') ) {

    /**
     * Encrypt a value
     * 
     * @param string $value
     * @return mixed
     */
    function encrypt(string $value) : mixed {
        return \Clicalmani\Foundation\Auth\EncryptionServiceProvider::encrypt($value);
    }
}

if ( ! function_exists('decrypt') ) {

    /**
     * Decrypt a value
     * 
     * @param string $value
     * @return mixed
     */
    function decrypt(string $value) : mixed {
        return \Clicalmani\Foundation\Auth\EncryptionServiceProvider::decrypt($value);
    }
}

if ( ! function_exists('verify_token') ) {
    function verify_token(string $token) : mixed 
    {
        return with (new \Clicalmani\Foundation\Auth\AuthServiceProvider)->verifyToken($token);
    }
}

if ( ! function_exists('console_log') ) {
    function console_log(mixed ...$args) 
    {
        \Clicalmani\Foundation\Support\Facades\Log::debug( ...$args );
    }
}

if ( ! function_exists('config') ) {
    function config(?string $key = null, $default = null) : mixed
    {
        return \Clicalmani\Foundation\Support\Facades\Config::get($key, $default);
    }
}

if ( ! function_exists('abort') ) {
    function abort(int $status_code) : never
    {
        response()->sendStatus($status_code);
    }
}

if (! function_exists('get_data')) {
    /**
     * Get an item from an array or object using "dot" notation.
     *
     * @param  mixed  $target
     * @param  string|array|int|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    function get_data($target, $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);
        
        foreach ($key as $i => $segment) {
            unset($key[$i]);
            
            if (is_null($segment)) {
                return $target;
            }

            if ($segment === '*') {
                if ($target instanceof Collection) {
                    $target = $target->all();
                } elseif (! is_iterable($target)) {
                    return value($default);
                }

                $result = [];

                foreach ($target as $item) {
                    $result[] = get_data($item, $key);
                }

                return in_array('*', $key) ? Arr::collapse($result) : $result;
            }

            $segment = match ($segment) {
                '\*' => '*',
                '\{first}' => '{first}',
                '{first}' => array_key_first(is_array($target) ? $target : (new Collection($target))->all()),
                '\{last}' => '{last}',
                '{last}' => array_key_last(is_array($target) ? $target : (new Collection($target))->all()),
                default => $segment,
            };
            
            if (Arr::accessible($target) && Arr::exists($target, $segment)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return value($default);
            }
        }
        
        return $target;
    }
}

function session(?string $name = null, mixed $value = null) {
    return new \Clicalmani\Foundation\Http\Session($name, $value);
}

if (!function_exists('auth')) {
    function auth()
    {
        return \Clicalmani\Foundation\Http\Request::current()->user();
    }
}