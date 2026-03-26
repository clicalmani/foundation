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
		return config('bootstrap.auth.user');
	}
}
