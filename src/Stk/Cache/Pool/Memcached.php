<?php

namespace Stk\Cache\Pool;

use Memcached as MemcachedExt;
use Psr\Cache;
use Psr\SimpleCache;
use Stk\Cache\Item;
use Stk\Cache\InvalidArgumentException;

class Memcached implements SimpleCache\CacheInterface, Cache\CacheItemPoolInterface
{
    /** @var MemcachedExt */
    protected $_cache;

    public function __construct(MemcachedExt $memcached)
    {
        $this->_cache = $memcached;
    }

    // PSR16 simple cache

    /**
     * {@inheritDoc}
     */
    public function get($key, $default = null)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException();
        }
        $val = $this->_cache->get($key);
        if ($this->_cache->getResultCode() === MemcachedExt::RES_NOTFOUND) {
            $val = $default;
        }

        return $val;
    }

    /**
     * {@inheritDoc}
     */
    public function set($key, $var, $ttl = 300): bool
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException();
        }

        return $this->_cache->set($key, $var, $ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key): bool
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException();
        }

        return $this->_cache->delete($key);
    }

    /**
     * {@inheritDoc}
     */
    public function clear()
    {
        return $this->_cache->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getMultiple($keys, $default = null)
    {
        if (!is_iterable($keys)) {
            throw new InvalidArgumentException();
        }

        if (is_array($keys)) {
            return $this->_cache->getMulti($keys);
        } else {
            $ret = [];
            foreach ($keys as $k) {
                $ret[$k] = $this->get($k, $default);
            }

            return $ret;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function setMultiple($values, $ttl = 300): bool
    {
        if (!is_iterable($values)) {
            throw new InvalidArgumentException();
        }

        if (is_array($values)) {
            return $this->_cache->setMulti($values, $ttl);
        } else {
            foreach ($values as $k => $v) {
                if ($this->set($k, $v, $ttl) === false) {
                    return false;
                }
            }

            return true;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function deleteMultiple($keys)
    {
        if (!is_iterable($keys)) {
            throw new InvalidArgumentException();
        }

        if (is_array($keys)) {
            return $this->_cache->deleteMulti($keys);
        } else {
            foreach ($keys as $k) {
                if ($this->delete($k) === false) {
                    return false;
                }
            }

            return true;
        }

    }

    /**
     * {@inheritDoc}
     */
    public function has($key)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException();
        }

        $v = $this->_cache->get($key);

        return $v !== false && $this->_cache->getResultCode() !== MemcachedExt::RES_NOTFOUND;
    }

    // PSR6 cache

    /**
     * {@inheritDoc}
     */
    public function getItem($key)
    {
        $val  = $this->get($key);
        $item = new Item($key, $val);

        return $item->setIsHit($this->_cache->getResultCode() !== MemcachedExt::RES_NOTFOUND)->set($val);
    }

    /**
     * {@inheritDoc}
     */
    public function getItems(array $keys = [])
    {
        $keysValues = $this->getMultiple($keys);

        $ret = [];
        foreach ($keys as $k) {
            $item = new Item($k);
            if (!array_key_exists($k, $keysValues)) {
                $ret[$k] = $item;
            } else {
                $ret[$k] = $item->setIsHit(true)->set($keysValues[$k]);
            }
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
        return $this->deleteMultiple($keys);
    }

    /**
     * {@inheritDoc}
     */
    public function save(Cache\CacheItemInterface $item)
    {
        return $this->set($item->getKey(), $item->get(), $item->getTtl());
    }

    /**
     * {@inheritDoc}
     */
    public function saveDeferred(Cache\CacheItemInterface $item)
    {
        return $this->save($item);
    }

    /**
     * {@inheritDoc}
     */
    public function commit()
    {
        return true;
    }

}

