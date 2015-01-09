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

use CCMBenchmark\Ting\Repository\Metadata;
use CCMBenchmark\Ting\Serializer\SerializerFactoryInterface;

class MetadataRepository
{
    /**
     * This array matches a repository (class name) and the corresponding metadata object
     *
     * @var array RepositoryClassName => MetadataObject
     */
    protected $metadataList = array();

    /**
     * This array matches an entity name and the corresponding repository name
     * @var array
     */
    protected $entityToRepository = array();

    /**
     * @var SerializerFactoryInterface|null
     */
    protected $serializerFactory = null;

    public function __construct(SerializerFactoryInterface $serializerFactory)
    {
        $this->serializerFactory = $serializerFactory;
    }

    /**
     * @param          $table
     * @param callable $callbackFound   called with applicable Metadata if applicable
     * @param callable $callbackNotFound called if unknown table - no parameter
     */
    public function findMetadataForTable($table, \Closure $callbackFound, \Closure $callbackNotFound = null)
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

        if ($found === false && $callbackNotFound !== null) {
            $callbackNotFound();
        }
    }

    /**
     * @param          $entity
     * @param callable $callbackFound Called with applicable Metadata if applicable
     * @param callable $callbackNotFound called if unknown entity - no parameter
     */
    public function findMetadataForEntity($entity, \Closure $callbackFound, \Closure $callbackNotFound = null)
    {
        if (
            isset($this->entityToRepository[get_class($entity)]) === true
            && isset($this->metadataList[$this->entityToRepository[get_class($entity)]]) === true
        ) {
            $callbackFound($this->metadataList[$this->entityToRepository[get_class($entity)]]);
        } elseif ($callbackNotFound !== null) {
            $callbackNotFound();
        }
    }

    public function addMetadata($repositoryClass, Metadata $metadata)
    {
        if (isset($this->metadataList[$repositoryClass]) === false) {
            $this->metadataList[$repositoryClass] = $metadata;
            $this->entityToRepository[$metadata->getEntity()] = $repositoryClass;
        }

    }

    /**
     * Read every files from given globPattern and load in memory all metadatas
     * This method should be used to discover the files and then create cache,
     * because glob uses directory reading at every hit.
     *
     * @param $namespace
     * @param $globPattern
     * @return array
     */
    public function batchLoadMetadata($namespace, $globPattern)
    {
        $loaded = [];

        if (file_exists(dirname($globPattern)) === false) {
            return $loaded;
        }

        foreach (glob($globPattern) as $repositoryFile) {
            $repository = $namespace . '\\' . basename($repositoryFile, '.php');
            $this->addMetadata($repository, $repository::initMetadata($this->serializerFactory));
            $loaded[] = $repository;
        }

        return $loaded;
    }


    /**
     * Read every classes (should be fully qualified namespaces) to load metadatas in memory.
     * This method is far more efficient than batchLoadMetadata : with opcache enabled, files
     * are not read from disk anymore.
     *
     * @param array $paths
     * @return array
     */
    public function batchLoadMetadataFromCache(array $paths)
    {
        $loaded = [];
        foreach ($paths as $repository) {
            $this->addMetadata($repository, $repository::initMetadata($this->serializerFactory));
            $loaded[] = $repository;
        }

        return $loaded;
    }
}
