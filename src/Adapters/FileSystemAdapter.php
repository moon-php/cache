<?php

namespace Moon\Cache\Adapters;

use Moon\Cache\CacheItem;
use Moon\Cache\Collection\CacheItemCollectionInterface;
use Moon\Cache\Exception\CacheItemNotFoundException;
use Psr\Cache\CacheItemInterface;

class FileSystemAdapter extends AbstractAdapter
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
        $this->directory = $directory ?: sys_get_temp_dir() . "/moon-cache";
        // If directory doesn't exists, create it
        !file_exists($this->directory) ?: mkdir($this->directory, 0777, true);
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

        throw new CacheItemNotFoundException();
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(array $keys = []): CacheItemCollectionInterface
    {
        // Create an empty collection
        $cacheItemCollection = $this->createCacheItemCollection();

        // Add to the collection all items found
        // Do not throw CacheItemNotFoundException if item is not found
        foreach ($keys as $key) {
            try {
                $cacheItemCollection->add($this->getItem($key));
            } catch (CacheItemNotFoundException $e) {
                continue;
            }
        }

        return $cacheItemCollection;
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem($key): bool
    {
        return file_exists($this->getFilenameFromKey($key));
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        // Create a RecursiveDirectoryIterator form the directory to clean up
        $directory = new \RecursiveDirectoryIterator("{$this->directory}/{$this->poolName}");

        // Return false if has subdirectories
        if ($directory->hasChildren()) {
            return false;
        }

        // Remove all files
        /** @var \RecursiveDirectoryIterator $file */
        foreach ($directory as $file) {
            // Skip '.' and '..' directories
            if ($file->isDot()) {
                continue;
            }

            // Delete the file
            if ($file->isFile()) {
                unlink($file->getFilename());
            }
        }

        return rmdir($directory->getPath());
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItem(string $key): bool
    {
        return unlink($this->getFilenameFromKey($key));
    }

    /**
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
     */
    public function save(CacheItemInterface $item): bool
    {
        $content = serialize($item->get()) . PHP_EOL . serialize($this->retrieveExpiringDateFromCacheItem($item));

        return (bool)file_put_contents($this->getFilenameFromKey($item->getKey()), $content);
    }

    /**
     * {@inheritdoc}
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
     */
    protected function createCacheItemFromFile(string $path): CacheItemInterface
    {
        // Get Key, Value and $expireDate from the file
        $key = end(explode('/', $path));
        list($value, $expireDate) = explode(PHP_EOL, file_get_contents($path));

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
    private function getFilenameFromKey($key)
    {
        return "{$this->directory}/{$this->poolName}.{$this->keyEncode($key)}";
    }
}