<?php

declare(strict_types=1);

namespace Moon\Cache;

use Moon\Cache\Adapters\AdapterInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class CacheItemPool implements CacheItemPoolInterface
{
    use KeyValidator;

    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * List of items waiting to be saved
     *
     * @var array $deferredItems
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
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem($key): CacheItemInterface
    {
        $this->validateKey($key);

        return $this->adapter->getItem($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(array $keys = []): array
    {
        foreach ($keys as $key) {
            $this->validateKey($key);
        }

        return $this->adapter->getItems($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem($key): bool
    {
        $this->validateKey($key);

        return $this->adapter->hasItem($key);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        return $this->adapter->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItem($key): bool
    {
        $this->validateKey($key);

        return $this->adapter->deleteItem($key);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys): bool
    {
        foreach ($keys as $key) {
            $this->validateKey($key);
        }

        return $this->adapter->deleteItems($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function save(CacheItemInterface $item): bool
    {
        return $this->adapter->save($item);
    }

    /**
     * {@inheritdoc}
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        // Do not add if the last try of saving deferred items fails
        if ($this->isSaveDeferredItemsFailed === true) {
            return false;
        }

        // Create array and assign it to deferredItems if is the first time
        if (!$this->deferredItems) {
            $this->deferredItems = [];
        }

        // Add to array and return
        $this->deferredItems->add($item);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): bool
    {
        $isSucceed = $this->adapter->saveItems($this->deferredItems);

        // If the commit fails, save isSaveDeferredItemsFailed as true
        // so that no other saveDeferred can be done as PSR-6 requires
        $this->isSaveDeferredItemsFailed = !$isSucceed;

        return $isSucceed;
    }
}