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
use CCMBenchmark\Ting\Driver\QueryException;
use CCMBenchmark\Ting\Entity\NotifyPropertyInterface;
use CCMBenchmark\Ting\Entity\PropertyListenerInterface;
use CCMBenchmark\Ting\Query\QueryFactoryInterface;
use CCMBenchmark\Ting\Repository\Metadata;
use WeakMap;

class UnitOfWork implements PropertyListenerInterface
{
    const STATE_NEW     = 1;
    const STATE_MANAGED = 2;
    const STATE_DELETE  = 3;

    protected $connectionPool            = null;
    protected $metadataRepository        = null;
    protected $queryFactory              = null;
    protected WeakMap $entities;
    protected WeakMap $entitiesChanged;
    protected WeakMap $entitiesShouldBePersisted;
    protected $statements = [];

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
        $this->entities = new WeakMap();
        $this->entitiesChanged = new WeakMap();
        $this->entitiesShouldBePersisted = new WeakMap();
    }

    /**
     * @return string
     */
    protected function generateUid()
    {
        return uniqid(mt_rand(), true);
    }

    /**
     * @return string
     * @deprecated generateUUID() method is deprecated as of version 3.6 of Ting and will be removed in 4.0. Use generateUid() instead.
     */
    protected function generateUUID()
    {
        error_log(sprintf('%s::generateUUID() method is deprecated as of version 3.6 of Ting and will be removed in 4.0. Use %s::generateUid() instead.',self::class, self::class),E_USER_DEPRECATED);

        return $this->generateUid();
    }

    /**
     * Watch changes on provided entity
     *
     * @param NotifyPropertyInterface $entity
     */
    public function manage(NotifyPropertyInterface $entity): void
    {
        if (isset($this->entities[$entity]) === false) {
            $this->entities[$entity] = true;
        }

        $entity->addPropertyListener($this);
    }

    /**
     * @param NotifyPropertyInterface $entity
     * @return bool - true if the entity is managed
     */
    public function isManaged(NotifyPropertyInterface $entity): bool
    {
        return isset($this->entities[$entity]);
    }

    /**
     * @param NotifyPropertyInterface $entity
     * @return bool - true if the entity has not been persisted yet
     */
    public function isNew(NotifyPropertyInterface $entity): bool
    {
        if (isset($this->entitiesShouldBePersisted[$entity]) === true
            && $this->entitiesShouldBePersisted[$entity] === self::STATE_NEW
        ) {
            return true;
        }
        return false;
    }

    /**
     * Flag the entity to be persisted (insert or update) on next process
     *
     * @param NotifyPropertyInterface $entity
     * @return $this
     */
    public function pushSave(NotifyPropertyInterface $entity): self
    {
        $state = self::STATE_MANAGED;

        if (isset($this->entities[$entity]) === false) {
            $state = self::STATE_NEW;
        }

        $this->entitiesShouldBePersisted[$entity] = $state;
        $this->entities[$entity] = $entity;

        return $this;
    }

    /**
     * @param NotifyPropertyInterface $entity
     * @return bool
     */
    public function shouldBePersisted(NotifyPropertyInterface $entity): bool
    {
        return isset($this->entitiesShouldBePersisted[$entity]);
    }

    /**
     * @param NotifyPropertyInterface $entity
     * @param string $propertyName
     * @param mixed $oldValue
     * @param mixed $newValue
     */
    public function propertyChanged(NotifyPropertyInterface $entity, $propertyName, $oldValue, $newValue): void
    {
        if ($oldValue === $newValue) {
            return;
        }

        if (isset($this->entitiesChanged[$entity]) === false) {
            $this->entitiesChanged[$entity] = [];
        }

        if (isset($this->entitiesChanged[$entity][$propertyName]) === false) {
            $this->entitiesChanged[$entity][$propertyName] = [$oldValue, null];
        }

        $this->entitiesChanged[$entity][$propertyName][1] = $newValue;
    }

    /**
     * @param NotifyPropertyInterface $entity
     * @param string $propertyName
     * @return bool
     */
    public function isPropertyChanged(NotifyPropertyInterface $entity, string $propertyName): bool
    {
        if (isset($this->entitiesChanged[$entity][$propertyName]) === true) {
            return true;
        }

        return false;
    }

    /**
     * Stop watching changes on the entity
     *
     * @param NotifyPropertyInterface $entity
     */
    public function detach(NotifyPropertyInterface $entity): void
    {
        $this->entitiesShouldBePersisted->offsetUnset($entity);
        $this->entitiesChanged->offsetUnset($entity);
        $this->entities->offsetUnset($entity);
    }

    /**
     * Stop watching changes on all entities
     */
    public function detachAll(): void
    {
        $this->entitiesChanged = new WeakMap();
        $this->entitiesShouldBePersisted = new WeakMap();
        $this->entities = new WeakMap();
    }

    /**
     * Flag the entity to be deleted on next process
     *
     * @param NotifyPropertyInterface $entity
     * @return $this
     */
    public function pushDelete(NotifyPropertyInterface $entity): self
    {
        $this->entitiesShouldBePersisted[$entity] = self::STATE_DELETE;
        $this->entities[$entity] = $entity;

        return $this;
    }

    /**
     * Returns true if delete($entity) has been called
     *
     * @param NotifyPropertyInterface $entity
     * @return bool
     */
    public function shouldBeRemoved(NotifyPropertyInterface $entity): bool
    {
        if (isset($this->entitiesShouldBePersisted[$entity]) === true
            && $this->entitiesShouldBePersisted[$entity] === self::STATE_DELETE
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
     *
     * @throws Exception
     * @throws QueryException
     */
    public function process(): void
    {
        foreach ($this->entitiesShouldBePersisted as $entity => $state) {
            switch ($state) {
                case self::STATE_MANAGED:
                    $this->processManaged($entity);
                    break;

                case self::STATE_NEW:
                    $this->processNew($entity);
                    break;

                case self::STATE_DELETE:
                    $this->processDelete($entity);
                    break;
            }
        }
        foreach ($this->statements as $statementName => $connections) {
            foreach ($connections as $connection) {
                $connection->closeStatement($statementName);
            }
        }
        $this->statements = [];
    }

    /**
     * Update all applicable entities in database
     *
     * @param NotifyPropertyInterface $entity
     * @throws Exception
     * @throws QueryException
     */
    protected function processManaged(NotifyPropertyInterface $entity): void
    {
        if (isset($this->entitiesChanged[$entity]) === false) {
            return;
        }

        $properties = [];
        foreach ($this->entitiesChanged[$entity] as $property => $values) {
            if ($values[0] !== $values[1]) {
                $properties[$property] = $values;
            }
        }

        if ($properties === []) {
            return;
        }

        $this->metadataRepository->findMetadataForEntity(
            $entity,
            function (Metadata $metadata) use ($entity, $properties) {
                $connection = $metadata->getConnection($this->connectionPool);
                $query = $metadata->generateQueryForUpdate(
                    $connection,
                    $this->queryFactory,
                    $entity,
                    $properties
                );

                $this->addStatementToClose($query->getStatementName(), $connection->master());
                $query->prepareExecute()->execute();

                $this->entitiesChanged->offsetUnset($entity);
                $this->entitiesShouldBePersisted->offsetUnset($entity);
            },
            function () use ($entity) {
                throw new QueryException('Could not find repository matching entity "' . \get_class($entity) . '"');
            }
        );
    }

    /**
     * Insert all applicable entities in database
     *
     * @param NotifyPropertyInterface $entity
     * @throws Exception
     * @throws QueryException
     */
    protected function processNew(NotifyPropertyInterface $entity): void
    {

        $this->metadataRepository->findMetadataForEntity(
            $entity,
            function (Metadata $metadata) use ($entity) {
                $connection = $metadata->getConnection($this->connectionPool);
                $query = $metadata->generateQueryForInsert(
                    $connection,
                    $this->queryFactory,
                    $entity
                );
                $this->addStatementToClose($query->getStatementName(), $connection->master());
                $query->prepareExecute()->execute();

                $metadata->setEntityPropertyForAutoIncrement($entity, $connection->master());

                $this->entitiesChanged->offsetUnset($entity);
                $this->entitiesShouldBePersisted->offsetUnset($entity);

                $this->manage($entity);
            },
            function () use ($entity) {
                throw new QueryException('Could not find repository matching entity "' . \get_class($entity) . '"');
            }
        );
    }

    /**
     * Delete all flagged entities from database
     *
     * @param NotifyPropertyInterface $entity
     * @throws Exception
     * @throws QueryException
     */
    protected function processDelete(NotifyPropertyInterface $entity): void
    {
        $properties = [];
        if (isset($this->entitiesChanged[$entity])) {
            foreach ($this->entitiesChanged[$entity] as $property => $values) {
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
                throw new QueryException('Could not find repository matching entity "' . \get_class($entity) . '"');
            }
        );
    }

    protected function addStatementToClose($statementName, DriverInterface $connection): void
    {
        if (isset($this->statements[$statementName]) === false) {
            $this->statements[$statementName] = [];
        }
        if (isset($this->statements[$statementName][spl_object_hash($connection)]) === false) {
            $this->statements[$statementName][spl_object_hash($connection)] = $connection;
        }
    }
}
