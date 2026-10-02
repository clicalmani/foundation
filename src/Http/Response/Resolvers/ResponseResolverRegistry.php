<?php 
namespace Clicalmani\Core\Http\Response\Resolvers;

use Clicalmani\Core\Http\Response\ResponseContext;

class ResponseResolverRegistry
{
    /** @var array<string, ResponseResolver> */
    protected array $resolvers = [];

    public function register(ResponseResolver $resolver): static
    {
        $this->resolvers[$resolver->context()->value] = $resolver;
        return $this;
    }

    public function for(ResponseContext $context): ResponseResolver
    {
        return $this->resolvers[$context->value]
            ?? $this->resolvers[ResponseContext::HTML->value];
    }

    public function all(): array
    {
        return $this->resolvers;
    }

    public function init(array $resolvers = []): void
    {
        foreach ($resolvers as $resolver) {
            $this->register(new $resolver);
        }
    }
}