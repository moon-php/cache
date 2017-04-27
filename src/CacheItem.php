<?php

namespace Moon;

use Psr\Cache\CacheItemInterface;

class CacheItem implements CacheItemInterface
{
    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        // TODO: Implement getKey() method.
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        // TODO: Implement get() method.
    }

    /**
     * {@inheritdoc}
     */
    public function isHit()
    {
        // TODO: Implement isHit() method.
    }

    /**
     * {@inheritdoc}
     */
    public function set($value)
    {
        // TODO: Implement set() method.
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAt($expiration)
    {
        // TODO: Implement expiresAt() method.
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAfter($time)
    {
        // TODO: Implement expiresAfter() method.
    }
}