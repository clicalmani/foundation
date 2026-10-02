<?php 
namespace Clicalmani\Core\Http\Response\Resolvers;

use Clicalmani\Core\Http\Response\ResponseContext;

interface ResponseResolver
{
    /**
     * The context this resolver handles.
     */
    public function context(): ResponseContext;

    /**
     * Build a redirect response to a named route.
     */
    public function redirectToRoute(string $routeName, int $status, array $params = []): mixed;

    /**
     * Build a redirect response to a raw URL.
     */
    public function redirectToUrl(string $url, int $status): mixed;

    /**
     * Render a view (Blade or Inertia component) directly.
     */
    public function render(string $view, array $data, int $status): mixed;

    /**
     * Build a plain abort response.
     */
    public function abort(int $status, string $message): mixed;
}