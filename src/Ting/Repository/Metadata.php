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

use CCMBenchmark\Ting\Connection;
use CCMBenchmark\Ting\ConnectionPoolInterface;
use CCMBenchmark\Ting\Exception;
use CCMBenchmark\Ting\Query\Generator;
use CCMBenchmark\Ting\Query\PreparedQuery;
use CCMBenchmark\Ting\Query\QueryFactoryInterface;

class Metadata
{

    protected $connectionName   = null;
    protected $databaseName     = null;
    protected $entity           = null;
    protected $table            = null;
    protected $fields           = [];
    protected $fieldsByProperty = [];
    protected $primaries        = [];
    protected $autoincrement    = null;

    /**
     * Return applicable connection
     * @param ConnectionPoolInterface $connectionPool
     * @return Connection
     */
    public function getConnection(ConnectionPoolInterface $connectionPool)
    {
        return new Connection($connectionPool, $this->connectionName, $this->databaseName);
    }

    /**
     * Set connection name related to configuration
     * @param $connectionName
     * @return $this
     */
    public function setConnectionName($connectionName)
    {
        $this->connectionName = (string) $connectionName;

        return $this;
    }

    /**
     * @param $databaseName
     * @return $this
     */
    public function setDatabase($databaseName)
    {
        $this->databaseName = (string) $databaseName;

        return $this;
    }

    /**
     * Set entity name
     * @throws \CCMBenchmark\Ting\Exception
     */
    public function setEntity($className)
    {
        if (substr($className, 0, 1) === '\\') {
            throw new Exception('Class must not start with a \\');
        }

        $this->entity = (string) $className;
    }

    /**
     * Set table name
     * @param $tableName
     */
    public function setTable($tableName)
    {
        $this->table = (string) $tableName;
    }

    /**
     * Add a field to metadata.
     * @param array $params. Associative array with :
     *      fieldName : string : name of the property on the object
     *      columnName : string : name of the mysql column
     *      primary : boolean : is this field a primary - optional
     *      autoincrement : boolean : is this field an autoincrement - optional
     * @throws \CCMBenchmark\Ting\Exception
     */
    public function addField(array $params)
    {
        if (isset($params['fieldName']) === false || isset($params['columnName']) === false) {
            throw new Exception('Field configuration must have fieldName and columnName properties');
        }

        if (isset($params['primary']) === true && $params['primary'] === true) {
            $this->primaries[$params['columnName']] = $params;

            if (isset($params['autoincrement']) === true && $params['autoincrement'] === true) {
                $this->autoincrement = $params;
            }
        }

        $this->fieldsByProperty[$params['fieldName']] = $params;
        $this->fields[$params['columnName']] = $params;

    }

    /**
     * Execute callback if the provided table is the actual
     * @param          $table
     * @param callable $callback
     * @return bool
     */
    public function ifTableKnown($table, \Closure $callback)
    {
        if ($this->table === $table) {
            $callback($this);
            return true;
        }

        return false;
    }

    /**
     * Returns true if the column is present in this metadata
     * @param $column
     * @return bool
     */
    public function hasColumn($column)
    {
        if (isset($this->fields[$column]) === true) {
            return true;
        }

        return false;
    }

    /**
     * Create a new entity
     * @return mixed
     */
    public function createEntity()
    {
        return new $this->entity;
    }

    /**
     * Set the provided value to autoincrement if applicable
     * @param $entity
     * @param $value
     * @return $this|bool
     */
    public function setEntityPropertyForAutoIncrement($entity, $value)
    {
        if ($this->autoincrement === null) {
            return false;
        }

        $property = 'set' . $this->autoincrement['fieldName'];
        $entity->$property($value);
        return $this;
    }

    /**
     * Set a property to the provided value
     * @param $entity
     * @param $column
     * @param $value
     */
    public function setEntityProperty($entity, $column, $value)
    {
        $property = 'set' . $this->fields[$column]['fieldName'];
        $entity->$property($value);
    }


    /**
     * Return a Query to get one object by it's primaries
     *
     * @param Connection $connection
     * @param QueryFactoryInterface $queryFactory
     * @param CollectionFactoryInterface $collectionFactory
     * @param $primariesKeyValue
     * @param $onMaster boolean
     * @return \CCMBenchmark\Ting\Query\Query
     */
    public function getByPrimaries(
        Connection $connection,
        QueryFactoryInterface $queryFactory,
        CollectionFactoryInterface $collectionFactory,
        $primariesKeyValue,
        $onMaster = false
    ) {
        $fields = array_keys($this->fields);
        $queryGenerator = new Generator(
            $connection,
            $queryFactory,
            $this->table,
            $fields
        );

        $primariesKeyValue = $this->getPrimariesKeyValuesAsArray($primariesKeyValue);

        return $queryGenerator->getByPrimaries($primariesKeyValue, $collectionFactory, $onMaster);
    }

    protected function getPrimariesKeyValuesAsArray($originalValue)
    {
        if (is_array($originalValue) === false) {
            $primariesKeyValue = [];
            if (count($this->primaries) == 1) {
                reset($this->primaries);
                $columnName = key($this->primaries);
                $primariesKeyValue[$columnName] = $originalValue;
                return $primariesKeyValue;
            } else {
                throw new \CCMBenchmark\Ting\Exception('Incorrect format for primaries');
            }
        } else {
            return $originalValue;
        }
    }

    /**
     * Return a query to insert a row in database
     *
     * @param Connection $connection
     * @param QueryFactoryInterface $queryFactory
     * @param $entity
     * @return PreparedQuery
     */
    public function generateQueryForInsert(
        Connection $connection,
        QueryFactoryInterface $queryFactory,
        $entity
    ) {
        $values = [];

        foreach ($this->fields as $column => $field) {
            $propertyName    = 'get' . $field['fieldName'];
            $values[$column] = $entity->$propertyName();
        }

        $fields = array_keys($this->fields);
        $queryGenerator = new Generator(
            $connection,
            $queryFactory,
            $this->table,
            $fields
        );

        return $queryGenerator->insert($values);
    }

    /**
     * Return a query to update a row in database
     *
     * @param Connection            $connection
     * @param QueryFactoryInterface $queryFactory
     * @param                       $entity
     * @param                       $properties
     * @return PreparedQuery
     */
    public function generateQueryForUpdate(
        Connection $connection,
        QueryFactoryInterface $queryFactory,
        $entity,
        $properties
    ) {
        $queryGenerator = new Generator(
            $connection,
            $queryFactory,
            $this->table,
            array_keys($properties)
        );

        // Get new values affected to entity
        $values = [];
        foreach ($properties as $name => $value) {
            $columnName = $this->fieldsByProperty[$name]['columnName'];

            // 0 means old value, 1 means new value
            $values[$columnName] = $value[1];
        }

        $primariesKeyValue = $this->getPrimariesKeyValuesByProperties($properties, $entity);

        return $queryGenerator->update($values, $primariesKeyValue);
    }

    /**
     * Return a query to delete a row from database
     *
     * @param Connection            $connection
     * @param QueryFactoryInterface $queryFactory
     * @param                       $properties
     * @param                       $entity
     * @return PreparedQuery
     */
    public function generateQueryForDelete(
        Connection $connection,
        QueryFactoryInterface $queryFactory,
        $properties,
        $entity
    ) {
        $queryGenerator = new Generator(
            $connection,
            $queryFactory,
            $this->table,
            array_keys($properties)
        );

        $primariesKeyValue = $this->getPrimariesKeyValuesByProperties($properties, $entity);

        return $queryGenerator->delete($primariesKeyValue);
    }

    protected function getPrimariesKeyValuesByProperties($properties, $entity)
    {
        $primariesKeyValue = [];
        foreach ($this->primaries as $key => $primary) {
            $fieldName = $this->fields[$key]['fieldName'];
            // Key value has been updated : we need the old one
            if (isset($properties[$fieldName]) === true) {
                $primariesKeyValue[$key] = $properties[$fieldName][0];
            } else {
                // No update, get the actual
                $propertyName = 'get' . $primary['fieldName'];
                $primariesKeyValue[$key] = $entity->$propertyName();
            }
        }
        return $primariesKeyValue;
    }
}
