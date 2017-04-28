<?php

namespace Moon\Cache\Adapters;

use Closure;
use Moon\Cache\Collection\CacheItemCollection;
use Moon\Cache\Collection\CacheItemCollectionInterface;
use Moon\Cache\Exception\CacheInvalidArgumentException;
use Psr\Cache\CacheItemInterface;

abstract class AbstractAdapter implements AdapterInterface
{
    /**
     * Value used for get by reflection the expiration date in a CacheItem
     * There's no method available from the PSR-6
     *
     * @var string $expirationParameterName
     */
    protected $expirationParameterName = 'expiration';

    /**
     * Create a CacheItemCollection from an array
     *
     * @param array $items
     *
     * @return CacheItemCollectionInterface
     */
    protected function createCacheItemCollection(array $items = []): CacheItemCollectionInterface
    {
        return new CacheItemCollection($items);
    }

    /**
     * Return the expiringDate from an CacheItemObject
     *
     * @param CacheItemInterface $cacheItem
     *
     * @return \DateTimeImmutable
     */
    protected function retrieveExpiringDateFromCacheItem(CacheItemInterface $cacheItem): \DateTimeImmutable
    {
        try {
            $expirationDate = Closure::bind(function (CacheItemInterface $cacheItem, $expirationParameterName) {
                return $cacheItem->$expirationParameterName;
            }, null, $cacheItem)->__invoke($cacheItem, $this->expirationParameterName);

            if ($expirationDate instanceof \DateTime) {
                $expirationDate = \DateTimeImmutable::createFromMutable($expirationDate);
            }

            return $expirationDate;
        } catch (\Exception $e) {
            throw new CacheInvalidArgumentException(
                "The CacheItemInterface object haven't any {$this->expirationParameterName} attribute."
            );
        }
    }

    /**
     * Update the $expirationParameterName
     *
     * @param string $expirationParameterName
     *
     * @return void
     */
    public function setExpirationParameterName(string $expirationParameterName): void
    {
        $this->expirationParameterName = $expirationParameterName;
    }
}