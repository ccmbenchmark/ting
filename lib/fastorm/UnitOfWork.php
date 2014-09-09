<?php

namespace fastorm;

use fastorm\Driver\DriverInterface;
use fastorm\Entity\MetadataRepository;
use fastorm\Query\PreparedQuery;

class UnitOfWork implements PropertyListenerInterface
{
    const STATE_NEW     = 1;
    const STATE_MANAGED = 2;
    const STATE_DELETE  = 3;

    protected $services                  = null;
    protected $entitiesManaged           = array();
    protected $entitiesChanged           = array();
    protected $entitiesShouldBePersisted = array();

    public function __construct(ContainerInterface $services)
    {
        $this->services = $services;
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
        $metadataRepository = $this->services->get('MetadataRepository');
        $connectionPool     = $this->services->get('ConnectionPool');

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

        $metadataRepository->findMetadataForEntity(
            $entity,
            function ($metadata) use ($connectionPool, $entity, $properties) {
                $metadata->connect(
                    $connectionPool,
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
        $metadataRepository = $this->services->get('MetadataRepository');
        $connectionPool     = $this->services->get('ConnectionPool');

        $entity = $this->entities[$oid];
        $metadataRepository->findMetadataForEntity(
            $entity,
            function ($metadata) use ($connectionPool, $entity) {
                $metadata->connect(
                    $connectionPool,
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
        $metadataRepository = $this->services->get('MetadataRepository');
        $connectionPool     = $this->services->get('ConnectionPool');

        $entity = $this->entities[$oid];
        $metadataRepository->findMetadataForEntity(
            $entity,
            function ($metadata) use ($connectionPool, $entity) {
                $metadata->connect(
                    $connectionPool,
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
