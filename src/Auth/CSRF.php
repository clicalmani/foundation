<?php
namespace Clicalmani\Foundation\Auth;

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
