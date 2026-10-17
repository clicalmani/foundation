<?php

declare(strict_types=1);

namespace Clicalmani\Core\Tests\Events\Listeners;

use Clicalmani\Core\Events\Listeners\SystemHttpListener;
use Clicalmani\Core\Http\Events\HttpRequest;
use Clicalmani\Core\Support\Facades\Metrics;
use Clicalmani\Core\Tests\TestCase;
use Clicalmani\Metrics\MetricsManager;

/**
 * Couvre SystemHttpListener à partir de l'événement HttpRequest.
 *
 * Le mode debug reste OFF partout, SAUF dans le test sur les statuts >= 500 :
 * logger()->error() y exécute `exit` en mode normal, ce qui tuerait PHPUnit.
 * En mode debug il lève une \Exception, ce qui rend la branche observable.
 */
final class SystemHttpListenerTest extends TestCase
{
    private SystemHttpListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        // Le VRAI gestionnaire (InMemoryDriver par défaut) : la façade Metrics
        // résout l'identifiant « metrics » et renvoie null tant qu'il n'est pas lié.
        $this->bindService('metrics', new MetricsManager);

        $this->listener = new SystemHttpListener;
    }

    /* -----------------------------------------------------------------
     |  Chemin nominal
     | -----------------------------------------------------------------
     */

    public function test_a_successful_request_records_count_duration_and_response_size(): void
    {
        ($this->listener)(new HttpRequest(
            method: 'GET',
            path: '/users',
            status: 200,
            route: 'users.index',
            duration: 42.5,
            responseSize: 1024,
        ));

        $this->assertSame(
            ['http.request.count[method=GET,route=users.index,status=200]' => 1],
            $this->counters(),
            'Aucun compteur d\'erreur ne doit être émis pour un 200.',
        );

        $this->assertSame(
            [
                'http.request.duration[method=GET,route=users.index]' => [42.5],
                'http.response.size[method=GET,route=users.index]'    => [1024.0],
            ],
            $this->timings(),
            'La taille de réponse est enregistrée comme un timing.',
        );
    }

    public function test_the_route_tag_falls_back_to_the_raw_path(): void
    {
        ($this->listener)(new HttpRequest(method: 'POST', path: '/unknown/path', status: 201));

        // Le tag passe par http_build_query() dans InMemoryDriver::key() : le chemin
        // brut y est donc URL-encodé (« / » -> « %2F »).
        $this->assertSame(
            ['http.request.count[method=POST,route=%2Funknown%2Fpath,status=201]' => 1],
            $this->counters(),
        );
    }

    public function test_extra_tags_are_merged_into_the_metric_key(): void
    {
        ($this->listener)(new HttpRequest(
            method: 'DELETE',
            path: '/users/9',
            status: 204,
            route: 'users.destroy',
            tags: ['section' => 'admin'],
        ));

        // Les tags sont triés par ksort dans InMemoryDriver::key() :
        // method, route, section, status.
        $this->assertSame(
            ['http.request.count[method=DELETE,route=users.destroy,section=admin,status=204]' => 1],
            $this->counters(),
        );
    }

    /* -----------------------------------------------------------------
     |  Garde-fous durée / taille
     | -----------------------------------------------------------------
     */

    public function test_a_zero_duration_and_size_record_no_timing(): void
    {
        ($this->listener)(new HttpRequest(method: 'GET', path: '/', status: 200, route: 'home'));

        $this->assertSame([], $this->timings());
        $this->assertSame(1, $this->counters()['http.request.count[method=GET,route=home,status=200]']);
    }

    public function test_a_negative_duration_and_size_record_no_timing(): void
    {
        ($this->listener)(new HttpRequest(
            method: 'GET',
            path: '/',
            status: 200,
            route: 'home',
            duration: -1.0,
            responseSize: -10,
        ));

        $this->assertSame([], $this->timings());
    }

    /* -----------------------------------------------------------------
     |  Erreurs client (4xx)
     | -----------------------------------------------------------------
     */

    public function test_a_not_found_status_increments_the_client_error_counter(): void
    {
        ($this->listener)(new HttpRequest(method: 'GET', path: '/missing', status: 404, route: 'missing.show'));

        $this->assertSame(
            [
                'http.request.count[method=GET,route=missing.show,status=404]' => 1,
                'http.client_error[method=GET,route=missing.show,status=404]'   => 1,
            ],
            $this->counters(),
        );

        $this->assertArrayNotHasKey(
            'http.server_error[method=GET,route=missing.show,status=404]',
            $this->counters(),
        );
    }

    public function test_the_upper_client_error_boundary_is_499(): void
    {
        ($this->listener)(new HttpRequest(method: 'GET', path: '/teapot', status: 499, route: 'teapot'));

        $this->assertSame(1, $this->counters()['http.client_error[method=GET,route=teapot,status=499]']);
        $this->assertNull($this->counter('http.server_error[method=GET,route=teapot,status=499]'));
    }

    public function test_a_redirect_records_no_error_counter(): void
    {
        ($this->listener)(new HttpRequest(method: 'GET', path: '/old', status: 302, route: 'old'));

        $this->assertSame(['http.request.count[method=GET,route=old,status=302]'], array_keys($this->counters()));
    }

    /* -----------------------------------------------------------------
     |  Erreurs serveur (5xx)
     | -----------------------------------------------------------------
     */

    public function test_a_server_error_records_its_metric_before_the_logger_throws(): void
    {
        // Sans mode debug, logger()->error() se termine par `exit` : processus PHPUnit mort.
        $this->enableDebugLogging();

        $event = new HttpRequest(
            method: 'POST',
            path: '/orders',
            status: 500,
            route: 'orders.store',
            duration: 30.0,
            responseSize: 120,
        );

        $thrown = null;

        try {
            ($this->listener)($event);
        } catch (\Exception $e) {
            $thrown = $e;
        }

        if ($thrown === null) {
            $this->fail('logger()->error() aurait dû lever une \\Exception en mode debug.');
        }

        $this->assertStringContainsString('HTTP server error', $thrown->getMessage());
        $this->assertStringContainsString('orders.store', $thrown->getMessage());

        // recordServerError() incrémente la métrique AVANT d'appeler le logger :
        // l'exception ne doit pas avoir empêché l'enregistrement.
        $this->assertSame(1, $this->counter('http.server_error[method=POST,route=orders.store,status=500]'));

        // Les métriques précédentes ont également survécu.
        $this->assertSame(1, $this->counter('http.request.count[method=POST,route=orders.store,status=500]'));
        $this->assertSame([30.0], $this->timings()['http.request.duration[method=POST,route=orders.store]']);
        $this->assertNull(
            $this->counter('http.client_error[method=POST,route=orders.store,status=500]'),
            'La branche 4xx est un elseif : elle ne doit pas être empruntée.',
        );
    }

    public function test_any_status_above_500_is_a_server_error(): void
    {
        $this->enableDebugLogging();

        $thrown = null;

        try {
            ($this->listener)(new HttpRequest(method: 'GET', path: '/gateway', status: 503));
        } catch (\Exception $e) {
            $thrown = $e;
        }

        $this->assertInstanceOf(\Exception::class, $thrown);

        // Route non résolue : le tag « route » retombe sur le chemin brut, URL-encodé.
        $this->assertSame(1, $this->counter('http.server_error[method=GET,route=%2Fgateway,status=503]'));
    }

    public function test_a_server_error_keeps_the_extra_tags(): void
    {
        $this->enableDebugLogging();

        try {
            ($this->listener)(new HttpRequest(
                method: 'PUT',
                path: '/users/1',
                status: 502,
                route: 'users.update',
                tags: ['section' => 'admin'],
            ));
        } catch (\Exception) {
            // Attendu : logger()->error() lève en mode debug.
        }

        $this->assertSame(
            1,
            $this->counter('http.server_error[method=PUT,route=users.update,section=admin,status=502]'),
        );
    }

    /* -----------------------------------------------------------------
     |  Événement HttpRequest lui-même
     | -----------------------------------------------------------------
     */

    public function test_the_event_exposes_readonly_properties_with_sensible_defaults(): void
    {
        $event = new HttpRequest(method: 'GET', path: '/health', status: 200);

        $this->assertSame('GET', $event->method);
        $this->assertSame('/health', $event->path);
        $this->assertSame(200, $event->status);
        $this->assertNull($event->route);
        $this->assertSame(0.0, $event->duration);
        $this->assertSame(0, $event->responseSize);
        $this->assertSame([], $event->tags);

        $reflection = new \ReflectionClass($event);

        $this->assertTrue($reflection->isFinal());

        foreach (['method', 'path', 'status', 'route', 'duration', 'responseSize', 'tags'] as $property) {
            $this->assertTrue($reflection->getProperty($property)->isReadOnly(), "{$property} devrait être readonly.");
            $this->assertTrue($reflection->getProperty($property)->isPublic(), "{$property} devrait être public.");
        }
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
