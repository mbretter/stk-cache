<?php

namespace Stk\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface as SimpleCacheInterface;

interface PoolInterface extends SimpleCacheInterface, CacheItemPoolInterface
{
}
