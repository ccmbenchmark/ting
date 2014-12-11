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
     * Flag the entity to be persisted (insert or update) on next flush
     *
     * @param $entity
     * @return $this
     */
    public function save($entity)
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
     * Flag the entity to be deleted on next flush
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
     * * Persist flagged new entities
     * * Update flagged entities
     * * Delete flagged entities
     */
    public function flush()
    {
        foreach ($this->entitiesShouldBePersisted as $oid => $state) {
            switch ($state) {
                case self::STATE_MANAGED:
                    $this->flushManaged($oid);
                    break;

                case self::STATE_NEW:
                    $this->flushNew($oid);
                    break;

                case self::STATE_DELETE:
                    $this->flushDelete($oid);
                    break;
            }
        }
    }

    /**
     * Update all applicable entities in database
     *
     * @param $oid
     */
    protected function flushManaged($oid)
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
                $query = $metadata->generateQueryForUpdate(
                    $metadata->getConnection($this->connectionPool),
                    $this->queryFactory,
                    $entity,
                    $properties
                );
                $query->prepareExecute()->execute();

                unset($this->entitiesChanged[$oid]);
                unset($this->entitiesShouldBePersisted[$oid]);
            }
        );
    }

    /**
     * Insert all applicable entities in database
     *
     * @param $oid
     */
    protected function flushNew($oid)
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
                $query->prepareExecute()->execute();
                $metadata->setEntityPropertyForAutoIncrement($entity, $connection->master()->getInsertId());

                unset($this->entitiesChanged[$oid]);
                unset($this->entitiesShouldBePersisted[$oid]);

                $this->manage($entity);
            }
        );
    }

    /**
     * Delete all flagged entities from database
     *
     * @param $oid
     */
    protected function flushDelete($oid)
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
                $query = $metadata->generateQueryForDelete(
                    $metadata->getConnection($this->connectionPool),
                    $this->queryFactory,
                    $properties,
                    $entity
                );
                $query->prepareExecute()->execute();
                $this->detach($entity);
            }
        );
    }
}
