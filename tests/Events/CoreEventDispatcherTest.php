<?php

declare(strict_types=1);

namespace Clicalmani\Core\Tests\Events;

use Clicalmani\Core\Events\CoreEventDispatcher;
use Clicalmani\Core\Events\EventDispatcherDelegate;
use Clicalmani\Core\Tests\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Couvre CoreEventDispatcher : la chaîne de delegates EventDispatcherDelegate,
 * l'ordre d'appel, la propagation vers le dispatch() parent (Symfony) et le
 * comportement quand un delegate lève.
 *
 * Les doubles sont des stubs sur l'INTERFACE EventDispatcherDelegate (aucune classe
 * final n'est mockée ici).
 */
final class CoreEventDispatcherTest extends TestCase
{
    public function test_it_extends_the_symfony_event_dispatcher(): void
    {
        $dispatcher = new CoreEventDispatcher;

        $this->assertInstanceOf(EventDispatcher::class, $dispatcher);
        $this->assertInstanceOf(EventDispatcherInterface::class, $dispatcher);
    }

    public function test_dispatch_returns_the_event_instance(): void
    {
        $event = new \stdClass;

        $this->assertSame($event, (new CoreEventDispatcher)->dispatch($event, 'whatever'));
    }

    public function test_dispatch_without_any_delegate_still_reaches_the_symfony_listeners(): void
    {
        $dispatcher = new CoreEventDispatcher;
        $event      = new \stdClass;
        $seen       = null;

        $dispatcher->addListener('mon.evenement', function (object $e) use (&$seen): void {
            $seen = $e;
        });

        $returned = $dispatcher->dispatch($event, 'mon.evenement');

        $this->assertSame($event, $returned);
        $this->assertSame($event, $seen);
    }

    public function test_delegates_are_called_in_registration_order(): void
    {
        $calls = [];

        $dispatcher = new CoreEventDispatcher([
            $this->spyDelegate($calls, 'premier'),
            $this->spyDelegate($calls, 'deuxieme'),
        ]);

        // addDelegate() ajoute en fin de chaîne.
        $dispatcher->addDelegate($this->spyDelegate($calls, 'troisieme'));

        $dispatcher->dispatch(new \stdClass, 'ordre');

        $this->assertSame(['premier', 'deuxieme', 'troisieme'], $this->labels($calls));
    }

    public function test_add_delegate_returns_the_dispatcher_for_chaining(): void
    {
        $dispatcher = new CoreEventDispatcher;
        $calls      = [];

        $returned = $dispatcher->addDelegate($this->spyDelegate($calls, 'a'));

        $this->assertSame($dispatcher, $returned);

        $returned->addDelegate($this->spyDelegate($calls, 'b'))->dispatch(new \stdClass, 'chainage');

        $this->assertSame(['a', 'b'], $this->labels($calls));
    }

    public function test_the_constructor_accepts_any_iterable_of_delegates(): void
    {
        $calls = [];

        // Un générateur prouve que le constructeur n'exige pas un tableau.
        $delegates = (function () use (&$calls) {
            yield $this->spyDelegate($calls, 'genere-1');
            yield $this->spyDelegate($calls, 'genere-2');
        })();

        (new CoreEventDispatcher($delegates))->dispatch(new \stdClass, 'iterable');

        $this->assertSame(['genere-1', 'genere-2'], $this->labels($calls));
    }

    public function test_a_delegate_receives_the_event_the_name_and_the_dispatcher_itself(): void
    {
        $calls      = [];
        $event      = new \stdClass;
        $dispatcher = new CoreEventDispatcher([$this->spyDelegate($calls, 'unique')]);

        $dispatcher->dispatch($event, 'mon.evenement');

        $this->assertCount(1, $calls);
        $this->assertSame($event, $calls[0]['event']);
        $this->assertSame('mon.evenement', $calls[0]['eventName']);
        $this->assertSame($dispatcher, $calls[0]['dispatcher'], 'Le delegate reçoit le CoreEventDispatcher lui-même.');
    }

    public function test_a_null_event_name_is_forwarded_as_is(): void
    {
        $calls = [];

        (new CoreEventDispatcher([$this->spyDelegate($calls, 'unique')]))->dispatch(new \stdClass);

        $this->assertNull($calls[0]['eventName']);
    }

    public function test_delegates_run_before_the_symfony_listeners(): void
    {
        $order = [];

        $dispatcher = new CoreEventDispatcher([
            $this->callbackDelegate(static function () use (&$order): void {
                $order[] = 'delegate';
            }),
        ]);

        $dispatcher->addListener('mon.evenement', static function () use (&$order): void {
            $order[] = 'listener';
        });

        $dispatcher->dispatch(new \stdClass, 'mon.evenement');

        $this->assertSame(['delegate', 'listener'], $order, 'La chaîne de delegates précède parent::dispatch().');
    }

    public function test_every_delegate_is_called_for_the_same_event(): void
    {
        $calls      = [];
        $event      = new \stdClass;
        $dispatcher = new CoreEventDispatcher([
            $this->spyDelegate($calls, 'a'),
            $this->spyDelegate($calls, 'b'),
        ]);

        $dispatcher->dispatch($event, 'partage');

        $this->assertSame($event, $calls[0]['event']);
        $this->assertSame($event, $calls[1]['event']);
    }

    public function test_a_throwing_delegate_stops_the_chain_and_propagates(): void
    {
        $calls       = [];
        $boom        = new \RuntimeException('delegate en panne');
        $listenerRan = false;

        $dispatcher = new CoreEventDispatcher([
            $this->spyDelegate($calls, 'avant'),
            $this->spyDelegate($calls, 'plantage', $boom),
            $this->spyDelegate($calls, 'apres'),
        ]);

        $dispatcher->addListener('mon.evenement', static function () use (&$listenerRan): void {
            $listenerRan = true;
        });

        $thrown = null;

        try {
            $dispatcher->dispatch(new \stdClass, 'mon.evenement');
        } catch (\Throwable $e) {
            $thrown = $e;
        }

        if ($thrown === null) {
            $this->fail('L\'exception du delegate aurait dû se propager hors de dispatch().');
        }

        $this->assertSame($boom, $thrown, 'C\'est bien l\'instance levée qui remonte, sans emballage.');
        $this->assertSame(
            ['avant', 'plantage'],
            $this->labels($calls),
            'Le delegate fautif a démarré, mais les suivants ne sont pas appelés.',
        );
        $this->assertFalse($listenerRan, 'parent::dispatch() ne doit pas être atteint.');
    }

    public function test_an_error_thrown_by_a_delegate_propagates_too(): void
    {
        $calls = [];
        $error = new \Error('erreur fatale logique');

        $dispatcher = new CoreEventDispatcher([
            $this->spyDelegate($calls, 'plantage', $error),
            $this->spyDelegate($calls, 'apres'),
        ]);

        $thrown = null;

        try {
            $dispatcher->dispatch(new \stdClass, 'mon.evenement');
        } catch (\Throwable $e) {
            $thrown = $e;
        }

        // Le `catch (\Throwable $e) { throw $e; }` de CoreEventDispatcher::dispatch()
        // est redondant, mais son comportement observable est bien la propagation.
        $this->assertInstanceOf(\Error::class, $thrown);
        $this->assertSame($error, $thrown);
        $this->assertSame('erreur fatale logique', $thrown->getMessage());
        $this->assertSame(['plantage'], $this->labels($calls), 'Le second delegate n\'est jamais atteint.');
    }

    public function test_the_dispatcher_is_reusable_after_a_successful_dispatch(): void
    {
        $calls      = [];
        $dispatcher = new CoreEventDispatcher([$this->spyDelegate($calls, 'unique')]);

        $first  = new \stdClass;
        $second = new \stdClass;

        $this->assertSame($first, $dispatcher->dispatch($first, 'un'));
        $this->assertSame($second, $dispatcher->dispatch($second, 'deux'));

        $this->assertSame(['unique', 'unique'], $this->labels($calls));
        $this->assertSame('un', $calls[0]['eventName']);
        $this->assertSame('deux', $calls[1]['eventName']);
    }

    /* -----------------------------------------------------------------
     |  Doubles
     | -----------------------------------------------------------------
     */

    /**
     * Delegate espion : empile dans $calls un enregistrement complet de chaque invocation.
     *
     * @param list<array{label: string, event: object, eventName: ?string, dispatcher: EventDispatcher}> $calls
     */
    private function spyDelegate(array &$calls, string $label, ?\Throwable $throw = null): EventDispatcherDelegate
    {
        $delegate = $this->createStub(EventDispatcherDelegate::class);

        $delegate->method('handle')->willReturnCallback(
            static function (object $event, ?string $eventName, EventDispatcher $dispatcher) use (&$calls, $label, $throw): void {
                $calls[] = [
                    'label'      => $label,
                    'event'      => $event,
                    'eventName'  => $eventName,
                    'dispatcher' => $dispatcher,
                ];

                if ($throw !== null) {
                    throw $throw;
                }
            }
        );

        return $delegate;
    }

    /** Delegate minimal qui exécute simplement un rappel. */
    private function callbackDelegate(callable $callback): EventDispatcherDelegate
    {
        $delegate = $this->createStub(EventDispatcherDelegate::class);

        $delegate->method('handle')->willReturnCallback(
            static function (object $event, ?string $eventName, EventDispatcher $dispatcher) use ($callback): void {
                $callback($event, $eventName, $dispatcher);
            }
        );

        return $delegate;
    }

    /**
     * @param list<array{label: string}> $calls
     * @return list<string>
     */
    private function labels(array $calls): array
    {
        return array_values(array_column($calls, 'label'));
    }
}
