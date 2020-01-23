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

    public function testClear()
    {
        $ret = $this->pool->clear();
        $this->assertTrue($ret);
    }

    public function testCommit()
    {
        $ret = $this->pool->commit();
        $this->assertTrue($ret);
    }

    public function testSet()
    {
        $ret = $this->pool->set('key1', 'val1');
        $this->assertTrue($ret);
    }

    public function testGet()
    {
        $ret = $this->pool->get('key1');
        $this->assertFalse($ret);
    }

    public function testDelete()
    {
        $ret = $this->pool->delete('key2');
        $this->assertTrue($ret);
    }

    public function testGetMultiple()
    {
        $vals = $this->pool->getMultiple(['key1', 'key2']);

        $this->assertEquals([], $vals);
    }

    public function testSetMultiple()
    {
        $ret = $this->pool->setMultiple(['key1' => 'val1', 'key2' => 'val2']);
        $this->assertTrue($ret);
    }

    public function testSave()
    {
        $ret = $this->pool->save(new Item('key1'));
        $this->assertTrue($ret);
    }

    public function testSaveDeferred()
    {
        $ret = $this->pool->saveDeferred(new Item('key1'));
        $this->assertTrue($ret);
    }

    public function testGetItem()
    {
        $ret = $this->pool->getItem('key1');
        $this->assertInstanceOf(Item::class, $ret);
        $this->assertFalse($ret->isHit());
    }

    public function testGetItems()
    {
        $ret = $this->pool->getItems(['key1', 'key2']);
        $this->assertEquals([], $ret);
    }

    public function testHasItem()
    {
        $ret = $this->pool->hasItem('key1');
        $this->assertFalse($ret);
    }

    public function testDeleteItem()
    {
        $ret = $this->pool->deleteItem('key1');
        $this->assertTrue($ret);
    }

    public function testDeleteItems()
    {
        $ret = $this->pool->deleteItems(['key1', 'key2']);
        $this->assertTrue($ret);
    }

    public function testDeleteMultiple()
    {
        $ret = $this->pool->deleteMultiple(['key1', 'key2']);
        $this->assertTrue($ret);
    }
}
