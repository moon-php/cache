<?php

declare(strict_types=1);

namespace Moon\Cache\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class CreateTableHelperTest extends TestCase
{
    /**
     * @dataProvider tableOptionsDataProvider
     */
    public function testQueryIsProperlyBuilt(array $tableOptions, array $expectedTableOptions)
    {
        $table = $this->prophesize(Table::class);
        $table->addColumn("`{$expectedTableOptions['idColumn']}`", 'bigint', ['unsigned' => true, 'autoincrement' => true])->shouldBeCalled(1);
        $table->addColumn("`{$expectedTableOptions['keyColumn']}`", 'string', ['notnull' => false])->shouldBeCalled(1);
        $table->addColumn("`{$expectedTableOptions['valueColumn']}`", 'text')->shouldBeCalled(1);
        $table->addColumn("`{$expectedTableOptions['poolNameColumn']}`", 'string', ['notnull' => false])->shouldBeCalled(1);
        $table->addColumn("`{$expectedTableOptions['expirationColumn']}`", 'datetime', ['notnull' => false])->shouldBeCalled(1);
        $table->setPrimaryKey(["`{$expectedTableOptions['idColumn']}`"], true)->shouldBeCalled(1);
        $table->addUniqueIndex(["`{$expectedTableOptions['keyColumn']}`", "`{$expectedTableOptions['poolNameColumn']}`"], 'key_pool')->shouldBeCalled(1);

        $schema = $this->prophesize(Schema::class);
        $schema->createTable("`{$expectedTableOptions['tableName']}`")->shouldBeCalled(1)->willReturn($table->reveal());
        $schema->toSql(Argument::type(AbstractPlatform::class))->shouldBeCalled(1)->willReturn(['first', 'second']);

        $schemaManager = $this->prophesize(AbstractSchemaManager::class);
        $schemaManager->createSchema()->shouldBeCalled(1)->willReturn($schema->reveal());

        $connection = $this->prophesize(Connection::class);
        $connection->exec(Argument::type('string'))->shouldBeCalled(1)->willReturn($schemaManager->reveal());
        $connection->getSchemaManager()->shouldBeCalled(1)->willReturn($schemaManager->reveal());
        $connection->getDatabasePlatform()->shouldBeCalled(1)->willReturn($this->prophesize(AbstractPlatform::class)->reveal());

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
            'expirationColumn' => 'expires_at',
        ];

        $two = [
            'tableName' => 'those',
            'idColumn' => 'are',
            'keyColumn' => 'some',
            'valueColumn' => 'random',
            'poolNameColumn' => 'values',
            'expirationColumn' => 'ok',
        ];

        $three = [
            'tableName' => 'only',
            'poolNameColumn' => 'three',
            'expirationColumn' => 'columns',
        ];

        $expectedOne = [
            'tableName' => 'moon_cache',
            'idColumn' => 'id',
            'keyColumn' => 'key',
            'valueColumn' => 'value',
            'poolNameColumn' => 'pool_name',
            'expirationColumn' => 'expires_at',
        ];

        $expectedTwo = [
            'tableName' => 'those',
            'idColumn' => 'are',
            'keyColumn' => 'some',
            'valueColumn' => 'random',
            'poolNameColumn' => 'values',
            'expirationColumn' => 'ok',
        ];

        $expectedThree = [
            'tableName' => 'only',
            'idColumn' => 'id',
            'keyColumn' => 'key',
            'valueColumn' => 'value',
            'poolNameColumn' => 'three',
            'expirationColumn' => 'columns',
        ];

        return [[$one, $expectedOne], [$two, $expectedTwo], [$three, $expectedThree]];
    }
}
