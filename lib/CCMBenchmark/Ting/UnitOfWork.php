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
use CCMBenchmark\Ting\MetadataRepository;
use CCMBenchmark\Ting\Entity\NotifyPropertyInterface;
use CCMBenchmark\Ting\Entity\PropertyListenerInterface;
use CCMBenchmark\Ting\Query\PreparedQuery;

class UnitOfWork implements PropertyListenerInterface
{
    const STATE_NEW     = 1;
    const STATE_MANAGED = 2;
    const STATE_DELETE  = 3;

    protected $connectionPool            = null;
    protected $metadataRepository        = null;
    protected $entitiesManaged           = array();
    protected $entitiesChanged           = array();
    protected $entitiesShouldBePersisted = array();

    public function __construct(ConnectionPool $connectionPool, MetadataRepository $metadataRepository)
    {
        $this->connectionPool     = $connectionPool;
        $this->metadataRepository = $metadataRepository;
    }

    public function manage($entity)
    {
        $this->entitiesManaged[spl_object_hash($entity)] = true;
        if ($entity instanceof NotifyPropertyInterface) {
            $entity->addPropertyListener($this);
        }
    }

    public function isManaged($entity)
    {
        if (isset($this->entitiesManaged[spl_object_hash($entity)]) === true) {
            return true;
        }

        return false;
    }

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

    public function persist($entity)
    {
        $oid   = spl_object_hash($entity);
        $state = self::STATE_NEW;
        if (isset($this->entitiesManaged[$oid]) === true) {
            $state = self::STATE_MANAGED;
        }

        $this->entitiesShouldBePersisted[$oid] = $state;
        $this->entities[$oid] = $entity;
    }

    public function shouldBePersisted($entity)
    {
        if (isset($this->entitiesShouldBePersisted[spl_object_hash($entity)]) === true) {
            return true;
        }

        return false;
    }

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

    public function isPropertyChanged($entity, $propertyName)
    {
        if (isset($this->entitiesChanged[spl_object_hash($entity)][$propertyName]) === true) {
            return true;
        }

        return false;
    }

    public function detach($entity)
    {
        $oid = spl_object_hash($entity);
        unset($this->entitiesChanged[$oid]);
        unset($this->entitiesShouldBePersisted[$oid]);
        unset($this->entities[$oid]);
    }

    public function remove($entity)
    {
        $oid = spl_object_hash($entity);
        $this->entitiesShouldBePersisted[$oid] = self::STATE_DELETE;
        $this->entities[$oid] = $entity;
    }

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

    protected function flushManaged($oid)
    {
        if (isset($this->entitiesChanged[$oid]) === false) {
            return;
        }

        $entity = $this->entities[$oid];
        $properties = array();
        foreach ($this->entitiesChanged[$oid] as $property => $values) {
            if ($values[0] !== $values[1]) {
                $properties[] = $property;
            }
        }

        if (count($properties) === 0) {
            return;
        }

        $this->metadataRepository->findMetadataForEntity(
            $entity,
            function ($metadata) use ($entity, $properties) {
                $metadata->connect(
                    $this->connectionPool,
                    function (DriverInterface $driver) use ($entity, $metadata, $properties) {
                        $metadata->generateQueryForUpdate(
                            $driver,
                            $entity,
                            $properties,
                            function (PreparedQuery $query) use ($driver, $entity) {
                                $query->setDriver($driver)->execute();
                                $this->detach($entity);
                            }
                        );
                    }
                );
            }
        );
    }

    protected function flushNew($oid)
    {
        $entity = $this->entities[$oid];
        $this->metadataRepository->findMetadataForEntity(
            $entity,
            function ($metadata) use ($entity) {
                $metadata->connect(
                    $this->connectionPool,
                    function (DriverInterface $driver) use ($entity, $metadata) {
                        $metadata->generateQueryForInsert(
                            $driver,
                            $entity,
                            function (PreparedQuery $query) use ($driver, $entity, $metadata) {
                                $id = $query->setDriver($driver)->execute();
                                $metadata->setEntityPrimary($entity, $id);
                                $this->detach($entity);
                                $this->manage($entity);
                            }
                        );
                    }
                );
            }
        );
    }

    protected function flushDelete($oid)
    {
        $entity = $this->entities[$oid];
        $this->metadataRepository->findMetadataForEntity(
            $entity,
            function ($metadata) use ($entity) {
                $metadata->connect(
                    $this->connectionPool,
                    function (DriverInterface $driver) use ($entity, $metadata) {
                        $metadata->generateQueryForDelete(
                            $driver,
                            $entity,
                            function (PreparedQuery $query) use ($driver, $entity, $metadata) {
                                $query->setDriver($driver)->execute();
                                $this->detach($entity);
                            }
                        );
                    }
                );
            }
        );
    }
}
