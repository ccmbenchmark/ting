<?php

namespace fastorm\Entity;

use fastorm\Driver\DriverInterface;
use fastorm\Exception;
use fastorm\ConnectionPool;
use fastorm\Query;

class Metadata
{

    protected $connectionName = null;
    protected $databaseName   = null;
    protected $class          = null;
    protected $table          = null;
    protected $fields         = array();
    protected $primary        = array();

    public function setConnection($connectionName)
    {
        $this->connectionName = (string) $connectionName;
    }

    public function setDatabase($databaseName)
    {
        $this->databaseName = (string) $databaseName;
    }

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
     * @throws \fastorm\Exception
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

    public function ifTableKnown($table, callable $callback)
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

    public function createObject()
    {
        $class = substr($this->class, 0, -10); // Remove "Repository" from class
        return new $class;
    }

    public function setObjectProperty($object, $column, $value)
    {
        $property = 'set' . $this->fields[$column]['fieldName'];
        $object->$property($value);
    }

    public function addInto(MetadataRepository $metadataRepository)
    {
        $metadataRepository->add($this->class, $this);
    }

    public function connect(ConnectionPool $connectionPool, callable $callback)
    {
        $connectionPool->connect($this->connectionName, $this->databaseName, $callback);
    }

    public function generateQueryForPrimary(DriverInterface $driver, $primaryValue, callable $callback)
    {
        $driver
            ->escapeField($this->table, function ($field) use (&$sql) {
                $sql = 'SELECT * FROM ' . $field;
            })
            ->escapeField($this->primary['column'], function ($field) use (&$sql) {
            $sql .= ' WHERE ' . $field . ' = :primary';
            });

        $callback(new Query($sql, array('primary' => $primaryValue)));
    }
}
