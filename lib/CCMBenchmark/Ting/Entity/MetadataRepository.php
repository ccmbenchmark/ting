<?php

namespace CCMBenchmark\Ting\Entity;

use CCMBenchmark\Ting\Entity\Metadata;
use CCMBenchmark\Ting\Entity\Repository;
use CCMBenchmark\Ting\Query\QueryFactoryInterface;

class MetadataRepository
{

    protected $metadataList    = array();
    protected $metadataFactory = null;

    public function __construct(MetadataFactoryInterface $metadataFactory)
    {
        $this->metadataFactory = $metadataFactory;
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

    public function addMetadata($repositoryClass, Metadata $metadata)
    {
        if (isset($this->metadataList[$repositoryClass]) === false) {
            $this->metadataList[$repositoryClass] = $metadata;
        }
    }

    public function batchLoadMetadata($namespace, $globPattern)
    {
        if (file_exists(dirname($globPattern)) === false) {
            return 0;
        }

        $loaded = 0;
        foreach (glob($globPattern) as $repositoryFile) {
            $repository = $namespace . '\\' . basename($repositoryFile, '.php');
            $this->addMetadata($repository, $repository::initMetadata($this->metadataFactory));
            $loaded++;
        }

        return $loaded;
    }
}
