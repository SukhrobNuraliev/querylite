<?php

namespace QueryLite\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use QueryLite\Drivers\SQLiteHandler;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use QueryLite\QueryException;

#[CoversClass(SQLiteHandler::class)]
#[UsesClass(\QueryLite\Core\SchemaBuilder::class)]
#[UsesClass(\QueryLite\Core\QuerySelector::class)]
class InsertUpdateBatchTest extends TestCase
{
    /**
     * @throws QueryException
     */
    public function test(): void
    {
        $targetData = ['foo' => 'bar', 'count' => 1];

        $model = new class(new \PDO('sqlite::memory:')) extends SQLiteHandler {
            const TABLE = 'test_table';
        };
        $model->createTable('test_table')
            ->column('id', autoIncrement: true, primaryKey: true)
            ->column('foo', type: 'string', unique: true)
            ->column('count')
            ->execute();

        $model->insertUpdateBatch([$targetData, $targetData], incrementColumns: ['count']);
        $resultData = $model->select()->whereEqual('foo', 'bar')->getFirstRow();
        $this->assertEquals(['id' => 1, 'foo' => 'bar', 'count' => 2], $resultData);
    }
}
