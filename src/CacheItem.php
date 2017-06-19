<?php

declare(strict_types=1);

namespace Moon\Cache;

use Moon\Cache\Exception\CacheInvalidArgumentException;
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
     * @var bool $hit
     */
    protected $hit;

    /**
     * @var \DateTimeImmutable $expiration
     */
    protected $expiration;

    /**
     * Default value to use as default expiration date
     */
    protected const DEFAULT_EXPIRATION = 'now +1 week';

    /**
     * CacheItem constructor.
     *
     * @param string $key
     * @param $value
     * @param bool $hit
     * @param \DateTimeImmutable $expiration
     */
    public function __construct(string $key, $value, \DateTimeImmutable $expiration = null, bool $hit = false)
    {
        $this->validateKey($key);
        $this->key = $key;
        $this->value = $value;
        $this->hit = $hit;
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
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function isHit(): bool
    {
        return $this->hit;
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
     */
    public function expiresAt($expiration = null): CacheItemInterface
    {
        if ($expiration instanceof \DateTimeInterface) {
            $this->expiration = $expiration;
        } elseif ($expiration instanceof \DateTime) {
            $this->expiration = \DateTimeImmutable::createFromMutable($expiration);
        } elseif ($expiration instanceof \DateInterval) {
            $this->expiration = (new \DateTimeImmutable())->add($expiration);
        } elseif (is_int($expiration)) {
            $this->expiration = new \DateTimeImmutable("now +$expiration seconds");
        } elseif (null === $expiration) {
            $this->expiration = new \DateTimeImmutable(self::DEFAULT_EXPIRATION);
        } else {
            throw new CacheInvalidArgumentException('Invalid expiration parameter for CacheItem.');
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAfter($time): CacheItemInterface
    {
        return $this->expiresAt($time);
    }
}