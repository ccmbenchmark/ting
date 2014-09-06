<?php

namespace fastorm\Entity;

use fastorm\ContainerInterface;
use fastorm\Entity\Metadata;
use fastorm\Entity\Repository;

class MetadataRepository
{

    protected $metadataList   = array();
    protected $serviceLocator = null;

    public function __construct(ContainerInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    public function add($repository, Metadata $metadata)
    {
        $this->metadataList[$repository] = $metadata;
    }

    public function findMetadataForTable($table, callable $callbackFound, callable $callbackNotFound)
    {
        $found = false;
        foreach ($this->metadataList as $metadata) {
            $found = $metadata->ifTableKnown(
                $table,
                function (Metadata $metadata) use ($callbackFound) {
                    $callbackFound($metadata);
                }
            );

            if ($found === true) {
                break;
            }
        }

        if ($found === false) {
            $callbackNotFound();
        }
    }

    public function findMetadataForEntity($entity, callable $callbackFound, callable $callbackNotFound = null)
    {
        $repository = get_class($entity) . 'Repository';
        if (isset($this->metadataList[$repository]) === true) {
            $callbackFound($this->metadataList[$repository]);
        } elseif ($callbackNotFound !== null) {
            $callbackNotFound();
        }
    }

    public function loadMetadata($repositoryClass, callable $callback)
    {
        if (isset($this->metadataList[$repositoryClass]) === false) {
            $repositoryClass::initMetadata($this->serviceLocator);
        }
        $callback($this->metadataList[$repositoryClass]);
    }

    public function batchLoadMetadata($namespace, $globPattern)
    {
        if (file_exists(dirname($globPattern)) === false) {
            return 0;
        }

        $loaded = 0;
        foreach (glob($globPattern) as $repositoryFile) {
            $repository = $namespace . '\\' . basename($repositoryFile, '.php');
            $repository::initMetadata($this->serviceLocator);
            $loaded++;
        }

        return $loaded;
    }
}
