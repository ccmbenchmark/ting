<?php

namespace fastorm\Entity;

use fastorm\Exception;
use fastorm\ConnectionPool;

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
        $this->table = (string) strtolower($tableName);
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

        if (isset($params['id']) === true && $params['id'] === true) {
            if (count($this->primary) > 0) {
                throw new Exception('Primary key has already been setted.');
            }
            $this->primary = array(
                'field' => strtolower($params['fieldName']),
                'column' => strtolower($params['columnName']));
        }

        $this->fields[strtolower($params['columnName'])] = $params;

    }

    public function ifTableKnown($table, callable $callback)
    {
        if ($this->table === strtolower($table)) {
            $callback($this);
            return true;
        }

        return false;
    }

    public function hasColumn($column)
    {
        if (isset($this->fields[strtolower($column)]) === true) {
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
        $property = 'set' . $this->fields[strtolower($column)]['fieldName'];
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
}
