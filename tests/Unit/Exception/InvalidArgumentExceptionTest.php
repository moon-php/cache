<?php

declare(strict_types=1);

use Moon\Cache\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class InvalidArgumentExceptionTest extends TestCase
{
    /**
     * @dataProvider invalidCacheItemDataProvider
     */
    public function testInvalidCacheItemIsProperlySet($invalidCacheItem)
    {
        $exception = new InvalidArgumentException('', $invalidCacheItem);
        $this->assertSame($invalidCacheItem, $exception->getInvalidCacheItem());
    }

    public function invalidCacheItemDataProvider(): array
    {
        return [
            [1],
            ['string'],
            [['an', 'array']],
            [null],
            [$this->prophesize(\Moon\Cache\CacheItem::class)->reveal()]
        ];
    }
}