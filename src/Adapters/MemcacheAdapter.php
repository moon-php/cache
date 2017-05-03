<?php

namespace Moon\Cache\Adapters;

use Moon\Cache\CacheItem;
use Moon\Cache\Collection\CacheItemCollectionInterface;
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
     * FileSystemAdapter constructor.
     * @param string $poolName
     * @param \Memcached $memcached
     */
    public function __construct(string $poolName, \Memcached $memcached)
    {
        $this->poolName = $poolName;
        $this->memcached = $memcached;
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(array $keys = []): CacheItemCollectionInterface
    {
        $this->normalizeKeyName($keys);

        $items = $this->memcached->getMultiByKey($this->poolName, $keys);

        $cacheItemCollection = $this->createCacheItemCollection();

        foreach ($items as $item) {
            $cacheItemCollection->add($this->createCacheItemFromValue($item));
        }

        return $cacheItemCollection;
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
                $keys[$k] = $this->poolName . '_' . $key;
            }
        } else {
            $keys = $this->poolName . '_' . $keys;
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
        //
        $key = str_replace($this->poolName . '_', '', array_keys($item)[0]);
        $value = unserialize($item[0]);
        $date = unserialize($item[1]);

        // Create a new CacheItem
        return new CacheItem($key, $value, $date);
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem($key): bool
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

        return $this->createCacheItemFromValue($item);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        // Get all keys and get only the one of this pool (comparing the prefix of the key with the poolName)
        $keys = $this->memcached->getAllKeys();
        foreach ($keys as $k => $key) {
            if (strpos($key, $this->poolName) !== 0) {
                unset($keys[$k]);
            }
        }

        return $this->memcached->deleteMultiByKey($this->poolName, $keys);
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

        return $this->memcached->deleteMultiByKey($this->poolName, $keys);
    }

    /**
     * {@inheritdoc}
     */
    public function saveItems(CacheItemCollectionInterface $items): bool
    {
        $elaboratedItems = [];

        /** @var CacheItemInterface $item */
        foreach ($items as $k => $item) {
            $elaboratedItems[$this->poolName . '_' . $item->getKey()] = [
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
        $key = $this->poolName . '_' . $item->getKey();
        $value = [
            serialize($item->get()),
            serialize($this->retrieveExpiringDateFromCacheItem($item))
        ];

        return $this->memcached->setByKey($this->poolName, $key, $value);
    }
}