<?php
namespace Clicalmani\Foundation\Providers;

interface ServiceProviderInterface
{
    /**
     * (non-PHPDoc)
     * @override
     */
    public function boot(): void;

    /**
     * (non-PHPDoc)
     * @override
     */
    public function register(): void;
}