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

namespace CCMBenchmark\Ting;

use CCMBenchmark\Ting\Driver\DriverInterface;
use CCMBenchmark\Ting\Entity\NotifyPropertyInterface;
use CCMBenchmark\Ting\Entity\PropertyListenerInterface;
use CCMBenchmark\Ting\Query\QueryFactoryInterface;
use CCMBenchmark\Ting\Repository\Metadata;

class UnitOfWork implements PropertyListenerInterface
{
    const STATE_NEW     = 1;
    const STATE_MANAGED = 2;
    const STATE_DELETE  = 3;

    protected $connectionPool            = null;
    protected $metadataRepository        = null;
    protected $queryFactory              = null;
    protected $entities                  = array();
    protected $entitiesManaged           = array();
    protected $entitiesChanged           = array();
    protected $entitiesShouldBePersisted = array();
    protected $statements = array();

    /**
     * @param ConnectionPool        $connectionPool
     * @param MetadataRepository    $metadataRepository
     * @param QueryFactoryInterface $queryFactory
     */
    public function __construct(
        ConnectionPool $connectionPool,
        MetadataRepository $metadataRepository,
        QueryFactoryInterface $queryFactory
    ) {
        $this->connectionPool     = $connectionPool;
        $this->metadataRepository = $metadataRepository;
        $this->queryFactory       = $queryFactory;
    }

    /**
     * Watch changes on provided entity
     *
     * @param $entity
     */
    public function manage($entity)
    {
        $oid = spl_object_hash($entity);
        $this->entitiesManaged[$oid] = true;
        if ($entity instanceof NotifyPropertyInterface) {
            $entity->addPropertyListener($this);
        }
    }

    /**
     * @param $entity
     * @return bool - true if the entity is managed
     */
    public function isManaged($entity)
    {
        if (isset($this->entitiesManaged[spl_object_hash($entity)]) === true) {
            return true;
        }

        return false;
    }

    /**
     * @param $entity
     * @return bool - true if the entity has not been persisted yet
     */
    public function isNew($entity)
    {
        $oid = spl_object_hash($entity);
        if (
            isset($this->entitiesShouldBePersisted[$oid]) === true
            && $this->entitiesShouldBePersisted[$oid] === self::STATE_NEW
        ) {
            return true;
        }
        return false;
    }

    /**
     * Flag the entity to be persisted (insert or update) on next process
     *
     * @param $entity
     * @return $this
     */
    public function pushSave($entity)
    {
        $oid   = spl_object_hash($entity);
        $state = self::STATE_NEW;
        if (isset($this->entitiesManaged[$oid]) === true) {
            $state = self::STATE_MANAGED;
        }

        $this->entitiesShouldBePersisted[$oid] = $state;
        $this->entities[$oid] = $entity;

        return $this;
    }

    /**
     * @param $entity
     * @return bool
     */
    public function shouldBePersisted($entity)
    {
        if (isset($this->entitiesShouldBePersisted[spl_object_hash($entity)]) === true) {
            return true;
        }

        return false;
    }

    /**
     * @param $entity
     * @param $propertyName
     * @param $oldValue
     * @param $newValue
     */
    public function propertyChanged($entity, $propertyName, $oldValue, $newValue)
    {
        if ($oldValue === $newValue) {
            return;
        }

        $oid = spl_object_hash($entity);

        if (isset($this->entitiesChanged[$oid]) === false) {
            $this->entitiesChanged[$oid] = array();
        }

        if (isset($this->entitiesChanged[$oid][$propertyName]) === false) {
            $this->entitiesChanged[$oid][$propertyName] = array($oldValue, null);
        }

        $this->entitiesChanged[$oid][$propertyName][1] = $newValue;
    }

    /**
     * @param $entity
     * @param $propertyName
     * @return bool
     */
    public function isPropertyChanged($entity, $propertyName)
    {
        if (isset($this->entitiesChanged[spl_object_hash($entity)][$propertyName]) === true) {
            return true;
        }

        return false;
    }

    /**
     * Stop watching changes on the entity
     *
     * @param $entity
     */
    public function detach($entity)
    {
        $oid = spl_object_hash($entity);
        unset($this->entitiesChanged[$oid]);
        unset($this->entitiesShouldBePersisted[$oid]);
        unset($this->entities[$oid]);
    }

    /**
     * Flag the entity to be deleted on next process
     *
     * @param $entity
     * @return $this
     */
    public function pushDelete($entity)
    {
        $oid = spl_object_hash($entity);
        $this->entitiesShouldBePersisted[$oid] = self::STATE_DELETE;
        $this->entities[$oid] = $entity;

        return $this;
    }

    /**
     * Returns true if delete($entity) has been called
     *
     * @param $entity
     * @return bool
     */
    public function shouldBeRemoved($entity)
    {
        $oid = spl_object_hash($entity);
        if (
            isset($this->entitiesShouldBePersisted[$oid]) === true
            && $this->entitiesShouldBePersisted[$oid] === self::STATE_DELETE
        ) {
            return true;
        }

        return false;
    }

    /**
     * Apply flagged changes against the database:
     * Save flagged new entities
     * Update flagged entities
     * Delete flagged entities
     */
    public function process()
    {
        foreach ($this->entitiesShouldBePersisted as $oid => $state) {
            switch ($state) {
                case self::STATE_MANAGED:
                    $this->processManaged($oid);
                    break;

                case self::STATE_NEW:
                    $this->processNew($oid);
                    break;

                case self::STATE_DELETE:
                    $this->processDelete($oid);
                    break;
            }
        }
        foreach ($this->statements as $statementName => $connections) {
            foreach ($connections as $connection) {
                $connection->closeStatement($statementName);
            }
        }
    }

    /**
     * Update all applicable entities in database
     *
     * @param $oid
     * @throws Exception
     */
    protected function processManaged($oid)
    {
        if (isset($this->entitiesChanged[$oid]) === false) {
            return;
        }

        $entity = $this->entities[$oid];
        $properties = array();
        foreach ($this->entitiesChanged[$oid] as $property => $values) {
            if ($values[0] !== $values[1]) {
                $properties[$property] = $values;
            }
        }

        if ($properties === []) {
            return;
        }

        $this->metadataRepository->findMetadataForEntity(
            $entity,
            function (Metadata $metadata) use ($entity, $properties, $oid) {
                $connection = $metadata->getConnection($this->connectionPool);
                $query = $metadata->generateQueryForUpdate(
                    $connection,
                    $this->queryFactory,
                    $entity,
                    $properties
                );

                $this->addStatementToClose($query->getStatementName(), $connection->master());
                $query->prepareExecute()->execute();

                unset($this->entitiesChanged[$oid]);
                unset($this->entitiesShouldBePersisted[$oid]);
            },
            function () use ($entity) {
                throw new Exception('Could not find repository matching entity "' . get_class($entity) . '"');
            }
        );
    }

    /**
     * Insert all applicable entities in database
     *
     * @param $oid
     * @throws Exception
     */
    protected function processNew($oid)
    {
        $entity = $this->entities[$oid];

        $this->metadataRepository->findMetadataForEntity(
            $entity,
            function (Metadata $metadata) use ($entity, $oid) {
                $connection = $metadata->getConnection($this->connectionPool);
                $query = $metadata->generateQueryForInsert(
                    $connection,
                    $this->queryFactory,
                    $entity
                );
                $this->addStatementToClose($query->getStatementName(), $connection->master());
                $query->prepareExecute()->execute();

                $metadata->setEntityPropertyForAutoIncrement($entity, $connection->master()->getInsertId());

                unset($this->entitiesChanged[$oid]);
                unset($this->entitiesShouldBePersisted[$oid]);

                $this->manage($entity);
            },
            function () use ($entity) {
                throw new Exception('Could not find repository matching entity "' . get_class($entity) . '"');
            }
        );
    }

    /**
     * Delete all flagged entities from database
     *
     * @param $oid
     * @throws Exception
     */
    protected function processDelete($oid)
    {
        $entity = $this->entities[$oid];
        $properties = [];
        if (isset($this->entitiesChanged[$oid])) {
            foreach ($this->entitiesChanged[$oid] as $property => $values) {
                if ($values[0] !== $values[1]) {
                    $properties[$property] = $values;
                }
            }
        }

        $this->metadataRepository->findMetadataForEntity(
            $entity,
            function (Metadata $metadata) use ($entity, $properties) {
                $connection = $metadata->getConnection($this->connectionPool);
                $query = $metadata->generateQueryForDelete(
                    $connection,
                    $this->queryFactory,
                    $properties,
                    $entity
                );
                $this->addStatementToClose($query->getStatementName(), $connection->master());
                $query->prepareExecute()->execute();
                $this->detach($entity);
            },
            function () use ($entity) {
                throw new Exception('Could not find repository matching entity "' . get_class($entity) . '"');
            }
        );
    }

    protected function addStatementToClose($statementName, DriverInterface $connection)
    {
        if (isset($this->statements[$statementName]) === false) {
            $this->statements[$statementName] = array();
        }
        if (isset($this->statements[$statementName][spl_object_hash($connection)]) === false) {
            $this->statements[$statementName][spl_object_hash($connection)] = $connection;
        }
    }
}
