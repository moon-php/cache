<?php

declare(strict_types=1);

namespace Moon\Cache\Adapter;

use Moon\Cache\Adapters\MemoryAdapter;
use Moon\Cache\CacheItem;
use Moon\Cache\Exception\InvalidArgumentException;
use Moon\Cache\Exception\ItemNotFoundException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class MemoryAdapterTest extends TestCase
{
    public function testGetItem()
    {
        $itemCache = $this->prophesize(CacheItem::class)->reveal();
        $adapter = new MemoryAdapter();
        $reflectionProperty = new ReflectionProperty(MemoryAdapter::class, 'items');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($adapter, ['a key' => $itemCache]);

        $this->assertSame($itemCache, $adapter->getItem('a key'));
    }

    public function testGetItemThrowNotFoundItem()
    {
        $this->expectException(ItemNotFoundException::class);
        $adapter = new MemoryAdapter();
        $adapter->getItem('a key');
    }

    public function testGetItems()
    {
        $itemCacheOne = $this->prophesize(CacheItem::class)->reveal();
        $itemCacheTwo = $this->prophesize(CacheItem::class)->reveal();
        $itemCacheThree = $this->prophesize(CacheItem::class)->reveal();
        $items = ['one' => $itemCacheOne, 'two' => $itemCacheTwo, 'three' => $itemCacheThree];

        $adapter = new MemoryAdapter();
        $reflectionProperty = new ReflectionProperty(MemoryAdapter::class, 'items');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($adapter, $items);

        $this->assertSame([$itemCacheOne, $itemCacheThree], $adapter->getItems(['one', 'three']));
    }

    public function testGetItemsReturnEmptyArray()
    {
        $adapter = new MemoryAdapter();
        $this->assertSame([], $adapter->getItems(['keyOne', 'keyTwo']));
    }

    public function testHasItem()
    {
        $itemCache = $this->prophesize(CacheItem::class)->reveal();
        $adapter = new MemoryAdapter();
        $reflectionProperty = new ReflectionProperty(MemoryAdapter::class, 'items');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($adapter, ['one' => $itemCache]);

        $this->assertTrue($adapter->hasItem('one'));
        $this->assertFalse($adapter->hasItem('two'));
    }

    public function testClearItem()
    {
        $itemCache = $this->prophesize(CacheItem::class)->reveal();
        $adapter = new MemoryAdapter();
        $reflectionProperty = new ReflectionProperty(MemoryAdapter::class, 'items');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($adapter, ['one' => $itemCache]);
        $adapter->clear();
        $this->assertSame([], $reflectionProperty->getValue($adapter));
    }

    public function testDeleteItem()
    {
        $itemCacheOne = $this->prophesize(CacheItem::class)->reveal();
        $itemCacheTwo = $this->prophesize(CacheItem::class)->reveal();
        $itemCacheThree = $this->prophesize(CacheItem::class)->reveal();
        $items = ['one' => $itemCacheOne, 'two' => $itemCacheTwo, 'three' => $itemCacheThree];

        $adapter = new MemoryAdapter();
        $reflectionProperty = new ReflectionProperty(MemoryAdapter::class, 'items');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($adapter, $items);

        $this->assertTrue($adapter->deleteItem('one'));
        $this->assertSame(['two' => $itemCacheTwo, 'three' => $itemCacheThree], $reflectionProperty->getValue($adapter));
        $this->assertFalse($adapter->deleteItem('one'));
    }

    public function testDeleteItems()
    {
        $itemCacheOne = $this->prophesize(CacheItem::class)->reveal();
        $itemCacheTwo = $this->prophesize(CacheItem::class)->reveal();
        $itemCacheThree = $this->prophesize(CacheItem::class)->reveal();
        $itemCacheFour = $this->prophesize(CacheItem::class)->reveal();
        $items = ['one' => $itemCacheOne, 'two' => $itemCacheTwo, 'three' => $itemCacheThree, 'four' => $itemCacheFour];

        $adapter = new MemoryAdapter();
        $reflectionProperty = new ReflectionProperty(MemoryAdapter::class, 'items');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($adapter, $items);

        $this->assertTrue($adapter->deleteItems(['two', 'three']));
        $this->assertSame(['one' => $itemCacheOne, 'four' => $itemCacheFour], $reflectionProperty->getValue($adapter));
        $this->assertFalse($adapter->deleteItems(['four', 'two']));
        $this->assertSame(['one' => $itemCacheOne, 'four' => $itemCacheFour], $reflectionProperty->getValue($adapter));
    }

    public function testSaveItem()
    {
        $itemCacheOne = $this->prophesize(CacheItem::class)->reveal();
        $itemCacheTwo = $this->prophesize(CacheItem::class);
        $itemCacheTwo->getKey()->shouldBeCalled(1)->willReturn('two');
        $itemCacheTwo = $itemCacheTwo->reveal();
        $itemCacheThree = $this->prophesize(CacheItem::class)->reveal();
        $items = ['one' => $itemCacheOne, 'three' => $itemCacheThree];

        $adapter = new MemoryAdapter();
        $reflectionProperty = new ReflectionProperty(MemoryAdapter::class, 'items');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($adapter, $items);

        $this->assertTrue($adapter->save($itemCacheTwo));
        $this->assertSame(['one' => $itemCacheOne, 'three' => $itemCacheThree, 'two' => $itemCacheTwo], $reflectionProperty->getValue($adapter));
    }

    public function testSaveItems()
    {
        $itemCacheOne = $this->prophesize(CacheItem::class);
        $itemCacheOne->getKey()->shouldBeCalled(1)->willReturn('one');
        $itemCacheOne = $itemCacheOne->reveal();
        $itemCacheTwo = $this->prophesize(CacheItem::class);
        $itemCacheTwo->getKey()->shouldBeCalled(1)->willReturn('two');
        $itemCacheTwo = $itemCacheTwo->reveal();
        $itemCacheThree = $this->prophesize(CacheItem::class)->reveal();
        $items = ['three' => $itemCacheThree];

        $adapter = new MemoryAdapter();
        $reflectionProperty = new ReflectionProperty(MemoryAdapter::class, 'items');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($adapter, $items);

        $this->assertTrue($adapter->saveItems([$itemCacheOne, $itemCacheTwo]));
        $this->assertSame(['three' => $itemCacheThree, 'one' => $itemCacheOne, 'two' => $itemCacheTwo], $reflectionProperty->getValue($adapter));
    }

    public function testSaveItemsThorwInvalidArgumentException()
    {
        $this->expectException(InvalidArgumentException::class);
        $adapter = new MemoryAdapter();
        $adapter->saveItems(['invalid cache item']);
    }
}
