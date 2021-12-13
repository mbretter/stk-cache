<?php

namespace Stk\Cache\Pool;

use DateInterval;
use DateTime;
use Psr\Cache;
use Stk\Cache\Item;
use Stk\Cache\PoolInterface;

class Memory implements PoolInterface
{
    protected array $_cache = [];

    protected array $_config = [];

    protected string $prefix = "";

    public function __construct(array $config)
    {
        $this->init($config);
    }

    public function init(array $config = null): void
    {
        if ($config !== null) {
            $this->_config = $config;
        }
        $this->setPrefix();
    }

    public function setPrefix(string $prefix = ''): void
    {
        if (strlen($prefix)) {
            $this->prefix = sprintf('%s%s_', $this->_config['prefix'], $prefix);
        } else {
            $this->prefix = $this->_config['prefix'];
        }
    }

    protected function buildKey(string $key): string
    {
        return sprintf('%s%s', $this->prefix, $key);
    }

    public function dump(): array
    {
        return $this->_cache;
    }

    public function restore(array $cacheDate): void
    {
        $this->_cache = $cacheDate;
    }

    // the simple interface

    /**
     * {@inheritDoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!isset($this->_cache[$this->buildKey($key)])) {
            return false;
        }

        $val = $this->_cache[$this->buildKey($key)];

        if (time() > $val['expires']) {
            return false;
        }

        return $val['data'];
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = Item::TTL_DEFAULT): bool
    {
        if ($ttl instanceof DateInterval) {
            $ttl = (new DateTime())->add($ttl)->getTimestamp() - (new DateTime())->getTimestamp();
        }

        $this->_cache[$this->buildKey($key)] = ['data' => $value, 'expires' => time() + $ttl];

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        return array_key_exists($this->buildKey($key), $this->_cache);
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): bool
    {
        $this->_cache = [];

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key): bool
    {
        if (!array_key_exists($this->buildKey($key), $this->_cache)) {
            return false;
        }

        unset($this->_cache[$this->buildKey($key)]);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $ret = [];
        foreach ($keys as $k) {
            $ret[$k] = $this->get($k, $default);
        }

        return $ret;
    }

    /**
     * {@inheritDoc}
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = 300): bool
    {
        foreach ($values as $k => $v) {
            $this->set($k, $v, $ttl);
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $k => $v) {
            $this->delete($k);
        }

        return true;
    }

    // psr-cache

    /**
     * {@inheritDoc}
     */
    public function getItem(string $key): Cache\CacheItemInterface
    {
        $item = new Item($key);

        if (!$this->has($key)) {
            return $item;
        }

        return $item->setIsHit(true)->set($this->get($key));
    }

    /**
     * {@inheritDoc}
     */
    public function getItems(array $keys = []): iterable
    {
        $ret = [];
        foreach ($keys as $k) {
            $ret[$k] = $this->getItem($k);
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
        foreach ($keys as $k) {
            $this->delete($k);
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function save(Cache\CacheItemInterface $item): bool
    {
        /** @var Item $item */

        return $this->set($item->getKey(), $item->get(), $item->getTtl());
    }

    /**
     * {@inheritDoc}
     */
    public function saveDeferred(Cache\CacheItemInterface $item): bool
    {
        return $this->save($item);
    }

    /**
     * {@inheritDoc}
     */
    public function commit(): bool
    {
        return true;
    }
}
