<?php
namespace Clicalmani\Core\Auth;

class EncryptionServiceProvider 
{
	/**
	 * Password hashing
	 * 
	 * @var mixed
	 */
	private static $config;

	public function boot()
	{
		if (!static::$config) static::$config = require_once config_path('/hashing.php');
	}

	/**
	 * Generate a data hash
	 * 
	 * @param mixed $data
	 * @param ?string $method
	 * @return mixed
	 */
    public static function hash(mixed $data) : mixed
	{
		$config = static::$config;
		$method = $config['algo'] ?? 'sha256';
		$secret = env('APP_KEY', 'Tonka');

		return hash_hmac($method, (string) $data, $secret);
	}
    
	/**
	 * Create parameters hash
	 * 
	 * @param array $params
	 * @return string
	 */
    public static function createParametersHash(array $params) : string
	{
		$data = '';
		foreach ($params as $key => $value) {
			$data .= $key . $value;
		}

		return strtoupper(substr(self::hash($data), 0, static::$config['hash_length']));
	}

	/**
	 * Verify parameters
	 * 
	 * @return bool
	 */
    public static function verifyParameters() : bool
	{
		$route = current_route();
		if (!$route) return true;

		$param = self::hashParameter();
		if (!$param) return true;

		$params = $_REQUEST;
		unset($params[$param]);

		// Recalcule la clé à partir des MÊMES données que celles utilisées à l'émission
		$key = self::storageKey($route, $params);

		$request_hash = cookie()->get($key) ?? session()->get($key) ?? ($_REQUEST[$param] ?? null);

		if (!$request_hash) return true; // aucun hash émis pour ce lien précis

		ksort($params);
		$hash = strtoupper(substr(self::hash(implode('', array_map(
			fn($k, $v) => $k . $v,
			array_keys($params),
			$params
		))), 0, static::$config['hash_length']));

		// logger()->info('verifyParameters', [
		// 	'params' => $params, 'request_hash' => $request_hash, 'computed' => $hash,
		// ]);

		session()->remove($key);
		cookie($key)->delete();

		return hash_equals($request_hash, $hash);
	}

	/**
	 * Create iv
	 * 
	 * @return string
	 */
	public static function iv() : string
	{
		return substr( hash(static::$config['algo'], env('APP_KEY')), 0, static::$config['iv_length']);
	}
	
	/**
	 * Openssl Encrypt or decrypt a string
	 * 
	 * @param string $action
	 * @param string $string
	 * @return mixed
	 */
    public static function opensslED(string $action, string $string) : mixed
	{
	
		$output = false;
		$encrypt_method = static::$config['cipher'];
		
		// hash
		$key = hash(static::$config['algo'], $_ENV['APP_KEY']);
		
		// iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
		$iv = self::iv();

		if ( $action == 'encrypt' ) {
			$output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
			$output = base64_encode($output);
		} else if( $action == 'decrypt' ) {
			$output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
		}

		return $output;
	}
    
	/**
	 * Encrypt a value
	 * 
	 * @param string $value
	 * @return mixed
	 */
    public static function encrypt(string $value) { return self::opensslED('encrypt', $value); }

	/**
	 * Decrypt an encrypted value
	 * 
	 * @param string $value
	 * @return mixed
	 */
    public static function decrypt(string $value) : mixed { return self::opensslED('decrypt', $value); }

	/**
	 * PHP built-in password_hash wrapper
	 * 
	 * @param string $str
	 * @return string
	 */
	public static function password(string $str) : string
	{
		$config = static::$config;

		switch($config['driver']) {
			case 'bcrypt': 
				$__func = fn($str) => password_hash($str, PASSWORD_BCRYPT, ['cost' => @ $config['bcrypt']['cost'] ?? PASSWORD_BCRYPT_DEFAULT_COST]);
				break;

			case 'argon': 
				if ($config['argon']['2i']) $algo = PASSWORD_ARGON2I;
				else $algo = PASSWORD_ARGON2ID;

				$__func = fn($str) => password_hash(
					$str, 
					$algo, 
					[
						'memory' => @ $config['argon']['memory'] ?? PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
						'threads' => @ $config['argon']['threads'] ?? PASSWORD_ARGON2_DEFAULT_THREADS,
						'time' => @ $config['argon']['time'] ?? PASSWORD_ARGON2_DEFAULT_TIME_COST
					]);
				break;

			default:
				$__func = fn($str) => password_hash($str, PASSWORD_DEFAULT);
				break;
		}

		return $__func($str);
	}

	/**
	 * Returns hash parameter name
	 * 
	 * @return string|null
	 */
	public static function hashParameter() : string|null
	{
		return static::$config['hash_parameter'] ?? null;
	}

	public static function storageKey(\Clicalmani\Routing\Route $route, array $params) : string
	{
		ksort($params);
		$signature = $route->uri() . '|' . http_build_query($params);
		return base64_encode(self::encrypt($signature));
	}
}
