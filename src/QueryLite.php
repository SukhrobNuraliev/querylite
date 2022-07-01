<?php

namespace QueryLite;

use QueryLite\Core\SchemaBuilder;

class QueryLite
{
    const TABLE = '';
    const PRIMARY_KEY = 'id';
    protected \PDO $connection;
    protected string $dynamicTable = '';

    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
        $this->setTable(static::TABLE);
    }

    public function setTable(string $tableName): static
    {
        $this->dynamicTable = $tableName;
        return $this;
    }

    /**
     * @throws QueryException
     */
    public function getTable(): string
    {
        if ($this->dynamicTable === '') {
            throw new QueryException('You should define table name by TABLE constant or setTable method');
        }
        return $this->dynamicTable;
    }


    /**
     * @throws QueryException
     */
    public function raw(string $sql, array $preparedParameterValues = []): QueryFetcher
    {
        $sth = $this->executeAndReturnStatement($sql, $preparedParameterValues);
        return new QueryFetcher($sth);
    }


    /**
     * @throws QueryException
     */
    public function insert(array $data, bool $ignore = false, bool $replace = false): int
    {
        if (array_is_list($data)) {
            throw new QueryException('Data keys should be column names, not numbers: ' . json_encode($data));
        }
        $markers = [];
        $values = [];
        $columns = [];
        foreach ($data as $k => $v) {
            $columns[] = "`$k`";
            $markers[] = "?";
            $values[] = $v;
        }
        $sql = $replace ? 'REPLACE INTO ' : ($ignore ? 'INSERT IGNORE INTO ' : 'INSERT INTO ');
        $sql .= $this->getTable() . " (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $markers) . ")";
        $this->execute($sql, $values);
        return $this->getLastInsertId();
    }

    /**
     * @throws QueryException
     */
    public function insertIgnore(array $data): int
    {
        return $this->insert($data, ignore: true);
    }

    /**
     * @throws QueryException
     */
    public function replace(array $data): int
    {
        return $this->insert($data, replace: true);
    }

    /**
     * @throws QueryException
     */
    public function insertBatch(array $arraysOfData, bool $ignore = false): bool
    {
        if (!array_is_list($arraysOfData)) {
            throw new QueryException('ArraysOfData should be with index keys, got one array with text keys: ' . json_encode($arraysOfData));
        }
        $keys = array_keys($arraysOfData[0]);
        $values = [];
        foreach ($arraysOfData as $data) {
            foreach ($data as $key => $value) {
                $values[] = $value;
            }
        }
        return $this->insertBatchRaw($keys, $values, $ignore);
    }

    /**
     * @throws QueryException
     */
    protected function insertBatchRaw(array $keys, array $values, bool $ignore = false): bool
    {
        list($keysCount, $valuesSqlChunkSize, $valuesChunkSize, $valuesSql, $keys, $valuesOffset) = $this->prepareInsertChunks($keys, $values);

        $ignoreSql = '';
        if ($ignore) {
            $ignoreSql = 'IGNORE ';
        }
        foreach (array_chunk($valuesSql, $valuesSqlChunkSize) as $valuesSqlPart) {
            $valuesPart = array_slice($values, $valuesOffset * $valuesSqlChunkSize * $keysCount, $valuesChunkSize);
            $valuesOffset++;
            $valuesSqlPart = implode(',', $valuesSqlPart);
            $sql = 'INSERT ' . $ignoreSql . "INTO `" . $this->getTable() . "` ({$keys}) VALUES {$valuesSqlPart}";
            $this->execute($sql, $valuesPart ?? []);
        }
        return true;
    }

    /**
     * @throws QueryException
     */
    public function insertUpdate(array $insertData, array $updateColumns): int
    {
        if (empty($insertData) || empty($updateData)) {
            throw new QueryException('InsertUpdate arrays insertData and updateColumns cant be empty');
        }
        $insertPairs = [];
        $updatePairs = [];
        $values = [];
        foreach ($insertData as $k => $v) {
            $insertPairs[] = "`{$k}` = ?";
            $values[] = $v;
        }
        foreach ($updateColumns as $column) {
            $updatePairs[] = "`{$column}` = ?";
            if (!isset($insertData[$column])) {
                throw new QueryException("InsertUpdate updateColumn '$column' not in array of insert data " . json_encode($insertData));
            }
            $values[] = $insertData[$column];
        }
        // TODO check for MYSQL
        $sql = "INSERT INTO `" . $this->getTable() . "` SET " . implode(', ', $insertPairs)
            . " ON DUPLICATE KEY UPDATE " . implode(', ', $updatePairs);
        $this->execute($sql, $values);
        return $this->getLastInsertId();
    }

    /**
     * @throws QueryException
     */
    public function insertUpdateBatch(array $insertRows, array $updateColumns = [], array $incrementColumns = []): bool
    {
        if (!array_is_list($insertRows)) {
            throw new QueryException('InsertRows should be with index keys, got one array with text keys: ' . json_encode($insertRows));
        }
        $insertKeys = array_keys($insertRows[0]);
        $insertValues = [];
        foreach ($insertRows as $row) {
            foreach ($row as $key => $value) {
                $insertValues[] = $value;
            }
        }
        $updateSql = [];
        foreach ($updateColumns as $column) {
            $updateSql[] = "`{$column}` = `VALUES(`{$column}`)";
        }
        foreach ($incrementColumns as $column) {
            $updateSql[] = "`{$column}` = `{$column}` + VALUES(`{$column}`)";
        }
        $updateSql = join(',', $updateSql);

        $res = $this->insertUpdateBatchRaw($insertKeys, $insertValues, $updateSql);
        return $res;
    }

    /**
     * @throws QueryException
     */
    protected function insertUpdateBatchRaw(array $insertKeys, array $insertValues, string $updateSql): bool
    {
        list($keysCount, $valuesSqlChunkSize, $valuesChunkSize, $valuesSql, $keys, $valuesOffset) = $this->prepareInsertChunks($insertKeys, $insertValues);

        $res = false;
        foreach (array_chunk($valuesSql, $valuesSqlChunkSize) as $valuesSqlPart) {
            $valuesPart = array_slice($insertValues, $valuesOffset * $valuesSqlChunkSize * $keysCount, $valuesChunkSize);
            $valuesOffset++;
            $valuesSqlPart = implode(',', $valuesSqlPart);
            $sql = "INSERT INTO `" . $this->getTable() . "` ({$keys}) VALUES {$valuesSqlPart} ON DUPLICATE KEY UPDATE {$updateSql}";

            $this->execute($sql, $valuesPart ?? []);
        }
        return true;
    }

    /**
     * @throws QueryException
     */
    public function increment(string $primaryKeyValue, string $columnName, int $amount = 1): bool
    {
        $sql = "UPDATE `" . $this->getTable() . "` SET {$columnName} = {$columnName} + {$amount} WHERE " . static::PRIMARY_KEY . " = ?";
        return $this->execute($sql, [$primaryKeyValue]);
    }

    /**
     * @throws QueryException
     */
    public function update(string $primaryKeyValue, array $data): bool
    {
        if (empty($data)) {
            throw new QueryException("Update data can't be empty");
        }
        if (array_is_list($data)) {
            throw new QueryException('Data keys should be column names, not numbers: ' . json_encode($data));
        }
        $pairs = [];
        $values = [];
        foreach ($data as $k => $v) {
            $pairs[] = "`{$k}` = ?";
            $values[] = $v;
        }
        $values = array_merge($values, [$primaryKeyValue]);
        $sql = "UPDATE `" . $this->getTable() . "` SET " . implode(', ', $pairs) . " WHERE " . static::PRIMARY_KEY . " = ?";
        return $this->execute($sql, $values);
    }

    /**
     * @throws QueryException
     */
    public function delete(string $primaryKeyValue): bool
    {
        $sql = "DELETE FROM `" . $this->getTable() . "` WHERE " . static::PRIMARY_KEY . " = ?";
        return $this->execute($sql, [$primaryKeyValue]);
    }

    public function getLastInsertId($sequenceName = null): bool|string
    {
        return $this->connection->lastInsertId($sequenceName);
    }

    public function createTable(string $tableName): SchemaBuilder
    {
        return new SchemaBuilder($tableName, $this->connection);
    }


}
