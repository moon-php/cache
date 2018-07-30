<?php

declare(strict_types=1);

namespace Moon\Cache\Adapters;

use Moon\Cache\CacheItem;
use Moon\Cache\Exception\InvalidArgumentException;
use Moon\Cache\Exception\ItemNotFoundException;
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

    /**
     * @var string
     */
    private $separator = '.';

    public function __construct(string $poolName, \Redis $redis)
    {
        $this->poolName = $poolName;
        $this->redis = $redis;
    }

    public function getItems(array $keys = []): array
    {
        $normalizedKeys = $this->normalizeKeyNames($keys);
        $items = $this->redis->getMultiple($normalizedKeys);

        $cacheItems = [];

        foreach ($items as $k => $item) {
            if (false !== $item) {
                $cacheItems[] = $this->createCacheItemFromValue([$normalizedKeys[$k] => $item]);
            }
        }

        return $cacheItems;
    }

    public function hasItem(string $key): bool
    {
        $normalizedKey = $this->normalizeKeyName($key);

        return $this->redis->exists($normalizedKey);
    }

    public function getItem(string $key): CacheItemInterface
    {
        $normalizedKey = $this->normalizeKeyName($key);
        $item = $this->redis->get($normalizedKey);

        if (false === $item) {
            throw new ItemNotFoundException();
        }

        return $this->createCacheItemFromValue([$normalizedKey => $item]);
    }

    public function clear(): bool
    {
        $keys = $this->redis->keys($this->poolName.$this->separator.'*');

        return (bool) $this->redis->delete($keys);
    }

    public function deleteItem(string $key): bool
    {
        $normalizedKey = $this->normalizeKeyName($key);

        return (bool) $this->redis->delete($normalizedKey);
    }

    public function deleteItems(array $keys): bool
    {
        $normalizedKeys = $this->normalizeKeyNames($keys);

        return (bool) $this->redis->delete($normalizedKeys);
    }

    public function saveItems(array $items): bool
    {
        $mul = $this->redis->multi();

        try {
            /** @var CacheItemInterface $item */
            foreach ($items as $k => $item) {
                if (!$item instanceof CacheItemInterface) {
                    throw new InvalidArgumentException('All items must implement'.CacheItemInterface::class, $item);
                }

                $key = $this->poolName.$this->separator.$item->getKey();
                $value = [$item->get(), $this->retrieveExpiringDateFromCacheItem($item)];
                $this->redis->set($key, \serialize($value));
            }

            return !\in_array(false, \array_values($mul->exec()), true);
        } catch (\Exception $e) {
            $mul->discard();

            return false;
        }
    }

    public function save(CacheItemInterface $item): bool
    {
        $key = $this->poolName.$this->separator.$item->getKey();
        $value = [$item->get(), $this->retrieveExpiringDateFromCacheItem($item)];

        return $this->redis->set($key, \serialize($value));
    }

    /**
     * Normalize the keys, adding the poolName prefix.
     */
    protected function normalizeKeyNames(array $keys): array
    {
        return \array_map([$this, 'normalizeKeyName'], $keys);
    }

    /**
     * Normalize the key, adding the poolName prefix.
     */
    protected function normalizeKeyName(string $key): string
    {
        return $this->poolName.$this->separator.$key;
    }

    /**
     * Create a CacheItemInterface object from an item.
     */
    protected function createCacheItemFromValue(array $item): CacheItemInterface
    {
        $originalKey = \key($item);
        $key = \str_replace($this->poolName.$this->separator, '', $originalKey);
        $values = \unserialize($item[$originalKey]);

        // Create a new CacheItem
        return new CacheItem($key, $values[0], $values[1]);
    }
}
