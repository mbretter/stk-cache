<?php

namespace StkTest\Cache;

require_once __DIR__ . '/stubs.php';

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Stk\Cache\Cache;
use Stk\Cache\Item;
use Stk\Cache\Pool\Memory;

class CacheTest extends TestCase
{
    protected Cache $cache;

    protected Memory|MockObject $pool;

    public function setUp(): void
    {
        $this->pool = $this->createMock(Memory::class);

        $this->cache = new Cache($this->pool);
    }

    public function testGetSet(): void
    {
        $this->pool->expects($this->once())
            ->method('getItem')
            ->with('key1')
            ->willReturn(new Item('key1', 'val1'));
        $ret = $this->cache->getSet('key1', function () {
            return 'newval';
        });
        $this->assertEquals('val1', $ret);
    }

    public function testGetSetNotFound(): void
    {
        $this->pool->expects($this->once())
            ->method('getItem')
            ->with('key1')
            ->willReturn(new Item('key1'));
        $ret = $this->cache->getSet('key1', function () {
            return 'newval';
        });
        $this->assertEquals('newval', $ret);
    }

    public function testGetGrouped(): void
    {
        $this->pool->expects($this->once())
            ->method('getItems')
            ->with(['grp1', 'key1'])
            ->willReturn([
                'grp1' => new Item('grp1', 115),
                'key1' => new Item('key1', ['exp' => 105, 'val' => 'val1'])
            ]);

        $ret = $this->cache->getGrouped('grp1', 'key1');
        $this->assertEquals('val1', $ret);
    }

    public function testGetGroupedKeyNotFound(): void
    {
        $this->pool->expects($this->once())
            ->method('getItems')
            ->with(['grp1', 'key1'])
            ->willReturn([
                'grp1' => new Item('grp1', 115),
                'key1' => new Item('key1')
            ]);

        $ret = $this->cache->getGrouped('grp1', 'key1');
        $this->assertNull($ret);
    }

    public function testGetGroupedGroupNotFound(): void
    {
        $this->pool->expects($this->once())
            ->method('getItems')
            ->with(['grp1', 'key1'])
            ->willReturn([
                'grp1' => new Item('grp1'),
                'key1' => new Item('key1')
            ]);

        $ret = $this->cache->getGrouped('grp1', 'key1');
        $this->assertNull($ret);
    }

    public function testGetGroupedKeyvalInvalid1(): void
    {
        $this->pool->expects($this->once())
            ->method('getItems')
            ->with(['grp1', 'key1'])
            ->willReturn([
                'grp1' => new Item('grp1'),
                'key1' => new Item('key1', 'wrongvalue')
            ]);

        $ret = $this->cache->getGrouped('grp1', 'key1');
        $this->assertNull($ret);
    }

    public function testGetGroupedKeyvalInvalid2(): void
    {
        $this->pool->expects($this->once())
            ->method('getItems')
            ->with(['grp1', 'key1'])
            ->willReturn([
                'grp1' => new Item('grp1'),
                'key1' => new Item('key1', ['wrongvalue'])
            ]);

        $ret = $this->cache->getGrouped('grp1', 'key1');
        $this->assertNull($ret);
    }

    public function testGetGroupedKeyvalExpired(): void
    {
        $this->pool->expects($this->once())
            ->method('getItems')
            ->with(['grp1', 'key1'])
            ->willReturn([
                'grp1' => new Item('grp1', 115),
                'key1' => new Item('key1', ['exp' => 55, 'val' => 'val1'])
            ]);

        $ret = $this->cache->getGrouped('grp1', 'key1');
        $this->assertNull($ret);
    }

    public function testGetGroupedEmptyItems(): void
    {
        $this->pool->expects($this->once())
            ->method('getItems')
            ->with(['grp1', 'key1'])
            ->willReturn([]);

        $ret = $this->cache->getGrouped('grp1', 'key1');
        $this->assertNull($ret);
    }

    public function testGetSetGrouped(): void
    {
        $this->pool->expects($this->once())
            ->method('getItems')
            ->with(['grp1', 'key1'])
            ->willReturn([
                'grp1' => new Item('grp1', 115),
                'key1' => new Item('key1', ['exp' => 155, 'val' => 'val1'])
            ]);

        $this->pool->expects($this->never())->method('setMultiple');

        $ret = $this->cache->getSetGrouped('grp1', 'key1', function () {
            return 'newval';
        });
        $this->assertEquals('val1', $ret);
    }

    public function testGetGroupElseNotFound(): void
    {
        $this->pool->expects($this->once())
            ->method('getItems')
            ->with(['grp1', 'key1'])
            ->willReturn([
                'grp1' => new Item('grp1', 115),
                'key1' => new Item('key1')
            ]);

        $this->pool->expects($this->once())->method('setMultiple')->willReturn(true);

        $ret = $this->cache->getSetGrouped('grp1', 'key1', function () {
            return 'newval';
        });
        $this->assertEquals('newval', $ret);
    }

    public function testSetGrouped(): void
    {
        $this->pool->expects($this->once())
            ->method('setMultiple')
            ->with(['grp1' => 400, 'key1' => ['exp' => 400, 'val' => 'val1']], Item::TTL_DEFAULT)
            ->willReturn(true);
        $ret = $this->cache->setGrouped('grp1', 'key1', 'val1');
        $this->assertTrue($ret);
    }

}
