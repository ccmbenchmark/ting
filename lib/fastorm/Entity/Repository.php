<?php

namespace fastorm\Entity;

use fastorm\ConnectionPool;
use fastorm\ConnectionPoolInterface;
use fastorm\ContainerInterface;
use fastorm\Driver\DriverInterface;
use fastorm\Exception;
use fastorm\Query\PreparedQuery;
use fastorm\Query\Query;

class Repository
{

    protected $serviceLocator = null;
    protected $metadata       = null;
    /**
     * @var \fastorm\ConnectionPoolInterface
     */
    protected $connectionPool;

    public function __construct(ContainerInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;

        $this->connectionPool = $this->serviceLocator->get('ConnectionPool');
        $this->serviceLocator->get('MetadataRepository')->loadMetadata($this, function (Metadata $metadata) {
            $this->metadata = $metadata;
        });
    }

    public function get(
        $primaryKeyValue,
        Hydrator $hydrator = null,
        Collection $collection = null
    ) {

        if ($hydrator === null) {
            $hydrator = $this->serviceLocator->get('Hydrator');
        }

        if ($collection === null) {
            $collection = $this->serviceLocator->get('Collection');
        }

        $this->metadata->connect(
            $this->connectionPool,
            function (DriverInterface $driver) use ($collection, $primaryKeyValue) {
                $this->metadata->generateQueryForPrimary(
                    $driver,
                    $primaryKeyValue,
                    function (Query $query) use ($driver, $collection) {
                        $query->setDriver($driver)->execute($collection);
                    }
                );
            }
        );

        $collection->hydrator($hydrator);
        return current($collection->rewind()->current());
    }

    public function execute(Query $query, Collection $collection = null)
    {
        if ($query === null) {
            $query = $this->serviceLocator->get('Query');
        }

        if ($collection === null) {
            $collection = $this->serviceLocator->get('Collection');
        }

        $this->metadata->connect(
            $this->connectionPool,
            function (DriverInterface $driver) use ($query, $collection) {
                $query->setDriver($driver)->execute($collection);
            }
        );

        return $collection;
    }

    public function executePrepared(PreparedQuery $query, $collection = null)
    {
        if ($query === null) {
            $query = $this->serviceLocator->get('Query');
        }

        if ($collection === null) {
            $collection = $this->serviceLocator->get('Collection');
        }

        $this->metadata->connect(
            $this->connectionPool,
            function (DriverInterface $driver) use ($query, $collection) {
                $query->setDriver($driver)->prepare()->execute($collection);
            }
        );

        return $collection;
    }

    public static function initMetadata(ContainerInterface $serviceLocator)
    {
        throw new Exception('You should add initMetadata in your class repository');

        /**
         * Example for your repository :
         *
            $metadataRepository = $serviceLocator->get('MetadataRepository');
            $metadata           = $serviceLocator->get('Metadata');

            $metadata->setClass(get_called_class());
            $metadata->addField(array(
               'primary'    => true,
               'fieldName'  => 'aField',
               'columnName' => 'COLUMN_NAME',
               'type'       => 'int'
            ));

            $metadata->addInto($metadataRepository);
        */
    }

    public function startTransaction()
    {
        $this->metadata->connect(
            $this->connectionPool,
            function (DriverInterface $driver) {
                $driver->startTransaction();
            }
        );
    }

    public function rollback()
    {
        $this->metadata->connect(
            $this->connectionPool,
            function (DriverInterface $driver) {
                $driver->rollback();
            }
        );
    }

    public function commit()
    {
        $this->metadata->connect(
            $this->connectionPool,
            function (DriverInterface $driver) {
                $driver->commit();
            }
        );
    }
}
