<?php

namespace StkTest\Cache\Pool;

require_once __DIR__ . '/../stubs.php';

use ArrayIterator;
use Memcached;
use Memcached as MemcachedExt;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Stk\Cache\Item;
use Stk\Cache\Pool\Memcached as MemcachedPool;
use Stk\Cache\Pool\Memory;

class MemcachedTest extends TestCase
{
    /** @var Memory */
    protected $pool;

    /** @var MockObject|Memcached */
    protected $memcached;

    public function setUp(): void
    {
        $this->memcached = $this->createMock(Memcached::class);

        $this->pool = new MemcachedPool($this->memcached);
    }

    public function testGet(): void
    {
        $this->memcached->expects($this->once())
            ->method('get')
            ->with('key1')
            ->willReturn('val1');
        $ret = $this->pool->get('key1');
        $this->assertEquals('val1', $ret);
    }

    public function testGetItem(): void
    {
        $this->memcached->expects($this->once())
            ->method('get')
            ->with('key1')
            ->willReturn('val1');
        $this->memcached->method('getResultCode')->willReturn(MemcachedExt::RES_SUCCESS);
        $item = $this->pool->getItem('key1');

        $this->assertEquals('val1', $item->get());
        $this->assertTrue($item->isHit());
    }

    public function testGetItemNotFound(): void
    {
        $this->memcached->expects($this->once())
            ->method('get')
            ->with('key1')
            ->willReturn(false);
        $this->memcached->method('getResultCode')->willReturn(MemcachedExt::RES_NOTFOUND);
        $item = $this->pool->getItem('key1');

        $this->assertFalse($item->isHit());
    }

    public function testGetItemWithFailure(): void
    {
        $this->memcached->expects($this->once())
            ->method('get')
            ->with('key1')
            ->willReturn(false);
        $this->memcached->method('getResultCode')->willReturn(MemcachedExt::RES_SERVER_ERROR);
        $item = $this->pool->getItem('key1');

        $this->assertFalse($item->isHit());
    }

    public function testSet(): void
    {
        $this->memcached->expects($this->once())
            ->method('set')
            ->with('key1', 'val1', 300)
            ->willReturn(true);
        $ret = $this->pool->set('key1', 'val1');
        $this->assertTrue($ret);
    }

    public function testDelete(): void
    {
        $this->memcached->expects($this->once())
            ->method('delete')
            ->with('key1')
            ->willReturn(true);
        $ret = $this->pool->delete('key1');
        $this->assertTrue($ret);
    }

    public function testClear(): void
    {
        $this->memcached->expects($this->once())
            ->method('flush')
            ->willReturn(true);
        $this->assertTrue($this->pool->clear());
    }

    public function testGetMultiple(): void
    {
        $expected = [
            'key1' => 'val1',
            'key2' => 'val2'
        ];
        $this->memcached->expects($this->once())
            ->method('getMulti')
            ->with(array_keys($expected))
            ->willReturn($expected);
        $ret = $this->pool->getMultiple(array_keys($expected));
        $this->assertEquals($expected, $ret);
    }

    public function testGetMultipleWithFailure(): void
    {
        $expected = [
            'key1' => null,
            'key2' => null
        ];
        $this->memcached->expects($this->once())
            ->method('getMulti')
            ->with(array_keys($expected))
            ->willReturn(false);
        $ret = $this->pool->getMultiple(array_keys($expected));
        $this->assertEquals($expected, $ret);
    }

    public function testGetMultipleWithIterable(): void
    {
        $expected = [
            'key1' => 'val1',
            'key2' => 'val2'
        ];
        $iterable = new ArrayIterator(array_keys($expected));
        $this->memcached->expects($this->once())
            ->method('getMulti')
            ->with(array_keys($expected))
            ->willReturn($expected);
        $ret = $this->pool->getMultiple($iterable);
        $this->assertEquals($expected, $ret);
    }

    public function testSetMultiple(): void
    {
        $this->memcached->expects($this->once())
            ->method('setMulti')
            ->with(['key1' => 'val1', 'key2' => 'val2'], 300)
            ->willReturn(true);

        $ret = $this->pool->setMultiple(['key1' => 'val1', 'key2' => 'val2']);
        $this->assertTrue($ret);
    }

    public function testSetMultipleWithIterable(): void
    {
        $expected = [
            'key1' => 'val1',
            'key2' => 'val2'
        ];
        $iterable = new ArrayIterator($expected);
        $this->memcached->expects($this->once())
            ->method('setMulti')
            ->with(['key1' => 'val1', 'key2' => 'val2'], 300)
            ->willReturn(true);

        $ret = $this->pool->setMultiple($iterable);
        $this->assertTrue($ret);
    }

    public function testDeleteMultiple(): void
    {
        $this->memcached->expects($this->once())
            ->method('deleteMulti')
            ->with(['key1', 'key2'])
            ->willReturn(['key1' => true, 'key2' => true]);
        $ret = $this->pool->deleteMultiple(['key1', 'key2']);
        $this->assertTrue($ret);
    }

    public function testDeleteMultipleWithError(): void
    {
        $this->memcached->expects($this->once())
            ->method('deleteMulti')
            ->with(['key1', 'key2'])
            ->willReturn(['key1' => true, 'key2' => false]);
        $ret = $this->pool->deleteMultiple(['key1', 'key2']);
        $this->assertFalse($ret);
    }

    public function testHas(): void
    {
        $this->memcached->expects($this->once())
            ->method('get')
            ->with('key1')
            ->willReturn('val1');
        $ret = $this->pool->has('key1');
        $this->assertTrue($ret);
    }

    public function testHasNot1(): void
    {
        $this->memcached->expects($this->once())
            ->method('get')
            ->with('key1')
            ->willReturn(false);
        $ret = $this->pool->has('key1');
        $this->assertFalse($ret);
    }

    public function testHasNot2(): void
    {
        $this->memcached->expects($this->once())
            ->method('get')
            ->with('key1')
            ->willReturn('foo');
        $this->memcached->method('getResultCode')->willReturn(MemcachedExt::RES_NOTFOUND);

        $ret = $this->pool->has('key1');
        $this->assertFalse($ret);
    }

    public function testCommit(): void
    {
        $this->assertTrue($this->pool->commit());
    }

    // items

    public function testGetItems(): void
    {
        $expected = [
            'key1' => new Item('key1', 'val1'),
            'key2' => new Item('key2', 'val2')
        ];
        $this->memcached->expects($this->once())
            ->method('getMulti')
            ->with(array_keys($expected))
            ->willReturn(['key1' => 'val1', 'key2' => 'val2']);
        $ret = $this->pool->getItems(array_keys($expected));
        $this->assertEquals($expected, $ret);
    }

    public function testGetItemsWithMissing(): void
    {
        $expected = [
            'key1' => new Item('key1', 'val1'),
            'key2' => (new Item('key2'))->setIsHit(false)
        ];
        $this->memcached->expects($this->once())
            ->method('getMulti')
            ->with(array_keys($expected))
            ->willReturn(['key1' => 'val1']);
        $ret = $this->pool->getItems(array_keys($expected));
        $this->assertEquals($expected, $ret);
    }

    public function testGetWithFailure(): void
    {
        $expected = [
            'key1' => new Item('key1'),
            'key2' => new Item('key2')
        ];
        $this->memcached->expects($this->once())
            ->method('getMulti')
            ->willReturn(false);
        $ret = $this->pool->getItems(array_keys($expected));
        $this->assertEquals($expected, $ret);
    }

    public function testHasItem(): void
    {
        $this->memcached->expects($this->once())->method('get')->with('key1')->willReturn('val1');
        $this->assertTrue($this->pool->hasItem('key1'));
    }

    public function testDeleteItem(): void
    {
        $this->memcached->expects($this->once())->method('delete')->with('key1')->willReturn(true);
        $this->assertTrue($this->pool->deleteItem('key1'));
    }

    public function testDeleteItems(): void
    {
        $this->memcached->expects($this->once())->method('deleteMulti')->with(['key1', 'key2'])->willReturn(['key1' => true, 'key2' => true]);
        $ret = $this->pool->deleteItems(['key1', 'key2']);
        $this->assertTrue($ret);
    }

    public function testSave(): void
    {
        $item = new Item('key1', 'val1', 876);
        $this->memcached->expects($this->once())->method('set')->with('key1', 'val1', 876)->willReturn(true);
        $ret = $this->pool->save($item);
        $this->assertTrue($ret);
    }

    public function testSaveDeferred(): void
    {
        $item = new Item('key1', 'val1', 876);
        $this->memcached->expects($this->once())->method('set')->with('key1', 'val1', 876)->willReturn(true);
        $ret = $this->pool->saveDeferred($item);
        $this->assertTrue($ret);
    }

}
