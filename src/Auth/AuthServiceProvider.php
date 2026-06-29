<?php
namespace Clicalmani\Foundation\Auth;

/**
 * AuthServiceProvider Class
 * 
 * @package Clicalmani\Foundation
 * @author @Clicalmani\Foundation
 */
class AuthServiceProvider
{
    private static $config;

    private $payload,    // JWT payload
            $secret,     // Encryption key
            $headers;    // Headers

    /**
     * Constructor
     * 
     * @param mixed $jti JWT ID claim
     */
    public function __construct(private mixed $jti = null)
    {
        if (!static::$config) $this->boot();
        $config = static::$config['tokens'] ?? null;
        
        /**
         * |--------------------------------------------------------
         * | Service initialization
         * |--------------------------------------------------------
         * 
         * Initialized in ServiceProvider
         */
        $this->headers = (object) [
            'alg' => $config['algo'],
            'typ' => $config['header']['type']
        ];
        $this->payload = [
            'iss' => env('APP_URL', ''), // Issuer claim
            'iat' => time(),             // Issued at claim
            'jti' => $this->jti,         // JWT ID claim
            'exp' => time() + ($config ? $config['expire']: 0) // Expiration time claim
        ];

        if (empty($_ENV['APP_KEY'])) {
            throw new \RuntimeException('APP_KEY environment variable is not set.');
        }
        $this->secret = $_ENV['APP_KEY'];
    }

    /**
     * Set JWT ID claim
     * 
     * @param mixed $new_jti
     * @return void
     */
    public function setJti(mixed $new_jti) : void
    {
        $this->jti = $new_jti;
        $this->payload = [
            'iss' => env('APP_URL', ''), // Issuer claim
            'iat' => time(),             // Issued at claim
            'jti' => $this->jti,         // JWT ID claim
            'exp' => time() + static::$config['tokens']['expire'] // Expiration time claim
        ];
    }

    /**
     * Set JWT expiration time claim
     * 
     * @param int $seconds
     * @return void
     */
    public function setExpiration(int $seconds) : void
    {
        $this->payload = [
            'iss' => env('APP_URL', ''), // Issuer claim
            'iat' => time(),             // Issued at claim
            'jti' => $this->jti,         // JWT ID claim
            'exp' => time() + $seconds   // Expiration time claim
        ];
    }

    /**
     * Generate token
     * 
     * @return string
     */
    public function generateToken() : string
    {
        $headers = $this->base64urlEncode(
            json_encode($this->headers)
        );
        $payload = $this->base64urlEncode(
            json_encode($this->payload)
        );
        $signature = $this->base64urlEncode(
            hash_hmac(
                static::$config['tokens']['algo'],
                "$headers.$payload",
                $this->secret,
                true
            )
        );

        return "$headers.$payload.$signature";
    }

    /**
     * Base 64 URL encode
     * 
     * @param string $url
     * @return string
     */
    private function base64urlEncode(string $url) : string
    {
        return rtrim(strtr(base64_encode($url), '+/', '-_'), '=');
    }

    /**
     * Verify token
     * 
     * @param string $token
     * @return mixed Payload if success, false if failure.
     */
    public function verifyToken(string $token) : mixed
    {
        if (!$token) {
            return false;
        }

        $parts = explode('.', $token);
        
        if ( count( $parts ) < 3 ) {
            return false;
        }

        $signature = $this->base64urlEncode(
            hash_hmac(
                static::$config['tokens']['algo'],
                "{$parts[0]}.{$parts[1]}",
                $this->secret,
                true
            )
        );
        $payload = json_decode(
            base64_decode(str_pad(strtr($parts[1], '-_', '+/'), strlen($parts[1]) % 4, '=', STR_PAD_RIGHT))
        );
        
        if (JSON_ERROR_NONE !== json_last_error()) {
            return false;
        }
        
        if ( $payload->exp > 0 && $payload->exp <= time() ) { // token expired
            return false;
        }
        
        if (!hash_equals($signature, $parts[2])) { // Invalid signature
            return false;
        }

        return $payload;
    }

    public function boot()
    {
        if ( is_file(config_path('/auth.php')) ) {
            static::$config = require config_path('/auth.php');
        }
    }
}
