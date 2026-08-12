<?php
namespace Clicalmani\Foundation\Maker;

/**
 * Enum ServiceType
 * 
 * Defines the classification types for services registered within the framework's dependency injection container.
 * 
 * @package Clicalmani\Foundation\Maker
 * @author @clicalmani
 */
enum ServiceType: string
{
    /**
     * Internal framework services required for core execution (e.g., router, database, logger).
     */
    case Core = 'core';

    /**
     * Wildcard or dynamic services resolved based on class naming patterns and namespaces (e.g., `*.request`).
     */
    case Namespace = 'namespace';

    /**
     * User-defined applications services bound dynamically via custom Service Providers.
     */
    case Custom = 'custom';
}