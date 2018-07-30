<?php

declare(strict_types=1);

namespace Moon\Cache\Adapters;

use DateTimeImmutable;
use Moon\Cache\CacheItem;
use Moon\Cache\Exception\InvalidArgumentException;
use Moon\Cache\Exception\ItemNotFoundException;
use Psr\Cache\CacheItemInterface;

class MemcacheAdapter extends AbstractAdapter
{
    /**
     * @var string
     */
    private $poolName;
    /**
     * @var \Memcached
     */
    private $memcached;
    /**
     * @var string Separator of the "namespace" combined from "poolName" and "key"
     */
    private $separator;
    /**
     * @const INVALID_CHARS Contains all invalid key chars
     */
    private const INVALID_CHARS = [' ', PHP_EOL, '\r', 0];

    public function __construct(string $poolName, \Memcached $memcached, $separator = '.')
    {
        $this->validateKey($poolName);
        $this->poolName = $poolName;
        $this->memcached = $memcached;
        $this->separator = $separator;
    }

    public function getItems(array $keys = []): array
    {
        $this->normalizeKeyName($keys);
        $items = $this->memcached->getMultiByKey($this->poolName, $keys);
        $cacheItems = [];

        foreach ($items as $k => $item) {
            $cacheItems[] = $this->createCacheItemFromValue([$k => $item]);
        }

        return $cacheItems;
    }

    public function hasItem(string $key): bool
    {
        $this->normalizeKeyName($keys);
        $item = $this->memcached->getByKey($this->poolName, $key);

        return !(!$item || \Memcached::RES_NOTFOUND === $item);
    }

    public function getItem(string $key): CacheItemInterface
    {
        $this->normalizeKeyName($key);
        $item = $this->memcached->getByKey($this->poolName, $key);
        if (!$item || \Memcached::RES_NOTFOUND === $item) {
            throw new ItemNotFoundException();
        }

        return $this->createCacheItemFromValue([$key => $item]);
    }

    public function clear(): bool
    {
        // Get all keys
        // Remove all the keys not related to this pool
        // Clean all related key by "poolName + separator" (so the deleteItems method will not append them twice)
        $keys = $this->memcached->getAllKeys();
        foreach ($keys as $k => $key) {
            if (0 !== \mb_strpos($key, $this->poolName)) {
                unset($keys[$k]);
            } else {
                $keys[$k] = \str_replace($this->poolName.$this->separator, '', $keys[$k]);
            }
        }

        return $this->deleteItems($keys);
    }

    public function deleteItem(string $key): bool
    {
        $this->normalizeKeyName($key);

        return $this->memcached->deleteByKey($this->poolName, $key);
    }

    public function deleteItems(array $keys): bool
    {
        $this->normalizeKeyName($keys);

        foreach ($keys as $key) {
            if (!$this->memcached->deleteMultiByKey($this->poolName, $key)) {
                return false;
            }
        }

        return true;
    }

    public function saveItems(array $items): bool
    {
        $elaboratedItems = [];

        foreach ($items as $k => $item) {
            if (!$item instanceof CacheItemInterface) {
                throw new InvalidArgumentException('All items must implement'.CacheItemInterface::class, $item);
            }

            $elaboratedItems[$this->poolName.$this->separator.$item->getKey()] = [
                \serialize($item->get()), \serialize($this->retrieveExpiringDateFromCacheItem($item)),
            ];
        }

        return $this->memcached->setMultiByKey($this->poolName, $elaboratedItems);
    }

    public function save(CacheItemInterface $item): bool
    {
        $key = $this->poolName.$this->separator.$item->getKey();
        $value = [
            \serialize($item->get()),
            \serialize($this->retrieveExpiringDateFromCacheItem($item)),
        ];

        return $this->memcached->setByKey($this->poolName, $key, $value);
    }

    /**
     * Normalize the key, adding the poolName prefix.
     */
    protected function normalizeKeyName(&$keys): void
    {
        if (\is_array($keys)) {
            foreach ($keys as $k => $key) {
                $keys[$k] = $this->poolName.$this->separator.$key;
            }
        } else {
            $keys = $this->poolName.$this->separator.$keys;
        }
    }

    /**
     * Create a CacheItemInterface object from an item.
     */
    protected function createCacheItemFromValue(array $item): CacheItemInterface
    {
        $originalKey = \key($item);
        $key = \str_replace($this->poolName.$this->separator, '', $originalKey);
        $value = \unserialize($item[$originalKey][0]);
        $date = \unserialize($item[$originalKey][1], [DateTimeImmutable::class]);

        return new CacheItem($key, $value, $date);
    }

    /**
     * Check if a key is valid.
     */
    protected function validateKey(string $key): void
    {
        foreach (self::INVALID_CHARS as $invalidChar) {
            if (false !== \mb_strpos($key, $invalidChar)) {
                throw new InvalidArgumentException(
                    "$key, is invalid, it contains an invalid character '$invalidChar'"
                );
            }
        }
    }
}
