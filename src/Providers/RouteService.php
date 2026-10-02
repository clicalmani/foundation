<?php 
namespace Clicalmani\Core\Providers;

use Clicalmani\Core\Http\Redirectable;
use Clicalmani\Core\Http\Response\Resolvers\ResponseResolverRegistry;
use Clicalmani\Core\Http\Response\ResponseContext;

/**
 * Class RouteService
 * 
 * Provisioning contract strategies for intercepting or redirecting browser traffic.
 * 
 * @package Clicalmani\Core\Providers
 * @author @clicalmani
 */
abstract class RouteService implements Redirectable
{
    /**
     * Active route model entity matching current application state.
     * 
     * @var \Clicalmani\Routing\Route|false
     */
    protected \Clicalmani\Routing\Route|false $route;

    protected mixed $response;

    /**
     * HTTP status code for the redirect.
     */
    protected int $status = 302;

    /**
     * Detected response context.
     */
    protected ResponseContext $context;

    protected ?string $routeName = null;

    /**
     * Resolver registry (injected or resolved from container).
     */
    protected ResponseResolverRegistry $registry;

    /**
     * RouteService constructor.
     * Captures and binds the active HTTP global request context state.
     */
    public function __construct()
    {
        $this->response = app()->response;

        if (isset($this->routeName)) {
            /** @var Route */
            $route = $route = \Clicalmani\Core\Support\Facades\Route::findByName($this->routeName);
            /** @var \Clicalmani\Core\Http\ResponseInterface */
            $this->response = call_user_func($route->action);
        }

        $this->context  = ResponseContext::fromResponse($this->response);
        $this->registry = app()->getContainer()->get('resolvers');
    }

    /**
     * Aborts the operational route matching context loop immediately.
     * 
     * @return void
     */
    protected function abort(): void
    {
        $this->route = false;
    }

    protected function redirectTo(string $routeName, int $status = 302, array $params = []): static
    {
        $this->status   = $status;
        $this->response = $this->registry
                            ->for($this->context)
                            ->redirectToRoute($routeName, $status, $params);
        return $this;
    }

    protected function redirectToUrl(string $url, int $status = 302) : static
    {
        $this->status   = $status;
        $this->response = $this->registry
                            ->for($this->context)
                            ->redirectToUrl($url, $status);
        return $this;
    }

    protected function render(string $view, array $data = [], int $status = 503): static
    {
        $this->status   = $status;
        $this->response = $this->registry
                            ->for($this->context)
                            ->render($view, $data, $status);
        return $this;
    }

    protected function abortWith(int $status, string $message = ''): static
    {
        $this->status   = $status;
        $this->response = $this->registry
                            ->for($this->context)
                            ->abort($status, $message);
        return $this;
    }
    
    public function getResponse(): mixed
    {
        return $this->response;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getContext(): ResponseContext
    {
        return $this->context;
    }
}