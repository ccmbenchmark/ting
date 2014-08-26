<?php

namespace fastorm\Entity;

use fastorm\ConnectionPoolInterface;
use fastorm\Driver\DriverInterface;
use fastorm\Exception;
use fastorm\PreparedQuery;
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

    /**
     * @throws \fastorm\Exception
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

    public function addInto(MetadataRepository $metadataRepository)
    {
        $metadataRepository->add($this->class, $this);
    }

    public function connect(ConnectionPoolInterface $connectionPool, callable $callback)
    {
        $connectionPool->connect($this->connectionName, $this->databaseName, $callback);
    }

    public function generateQueryForPrimary(DriverInterface $driver, $primaryValue, callable $callback)
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

        $callback(new Query($sql, array('primary' => $primaryValue)));
    }

    public function generateQueryForInsert(DriverInterface $driver, $entity, callable $callback)
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

        $callback(new PreparedQuery($sql, $values));
    }

    public function generateQueryForUpdate(DriverInterface $driver, $entity, $properties, callable $callback)
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

        $callback(new PreparedQuery($sql, $values));
    }

    public function generateQueryForDelete(DriverInterface $driver, $entity, callable $callback)
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

        $callback(new PreparedQuery($sql, $values));
    }
}
