<?php 
namespace Clicalmani\Core\Http\Response\Resolvers;

use Clicalmani\Core\Http\Response\ResponseContext;

class JsonResolver implements ResponseResolver
{
    public function context(): ResponseContext
    {
        return ResponseContext::JSON;
    }

    public function redirectToRoute(string $routeName, int $status, array $params = []): mixed
    {
        return response('', $status)->json(
            ['redirect' => route($routeName, $params)]
        );
    }

    public function redirectToUrl(string $url, int $status): mixed
    {
        return response('', $status)->json(['redirect' => $url]);
    }

    public function render(string $view, array $data, int $status): mixed
    {
        return response('', $status)->json(['view' => $view, 'data' => $data]);
    }

    public function abort(int $status, string $message): mixed
    {
        return response('', $status)->json(['message' => $message]);
    }
}