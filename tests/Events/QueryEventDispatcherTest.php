<?php

declare(strict_types=1);

namespace Clicalmani\Core\Tests\Events;

use Clicalmani\Core\Events\CoreEventDispatcher;
use Clicalmani\Core\Events\EventDispatcherDelegate;
use Clicalmani\Core\Events\Listeners\SystemQueryListener;
use Clicalmani\Core\Events\QueryEventDispatcher;
use Clicalmani\Core\Support\Facades\Metrics;
use Clicalmani\Core\Tests\TestCase;
use Clicalmani\Database\Events\Query;
use Clicalmani\Database\Events\QueryProfiled;
use Clicalmani\Database\Events\SlowQuery;
use Clicalmani\Database\Profiling\QueryProfiler;
use Clicalmani\Metrics\MetricsManager;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Couvre QueryEventDispatcher : le filtrage sur le type Query et la transmission
 * au SystemQueryListener injecté.
 *
 * SystemQueryListener est `final` (donc non mockable) et le constructeur exige ce
 * type précis : aucun double n'est possible. L'observation passe donc par le VRAI
 * listener adossé à un MetricsManager en mémoire — si une métrique apparaît, le
 * listener a bien été invoqué.
 */
final class QueryEventDispatcherTest extends TestCase
{
    private QueryEventDispatcher $delegate;

    private EventDispatcher $symfonyDispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bindService('metrics', new MetricsManager);

        // Profiling désactivé : QueryProfiler::profile() exigerait une connexion MySQL.
        $this->setConfig('database.profile_slow_queries', false);

        $this->delegate          = new QueryEventDispatcher(new SystemQueryListener(new QueryProfiler));
        $this->symfonyDispatcher = new EventDispatcher;
    }

    public function test_it_is_an_event_dispatcher_delegate(): void
    {
        $this->assertInstanceOf(EventDispatcherDelegate::class, $this->delegate);
    }

    public function test_a_query_event_is_forwarded_to_the_listener(): void
    {
        $this->delegate->handle(
            new Query(sql: 'SELECT * FROM users', duration: 3.5),
            'db.query',
            $this->symfonyDispatcher,
        );

        $this->assertSame(1, $this->counter('db.query.count[connection=default,type=SELECT]'));
        $this->assertSame([3.5], $this->timings()['db.query.duration[connection=default,type=SELECT]']);
    }

    public function test_a_slow_query_derivative_is_forwarded_too(): void
    {
        $this->delegate->handle(
            new SlowQuery(sql: 'SELECT * FROM huge', duration: 1500.0),
            'db.query',
            $this->symfonyDispatcher,
        );

        $this->assertSame(1, $this->counter('db.slow_query[connection=default,type=SELECT]'));
    }

    public function test_a_profiled_query_derivative_is_forwarded_too(): void
    {
        $this->delegate->handle(
            new QueryProfiled(
                sql: 'SELECT 1',
                profile: [['Status' => 'starting', 'Duration' => 0.5]],
            ),
            'db.query',
            $this->symfonyDispatcher,
        );

        $this->assertSame([0.5], $this->timings()['db.profile.phase[connection=default,phase=starting]']);
        $this->assertSame([0.5], $this->timings()['db.profile.total[connection=default]']);
    }

    public function test_the_event_name_is_irrelevant_to_the_filtering(): void
    {
        $this->delegate->handle(new Query(sql: 'SELECT 1', duration: 1.0), null, $this->symfonyDispatcher);
        $this->delegate->handle(new Query(sql: 'SELECT 2', duration: 2.0), 'totalement.autre', $this->symfonyDispatcher);

        $this->assertSame(2, $this->counter('db.query.count[connection=default,type=SELECT]'));
    }

    public function test_a_non_query_event_is_ignored(): void
    {
        $this->delegate->handle(new \stdClass, 'anything', $this->symfonyDispatcher);

        $this->assertSame([], $this->counters());
        $this->assertSame([], $this->timings());
        $this->assertSame([], Metrics::all()['gauges']);
    }

    public function test_a_lookalike_event_is_ignored(): void
    {
        // Même forme, autre type : le filtre est un `instanceof Query`, pas du duck typing.
        $lookalike = new class ('SELECT 1') {
            public function __construct(public readonly string $sql) {}
        };

        $this->delegate->handle($lookalike, 'db.query', $this->symfonyDispatcher);

        $this->assertSame([], $this->counters());
    }

    public function test_an_unrelated_framework_event_is_ignored(): void
    {
        $this->delegate->handle($this->symfonyDispatcher, 'dispatcher.itself', $this->symfonyDispatcher);

        $this->assertSame([], $this->counters());
    }

    public function test_repeated_events_accumulate_through_the_dispatcher(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->delegate->handle(new Query(sql: 'SELECT 1', duration: 1.0), 'db.query', $this->symfonyDispatcher);
        }

        $this->assertSame(3, $this->counter('db.query.count[connection=default,type=SELECT]'));
        $this->assertSame([1.0, 1.0, 1.0], $this->timings()['db.query.duration[connection=default,type=SELECT]']);
    }

    public function test_it_works_when_plugged_into_the_core_dispatcher_chain(): void
    {
        $dispatcher = new CoreEventDispatcher([$this->delegate]);

        $returned = $dispatcher->dispatch(new Query(sql: 'DELETE FROM logs', duration: 4.0), 'db.query');

        $this->assertInstanceOf(Query::class, $returned);
        $this->assertSame(1, $this->counter('db.query.count[connection=default,type=DELETE]'));
    }

    public function test_the_core_chain_still_ignores_non_query_events(): void
    {
        $dispatcher = new CoreEventDispatcher([$this->delegate]);

        $dispatcher->dispatch(new \stdClass, 'autre');

        $this->assertSame([], $this->counters());
    }

    /* -----------------------------------------------------------------
     |  Helpers
     | -----------------------------------------------------------------
     */

    /** @return array<string, int> */
    private function counters(): array
    {
        return Metrics::all()['counters'];
    }

    /** @return array<string, list<float>> */
    private function timings(): array
    {
        return Metrics::all()['timings'];
    }

    private function counter(string $key): ?int
    {
        return $this->counters()[$key] ?? null;
    }
}
