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
    public function add(CacheItemInterface $item);
}