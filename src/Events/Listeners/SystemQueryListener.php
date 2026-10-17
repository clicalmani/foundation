<?php

namespace Clicalmani\Core\Events\Listeners;

use Clicalmani\Database\Events\{
    Query,
    SlowQuery,
    QueryProfiled,
};
use Clicalmani\Core\Support\Facades\Metrics;
use Clicalmani\Database\Profiling\QueryProfiler;

final class SystemQueryListener
{
    public function __construct(
        private QueryProfiler $profiler,
    ) {}

    public function __invoke(Query $event): void
    {
        // 1. Métrique universelle pour chaque requête
        $this->recordBasicMetrics($event);

        // 2. Cas particulier : slow query
        if ($event instanceof SlowQuery) {
            $this->handleSlowQuery($event);
        }

        // 3. Cas particulier : query profilée
        if ($event instanceof QueryProfiled) {
            $this->handleProfiledQuery($event);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Métriques de base — pour toutes les requêtes
    // ─────────────────────────────────────────────────────────────

    private function recordBasicMetrics(Query $event): void
    {
        $type = strtoupper(explode(' ', trim($event->sql))[0] ?? 'OTHER');

        Metrics::increment('db.query.count', tags: [
            'connection' => $event->connection,
            'type'       => $type,
        ]);

        Metrics::timing('db.query.duration', $event->duration, tags: [
            'connection' => $event->connection,
            'type'       => $type,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Slow queries
    // ─────────────────────────────────────────────────────────────

    private function handleSlowQuery(SlowQuery $event): void
    {
        Metrics::increment('db.slow_query', tags: [
            'connection' => $event->connection,
            'type'       => strtoupper(explode(' ', trim($event->sql))[0] ?? 'OTHER'),
        ]);

        logger()->warning('Slow query detected', [
            'sql'        => $event->sql,
            'bindings'   => $event->bindings,
            'duration'   => round($event->duration, 2) . ' ms',
            'connection' => $event->connection,
        ]);

        // Profiling automatique si activé en config
        if (config('database.profile_slow_queries', false)) {
            $this->profiler->profile($event->sql, $event->bindings, $event->connection);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Query profilée — décomposition par phase
    // ─────────────────────────────────────────────────────────────

    private function handleProfiledQuery(QueryProfiled $event): void
    {
        foreach ($event->profile as $step) {
            $phase    = $step['Status']   ?? 'unknown';
            $duration = (float) ($step['Duration'] ?? 0);

            Metrics::timing('db.profile.phase', $duration, tags: [
                'connection' => $event->connection,
                'phase'      => $phase,
            ]);
        }

        // Total du profil (somme des phases)
        $total = array_sum(array_column($event->profile, 'Duration'));
        Metrics::timing('db.profile.total', $total, tags: [
            'connection' => $event->connection,
        ]);

        logger()->info('Query profile captured', [
            'sql'     => $event->sql,
            'total'   => round($total, 2) . ' ms',
            'phases'  => count($event->profile),
        ]);
    }
}