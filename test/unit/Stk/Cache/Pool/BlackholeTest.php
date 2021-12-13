<?php

namespace StkTest\Cache\Pool;

require_once __DIR__ . '/../stubs.php';

use PHPUnit\Framework\TestCase;
use Stk\Cache\Item;
use Stk\Cache\Pool\Blackhole;

class BlackholeTest extends TestCase
{
    /** @var Blackhole */
    protected $pool;

    public function setUp(): void
    {
        $this->pool = new Blackhole();
    }

    public function testClear(): void
    {
        $ret = $this->pool->clear();
        $this->assertTrue($ret);
    }

    public function testCommit(): void
    {
        $ret = $this->pool->commit();
        $this->assertTrue($ret);
    }

    public function testSet(): void
    {
        $ret = $this->pool->set('key1', 'val1');
        $this->assertTrue($ret);
    }

    public function testGet(): void
    {
        $ret = $this->pool->get('key1');
        $this->assertFalse($ret);
    }

    public function testDelete(): void
    {
        $ret = $this->pool->delete('key2');
        $this->assertTrue($ret);
    }

    public function testGetMultiple(): void
    {
        $vals = $this->pool->getMultiple(['key1', 'key2']);

        $this->assertEquals([], $vals);
    }

    public function testSetMultiple(): void
    {
        $ret = $this->pool->setMultiple(['key1' => 'val1', 'key2' => 'val2']);
        $this->assertTrue($ret);
    }

    public function testSave(): void
    {
        $ret = $this->pool->save(new Item('key1'));
        $this->assertTrue($ret);
    }

    public function testSaveDeferred(): void
    {
        $ret = $this->pool->saveDeferred(new Item('key1'));
        $this->assertTrue($ret);
    }

    public function testGetItem(): void
    {
        $ret = $this->pool->getItem('key1');
        $this->assertInstanceOf(Item::class, $ret);
        $this->assertFalse($ret->isHit());
    }

    public function testGetItems(): void
    {
        $expected = [
            'key1' => new Item('key1'),
            'key2' => new Item('key2')
        ];

        $ret = $this->pool->getItems(['key1', 'key2']);
        $this->assertEquals($expected, $ret);
    }

    public function testHasItem(): void
    {
        $ret = $this->pool->hasItem('key1');
        $this->assertFalse($ret);
    }

    public function testDeleteItem(): void
    {
        $ret = $this->pool->deleteItem('key1');
        $this->assertTrue($ret);
    }

    public function testDeleteItems(): void
    {
        $ret = $this->pool->deleteItems(['key1', 'key2']);
        $this->assertTrue($ret);
    }

    public function testDeleteMultiple(): void
    {
        $ret = $this->pool->deleteMultiple(['key1', 'key2']);
        $this->assertTrue($ret);
    }
}
