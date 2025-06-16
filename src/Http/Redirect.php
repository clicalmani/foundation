<?php
namespace Clicalmani\Foundation\Http;

use Clicalmani\Foundation\Http\Controllers\MethodReflector;
use Clicalmani\Foundation\Http\Requests\RequestController;
use Clicalmani\Psr7\NonBufferedBody;
use Clicalmani\Routing\Memory;
use Inertia\Inertia;

class Redirect implements RedirectInterface
{
    private string $uri = '/';

    private int $status = 302;

    private string $message = '';

    private string $message_name = '';

    public function __construct(string $uri = '/', int $status = 302)
    {
        $this->uri = $uri;
        $this->status = $status;

        if (Request::current()?->hasHeader('X-Inertia') && in_array((new Request)->getMethod(), ['put', 'patch', 'delete'])) {
            $this->status = 303;
        }
    }

    public function with(string $status, string $value): RedirectInterface
    {
        session($status, $value)->set();
        return $this;
    }

    public function status(int $code): RedirectInterface
    {
        $this->status = $code;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->status;
    }

    public function back(): RedirectInterface
    {
        $route = Memory::currentRoute();
        $this->uri = $route->uri;
        $parameters = [];
        
        /** @var \Clicalmani\Routing\Segment */
        foreach ($route as $segment) {
            $parameters[$segment->name] = $segment->value;
        }

        $this->uri = route($this->uri, ...$parameters);
        return $this;
    }

    public function route(mixed ...$args) : static
    {
        $this->uri = route( ...$args );
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

    public function away(string $url): RedirectInterface
    {
        $this->uri = $url;
        return $this;
    }

    public function __toString()
    {
        if ($this->message_name) $this->uri .= (strpos($this->uri, '?') === false ? '?' : '&') . $this->message_name . '=' . $this->message;
        
        if (Request::current()?->hasHeader('X-Inertia')) {
            return Inertia::location($this->uri);
        }
        
        (new Response)->withBody(new NonBufferedBody)
            ->status($this->status)
            ->header('Location', $this->uri)
            ->send();
    }
}