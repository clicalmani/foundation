<?php

declare(strict_types=1);

namespace Clicalmani\Core\Tests\Events\Listeners;

use Clicalmani\Core\Events\Listeners\SystemQueryListener;
use Clicalmani\Core\Support\Facades\Metrics;
use Clicalmani\Core\Tests\TestCase;
use Clicalmani\Database\Events\Query;
use Clicalmani\Database\Events\QueryProfiled;
use Clicalmani\Database\Events\SlowQuery;
use Clicalmani\Database\Profiling\QueryProfiler;
use Clicalmani\Metrics\MetricsManager;

/**
 * Couvre SystemQueryListener : les métriques émises pour une requête simple,
 * une requête lente et une requête profilée, ainsi que la journalisation associée.
 *
 * LIMITES ASSUMÉES
 *  - La branche `database.profile_slow_queries` = true appelle QueryProfiler::profile(),
 *    qui exécute `SET PROFILING = 1` puis la requête sur une vraie connexion MySQL.
 *    Elle n'est donc volontairement PAS couverte ici (aucune base en test) : la
 *    configuration reste à false pour toute la suite.
 *  - QueryProfiler est `final` et non mockable : on l'installe pour de vrai, mais
 *    il n'est jamais atteint tant que le profiling reste désactivé.
 */
final class SystemQueryListenerTest extends TestCase
{
    private SystemQueryListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        // Le VRAI gestionnaire, avec son InMemoryDriver par défaut. Sans service
        // « metrics » lié, la façade renvoie silencieusement null et rien n'est tracé.
        $this->bindService('metrics', new MetricsManager);

        // Profiling désactivé : la branche correspondante toucherait MySQL.
        $this->setConfig('database.profile_slow_queries', false);

        $this->listener = new SystemQueryListener(new QueryProfiler);
    }

    /* -----------------------------------------------------------------
     |  Requête simple — métriques universelles
     | -----------------------------------------------------------------
     */

    public function test_a_plain_query_increments_the_counter_and_records_the_duration(): void
    {
        ($this->listener)(new Query(
            sql: 'SELECT * FROM users WHERE id = ?',
            bindings: [7],
            duration: 12.5,
        ));

        $this->assertSame(
            ['db.query.count[connection=default,type=SELECT]' => 1],
            $this->counters(),
        );

        $this->assertSame(
            ['db.query.duration[connection=default,type=SELECT]' => [12.5]],
            $this->timings(),
        );

        $this->assertSame([], Metrics::all()['gauges'], 'Aucune jauge ne doit être émise.');
    }

    public function test_the_type_tag_is_the_uppercased_first_sql_word(): void
    {
        ($this->listener)(new Query(
            sql: 'insert into logs (message) values (?)',
            bindings: ['bonjour'],
            duration: 1.0,
            connection: 'writing',
        ));

        $this->assertArrayHasKey('db.query.count[connection=writing,type=INSERT]', $this->counters());
        $this->assertArrayHasKey('db.query.duration[connection=writing,type=INSERT]', $this->timings());
    }

    public function test_leading_whitespace_does_not_break_type_detection(): void
    {
        ($this->listener)(new Query(sql: "   \n  update users set name = 'x'", duration: 2.0));

        $this->assertArrayHasKey('db.query.count[connection=default,type=UPDATE]', $this->counters());
    }

    public function test_repeated_queries_accumulate_into_the_same_counter(): void
    {
        ($this->listener)(new Query(sql: 'SELECT 1', duration: 1.0));
        ($this->listener)(new Query(sql: 'SELECT 2', duration: 2.0));
        ($this->listener)(new Query(sql: 'SELECT 3', duration: 3.0));

        $this->assertSame(3, $this->counters()['db.query.count[connection=default,type=SELECT]']);
        $this->assertSame(
            [1.0, 2.0, 3.0],
            $this->timings()['db.query.duration[connection=default,type=SELECT]'],
            'Chaque durée est empilée, pas écrasée.',
        );
    }

    public function test_a_plain_query_records_no_slow_nor_profile_metric(): void
    {
        ($this->listener)(new Query(sql: 'SELECT 1', duration: 1.0));

        $this->assertSame(['db.query.count[connection=default,type=SELECT]'], array_keys($this->counters()));
        $this->assertSame(['db.query.duration[connection=default,type=SELECT]'], array_keys($this->timings()));
    }

    /* -----------------------------------------------------------------
     |  Requêtes lentes
     | -----------------------------------------------------------------
     */

    public function test_a_slow_query_adds_its_own_counter_on_top_of_the_basic_metrics(): void
    {
        ($this->listener)(new SlowQuery(
            sql: 'SELECT * FROM huge_table',
            duration: 2500.0,
            connection: 'reporting',
        ));

        $this->assertSame(
            [
                'db.query.count[connection=reporting,type=SELECT]' => 1,
                'db.slow_query[connection=reporting,type=SELECT]'  => 1,
            ],
            $this->counters(),
        );
    }

    public function test_a_slow_query_is_logged_as_a_warning(): void
    {
        $appended = $this->captureLog(fn () => ($this->listener)(new SlowQuery(
            sql: 'SELECT * FROM huge_table WHERE legacy = ?',
            bindings: [1],
            duration: 1234.567,
        )));

        $this->assertStringContainsString('PHP Warning', $appended);
        $this->assertStringContainsString('Slow query detected', $appended);
        $this->assertStringContainsString('SELECT * FROM huge_table WHERE legacy = ?', $appended);
        $this->assertStringContainsString('1234.57 ms', $appended, 'La durée est arrondie à 2 décimales.');
    }

    public function test_profiling_stays_untouched_while_profile_slow_queries_is_disabled(): void
    {
        $this->assertFalse((bool) config('database.profile_slow_queries', false));

        // QueryProfiler::profile() exécuterait `SET PROFILING = 1` sur une connexion
        // MySQL réelle : si la branche était prise, ce test lèverait. Il passe donc
        // uniquement parce que la configuration reste à false.
        ($this->listener)(new SlowQuery(sql: 'SELECT 1', duration: 900.0));

        $this->assertSame(1, $this->counters()['db.slow_query[connection=default,type=SELECT]']);
    }

    /* -----------------------------------------------------------------
     |  Requêtes profilées
     | -----------------------------------------------------------------
     */

    public function test_a_profiled_query_records_one_timing_per_phase_and_the_total(): void
    {
        ($this->listener)(new QueryProfiled(
            sql: 'SELECT * FROM users',
            duration: 5.0,
            profile: [
                ['Status' => 'starting',  'Duration' => 0.5],
                ['Status' => 'executing', 'Duration' => 1.25],
                ['Status' => 'ending',    'Duration' => 3.25],
            ],
        ));

        $timings = $this->timings();

        $this->assertSame([0.5],  $timings['db.profile.phase[connection=default,phase=starting]']);
        $this->assertSame([1.25], $timings['db.profile.phase[connection=default,phase=executing]']);
        $this->assertSame([3.25], $timings['db.profile.phase[connection=default,phase=ending]']);
        $this->assertSame([5.0],  $timings['db.profile.total[connection=default]'], 'Le total est la somme des phases.');

        // Les métriques universelles restent émises, QueryProfiled héritant de Query.
        $this->assertSame(1, $this->counters()['db.query.count[connection=default,type=SELECT]']);
    }

    public function test_a_profile_step_without_status_or_duration_falls_back_to_defaults(): void
    {
        ($this->listener)(new QueryProfiled(
            sql: 'SELECT 1',
            profile: [
                ['Duration' => 2.0],   // pas de Status  -> phase « unknown »
                ['Status' => 'ending'], // pas de Duration -> 0.0
            ],
        ));

        $timings = $this->timings();

        $this->assertSame([2.0], $timings['db.profile.phase[connection=default,phase=unknown]']);
        $this->assertSame([0.0], $timings['db.profile.phase[connection=default,phase=ending]']);
        $this->assertSame([2.0], $timings['db.profile.total[connection=default]'], 'array_column ignore les Duration absents.');
    }

    public function test_a_profiled_query_is_logged_as_a_notice(): void
    {
        $appended = $this->captureLog(fn () => ($this->listener)(new QueryProfiled(
            sql: 'SELECT * FROM users',
            profile: [
                ['Status' => 'starting',  'Duration' => 0.5],
                ['Status' => 'executing', 'Duration' => 1.25],
            ],
        )));

        $this->assertStringContainsString('PHP Notice', $appended, 'logger()->info() passe par le niveau notice.');
        $this->assertStringContainsString('Query profile captured', $appended);
        $this->assertStringContainsString('1.75 ms', $appended);
        $this->assertStringContainsString('"phases":2', $appended);
    }

    public function test_an_empty_profile_still_records_a_zero_total(): void
    {
        ($this->listener)(new QueryProfiled(sql: 'SELECT 1', profile: []));

        $this->assertSame([0.0], $this->timings()['db.profile.total[connection=default]']);
        $this->assertSame(
            ['db.query.count[connection=default,type=SELECT]'],
            array_keys($this->counters()),
            'Aucune métrique de requête lente ne doit être émise.',
        );
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

    /**
     * Exécute $action et renvoie ce qui a été ajouté au fichier de log pendant l'appel.
     *
     * Le mode debug reste OFF : warning()/info() écrivent alors dans le fichier
     * (error(), lui, ferait `exit` — voir SystemHttpListenerTest pour cette branche).
     */
    private function captureLog(callable $action): string
    {
        $file = storage_path('/logs/tonka.log');

        clearstatcache();
        $offset = is_file($file) ? (int) filesize($file) : 0;

        $action();

        if (!is_file($file)) {
            return '';
        }

        clearstatcache();

        $handle = fopen($file, 'rb');
        $this->assertIsResource($handle);

        fseek($handle, $offset);
        $appended = stream_get_contents($handle);
        fclose($handle);

        return $appended === false ? '' : $appended;
    }
}
