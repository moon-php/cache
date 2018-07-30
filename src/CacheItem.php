<?php

declare(strict_types=1);

namespace Moon\Cache;

use Moon\Cache\Exception\InvalidArgumentException;
use Psr\Cache\CacheItemInterface;

class CacheItem implements CacheItemInterface
{
    use KeyValidator;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var bool
     */
    protected $hit;

    /**
     * @var \DateTimeImmutable
     */
    protected $expiration;

    /**
     * Default value to use as default expiration date.
     */
    public const DEFAULT_EXPIRATION = 'now +1 week';

    public function __construct(string $key, $value, \DateTimeImmutable $expiration = null)
    {
        $this->validateKey($key);
        $this->key = $key;
        $this->value = $value;
        $this->expiration = $expiration ?: new \DateTimeImmutable(static::DEFAULT_EXPIRATION);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function get()
    {
        return $this->isHit() ? $this->value : null;
    }

    public function isHit(): bool
    {
        return $this->expiration >= new \DateTimeImmutable();
    }

    public function set($value): CacheItemInterface
    {
        $this->value = $value;

        return $this;
    }

    public function expiresAt($expiration = null): CacheItemInterface
    {
        if ($expiration instanceof \DateTimeImmutable) {
            $this->expiration = $expiration;
        } elseif ($expiration instanceof \DateTimeImmutable) {
            $this->expiration = \DateTimeImmutable::createFromMutable($expiration);
        } elseif ($expiration instanceof \DateInterval) {
            $this->expiration = (new \DateTimeImmutable())->add($expiration);
        } elseif (\is_int($expiration)) {
            $this->expiration = new \DateTimeImmutable("now +$expiration seconds");
        } elseif (null === $expiration) {
            $this->expiration = new \DateTimeImmutable(self::DEFAULT_EXPIRATION);
        } else {
            throw new InvalidArgumentException('Invalid expiration parameter for CacheItem.');
        }

        return $this;
    }

    public function expiresAfter($time = null): CacheItemInterface
    {
        if ($time instanceof \DateInterval) {
            $this->expiration = (new \DateTimeImmutable())->add($time);
        } elseif (\is_int($time)) {
            $this->expiration = new \DateTimeImmutable("now +$time seconds");
        } elseif (null === $time) {
            $this->expiration = new \DateTimeImmutable(static::DEFAULT_EXPIRATION);
        } else {
            throw new InvalidArgumentException('Invalid time for CacheItem.');
        }

        return $this;
    }
}
