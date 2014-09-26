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

namespace CCMBenchmark\Ting\Repository;

use CCMBenchmark\Ting\ConnectionPoolInterface;
use CCMBenchmark\Ting\Driver\DriverInterface;
use CCMBenchmark\Ting\Exception;
use CCMBenchmark\Ting\Query\QueryFactoryInterface;

class Metadata
{

    protected $queryFactory   = null;
    protected $connectionName = null;
    protected $databaseName   = null;
    protected $class          = null;
    protected $table          = null;
    protected $fields         = [];
    protected $primaries      = [];

    public function __construct(QueryFactoryInterface $queryFactory)
    {
        $this->queryFactory = $queryFactory;
    }

    public function setConnection($connectionName)
    {
        $this->connectionName = (string) $connectionName;
    }

    public function setDatabase($databaseName)
    {
        $this->databaseName = (string) $databaseName;
    }

    /**
     * @throws \CCMBenchmark\Ting\Exception
     */
    public function setClass($className)
    {
        if (substr($className, 0, 1) === '\\') {
            throw new Exception('Class must not start with a \\');
        }

        $this->class = (string) $className;
    }

    public function setTable($tableName)
    {
        $this->table = (string) $tableName;
    }

    /**
     * @param array $params
     * @throws \CCMBenchmark\Ting\Exception
     */
    public function addField(array $params)
    {
        if (isset($params['fieldName']) === false || isset($params['columnName']) === false) {
            throw new Exception('Field configuration must have fieldName and columnName properties');
        }

        if (isset($params['primary']) === true && $params['primary'] === true) {
            $this->primaries[$params['columnName']] = $params;
        }

        $this->fields[$params['columnName']] = $params;

    }

    public function ifTableKnown($table, \Closure $callback)
    {
        if ($this->table === $table) {
            $callback($this);
            return true;
        }

        return false;
    }

    public function hasColumn($column)
    {
        if (isset($this->fields[$column]) === true) {
            return true;
        }

        return false;
    }

    public function createEntity()
    {
        $class = substr($this->class, 0, -10); // Remove "Repository" from class
        return new $class;
    }

    public function setEntityPrimary($entity, $value)
    {
        if (count($this->primaries) > 1) {
            throw new Exception('setEntityPrimary can\'be called on multiprimary model');
        }

        $property = 'set' . reset($this->primaries)['fieldName'];
        $entity->$property($value);
        return $this;
    }

    public function setEntityProperty($entity, $column, $value)
    {
        $property = 'set' . $this->fields[$column]['fieldName'];
        $entity->$property($value);
    }

    public function connectMaster(ConnectionPoolInterface $connectionPool, \Closure $callback)
    {
        $this->connect($connectionPool, ConnectionPoolInterface::CONNECTION_MASTER, $callback);
    }

    public function connectSlave(ConnectionPoolInterface $connectionPool, \Closure $callback)
    {
        $this->connect($connectionPool, ConnectionPoolInterface::CONNECTION_SLAVE, $callback);
    }

    public function connect(ConnectionPoolInterface $connectionPool, $connectionType, \Closure $callback)
    {
        $connectionPool->connect($this->connectionName, $this->databaseName, $connectionType, $callback);
    }

    public function generateQueryForPrimary(DriverInterface $driver, $primariesValue, \Closure $callback)
    {
        $columns          = array_keys($this->fields);
        $columns['table'] = $this->table;

        $driver
            ->escapeFields($columns, function ($fields) use (&$sql) {
                $table = $fields['table'];
                unset($fields['table']);

                $sql = 'SELECT ' . implode(', ', $fields) . ' FROM ' . $table;
            });

        $conditions = [];
        if (is_array($primariesValue) === false) {
            $conditions[reset($this->primaries)['columnName']] = $primariesValue;
        }

        $this->generateWhereCondition($driver, $conditions, function ($whereCondition, $values) use ($sql, $callback) {
            $callback($this->queryFactory->get($sql . $whereCondition, $values));
        });
    }

    public function generateQueryForInsert(DriverInterface $driver, $entity, \Closure $callback)
    {
        $columns          = [];
        $columns['table'] = $this->table;

        foreach ($this->fields as $column => $field) {
            $columns[]       = $column;
            $propertyName    = 'get' . $field['fieldName'];
            $values[$column] = $entity->$propertyName();
        }

        $driver
            ->escapeFields(
                $columns,
                function ($fields) use (&$sql, $columns) {
                    $table = $fields['table'];
                    unset($fields['table']);
                    unset($columns['table']);

                    $sql = 'INSERT INTO ' . $table . ' ('
                        . implode($fields, ', ') . ') VALUES (:' . implode($columns, ', :') . ')';
                }
            );

        $callback($this->queryFactory->getPrepared($sql, $values));
    }

    public function generateQueryForUpdate(DriverInterface $driver, $entity, $properties, \Closure $callback)
    {
        $values           = [];
        $columns          = [];
        $columns['table'] = $this->table;

        foreach ($this->fields as $column => $field) {
            if (isset($properties[$field['fieldName']]) === true) {
                $columns[]       = $column;
                $values[$column] = $properties[$field['fieldName']][1];
            }
        }

        $driver
            ->escapeFields(
                $columns,
                function ($fields) use (&$sql, $columns) {
                    $table = $fields['table'];
                    unset($fields['table']);

                    $sqlSet = [];
                    foreach ($fields as $index => $field) {
                        $sqlSet[] = $field . ' = :' . $columns[$index];
                    }

                    $sql = 'UPDATE ' . $table . ' SET ' . implode($sqlSet, ', ');
                }
            );

        $primariesValue = [];
        foreach ($this->primaries as $primary) {
            $propertyName = 'get' . $primary['fieldName'];
            $primariesValue[$primary['columnName']] = $entity->$propertyName();
        }

        $this->generateWhereCondition($driver, $primariesValue,
            function ($whereCondition, $primariesValue) use ($values, $sql, $callback) {
                $callback(
                    $this->queryFactory->getPrepared($sql . $whereCondition, array_merge($values, $primariesValue))
                );
            }
        );
    }

    public function generateQueryForDelete(DriverInterface $driver, $entity, \Closure $callback)
    {
        $driver
            ->escapeFields(
                ['table' => $this->table],
                function ($fields) use (&$sql) {
                    $sql = 'DELETE FROM ' . $fields['table'];
                }
            );

        $values = [];
        foreach ($this->primaries as $primary) {
            $propertyName = 'get' . $primary['fieldName'];
            $values[$primary['columnName']] = $entity->$propertyName();
        }

        $this->generateWhereCondition($driver, $values, function ($whereCondition, $values) use ($sql, $callback) {
            $callback($this->queryFactory->getPrepared($sql . $whereCondition, $values));
        });
    }

    protected function generateWhereCondition(DriverInterface $driver, $values, \Closure $callback)
    {
        $driver
            ->escapeFields(
                array_keys($values),
                function ($fields) use ($values, $callback) {
                    $conditions = [];
                    $i = 0;
                    foreach ($values as $field => $value) {
                        $conditions[] = $fields[$i] . ' = :#' . $field;
                        $values['#' . $field] = $value;
                        unset($values[$field]);
                        $i++;
                    }

                    $callback(' WHERE ' . implode(' AND ', $conditions), $values);
                }
            );
    }
}
