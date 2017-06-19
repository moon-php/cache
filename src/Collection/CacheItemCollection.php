<?php

declare(strict_types=1);

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
    public function has(string $key): bool
    {
        return isset($this->items[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key): CacheItemInterface
    {
        if ($this->has($key)) {
            return $this->items[$key];
        }

        throw new \InvalidArgumentException("The key $key is not available in the collection");
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        if ($this->has($key)) {
            unset($this->items[$key]);

            return true;
        }

        return false;
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