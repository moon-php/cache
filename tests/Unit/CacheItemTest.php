<?php

declare(strict_types=1);

namespace Moon\Cache;

use Moon\Cache\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CacheItemTest extends TestCase
{
    /**
     * @dataProvider invalidLengthKeyDataProvider
     */
    public function testInvalidLegthKeyThrowAnException($key)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The key '$key' must be length from 1 up to 64 characters");
        new CacheItem($key, '');
    }

    /**
     * @dataProvider invalidCharKeyDataProvider
     */
    public function testInvalidCharsThrowAnException($key)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The key '$key' contains invalid characters. Supported characters are A-Z a-z 0-9 _ and .");
        new CacheItem($key, '');
    }

    public function testThatValueAndKeyAreProperlySet()
    {
        $item = new CacheItem('a_key', 'a value');
        $this->assertSame($item->getKey(), 'a_key');
        $this->assertSame($item->get(), 'a value');
    }

    public function testThatSetMethodReturnAnObjectWithADifferentValue()
    {
        $item = new CacheItem('a_key', 'a value');
        $itemTwo = $item->set('another_value');
        $this->assertSame($itemTwo->get(), 'another_value');
    }

    /**
     * @dataProvider expirationDateDataProvider
     */
    public function testDefaultExpirationDateIsProperlySet($expectedDate, $date)
    {
        $reflectionExpiration = new \ReflectionProperty(CacheItem::class, 'expiration');
        $reflectionExpiration->setAccessible(true);
        $item = new CacheItem('key', '', $date);
        $returnedDate = $reflectionExpiration->getValue($item);
        $this->assertEquals($expectedDate->getTimestamp(), $returnedDate->getTimestamp(), '', 3);
    }

    /**
     * @dataProvider expiresAtDataProvider
     */
    public function testExpiresAt($expectedDate, $date)
    {
        $reflectionExpiration = new \ReflectionProperty(CacheItem::class, 'expiration');
        $reflectionExpiration->setAccessible(true);
        $item = new CacheItem('key', '');
        $item->expiresAt($date);
        $returnedDate = $reflectionExpiration->getValue($item);
        $this->assertEquals($expectedDate->getTimestamp(), $returnedDate->getTimestamp(), '', 3);
    }

    public function testIsHit()
    {
        $item = new CacheItem('key', '');
        $this->assertTrue($item->isHit());
        $item = new CacheItem('key', '', new \DateTimeImmutable('-1 day'));
        $this->assertFalse($item->isHit());
    }

    /**
     * @dataProvider invalidExpiresAtValuesDataProvider
     */
    public function testExpiresAtThrowInvalidArgumentExcpetion($invalidDate)
    {
        $this->expectException(InvalidArgumentException::class);

        $item = new CacheItem('key', '');
        $item->expiresAt($invalidDate);
    }

    /**
     * @dataProvider valueAcceptedByExpiresAfterDataProvider
     */
    public function testExpiresAfter($expectedDate, $date)
    {
        $reflectionExpiration = new \ReflectionProperty(CacheItem::class, 'expiration');
        $reflectionExpiration->setAccessible(true);
        $item = new CacheItem('key', '');
        $item->expiresAfter($date);
        $returnedDate = $reflectionExpiration->getValue($item);
        $this->assertEquals($expectedDate->getTimestamp(), $returnedDate->getTimestamp(), '', 3);
    }

    /**
     * @dataProvider invalidExpiresAfterValuesDataProvider
     */
    public function testExpiresAfterThrowInvalidArgumentExcpetion($invalidDate)
    {
        $this->expectException(InvalidArgumentException::class);

        $item = new CacheItem('key', '');
        $item->expiresAfter($invalidDate);
    }

    public function valueAcceptedByExpiresAfterDataProvider()
    {
        return [
            [new \DateTimeImmutable('+10 seconds'), 10],
            [new \DateTimeImmutable('+1 second'), new \DateInterval('PT1S')],
            [new \DateTimeImmutable(CacheItem::DEFAULT_EXPIRATION), null],
        ];
    }

    public function invalidExpiresAfterValuesDataProvider()
    {
        return [
            [[1, 2, 3]],
            [new \DateTimeImmutable()],
            [CacheItem::DEFAULT_EXPIRATION],
            [new \SplStack()],
        ];
    }

    public function invalidExpiresAtValuesDataProvider()
    {
        return [
            [[1, 2, 3]],
            [CacheItem::DEFAULT_EXPIRATION],
            [new \SplStack()],
        ];
    }

    public function expiresAtDataProvider()
    {
        return [
            [new \DateTimeImmutable('now'), new \DateTimeImmutable('now')],
            [new \DateTimeImmutable('+1 day'), new \DateTimeImmutable('+1 day')],
            [new \DateTimeImmutable(CacheItem::DEFAULT_EXPIRATION), null],
        ];
    }

    public function expirationDateDataProvider()
    {
        return [
            [new \DateTimeImmutable('now'), new \DateTimeImmutable('now')],
            [new \DateTimeImmutable('+1 day'), new \DateTimeImmutable('+1 day')],
            [new \DateTimeImmutable(CacheItem::DEFAULT_EXPIRATION), null],
        ];
    }

    public function invalidLengthKeyDataProvider()
    {
        return [[''], ['qqedjspjasopdjopasjqqedjspjasopdjopasjqqedjspjasopdjopasjqqedjspjasopdjopasjqqedjspjasopdjopasjqqedjspjasopdjopasjqqedjs']];
    }

    public function invalidCharKeyDataProvider()
    {
        return [['  '], ['ès + àò']];
    }
}
