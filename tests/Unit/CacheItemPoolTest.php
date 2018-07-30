<?php

declare(strict_types=1);

namespace Moon\Cache;

use Moon\Cache\Adapters\AdapterInterface;
use Moon\Cache\Exception\InvalidArgumentException;
use Moon\Cache\Exception\ItemNotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;

class CacheItemPoolTest extends TestCase
{
    public function testAdapterIsSet()
    {
        $adapter = $this->prophesize(AdapterInterface::class)->reveal();
        $pool = new CacheItemPool($adapter);
        $reflectionProperty = new \ReflectionProperty(CacheItemPool::class, 'adapter');
        $reflectionProperty->setAccessible(true);
        $this->assertSame($adapter, $reflectionProperty->getValue($pool));
    }

    public function testItemIsProperlyRetrivedFromPool()
    {
        $adapter = $this->prophesize(AdapterInterface::class);
        $cacheItem = $this->prophesize(CacheItemInterface::class)->reveal();
        $adapter->getItem('key')->shouldBeCalled(1)->willReturn($cacheItem);
        $pool = new CacheItemPool($adapter->reveal());
        $this->assertSame($cacheItem, $pool->getItem('key'));
    }

    /**
     * @dataProvider invalidLengthKeyDataProvider
     */
    public function testGetItemByInvalidKeyLegthThrowInvalidArgumentExcpetion($key)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The key '$key' must be length from 1 up to 64 characters");
        $adapter = $this->prophesize(AdapterInterface::class)->reveal();
        $pool = new CacheItemPool($adapter);
        $pool->getItem($key);
    }

    /**
     * @dataProvider invalidCharKeyDataProvider
     */
    public function testGetItemByInvalidKeyCharsThrowInvalidArgumentExcpetion($key)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The key '$key' contains invalid characters. Supported characters are A-Z a-z 0-9 _ and .");
        $adapter = $this->prophesize(AdapterInterface::class)->reveal();
        $pool = new CacheItemPool($adapter);
        $pool->getItem($key);
    }

    public function testItemNotFoundExceptionIsThrownGettingAnItemNotSaved()
    {
        $this->expectException(ItemNotFoundException::class);
        $adapter = $this->prophesize(AdapterInterface::class);
        $adapter->getItem('key')->willThrow(ItemNotFoundException::class);
        $pool = new CacheItemPool($adapter->reveal());
        $pool->getItem('key');
    }

    public function testGetItemsReturnArray()
    {
        $inArray = ['an', 'input', 'array'];
        $outArray = ['an', 'output', 'array'];
        $adapter = $this->prophesize(AdapterInterface::class);
        $adapter->getItems($inArray)->shouldBeCalled(1)->willReturn($outArray);
        $pool = new CacheItemPool($adapter->reveal());
        $this->assertSame($outArray, $pool->getItems($inArray));
    }

    /**
     * @dataProvider invalidLengthKeyDataProvider
     */
    public function testGetItemsByInvalidKeyLegthThrowInvalidArgumentException($key)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The key '$key' must be length from 1 up to 64 characters");
        $adapter = $this->prophesize(AdapterInterface::class)->reveal();
        $pool = new CacheItemPool($adapter);
        $pool->getItems([$key]);
    }

    /**
     * @dataProvider invalidCharKeyDataProvider
     */
    public function testGetItemsByInvalidKeyCharsThrowInvalidArgumentExcpetion($key)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The key '$key' contains invalid characters. Supported characters are A-Z a-z 0-9 _ and .");
        $adapter = $this->prophesize(AdapterInterface::class)->reveal();
        $pool = new CacheItemPool($adapter);
        $pool->getItems([$key]);
    }

    public function testHasItems()
    {
        $adapter = $this->prophesize(AdapterInterface::class);
        $adapter->hasItem('existingItem')->shouldBeCalled(1)->willReturn(true);
        $adapter->hasItem('notExistingItem')->shouldBeCalled(1)->willReturn(false);
        $pool = new CacheItemPool($adapter->reveal());
        $this->assertTrue($pool->hasItem('existingItem'));
        $this->assertFalse($pool->hasItem('notExistingItem'));
    }

    /**
     * @dataProvider invalidLengthKeyDataProvider
     */
    public function testHasItemByInvalidKeyLegthThrowInvalidArgumentExcpetion($key)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The key '$key' must be length from 1 up to 64 characters");
        $adapter = $this->prophesize(AdapterInterface::class)->reveal();
        $pool = new CacheItemPool($adapter);
        $pool->hasItem($key);
    }

    /**
     * @dataProvider invalidCharKeyDataProvider
     */
    public function testHasItemByInvalidKeyCharsThrowInvalidArgumentExcpetion($key)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The key '$key' contains invalid characters. Supported characters are A-Z a-z 0-9 _ and .");
        $adapter = $this->prophesize(AdapterInterface::class)->reveal();
        $pool = new CacheItemPool($adapter);
        $pool->hasItem($key);
    }

    public function testClearMethod()
    {
        $adapter = $this->prophesize(AdapterInterface::class);
        $adapter->clear()->shouldBeCalled(1);
        $pool = new CacheItemPool($adapter->reveal());
        $pool->clear();
    }

    public function testDeleteItem()
    {
        $adapter = $this->prophesize(AdapterInterface::class);
        $adapter->deleteItem('existingItem')->shouldBeCalled(1)->willReturn(true);
        $adapter->deleteItem('notExistingItem')->shouldBeCalled(1)->willReturn(false);
        $pool = new CacheItemPool($adapter->reveal());
        $this->assertTrue($pool->deleteItem('existingItem'));
        $this->assertFalse($pool->deleteItem('notExistingItem'));
    }

    /**
     * @dataProvider invalidLengthKeyDataProvider
     */
    public function testDeleteItemByInvalidKeyLegthThrowInvalidArgumentExcpetion($key)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The key '$key' must be length from 1 up to 64 characters");
        $adapter = $this->prophesize(AdapterInterface::class)->reveal();
        $pool = new CacheItemPool($adapter);
        $pool->deleteItem($key);
    }

    /**
     * @dataProvider invalidCharKeyDataProvider
     */
    public function testDeleteItemByInvalidKeyCharsThrowInvalidArgumentExcpetion($key)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The key '$key' contains invalid characters. Supported characters are A-Z a-z 0-9 _ and .");
        $adapter = $this->prophesize(AdapterInterface::class)->reveal();
        $pool = new CacheItemPool($adapter);
        $pool->deleteItem($key);
    }

    public function testDeleteItems()
    {
        $adapter = $this->prophesize(AdapterInterface::class);
        $adapter->deleteItems(['existingItem'])->shouldBeCalled(1)->willReturn(true);
        $adapter->deleteItems(['notExistingItem'])->shouldBeCalled(1)->willReturn(false);
        $pool = new CacheItemPool($adapter->reveal());
        $this->assertTrue($pool->deleteItems(['existingItem']));
        $this->assertFalse($pool->deleteItems(['notExistingItem']));
    }

    /**
     * @dataProvider invalidLengthKeyDataProvider
     */
    public function testDeleteItemsByInvalidKeyLegthThrowInvalidArgumentExcpetion($key)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The key '$key' must be length from 1 up to 64 characters");
        $adapter = $this->prophesize(AdapterInterface::class)->reveal();
        $pool = new CacheItemPool($adapter);
        $pool->deleteItems([$key]);
    }

    /**
     * @dataProvider invalidCharKeyDataProvider
     */
    public function testDeleteItemsByInvalidKeyCharsThrowInvalidArgumentExcpetion($key)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The key '$key' contains invalid characters. Supported characters are A-Z a-z 0-9 _ and .");
        $adapter = $this->prophesize(AdapterInterface::class)->reveal();
        $pool = new CacheItemPool($adapter);
        $pool->deleteItems([$key]);
    }

    public function testSaveItem()
    {
        $adapter = $this->prophesize(AdapterInterface::class);
        $goodCacheItem = $this->prophesize(CacheItemInterface::class)->reveal();
        $badCacheItem = $this->prophesize(CacheItemInterface::class)->reveal();
        $adapter->save($goodCacheItem)->shouldBeCalled(1)->willReturn(true);
        $adapter->save($badCacheItem)->shouldBeCalled(1)->willReturn(false);
        $pool = new CacheItemPool($adapter->reveal());
        $this->assertTrue($pool->save($goodCacheItem));
        $this->assertFalse($pool->save($badCacheItem));
    }

    public function testSaveDeferredReturnFalseForSaveFail()
    {
        $adapter = $this->prophesize(AdapterInterface::class)->reveal();
        $cacheItem = $this->prophesize(CacheItemInterface::class)->reveal();
        $pool = new CacheItemPool($adapter);
        $reflectionProperty = new \ReflectionProperty($pool, 'isSaveDeferredItemsFailed');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($pool, true);
        $this->assertFalse($pool->saveDeferred($cacheItem));
    }

    public function testSaveDeferredAddTheNewItemToTheDeferredItemsList()
    {
        $adapter = $this->prophesize(AdapterInterface::class)->reveal();
        $cacheItem = $this->prophesize(CacheItemInterface::class)->reveal();
        $pool = new CacheItemPool($adapter);
        $this->assertTrue($pool->saveDeferred($cacheItem));
        $reflectionProperty = new \ReflectionProperty($pool, 'deferredItems');
        $reflectionProperty->setAccessible(true);
        $this->assertSame([$cacheItem], $reflectionProperty->getValue($pool));
    }

    public function testCommitFails()
    {
        $cacheItem = $this->prophesize(CacheItemInterface::class)->reveal();
        $adapter = $this->prophesize(AdapterInterface::class);
        $adapter->saveItems([$cacheItem])->shouldBeCalled(1)->willReturn(false);
        $pool = new CacheItemPool($adapter->reveal());
        $reflectionPropertyDeferredItems = new \ReflectionProperty(CacheItemPool::class, 'deferredItems');
        $reflectionPropertyDeferredItems->setAccessible(true);
        $reflectionPropertyDeferredItems->setValue($pool, [$cacheItem]);
        $this->assertFalse($pool->commit());
        $reflectionPropertyIsSaveDeferredItemsFailed = new \ReflectionProperty($pool, 'isSaveDeferredItemsFailed');
        $reflectionPropertyIsSaveDeferredItemsFailed->setAccessible(true);
        $this->assertSame([$cacheItem], $reflectionPropertyDeferredItems->getValue($pool));
        $this->assertTrue($reflectionPropertyIsSaveDeferredItemsFailed->getValue($pool));
    }

    public function testCommitSucceed()
    {
        $cacheItem = $this->prophesize(CacheItemInterface::class)->reveal();
        $adapter = $this->prophesize(AdapterInterface::class);
        $adapter->saveItems([$cacheItem])->shouldBeCalled(1)->willReturn(true);
        $pool = new CacheItemPool($adapter->reveal());
        $reflectionPropertyDeferredItems = new \ReflectionProperty(CacheItemPool::class, 'deferredItems');
        $reflectionPropertyDeferredItems->setAccessible(true);
        $reflectionPropertyDeferredItems->setValue($pool, [$cacheItem]);
        $this->assertTrue($pool->commit());
        $reflectionPropertyIsSaveDeferredItemsFailed = new \ReflectionProperty($pool, 'isSaveDeferredItemsFailed');
        $reflectionPropertyIsSaveDeferredItemsFailed->setAccessible(true);
        $this->assertSame([], $reflectionPropertyDeferredItems->getValue($pool));
        $this->assertFalse($reflectionPropertyIsSaveDeferredItemsFailed->getValue($pool));
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
