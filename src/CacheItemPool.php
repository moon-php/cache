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
     * List of items waiting to be saved.
     *
     * @var array
     */
    protected $deferredItems = [];

    /**
     * Set to true when a commit fails.
     *
     * @var bool
     */
    protected $isSaveDeferredItemsFailed = false;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public function getItem($key): CacheItemInterface
    {
        $this->validateKey($key);

        return $this->adapter->getItem($key);
    }

    public function getItems(array $keys = []): array
    {
        foreach ($keys as $key) {
            $this->validateKey($key);
        }

        return $this->adapter->getItems($keys);
    }

    public function hasItem($key): bool
    {
        $this->validateKey($key);

        return $this->adapter->hasItem($key);
    }

    public function clear(): bool
    {
        return $this->adapter->clear();
    }

    public function deleteItem($key): bool
    {
        $this->validateKey($key);

        return $this->adapter->deleteItem($key);
    }

    public function deleteItems(array $keys): bool
    {
        foreach ($keys as $key) {
            $this->validateKey($key);
        }

        return $this->adapter->deleteItems($keys);
    }

    public function save(CacheItemInterface $item): bool
    {
        return $this->adapter->save($item);
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        // Do not add if the last try of saving deferred items fails
        if (true === $this->isSaveDeferredItemsFailed) {
            return false;
        }

        $this->deferredItems[] = $item;

        return true;
    }

    public function commit(): bool
    {
        $isSucceed = $this->adapter->saveItems($this->deferredItems);

        if (true === $isSucceed) {
            $this->deferredItems = [];

            return true;
        }

        // If the commit fails, save isSaveDeferredItemsFailed as true
        // so that no other saveDeferred can be done as PSR-6 requires
        $this->isSaveDeferredItemsFailed = true;

        return false;
    }
}
