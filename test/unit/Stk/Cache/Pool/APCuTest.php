<?php

namespace StkTest\Cache\Pool;

require_once __DIR__ . '/../stubs.php';

use ArrayIterator;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Stk\Cache\Item;
use Stk\Cache\Pool\APCu;

class APCuTest extends TestCase
{
    use PHPMock;

    protected APCu $pool;

    protected MockObject $apcu_exists;
    protected MockObject $apcu_fetch;
    protected MockObject $apcu_store;
    protected MockObject $apcu_delete;

    public function setUp(): void
    {
        $this->pool = new APCu();

        $this->apcu_exists = $this->getFunctionMock('Stk\Cache\Pool', 'apcu_exists');
        $this->apcu_fetch  = $this->getFunctionMock('Stk\Cache\Pool', 'apcu_fetch');
        $this->apcu_store  = $this->getFunctionMock('Stk\Cache\Pool', 'apcu_store');
        $this->apcu_delete = $this->getFunctionMock('Stk\Cache\Pool', 'apcu_delete');
    }

    public function testGet(): void
    {
        $this->apcu_exists->expects($this->once())->willReturn(true);
        $this->apcu_fetch->expects($this->once())
            ->with('key1')
            ->willReturn('val1');

        $ret = $this->pool->get('key1');
        $this->assertEquals('val1', $ret);
    }

    public function testGetItem(): void
    {
        $this->apcu_exists->expects($this->once())->willReturn(true);
        $this->apcu_fetch->expects($this->once())
            ->with('key1')
            ->willReturn('val1');

        $item = $this->pool->getItem('key1');

        $this->assertEquals('val1', $item->get());
        $this->assertTrue($item->isHit());
    }

    public function testGetItemNotFound(): void
    {
        $this->apcu_exists->expects($this->once())->willReturn(false);
        $this->apcu_fetch->expects($this->never());
        $item = $this->pool->getItem('key1');

        $this->assertFalse($item->isHit());
    }

// currently not testable
//    public function testGetItemWithFailure(): void
//    {
//        $this->apcu_exists->expects($this->once())->willReturn(true);
//        $this->apcu_fetch->expects($this->once())
//            ->with('key1')
//            ->will($this->returnCallback(function ($patient, &$success) {
//                $success = false;
//
//                return false;
//            }));
//        $item = $this->pool->getItem('key1');
//
//        $this->assertNull($item->get());
//        $this->assertFalse($item->isHit());
//    }

    public function testSet(): void
    {
        $this->apcu_store->expects($this->once())
            ->with('key1', 'val1', 300)
            ->willReturn(true);

        $ret = $this->pool->set('key1', 'val1');
        $this->assertTrue($ret);
    }

    public function testDelete(): void
    {
        $this->apcu_delete->expects($this->once())->with('key1')
            ->willReturn(true);

        $ret = $this->pool->delete('key1');
        $this->assertTrue($ret);
    }

    public function testClear(): void
    {
        $this->getFunctionMock('Stk\Cache\Pool', 'apcu_clear_cache')->expects($this->once())->willReturn(true);

        $this->assertTrue($this->pool->clear());
    }

    public function testGetMultiple(): void
    {
        $expected = [
            'key1' => 'val1',
            'key2' => 'val2'
        ];

        $this->apcu_fetch->expects($this->once())
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

        $this->apcu_fetch->expects($this->once())
            ->with(array_keys($expected))
            ->willReturn(false);

        $ret = $this->pool->getMultiple(array_keys($expected));
        $this->assertEquals($expected, $ret);
    }

    public function testSetMultiple(): void
    {
        $this->apcu_store->expects($this->atMost(2))
            ->withConsecutive(['key1', 'val1', 300], ['key2', 'val2', 300])
            ->willReturnOnConsecutiveCalls(true, true);

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
        $this->apcu_store->expects($this->atMost(2))
            ->withConsecutive(['key1', 'val1', 499], ['key2', 'val2', 499])
            ->willReturnOnConsecutiveCalls(true, true);

        $ret = $this->pool->setMultiple($iterable, 499);
        $this->assertTrue($ret);
    }

    public function testSetMultipleWithIterableError(): void
    {
        $expected = [
            'key1' => 'val1',
            'key2' => 'val2'
        ];
        $iterable = new ArrayIterator($expected);

        $this->apcu_store->expects($this->atMost(2))
            ->with('key1', 'val1', 499)
            ->willReturn(false);

        $ret = $this->pool->setMultiple($iterable, 499);
        $this->assertFalse($ret);
    }

    public function testDeleteMultiple(): void
    {
        $this->apcu_delete->expects($this->once())
            ->with(['key1', 'key2'])
            ->willReturn([]);

        $ret = $this->pool->deleteMultiple(['key1', 'key2']);
        $this->assertTrue($ret);
    }

    public function testDeleteMultipleWithIterableError(): void
    {
        $iterable = new ArrayIterator(['key1', 'key2']);
        $this->apcu_delete->expects($this->once())->with(['key1', 'key2'])
            ->willReturn(['key']);
        $ret = $this->pool->deleteMultiple($iterable);
        $this->assertFalse($ret);
    }

    public function testHas(): void
    {
        $this->apcu_exists->expects($this->once())
            ->with('key1')
            ->willReturn(true);
        $ret = $this->pool->has('key1');
        $this->assertTrue($ret);
    }

    public function testHasNot1(): void
    {
        $this->apcu_exists->expects($this->once())
            ->with('key1')
            ->willReturn(false);
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
        $this->apcu_fetch->expects($this->once())
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
        $this->apcu_fetch->expects($this->once())
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
        $this->apcu_fetch->expects($this->once())
            ->willReturn(false);
        $ret = $this->pool->getItems(array_keys($expected));
        $this->assertEquals($expected, $ret);
    }

    public function testHasItem(): void
    {
        $this->apcu_exists->expects($this->once())->with('key1')->willReturn('val1');
        $this->assertTrue($this->pool->hasItem('key1'));
    }

    public function testDeleteItem(): void
    {
        $this->apcu_delete->expects($this->once())->with('key1')->willReturn(true);
        $this->assertTrue($this->pool->deleteItem('key1'));
    }

    public function testDeleteItems(): void
    {
        $this->apcu_delete->expects($this->once())->with(['key1', 'key2'])->willReturn([]);
        $ret = $this->pool->deleteItems(['key1', 'key2']);
        $this->assertTrue($ret);
    }

    public function testSave(): void
    {
        $item = new Item('key1', 'val1', 876);
        $this->apcu_store->expects($this->once())->with('key1', 'val1', 876)->willReturn(true);
        $ret = $this->pool->save($item);
        $this->assertTrue($ret);
    }

    public function testSaveDeferred(): void
    {
        $item = new Item('key1', 'val1', 876);
        $this->apcu_store->expects($this->once())->with('key1', 'val1', 876)->willReturn(true);
        $ret = $this->pool->saveDeferred($item);
        $this->assertTrue($ret);
    }

}
