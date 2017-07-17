<?php

declare(strict_types=1);

namespace Moon\Cache\Adapters;

use Moon\Cache\Collection\CacheItemCollectionInterface;
use Moon\Cache\Exception\ItemNotFoundException;
use Psr\Cache\CacheItemInterface;

class MemoryAdapter extends AbstractAdapter
{
    /**
     * @var CacheItemCollectionInterface
     */
    private $collection;

    /**
     * MemoryAdapter constructor.
     * @param CacheItemCollectionInterface $collection
     */
    public function __construct(CacheItemCollectionInterface $collection)
    {
        $this->collection = $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $key): CacheItemInterface
    {
        try {
            return $this->collection->get($key);
        } catch (\InvalidArgumentException $e) {
            throw new ItemNotFoundException($e->getMessage(), $e->getCode(), $e);
        }

    }

    /**
     * {@inheritdoc}
     */
    public function getItems(array $keys = []): CacheItemCollectionInterface
    {
        // Create an empty collection
        $cacheItemCollection = $this->createCacheItemCollection();

        // Add to the collection all items found
        // Do not throw ItemNotFoundException if item is not found
        foreach ($keys as $key) {
            try {
                $cacheItemCollection->add($this->getItem($key));
            } catch (ItemNotFoundException $e) {
                continue;
            }
        }

        return $cacheItemCollection;
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem(string $key): bool
    {
        return $this->collection->has($key);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        $this->collection = $this->createCacheItemCollection();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItem(string $key): bool
    {
        return $this->collection->delete($key);
    }

    /**
     * THIS IS NOT TRANSACTION-SAFE
     * {@inheritdoc}
     */
    public function deleteItems(array $keys): bool
    {
        foreach ($keys as $key) {
            if (!$this->deleteItem($key)) {

                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function save(CacheItemInterface $item): bool
    {
        $this->collection->add($item);

        return true;
    }

    /**
     * THIS IS NOT TRANSACTION-SAFE
     * {@inheritdoc}
     */
    public function saveItems(CacheItemCollectionInterface $items): bool
    {
        foreach ($items as $item) {
            if (!$this->save($item)) {

                return false;
            }
        }

        return true;
    }
}