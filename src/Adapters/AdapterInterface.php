<?php

namespace Moon\Cache\Adapters;

use Moon\Cache\Collection\CacheItemCollectionInterface;
use Moon\Cache\Exception\CacheItemNotFoundException;
use Psr\Cache\CacheItemInterface;

interface AdapterInterface
{
    /**
     * Return a CacheItem using his key
     *
     * @param string $key
     *
     * @return CacheItemInterface
     *
     * @throws CacheItemNotFoundException
     */
    public function getItem(string $key): CacheItemInterface;

    /**
     * Return a CacheItemCollection from an array
     *
     * @param string[] $keys
     *
     * @return CacheItemCollectionInterface
     */
    public function getItems(array $keys = []): CacheItemCollectionInterface;


    /**
     * Return true if CacheItem exists instead false
     *
     * @param $key
     *
     * @return bool
     */
    public function hasItem(string $key): bool;

    /**
     * Remove all CacheItem of a pool
     *
     * @return bool
     */
    public function clear(): bool;

    /**
     * Delete a CacheItem using his key
     *
     * @param string $key
     *
     * @return bool
     */
    public function deleteItem(string $key): bool;

    /**
     * Delete a list of CacheItem using their keys
     *
     * @param string[] $keys
     *
     * @return bool
     */
    public function deleteItems(array $keys): bool;


    /**
     * Add a new CacheItem to the pool
     *
     * @param CacheItemInterface $item
     *
     * @return bool
     */
    public function save(CacheItemInterface $item): bool;

    /**
     * Add new CacheItems to the pool
     *
     * @param CacheItemCollectionInterface $items
     *
     * @return bool
     */
    public function saveItems(CacheItemCollectionInterface $items): bool;
}