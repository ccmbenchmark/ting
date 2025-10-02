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
     * @param Connection            $connection
     * @param QueryFactoryInterface $queryFactory
     * @param string $schemaName
     * @param string $tableName
     * @param array                 $fields
     *
     * @internal
     */
    public function __construct(protected Connection $connection, protected QueryFactoryInterface $queryFactory, protected string $schemaName, protected string $tableName, protected array $fields)
    {
    }

    /**
     * @param DriverInterface $driver
     * @return string
     */
    protected function getTarget(DriverInterface $driver): string
    {
        $schema = '';
        if ($this->schemaName !== '') {
            $schema = $driver->escapeField($this->schemaName) . '.';
        }

        return $schema . $driver->escapeField($this->tableName);
    }

    /**
     * @param array           $fields
     * @param DriverInterface $driver
     * @return string
     */
    protected function getSelect(array $fields, DriverInterface $driver): string
    {
        return 'SELECT ' . implode(', ', $fields) . ' FROM ' .
            $this->getTarget($driver);
    }


    /**
     * @param bool $forceMaster
     * @return DriverInterface
     */
    protected function getDriver($forceMaster): DriverInterface
    {
        $driver = $forceMaster === true ? $this->connection->master() : $this->connection->slave();

        return $driver;
    }

    /**
     * @param CollectionFactoryInterface $collectionFactory
     * @param bool                       $forceMaster
     * @return QueryInterface
     *
     * @internal
     */
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
     *
     * @internal
     */
    public function getOneByCriteria(
        array $primariesValue,
        CollectionFactoryInterface $collectionFactory,
        $forceMaster = false
    ): QueryInterface {
        $driver = $this->getDriver($forceMaster);

        [$sql, $params] = $this->getSqlAndParamsByCriteria($primariesValue, $driver);
        $sql .=  ' LIMIT 1';

        $query = $this->queryFactory->get($sql, $this->connection, $collectionFactory);
        $query->setParams($params);

        if ($forceMaster === true) {
            $query->selectMaster(true);
        }

        return $query;
    }

    /**
     * @param array           $criteria
     * @param DriverInterface $driver
     * @return array
     */
    protected function getSqlAndParamsByCriteria(array $criteria, DriverInterface $driver): array
    {
        $fields = $this->escapeFields($this->fields, $driver);

        $sql = $this->getSelect($fields, $driver);

        [$conditions, $params] = $this->generateConditionAndParams(array_keys($criteria), $criteria);
        $sql .= ' WHERE ' . implode(' AND ', $conditions);

        return [$sql, $params];
    }

    /**
     * @param array<string, mixed>       $criteria
     * @param array<string, string>      $order
     */
    public function getByCriteria(
        array $criteria,
        CollectionFactoryInterface $collectionFactory,
        bool $forceMaster = false,
        array $order = [],
        int $limit = 0
    ): QueryInterface {
        $driver = $this->getDriver($forceMaster);

        [$sql, $params] = $this->getSqlAndParamsByCriteria($criteria, $driver);
        $this->updateSQLWithOrderAndLimit($sql, $driver, $order, $limit);

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
     *
     * @internal
     */
    public function insert(array $values)
    {
        $driver = $this->getDriver(true);
        $fields = $this->escapeFields(array_keys($values), $driver);

        $sql = 'INSERT INTO ' . $this->getTarget($driver) . ' ('
            . implode(', ', $fields) . ') VALUES (:' . implode(', :', array_keys($values)) . ')';

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
     *
     * @internal
     */
    public function update(array $values, array $primariesValue)
    {
        $driver = $this->getDriver(true);

        $sql = 'UPDATE ' . $this->getTarget($driver) . ' SET ';
        $set = [];
        foreach (array_keys($values) as $column) {
            $set[] = $driver->escapeField($column) . ' = :' . $column;
        }
        $sql .= implode(', ', $set);

        $primaryFields = $this->escapeFields(array_keys($primariesValue), $driver);

        [$conditions, $params] = $this->generateConditionAndParams($primaryFields, $primariesValue);

        $params = array_merge($values, $params);

        $sql .= ' WHERE ' . implode(' AND ', $conditions);

        $query = $this->queryFactory->getPrepared($sql, $this->connection);
        $query->setParams($params);

        return $query;
    }

    /**
     * @param array $primariesKeyValue
     *
     * @return PreparedQuery
     *
     * @internal
     */
    public function delete(array $primariesKeyValue)
    {
        $driver = $this->getDriver(true);

        $sql = 'DELETE FROM ' . $this->getTarget($driver);

        $primaryFields = $this->escapeFields(array_keys($primariesKeyValue), $driver);

        [$conditions, $params] = $this->generateConditionAndParams($primaryFields, $primariesKeyValue);

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
    protected function escapeFields(array $fields, DriverInterface $driver): array
    {
        return array_map(
            fn ($field) => $driver->escapeField($field),
            $fields
        );
    }

    /**
     * @param array $fields fields names
     * @param array $values each values can be a value or an array
     *
     * @return array
     */
    protected function generateConditionAndParams(array $fields, array $values): array
    {
        $conditions = [];
        $i = 0;

        foreach ($values as $field => $value) {
            if ($value === null) {
                $conditions[] = $fields[$i] . ' IS NULL';
            } elseif (is_array($value)) {
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

    /**
     * @param string          $sql
     * @param DriverInterface $driver
     * @param array           $order
     * @param int             $limit
     * @return void
     */
    protected function updateSQLWithOrderAndLimit(string &$sql, DriverInterface $driver, array $order = [], int $limit = 0)
    {
        if (count($order) > 0) {
            $sql .= $this->generateOrder($order, $driver);
        }

        if ($limit > 0) {
            $sql .= $this->generateLimit($limit);
        }
    }

    /**
     * Generate Order params to add to query
     *
     * @param array             $orderList
     * @param DriverInterface   $driver
     * @return string
     */
    protected function generateOrder(array $orderList, DriverInterface $driver): string
    {
        $fields = $this->escapeFields(array_keys($orderList), $driver);

        $orderClause = '';
        $orderCriteria = [];

        $i = 0;
        foreach ($orderList as $value) {
            $value = strtoupper((string) $value);
            if (\in_array($value, ['ASC', 'DESC'])) {
                $orderCriteria[] = $fields[$i] . ' ' . $value;
            }
            $i++;
        }

        if (count($orderList) > 0) {
            return ' ORDER BY ' . implode(',', $orderCriteria);
        }

        return '';
    }

    /**
     * Generate Limit params to add to query
     */
    protected function generateLimit(int $limit): string
    {
        return ' LIMIT ' . $limit;
    }
}
