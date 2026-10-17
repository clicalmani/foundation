<?php

declare(strict_types=1);

namespace Clicalmani\Core\Tests\Cache;

use Clicalmani\Core\Cache\CacheStore;
use Clicalmani\Core\Tests\Fakes\RecordingCachePool;
use Clicalmani\Core\Tests\TestCase;
use Psr\Cache\CacheItemPoolInterface;

final class CacheStoreTest extends TestCase
{
    private RecordingCachePool $pool;

    private CacheStore $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = new CacheStore($this->pool = new RecordingCachePool, 'memory');
    }

    /* -----------------------------------------------------------------
     |  Écriture / lecture
     | -----------------------------------------------------------------
     */

    public function test_set_then_get_round_trips_every_scalar_and_structure(): void
    {
        $values = [
            'string' => 'bonjour',
            'int'    => 42,
            'float'  => 4.2,
            'bool'   => false,
            'array'  => ['a' => 1, 'b' => [2, 3]],
            'object' => (object) ['id' => 7],
        ];

        foreach ($values as $key => $value) {
            $this->assertTrue($this->store->set($key, $value));
            $this->assertEquals($value, $this->store->get($key));
        }
    }

    public function test_put_is_an_alias_of_set(): void
    {
        $this->assertTrue($this->store->put('k', 'v', 60));

        $this->assertSame('v', $this->store->get('k'));
        $this->assertArrayHasKey('k', $this->pool->entries());
        $this->assertSame([['expiresAfter', 60]], $this->pool->itemCalls('k'));
    }

    public function test_get_returns_the_default_when_the_key_is_missing(): void
    {
        $this->assertNull($this->store->get('absent'));
        $this->assertSame('repli', $this->store->get('absent', 'repli'));
    }

    /**
     * Une valeur null stockée est un succès, pas une absence : le défaut ne
     * doit pas prendre le dessus.
     */
    public function test_get_returns_a_stored_null_instead_of_the_default(): void
    {
        $this->store->set('k', null);

        $this->assertTrue($this->store->has('k'));
        $this->assertNull($this->store->get('k', 'repli'));
    }

    public function test_set_without_ttl_stores_a_permanent_entry(): void
    {
        $this->store->set('k', 'v');

        $this->assertNull($this->pool->entries()['k']['expiry']);
        $this->assertSame([], $this->pool->itemCalls('k'));
    }

    public function test_set_with_a_ttl_forwards_it_to_the_cache_item(): void
    {
        $this->store->set('k', 'v', 120);

        $expiry = $this->pool->entries()['k']['expiry'];

        $this->assertSame([['expiresAfter', 120]], $this->pool->itemCalls('k'));
        $this->assertGreaterThanOrEqual(time() + 119, $expiry);
        $this->assertLessThanOrEqual(time() + 120, $expiry);
    }

    /**
     * ttl <= 0 est ignoré (garde `$ttl !== null && $ttl > 0`) : l'entrée reste
     * permanente au lieu d'expirer immédiatement.
     */
    public function test_a_zero_or_negative_ttl_leaves_the_entry_permanent(): void
    {
        $this->store->set('zero', 'v', 0);
        $this->store->set('negative', 'v', -30);

        $this->assertNull($this->pool->entries()['zero']['expiry']);
        $this->assertNull($this->pool->entries()['negative']['expiry']);
    }

    public function test_forever_stores_without_expiry(): void
    {
        $this->assertTrue($this->store->forever('k', 'v'));

        $this->assertNull($this->pool->entries()['k']['expiry']);
    }

    /* -----------------------------------------------------------------
     |  add / replace
     | -----------------------------------------------------------------
     */

    public function test_add_stores_only_when_the_key_is_free(): void
    {
        $this->assertTrue($this->store->add('k', 'premier', 60));
        $this->assertFalse($this->store->add('k', 'second', 60));

        $this->assertSame('premier', $this->store->get('k'));
    }

    public function test_replace_stores_only_when_the_key_exists(): void
    {
        $this->assertFalse($this->store->replace('k', 'v'));
        $this->assertFalse($this->store->has('k'));

        $this->store->set('k', 'ancienne');

        $this->assertTrue($this->store->replace('k', 'nouvelle', 60));
        $this->assertSame('nouvelle', $this->store->get('k'));
    }

    public function test_add_and_replace_ignore_an_expired_entry(): void
    {
        $this->pool->seed('k', 'perime', time() - 10);

        $this->assertTrue($this->store->add('k', 'fraiche'));
        $this->assertFalse($this->store->replace('absent', 'v'));
    }

    /* -----------------------------------------------------------------
     |  remember / pull
     | -----------------------------------------------------------------
     */

    public function test_remember_computes_once_then_serves_the_cache(): void
    {
        $calls = 0;
        $compute = function () use (&$calls) {
            $calls++;

            return 'calcule';
        };

        $this->assertSame('calcule', $this->store->remember('k', 60, $compute));
        $this->assertSame('calcule', $this->store->remember('k', 60, $compute));

        $this->assertSame(1, $calls);
        $this->assertSame([['expiresAfter', 60]], $this->pool->itemCalls('k'));
    }

    public function test_remember_recomputes_after_expiration(): void
    {
        $this->store->remember('k', 60, fn () => 'premier');

        $this->pool->seed('k', 'perime', time() - 1);

        $this->assertSame('second', $this->store->remember('k', 60, fn () => 'second'));
    }

    public function test_remember_forever_stores_a_permanent_entry(): void
    {
        $this->assertSame('v', $this->store->rememberForever('k', fn () => 'v'));

        $this->assertNull($this->pool->entries()['k']['expiry']);
        $this->assertSame([], $this->pool->itemCalls('k'));
    }

    public function test_pull_returns_the_value_then_removes_it(): void
    {
        $this->store->set('k', 'v');

        $this->assertSame('v', $this->store->pull('k'));
        $this->assertFalse($this->store->has('k'));
        $this->assertSame('repli', $this->store->pull('k', 'repli'));
    }

    /* -----------------------------------------------------------------
     |  has / missing / delete / clear
     | -----------------------------------------------------------------
     */

    public function test_has_and_missing_are_complementary(): void
    {
        $this->assertFalse($this->store->has('k'));
        $this->assertTrue($this->store->missing('k'));

        $this->store->set('k', 'v');

        $this->assertTrue($this->store->has('k'));
        $this->assertFalse($this->store->missing('k'));
    }

    public function test_an_expired_entry_is_a_miss(): void
    {
        $this->pool->seed('k', 'v', time() - 1);

        $this->assertFalse($this->store->has('k'));
        $this->assertNull($this->store->get('k'));
    }

    /**
     * PSR-6 : deleteItem() rend true même quand la clé est absente.
     */
    public function test_delete_always_reports_success(): void
    {
        $this->assertTrue($this->store->delete('absent'));

        $this->store->set('k', 'v');

        $this->assertTrue($this->store->delete('k'));
        $this->assertFalse($this->store->has('k'));
    }

    public function test_delete_many_removes_every_key_at_once(): void
    {
        $this->store->set('a', 1);
        $this->store->set('b', 2);
        $this->store->set('c', 3);

        $this->assertTrue($this->store->deleteMany(['a', 'c', 'absent']));

        $this->assertFalse($this->store->has('a'));
        $this->assertTrue($this->store->has('b'));
        $this->assertFalse($this->store->has('c'));
    }

    public function test_delete_many_normalizes_the_keys(): void
    {
        $this->store->set('user:1', 'v');

        $this->assertTrue($this->store->deleteMany(['user:1']));
        $this->assertFalse($this->store->has('user:1'));
    }

    public function test_clear_empties_the_pool(): void
    {
        $this->store->set('a', 1);
        $this->store->set('b', 2);

        $this->assertTrue($this->store->clear());
        $this->assertSame([], $this->pool->entries());
    }

    /* -----------------------------------------------------------------
     |  Compteurs
     | -----------------------------------------------------------------
     */

    public function test_increment_starts_from_zero_and_accumulates(): void
    {
        $this->assertSame(1, $this->store->increment('cnt'));
        $this->assertSame(6, $this->store->increment('cnt', 5));
        $this->assertSame(6, $this->store->get('cnt'));
    }

    public function test_decrement_is_a_negative_increment(): void
    {
        $this->store->set('cnt', 10);

        $this->assertSame(7, $this->store->decrement('cnt', 3));
        $this->assertSame(6, $this->store->decrement('cnt'));
        $this->assertSame(8, $this->store->increment('cnt', 2));
    }

    public function test_counters_go_below_zero(): void
    {
        $this->assertSame(-5, $this->store->decrement('cnt', 5));
    }

    public function test_increment_casts_a_non_numeric_value_to_int(): void
    {
        $this->store->set('txt', 'abc');

        $this->assertSame(1, $this->store->increment('txt'));
    }

    /**
     * increment() réécrit l'item hydraté par getItem() sans toucher à son
     * expiration : le TTL restant est conservé.
     */
    public function test_increment_preserves_the_remaining_ttl(): void
    {
        $this->store->set('cnt', 1, 60);

        $before = $this->pool->entries()['cnt']['expiry'];

        $this->assertSame(2, $this->store->increment('cnt'));

        $this->assertSame($before, $this->pool->entries()['cnt']['expiry']);
        $this->assertGreaterThan(time(), $this->pool->entries()['cnt']['expiry']);
    }

    /* -----------------------------------------------------------------
     |  Normalisation des clés & accès bas niveau
     | -----------------------------------------------------------------
     */

    public function test_reserved_psr6_characters_are_replaced_by_underscores(): void
    {
        $this->store->set('user:{1}(a)/b\\c@d:e', 'v');

        $this->assertArrayHasKey('user__1__a__b_c_d_e', $this->pool->entries());
    }

    /**
     * // FIXME: la docblock de normalizeKey() annonce aussi les espaces, mais
     * l'expression `/[{}()\/\\@:]/` ne les couvre pas. ArrayAdapter les tolère,
     * les adaptateurs Redis/Filesystem les rejettent.
     */
    public function test_spaces_are_left_untouched_by_the_normalization(): void
    {
        $this->store->set('ma cle', 'v');

        $this->assertArrayHasKey('ma cle', $this->pool->entries());
    }

    public function test_the_normalized_key_is_the_one_read_back(): void
    {
        $this->store->set('user:1', 'v');

        $this->assertSame('v', $this->store->get('user:1'));
        $this->assertTrue($this->store->has('user:1'));
    }

    public function test_low_level_accessors_expose_the_adapter_and_the_name(): void
    {
        $this->assertInstanceOf(CacheItemPoolInterface::class, $this->store->getAdapter());
        $this->assertSame($this->pool, $this->store->getAdapter());
        $this->assertSame('memory', $this->store->getName());
    }
}
