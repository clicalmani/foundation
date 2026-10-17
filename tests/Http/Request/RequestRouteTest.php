<?php

declare(strict_types=1);

namespace Clicalmani\Core\Tests\Http\Request;

use Clicalmani\Core\Http\Request\RequestRoute;
use Clicalmani\Core\Support\Facades\Route;
use Clicalmani\Core\Tests\TestCase;
use Clicalmani\Routing\Factory\LaravelStyleBuilder;
use Clicalmani\Routing\Registry;

/**
 * Tests de Clicalmani\Core\Http\Request\RequestRoute.
 *
 * current() s'appuie sur le helper client_url() -> façade Route::uri() ->
 * Clicalmani\Routing\Router::uri() (parse_url($_SERVER['REQUEST_URI'])['path']).
 * named() passe par Route::findByName(), qui parcourt Registry::getRoutes() en
 * comparant $route->name puis $route->uri.
 */
final class RequestRouteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Le registre des routes est statique : il doit être vidé à chaque test.
        Registry::reset();

        // Nécessaire à Router::createRoute() (default_builder) et à la détection
        // des segments paramètres (parameter_prefix).
        $this->setConfig('route', [
            'parameter_prefix' => ':',
            'default_builder'  => LaravelStyleBuilder::class,
        ]);

        unset($_SERVER['REQUEST_URI']);
    }

    protected function tearDown(): void
    {
        Registry::reset();

        parent::tearDown();
    }

    /** Enregistre une route nommée dans le registre global. */
    private function registerRoute(string $verb, string $uri, string $name): void
    {
        $route = Route::{$verb}($uri, static fn() => 'ok');

        $route->name($name);
    }

    /* -----------------------------------------------------------------
     |  current()
     | -----------------------------------------------------------------
     */

    public function test_current_renvoie_le_chemin_demande(): void
    {
        $_SERVER['REQUEST_URI'] = '/users/42';

        $this->assertSame('/users/42', (new RequestRoute)->current());
    }

    public function test_current_retire_la_query_string_et_le_fragment(): void
    {
        $_SERVER['REQUEST_URI'] = '/users/42?page=2#ancre';

        $this->assertSame('/users/42', (new RequestRoute)->current());
    }

    public function test_current_renvoie_une_chaine_vide_sans_request_uri(): void
    {
        $this->assertArrayNotHasKey('REQUEST_URI', $_SERVER);
        $this->assertSame('', (new RequestRoute)->current());
    }

    public function test_current_renvoie_une_barre_oblique_pour_une_url_sans_chemin(): void
    {
        // parse_url() ne remonte aucune clé « path » : Router::uri() retombe sur '/'.
        $_SERVER['REQUEST_URI'] = 'https://example.com';

        $this->assertSame('/', (new RequestRoute)->current());
    }

    public function test_current_ne_resout_pas_les_parametres_de_route(): void
    {
        $this->registerRoute('get', '/users/{id}', 'users.show');

        $_SERVER['REQUEST_URI'] = '/users/42';

        // current() renvoie l'URI brute du client, pas l'URI déclarée.
        $this->assertSame('/users/42', (new RequestRoute)->current());
    }

    /* -----------------------------------------------------------------
     |  named()
     | -----------------------------------------------------------------
     */

    public function test_named_est_faux_quand_aucune_route_n_est_enregistree(): void
    {
        $this->assertFalse((new RequestRoute)->named('users.show'));
    }

    public function test_named_est_vrai_pour_une_route_portant_ce_nom(): void
    {
        $this->registerRoute('get', '/users/{id}', 'users.show');

        $this->assertTrue((new RequestRoute)->named('users.show'));
    }

    public function test_named_est_faux_pour_un_nom_inconnu(): void
    {
        $this->registerRoute('get', '/users/{id}', 'users.show');

        $this->assertFalse((new RequestRoute)->named('users.edit'));
    }

    public function test_named_cherche_aussi_dans_les_autres_verbes(): void
    {
        $this->registerRoute('post', '/users', 'users.store');
        $this->registerRoute('delete', '/users/{id}', 'users.delete');

        $route = new RequestRoute;

        $this->assertTrue($route->named('users.store'));
        $this->assertTrue($route->named('users.delete'));
    }

    public function test_named_est_faux_sur_une_chaine_vide(): void
    {
        $this->registerRoute('get', '/users/{id}', 'users.show');

        $this->assertFalse((new RequestRoute)->named(''));
    }

    public function test_named_est_vrai_quand_le_nom_correspond_a_l_uri_de_la_route(): void
    {
        $this->registerRoute('get', '/users/{id}', 'users.show');

        // findByName() compare également $route->uri === trim($name, '/') :
        // l'URI convertie par le builder (sans « / » initial) répond donc aussi.
        $this->assertTrue((new RequestRoute)->named('users/:id'));
    }

    public function test_les_routes_d_un_test_ne_fuient_pas_dans_le_suivant(): void
    {
        // Aucun enregistrement ici : si Registry::reset() fonctionne, la route
        // posée par les tests précédents a disparu.
        $this->assertSame([], Registry::getRoutes());
        $this->assertFalse((new RequestRoute)->named('users.show'));
    }
}
