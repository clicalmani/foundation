<?php
namespace Clicalmani\Foundation\Messenger;

use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus as MessengerMessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

class MessageBus extends MessengerMessageBus implements MessageBusInterface
{
    /**
     * @param iterable<mixed, MiddlewareInterface> $middlewareHandlers
     */
    public function __construct()
    {
        parent::__construct([
            new HandleMessageMiddleware(new HandlersLocator(
                $this->getHandlers()
            ))
        ]);
    }

    private function getHandlers()
    {
        $result = [];
        
        foreach (app()->config('bootstrap.messages') as $messageClas => $handlers) {
            foreach ($handlers as $i => $handler) {

                if (! $handler instanceof \Closure) {
                    $handlers[$i] = new $handler;
                    $r = new \ReflectionClass($handler);
                }
            }

            $result[$messageClas] = $handlers;
        }

        return $result;
    }
}