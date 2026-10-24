<?php
namespace Clicalmani\Core\Http;

use Clicalmani\Core\Http\Controllers\MethodReflector;
use Clicalmani\Core\Support\Facades\RequestController;
use Clicalmani\Core\Support\Facades\Route;
use Clicalmani\Psr\Headers;
use Clicalmani\Psr\NonBufferedBody;
use Clicalmani\Routing\Registry;
use Inertia\Inertia;

class Redirect extends Response implements RedirectInterface
{
    protected string $uri = '/';

    protected int $status = 302;

    protected bool $external = false;

    protected mixed $response = null;

    protected string $message = '';

    protected string $message_name = '';

    public function __construct(string $uri = '/', int $status = 302)
    {
        $this->uri = $uri;

        $this->status = $this->filterStatus($status);
        $this->headers = \Clicalmani\Psr\Headers::fromArray([]);
        $this->body = \Clicalmani\Psr\Stream::create();

        $this->header('Location', $uri);

        if (Request::current()?->hasHeader('X-Inertia') && 
            in_array((new Request)->getMethod(), ['put', 'patch', 'delete'])) {
            $this->status = 303;
        }
    }

    public function with(string $status, string $value): RedirectInterface
    {
        session($status, $value)->set();
        return $this;
    }

    public function withError(string $message = ''): RedirectInterface
    {
        return $this->with('error', $message);
    }

    public function withErrors(array $errors, ?string $bag = null): RedirectInterface
    {
        $errors = array_map(fn($e) => is_array($e) ? $e[0] : $e, $errors);
        session('errors', json_encode($bag ? [$bag => $errors] : $errors))->set();
        return $this;
    }

    public function withSuccess(string $message = ''): RedirectInterface
    {
        return $this->with('success', $message);
    }
    
    public function withInput(array $input): RedirectInterface
    {
        session()->flush($input);
        return $this;
    }

    public function status(int $code): ResponseInterface
    {
        $this->status = $code;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->status;
    }

    public function getLocation(): string
    {
        return $this->uri;
    }

    public function back(): RedirectInterface
    {
        if (NULL !== $uri = \Clicalmani\Core\Providers\Config\RedirectService::traceBack()) {
            $this->uri = $uri;
            return $this;
        }

        $route = Registry::currentRoute();
        
        if ($route->isGettable()) {
            $this->uri = $route->uri;
            $parameters = [];
            
            /** @var \Clicalmani\Routing\Segment */
            foreach ($route as $segment) {
                $parameters[$segment->name] = $segment->value;
            }

            $this->uri = route($this->uri, ...$parameters);
        }
        
        return $this;
    }

    public function route(mixed ...$args) : RedirectInterface
    {
        $this->uri = route( ...$args );
        return $this;
    }

    public function to(string $url) : RedirectInterface
    {
        $this->uri = $url;
        return $this;
    }

    public function action(string|array $value): RedirectInterface
    {
        if ($action = \Clicalmani\Routing\action($value)) {
            return RequestController::invokeMethod(new MethodReflector( new \ReflectionMethod($action[0], $action[1])));
        }

        throw new \Exception(
            sprintf("Action not found for the given value %s", is_string($value) ? $value : json_encode($value))
        );
    }

    public function away(?string $url = '/'): RedirectInterface
    {
        $this->uri = $url;
        $this->external = true;
        return $this;
    }

    public function __toString()
    {
        $uri = $this->uri === '' ? '/' : $this->uri;

        if (!str_starts_with($uri, '/') && !str_starts_with($uri, 'http')) {
            $uri = '/' . $uri;
        }

        if ($this->message_name) {
            $this->uri .= (strpos($uri, '?') === false ? '?' : '&') . $this->message_name . '=' . $this->message;
        }
        
        if ($this->external && Request::current()?->hasHeader('X-Inertia')) {
            return Inertia::location($uri);
        }

        if (app()->response instanceof \Clicalmani\Core\Http\Redirect) {
            return (string) app()->response;
        }

        app()->response?->status($this->status);
        
        app()->response?->withBody(new NonBufferedBody)
            ->header('Location', $this->uri === '' ? '/': $uri)
            ->send();

        return '';
    }
}