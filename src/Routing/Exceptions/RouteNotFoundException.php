<?php
namespace Clicalmani\Foundation\Routing\Exceptions;

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
			response()->notFound();
		}
	}
}
