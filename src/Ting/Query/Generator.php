<?php
/***********************************************************************
 *
 * Ting - PHP Datamapper
 * ==========================================
 *
 * Copyright (C) 2014 CCM Benchmark Group. (http://www.ccmbenchmark.com)
 *
 ***********************************************************************
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you
 * may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or
 * implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 **********************************************************************/


namespace CCMBenchmark\Ting\Query;


use CCMBenchmark\Ting\Connection;
use CCMBenchmark\Ting\Driver\DriverInterface;
use CCMBenchmark\Ting\Repository\CollectionFactoryInterface;

class Generator
{

    /**
     * @var Connection
     */
    protected $connection = null;
    /**
     * @var QueryFactory
     */
    protected $queryFactory = null;

    protected $database = '';

    protected $tableName = '';

    protected $fields = [];

    public function __construct(
        Connection $connection,
        QueryFactoryInterface $queryFactory,
        $database,
        $table,
        array $fields
    ) {
        $this->connection  = $connection;
        $this->queryFactory = $queryFactory;
        $this->database = $database;
        $this->tableName = $table;
        $this->fields = $fields;
    }

    public function getByPrimaries(
        array $primariesValue,
        CollectionFactoryInterface $collectionFactory,
        $onMaster = false
    )
    {
        if ($onMaster === true) {
            $driver = $this->connection->master();
        } else {
            $driver = $this->connection->slave();
        }

        $fields = $this->escapeFields($this->fields, $driver);

        $sql  = 'SELECT ' . implode(', ', $fields) . ' FROM ' .
            $driver->escapeField($this->tableName);

        list($conditions, $params) = $this->generateConditionAndParams($fields, $primariesValue);

        $sql .= 'WHERE '.implode(' AND ', $conditions);


        $query = $this->queryFactory->get($sql, $this->connection, $collectionFactory);
        $query->setParams($params);

        return $query;
    }

    /**
     * @param array $values associative array : columnName => value
     * @return PreparedQuery
     */
    public function insert(array $values)
    {
        $driver = $this->connection->master();
        $fields = $this->escapeFields(array_keys($values), $driver);

        $sql = 'INSERT INTO ' . $driver->escapeField($this->tableName) . ' ('
            . implode($fields, ', ') . ') VALUES (:' . implode(array_keys($values), ', :') . ')';

        $query = $this->queryFactory->getPrepared($sql, $this->connection);
        $query->setParams($values);
        return $query;
    }

    /**
     * @param array $values associative array : columnName => value
     * @param array $primariesValue
     * @return PreparedQuery
     */
    public function update(array $values, array $primariesValue)
    {
        $driver = $this->connection->master();

        $sql = 'UPDATE ' . $driver->escapeField($this->tableName) .' SET ';
        $set = [];
        foreach ($values as $column => $value) {
            $set[] = $driver->escapeField($column) . ' = :' . $column;
        }
        $sql .= implode(', ', $set);

        $primaryFields = $this->escapeFields(array_keys($primariesValue), $driver);

        list($conditions, $params) = $this->generateConditionAndParams($primaryFields, $primariesValue);

        $params = array_merge($values, $params);

        $sql .= ' WHERE ' . implode(' AND ', $conditions);

        $query = $this->queryFactory->getPrepared($sql, $this->connection);
        $query->setParams($params);

        return $query;
    }

    public function delete(array $primariesKeyValue)
    {
        $driver = $this->connection->master();

        $sql = 'DELETE FROM ' . $driver->escapeField($this->tableName);

        $primaryFields = $this->escapeFields(array_keys($primariesKeyValue), $driver);

        list($conditions, $params) = $this->generateConditionAndParams($primaryFields, $primariesKeyValue);

        $sql .= ' WHERE ' . implode(' AND ', $conditions);

        $query = $this->queryFactory->getPrepared($sql, $this->connection);
        $query->setParams($params);

        return $query;
    }

    protected function escapeFields(array $fields, DriverInterface $driver)
    {
        return array_map(
            function($field) use ($driver) {
                return $driver->escapeField($field);
            },
            $fields
        );
    }

    protected function generateConditionAndParams($fields, $values)
    {
        $conditions = [];
        $i = 0;

        foreach ($values as $field => $value) {
            $conditions[] = $fields[$i] . ' = :#' . $field;
            $values['#' . $field] = $value;
            unset($values[$field]);
            $i++;
        }

        return [$conditions, $values];
    }
} 