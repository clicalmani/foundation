<?php 
namespace Clicalmani\Core\Http\Response\Resolvers;

use Clicalmani\Core\Http\Response\ResponseContext;
use Inertia\Inertia;

class InertiaResolver implements ResponseResolver
{
    public function context(): ResponseContext
    {
        return ResponseContext::INERTIA;
    }
    
    public function redirectToRoute(string $routeName, int $status, array $params = []): mixed
    {
        // Internal Inertia visit — keep SPA navigation.
        return response('', $status)
                ->withHeaders(['X-Inertia' => 'true'])
                ->redirect()
                ->route($routeName, $params);
    }

    public function redirectToUrl(string $url, int $status): mixed
    {
        return redirect()->to($url, $status);
    }

    public function render(string $component, array $data, int $status): mixed
    {
        return inertia($component, $data)->status($status);
    }

    public function abort(int $status, string $message): mixed
    {
        return response('', $status)->json(['message' => $message])
            ->withHeaders(['X-Inertia' => 'true']);
    }
}