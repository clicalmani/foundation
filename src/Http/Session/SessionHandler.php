<?php
namespace Clicalmani\Foundation\Http\Session;

use Clicalmani\Foundation\Auth\EncryptionServiceProvider;

/**
 * SessionHandler Class
 * 
 * @package clicalmani/fundation
 * @author @clicalmani
 */
abstract class SessionHandler implements \SessionHandlerInterface
{
    protected bool $encrypt = false;
    protected string $table = '';
    protected static $instance;

    public static function getInstance()
    {
        if (static::$instance) return static::$instance;

        return static::$instance = new self;
    }

    public function __construct(?bool $encrypt = false, ?array $flags = [])
    {
        $this->encrypt = $encrypt;
        $this->table = $flags['table'];
    }

    /**
     * Encrypt data
     * 
     * @param string $data
     * @return string
     */
    protected function encrypt(string $data) : string
    {
        return $this->encrypt ? EncryptionServiceProvider::encrypt($data): base64_encode($data);
    }

    /**
     * Decrypt data
     * 
     * @param string $data
     * @return string
     */
    protected function decrypt(string $data) : string
    {
        return $this->encrypt ? EncryptionServiceProvider::decrypt($data): base64_decode($data);
    }

    public static function getIdPrefix()
    {
        return 'tonka-';
    }

    /**
     * Get CSRF token
     * 
     * @return string
     */
    public function token()
    {
        return csrf_token();
    }

    /**
     * Get all session values
     * 
     * @return array
     */
    public function all() : array
    {
        return $_SESSION;
    }

    /**
     * Flush all session values
     * 
     * @param ?string $key
     * @param mixed $data
     * @return void
     */
    public function flush(?string $key = null, mixed $data) : void
    {
        if ( !isset($key) ) $_SESSION = [];
        else {
            $_SESSION = [];
            $_SESSION[$key] = $data;
        }
    }

    /**
     * Get session value
     * 
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        return @$_SESSION[$name] ?? null;
    }

    /**
     * Set session value
     * 
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set(string $name, mixed $value)
    {
        $_SESSION[$name] = $value;
    }
}
