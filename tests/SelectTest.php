<?php

namespace QueryLite\Tests;

use QueryLite\QueryException;
use QueryLite\QueryLite;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(QueryLite::class)]
#[UsesClass(\QueryLite\Core\SchemaBuilder::class)]
#[UsesClass(\QueryLite\Core\QuerySelector::class)]
class SelectTest extends TestCase
{
    /**
     * @throws QueryException
     */
    public function testSelect(): void
    {
        $insertData = [
            ['foo' => 'cut by where', 'height' => 11, 'day' => 2],
            ['foo' => 'ok', 'height' => 10, 'day' => 2],
            ['foo' => 'ok', 'height' => 10, 'day' => 2],
        ];
        $targetResult = [['foo' => 'ok', 'height' => 20]];


        $model = new class(new \PDO('sqlite::memory:')) extends QueryLite {
            const TABLE = 'test_table';
        };
        $model->createTable('test_table')
            ->column('id', autoIncrement: true, primaryKey: true)
            ->column('height')
            ->column('foo', type: 'string')
            ->column('day')
            ->execute();
        foreach ($insertData as $row) {
            $model->insert($row);
        }

        $result = $model->select(['foo', 'sum(height) as height'])
            ->whereEqual('height', 10)
            ->orderBy('id desc')
            ->limit(3)
            ->offset(0)
            ->groupBy('day')
            ->collate('utf8mb4_0900_ai_ci')
            ->getAllRows();
        $this->assertEquals($targetResult, $result);
    }

    /**
     * @throws QueryException
     * @throws \Exception
     */
    public function testOr()
    {
        $insertData = [
            ['foo' => 1],
            ['foo' => 2],
            ['foo' => 3],
        ];

        $model = (new QueryLite(new \PDO('sqlite::memory:')))->setTable('test_table');
        $model->createTable('test_table')
            ->column('foo', type: 'string')
            ->execute();
        $model->insertBatch($insertData);


        $actualResult = $model->select()
            ->where('foo', '=', 1)
            ->orWhere('foo', '=', 2)
            ->orWhere('foo', '=', 3)
            ->getAllRows();
        $this->assertEquals($insertData, $actualResult);
    }
}
