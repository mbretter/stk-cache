<?php

namespace StkTest\Cache;

require_once __DIR__ . '/stubs.php';

use PHPUnit\Framework\TestCase;
use Stk\Cache\Local;

class LocalTest extends TestCase
{
    /** @var Local */
    protected $cache;

    public function setUp()
    {
        $this->cache = new Local([
            'prefix' => "xx_",
        ]);
    }

    public function testConstructor()
    {
        $cache = new Local([
            'prefix' => "xx_",
        ]);
        $cache->store('key1', 'val1');
        $this->assertEquals([
            'xx_key1' => [
                'data'    => 'val1',
                'expires' => 400,
            ],
        ], $cache->dump());
    }

    public function testStore()
    {
        $ret = $this->cache->store('key1', 'val1');
        $this->assertTrue($ret);
        $this->assertEquals([
            'xx_key1' => [
                'data'    => 'val1',
                'expires' => 400,
            ],
        ], $this->cache->dump());
    }

    public function testAdd()
    {
        $ret = $this->cache->add('key1', 'val1');
        $this->assertTrue($ret);
        $this->assertEquals([
            'xx_key1' => [
                'data'    => 'val1',
                'expires' => 400,
            ],
        ], $this->cache->dump());

        $ret = $this->cache->add('key1', 'val1');
        $this->assertFalse($ret);
    }

    public function testGet()
    {
        $this->cache->restore([
            'xx_key1' => [
                'data'    => 'val1',
                'expires' => 400,
            ]
        ]);

        $ret = $this->cache->get('key1');
        $this->assertEquals('val1', $ret);
    }

    public function testGetNonExistend()
    {
        $ret = $this->cache->get('key1');
        $this->assertFalse($ret);
    }

    public function testGetExpired()
    {
        $this->cache->restore([
            'xx_key1' => [
                'data'    => 'val1',
                'expires' => 10,
            ]
        ]);

        $ret = $this->cache->get('key1');
        $this->assertFalse($ret);
    }

    public function testDelete()
    {
        $this->cache->restore([
            'xx_key1' => [
                'data'    => 'val1',
                'expires' => 400,
            ]
        ]);

        $ret = $this->cache->delete('key1');
        $this->assertTrue($ret);
        $this->assertEquals([], $this->cache->dump());

        $ret = $this->cache->delete('key2');
        $this->assertFalse($ret);
    }

    public function testSetPrefix()
    {
        $this->cache->setPrefix('yy');

        $ret = $this->cache->store('key1', 'val1');
        $this->assertTrue($ret);
        $this->assertEquals([
            'xx_yy_key1' => [
                'data'    => 'val1',
                'expires' => 400,
            ],
        ], $this->cache->dump());
    }

    public function testStoreGroup()
    {
        $ret = $this->cache->storeGroup('grp1', 'key1', 'val1');
        $this->assertTrue($ret);
        $this->assertEquals([
            'xx_key1' => [
                'data'    => [1000, 'val1'],
                'expires' => 400,
            ],
            'xx_grp1' => [
                'data'    => 1000,
                'expires' => 400,
            ],
        ], $this->cache->dump());
    }

    public function testGetGroup()
    {
        $this->cache->restore([
            'xx_key1' => [
                'data'    => [1000, 'val1'],
                'expires' => 400,
            ],
            'xx_grp1' => [
                'data'    => 1000,
                'expires' => 400,
            ],
        ]);

        $ret = $this->cache->getGroup('grp1', 'key1');

        $this->assertEquals('val1', $ret);
    }

    public function testGetGroupNoGroup()
    {
        $this->cache->restore([
            'xx_key1' => [
                'data'    => [1000, 'val1'],
                'expires' => 400,
            ]
        ]);

        $ret = $this->cache->getGroup('grp1', 'key1');

        $this->assertFalse($ret);
    }

    public function testGetGroupNoKey()
    {
        $this->cache->restore([
            'xx_grp1' => [
                'data'    => 1000,
                'expires' => 400,
            ],
        ]);

        $ret = $this->cache->getGroup('grp1', 'key1');

        $this->assertFalse($ret);
    }

    public function testGetGroupNonArrayKeyVal()
    {
        $this->cache->restore([
            'xx_key1' => [
                'data'    => 'val1',
                'expires' => 400,
            ],
            'xx_grp1' => [
                'data'    => 1000,
                'expires' => 400,
            ],
        ]);

        $ret = $this->cache->getGroup('grp1', 'key1');

        $this->assertFalse($ret);
    }

    public function testGetGroupInvalidRef()
    {
        $this->cache->restore([
            'xx_key1' => [
                'data'    => [99, 'val1'],
                'expires' => 400,
            ],
            'xx_grp1' => [
                'data'    => 1000,
                'expires' => 400,
            ],
        ]);

        $ret = $this->cache->getGroup('grp1', 'key1');

        $this->assertFalse($ret);
    }

    public function testDeleteGetGroup()
    {
        $this->cache->restore([
            'xx_key1' => [
                'data'    => [1000, 'val1'],
                'expires' => 400,
            ],
            'xx_grp1' => [
                'data'    => 1000,
                'expires' => 400,
            ],
        ]);

        $ret = $this->cache->delete('grp1');
        $this->assertTrue($ret);

        $ret = $this->cache->getGroup('grp1', 'key1');
        $this->assertFalse($ret);
    }

    public function testAddToGroup()
    {
        $this->cache->restore([
            'xx_key1' => [
                'data'    => [1000, 'val1'],
                'expires' => 400,
            ],
            'xx_grp1' => [
                'data'    => 1000,
                'expires' => 400,
            ],
        ]);

        $ret = $this->cache->storeGroup('grp1', 'key2', 'val2');
        $this->assertTrue($ret);

        $ret = $this->cache->getGroup('grp1', 'key1');
        $this->assertEquals('val1', $ret);
        $ret = $this->cache->getGroup('grp1', 'key2');
        $this->assertEquals('val2', $ret);

        $this->assertEquals([
            'xx_key1' => [
                'data'    => [1000, 'val1'],
                'expires' => 400,
            ],
            'xx_grp1' => [
                'data'    => 1000,
                'expires' => 400,
            ],
            'xx_key2' => [
                'data'    => [1000, 'val2'],
                'expires' => 400,
            ],
        ], $this->cache->dump());
    }

    public function testGetGroupElse()
    {
        $this->cache->restore([
            'xx_key1' => [
                'data'    => [1000, 'val1'],
                'expires' => 400,
            ],
            'xx_grp1' => [
                'data'    => 1000,
                'expires' => 400,
            ],
        ]);

        $ret = $this->cache->getGroupElse('grp1', 'key1', function () {
            return 'val2';
        });

        $this->assertEquals('val1', $ret);
    }

    public function testGetGroupElseNotFound()
    {
        $ret = $this->cache->getGroupElse('grp1', 'key1', function () {
            return 'val1';
        });

        $this->assertEquals('val1', $ret);
        $this->assertEquals([
            'xx_key1' => [
                'data'    => [1000, 'val1'],
                'expires' => 400,
            ],
            'xx_grp1' => [
                'data'    => 1000,
                'expires' => 400,
            ],
        ], $this->cache->dump());
    }

    public function testGetElse()
    {
        $this->cache->restore([
            'xx_key1' => [
                'data'    => 'val1',
                'expires' => 400,
            ]
        ]);

        $ret = $this->cache->getElse('key1', function () {
            return 'val2';
        });

        $this->assertEquals('val1', $ret);
    }

    public function testGetElseNotFound()
    {
        $ret = $this->cache->getElse('key1', function () {
            return 'val2';
        });

        $this->assertEquals('val2', $ret);

        $this->assertEquals([
            'xx_key1' => [
                'data'    => 'val2',
                'expires' => 400,
            ],
        ], $this->cache->dump());
    }

    public function testGetMulti()
    {
        $this->cache->restore([
            'xx_key1' => [
                'data'    => 'val1',
                'expires' => 400,
            ],
            'xx_key2' => [
                'data'    => 'val2',
                'expires' => 400,
            ]
        ]);

        $vals = $this->cache->getMulti(['key1', 'key2']);

        $this->assertEquals(['key1' => 'val1', 'key2' => 'val2'], $vals);
    }

    public function testSetMulti()
    {
        $this->cache->setMulti(['key1' => 'val1', 'key2' => 'val2']);

        $this->assertEquals([
            'xx_key1' => [
                'data'    => 'val1',
                'expires' => 400,
            ],
            'xx_key2' => [
                'data'    => 'val2',
                'expires' => 400,
            ]
        ], $this->cache->dump());
    }
}
