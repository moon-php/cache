<?php

declare(strict_types=1);

use Moon\Cache\Helper\CreateTableHelper;
use PHPUnit\Framework\TestCase;

class CreateTableHelperTest extends TestCase
{
    /**
     * @dataProvider tableOptionsDataProvider
     */
    public function testQueryIsProperlyBuilt(array $tableOptions, array $expectedTableOptions)
    {
        $table = $this->prophesize(\Doctrine\DBAL\Schema\Table::class);
        $table->addColumn("`{$expectedTableOptions['idColumn']}`", 'bigint', ['unsigned' => true, 'autoincrement' => true])->shouldBeCalled(1);
        $table->addColumn("`{$expectedTableOptions['keyColumn']}`", 'string', ['notnull' => false])->shouldBeCalled(1);
        $table->addColumn("`{$expectedTableOptions['valueColumn']}`", 'text')->shouldBeCalled(1);
        $table->addColumn("`{$expectedTableOptions['poolNameColumn']}`", 'string', ['notnull' => false])->shouldBeCalled(1);
        $table->addColumn("`{$expectedTableOptions['expirationColumn']}`", 'datetime', ['notnull' => false])->shouldBeCalled(1);
        $table->setPrimaryKey(["`{$expectedTableOptions['idColumn']}`"], true)->shouldBeCalled(1);
        $table->addUniqueIndex(["`{$expectedTableOptions['keyColumn']}`", "`{$expectedTableOptions['poolNameColumn']}`"], 'key_pool')->shouldBeCalled(1);

        $schema = $this->prophesize(\Doctrine\DBAL\Schema\Schema::class);
        $schema->createTable("`{$expectedTableOptions['tableName']}`")->shouldBeCalled(1)->willReturn($table->reveal());
        $schema->toSql(\Prophecy\Argument::type(Doctrine\DBAL\Platforms\AbstractPlatform::class))->shouldBeCalled(1)->willReturn(['first', 'second']);

        $schemaManager = $this->prophesize(\Doctrine\DBAL\Schema\AbstractSchemaManager::class);
        $schemaManager->createSchema()->shouldBeCalled(1)->willReturn($schema->reveal());

        $connection = $this->prophesize(\Doctrine\DBAL\Connection::class);
        $connection->exec(\Prophecy\Argument::type('string'))->shouldBeCalled(1)->willReturn($schemaManager->reveal());
        $connection->getSchemaManager()->shouldBeCalled(1)->willReturn($schemaManager->reveal());
        $connection->getDatabasePlatform()->shouldBeCalled(1)->willReturn($this->prophesize(Doctrine\DBAL\Platforms\AbstractPlatform::class)->reveal());

        $helper = new CreateTableHelper();
        $helper->generate($connection->reveal(), $tableOptions);
    }

    public function tableOptionsDataProvider()
    {
        $one = [
            'tableName' => 'moon_cache',
            'idColumn' => 'id',
            'keyColumn' => 'key',
            'valueColumn' => 'value',
            'poolNameColumn' => 'pool_name',
            'expirationColumn' => 'expires_at'
        ];

        $two = [
            'tableName' => 'those',
            'idColumn' => 'are',
            'keyColumn' => 'some',
            'valueColumn' => 'random',
            'poolNameColumn' => 'values',
            'expirationColumn' => 'ok'
        ];

        $three = [
            'tableName' => 'only',
            'poolNameColumn' => 'three',
            'expirationColumn' => 'columns'
        ];


        $expectedOne = [
            'tableName' => 'moon_cache',
            'idColumn' => 'id',
            'keyColumn' => 'key',
            'valueColumn' => 'value',
            'poolNameColumn' => 'pool_name',
            'expirationColumn' => 'expires_at'
        ];

        $expectedTwo = [
            'tableName' => 'those',
            'idColumn' => 'are',
            'keyColumn' => 'some',
            'valueColumn' => 'random',
            'poolNameColumn' => 'values',
            'expirationColumn' => 'ok'
        ];

        $expectedThree = [
            'tableName' => 'only',
            'idColumn' => 'id',
            'keyColumn' => 'key',
            'valueColumn' => 'value',
            'poolNameColumn' => 'three',
            'expirationColumn' => 'columns'
        ];

        return [[$one, $expectedOne], [$two, $expectedTwo], [$three, $expectedThree]];
    }
}