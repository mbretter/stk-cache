<?php

namespace StkTest\Cache\Pool;

require_once __DIR__ . '/../stubs.php';

use PHPUnit\Framework\TestCase;
use Stk\Cache\Pool\Memory;

class MemoryTest extends TestCase
{
    /** @var Memory */
    protected $pool;

    public function setUp(): void
    {
        $this->pool = new Memory([
            'prefix' => "xx_",
        ]);
    }

    public function testConstructor()
    {
        $cache = new Memory([
            'prefix' => "xx_",
        ]);
        $cache->set('key1', 'val1');
        $this->assertEquals([
            'xx_key1' => [
                'data'    => 'val1',
                'expires' => 400,
            ],
        ], $cache->dump());
    }


    public function testSet()
    {
        $ret = $this->pool->set('key1', 'val1');
        $this->assertTrue($ret);
        $this->assertEquals([
            'xx_key1' => [
                'data'    => 'val1',
                'expires' => 400,
            ],
        ], $this->pool->dump());
    }


    public function testGet()
    {
        $this->pool->restore([
            'xx_key1' => [
                'data'    => 'val1',
                'expires' => 400,
            ]
        ]);

        $ret = $this->pool->get('key1');
        $this->assertEquals('val1', $ret);
    }

    public function testGetNonExistend()
    {
        $ret = $this->pool->get('key1');
        $this->assertFalse($ret);
    }

    public function testGetExpired()
    {
        $this->pool->restore([
            'xx_key1' => [
                'data'    => 'val1',
                'expires' => 10,
            ]
        ]);

        $ret = $this->pool->get('key1');
        $this->assertFalse($ret);
    }

    public function testDelete()
    {
        $this->pool->restore([
            'xx_key1' => [
                'data'    => 'val1',
                'expires' => 400,
            ]
        ]);

        $ret = $this->pool->delete('key1');
        $this->assertTrue($ret);
        $this->assertEquals([], $this->pool->dump());

        $ret = $this->pool->delete('key2');
        $this->assertFalse($ret);
    }

    public function testSetPrefix()
    {
        $this->pool->setPrefix('yy');

        $ret = $this->pool->set('key1', 'val1');
        $this->assertTrue($ret);
        $this->assertEquals([
            'xx_yy_key1' => [
                'data'    => 'val1',
                'expires' => 400,
            ],
        ], $this->pool->dump());
    }

    public function testGetMultiple()
    {
        $this->pool->restore([
            'xx_key1' => [
                'data'    => 'val1',
                'expires' => 400,
            ],
            'xx_key2' => [
                'data'    => 'val2',
                'expires' => 400,
            ]
        ]);

        $vals = $this->pool->getMultiple(['key1', 'key2']);

        $this->assertEquals(['key1' => 'val1', 'key2' => 'val2'], $vals);
    }

    public function testSetMultiple()
    {
        $this->pool->setMultiple(['key1' => 'val1', 'key2' => 'val2']);

        $this->assertEquals([
            'xx_key1' => [
                'data'    => 'val1',
                'expires' => 400,
            ],
            'xx_key2' => [
                'data'    => 'val2',
                'expires' => 400,
            ]
        ], $this->pool->dump());
    }
}
