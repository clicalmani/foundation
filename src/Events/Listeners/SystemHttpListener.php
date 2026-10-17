<?php

namespace Clicalmani\Core\Events\Listeners;

use Clicalmani\Core\Http\Events\HttpRequest;
use Clicalmani\Core\Support\Facades\Metrics;

final class SystemHttpListener
{
    public function __invoke(HttpRequest $event): void
    {
        $this->recordCount($event);
        $this->recordDuration($event);
        $this->recordResponseSize($event);

        if ($event->status >= 500) {
            $this->recordServerError($event);
        } elseif ($event->status >= 400) {
            $this->recordClientError($event);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Rate — combien de requêtes
    // ─────────────────────────────────────────────────────────────

    private function recordCount(HttpRequest $event): void
    {
        Metrics::increment('http.request.count', tags: $this->baseTags($event));
    }

    // ─────────────────────────────────────────────────────────────
    // Duration — combien de temps
    // ─────────────────────────────────────────────────────────────

    private function recordDuration(HttpRequest $event): void
    {
        if ($event->duration <= 0) {
            return;
        }

        Metrics::timing('http.request.duration', $event->duration, tags: [
            'method' => $event->method,
            'route'  => $this->routeName($event),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Response size — combien d'octets
    // ─────────────────────────────────────────────────────────────

    private function recordResponseSize(HttpRequest $event): void
    {
        if ($event->responseSize <= 0) {
            return;
        }

        Metrics::timing('http.response.size', $event->responseSize, tags: [
            'method' => $event->method,
            'route'  => $this->routeName($event),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Errors — 4xx / 5xx
    // ─────────────────────────────────────────────────────────────

    private function recordClientError(HttpRequest $event): void
    {
        Metrics::increment('http.client_error', tags: $this->baseTags($event));
    }

    private function recordServerError(HttpRequest $event): void
    {
        Metrics::increment('http.server_error', tags: $this->baseTags($event));

        logger()->error('HTTP server error', [
            'method' => $event->method,
            'path'   => $event->path,
            'route'  => $event->route,
            'status' => $event->status,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    private function baseTags(HttpRequest $event): array
    {
        return array_merge([
            'method' => $event->method,
            'route'  => $this->routeName($event),
            'status' => $event->status,
        ], $event->tags);
    }

    /**
     * Retourne le nom de la route si résolu, sinon le chemin brut.
     */
    private function routeName(HttpRequest $event): string
    {
        return $event->route ?? $event->path;
    }
}