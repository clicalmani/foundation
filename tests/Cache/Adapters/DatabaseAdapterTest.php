<?php

declare(strict_types=1);

namespace Clicalmani\Core\Tests\Cache\Adapters;

use Clicalmani\Core\Cache\Adapters\DatabaseAdapter;
use Clicalmani\Core\Cache\Adapters\DatabaseCacheItem;
use Clicalmani\Core\Cache\CacheStore;
use Clicalmani\Core\Tests\Fakes\FakeDatabaseConnection;
use Clicalmani\Core\Tests\TestCase;

final class DatabaseAdapterTest extends TestCase
{
    private FakeDatabaseConnection $db;

    private DatabaseAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = new FakeDatabaseConnection;

        $this->bindService('database', $this->db);

        $this->adapter = (new DatabaseAdapter)->setPruneProbability(0);
    }

    private function seed(string $key, mixed $value, int $expiration = 0, string $table = 'cache'): void
    {
        $this->db->seed($table, $key, json_encode($value), $expiration);
    }

    private function row(string $key, string $table = 'cache'): ?array
    {
        return $this->db->row($table, $key);
    }

    /** @return list<string> */
    private function methods(): array
    {
        return array_map(fn (array $call) => $call[0], $this->db->calls);
    }

    /** @return list<string> */
    private function clauses(): array
    {
        $clauses = [];

        foreach ($this->db->calls as $call) {
            if ($call[0] === 'where') {
                $clauses[] = $call[1]['sql'];
            }
        }

        return $clauses;
    }

    /* -----------------------------------------------------------------
     |  getItem()
     | -----------------------------------------------------------------
     */

    public function test_get_item_returns_a_miss_for_an_unknown_key(): void
    {
        $item = $this->adapter->getItem('inconnu');

        $this->assertInstanceOf(DatabaseCacheItem::class, $item);
        $this->assertSame('inconnu', $item->getKey());
        $this->assertFalse($item->isHit());
        $this->assertNull($item->get());
        $this->assertSame([], $this->db->rows('cache'));
    }

    public function test_get_item_hydrates_a_stored_row(): void
    {
        $this->seed('k', ['a' => 1], time() + 60);

        $item = $this->adapter->getItem('k');

        $this->assertTrue($item->isHit());
        $this->assertSame(['a' => 1], $item->get());
        $this->assertGreaterThan(time(), $item->getExpiration());
    }

    public function test_get_item_treats_a_zero_expiration_as_permanent(): void
    {
        $this->seed('k', 'v');

        $item = $this->adapter->getItem('k');

        $this->assertTrue($item->isHit());
        $this->assertNull($item->getExpiration());
    }

    public function test_get_item_deletes_an_expired_row_and_returns_a_miss(): void
    {
        $this->seed('k', 'v', time() - 1);

        $item = $this->adapter->getItem('k');

        $this->assertFalse($item->isHit());
        $this->assertNull($this->row('k'));
    }

    public function test_get_item_returns_the_deferred_item_without_querying(): void
    {
        $deferred = new DatabaseCacheItem('k');

        $this->adapter->saveDeferred($deferred);
        $this->db->calls = [];

        $this->assertSame($deferred, $this->adapter->getItem('k'));
        $this->assertSame([], $this->db->calls);
    }

    /**
     * Un payload non JSON est relu en base64 + unserialize.
     */
    public function test_get_item_falls_back_to_a_serialized_payload(): void
    {
        $object = new \stdClass;
        $object->name = 'tonka';

        $this->db->seed('cache', 'k', base64_encode(serialize($object)));

        $item = $this->adapter->getItem('k');

        $this->assertTrue($item->isHit());
        $this->assertEquals($object, $item->get());
    }

    public function test_get_item_reads_from_the_configured_table(): void
    {
        $adapter = (new DatabaseAdapter('tonka_cache'))->setPruneProbability(0);

        $this->db->seed('tonka_cache', 'k', 'v');

        $this->assertTrue($adapter->getItem('k')->isHit());
        $this->assertFalse($this->adapter->getItem('k')->isHit());
    }

    /* -----------------------------------------------------------------
     |  hasItem()
     | -----------------------------------------------------------------
     */

    public function test_has_item_follows_the_row_state(): void
    {
        $this->assertFalse($this->adapter->hasItem('k'));

        $this->seed('k', 'v');

        $this->assertTrue($this->adapter->hasItem('k'));
    }

    public function test_has_item_deletes_an_expired_row(): void
    {
        $this->seed('k', 'v', time() - 1);

        $this->assertFalse($this->adapter->hasItem('k'));
        $this->assertNull($this->row('k'));
    }

    public function test_has_item_reads_the_deferred_item(): void
    {
        $this->adapter->saveDeferred((new DatabaseCacheItem('k'))->markHit('v', null));

        $this->assertTrue($this->adapter->hasItem('k'));
        $this->assertSame([], $this->db->rows('cache'));
    }

    /* -----------------------------------------------------------------
     |  save()
     | -----------------------------------------------------------------
     */

    public function test_save_inserts_a_new_row_when_the_update_matches_nothing(): void
    {
        $item = (new DatabaseCacheItem('k'))->markHit('v', null)->set('v');

        $this->assertTrue($this->adapter->save($item));

        $this->assertSame(['key' => 'k', 'value' => '"v"', 'expiration' => 0], $this->row('k'));
        $this->assertContains('insert', $this->methods());
    }

    public function test_save_updates_an_existing_row_without_inserting(): void
    {
        $this->seed('k', 'avant');

        $this->adapter->save((new DatabaseCacheItem('k'))->markHit('avant', null)->set('apres'));

        $this->assertSame('"apres"', $this->row('k')['value']);
        $this->assertNotContains('insert', $this->methods());
    }

    public function test_save_applies_the_default_lifetime_when_the_item_has_none(): void
    {
        $adapter = (new DatabaseAdapter('cache', 120))->setPruneProbability(0);

        $before = time();

        $adapter->save((new DatabaseCacheItem('k'))->markHit('v', null));

        $this->assertGreaterThanOrEqual($before + 120, $this->row('k')['expiration']);
        $this->assertLessThanOrEqual(time() + 120, $this->row('k')['expiration']);
    }

    public function test_save_stores_zero_for_a_permanent_item_without_default_lifetime(): void
    {
        $this->adapter->save((new DatabaseCacheItem('k'))->markHit('v', null));

        $this->assertSame(0, $this->row('k')['expiration']);
    }

    /**
     * FIXME : DatabaseCacheItem::get() renvoie null tant que isHit est faux,
     * donc la première écriture d'une clé persiste "null" au lieu de la valeur.
     */
    public function test_save_persists_null_for_a_freshly_set_item(): void
    {
        $this->adapter->save((new DatabaseCacheItem('k'))->set('valeur')->expiresAfter(60));

        $this->assertSame('null', $this->row('k')['value']);
    }

    public function test_save_encodes_a_json_friendly_value(): void
    {
        $this->adapter->save((new DatabaseCacheItem('k'))->markHit(null, null)->set(['a' => [1, 2]]));

        $this->assertSame('{"a":[1,2]}', $this->row('k')['value']);
    }

    /**
     * json_encode() échoue sur NAN : l'adapter bascule en base64 + serialize,
     * ce que getItem() sait relire.
     */
    public function test_save_falls_back_to_serialization_when_json_encoding_fails(): void
    {
        $this->adapter->save((new DatabaseCacheItem('k'))->markHit(NAN, null));

        $stored = $this->row('k')['value'];

        $this->assertSame(base64_encode(serialize(NAN)), $stored);
        $this->assertNan($this->adapter->getItem('k')->get());
    }

    public function test_save_refuses_a_foreign_cache_item(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DatabaseAdapter only accepts DatabaseCacheItem instances.');

        $this->adapter->save($this->createMock(\Psr\Cache\CacheItemInterface::class));
    }

    /**
     * FIXME : hasItem() consulte la file différée. Quand une clé est différée
     * mais absente de la table, save() croit à une course concurrente, repasse
     * par UPDATE (0 ligne touchée) et n'insère jamais : l'écriture est perdue
     * en silence alors que save() renvoie true.
     */
    public function test_save_loses_the_write_when_the_key_is_also_deferred(): void
    {
        $this->adapter->saveDeferred((new DatabaseCacheItem('k'))->markHit('differee', null));

        $this->assertTrue($this->adapter->save((new DatabaseCacheItem('k'))->markHit('enregistree', null)));

        $this->assertSame([], $this->db->rows('cache'));
        $this->assertNotContains('insert', $this->methods());
    }

    public function test_save_removes_the_key_from_the_deferred_queue(): void
    {
        $deferred = (new DatabaseCacheItem('k'))->markHit('differee', null);

        $this->adapter->saveDeferred($deferred);
        $this->adapter->save($deferred);

        $this->adapter->commit();

        $this->assertSame(0, $this->db->transactions);
    }

    /* -----------------------------------------------------------------
     |  saveDeferred() / commit()
     | -----------------------------------------------------------------
     */

    public function test_save_deferred_does_not_touch_the_database(): void
    {
        $this->assertTrue($this->adapter->saveDeferred((new DatabaseCacheItem('k'))->markHit('v', null)));

        $this->assertSame([], $this->db->rows('cache'));
        $this->assertSame([], $this->db->calls);
    }

    public function test_save_deferred_refuses_a_foreign_item(): void
    {
        $this->assertFalse($this->adapter->saveDeferred($this->createMock(\Psr\Cache\CacheItemInterface::class)));
    }

    public function test_commit_without_deferred_items_is_a_no_op(): void
    {
        $this->assertTrue($this->adapter->commit());

        $this->assertSame(0, $this->db->transactions);
        $this->assertSame([], $this->db->calls);
    }

    /**
     * FIXME : commit() délègue à save(), qui appelle hasItem(). Or hasItem()
     * répond « hit » pour une clé encore présente dans la file différée :
     * save() croit à une course concurrente, repasse par UPDATE (0 ligne
     * touchée) et n'insère jamais. Un item différé en état « hit » n'est donc
     * jamais écrit — et commit() renvoie true.
     */
    public function test_commit_writes_nothing_for_a_deferred_hit_item(): void
    {
        $this->adapter->saveDeferred((new DatabaseCacheItem('k'))->markHit('v', null));

        $this->assertTrue($this->adapter->commit());

        $this->assertSame([], $this->db->rows('cache'));
        $this->assertNotContains('insert', $this->methods());
        $this->assertSame(1, $this->db->transactions);
    }

    public function test_commit_wraps_every_deferred_item_in_a_single_transaction(): void
    {
        $this->adapter->saveDeferred((new DatabaseCacheItem('a'))->markHit('1', null));
        $this->adapter->saveDeferred((new DatabaseCacheItem('b'))->markHit('2', null));

        $this->adapter->commit();

        $this->assertSame(1, $this->db->transactions);
        // 2 UPDATE par item : le premier ne touche aucune ligne, le second est
        // la « retentative après course » déclenchée par hasItem().
        $this->assertSame(4, count(array_filter($this->methods(), fn (string $m) => $m === 'update')));
    }

    /**
     * Un item différé issu d'un miss (isHit faux) évite le piège précédent,
     * mais retombe sur celui de get() : la ligne insérée contient "null".
     */
    public function test_commit_inserts_a_null_row_for_a_deferred_miss_item(): void
    {
        $item = $this->adapter->getItem('k');

        $item->set('valeur');
        $item->expiresAfter(60);

        $this->adapter->saveDeferred($item);
        $this->adapter->commit();

        $this->assertSame('null', $this->row('k')['value']);
        $this->assertGreaterThan(time(), $this->row('k')['expiration']);
    }

    public function test_commit_empties_the_deferred_queue(): void
    {
        $this->adapter->saveDeferred((new DatabaseCacheItem('a'))->markHit('1', null));

        $this->adapter->commit();
        $this->db->calls = [];
        $this->db->transactions = 0;

        $this->adapter->commit();

        $this->assertSame(0, $this->db->transactions);
        $this->assertSame([], $this->db->calls);
    }

    /* -----------------------------------------------------------------
     |  getItems()
     | -----------------------------------------------------------------
     */

    public function test_get_items_without_keys_returns_nothing(): void
    {
        $this->assertSame([], $this->adapter->getItems());
        $this->assertSame([], $this->adapter->getItems([]));
        $this->assertSame([], $this->db->calls);
    }

    public function test_get_items_keys_the_result_by_requested_key(): void
    {
        $this->seed('a', 'va');

        $items = $this->adapter->getItems(['a', 'b']);

        $this->assertSame(['a', 'b'], array_keys($items));
        $this->assertTrue($items['a']->isHit());
        $this->assertSame('va', $items['a']->get());
        $this->assertFalse($items['b']->isHit());
    }

    public function test_get_items_drops_expired_rows(): void
    {
        $this->seed('old', 'v', time() - 1);
        $this->seed('fresh', 'v', time() + 60);

        $items = $this->adapter->getItems(['old', 'fresh']);

        $this->assertFalse($items['old']->isHit());
        $this->assertTrue($items['fresh']->isHit());
        $this->assertNull($this->row('old'));
    }

    /**
     * Contrairement à getItem(), getItems() ne consulte pas la file différée.
     */
    public function test_get_items_ignores_deferred_items(): void
    {
        $this->adapter->saveDeferred((new DatabaseCacheItem('k'))->markHit('v', null));

        $items = $this->adapter->getItems(['k']);

        $this->assertFalse($items['k']->isHit());
    }

    /* -----------------------------------------------------------------
     |  Suppression
     | -----------------------------------------------------------------
     */

    public function test_delete_item_removes_the_row_and_always_returns_true(): void
    {
        $this->seed('k', 'v');

        $this->assertTrue($this->adapter->deleteItem('k'));
        $this->assertNull($this->row('k'));

        $this->assertTrue($this->adapter->deleteItem('k'));
    }

    public function test_delete_item_drops_the_deferred_entry(): void
    {
        $this->adapter->saveDeferred((new DatabaseCacheItem('k'))->markHit('v', null));

        $this->adapter->deleteItem('k');

        $this->assertFalse($this->adapter->hasItem('k'));
    }

    public function test_delete_items_without_keys_is_a_no_op(): void
    {
        $this->assertTrue($this->adapter->deleteItems([]));
        $this->assertSame([], $this->db->calls);
    }

    public function test_delete_items_removes_every_key_in_one_query(): void
    {
        $this->seed('a', 1);
        $this->seed('b', 2);
        $this->seed('c', 3);

        $this->assertTrue($this->adapter->deleteItems(['a', 'b']));

        $this->assertSame(['c'], array_keys($this->db->rows('cache')));
        $this->assertSame(1, count(array_filter($this->methods(), fn (string $m) => $m === 'delete')));
    }

    public function test_clear_truncates_the_table_and_the_deferred_queue(): void
    {
        $this->seed('a', 1);
        $this->adapter->saveDeferred((new DatabaseCacheItem('b'))->markHit('2', null));

        $this->assertTrue($this->adapter->clear());

        $this->assertSame([], $this->db->rows('cache'));
        $this->assertTrue($this->adapter->commit());
        $this->assertSame([], $this->db->rows('cache'));
    }

    /* -----------------------------------------------------------------
     |  increment()
     | -----------------------------------------------------------------
     */

    public function test_increment_locks_the_row_inside_a_transaction(): void
    {
        $this->seed('cnt', 5);

        $this->assertSame(6, $this->adapter->increment('cnt'));

        $this->assertSame(1, $this->db->transactions);
        $this->assertContains('lockForUpdate', $this->methods());
        $this->assertSame('6', $this->row('cnt')['value']);
    }

    public function test_increment_accepts_a_custom_step(): void
    {
        $this->seed('cnt', 5);

        $this->assertSame(15, $this->adapter->increment('cnt', 10));
        $this->assertSame(2, $this->adapter->increment('cnt', -13));
    }

    /**
     * FIXME : sur une clé absente, increment() passe par save() avec un item
     * non « hit » — la ligne reçoit donc "null". L'incrément suivant repart de
     * 0 : le premier pas est perdu.
     */
    public function test_increment_loses_the_first_step_on_a_missing_key(): void
    {
        $this->assertSame(1, $this->adapter->increment('cnt'));
        $this->assertSame('null', $this->row('cnt')['value']);

        $this->assertSame(1, $this->adapter->increment('cnt'));
        $this->assertSame(2, $this->adapter->increment('cnt'));
    }

    /* -----------------------------------------------------------------
     |  prune()
     | -----------------------------------------------------------------
     */

    public function test_prune_deletes_only_the_expired_rows(): void
    {
        $this->seed('old', 'v', time() - 10);
        $this->seed('fresh', 'v', time() + 60);
        $this->seed('forever', 'v');

        $this->adapter->prune();

        $this->assertSame(['fresh', 'forever'], array_keys($this->db->rows('cache')));
    }

    /**
     * FIXME : prune() est déclarée « : int » mais renvoie le booléen de
     * delete(), converti en 1 même quand aucune ligne n'a été supprimée.
     */
    public function test_prune_returns_one_even_when_nothing_was_deleted(): void
    {
        $this->seed('fresh', 'v', time() + 60);

        $this->assertSame(1, $this->adapter->prune());
    }

    public function test_a_zero_prune_probability_never_prunes(): void
    {
        $this->seed('old', 'v', time() - 10);
        $this->seed('fresh', 'v', time() + 60);

        $this->adapter->getItem('fresh');

        $this->assertNotContains('expiration > 0 AND expiration < ?', $this->clauses());
        $this->assertNotNull($this->row('old'));
    }

    public function test_a_full_prune_probability_prunes_on_every_read(): void
    {
        $adapter = (new DatabaseAdapter)->setPruneProbability(100);

        $this->seed('old', 'v', time() - 10);

        $adapter->getItem('missing');

        $this->assertContains('expiration > 0 AND expiration < ?', $this->clauses());
        $this->assertNull($this->row('old'));
    }

    public function test_set_prune_probability_clamps_the_value_and_is_fluent(): void
    {
        $adapter = new DatabaseAdapter;

        $this->assertSame($adapter, $adapter->setPruneProbability(50));
        $this->assertSame(50, $this->probability($adapter));

        $adapter->setPruneProbability(-10);
        $this->assertSame(0, $this->probability($adapter));

        $adapter->setPruneProbability(500);
        $this->assertSame(100, $this->probability($adapter));
    }

    /* -----------------------------------------------------------------
     |  Via CacheStore
     | -----------------------------------------------------------------
     */

    /**
     * FIXME : conséquence directe du get()/isHit de DatabaseCacheItem —
     * un store database mémorise la clé mais perd la valeur au premier write.
     */
    public function test_a_store_on_the_database_adapter_remembers_the_key_but_loses_the_value(): void
    {
        $store = new CacheStore($this->adapter, 'database');

        $this->assertTrue($store->set('k', 'valeur', 60));
        $this->assertTrue($store->has('k'));
        $this->assertNull($store->get('k'));
        $this->assertNull($store->get('k', 'defaut'));
    }

    public function test_a_store_can_update_a_value_it_already_knows(): void
    {
        $store = new CacheStore($this->adapter, 'database');

        $store->set('k', 'v1');
        $store->set('k', 'v2');

        $this->assertSame('v2', $store->get('k'));
    }

    public function test_remember_recomputes_and_stores_null_on_the_database_adapter(): void
    {
        $store = new CacheStore($this->adapter, 'database');

        $calls = 0;

        $this->assertSame('calcule', $store->remember('k', 60, function () use (&$calls) {
            ++$calls;

            return 'calcule';
        }));

        $this->assertSame(1, $calls);
        $this->assertNull($store->remember('k', 60, fn () => 'autre'));
        $this->assertSame(1, $calls);
    }

    public function test_a_store_deletes_and_clears_the_database_table(): void
    {
        $this->seed('a', 'va');
        $this->seed('b', 'vb');

        $store = new CacheStore($this->adapter, 'database');

        $this->assertSame('va', $store->pull('a'));
        $this->assertNull($this->row('a'));

        $store->clear();

        $this->assertSame([], $this->db->rows('cache'));
    }

    private function probability(DatabaseAdapter $adapter): int
    {
        return (new \ReflectionProperty(DatabaseAdapter::class, 'pruneProbability'))->getValue($adapter);
    }
}
