<?php
namespace Clicalmani\Core\Http\Controllers;

use Clicalmani\Core\Http\Request;
use Clicalmani\Core\Http\RequestInterface;
use Clicalmani\Validation\AsValidator;

class InjectRequest extends InjectionLocator
{
    public function handle(): ?object
    {
        if (!is_subclass_of($this->class, Request::class) 
            && $this->class !== Request::class 
            && $this->class !== RequestInterface::class) {
            return null;
        }

        $this->createInstance();
        $current = Request::current();
        $data = $current->all();

        /** @var Request $request */
        $request = $this->instance;
        $request->extend($data);

        if ($this->reflector instanceof MethodReflector) {
            $method = new \ReflectionMethod(
                $this->reflector->getClass(),
                $this->reflector->getName()
            );
            if ($attrs = $method->getAttributes(AsValidator::class)) {
                $request->merge($attrs[0]->newInstance()->args);
            }
        }
        
        Request::current($request);
        self::validateRequest($request);
        
        return $request;
    }

    /**
	 * Validate request
	 * 
	 * @param \Clicalmani\Core\Http\Request
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
			$request->prepareForValidation(); // Call prepareForValidation method
		}
		
		if (method_exists($request, 'signatures')) {
			$request->signatures();  // Set parameters signatures
		}

		if (method_exists($request, 'messages')) {
			$messages = $request->messages();  // Set custom error messages
			$sigs = $request->getSignatures();

			foreach ($messages as $field => $message) {
				if (isset($sigs[$field])) {
					$sigs[$field] .= '|message:' . $message;
				}
			}

			$request->setSignatures($sigs);
		}

		return null;
	}
}