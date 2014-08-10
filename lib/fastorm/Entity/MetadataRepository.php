<?php

namespace fastorm\Entity;

use fastorm\Entity\Metadata;
use fastorm\Entity\Repository;

class MetadataRepository
{

    protected static $instance = null;
    protected $metadataList = array();


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

    public function add($repository, Metadata $metadata)
    {
        $this->metadataList[$repository] = $metadata;
    }

    public function hasMetadataForTable($table, callable $callbackHasMetadata, callable $callbackNoMetadata)
    {
        $found = false;
        foreach ($this->metadataList as $metadata) {
            $metadata->ifTableKnown(
                $table,
                function ($metadata) use (&$found, $callbackHasMetadata) {
                    $found = true;
                    $callbackHasMetadata($metadata);
                }
            );
        }

        if ($found === false) {
            $callbackNoMetadata();
        }
    }

    public function loadMetadata(Repository $repository, callable $callback)
    {
        if (isset($this->metadataList[get_class($repository)]) === false) {
            $repository::initMetadata();
        }
        $callback($this->metadataList[get_class($repository)]);
    }
}
