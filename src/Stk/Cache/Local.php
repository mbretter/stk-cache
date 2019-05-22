<?php

namespace Stk\Cache;

use Exception;

class Local implements CacheInterface
{
    /** @var array */
    protected $_cache = [];

    /** @var array */
    protected $_config = [];

    protected $prefix = "";

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
        if ($config !== null)
            $this->_config = $config;
        $this->setPrefix();
    }

    public function store($key, $var, $ttl = 300): bool
    {
        $this->_cache[$this->buildKey($key)] = ['data' => $var, 'expires' => time() + $ttl];

        return true;
    }


    public function add($key, $var, $ttl = 300): bool
    {
        if (array_key_exists($this->buildKey($key), $this->_cache)) {
            return false;
        }

        return $this->store($key, $var, $ttl);
    }


    public function get($key)
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

    public function storeGroup($group, $key, $val, $ttl = 300, $ref = null): bool
    {
        if ($ref === null) {
            $ref = microtime(true);
        }

        $this->store($key, [$ref, $val], $ttl);
        $this->store($group, $ref, $ttl);

        return true;
    }

    /**
     * store grouped value use reference value to check whether value is invalid
     *
     * @param $group
     * @param $key
     * @param null $ref
     *
     * @return mixed|false
     */
    public function getGroup($group, $key, &$ref = null)
    {
        $groupVal = $this->get($group);
        if ($groupVal === false) {
            return false;
        }

        $keyVal = $this->get($key);
        if ($keyVal === false) {
            return false;
        }

        if (!is_array($keyVal)) {
            return false;
        }

        // compare ref values
        if ($groupVal !== $keyVal[0]) {
            $ref = $groupVal;

            return false;
        }

        return $keyVal[1];
    }

    public function getGroupElse($group, $key, $callableNotFound, $ttl = 300)
    {
        $val = $this->getGroup($group, $key, $ref);
        if ($val !== false) {
            return $val;
        }

        $val = $callableNotFound();
        if ($val !== false) {
            $this->storeGroup($group, $key, $val, $ttl, $ref);
        }

        return $val;
    }

    public function getElse($key, $callableNotFound, $ttl = 300)
    {
        $val = $this->get($key);
        if ($val !== false) {
            return $val;
        }

        $val = $callableNotFound();
        if ($val !== false) {
            $this->store($key, $val, $ttl);
        }

        return $val;
    }

    public function delete($key): bool
    {
        if (!array_key_exists($this->buildKey($key), $this->_cache)) {
            return false;
        }

        unset($this->_cache[$this->buildKey($key)]);

        return true;
    }

    public function getMulti(array $keys): array
    {
        $ret = [];
        foreach ($keys as $k) {
            $ret[$k] = $this->get($k);
        }

        return $ret;
    }

    public function setMulti(array $values, $ttl = 300): bool
    {
        foreach ($values as $k => $v) {
            $this->store($k, $v, $ttl);
        }

        return true;
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
}

