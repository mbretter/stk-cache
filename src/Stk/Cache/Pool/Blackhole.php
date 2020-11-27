<?php

namespace Stk\Cache\Pool;

use Psr\Cache;
use Psr\SimpleCache;
use Stk\Cache\Item;

class Blackhole implements SimpleCache\CacheInterface, Cache\CacheItemPoolInterface
{
    // the simple interface

    /**
     * {@inheritDoc}
     */
    public function get($key, $default = null)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function set($key, $value, $ttl = Item::TTL_DEFAULT)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function has($key)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function clear()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getMultiple($keys, $default = null)
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function setMultiple($values, $ttl = 300)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteMultiple($values)
    {
        return true;
    }

    // psr-cache

    /**
     * {@inheritDoc}
     */
    public function getItem($key)
    {
        return new Item($key);
    }

    /**
     * {@inheritDoc}
     */
    public function getItems(array $keys = [])
    {
        $ret = [];
        foreach ($keys as $k) {
            $ret[$k] = new Item($k);
        }

        return $ret;
    }

    /**
     * {@inheritDoc}
     */
    public function hasItem($key)
    {
        return $this->has($key);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteItem($key)
    {
        return $this->delete($key);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteItems(array $keys)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function save(Cache\CacheItemInterface $item)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function saveDeferred(Cache\CacheItemInterface $item)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function commit()
    {
        return true;
    }

}

