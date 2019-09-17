<?php

namespace Stk\Cache;

use DateInterval;
use DateTime;
use DateTimeInterface;
use Exception;
use Psr\Cache\CacheItemInterface;

class Item implements CacheItemInterface
{
    public const TTL_FOREVER = 0;
    public const TTL_DEFAULT = 300;

    /** @var string */
    protected $key;

    /** @var mixed */
    protected $val;

    /** @var bool */
    protected $hit = false;

    /** @var int TTL in seconds */
    protected $ttl;

    public function __construct($key, $val = null, $ttl = self::TTL_DEFAULT)
    {
        $this->key = $key;
        $this->val = $val;
        $this->hit = $val !== null;
        $this->ttl = $ttl;
    }

    /**
     * {@inheritDoc}
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * {@inheritDoc}
     */
    public function get()
    {
        if (!$this->isHit()) {
            return null;
        }

        return $this->val;
    }

    /**
     * {@inheritDoc}
     */
    public function isHit()
    {
        return $this->hit;
    }

    /**
     * {@inheritDoc}
     */
    public function set($value)
    {
        $this->val = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function expiresAt($expiration)
    {
        if ($expiration instanceof DateTimeInterface) {
            $this->ttl = $expiration->getTimestamp() - (new DateTime())->getTimestamp();
        }

        if ($expiration === null) {
            $this->ttl = self::TTL_FOREVER;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function expiresAfter($time)
    {
        if (is_int($time)) {
            $this->ttl = $time;
        }

        if ($time instanceof DateInterval) {
            $this->ttl = ((new DateTime())->add($time))->getTimestamp() - new DateTime();
        }

        if ($time === null) {
            $this->ttl = self::TTL_FOREVER;
        }

        return $this;
    }

    /**
     * @param bool $isHit
     *
     * @return $this
     */
    public function setIsHit($isHit)
    {
        $this->hit = $isHit;

        return $this;
    }

    /**
     * TTL in seconds
     * @return int
     */
    public function getTtl()
    {
        return $this->ttl;
    }
}