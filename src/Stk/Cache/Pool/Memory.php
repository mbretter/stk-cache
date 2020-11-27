<?php

namespace Stk\Cache\Pool;

use Exception;
use Psr\Cache;
use Psr\SimpleCache;
use Stk\Cache\Item;

class Memory implements SimpleCache\CacheInterface, Cache\CacheItemPoolInterface
{
    protected array $_cache = [];

    protected array $_config = [];

    protected string $prefix = "";

    public function __construct(array $config)
    {
        $this->init($config);
    }

    /**
     * @param $config
     *
     * @throws Exception
     */
    public function init(array $config = null)
    {
        if ($config !== null) {
            $this->_config = $config;
        }
        $this->setPrefix();
    }

    public function setPrefix($prefix = '')
    {
        if (strlen($prefix)) {
            $this->prefix = sprintf('%s%s_', $this->_config['prefix'], $prefix);
        } else {
            $this->prefix = $this->_config['prefix'];
        }
    }

    protected function buildKey($key)
    {
        return sprintf('%s%s', $this->prefix, $key);
    }

    public function dump()
    {
        return $this->_cache;
    }

    public function restore($cacheDate)
    {
        $this->_cache = $cacheDate;
    }

    // the simple interface

    /**
     * {@inheritDoc}
     */
    public function get($key, $default = null)
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
    public function set($key, $value, $ttl = Item::TTL_DEFAULT)
    {
        $this->_cache[$this->buildKey($key)] = ['data' => $value, 'expires' => time() + $ttl];

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function has($key)
    {
        return array_key_exists($this->buildKey($key), $this->_cache);
    }

    /**
     * {@inheritDoc}
     */
    public function clear()
    {
        $this->_cache = [];

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
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
    public function getMultiple($keys, $default = null)
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
    public function setMultiple($values, $ttl = 300)
    {
        foreach ($values as $k => $v) {
            $this->set($k, $v, $ttl);
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteMultiple($values)
    {
        foreach ($values as $k => $v) {
            $this->delete($k);
        }

        return true;
    }

    // psr-cache

    /**
     * {@inheritDoc}
     */
    public function getItem($key)
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
    public function getItems(array $keys = [])
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
        foreach ($keys as $k) {
            $this->delete($k);
        }

        return true;
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
        return $this->set($item->getKey(), $item->get(), $item->getTtl());
    }

    /**
     * {@inheritDoc}
     */
    public function commit()
    {
        return true;
    }

}

