<?php

namespace fastorm\Entity;

use fastorm\ConnectionPool;
use fastorm\ConnectionPoolInterface;
use fastorm\Driver\DriverInterface;
use fastorm\Exception;
use fastorm\PreparedQuery;
use fastorm\Query;

class Repository
{

    protected static $instance = null;
    protected $metadata;
    /**
     * @var \fastorm\ConnectionPoolInterface
     */
    protected $connectionPool;

    public function __construct(ConnectionPoolInterface $connectionPool = null)
    {
        MetadataRepository::getInstance()->loadMetadata($this, function (Metadata $metadata) {
            $this->metadata = $metadata;
        });
        if ($connectionPool === null) {
            $this->connectionPool = ConnectionPool::getInstance();
        } else {
            $this->connectionPool = $connectionPool;
        }
    }

    public function get(
        $primaryKeyValue,
        Hydrator $hydrator = null,
        Collection $collection = null
    ) {
        if ($hydrator === null) {
            $hydrator = new Hydrator();
        }

        if ($collection === null) {
            $collection = new Collection();
        }

        $this->metadata->connect(
            $this->connectionPool,
            function (DriverInterface $driver) use ($collection, $primaryKeyValue) {
                $this->metadata->generateQueryForPrimary(
                    $driver,
                    $primaryKeyValue,
                    function (Query $query) use ($driver, $collection) {
                        $query->execute($driver, $collection);
                    }
                );
            }
        );

        $collection->hydrator($hydrator);
        return current($collection->rewind()->current());
    }

    public function execute(Query $query, Collection $collection = null)
    {
        if ($collection === null) {
            $collection = new Collection();
        }

        $this->metadata->connect(
            $this->connectionPool,
            function (DriverInterface $driver) use ($query, $collection) {
                $query->execute($driver, $collection);
            }
        );

        return $collection;
    }

    public function executePrepared(PreparedQuery $query, $collection = null)
    {
        if ($collection === null) {
            $collection = new Collection();
        }
        $this->metadata->connect(
            $this->connectionPool,
            function (DriverInterface $driver) use ($query, $collection) {
                $query->setDriver($driver)->prepare()->execute($collection);
            }
        );

        return $collection;
    }

    public static function initMetadata(MetadataRepository $metadataRepository = null, Metadata $metadata = null)
    {
        throw new Exception('You should add initMetadata in your class repository');

        /**
         * Example for your repository :
         *
            if ($metadataRepository === null) {
                $metadataRepository = MetadataRepository::getInstance();
            }

            if ($metadata === null) {
                $metadata = new Metadata();
            }

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
