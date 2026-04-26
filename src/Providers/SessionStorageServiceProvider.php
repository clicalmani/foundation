<?php
namespace Clicalmani\Foundation\Providers;

use Clicalmani\Foundation\Support\Facades\Route;

/**
 * SessionStorageServiceProvider Class
 * 
 * @package clicalmani/fundation 
 * @author @clicalmani
 */
abstract class SessionStorageServiceProvider extends ServiceProvider
{
    /**
     * Session driver
     * 
     * This option controls the default session "driver" that will be used on
     * requests. By default, we will use the lightweight native driver but
     * you may specify any of the other wonderful drivers provided here.
     *
     * Supported: "file", "cookie", "database", "apc", "memcached", "redis", "dynamodb", "array"
     * 
     * @var string
     */
    protected static $driver = \Clicalmani\Foundation\Http\Session\FileSessionHandler::class;

    /**
     * Here you may specify the number of seconds that you wish the session
     * to be allowed to remain idle before it expires.
     * 
     * @var int
     */
    protected static $lifetime = 300;

    /**
     * Here you may specify the maximum number of seconds that you wish the session
     * should be idle.
     * 
     * @var int
     */
    protected static $max_lifetime = 900;

    /**
     * If you want session to immediately expire on the browser closing, set that option.
     * 
     * @var bool
     */
    protected static $expire_on_close = false;

    /**
     * This option allows you to easily specify that all of your session data
     * should be encrypted before it is stored. All encryption will be run
     * automatically by and you can use the Session like normal.
     * 
     * @var bool
     */
    protected static $encrypt = false;

    /**
     * When using the "database" or "redis" session drivers, you may specify a
     * connection that should be used to manage these sessions. This should
     * correspond to a connection in your database configuration options.
     * 
     * @var string
     */
    protected static $connection = 'mysql';

    /**
     * When using the "database" session driver, you may specify the table we
     * should use to manage the sessions. Of course, a sensible default is
     * provided for you; however, you are free to change this as needed.
     * 
     * @var string
     */
    protected static $table = 'sessions';

    /**
     * Some session drivers must manually sweep their storage location to get
     * rid of old sessions from storage. Here are the chances that it will
     * happen on a given request. By default, the odds are 2 out of 100.
     * 
     * @var array
     */
    protected static $lotery = [1, 100];

    /**
     * Here you may change the cookie settings used to identify a session
     * instance by ID.
     * 
     * @var array
     */
    protected static $cookie = [
                                'name' => '_SESSION_COOKIE',
                                'path' => '/',
                                'domain' => '',
                                'secure' => false,
                                'http_only' => false,
                                'samesite' => true
                            ];

    private $session_dir;

    private const __DEFAULT_KEYS = [
        'LAST_ACTIVITY' => 'f3in64jecu0k9sdovwm75ayh8pbz12gxqrtl__LAST_ACTIVITY',
        'IDLE' => '6o3w8hiunfqlms91kb5t7d2yvrapzejxg04c__IDLE',
        'TRACE_BACK' => 'zjfsr2nyu51elg4mop9v6wt3k8iq70cahbdx__TRACE_BACK'
    ];

    public function __construct()
    {
        $this->session_dir = dirname( __DIR__, 5) . '/storage/framework/sessions';
        
        if (!is_dir($this->session_dir)) {
            mkdir($this->session_dir, 0777, true);
        }

        $config = [
            'session.save_handler' => 'files',
            'session.save_path' => realpath($this->session_dir),
            'session.use_cookies' => 1,
            'session.name' => static::$cookie['name'],
            'session.auto_start' => 0,
            'session.cookie_lifetime' => static::$max_lifetime,
            'session.cookie_path' => static::$cookie['path'],
            'session.cookie_domain' => static::$cookie['domain'],
            'session.cookie_samesite' => (int)static::$cookie['samesite'],
            'session.cookie_secure' => (int)static::$cookie['secure'],
            'session.cookie_httponly' => (int)static::$cookie['http_only'],
            'session.serialize_handler' => 'php',
            'session.gc_probability' => static::$lotery[0],
            'session.gc_divisor' => static::$lotery[1],
            'session.gc_maxlifetime' => static::$max_lifetime,
            'session.cache_limiter' => 'nocache',
            'session.use_strict_mode' => 1
        ];

        if (FALSE === isConsoleMode())
            foreach ($config as $k => $v) ini_set($k, $v);
    }

    public function boot(): void
    {
        if (FALSE === isConsoleMode() && FALSE === Route::isApi()) {
            // Start a session
            if (session_status() === PHP_SESSION_NONE) {
                
                session_set_save_handler(
                    new static::$driver(static::$encrypt, [
                        'table' => env('DB_TABLE_PREFIX') . static::$table,
                        'driver' => static::$connection
                    ]), 
                    true
                );
                register_shutdown_function('session_write_close');
                session_start();
                
                // 1. Initialization on first startup
                if ( ! isset($_SESSION[self::__DEFAULT_KEYS['IDLE']])) {
                    $_SESSION[self::__DEFAULT_KEYS['IDLE']] = time();
                }
                if ( ! isset($_SESSION[self::__DEFAULT_KEYS['LAST_ACTIVITY']])) {
                    $_SESSION[self::__DEFAULT_KEYS['LAST_ACTIVITY']] = time();
                }

                setcookie(
                    static::$cookie['name'],
                    session_id(),
                    time() + static::$max_lifetime,
                    static::$cookie['path'],
                    static::$cookie['domain'],
                    static::$cookie['secure'],
                    static::$cookie['http_only']
                );
                
                // Inactivity is calculated only once to avoid multiple system calls
                $inactive_time = time() - $_SESSION[self::__DEFAULT_KEYS['LAST_ACTIVITY']];

                // 2. MAX LIFETIME Verification (Maximum Wait Time / High Inactivity)
                if ($inactive_time > static::$max_lifetime) {
                    // The user has been away for too long, we destroy everything
                    session_unset();
                    session_destroy();
                    
                    // VERY IMPORTANT: We stop the script here! 
                    // Otherwise, the code below will attempt to work on a destroyed session.
                    return; 
                }
                
                // 3. LIFETIME check (Light inactivity -> ID regeneration)
                if ($inactive_time > static::$lifetime) {
                    // The user has been inactive for 'lifetime' seconds; the ID is regenerated for security reasons.
                    session_regenerate_id(true);
                }
                
                // 4. ACTIVITY UPDATE
                // If we arrive here, it means the user has not exceeded max_lifetime.
                // The counter is reset to 0 for the next request.
                $_SESSION[self::__DEFAULT_KEYS['LAST_ACTIVITY']] = time();
                $_SESSION[self::__DEFAULT_KEYS['IDLE']] = time();
            }
        }
    }

    /**
     * Returns session table
     * 
     * @return string
     */
    public static function getTable() : string
    {
        return static::$table;
    }

    /**
     * Returns session driver
     * 
     * @return string
     */
    public static function getDriver() : string
    {
        return static::$driver;
    }

    public static function backTraceIndex() : string
    {
        return self::__DEFAULT_KEYS['TRACE_BACK'];
    }
}
