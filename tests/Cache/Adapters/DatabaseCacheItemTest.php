<?php

declare(strict_types=1);

namespace Clicalmani\Core\Tests\Cache\Adapters;

use Clicalmani\Core\Cache\Adapters\DatabaseCacheItem;
use Clicalmani\Core\Tests\TestCase;

final class DatabaseCacheItemTest extends TestCase
{
    /* -----------------------------------------------------------------
     |  État initial
     | -----------------------------------------------------------------
     */

    public function test_a_fresh_item_is_a_miss(): void
    {
        $item = new DatabaseCacheItem('k');

        $this->assertSame('k', $item->getKey());
        $this->assertFalse($item->isHit());
        $this->assertNull($item->get());
        $this->assertNull($item->getExpiration());
        $this->assertFalse($item->isDirty());
    }

    /* -----------------------------------------------------------------
     |  set()
     | -----------------------------------------------------------------
     */

    /**
     * FIXME : set() n'active pas isHit, et get() renvoie null tant que
     * l'item est un miss. PSR-6 attend au contraire que get() renvoie la
     * valeur posée par set() (c'est ce que fait Symfony\Cache\CacheItem).
     * Conséquence : DatabaseAdapter::save() persiste json_encode(null)
     * pour toute première écriture.
     */
    public function test_set_does_not_make_the_value_readable(): void
    {
        $item = (new DatabaseCacheItem('k'))->set('valeur');

        $this->assertTrue($item->isDirty());
        $this->assertNull($item->get());
        $this->assertFalse($item->isHit());
    }

    public function test_set_overwrites_the_value_of_a_hit_item(): void
    {
        $item = (new DatabaseCacheItem('k'))->markHit('avant', null);

        $this->assertSame('valeur', $item->set('valeur')->get());
        $this->assertTrue($item->isDirty());
    }

    public function test_set_is_fluent(): void
    {
        $item = new DatabaseCacheItem('k');

        $this->assertSame($item, $item->set(1));
    }

    /* -----------------------------------------------------------------
     |  Expiration
     | -----------------------------------------------------------------
     */

    public function test_expires_at_stores_a_timestamp(): void
    {
        $item = new DatabaseCacheItem('k');
        $at   = new \DateTimeImmutable('2030-01-01 00:00:00');

        $this->assertSame($item, $item->expiresAt($at));

        $this->assertSame($at->getTimestamp(), $item->getExpiration());
        $this->assertTrue($item->isDirty());
    }

    public function test_expires_at_null_makes_the_item_permanent(): void
    {
        $item = (new DatabaseCacheItem('k'))->markHit('v', time() + 60);

        $item->expiresAt(null);

        $this->assertNull($item->getExpiration());
    }

    public function test_expires_after_accepts_a_number_of_seconds(): void
    {
        $before = time();

        $item = (new DatabaseCacheItem('k'))->expiresAfter(60);

        $this->assertGreaterThanOrEqual($before + 60, $item->getExpiration());
        $this->assertLessThanOrEqual(time() + 60, $item->getExpiration());
        $this->assertTrue($item->isDirty());
    }

    public function test_expires_after_accepts_a_date_interval(): void
    {
        $before = time();

        $item = (new DatabaseCacheItem('k'))->expiresAfter(new \DateInterval('PT90S'));

        $this->assertGreaterThanOrEqual($before + 90, $item->getExpiration());
        $this->assertLessThanOrEqual(time() + 90, $item->getExpiration());
    }

    public function test_expires_after_null_makes_the_item_permanent(): void
    {
        $item = (new DatabaseCacheItem('k'))->markHit('v', time() + 60);

        $item->expiresAfter(null);

        $this->assertNull($item->getExpiration());
    }

    /* -----------------------------------------------------------------
     |  markHit() / markMiss()
     | -----------------------------------------------------------------
     */

    public function test_mark_hit_hydrates_the_item_and_clears_the_dirty_flag(): void
    {
        $expiration = time() + 60;

        $item = (new DatabaseCacheItem('k'))->set('brouillon')->expiresAfter(30);

        $this->assertSame($item, $item->markHit(['a' => 1], $expiration));

        $this->assertTrue($item->isHit());
        $this->assertSame(['a' => 1], $item->get());
        $this->assertSame($expiration, $item->getExpiration());
        $this->assertFalse($item->isDirty());
    }

    public function test_mark_hit_accepts_a_null_expiration(): void
    {
        $item = (new DatabaseCacheItem('k'))->markHit('v', null);

        $this->assertTrue($item->isHit());
        $this->assertNull($item->getExpiration());
    }

    public function test_mark_hit_can_store_a_null_value(): void
    {
        $item = (new DatabaseCacheItem('k'))->markHit(null, null);

        $this->assertTrue($item->isHit());
        $this->assertNull($item->get());
    }

    public function test_mark_miss_resets_everything(): void
    {
        $item = (new DatabaseCacheItem('k'))->markHit('v', time() + 60);

        $this->assertSame($item, $item->markMiss());

        $this->assertFalse($item->isHit());
        $this->assertNull($item->get());
        $this->assertNull($item->getExpiration());
        $this->assertFalse($item->isDirty());
    }
}
