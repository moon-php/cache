<?php

declare(strict_types=1);

namespace Moon\Cache\Adapters;

use Moon\Cache\CacheItem;
use Moon\Cache\Exception\InvalidArgumentException;
use Psr\Cache\CacheItemInterface;

interface AdapterInterface
{
    /**
     * Return a CacheItem using his key.
     */
    public function getItem(string $key): CacheItemInterface;

    /**
     * Return a array from an array of keys.
     *
     * @param string[] $keys
     *
     * @return CacheItem[]
     */
    public function getItems(array $keys = []): array;

    /**
     * Return true if CacheItem exists instead false.
     */
    public function hasItem(string $key): bool;

    /**
     * Remove all CacheItem of a pool.
     */
    public function clear(): bool;

    /**
     * Delete a CacheItem using his key.
     */
    public function deleteItem(string $key): bool;

    /**
     * Delete a list of CacheItem using their keys.
     */
    public function deleteItems(array $keys): bool;

    /**
     * Add a new CacheItem to the pool.
     */
    public function save(CacheItemInterface $item): bool;

    /**
     * Add new CacheItems to the pool.
     *
     * @param CacheItem[] $items
     *
     * @return bool
     *
     * @throws InvalidArgumentException
     */
    public function saveItems(array $items): bool;
}
