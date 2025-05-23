<?php
namespace Clicalmani\Foundation\Http\Client\Sample;

/**
 * Class ProductionEnvironment
 * 
 * @package Clicalmani\Foundation
 * @author @Clicalmani\Foundation
 */
class ProductionEnvironment extends PDMSEnvironment
{
    /**
     * Constructor
     * 
     * @param string $clientId
     * @param string $clientSecret
     */
    public function __construct(string $clientId, string $clientSecret)
    {
        parent::__construct($clientId, $clientSecret);
    }

    /**
     * Base url
     * 
     * @return string
     */
    public function baseUrl() : string
    {
        return "https://api.paypal.com";
    }
}
