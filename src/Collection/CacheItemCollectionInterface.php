<?php

namespace Moon\Cache\Collection;

use Psr\Cache\CacheItemInterface;

interface CacheItemCollectionInterface extends \IteratorAggregate
{
    /**
     * Add an item to the collection
     *
     * @param CacheItemInterface $item
     *
     * @return void
     */
    public function add(CacheItemInterface $item): void;

    /**
     * Return true if a key is available in the collection, otherwise return false
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Return a CacheItemInterface from a key
     *
     * @param string $key
     *
     * @return CacheItemInterface
     *
     * @throws \InvalidArgumentException
     */
    public function get(string $key): CacheItemInterface;

    /**
     * Remove an item by a key
     *
     * @param string $key
     *
     * @return bool
     */
    public function delete(string $key): bool;
}