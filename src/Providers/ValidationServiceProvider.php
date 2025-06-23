<?php
namespace Clicalmani\Foundation\Providers;

use Clicalmani\Foundation\Collection\Collection;
use Clicalmani\Foundation\Support\Facades\Config;

class ValidationServiceProvider extends ServiceProvider
{
    /**
     * Input validators
     * 
     * @var array
     */
    protected static $validators = [];

	/**
	 * Bootstrap input validators
	 * 
	 * @return void
	 */
    public function boot() : void
	{
        $rules = collection(\Clicalmani\Foundation\Support\Facades\Config::http('custom_rules') ?? [])
                                ->unique();
        $arguments = collection($rules)->map(fn(string $validator) => $validator::getArgument());
        
        $rules->extends(
            ( new \Clicalmani\Validation\Kernel )->validators(), 
            fn(string $validator) => !$arguments->has($validator::getArgument())
        );

        self::$validators = $rules->toArray();
	}

    /**
     * Verify whether a validator argument is valid.
     * 
     * @param string $argument
     * @return bool
     */
    public static function seemsValidator(string $argument) : bool
    {
        return !!collection(static::getValidators())->find(fn(string $validator) => $argument === $validator::getArgument());
    }

    /**
     * Get a validator by its argument.
     * 
     * @param string $argument
     * @return mixed Validator class on success, NULL on failure.
     */
    public static function getValidator(string $argument) : ?string
    {
        return collection(self::$validators)
                ->find(function(string $class) use($argument) {
                    return $argument === $class::getArgument();
                });
    }

    /**
     * Returns validators
     * 
     * @return array
     */
    public static function getValidators() : array
    {
        return self::$validators;
    }
}
