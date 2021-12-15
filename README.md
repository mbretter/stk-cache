# php cache implementation (PSR-6 and PSR-16)

[![License](https://img.shields.io/badge/license-BSD-blue.svg)](https://opensource.org/licenses/BSD-3-Clause)
[![PHP 8.0](https://img.shields.io/badge/php-8.0-yellow.svg)](http://www.php.net)
[![Latest Stable Version](https://img.shields.io/packagist/v/mbretter/stk-cache.svg)](https://packagist.org/packages/mbretter/stk-cache)
[![Total Downloads](https://img.shields.io/packagist/dt/mbretter/stk-cache.svg)](https://packagist.org/packages/mbretter/stk-cache)
![CI](https://github.com/mbretter/stk-cache/actions/workflows/ci.yml/badge.svg)

Support for memcached and APCu is included.

## Memcached

```php
$memcached = new Memcached();
$memcached->addServer("127.0.0.1", 11211, 50]);
$pool = new Cache\Pool\Memcached($memcached);

$cache = new Cache\Cache($pool);
```

## APCU

```php
$pool = new Cache\Pool\APCu();
$cache = new Cache\Cache($pool);
```

## Blackhole

The blackhole pool is a dummy pool, which does not do any caching. 
It can be used on a development environment, when caching should be disabled.
```php
$pool = new Cache\Pool\Blackhole();
$cache = new Cache\Cache($pool);
```

## Memory

A cache pool which uses an instance variable of the pool object as cache.

```php
$pool = new Cache\Pool\Memory();
$cache = new Cache\Cache($pool);
```

## Additional methods

Besides, the PSR standards, the Cache has implemented some useful extra methods. 

### getSet

getSet invokes a closure, if the key was not found inside the cache.
This helps to build a linear code base, without additional conditions checking whether the key was found or not.

If the key was found, the value is returned directly, without invkoing the closure.

```php
$pool = new Cache\Pool\APCu();
$cache = new Cache\Cache($pool);

$val = $cache->getSet('mykey', function() {
    // ... do some expensive calculations
    return $val;
});
```

### Grouping

If you want to invalidate a group of cache items, by only removing one key, this could be done by using the group feature.

```php
$groupkey = 'mygroup';

$cache->setGrouped($groupkey, 'key1', $val1);
$cache->setGrouped($groupkey, 'key2', $val2);
$cache->setGrouped($groupkey, 'key-' . uniqid(), $val2);

$cache->delete($groupkey); // invalidates key1, key2, key-xxxx

$val = $cache->getGrouped($groupkey, 'key1'); // $val is null
```

getSetGrouped works in the same way as getSet, but with the additional group key.

