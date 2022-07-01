<?php

namespace QueryLite\Core;

use Exception;
use PDO;

class SchemaBuilder
{
    protected string $tableName;
    protected bool $ifNotExists = true;
    protected array $columns;

    protected string $collation = 'utf8mb4_general_ci';
    protected ?string $engine = null;
    protected ?PDO $connection = null;

    public function __construct(string $tableName, PDO $connection = null)
    {
        $this->tableName = $tableName;
        if ($connection) {
            $this->connection = $connection;
        }
    }

    public function column(
        string $name,
        string $type = 'int',
        bool   $autoIncrement = false,
        bool   $primaryKey = false,
        bool   $notNull = false,
        bool   $unique = false,
    ): static
    {
        $this->columns[] = [
            'name' => $name,
            'type' => $type,
            'AUTO_INCREMENT' => $autoIncrement,
            'PRIMARY KEY' => $primaryKey,
            'NOT NULL' => $notNull,
            'UNIQUE' => $unique,
        ];

        return $this;
    }

    public function ifNotExists(bool $value = true): static
    {
        $this->ifNotExists = $value;
        return $this;
    }

    public function setEngine(string $engineName = 'INNODB'): static
    {
        $this->engine = $engineName;
        return $this;
    }

    public function setCollation(string $collation = 'utf8mb4_general_ci'): static
    {
        $this->collation = $collation;
        return $this;
    }

    public function buildSql(): string
    {
        $sql = "CREATE TABLE ";
        if ($this->ifNotExists) {
            $sql .= 'IF NOT EXISTS ';
        }
        $sql .= $this->tableName;
        $columnStrings = [];
        foreach ($this->columns as $column) {
            $parts = [$column['name']];
            if ($this->isDriverSQLite() && $column['AUTO_INCREMENT']) {
                $column['type'] = 'INTEGER';
            }
            $parts[] = $column['type'];
            foreach ([
                         'PRIMARY KEY',
                         'NOT NULL',
                         'UNIQUE',
                     ] as $constraint) {
                if ($column[$constraint]) {
                    $parts[] = $constraint;
                }
            }
            if ($column['AUTO_INCREMENT']) {
                if ($this->isDriverSQLite()) {
                    $column['type'] = 'INTEGER';
                    $parts[] = 'AUTOINCREMENT';
                } else {
                    $parts[] = 'AUTO_INCREMENT';
                }
            }
            $columnStrings[] = join(' ', $parts);
        }
        $sql .= ' (' . join(',', $columnStrings) . ') ';
        if ($this->engine) {
            $sql .= 'ENGINE=' . $this->engine;
        }
        if (!$this->isDriverSQLite()) {
            $sql .= 'COLLATE ' . $this->collation;
        }
        return $sql;
    }

    /**
     * @throws Exception
     */
    public function execute(): bool|int
    {
        $query = $this->buildSql();
        if (!$this->connection) {
            throw new Exception('You should pass PDO instance for using this method');
        }
        try {
            return $this->connection->exec($query);
        } catch (\PDOException $ex) {
            $query = str_replace("\n", ' ', $query);
            error_log(" !!! PDO thrown an error " . $ex->getMessage() . " --- for SQL query: $query\n");
            throw $ex;
        }
    }

    protected function isDriverSQLite(): bool
    {
        return strtolower($this->connection->getAttribute(PDO::ATTR_DRIVER_NAME)) == 'sqlite';
    }
}
