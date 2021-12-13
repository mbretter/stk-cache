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

    protected string $key;

    protected mixed $val = null;

    protected bool $hit = false;

    /** @var int TTL in seconds */
    protected int $ttl;

    /**
     * Item constructor.
     * @param string $key
     * @param ?mixed $val
     * @param int $ttl
     */
    public function __construct(string $key, mixed $val = null, int $ttl = self::TTL_DEFAULT)
    {
        $this->key = $key;
        $this->val = $val;
        $this->hit = $val !== null;
        $this->ttl = $ttl;
    }

    /**
     * {@inheritDoc}
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * {@inheritDoc}
     */
    public function get(): mixed
    {
        if (!$this->isHit()) {
            return null;
        }

        return $this->val;
    }

    /**
     * {@inheritDoc}
     */
    public function isHit(): bool
    {
        return $this->hit;
    }

    /**
     * {@inheritDoc}
     */
    public function set($value): static
    {
        $this->val = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function expiresAt($expiration): static
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
    public function expiresAfter($time): static
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

    public function setIsHit(bool $isHit): static
    {
        $this->hit = $isHit;

        return $this;
    }

    /**
     * TTL in seconds
     *
     * @return int
     */
    public function getTtl(): int
    {
        return $this->ttl;
    }
}