<?php

declare(strict_types=1);

namespace Moon\Cache;

use Moon\Cache\Exception\InvalidArgumentException;
use Psr\Cache\CacheItemInterface;

class CacheItem implements CacheItemInterface
{
    use KeyValidator;

    /**
     * @var string $key
     */
    protected $key;

    /**
     * @var mixed $value
     */
    protected $value;

    /**
     * @var \DateTimeImmutable $expiration
     */
    protected $expiration;

    /**
     * Default value to use as default expiration date
     */
    public const DEFAULT_EXPIRATION = 'now +1 week';

    /**
     * CacheItem constructor.
     *
     * @param string $key
     * @param $value
     * @param \DateTimeImmutable $expiration
     *
     * @throws InvalidArgumentException
     */
    public function __construct(string $key, $value, \DateTimeImmutable $expiration = null)
    {
        $this->validateKey($key);
        $this->key = $key;
        $this->value = $value;
        $this->expiration = $expiration ?: new \DateTimeImmutable(static::DEFAULT_EXPIRATION);
    }

    /**
     * {@inheritdoc}
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        return $this->isHit() ? $this->value : null;
    }

    /**
     * {@inheritdoc}
     */
    public function isHit(): bool
    {
        return $this->expiration >= new \DateTimeImmutable();
    }

    /**
     * {@inheritdoc}
     */
    public function set($value): CacheItemInterface
    {
        $this->value = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    public function expiresAt($expiration = null): CacheItemInterface
    {
        if ($expiration instanceof \DateTimeImmutable) {
            $this->expiration = $expiration;
        } elseif ($expiration instanceof \DateTime) {
            $this->expiration = \DateTimeImmutable::createFromMutable($expiration);
        } elseif (null === $expiration) {
            $this->expiration = new \DateTimeImmutable(self::DEFAULT_EXPIRATION);
        } else {
            throw new InvalidArgumentException('Invalid expiration parameter for CacheItem.');
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    public function expiresAfter($time = null): CacheItemInterface
    {
        if ($time instanceof \DateInterval) {
            $this->expiration = (new \DateTimeImmutable())->add($time);
        } elseif (is_int($time)) {
            $this->expiration = new \DateTimeImmutable("now +$time seconds");
        } elseif ($time === null) {
            $this->expiration = new \DateTimeImmutable(static::DEFAULT_EXPIRATION);
        } else {
            throw new InvalidArgumentException('Invalid time for CacheItem.');
        }

        return $this;
    }
}