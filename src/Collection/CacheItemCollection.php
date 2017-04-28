<?php

namespace Moon\Cache\Collection;


use Moon\Cache\Exception\CacheInvalidArgumentException;
use Psr\Cache\CacheItemInterface;

class CacheItemCollection implements CacheItemCollectionInterface
{
    /**
     * @var CacheItemInterface[] $items
     */
    protected $items = [];

    /**
     * CacheItemCollection constructor.
     * @param CacheItemInterface[] $items
     */
    public function __construct(array $items = [])
    {
        foreach ($items as $item) {
            if (!$item instanceof CacheItemInterface) {
                throw new CacheInvalidArgumentException("Item $item be an instance of CacheItemInterface");
            }
            $this->items[$item->getKey()] = $item;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function add(CacheItemInterface $item): void
    {
        $this->items[$item->getKey()] = $item;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        foreach ($this->items as $key => $item) {
            yield $key => $item;
        }
    }
}