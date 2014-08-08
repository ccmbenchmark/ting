<?php

namespace fastorm\Entity;

use fastorm\ConnectionPool;
use fastorm\Entity\MetadataRepository;
use fastorm\Query;

class Repository
{

    protected static $instance = null;
    protected $metadata;

    protected function __construct()
    {
        MetadataRepository::getInstance()->loadMetadata(get_class($this), function($metadata) {
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
            function ($driver) use ($query, $collection) {
                $this->metadata->columns(function ($columns) use ($query, $driver, $collection) {
                    $query->execute($driver, $columns, $collection);
                });
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
            $metadata->setClass(get_class());
            $metadata->addField(array(
               'id'         => true,
               'fieldName'  => 'YOU_SHOULD_ADD',
               'columnName' => 'YOUR_OWN_INIT_METADATA',
               'type'       => 'IN_YOUR_REPOSITORY'
            ));
        }

        $metadata->addInto($metadataRepository);
    }
}
