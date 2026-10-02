<?php
namespace Clicalmani\Core\Routing\Exceptions;

use App\Providers\RouteServiceProvider;

/**
 * Class RouteNotFoundException
 * 
 * @package Clicalmani\Route
 * @author @clicalmani
 */
class RouteNotFoundException extends \Exception 
{
	public function __construct(?string $route = ''){
		parent::__construct("Route $route Not Found");
		
		/**
		 * Render asset
		 */
		if ( file_exists( root_path('/public/' . $_SERVER['REQUEST_URI']) ) ) {
			header('Location: /public/' . $_SERVER['REQUEST_URI']); exit;
		}
		
		/**
		 * Render response
		 */
		else {
			// $tps = RouteServiceProvider::getProvidedTPS();
			// if ($serviceClass = $tps['404'] ?? null) {
			// 	RouteServiceProvider::resolveService('404', $serviceClass);
			// 	die(app()->response);
			// } else 
			response()->notFound();
		}
	}
}
