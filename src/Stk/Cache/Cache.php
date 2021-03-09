<?php /** @noinspection PhpDocMissingThrowsInspection */

namespace Stk\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface as SimpleCacheInterface;
use Psr\Cache\CacheItemInterface;
use Stk\Service\Injectable;

class Cache implements SimpleCacheInterface, CacheItemPoolInterface, Injectable
{
    /** @var SimpleCacheInterface|CacheItemPoolInterface */
    protected $pool;

    /**
     * @param SimpleCacheInterface|CacheItemPoolInterface $pool
     */
    public function __construct($pool)
    {
        $this->pool = $pool;
    }

    // extensions


    /**
     * get a cache value and invoke callable, if key was not found.
     * The callable must return the cache value.
     *
     * ```php
     * $val = $cache->getSet('mykey', function() {
     *     ....
     *     return $myNewValue
     * });
     *
     * // do whatever with $val
     * print $val;
     * ```
     *
     * @param string $key
     * @param callable $callableNotFound
     * @param int $ttl
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function getSet(string $key, callable $callableNotFound, int $ttl = Item::TTL_DEFAULT)
    {
        $item = $this->pool->getItem($key);
        if ($item->isHit()) {
            return $item->get();
        }

        $val = $callableNotFound();
        if ($val !== null) {
            $this->pool->set($key, $val, $ttl);
        }

        return $val;
    }

    /**
     * @param string $group
     * @param string $key
     * @param mixed $val
     * @param int $ttl
     * @return bool
     */
    public function setGrouped(string $group, string $key, $val, int $ttl = Item::TTL_DEFAULT): bool
    {
        $expires = time() + $ttl;

        return $this->pool->setMultiple([$group => $expires, $key => ['exp' => $expires, 'val' => $val]], $ttl);
    }

    /**
     * @param string $group
     * @param string $key
     *
     * @return null|mixed
     */
    public function getGrouped(string $group, string $key)
    {
        $items = $this->pool->getItems([$group, $key]);

        foreach ($items as $i) {
            /** @var CacheItemInterface $i */
            if (!$i->isHit()) {
                return null;
            }
        }

        // we do not support Traversables
        if (!is_array($items)) {
            return null;
        }

        if (!isset($items[$key])) {
            return null;
        }

        /** @var CacheItemInterface $item */
        $item = $items[$key];
        $data = $item->get();
        if (!is_array($data) || count($data) < 2) {
            return null;
        }

        if ($data['exp'] < time()) {
            return null;
        }

        return $data['val'];
    }

    /**
     * @param string $group
     * @param string $key
     * @param callable $callableNotFound
     * @param int $ttl
     * @return mixed
     */
    public function getSetGrouped(string $group, string $key, callable $callableNotFound, int $ttl = Item::TTL_DEFAULT)
    {
        $val = $this->getGrouped($group, $key);
        if ($val !== null) {
            return $val;
        }

        $val = $callableNotFound();
        if ($val !== null) {
            $this->setGrouped($group, $key, $val, $ttl);
        }

        return $val;
    }


    // pass through methods to fullfill interface specs
    // we don not want to use magic methods

    // PSR-16 SimpleCacheInterface

    /**
     * {@inheritDoc}
     */
    public function get($key, $default = null)
    {
        return $this->pool->get($key, $default);
    }

    /**
     * {@inheritDoc}
     */
    public function set($key, $value, $ttl = Item::TTL_DEFAULT)
    {
        return $this->pool->set($key, $value, $ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
        return $this->pool->delete($key);
    }

    /**
     * {@inheritDoc}
     */
    public function clear()
    {
        return $this->pool->clear();
    }

    /**
     * {@inheritDoc}
     */
    public function getMultiple($keys, $default = null)
    {
        return $this->pool->getMultiple($keys, $default);
    }

    /**
     * {@inheritDoc}
     */
    public function setMultiple($keyValues, $ttl = null)
    {
        return $this->pool->setMultiple($keyValues, $ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteMultiple($keys)
    {
        return $this->pool->deleteMultiple($keys);
    }

    /**
     * {@inheritDoc}
     */
    public function has($key)
    {
        return $this->pool->has($key);
    }

    // PSR-6 CacheItemPoolInterface

    /**
     * {@inheritDoc}
     */
    public function getItem($key)
    {
        return $this->pool->getItem($key);
    }

    /**
     * {@inheritDoc}
     */
    public function getItems(array $keys = [])
    {
        return $this->pool->getItems($keys);
    }

    /**
     * {@inheritDoc}
     */
    public function hasItem($key)
    {
        return $this->pool->hasItem($key);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteItem($key)
    {
        return $this->pool->deleteItem($key);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteItems(array $keys)
    {
        return $this->pool->deleteItems($keys);
    }

    /**
     * {@inheritDoc}
     */
    public function save(CacheItemInterface $item)
    {
        return $this->pool->save($item);
    }

    /**
     * {@inheritDoc}
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        return $this->pool->saveDeferred($item);
    }

    /**
     * {@inheritDoc}
     */
    public function commit()
    {
        return $this->pool->commit();
    }
}