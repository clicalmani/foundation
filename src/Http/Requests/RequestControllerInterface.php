<?php
namespace Clicalmani\Core\Http\Requests;

interface RequestControllerInterface
{
    /**
	 * Render request response
	 * 
	 * @return never
	 */
	public function render() : never;

    /**
	 * Run route action
	 * 
	 * @param mixed $controllerClass
	 * @param mixed $method
	 * @return mixed
	 */
	public function invokeMethod($controllerClass, $method = '__invoke') : mixed;

    /**
	 * Controller test
	 * 
	 * @param string $action Test action
	 * @return \Clicalmani\Core\Test\Controllers\TestController
	 */
	public function test(string $action) : \Clicalmani\Core\Test\Controllers\TestController;
}