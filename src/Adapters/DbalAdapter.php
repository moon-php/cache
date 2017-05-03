<?php

namespace Moon\Cache\Adapters;

use Doctrine\DBAL\Connection;
use Moon\Cache\CacheItem;
use Moon\Cache\Collection\CacheItemCollectionInterface;
use Moon\Cache\Exception\CacheItemNotFoundException;
use Moon\Cache\Exception\CachePersistenceException;
use Psr\Cache\CacheItemInterface;

class DbalAdapter extends AbstractAdapter
{
    /**
     * @var Connection $connection
     */
    private $connection;


    /**
     * @var string $poolName
     */
    private $poolName;

    /**
     * @var array
     */
    private $tableOptions = [
        'tableName' => 'moon_cache',
        'idColumn' => 'id',
        'keyColumn' => 'key',
        'valueColumn' => 'value',
        'poolNameColumn' => 'pool_name',
        'expirationColumn' => 'expires_at'
    ];

    /**
     * Expiration format for database date
     *
     * @var string
     */
    private $expirationDateFormat = 'Y-m-d H:i:s';

    /**
     * FileSystemAdapter constructor.
     * @param string $poolName
     * @param Connection $connection
     * @param array $tableOptions
     * @param null $expirationDateFormat
     */
    public function __construct(string $poolName, Connection $connection, array $tableOptions = [], $expirationDateFormat = null)
    {
        $this->poolName = $poolName;
        $this->connection = $connection;
        $this->tableOptions = array_merge($this->tableOptions, $tableOptions);
        $this->expirationDateFormat = $expirationDateFormat ?: $this->expirationDateFormat;

        try {
            $checkValidFormat = \DateTimeImmutable::createFromFormat($this->expirationDateFormat, 'now');
            unset($checkValidFormat);
        } catch (\Exception $e) {
            throw new CacheItemNotFoundException('Invalid expiration column format', 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(array $keys = []): CacheItemCollectionInterface
    {
        $stmt = $this->connection->createQueryBuilder()
            ->select('*')
            ->from("`{$this->tableOptions['tableName']}`")
            ->where("`{$this->tableOptions['keyColumn']}` IN (:keys)")
            ->setParameter(':keys', $keys, Connection::PARAM_STR_ARRAY)
            ->execute();

        $cacheItemCollection = $this->createCacheItemCollection();

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $cacheItemCollection->add($this->createCacheItemFromRow($row));
        }

        return $cacheItemCollection;
    }

    /**
     * Create a CacheItemInterface object from a row
     *
     * @param array $row
     *
     * @return CacheItemInterface
     */
    protected function createCacheItemFromRow(array $row): CacheItemInterface
    {
        // Create a new CacheItem
        return new CacheItem(
            $row[$this->tableOptions['keyColumn']],
            $row[$this->tableOptions['valueColumn']],
            new \DateTimeImmutable($row[$this->tableOptions['expirationColumn']])
        );
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem($key): bool
    {
        try {
            $this->getItem($key);
        } catch (CacheItemNotFoundException $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $key): CacheItemInterface
    {
        try {
            $row = $this->connection->createQueryBuilder()
                ->select('*')
                ->from("`{$this->tableOptions['tableName']}`")
                ->where("`{$this->tableOptions['keyColumn']}` = :key")
                ->setParameter(':key', $key, \PDO::PARAM_STR)
                ->setFirstResult(0)
                ->execute()
                ->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new CachePersistenceException($e->getMessage(), 0, $e);
        }

        if (empty($row)) {
            throw new CacheItemNotFoundException();
        }

        return $this->createCacheItemFromRow($row);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        try {
            return (bool)$this->connection->createQueryBuilder()
                ->delete("`{$this->tableOptions['tableName']}`")
                ->where("`{$this->tableOptions['poolNameColumn']}` = :poolName")
                ->setParameter(':poolName', $this->poolName, \PDO::PARAM_STR)
                ->execute();

        } catch (\Exception $e) {
            throw new CachePersistenceException($e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItem(string $key): bool
    {
        try {
            return (bool)$this->connection->createQueryBuilder()
                ->delete("`{$this->tableOptions['tableName']}`")
                ->where("`{$this->tableOptions['keyColumn']}` = :key")
                ->setParameter(':key', $key, \PDO::PARAM_STR)
                ->execute();

        } catch (\Exception $e) {
            throw new CachePersistenceException($e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys): bool
    {
        try {
            $deletedRows = $this->connection->createQueryBuilder()
                ->delete("`{$this->tableOptions['tableName']}`")
                ->where("`{$this->tableOptions['keyColumn']}` IN (:keys)")
                ->setParameter(':keys', $keys, Connection::PARAM_STR_ARRAY)
                ->execute();

            return (bool)$deletedRows;
        } catch (\Exception $e) {
            throw new CachePersistenceException($e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function saveItems(CacheItemCollectionInterface $items): bool
    {
        $this->connection->beginTransaction();
        try {
            foreach ($items as $item) {
                if (!$this->save($item)) {
                    $this->connection->rollBack();

                    return false;
                }
            }
            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();

            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function save(CacheItemInterface $item): bool
    {
        $data = [
            "`{$this->tableOptions['keyColumn']}`" => $item->getKey(),
            "`{$this->tableOptions['valueColumn']}`" => serialize($item->get()),
            "`{$this->tableOptions['poolNameColumn']}`" => $this->poolName,
            "`{$this->tableOptions['expirationColumn']}`" => $this->retrieveExpiringDateFromCacheItem($item)->format($this->expirationDateFormat)
        ];

        try {
            return (bool)$this->connection->insert("`{$this->tableOptions['tableName']}`", $data);
        } catch (\Exception $e) {
            throw new CachePersistenceException($e->getMessage(), 0, $e);
        }
    }
}