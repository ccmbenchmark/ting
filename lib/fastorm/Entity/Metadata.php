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

        if (isset($params['id']) === true && $params['id'] === true) {
            if (count($this->primary) > 0) {
                throw new Exception('Primary key has already been setted.');
            }
            $this->primary = array('field' => $params['fieldName'], 'column' => $params['columnName']);
        }

        $this->fields[$params['columnName']] = $params;

    }

    public function ifTableKnown($table, callable $callback)
    {
        if (strtolower($this->table) === strtolower($table)) {
            $callback($this);
        }
    }

    public function createObject()
    {
        $class = substr($this->class, 0, -10); // Remove "Repository" from class
        return new $class;
    }

    public function setObjectProperty($object, $column, $value)
    {
        if (get_class($object) !== substr($this->class, 0, -10)) {
            throw new Exception('setObjectProperty must be called on object of the Metadata\'s repository');
        }

        foreach ($this->fields as $fieldColumn => $field) {
            if (strtolower($fieldColumn) === strtolower($column)) {
                $property = 'set' . ucfirst(strtolower($field['fieldName']));
                $object->$property($value);
                break;
            }
        }
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
