<?php

namespace Stk\Cache\Pool;

use DateInterval;
use Psr\Cache;
use Psr\Cache\CacheItemInterface;
use Stk\Cache\Item;
use Stk\Cache\PoolInterface;

class Blackhole implements PoolInterface
{
    // the simple interface

    /**
     * {@inheritDoc}
     */
    public function get(string $key, mixed $default = null): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = Item::TTL_DEFAULT): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = 300): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteMultiple(iterable $keys): bool
    {
        return true;
    }

    // psr-cache

    /**
     * {@inheritDoc}
     */
    public function getItem(string $key): CacheItemInterface
    {
        return new Item($key);
    }

    /**
     * {@inheritDoc}
     */
    public function getItems(array $keys = []): iterable
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
    public function hasItem(string $key): bool
    {
        return $this->has($key);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteItem(string $key): bool
    {
        return $this->delete($key);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteItems(array $keys): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function save(Cache\CacheItemInterface $item): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function saveDeferred(Cache\CacheItemInterface $item): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function commit(): bool
    {
        return true;
    }
}
