<?php

namespace QueryLite;

use PDOStatement;

class QueryFetcher
{
    protected PDOStatement $statement;

    public function __construct(PDOStatement $statement)
    {
        $this->statement = $statement;
    }

    public function getFirstRow(int $fetchStyle = \PDO::FETCH_ASSOC): bool|array
    {
        $result = $this->statement->fetch($fetchStyle);
        return !empty($result) ? $result : false;
    }

    public function getFirstValue($columnName = null): bool|int|string
    {
        $result = $this->getFirstRow();
        if (!$result) {
            return false;
        }
        if ($columnName) {
            return $result[$columnName] ?? false;
        }
        return reset($result);
    }

    public function getColumn(int $columnNumber = 0): bool|array
    {
        $result = $this->statement->fetchAll(\PDO::FETCH_COLUMN, $columnNumber);
        return !empty($result) ? $result : false;
    }

    public function getAllRows(int $fetchStyle = \PDO::FETCH_ASSOC): bool|array
    {
        $result = $this->statement->fetchAll($fetchStyle);
        return !empty($result) ? $result : false;
    }
}
