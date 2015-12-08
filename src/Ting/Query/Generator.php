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

    protected $tableName = '';

    protected $fields = [];

    /**
     * @param Connection            $connection
     * @param QueryFactoryInterface $queryFactory
     * @param                       $table
     * @param array                 $fields
     */
    public function __construct(
        Connection $connection,
        QueryFactoryInterface $queryFactory,
        $table,
        array $fields
    ) {
        $this->connection = $connection;
        $this->queryFactory = $queryFactory;
        $this->tableName = $table;
        $this->fields = $fields;
    }

    protected function getSelect(array $fields, DriverInterface $driver)
    {
        return 'SELECT ' . implode(', ', $fields) . ' FROM ' .
            $driver->escapeField($this->tableName);
    }

    protected function getDriver($forceMaster)
    {
        if ($forceMaster === true) {
            $driver = $this->connection->master();
        } else {
            $driver = $this->connection->slave();
        }

        return $driver;
    }

    public function getAll(
        CollectionFactoryInterface $collectionFactory,
        $forceMaster = false
    ) {
        $driver = $this->getDriver($forceMaster);

        $fields = $this->escapeFields($this->fields, $driver);

        $sql = $this->getSelect($fields, $driver);

        $query = $this->queryFactory->get($sql, $this->connection, $collectionFactory);

        if ($forceMaster === true) {
            $query->selectMaster(true);
        }

        return $query;
    }

    /**
     * Returns a Query, allowing to fetch an object by an associative array (column => value).
     *
     * @param array                      $primariesValue
     * @param CollectionFactoryInterface $collectionFactory
     * @param bool                       $forceMaster
     *
     * @return Query
     */
    public function getOneByCriteria(
        array $primariesValue,
        CollectionFactoryInterface $collectionFactory,
        $forceMaster = false
    ) {
        $driver = $this->getDriver($forceMaster);

        list($sql, $params) = $this->getSqlAndParamsByCriteria($primariesValue, $driver);
        $sql .=  ' LIMIT 1';

        $query = $this->queryFactory->get($sql, $this->connection, $collectionFactory);
        $query->setParams($params);

        if ($forceMaster === true) {
            $query->selectMaster(true);
        }

        return $query;
    }

    protected function getSqlAndParamsByCriteria(array $criteria, DriverInterface $driver)
    {
        $fields = $this->escapeFields($this->fields, $driver);

        $sql = $this->getSelect($fields, $driver);

        list($conditions, $params) = $this->generateConditionAndParams(array_keys($criteria), $criteria);
        $sql .= ' WHERE ' . implode(' AND ', $conditions);

        return [$sql, $params];
    }

    /**
     * Returns a Query, allowing to fetch an object by criteria (associative array).
     *
     * @param array                      $criteria
     * @param CollectionFactoryInterface $collectionFactory
     * @param bool                       $forceMaster
     *
     * @return Query
     */
    public function getByCriteria(
        array $criteria,
        CollectionFactoryInterface $collectionFactory,
        $forceMaster = false
    ) {
        $driver = $this->getDriver($forceMaster);

        list($sql, $params) = $this->getSqlAndParamsByCriteria($criteria, $driver);

        $query = $this->queryFactory->get($sql, $this->connection, $collectionFactory);
        $query->setParams($params);

        if ($forceMaster === true) {
            $query->selectMaster(true);
        }

        return $query;
    }

    /**
     * Returns a PreparedQuery to insert an object in database.
     *
     * @param array $values associative array : columnName => value
     *
     * @return PreparedQuery
     */
    public function insert(array $values)
    {
        $driver = $this->getDriver(true);
        $fields = $this->escapeFields(array_keys($values), $driver);

        $sql = 'INSERT INTO ' . $driver->escapeField($this->tableName) . ' ('
            . implode($fields, ', ') . ') VALUES (:' . implode(array_keys($values), ', :') . ')';

        $query = $this->queryFactory->getPrepared($sql, $this->connection);

        $query->setParams($values);

        return $query;
    }

    /**
     * Returns a prepared query to update values in database.
     *
     * @param array $values         associative array : columnName => value
     * @param array $primariesValue
     *
     * @return PreparedQuery
     */
    public function update(array $values, array $primariesValue)
    {
        $driver = $this->getDriver(true);

        $sql = 'UPDATE ' . $driver->escapeField($this->tableName) . ' SET ';
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
        $driver = $this->getDriver(true);

        $sql = 'DELETE FROM ' . $driver->escapeField($this->tableName);

        $primaryFields = $this->escapeFields(array_keys($primariesKeyValue), $driver);

        list($conditions, $params) = $this->generateConditionAndParams($primaryFields, $primariesKeyValue);

        $sql .= ' WHERE ' . implode(' AND ', $conditions);

        $query = $this->queryFactory->getPrepared($sql, $this->connection);
        $query->setParams($params);

        return $query;
    }

    /**
     * Protect every fields provided, using the driver provided.
     *
     * @param array           $fields
     * @param DriverInterface $driver
     *
     * @return array
     */
    protected function escapeFields(array $fields, DriverInterface $driver)
    {
        return array_map(
            function ($field) use ($driver) {
                return $driver->escapeField($field);
            },
            $fields
        );
    }

    /**
     * @param array $fields fields names
     * @param array $values each values can be a value or an array
     * 
     * @return array
     */
    protected function generateConditionAndParams($fields, $values)
    {
        $conditions = [];
        $i = 0;

        foreach ($values as $field => $value) {
            if (is_array($value) === true) {

                // handle array values...
                $j = 0;
                $condition = $fields[$i] . ' IN (';
                foreach ($value as $v) {
                    $j++;
                    $condition .= ':' . $field . '__' . $j . ',';

                    $values[$field.'__' . $j] = $v;
                }
                $condition = rtrim($condition, ',');
                $condition .= ')';

                $conditions[] = $condition;
            } else {
                $conditions[] = $fields[$i] . ' = :#' . $field;
                $values['#' . $field] = $value;
            }
            unset($values[$field]);
            $i++;
        }

        return [$conditions, $values];
    }
}
