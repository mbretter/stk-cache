<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace Stk\Cache\Pool;

use DateInterval;
use DateTime;
use Memcached as MemcachedExt;
use Psr\Cache;
use Stk\Cache\Item;
use Stk\Cache\PoolInterface;

class Memcached implements PoolInterface
{
    protected MemcachedExt $_cache;

    public function __construct(MemcachedExt $memcached)
    {
        $this->_cache = $memcached;
    }

    // PSR16 simple cache

    /**
     * {@inheritDoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $val = $this->_cache->get($key);
        if ($this->_cache->getResultCode() === MemcachedExt::RES_NOTFOUND) {
            $val = $default;
        }

        if ($val === false && $this->_cache->getResultCode() !== MemcachedExt::RES_SUCCESS) {
            $val = $default;
        }

        return $val;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = 300): bool
    {
        if ($ttl instanceof DateInterval) {
            $ttl = (new DateTime())->add($ttl)->getTimestamp() - (new DateTime())->getTimestamp();
        }

        return $this->_cache->set($key, $value, (int) $ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key): bool
    {
        return $this->_cache->delete($key);
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): bool
    {
        return $this->_cache->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $aKeys = [...$keys];

        $ret = $this->_cache->getMulti($aKeys);
        if ($ret === false) {
            $ret = [];
        }
        foreach ($aKeys as $k) {
            if (!array_key_exists($k, $ret)) {
                $ret[$k] = $default;
            }
        }

        return $ret;
    }

    /**
     * {@inheritDoc}
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = 300): bool
    {
        if ($ttl instanceof DateInterval) {
            $ttl = (new DateTime())->add($ttl)->getTimestamp() - (new DateTime())->getTimestamp();
        }

        return $this->_cache->setMulti([...$values], (int) $ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteMultiple(iterable $keys): bool
    {
        $aKeys = [...$keys];

        $keysRemoved = $this->_cache->deleteMulti($aKeys);
        foreach ($keysRemoved as $key => $res) {
            if ($res === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        $v = $this->_cache->get($key);

        return $v !== false && $this->_cache->getResultCode() !== MemcachedExt::RES_NOTFOUND;
    }

    // PSR6 cache

    /**
     * {@inheritDoc}
     */
    public function getItem(string $key): Cache\CacheItemInterface
    {
        $val  = $this->get($key);
        $item = new Item($key, $val);

        return $item->setIsHit($val !== null)->set($val);
    }

    /**
     * {@inheritDoc}
     */
    public function getItems(array $keys = []): iterable
    {
        // map iterable to assoc array
        $keysValues = [];
        foreach ($this->getMultiple($keys) as $k => $v) {
            $keysValues[$k] = $v;
        }

        $ret = [];
        foreach ($keys as $k) {
            $item = new Item($k);
            if (!array_key_exists($k, $keysValues)) {
                $ret[$k] = $item;
            } else {
                $ret[$k] = $item->setIsHit($keysValues[$k] !== null)->set($keysValues[$k]);
            }
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
        return $this->deleteMultiple($keys);
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
