<?php
namespace Clicalmani\Foundation\Providers;

abstract class AuthServiceProvider extends ServiceProvider
{
	/**
	 * Get user authentication class
	 * 
	 * @return mixed
	 */
    public static function userAuthenticator() : mixed
	{
		return @ static::$kernel['auth']['user'];
	}
}
