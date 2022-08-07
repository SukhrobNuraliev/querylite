<?php

namespace QueryLite\Tests\CreateTable;

use Exception;
use QueryLite\QueryException;
use PHPUnit\Framework\Attributes\CoversClass;
use QueryLite\QueryLite;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\QueryLite\Core\SchemaBuilder::class)]
#[UsesClass(\QueryLite\QueryLite::class)]
#[UsesClass(\QueryLite\Core\QuerySelector::class)]
class AutoIncrementTest extends TestCase
{
    /**
     * @throws QueryException
     * @throws Exception
     */
    public function testSqlite(): void
    {
        $model = (new QueryLite(new \PDO('sqlite::memory:')))->setTable('test_table');
        $model->createTable('test_table')
            ->column('id', autoIncrement: true, primaryKey: true)
            ->column('foo', type: 'string')
            ->execute();
        $model->insert(['foo' => 1]);
        $model->insert(['foo' => 2]);
        $model->insert(['foo' => 3]);

        $actualResult = $model->select()->getAllRows();

        $this->assertEquals([
            ['id' => 1, 'foo' => 1],
            ['id' => 2, 'foo' => 2],
            ['id' => 3, 'foo' => 3],
        ], $actualResult);
    }

}
