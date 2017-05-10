<?php

namespace Moon\Cache\Adapters;

use Moon\Cache\CacheItem;
use Moon\Cache\Collection\CacheItemCollectionInterface;
use Moon\Cache\Exception\CacheInvalidArgumentException;
use Moon\Cache\Exception\CacheItemNotFoundException;
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
    const INVALID_CHARS = [' ', PHP_EOL, '\r', 0];

    /**
     * MemcacheAdapter constructor.
     * @param string $poolName
     * @param \Memcached $memcached
     * @param string $separator
     */
    public function __construct(string $poolName, \Memcached $memcached, $separator = '.')
    {
        $this->validateKey($poolName);
        $this->poolName = $poolName;
        $this->memcached = $memcached;
        $this->separator = $separator;
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(array $keys = []): CacheItemCollectionInterface
    {
        $this->normalizeKeyName($keys);
        $items = $this->memcached->getMultiByKey($this->poolName, $keys);
        $cacheItemCollection = $this->createCacheItemCollection();

        foreach ($items as $k => $item) {
            $cacheItemCollection->add($this->createCacheItemFromValue([$k => $item]));
        }

        return $cacheItemCollection;
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem(string $key): bool
    {
        $this->normalizeKeyName($keys);
        $item = $this->memcached->getByKey($this->poolName, $key);

        if (!$item || \Memcached::RES_NOTFOUND == $item) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $key): CacheItemInterface
    {
        $this->normalizeKeyName($key);
        $item = $this->memcached->getByKey($this->poolName, $key);
        if (!$item || \Memcached::RES_NOTFOUND == $item) {
            throw new CacheItemNotFoundException();
        }

        return $this->createCacheItemFromValue([$key => $item]);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        // Get all keys
        // Remove all the keys not related to this pool
        // Clean all related key by "poolName + separator" (so the deleteItems method will not append them twice)
        $keys = $this->memcached->getAllKeys();
        foreach ($keys as $k => $key) {
            if (strpos($key, $this->poolName) !== 0) {
                unset($keys[$k]);
            } else {
                $keys[$k] = str_replace($this->poolName . $this->separator, '', $keys[$k]);
            }
        }

        return $this->deleteItems($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItem(string $key): bool
    {
        $this->normalizeKeyName($key);

        return $this->memcached->deleteByKey($this->poolName, $key);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys): bool
    {
        $this->normalizeKeyName($keys);

        foreach ($this->memcached->deleteMultiByKey($this->poolName, $keys) as $deletedItem) {
            if (!$deletedItem) {

                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function saveItems(CacheItemCollectionInterface $items): bool
    {
        $elaboratedItems = [];

        /** @var CacheItemInterface $item */
        foreach ($items as $k => $item) {
            $elaboratedItems[$this->poolName . $this->separator . $item->getKey()] = [
                serialize($item->get()), serialize($this->retrieveExpiringDateFromCacheItem($item))
            ];
        }

        return $this->memcached->setMultiByKey($this->poolName, $elaboratedItems);
    }

    /**
     * {@inheritdoc}
     */
    public function save(CacheItemInterface $item): bool
    {
        $key = $this->poolName . $this->separator . $item->getKey();
        $value = [
            serialize($item->get()),
            serialize($this->retrieveExpiringDateFromCacheItem($item))
        ];

        return $this->memcached->setByKey($this->poolName, $key, $value);
    }

    /**
     * Normalize the key, adding the poolName prefix
     *
     * @param $keys
     *
     * @return mixed
     */
    protected function normalizeKeyName(&$keys): void
    {
        if (is_array($keys)) {
            foreach ($keys as $k => $key) {
                $keys[$k] = $this->poolName . $this->separator . $key;
            }
        } else {
            $keys = $this->poolName . $this->separator . $keys;
        }
    }

    /**
     * Create a CacheItemInterface object from an item
     *
     * @param array $item
     *
     * @return CacheItemInterface
     */
    protected function createCacheItemFromValue(array $item): CacheItemInterface
    {
        $originalKey = key($item);
        $key = str_replace($this->poolName . $this->separator, '', $originalKey);
        $value = unserialize($item[$originalKey][0]);
        $date = unserialize($item[$originalKey][1]);

        // Create a new CacheItem
        return new CacheItem($key, $value, $date);
    }

    /**
     * Check if a key is valid
     *
     * @param string $key
     *
     * @throws CacheInvalidArgumentException
     */
    protected function validateKey(string $key): void
    {
        foreach (self::INVALID_CHARS as $invalidChar) {
            if (strpos($key, $invalidChar) !== false) {
                throw new CacheInvalidArgumentException("$key, is invalid, it contains an invalid character '$invalidChar'");
            }
        }
    }
}