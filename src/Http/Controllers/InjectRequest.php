<?php
namespace Clicalmani\Foundation\Http\Controllers;

use Clicalmani\Foundation\Http\Request;
use Clicalmani\Validation\AsValidator;

class InjectRequest extends InjectionLocator
{
    public function handle(): ?object
    {
        if (is_subclass_of($this->class, \Clicalmani\Foundation\Http\Request::class) ||
					$this->class === \Clicalmani\Foundation\Http\Request::class || $this->class === \Clicalmani\Foundation\Http\RequestInterface::class) {

			$this->createInstance();

            $request = isConsoleMode() ? Request::current() : new Request; // Fallback to default request
		
            $data = $request->all();
            /** @var \Clicalmani\Foundation\Http\Request */
            $request = $this->instance;
            $request->extend($data);

            if ($this->reflector instanceof MethodReflector) {
                if ($attribute = (new \ReflectionMethod($this->reflector->getClass(), $this->reflector->getName()))->getAttributes(AsValidator::class)) {
                    $request->merge($attribute[0]->newInstance()->args);
                }
            }
            
            Request::current($request);
			self::validateRequest($request);

            return $request;
        }
        
        return null;
    }

    /**
	 * Validate request
	 * 
	 * @param \Clicalmani\Foundation\Http\Request
	 * @return mixed
	 */
	public static function validateRequest(Request $request) : mixed
	{
		if (method_exists($request, 'authorize')) {
			if (false == $request->authorize()) {
				response()->sendStatus(403);		// Forbiden
				die();
			}
		}

		if (method_exists($request, 'prepareForValidation')) {
			$request->prepareForValidation();                    // Call prepareForValidation method
		}
		
		if (method_exists($request, 'signatures')) {
			$request->signatures();  // Set parameters signatures
		}

		return null;
	}
}