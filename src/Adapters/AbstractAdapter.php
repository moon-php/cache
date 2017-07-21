<?php

declare(strict_types=1);

namespace Moon\Cache\Adapters;

use Closure;
use Moon\Cache\Exception\InvalidArgumentException;
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
     * Return the expiringDate from an CacheItemObject
     *
     * @param CacheItemInterface $cacheItem
     *
     * @return \DateTimeImmutable
     *
     * @throws InvalidArgumentException
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
            throw new InvalidArgumentException(
                "The CacheItemInterface object haven't any {$this->expirationParameterName} attribute.", 0, $e
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