<?php 
namespace Clicalmani\Core\Http\Response\Resolvers;

use Clicalmani\Core\Http\Response\ResponseContext;

class ViewResolver implements ResponseResolver
{
    public function context(): ResponseContext
    {
        return ResponseContext::VIEW;
    }

    public function redirectToRoute(string $routeName, int $status, array $params = []): mixed
    {
        return redirect()->route($routeName, $params)->status($status);
    }

    public function redirectToUrl(string $url, int $status): mixed
    {
        return redirect()->to($url)->status($status);
    }

    public function render(string $view, array $data, int $status): mixed
    {
        return response('', $status)->view($view, $data);
    }

    public function abort(int $status, string $message): mixed
    {
        return response($message, $status);
    }
}