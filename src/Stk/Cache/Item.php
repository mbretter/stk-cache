<?php

namespace Stk\Cache;

use DateTime;
use DateInterval;
use DateTimeInterface;
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

    /**
     * Item constructor.
     * @param string $key
     * @param ?mixed $val
     * @param int $ttl
     */
    public function __construct(string $key, $val = null, int $ttl = self::TTL_DEFAULT)
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
            $now1      = new DateTime();
            $now2      = clone($now1);
            $this->ttl = $now1->add($time)->getTimestamp() - $now2->getTimestamp();
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
     *
     * @return int
     */
    public function getTtl()
    {
        return $this->ttl;
    }
}