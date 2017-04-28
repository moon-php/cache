<?php

namespace Moon\Cache;

use Moon\Cache\Adapters\AdapterInterface;
use Moon\Cache\Collection\CacheItemCollection;
use Moon\Cache\Collection\CacheItemCollectionInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class CacheItemPool implements CacheItemPoolInterface
{
    /**
     * @var AdapterInterface
     */
    protected $adapterInterface;

    /**
     * List of items waiting to be saved
     *
     * @var CacheItemCollectionInterface $deferredItems
     */
    protected $deferredItems = [];

    /**
     * Set to true when a commit fails
     *
     * @var bool $isSaveDeferredItemsFailed
     */
    protected $isSaveDeferredItemsFailed = false;

    /**
     * CacheItemPool constructor.
     * @param AdapterInterface $adapterInterface
     */
    public function __construct(AdapterInterface $adapterInterface)
    {
        $this->adapterInterface = $adapterInterface;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem($key): CacheItemInterface
    {
        return $this->adapterInterface->getItem($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(array $keys = []): CacheItemCollectionInterface
    {
        return $this->adapterInterface->getItems($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem($key): bool
    {
        return $this->adapterInterface->hasItem($key);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        return $this->adapterInterface->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItem($key): true
    {
        return $this->adapterInterface->deleteItem($key);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys): bool
    {
        return $this->adapterInterface->deleteItems($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function save(CacheItemInterface $item): bool
    {
        return $this->adapterInterface->save($item);
    }

    /**
     * {@inheritdoc}
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        // Do not add if the last try of saving deferred items fails
        if ($this->isSaveDeferredItemsFailed == true) {
            return false;
        }

        // Create CacheItemCollectionInterface and assign it to deferredItems if is the first time
        if (!$this->deferredItems) {
            $this->deferredItems = $this->createCacheItemCollectionInterface();
        }

        // Add to collection and return
        $this->deferredItems->add($item);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): bool
    {
        $isSucceed = $this->adapterInterface->saveItems($this->deferredItems);

        // If the commit fails, save isSaveDeferredItemsFailed as true
        // so that no other saveDeferred can be done as PSR-6 requires
        $this->isSaveDeferredItemsFailed = !$isSucceed;

        return $isSucceed;
    }

    protected function createCacheItemCollectionInterface(array $items = []): CacheItemCollectionInterface
    {
        return new CacheItemCollection($items);
    }
}