<?php

declare(strict_types=1);

namespace Moon\Cache\Adapters;

use Moon\Cache\CacheItem;
use Moon\Cache\Collection\CacheItemCollectionInterface;
use Moon\Cache\Exception\InvalidArgumentException;
use Moon\Cache\Exception\ItemNotFoundException;
use Psr\Cache\CacheItemInterface;

class FilesystemAdapter extends AbstractAdapter
{
    /**
     * Path to pool directory
     *
     * @var string $directory
     */
    protected $directory;

    /**
     * @var string
     */
    private $poolName;

    /**
     * FileSystemAdapter constructor.
     * @param string $poolName
     * @param null|string $directory
     */
    public function __construct(string $poolName, string $directory = null)
    {
        $this->poolName = $poolName;
        $this->directory = $directory ? "$directory/{$this->poolName}" : sys_get_temp_dir() . "/moon-cache/{$this->poolName}";
        // If directory doesn't exists, create it
        is_dir($this->directory) ?: mkdir($this->directory, 0777, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $key): CacheItemInterface
    {
        $filename = $this->getFilenameFromKey($key);

        if (file_exists($filename)) {
            return $this->createCacheItemFromFile($filename);
        }

        throw new ItemNotFoundException();
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(array $keys = []): CacheItemCollectionInterface
    {
        // Create an empty collection
        $cacheItemCollection = $this->createCacheItemCollection();

        // Add to the collection all items found
        // Do not throw ItemNotFoundException if item is not found
        foreach ($keys as $key) {
            try {
                $cacheItemCollection->add($this->getItem($key));
            } catch (ItemNotFoundException $e) {
                continue;
            }
        }

        return $cacheItemCollection;
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem(string $key): bool
    {
        return file_exists($this->getFilenameFromKey($key));
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        // Create a RecursiveDirectoryIterator form the directory to clean up
        $directory = new \RecursiveDirectoryIterator($this->directory);

        // Return false if has subdirectories
        /** @var \SplFileInfo $file */
        foreach ($directory as $element) {
            // Skip '.' and '..' directories
            if (in_array($element->getFilename(), ['.', '..'], true)) {
                continue;
            }

            if ($element->isDir()) {
                return false;
            }
        }

        // Remove all files
        /** @var \SplFileInfo $element */
        foreach ($directory as $element) {
            // Skip '.' and '..' directories
            if (in_array($element->getFilename(), ['.', '..'], true)) {
                continue;
            }

            // Delete the file
            if ($element->isFile()) {
                unlink($element->getPathname());
            }
        }

        return rmdir($directory->getPath());
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItem(string $key): bool
    {
        $filename = $this->getFilenameFromKey($key);

        if (!file_exists($filename)) {

            return false;
        }

        return unlink($filename);
    }

    /**
     * THIS IS NOT TRANSACTION-SAFE
     * {@inheritdoc}
     */
    public function deleteItems(array $keys): bool
    {
        foreach ($keys as $key) {
            if (!$this->deleteItem($key)) {

                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    public function save(CacheItemInterface $item): bool
    {
        $content = serialize($item->get()) . PHP_EOL . serialize($this->retrieveExpiringDateFromCacheItem($item));

        return (bool)file_put_contents($this->getFilenameFromKey($item->getKey()), $content);
    }

    /**
     * THIS IS NOT TRANSACTION-SAFE
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     */
    public function saveItems(CacheItemCollectionInterface $items): bool
    {
        foreach ($items as $item) {
            if (!$this->save($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create a CacheItemInterface object from a cached file
     *
     * @param string $path
     *
     * @return CacheItemInterface
     *
     * @throws InvalidArgumentException
     */
    protected function createCacheItemFromFile(string $path): CacheItemInterface
    {
        // Get Key, Value and $expireDate from the file
        $parts = explode('/', $path);
        $key = end($parts);
        [$value, $expireDate] = explode(PHP_EOL, file_get_contents($path));

        // Create a new CacheItem
        return new CacheItem($this->keyDecode($key), unserialize($value), unserialize($expireDate));
    }

    /**
     * Encode a key
     *
     * @param string $key
     *
     * @return string
     */
    protected function keyEncode(string $key): string
    {
        return base64_encode($key);
    }

    /**
     * Decode a key
     *
     * @param string $key
     *
     * @return string
     */
    protected function keyDecode(string $key): string
    {
        return base64_decode($key);
    }

    /**
     * Get the filename from a given key
     *
     * @param $key
     *
     * @return string
     */
    private function getFilenameFromKey($key): string
    {
        return "{$this->directory}/{$this->keyEncode($key)}";
    }
}