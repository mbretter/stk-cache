<?php

namespace Stk\Cache;

interface CacheInterface
{
    public function init(array $config = null);

    public function store($key, $var, $ttl = 300): bool ;

    public function add($key, $var, $ttl = 300): bool;

    public function get($key);

    public function getElse($key, $callableNotFound, $ttl = 300);

    public function delete($key): bool;

    public function getMulti(array $keys);

    public function setMulti(array $values, $ttl = 300): bool;

    public function setPrefix($prefix);

    public function storeGroup($group, $key, $var, $ttl = 300): bool;

    public function getGroup($group, $key);

    public function getGroupElse($group, $key, $callableNotFound, $ttl = 300);
}
