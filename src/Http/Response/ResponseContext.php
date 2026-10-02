<?php 
namespace Clicalmani\Core\Http\Response;

use Clicalmani\Core\Http\RedirectInterface;
use Clicalmani\Core\Http\ResponseInterface;
use Clicalmani\Core\Resources\ViewInterface;

/**
 * Enumeration of supported response contexts.
 * 
 * Each case knows how to detect itself from a Request
 * and can be used as a discriminant for the resolver.
 */
enum ResponseContext: string
{
    case INERTIA = 'inertia';
    case JSON    = 'json';
    case VIEW    = 'view';
    case HTML    = 'html';

    /**
     * Detect the response context from the incoming request.
     */
    public static function fromResponse(\Psr\Http\Message\ResponseInterface|\Clicalmani\Core\Http\ResponseInterface|\Clicalmani\Core\Http\RedirectInterface $response): self
    {
        if ($response instanceof RedirectInterface) {
            die($response);
        }

        return match (true) {
            $response === null => self::HTML,
            $response instanceof \Inertia\Response => self::INERTIA,
            $response instanceof ViewInterface => self::VIEW,
            $response instanceof ResponseInterface => self::JSON,
            default => self::HTML
        };
    }
}