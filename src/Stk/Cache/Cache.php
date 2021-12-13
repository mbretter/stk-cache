<?php /** @noinspection PhpDocMissingThrowsInspection */

namespace Stk\Cache;


use DateInterval;
use Psr\Cache\CacheItemInterface;
use Stk\Service\Injectable;

class Cache implements PoolInterface, Injectable
{
    protected PoolInterface $pool;

    /**
     * @param PoolInterface $pool
     */
    public function __construct(PoolInterface $pool)
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
     */
    public function getSet(string $key, callable $callableNotFound, int $ttl = Item::TTL_DEFAULT): mixed
    {
        $item = $this->pool->getItem($key);
        if ($item->isHit()) {
            return $item->get();
        }

        $val = $callableNotFound();
        if ($val !== null) {
            $item = new Item($key, $val, $ttl);

            $this->pool->save($item);
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
    public function setGrouped(string $group, string $key, mixed $val, int $ttl = Item::TTL_DEFAULT): bool
    {
        $expires = time() + $ttl;

        return $this->pool->setMultiple([$group => $expires, $key => ['exp' => $expires, 'val' => $val]], $ttl);
    }

    /**
     * @param string $group
     * @param string $key
     *
     * @return mixed
     */
    public function getGrouped(string $group, string $key): mixed
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
    public function getSetGrouped(
        string $group,
        string $key,
        callable $callableNotFound,
        int $ttl = Item::TTL_DEFAULT
    ): mixed {
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
    // we do not want to use magic methods

    // PSR-16 SimpleCacheInterface

    /**
     * {@inheritDoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->pool->get($key, $default);
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = Item::TTL_DEFAULT): bool
    {
        return $this->pool->set($key, $value, $ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key): bool
    {
        return $this->pool->delete($key);
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): bool
    {
        return $this->pool->clear();
    }

    /**
     * {@inheritDoc}
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        return $this->pool->getMultiple($keys, $default);
    }

    /**
     * {@inheritDoc}
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        return $this->pool->setMultiple($values, $ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteMultiple(iterable $keys): bool
    {
        return $this->pool->deleteMultiple($keys);
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        return $this->pool->has($key);
    }

    // PSR-6 CacheItemPoolInterface

    /**
     * {@inheritDoc}
     */
    public function getItem(string $key): CacheItemInterface
    {
        return $this->pool->getItem($key);
    }

    /**
     * {@inheritDoc}
     */
    public function getItems(array $keys = []): iterable
    {
        return $this->pool->getItems($keys);
    }

    /**
     * {@inheritDoc}
     */
    public function hasItem(string $key): bool
    {
        return $this->pool->hasItem($key);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteItem(string $key): bool
    {
        return $this->pool->deleteItem($key);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteItems(array $keys): bool
    {
        return $this->pool->deleteItems($keys);
    }

    /**
     * {@inheritDoc}
     */
    public function save(CacheItemInterface $item): bool
    {
        return $this->pool->save($item);
    }

    /**
     * {@inheritDoc}
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        return $this->pool->saveDeferred($item);
    }

    /**
     * {@inheritDoc}
     */
    public function commit(): bool
    {
        return $this->pool->commit();
    }
}