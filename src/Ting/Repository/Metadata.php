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

use CCMBenchmark\Ting\Serializer\DateTime;
use CCMBenchmark\Ting\Serializer\DateTimeImmutable;
use CCMBenchmark\Ting\Serializer\DateTimeZone;
use CCMBenchmark\Ting\Serializer\Json;
use CCMBenchmark\Ting\Serializer\Ip;
use CCMBenchmark\Ting\Serializer\Geometry;
use CCMBenchmark\Ting\Serializer\Uuid;
use CCMBenchmark\Ting\Serializer\SerializerFactoryInterface;
use Closure;
use CCMBenchmark\Ting\Query\Query;
use CCMBenchmark\Ting\Query\QueryInterface;
use CCMBenchmark\Ting\Connection;
use CCMBenchmark\Ting\ConnectionPoolInterface;
use CCMBenchmark\Ting\Driver\DriverInterface;
use CCMBenchmark\Ting\Exception;
use CCMBenchmark\Ting\Exceptions\ConfigException;
use CCMBenchmark\Ting\Exceptions\SyntaxException;
use CCMBenchmark\Ting\Exceptions\ValueException;
use CCMBenchmark\Ting\Query\Generator;
use CCMBenchmark\Ting\Query\PreparedQuery;
use CCMBenchmark\Ting\Query\QueryFactoryInterface;
use CCMBenchmark\Ting\Query\QueryInterface;
use CCMBenchmark\Ting\Serializer;
use CCMBenchmark\Ting\Util\PropertyAccessor;

/**
 * @template T
 * @phpstan-type Field array{
 *     fieldName: string,
 *     columnName: string,
 *     type: string,
 *     primary?: bool,
 *     autoincrement?: bool,
 *     serializer?: class-string<Serializer\SerializerInterface>,
 *     serializer_options?: array{serialize?: array<mixed>, unserialize?: array<mixed>},
 *     setter?: string,
 *     getter?: string
 * }
 */
class Metadata
{
    protected $connectionName     = null;
    protected $databaseName       = null;
    /** @var class-string<Repository<T>>|null */
    protected $repository         = null;
    /** @var class-string<T>|null */
    protected $entity             = null;
    protected $table              = null;
    protected $schemaName         = '';
    /** @phpstan-var array<string, Field> */
    protected $fields             = [];
    /** @phpstan-var array<string, Field> */
    protected $fieldsByProperty   = [];
    protected $primaries          = [];
    protected $autoincrement      = null;
    protected $defaultSerializers = [
        'datetime' => DateTime::class,
        'datetime_immutable' => DateTimeImmutable::class,
        'datetimezone' => DateTimeZone::class,
        'json'     => Json::class,
        'ip'       => Ip::class,
        'geometry' => Geometry::class,
        'uuid'     => Uuid::class,
    ];
    public PropertyAccessor $propertyAccessor;

    /**
     * @param Serializer\SerializerFactoryInterface $serializerFactory
     */
    public function __construct(private readonly SerializerFactoryInterface $serializerFactory)
    {
        $this->propertyAccessor = new PropertyAccessor();
    }

    /**
     * Return applicable connection
     * @param ConnectionPoolInterface $connectionPool
     * @return Connection
     *
     * @internal
     */
    public function getConnection(ConnectionPoolInterface $connectionPool): Connection
    {
        return new Connection($connectionPool, $this->connectionName, $this->databaseName);
    }

    /**
     * Set connection name related to configuration
     * @param string $connectionName
     * @return $this
     */
    public function setConnectionName($connectionName): static
    {
        $this->connectionName = (string) $connectionName;

        return $this;
    }

    /**
     * Retrieve the connection name
     * @return string
     */
    public function getConnectionName()
    {
        return $this->connectionName;
    }

    /**
     * @param string $databaseName
     * @return $this
     *
     */
    public function setDatabase($databaseName): static
    {
        $this->databaseName = (string) $databaseName;

        return $this;
    }

    /**
     * @return string
     *
     */
    public function getDatabase()
    {
        return $this->databaseName;
    }

    /**
     * Set repository name
     * @param class-string<Repository<T>> $className
     * @return $this
     * @throws SyntaxException
     */
    public function setRepository($className): static
    {
        if (($className[0] ?? '') === '\\') {
            throw new SyntaxException('Class must not start with a \\');
        }

        $this->repository = (string) $className;

        return $this;
    }

    /**
     * @return class-string<Repository<T>>|null
     *
     * @internal
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Set entity name
     * @param class-string<T> $className
     * @return $this
     * @throws SyntaxException
     */
    public function setEntity($className): static
    {
        if (($className[0] ?? '') === '\\') {
            throw new SyntaxException('Class must not start with a \\');
        }

        $this->entity = (string) $className;

        return $this;
    }

    /**
     * @return class-string<T>
     *
     * @internal
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set table name
     * @param string $tableName
     * @return $this
     */
    public function setTable($tableName): static
    {
        $this->table = (string) $tableName;

        return $this;
    }

    /**
     * Get table name
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param string $schemaName
     * @return $this
     *
     */
    public function setSchema($schemaName): static
    {
        $this->schemaName = (string) $schemaName;

        return $this;
    }

    /**
     * Get schema name
     * @return string
     */
    public function getSchema()
    {
        return $this->schemaName;
    }


    /**
     * Add a field to metadata.
     * @param array $params. Associative array with :
     *      fieldName : string : name of the property on the object
     *      columnName : string : name of the mysql column
     *      primary : boolean : is this field a primary - optional
     *      autoincrement : boolean : is this field an autoincrement - optional
     * @throws ConfigException
     * @return $this
     */
    public function addField(array $params): static
    {
        if (isset($params['fieldName']) === false) {
            throw new ConfigException('Field configuration must have "fieldName" property');
        }

        if (isset($params['columnName']) === false) {
            throw new ConfigException('Field configuration must have "columnName" property');
        }

        if (isset($params['type']) === false) {
            throw new ConfigException('Field configuration must have "type" property');
        }

        if (isset($params['primary']) && $params['primary'] === true) {
            $this->primaries[$params['columnName']] = $params;

            if (isset($params['autoincrement']) && $params['autoincrement'] === true) {
                $this->autoincrement = $params;
            }
        }

        if (isset($params['serializer']) === false && isset($this->defaultSerializers[$params['type']])) {
            $params['serializer'] = $this->defaultSerializers[$params['type']];
        }

        $this->fieldsByProperty[$params['fieldName']] = $params;
        $this->fields[$params['columnName']] = $params;

        return $this;
    }

    /**
     * Retrieve all defined primaries.
     *
     * @return array
     */
    public function getPrimaries()
    {
        return $this->primaries;
    }

    /**
     * Retrieve all defined fields.
     *
     * @return list<Field>
     */
    public function getFields()
    {
        return array_values($this->fields);
    }

    /**
     * Execute callback if the provided table is the actual
     * @param string   $connectionName
     * @param string   $database
     * @param string   $table
     * @param Closure $callback
     * @return bool
     *
     * @internal
     */
    public function ifTableKnown($connectionName, $database, $table, Closure $callback): bool
    {
        if ($this->table === $table
            && $this->connectionName === $connectionName && $this->databaseName === $database
        ) {
            $callback($this);
            return true;
        }

        return false;
    }

    /**
     * Returns true if the column is present in this metadata
     *
     * @internal
     */
    public function hasColumn(string $column): bool
    {
        return isset($this->fields[$column]);
    }

    /**
     * Create a new entity
     * @return T
     *
     * @internal
     */
    public function createEntity()
    {
        return new $this->entity();
    }

    /**
     * Set the provided value to autoincrement if applicable
     * @internal
     */
    public function setEntityPropertyForAutoIncrement(object $entity, DriverInterface $driver): Metadata|false
    {
        if ($this->autoincrement === null) {
            return false;
        }

        if (method_exists($driver, 'getInsertedIdForSequence')
            && isset($this->autoincrement['sequenceName'])
        ) {
            $insertId = $driver->getInsertedIdForSequence($this->autoincrement['sequenceName']);
        } else {
            $insertId = $driver->getInsertedId();
        }

        $this->propertyAccessor->setValue($entity, $this->autoincrement['fieldName'], $insertId, $this->fieldsByProperty[$this->autoincrement['fieldName']]['setter'] ?? null);
        return $this;
    }

    /**
     * Set a property to the provided value
     *
     * @internal
     */
    public function setEntityProperty(object $entity, string $column, mixed $value): void
    {
        if (isset($this->fields[$column]['serializer'])) {
            $options = [];

            if (isset($this->fields[$column]['serializer_options']['unserialize'])) {
                $options = $this->fields[$column]['serializer_options']['unserialize'];
            }
            $value = $this->serializerFactory->get($this->fields[$column]['serializer'])->unserialize($value, $options);
        } elseif ($value !== null) {
            switch ($this->fields[$column]['type']) {
                case "int":
                    $value = (int) $value;
                    break;
                case "double":
                    $value = (float) $value;
                    break;
                case "bool":
                    $value = (bool) $value;
                    break;
            }
        }

        $this->propertyAccessor->setValue($entity, $this->fields[$column]['fieldName'], $value, $this->fields[$column]['setter'] ?? null);
    }

    /**
     * Retrieve property of entity according to the field (unserialize if needed)
     * @param object $entity
     * @param array $field
     * @return mixed
     *
     */
    protected function getEntityProperty(object $entity, $field): mixed
    {
        $value = $this->propertyAccessor->getValue($entity, $field['fieldName'], $field['getter'] ?? null);

        if (isset($field['serializer'])) {
            $options = [];

            if (isset($field['serializer_options']['serialize'])) {
                $options = $field['serializer_options']['serialize'];
            }
            $value = $this->serializerFactory->get($field['serializer'])->serialize($value, $options);
        }

        return $value;
    }
    
    public function getEntityPropertyByFieldName(object $entity, string $fieldName): mixed
    {
        $field = $this->fieldsByProperty[$fieldName];
        return $this->getEntityProperty($entity, $field);
    }
    
    /**
     * Return a Query to get one object by it's primaries
     *
     * @internal
     */
    public function getByPrimaries(
        Connection $connection,
        QueryFactoryInterface $queryFactory,
        CollectionFactoryInterface $collectionFactory,
        mixed $primariesKeyValue,
        bool $forceMaster = false
    ): QueryInterface {
        $fields = array_keys($this->fields);
        $queryGenerator = new Generator(
            $connection,
            $queryFactory,
            $this->schemaName,
            $this->table,
            $fields
        );

        $primariesKeyValue = $this->getPrimariesKeyValuesAsArray($primariesKeyValue);

        return $queryGenerator->getOneByCriteria($primariesKeyValue, $collectionFactory, $forceMaster);
    }


    /**
     * Return a Query to get one object by an associative array of criterias
     *
     * @return QueryInterface<T>
     *
     * @internal
     */
    public function getOneByCriteria(
        Connection $connection,
        QueryFactoryInterface $queryFactory,
        CollectionFactoryInterface $collectionFactory,
        array $criteria,
        bool $forceMaster = false
    ): QueryInterface {
        $fields = array_keys($this->fields);
        $queryGenerator = new Generator(
            $connection,
            $queryFactory,
            $this->schemaName,
            $this->table,
            $fields
        );

        $criteriaColumn = $this->getColumnsFromCriteria($criteria);

        return $queryGenerator->getOneByCriteria($criteriaColumn, $collectionFactory, $forceMaster);
    }

    /**
     * @param array $criteria
     * @return array
     * @throws ValueException
     */
    protected function getColumnsFromCriteria(array $criteria): array
    {
        $criteriaColumn = [];
        foreach ($criteria as $property => $value) {
            if (isset($this->fieldsByProperty[$property]) === false) {
                throw new ValueException(sprintf('Undefined property %s in your criteria', $property));
            }
            $column = $this->fieldsByProperty[$property]['columnName'];
            $criteriaColumn[$column] = $value;
        }

        return $criteriaColumn;
    }

    /**
     * Retrieve all lines from the table
     *
     * @param Connection                 $connection
     * @param QueryFactoryInterface      $queryFactory
     * @param CollectionFactoryInterface $collectionFactory
     * @param bool                       $forceMaster
     * @return QueryInterface
     *
     * @internal
     */
    public function getAll(
        Connection $connection,
        QueryFactoryInterface $queryFactory,
        CollectionFactoryInterface $collectionFactory,
        $forceMaster = false
    ) {
        $fields = array_keys($this->fields);
        $queryGenerator = new Generator(
            $connection,
            $queryFactory,
            $this->schemaName,
            $this->table,
            $fields
        );

        return $queryGenerator->getAll($collectionFactory, $forceMaster);
    }

    /**
     * Retrieve matching lines from the table, according to the criteria
     *
     * @internal
     */
    public function getByCriteria(
        array $criteria,
        Connection $connection,
        QueryFactoryInterface $queryFactory,
        CollectionFactoryInterface $collectionFactory,
        bool $forceMaster = false
    ): QueryInterface {
        $fields = array_keys($this->fields);
        $queryGenerator = new Generator(
            $connection,
            $queryFactory,
            $this->schemaName,
            $this->table,
            $fields
        );

        $criteriaColumn = $this->getColumnsFromCriteria($criteria);

        return $queryGenerator->getByCriteria($criteriaColumn, $collectionFactory, $forceMaster);
    }

    public function getByCriteriaWithOrderAndLimit(
        array $criteria,
        array $orderBy,
        int $limit,
        Connection $connection,
        QueryFactoryInterface $queryFactory,
        CollectionFactoryInterface $collectionFactory,
        bool $forceMaster = false
    ): QueryInterface {
        $fields = array_keys($this->fields);
        $queryGenerator = new Generator(
            $connection,
            $queryFactory,
            $this->schemaName,
            $this->table,
            $fields
        );
        $criteriaColumn = $this->getColumnsFromCriteria($criteria);

        return $queryGenerator->getByCriteria($criteriaColumn, $collectionFactory, $forceMaster, $orderBy, $limit);
    }

    /**
     * @param $originalValue
     * @return array
     * @throws Exception
     */
    protected function getPrimariesKeyValuesAsArray(mixed $originalValue): array
    {
        if (is_array($originalValue) === false) {
            $primariesKeyValue = [];
            if (count($this->primaries) == 1) {
                $columnName = array_key_first($this->primaries);
                $primariesKeyValue[$columnName] = $originalValue;
                return $primariesKeyValue;
            }
            throw new Exception('Incorrect format for primaries');
        }
        return $originalValue;
    }

    /**
     * Return a query to insert a row in database
     *
     * @param Connection $connection
     * @param QueryFactoryInterface $queryFactory
     * @param $entity
     * @return PreparedQuery
     *
     * @internal
     */
    public function generateQueryForInsert(
        Connection $connection,
        QueryFactoryInterface $queryFactory,
        object $entity
    ) {
        $values = [];

        foreach ($this->fields as $column => $field) {
            if ($field['autoincrement'] ?? false) {
                continue;
            }
            
            // Public typed properties non initialized is non-readable
            // In this case we don't insert it, relying on database default value
            if ($this->propertyAccessor->isReadable($entity, $field['fieldName'], $field['getter'] ?? null)) {
                $values[$column] = $this->getEntityProperty($entity, $field);
            }
        }

        $fields = array_keys($this->fields);
        $queryGenerator = new Generator(
            $connection,
            $queryFactory,
            $this->schemaName,
            $this->table,
            $fields
        );

        return $queryGenerator->insert($values);
    }

    /**
     * Return a query to update a row in database
     *
     * @param $array<string, mixed> $properties
     *
     * @internal
     */
    public function generateQueryForUpdate(
        Connection $connection,
        QueryFactoryInterface $queryFactory,
        object $entity,
        array $properties
    ): PreparedQuery {
        $queryGenerator = new Generator(
            $connection,
            $queryFactory,
            $this->schemaName,
            $this->table,
            array_keys($properties)
        );

        // Get new values affected to entity
        $values = [];
        foreach ($properties as $name => $value) {
            $columnName = $this->fieldsByProperty[$name]['columnName'];

            // Public typed properties non initialized is non-readable
            // In this case we don't update it, so it will keep the current value
            if ($this->propertyAccessor->isReadable($entity, $this->fieldsByProperty[$name]['fieldName'], $this->fieldsByProperty[$name]['getter'] ?? null)) {
                $values[$columnName] = $this->getEntityProperty($entity, $this->fieldsByProperty[$name]);
            }
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
     *
     * @internal
     */
    public function generateQueryForDelete(
        Connection $connection,
        QueryFactoryInterface $queryFactory,
        array $properties,
        object $entity
    ) {
        $queryGenerator = new Generator(
            $connection,
            $queryFactory,
            $this->schemaName,
            $this->table,
            array_keys($properties)
        );

        $primariesKeyValue = $this->getPrimariesKeyValuesByProperties($properties, $entity);

        return $queryGenerator->delete($primariesKeyValue);
    }

    /**
     * @param array<string, mixed> $properties
     * @return array
     */
    protected function getPrimariesKeyValuesByProperties(array $properties, object $entity): array
    {
        $primariesKeyValue = [];
        foreach ($this->primaries as $key => $primary) {
            $fieldName = $this->fields[$key]['fieldName'];
            // Key value has been updated : we need the old one
            if (isset($properties[$fieldName])) {
                $primariesKeyValue[$key] = $properties[$fieldName];
            } else {
                // No update, get the actual
                $primariesKeyValue[$key] = $this->propertyAccessor->getValue($entity, $primary['fieldName']);
            }
        }
        return $primariesKeyValue;
    }
}
