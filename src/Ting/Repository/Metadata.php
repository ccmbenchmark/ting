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
    protected $fields         = array();
    protected $primary        = array();

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
            if (count($this->primary) > 0) {
                throw new Exception('Primary key has already been setted.');
            }
            $this->primary = array(
                'field'  => $params['fieldName'],
                'column' => $params['columnName']
            );
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
        $property = 'set' . $this->primary['field'];
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

    public function connect(ConnectionPoolInterface $connectionPool, $connexionType, \Closure $callback)
    {
        $connectionPool->connect($this->connectionName, $this->databaseName, $connexionType, $callback);
    }

    public function generateQueryForPrimary(DriverInterface $driver, $primaryValue, \Closure $callback)
    {
        $fields = array_keys($this->fields);
        array_unshift($fields, $this->table);

        $driver
            ->escapeFields($fields, function ($fields) use (&$sql) {
                $table = $fields[0];
                unset($fields[0]);

                $sql = 'SELECT ' . implode(', ', $fields) . ' FROM ' . $table;
            })
            ->escapeFields(array($this->primary['column']), function ($fields) use (&$sql) {
                $sql .= ' WHERE ' . $fields[0] . ' = :primary';
            });

        $callback($this->queryFactory->get($sql, ['primary' => $primaryValue]));
    }

    public function generateQueryForInsert(DriverInterface $driver, $entity, \Closure $callback)
    {

        $columns = array();
        $columns['table'] = $this->table;

        foreach ($this->fields as $column => $field) {
            $columns[] = $column;
            $propertyName = 'get' . $field['fieldName'];
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
        $values  = array();
        $columns = array();
        $columns['table']   = $this->table;
        $columns['primary'] = $this->primary['column'];

        $propertyName = 'get' . $this->primary['field'];
        $values[$this->primary['column']]  = $entity->$propertyName();

        foreach ($this->fields as $column => $field) {
            if (in_array($field['fieldName'], $properties) === true) {
                $columns[] = $column;
                $propertyName = 'get' . $field['fieldName'];
                $values[$column] = $entity->$propertyName();
            }
        }

        $driver
            ->escapeFields(
                $columns,
                function ($fields) use (&$sql, $columns) {
                    $table = $fields['table'];
                    unset($fields['table']);

                    $primary = $fields['primary'];
                    unset($fields['primary']);

                    $sqlSet = array();
                    foreach ($fields as $index => $field) {
                        $sqlSet[] = $field . ' = :' . $columns[$index];
                    }

                    $sql = 'UPDATE ' . $table . ' SET ' . implode($sqlSet, ', ')
                        . ' WHERE ' . $primary . ' = :' . $columns['primary'];
                }
            );

        $callback($this->queryFactory->getPrepared($sql, $values));
    }

    public function generateQueryForDelete(DriverInterface $driver, $entity, \Closure $callback)
    {
        $values  = array();
        $columns = array();
        $columns['table']   = $this->table;
        $columns['primary'] = $this->primary['column'];

        $propertyName = 'get' . $this->primary['field'];
        $values[$this->primary['column']]  = $entity->$propertyName();

        $driver
            ->escapeFields(
                $columns,
                function ($fields) use (&$sql, $columns) {
                    $sql = 'DELETE FROM ' . $fields['table']
                        . ' WHERE ' . $fields['primary'] . ' = :' . $columns['primary'];
                }
            );

        $callback($this->queryFactory->getPrepared($sql, $values));
    }
}
