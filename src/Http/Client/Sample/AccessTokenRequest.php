<?php
namespace Clicalmani\Foundation\Http\Client\Sample;

use Clicalmani\Foundation\Http\Client\HttpRequest;

class AccessTokenRequest extends HttpRequest
{
    /**
     * Constructor
     * 
     * @param PDMSEnvironment $environment
     */
    public function __construct(PDMSEnvironment $environment)
    {
        parent::__construct("/v1/oauth2/token", "POST");
        $this->headers["Authorization"] = "Basic " . $environment->authorizationString();
        $this->body = [
            "grant_type" => "client_credentials"
        ];
    }
}
