<?php

namespace StkTest\Cache;

require_once __DIR__ . '/stubs.php';

use Exception;
use Memcached;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Stk\Cache\Memcache;

class MemcacheTest extends TestCase
{
    /** @var Memcache */
    protected $cache;

    /** @var MockObject|Memcached */
    protected $memcachedMock;

    public function setUp()
    {
        $this->cache = new Memcache([
            'prefix'  => "xx_",
            'servers' => ["192.168.33.22:11211:50", "192.168.33.23:11212:20"]
        ]);

        $this->memcachedMock = $this->createMock(Memcached::class);

        $this->cache->setCache($this->memcachedMock);
    }

    public function testInit()
    {
        $this->memcachedMock->expects($this->once())
            ->method('addServers')
            ->with([
                ["192.168.33.22", "11211", "50"],
                ["192.168.33.23", "11212", "20"]
            ])
            ->willReturn(true);

        $this->memcachedMock->expects($this->once())
            ->method('setOption')
            ->with(Memcached::OPT_PREFIX_KEY, 'xx_');

        $this->cache->init();
    }

    public function testInitWithConfig()
    {
        $this->memcachedMock->expects($this->once())
            ->method('addServers')
            ->with([
                ["127.0.0.1", "11211", "50"],
                ["127.0.0.2", "11211", "50"]
            ])
            ->willReturn(true);

        $this->memcachedMock->expects($this->once())
            ->method('setOption')
            ->with(Memcached::OPT_PREFIX_KEY, 'yy_');

        $this->cache->init([
            'prefix'  => "yy_",
            'servers' => ["127.0.0.1:11211", "127.0.0.2"]
        ]);

    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessageRegExp /^Memcached: unable to set server/
     */
    public function testInitWithError()
    {
        $this->memcachedMock->method('addServers')->willReturn(false);

        $this->cache->init();
    }

    public function testGetCache()
    {
        $cache = new Memcache([
            'prefix'  => "xx_",
            'servers' => ["192.168.33.22:11211:50", "192.168.33.23:11212:20"]
        ]);

        $this->assertInstanceOf(Memcached::class, $cache->getCache());
    }

    public function testGet()
    {
        $this->memcachedMock->expects($this->once())
            ->method('get')
            ->with('key1')
            ->willReturn('val1');
        $ret = $this->cache->get('key1');
        $this->assertEquals('val1', $ret);
    }

    public function testGetElse()
    {
        $this->memcachedMock->expects($this->once())
            ->method('get')
            ->with('key1')
            ->willReturn('val1');
        $ret = $this->cache->getElse('key1', function () {
            return 'newval';
        });
        $this->assertEquals('val1', $ret);
    }

    public function testGetElseNotFound()
    {
        $this->memcachedMock->expects($this->once())
            ->method('get')
            ->willReturn(false);
        $ret = $this->cache->getElse('key1', function () {
            return 'newval';
        });
        $this->assertEquals('newval', $ret);
    }

    public function testAdd()
    {
        $this->memcachedMock->expects($this->once())
            ->method('add')
            ->with('key1', 'val1', 300)
            ->willReturn(true);
        $ret = $this->cache->add('key1', 'val1');
        $this->assertTrue($ret);
    }

    public function testStore()
    {
        $this->memcachedMock->expects($this->once())
            ->method('set')
            ->with('key1', 'val1', 300)
            ->willReturn(true);
        $ret = $this->cache->store('key1', 'val1');
        $this->assertTrue($ret);
    }

    public function testStoreGroup()
    {
        $this->memcachedMock->expects($this->once())
            ->method('setMulti')
            ->with(['grp1' => 1000, 'key1' => [1000, 'val1']], 300)
            ->willReturn(true);
        $ret = $this->cache->storeGroup('grp1', 'key1', 'val1');
        $this->assertTrue($ret);
    }

    public function testStoreGroupWithRef()
    {
        $this->memcachedMock->expects($this->once())
            ->method('setMulti')
            ->with(['grp1' => 'someref', 'key1' => ['someref', 'val1']], 300)
            ->willReturn(true);
        $ret = $this->cache->storeGroup('grp1', 'key1', 'val1', 300, 'someref');
        $this->assertTrue($ret);
    }

    public function testGetGroup()
    {
        $this->memcachedMock->expects($this->once())
            ->method('getMulti')
            ->with(['grp1', 'key1'])
            ->willReturn([
                'grp1' => 'ref1',
                'key1' => ['ref1', 'val1']
            ]);

        $ret = $this->cache->getGroup('grp1', 'key1');
        $this->assertEquals('val1', $ret);
    }

    public function testGetGroupKeyNotFound()
    {
        $this->memcachedMock->expects($this->once())
            ->method('getMulti')
            ->with(['grp1', 'key1'])
            ->willReturn([
                'grp1' => 'ref1',
            ]);

        $ret = $this->cache->getGroup('grp1', 'key1');
        $this->assertFalse($ret);
    }

    public function testGetGroupGrpNotFound()
    {
        $this->memcachedMock->expects($this->once())
            ->method('getMulti')
            ->with(['grp1', 'key1'])
            ->willReturn([
                'key1' => ['ref1', 'val1']
            ]);

        $ret = $this->cache->getGroup('grp1', 'key1');
        $this->assertFalse($ret);
    }

    public function testGetGroupKeyvalInvalid1()
    {
        $this->memcachedMock->expects($this->once())
            ->method('getMulti')
            ->with(['grp1', 'key1'])
            ->willReturn([
                'grp1' => 'ref1',
                'key1' => 'val1'
            ]);

        $ret = $this->cache->getGroup('grp1', 'key1');
        $this->assertFalse($ret);
    }

    public function testGetGroupKeyvalInvalid2()
    {
        $this->memcachedMock->expects($this->once())
            ->method('getMulti')
            ->with(['grp1', 'key1'])
            ->willReturn([
                'grp1' => 'ref1',
                'key1' => ['val1']
            ]);

        $ret = $this->cache->getGroup('grp1', 'key1');
        $this->assertFalse($ret);
    }

    public function testGetGroupRefEqual()
    {
        $this->memcachedMock->expects($this->once())
            ->method('getMulti')
            ->with(['grp1', 'key1'])
            ->willReturn([
                'grp1' => 'ref1',
                'key1' => ['ref2', 'val1']
            ]);

        $ret = $this->cache->getGroup('grp1', 'key1');
        $this->assertFalse($ret);
    }

    public function testGetGroupElse()
    {
        $this->memcachedMock->expects($this->once())
            ->method('getMulti')
            ->with(['grp1', 'key1'])
            ->willReturn([
                'grp1' => 'ref1',
                'key1' => ['ref1', 'val1']
            ]);

        $ret = $this->cache->getGroupElse('grp1', 'key1', function () {
            return 'newval';
        });
        $this->assertEquals('val1', $ret);
    }

    public function testGetGroupElseNotFound()
    {
        $this->memcachedMock->expects($this->once())
            ->method('getMulti')
            ->with(['grp1', 'key1'])
            ->willReturn(false);

        $this->memcachedMock->expects($this->once())
            ->method('setMulti')
            ->willReturn(true);

        $ret = $this->cache->getGroupElse('grp1', 'key1', function () {
            return 'newval';
        });
        $this->assertEquals('newval', $ret);
    }

    public function testDelete()
    {
        $this->memcachedMock->expects($this->once())
            ->method('delete')
            ->with('key1')
            ->willReturn(true);
        $ret = $this->cache->delete('key1');
        $this->assertTrue($ret);
    }

    public function testGetMulti()
    {
        $this->memcachedMock->expects($this->once())
            ->method('getMulti')
            ->with(['key1', 'key2'])
            ->willReturn(true);
        $ret = $this->cache->getMulti(['key1', 'key2']);
        $this->assertEquals(true, $ret);
    }

    public function testSetMulti()
    {
        $this->memcachedMock->expects($this->once())
            ->method('setMulti')
            ->with(['key1' => 'val1', 'key2' => 'val2'], 300)
            ->willReturn(true);

        $ret = $this->cache->setMulti(['key1' => 'val1', 'key2' => 'val2']);
        $this->assertTrue($ret);
    }

    public function testSetPrefix()
    {
        $this->memcachedMock->expects($this->once())
            ->method('setOption')
            ->with(Memcached::OPT_PREFIX_KEY, 'xx_extra_');

        $this->cache->setPrefix('extra');
    }
}


