<?php

declare(strict_types=1);

namespace Moon\Cache\Collection;

use Psr\Cache\CacheItemInterface;

class CacheItemCollection implements CacheItemCollectionInterface
{
    /**
     * @var CacheItemInterface[]
     */
    protected $items = [];

    public function __construct(CacheItemInterface ...$items)
    {
        foreach ($items as $item) {
            $this->items[$item->getKey()] = $item;
        }
    }

    public function add(CacheItemInterface $item): void
    {
        $this->items[$item->getKey()] = $item;
    }

    public function has(string $key): bool
    {
        return isset($this->items[$key]);
    }

    public function get(string $key): CacheItemInterface
    {
        if ($this->has($key)) {
            return $this->items[$key];
        }

        throw new \InvalidArgumentException("The key $key is not available in the collection");
    }

    public function delete(string $key): bool
    {
        if ($this->has($key)) {
            unset($this->items[$key]);

            return true;
        }

        return false;
    }

    public function getIterator()
    {
        foreach ($this->items as $key => $item) {
            yield $key => $item;
        }
    }
}
