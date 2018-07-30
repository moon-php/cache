<?php

declare(strict_types=1);

namespace Moon\Cache\Collection;

use Psr\Cache\CacheItemInterface;

interface CacheItemCollectionInterface extends \IteratorAggregate
{
    /**
     * Add an item to the collection.
     */
    public function add(CacheItemInterface $item): void;

    /**
     * Return true if a key is available in the collection, otherwise return false.
     */
    public function has(string $key): bool;

    /**
     * Return a CacheItemInterface from a key.
     *
     * @throws \InvalidArgumentException
     */
    public function get(string $key): CacheItemInterface;

    /**
     * Remove an item by a key.
     */
    public function delete(string $key): bool;
}
