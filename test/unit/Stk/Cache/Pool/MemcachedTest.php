<?php

namespace StkTest\Cache\Pool;

require_once __DIR__ . '/../stubs.php';

use Memcached;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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

    public function testGet()
    {
        $this->memcached->expects($this->once())
            ->method('get')
            ->with('key1')
            ->willReturn('val1');
        $ret = $this->pool->get('key1');
        $this->assertEquals('val1', $ret);
    }

    public function testSet()
    {
        $this->memcached->expects($this->once())
            ->method('set')
            ->with('key1', 'val1', 300)
            ->willReturn(true);
        $ret = $this->pool->set('key1', 'val1');
        $this->assertTrue($ret);
    }

    public function testDelete()
    {
        $this->memcached->expects($this->once())
            ->method('delete')
            ->with('key1')
            ->willReturn(true);
        $ret = $this->pool->delete('key1');
        $this->assertTrue($ret);
    }

    public function testGetMultiple()
    {
        $this->memcached->expects($this->once())
            ->method('getMulti')
            ->with(['key1', 'key2'])
            ->willReturn(true);
        $ret = $this->pool->getMultiple(['key1', 'key2']);
        $this->assertEquals(true, $ret);
    }

    public function testSetMultiple()
    {
        $this->memcached->expects($this->once())
            ->method('setMulti')
            ->with(['key1' => 'val1', 'key2' => 'val2'], 300)
            ->willReturn(true);

        $ret = $this->pool->setMultiple(['key1' => 'val1', 'key2' => 'val2']);
        $this->assertTrue($ret);
    }
}
