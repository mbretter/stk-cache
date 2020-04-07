<?php

namespace StkTest\Cache;

require_once __DIR__ . '/stubs.php';

use DateInterval;
use DateTime;
use PHPUnit\Framework\TestCase;
use Stk\Cache\Item;

class ItemTest extends TestCase
{
    public function testGetNoHit()
    {
        $item = new Item('key1');
        $item->setIsHit(false);
        $this->assertNull($item->get());
    }

    public function testExpiresAt()
    {
        $item = new Item('key1');
        $item->expiresAt(new DateTime('2020-04-01 12:00:00'));
        $this->assertLessThan(0, $item->getTtl()); // lame check
    }

    public function testExpiresAtNull()
    {
        $item = new Item('key1');
        $item->expiresAt(null);
        $this->assertEquals(Item::TTL_FOREVER, $item->getTtl());
    }

    public function testExpiresAfter()
    {
        $item = new Item('key1');
        $item->expiresAfter(99);
        $this->assertEquals(99, $item->getTtl());
    }

    public function testExpiresAfterInterval()
    {
        $item = new Item('key1');
        $item->expiresAfter(new DateInterval('PT99S'));
        $this->assertEquals(99, $item->getTtl());
    }

    public function testExpiresAfterNull()
    {
        $item = new Item('key1');
        $item->expiresAfter(null);
        $this->assertEquals(Item::TTL_FOREVER, $item->getTtl());
    }
}
