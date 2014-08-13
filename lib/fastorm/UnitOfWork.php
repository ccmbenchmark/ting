<?php

namespace fastorm;

use fastorm\ConnectionPool;
use fastorm\Driver\DriverInterface;
use fastorm\Entity\Collection;
use fastorm\Entity\MetadataRepository;

class UnitOfWork implements PropertyListenerInterface
{
    const STATE_NEW     = 1;
    const STATE_MANAGED = 2;
    const STATE_DELETE  = 3;

    protected static $instance           = null;
    protected $entitiesManaged           = array();
    protected $entitiesChanged           = array();
    protected $entitiesShouldBePersisted = array();
    protected $entities                  = array();

    protected function __construct()
    {

    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function manage($entity)
    {
        $this->entitiesManaged[spl_object_hash($entity)] = true;
        if ($entity instanceOf NotifyPropertyInterface) {
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
        $oid = spl_object_hash($entity);
        $state = self::STATE_NEW;
        if (isset($this->entitiesManaged[$oid]) === true) {
            $state = self::STATE_MANAGED;
        }

        $this->entitiesShouldBePersisted[$oid] = $state;
        $this->entities[$oid] = $entity;
    }

    public function isPersisted($entity)
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

    public function isRemoved($entity)
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

    public function flush(MetadataRepository $metadataRepository = null, ConnectionPool $connectionPool = null)
    {
        if ($metadataRepository === null) {
            $metadataRepository = MetadataRepository::getInstance();
        }

        if ($connectionPool === null) {
            $connectionPool = ConnectionPool::getInstance();
        }

        foreach ($this->entitiesShouldBePersisted as $oid => $state) {
            switch ($state) {
                case self::STATE_MANAGED:
                    $this->flushManaged($oid, $metadataRepository, $connectionPool);
                    break;

                case self::STATE_NEW:
                    $this->flushNew($oid, $metadataRepository, $connectionPool);
                    break;

                case self::STATE_DELETE:
                    $this->flushDelete($oid, $metadataRepository, $connectionPool);
                    break;
            }
        }
    }

    protected function flushManaged($oid, MetadataRepository $metadataRepository, ConnectionPool $connectionPool)
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

        $metadataRepository->findMetadataForEntity($entity,
            function ($metadata) use ($connectionPool, $entity, $properties) {
                $metadata->connect(
                    $connectionPool,
                    function (DriverInterface $driver) use ($entity, $metadata, $properties) {
                        $metadata->generateQueryForUpdate(
                            $driver,
                            $entity,
                            $properties,
                            function (Query $query) use ($driver, $entity) {
                                $query->execute($driver);
                                $this->detach($entity);
                            }
                        );
                    }
                );
            }
        );
    }

    protected function flushNew($oid, MetadataRepository $metadataRepository, ConnectionPool $connectionPool)
    {
        $entity = $this->entities[$oid];
        $metadataRepository->findMetadataForEntity($entity,
            function ($metadata) use ($connectionPool, $entity) {
                $metadata->connect(
                    $connectionPool,
                    function (DriverInterface $driver) use ($entity, $metadata) {
                        $metadata->generateQueryForInsert(
                            $driver,
                            $entity,
                            function (Query $query) use ($driver, $entity, $metadata) {
                                $id = $query->execute($driver);
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

    protected function flushDelete($oid, MetadataRepository $metadataRepository, ConnectionPool $connectionPool)
    {
        $entity = $this->entities[$oid];
        $metadataRepository->findMetadataForEntity($entity,
            function ($metadata) use ($connectionPool, $entity) {
                $metadata->connect(
                    $connectionPool,
                    function (DriverInterface $driver) use ($entity, $metadata) {
                        $metadata->generateQueryForDelete(
                            $driver,
                            $entity,
                            function (Query $query) use ($driver, $entity, $metadata) {
                                $query->execute($driver);
                                $this->detach($entity);
                            }
                        );
                    }
                );
            }
        );
    }
}
