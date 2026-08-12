<?php
namespace Clicalmani\Foundation\Providers;

use Override;

/**
 * Class ValidationServiceProvider
 * 
 * Boots and registers the global request validation layer. Consolidates default core framework 
 * validation constraint mechanics with user-configured custom rule structures, provisioning 
 * global lookup strategies for input sanitization runs.
 * 
 * @package Clicalmani\Foundation\Providers
 * @author @clicalmani
 */
class ValidationServiceProvider extends ServiceProvider
{
    /**
     * Map collection holding qualified target paths of registered rule validators.
     * 
     * @var array<int, string>
     */
    protected static array $rules = [];

    /**
     * Bootstraps validation configuration profiles and extends structural mapping criteria 
     * using default kernel definitions.
     * 
     * @return void
     */
    #[Override]
    public function boot(): void
    {
        $rules = collection(\Clicalmani\Foundation\Support\Facades\Config::http('custom_rules') ?? [])
                    ->unique();
                    
        $arguments = collection($rules)->map(fn(string $validator) => $validator::getArgument());
        
        // Append core system validations without clashing on overriden custom constraint tags
        $rules->extends(
            (new \Clicalmani\Validation\Kernel())->validators(), 
            fn(string $validator) => !$arguments->has($validator::getArgument())
        );

        self::$rules = $rules->toArray();
    }

    /**
     * Verifies if a prospective request argument tag is registered as an active validator directive.
     * 
     * @param string $argument Distinct argument keyword tag signature evaluated (e.g., 'required', 'email').
     * @return bool True if the key signature is structurally recognized, false otherwise.
     */
    public static function seemsValidator(string $argument): bool
    {
        return (bool) collection(static::getValidators())->find(
            fn(string $validator) => $argument === $validator::getArgument()
        );
    }

    /**
     * Resolves a distinct rule validator class reference mapping to a specific argument keyword tag.
     * 
     * @param string $argument Distinct argument keyword tag signature evaluated.
     * @return string|null Full class path target reference string on success, null on matching failure.
     */
    public static function getValidator(string $argument): ?string
    {
        return collection(self::$rules)->find(
            fn(string $class) => $argument === $class::getArgument()
        );
    }

    /**
     * Retrieves the comprehensive runtime listing of all active structural validation rules.
     * 
     * @return array<int, string> Collection array listing registered class pathways.
     */
    public static function getValidators(): array
    {
        return self::$rules;
    }

    /**
     * Appends a dynamic validation check rule definition cleanly into the global collection container.
     * 
     * @param string $rule Qualified target class path string of the custom validation rule.
     * @return void
     */
    public static function addRule(string $rule): void
    {
        self::$rules[] = $rule;
    }
}