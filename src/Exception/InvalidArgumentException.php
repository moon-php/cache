<?php

declare(strict_types=1);

namespace Moon\Cache\Exception;

use Throwable;

class InvalidArgumentException extends \InvalidArgumentException implements \Psr\Cache\InvalidArgumentException
{
    /**
     * @var mixed
     */
    private $invalidCacheItem;

    public function __construct($message = '', $invalidCacheItem = null, $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->invalidCacheItem = $invalidCacheItem;
    }

    /**
     * Get the invalid CacheItem.
     *
     * @return mixed|null
     */
    public function getInvalidCacheItem()
    {
        return $this->invalidCacheItem;
    }
}
