<?php

namespace fastorm\Entity;

use fastorm\ConnectionPool;
use fastorm\Driver\DriverInterface;
use fastorm\Entity\Collection;
use fastorm\Entity\Hydrator;
use fastorm\Entity\MetadataRepository;
use fastorm\Query;

class Repository
{

    protected static $instance = null;
    protected $metadata;

    protected function __construct()
    {
        MetadataRepository::getInstance()->loadMetadata($this, function (Metadata $metadata) {
            $this->metadata = $metadata;
        });
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    public function get(
        $primaryKeyValue,
        Hydrator $hydrator = null,
        Collection $collection = null,
        ConnectionPool $connectionPool = null
    ) {
        if ($hydrator === null) {
            $hydrator = new Hydrator();
        }

        if ($collection === null) {
            $collection = new Collection();
        }

        if ($connectionPool === null) {
            $connectionPool = ConnectionPool::getInstance();
        }

        $this->metadata->connect(
            $connectionPool,
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

    public function execute(Query $query, Collection $collection = null, ConnectionPool $connectionPool = null)
    {

        if ($connectionPool === null) {
            $connectionPool = ConnectionPool::getInstance();
        }

        if ($collection === null) {
            $collection = new Collection();
        }

        $this->metadata->connect(
            $connectionPool,
            function (DriverInterface $driver) use ($query, $collection) {
                $query->execute($driver, $collection);
            }
        );

        return $collection;
    }

    public static function initMetadata(MetadataRepository $metadataRepository = null, Metadata $metadata = null)
    {
        if ($metadataRepository === null) {
            $metadataRepository = MetadataRepository::getInstance();
        }

        if ($metadata === null) {
            $metadata = new Metadata();
            $metadata->setClass(get_called_class());
            $metadata->addField(array(
               'primary'    => true,
               'fieldName'  => 'YOU_SHOULD_ADD',
               'columnName' => 'YOUR_OWN_INIT_METADATA',
               'type'       => 'IN_YOUR_REPOSITORY'
            ));
        }

        $metadata->addInto($metadataRepository);
    }
}
