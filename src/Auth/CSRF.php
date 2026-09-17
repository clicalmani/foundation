<?php
namespace Clicalmani\Core\Auth;

class CSRF
{
    /**
     * Generate CSRF token
     * 
     * @return string
     */
    public function getToken() : string
    {
        return bin2hex( EncryptionServiceProvider::hash( time() ) );
    }
}
