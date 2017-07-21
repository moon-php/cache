<?php

declare(strict_types=1);

namespace Moon\Cache\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

class CreateTableHelper
{
    /**
     * Default table values for caching system
     *
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
     * Create a table for cache
     *
     * @param Connection $connection
     * @param array $tableOptions
     *
     * @return void
     *
     * @throws DBALException
     */
    public function generate(Connection $connection, array $tableOptions = []): void
    {
        // Merge default options with no-default
        $this->tableOptions = array_merge($this->tableOptions, $tableOptions);

        // Build query for create table
        $schema = $connection->getSchemaManager()->createSchema();
        $myTable = $schema->createTable("`{$this->tableOptions['tableName']}`");
        $myTable->addColumn("`{$this->tableOptions['idColumn']}`", 'bigint', ['unsigned' => true, 'autoincrement' => true]);
        $myTable->addColumn("`{$this->tableOptions['keyColumn']}`", 'string', ['notnull' => false]);
        $myTable->addColumn("`{$this->tableOptions['valueColumn']}`", 'text');
        $myTable->addColumn("`{$this->tableOptions['poolNameColumn']}`", 'string', ['notnull' => false]);
        $myTable->addColumn("`{$this->tableOptions['expirationColumn']}`", 'datetime', ['notnull' => false]);
        $myTable->setPrimaryKey(["`{$this->tableOptions['idColumn']}`"], true);
        $myTable->addUniqueIndex(["`{$this->tableOptions['keyColumn']}`", "`{$this->tableOptions['poolNameColumn']}`"], 'key_pool');
        // Get query and execute it
        $query = $schema->toSql($connection->getDatabasePlatform())[0];
        $connection->exec($query);
    }
}