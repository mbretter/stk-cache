<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace Stk\Cache\Pool;

use DateInterval;
use DateTime;
use Psr\Cache;
use Stk\Cache\Item;
use Stk\Cache\PoolInterface;

class APCu implements PoolInterface
{

    // PSR16 simple cache

    /**
     * {@inheritDoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $success = true;
        if (apcu_exists($key)) {
            $val = apcu_fetch($key, $success);
        } else {
            $val = $default;
        }

        if ($val === false && $success === false) {
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

        return apcu_store($key, $value, (int) $ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key): bool
    {
        return apcu_delete($key);
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): bool
    {
        return apcu_clear_cache();
    }

    /**
     * {@inheritDoc}
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $aKeys = [...$keys];

        $ret = apcu_fetch($aKeys);
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

        foreach ($values as $k => $v) {
            if ($this->set($k, $v, $ttl) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteMultiple(iterable $keys): bool
    {
        $res = apcu_delete([...$keys]);

        return count($res) === 0;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        return apcu_exists($key);
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

