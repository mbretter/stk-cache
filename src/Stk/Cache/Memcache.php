<?php

namespace Stk\Cache;

use Exception;
use Memcached;

class Memcache implements CacheInterface
{
    /** @var Memcached */
    protected $_cache;

    protected $prefix = "";

    /** @var array */
    protected $_config;

    public function __construct(array $config)
    {
        $this->_config = $config;
    }

    /**
     * @param $config
     *
     * @throws \Exception
     */
    public function init(array $config = null)
    {
        if ($config !== null) {
            $this->_config = $config;
        }

        $servers = array();
        foreach ($this->_config['servers'] as $server) {
            $temp_array = explode(":", $server);
            if (!isset($temp_array[1])) {
                $temp_array[1] = 11211;
            }
            if (!isset($temp_array[2])) {
                $temp_array[2] = 50;
            }
            $servers[] = $temp_array;
        }

        $this->getCache()->resetServerList();
        if (!$this->getCache()->addServers($servers)) {
            throw new Exception("Memcached: unable to set server: " . print_r($servers, true));
        }

        $this->setPrefix();
    }

    public function setCache(Memcached $memcached)
    {
        $this->_cache = $memcached;
    }

    public function getCache()
    {
        if ($this->_cache === null) {
            $this->_cache = new Memcached();
            $this->init();
        }

        return $this->_cache;
    }

    public function store($key, $var, $ttl = 300): bool
    {
        return $this->getCache()->set($key, $var, $ttl);
    }

    public function storeGroup($group, $key, $val, $ttl = 300, $ref = null): bool
    {
        if ($ref === null) {
            $ref = microtime(true);
        }

        return $this->getCache()->setMulti([$group => $ref, $key => [$ref, $val]], $ttl);
    }

    /**
     * store grouped value use reference value to check whether value is invalid
     *
     * @param $group
     * @param $key
     * @param null $ref
     *
     * @return bool
     */
    public function getGroup($group, $key, &$ref = null)
    {
        $values = $this->getCache()->getMulti([$group, $key]);
        if ($values === false) {
            return false;
        }

        if (!array_key_exists($group, $values) || !array_key_exists($key, $values)) {
            if (array_key_exists($group, $values)) {
                $ref = $values[$group];
            }

            return false;
        }

        if (!is_array($values[$key]) || count($values[$key]) != 2) {
            return false;
        }

        // compare ref values
        if ($values[$group] !== $values[$key][0]) {
            $ref = $values[$group];

            return false;
        }

        return $values[$key][1];
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


    public function add($key, $var, $ttl = 300): bool
    {
        return $this->getCache()->add($key, $var, $ttl);
    }

    public function get($key)
    {
        return $this->getCache()->get($key);
    }

    public function getElse($key, $callableNotFound, $ttl = 300)
    {
        $val = $this->getCache()->get($key);
        if ($val !== false) {
            return $val;
        }

        $val = $callableNotFound();
        if ($val !== false) {
            $this->getCache()->add($key, $val, $ttl);
        }

        return $val;
    }

    public function delete($key): bool
    {
        return $this->getCache()->delete($key);
    }

    public function getMulti(array $keys)
    {
        return $this->getCache()->getMulti($keys);
    }

    public function setMulti(array $values, $ttl = 300): bool
    {
        return $this->getCache()->setMulti($values, $ttl);
    }

    public function setPrefix($prefix = '')
    {
        if (strlen($prefix)) {
            $this->prefix = sprintf('%s%s_', $this->_config['prefix'], $prefix);
        } else {
            $this->prefix = $this->_config['prefix'];
        }

        $this->getCache()->setOption(Memcached::OPT_PREFIX_KEY, $this->prefix);
    }
}

