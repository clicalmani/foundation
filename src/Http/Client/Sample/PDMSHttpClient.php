<?php
namespace Clicalmani\Foundation\Http\Client\Sample;

use Clicalmani\Foundation\Http\Client\Core\UserAgent;
use Clicalmani\Foundation\Http\Client\HttpClient;

/**
 * Class PDMSHttpClient
 * 
 * @package Clicalmani\Foundation
 * @author @Clicalmani\Foundation
 */
class PDMSHttpClient extends HttpClient
{
    /**
     * Auth injector
     * 
     * @var AuthorizationInjector
     */
    public $authInjector;

    /**
     * Constructor
     * 
     * @param PDMSEnvironment $environment
     */
    public function __construct(PDMSEnvironment $environment)
    {
        parent::__construct($environment);
        $this->authInjector = new AuthorizationInjector($this, $environment);
        $this->addInjector($this->authInjector);
    }

    /**
     * Get user agent
     * 
     * @return string
     */
    public function userAgent() : string
    {
        return UserAgent::getValue();
    }
}

