<?php

declare(strict_types=1);

namespace Moon\Cache\Exception;

use Moon\Cache\CacheItem;
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
            [$this->prophesize(CacheItem::class)->reveal()],
        ];
    }
}
