<?php

namespace fastorm\Entity;

use fastorm\Exception;

class Metadata
{

    protected $connectionName = null;
    protected $databaseName = null;
    protected $table = null;
    protected $fields = array();
    protected $primary = array();

    public function setConnection($connectionName)
    {
        $this->connectionName = (string) $connectionName;
    }

    public function getConnection()
    {
        return (string) $this->connectionName;
    }

    public function setDatabase($databaseName)
    {
        $this->databaseName = (string) $databaseName;
    }

    public function getDatabase()
    {
        return (string) $this->databaseName;
    }

    public function setTable($tableName)
    {
        $this->table = (string) $tableName;
    }

    public function getTable()
    {
        return (string) $this->table;
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

    public function getPrimary()
    {
        return $this->primary;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function getFieldsName()
    {
        return array_map(
            function ($field) {
                return $field['columnName'];
            },
            $this->fields
        );
    }
}
