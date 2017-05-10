<?php

namespace Moon\Cache\Adapters;

use Moon\Cache\CacheItem;
use Moon\Cache\Collection\CacheItemCollectionInterface;
use Moon\Cache\Exception\CacheItemNotFoundException;
use Psr\Cache\CacheItemInterface;

class RedisAdapter extends AbstractAdapter
{
    /**
     * @var string
     */
    private $poolName;
    /**
     * @var \Redis
     */
    private $redis;

    private $separator = '.';

    /**
     * RedisAdapter constructor.
     * @param string $poolName
     * @param \Redis $redis
     */
    public function __construct(string $poolName, \Redis $redis)
    {
        $this->poolName = $poolName;
        $this->redis = $redis;
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(array $keys = []): CacheItemCollectionInterface
    {
        $this->normalizeKeyName($keys);
        $items = $this->redis->getMultiple($keys);

        $cacheItemCollection = $this->createCacheItemCollection();

        foreach ($items as $k => $item) {
            if ($item !== false) {
                $cacheItemCollection->add($this->createCacheItemFromValue([$keys[$k] => $item]));
            }
        }

        return $cacheItemCollection;
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem($key): bool
    {
        $this->normalizeKeyName($keys);

        return $this->redis->exists($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $key): CacheItemInterface
    {
        $this->normalizeKeyName($key);
        $item = $this->redis->get($key);

        if ($item === false) {
            throw new CacheItemNotFoundException();
        }

        return $this->createCacheItemFromValue([$key => $item]);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        $keys = $this->redis->keys($this->poolName . $this->separator . '*');
        return (bool)$this->redis->delete($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItem(string $key): bool
    {
        $this->normalizeKeyName($key);

        return (bool)$this->redis->delete($key);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys): bool
    {
        $this->normalizeKeyName($keys);

        return (bool)$this->redis->delete($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function saveItems(CacheItemCollectionInterface $items): bool
    {
        $mul = $this->redis->multi();

        try {
            /** @var CacheItemInterface $item */
            foreach ($items as $k => $item) {
                $key = $this->poolName . $this->separator . $item->getKey();
                $value = [$item->get(), $this->retrieveExpiringDateFromCacheItem($item)];
                $this->redis->set($key, serialize($value));
            }
            return !in_array(false, array_values($mul->exec()));
        } catch (\Exception $e) {
            $mul->discard();
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save(CacheItemInterface $item): bool
    {
        $key = $this->poolName . $this->separator . $item->getKey();
        $value = [$item->get(), $this->retrieveExpiringDateFromCacheItem($item)];

        return $this->redis->set($key, serialize($value));
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
        $values = unserialize($item[$originalKey]);

        // Create a new CacheItem
        return new CacheItem($key, $values[0], $values[1]);
    }
}