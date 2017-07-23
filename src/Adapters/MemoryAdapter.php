<?php

declare(strict_types=1);

namespace Moon\Cache\Adapters;

use Moon\Cache\Exception\InvalidArgumentException;
use Moon\Cache\Exception\ItemNotFoundException;
use Psr\Cache\CacheItemInterface;

class MemoryAdapter extends AbstractAdapter
{
    /**
     * @var array
     */
    private $items = [];

    /**
     * {@inheritdoc}
     */
    public function getItem(string $key): CacheItemInterface
    {
        if (!isset($this->items[$key])) {
            throw new ItemNotFoundException("The key $key is not available in the array");
        }

        return $this->items[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(array $keys = []): array
    {
        // Create an empty items
        $cacheItems = [];

        // Add to the items all items found
        // Do not throw ItemNotFoundException if item is not found
        foreach ($keys as $key) {
            try {
                $cacheItems[] = $this->getItem($key);
            } catch (ItemNotFoundException $e) {
                continue;
            }
        }

        return $cacheItems;
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem(string $key): bool
    {
        return isset($this->items[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        $this->items = [];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItem(string $key): bool
    {
        if (isset($this->items[$key])) {
            unset($this->items[$key]);

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys): bool
    {
        $clonedItems = $this->items;
        foreach ($keys as $key) {
            if (!isset($clonedItems[$key])) {

                return false;
            }
            unset($clonedItems[$key]);
        }
        $this->items = $clonedItems;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function save(CacheItemInterface $item): bool
    {
        $this->items[$item->getKey()] = $item;

        return true;
    }

    /**
     * {@inheritdoc}
     * @throws InvalidArgumentException
     */
    public function saveItems(array $items): bool
    {
        $clonedItems = $this->items;
        foreach ($items as $item) {
            if (!$item instanceof CacheItemInterface) {
                throw new InvalidArgumentException('All items must implement' . CacheItemInterface::class, $item);
            }

            $clonedItems[$item->getKey()] = $item;
        }
        $this->items = $clonedItems;

        return true;
    }
}