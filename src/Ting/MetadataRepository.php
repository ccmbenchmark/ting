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
use CCMBenchmark\Ting\Repository\MetadataInitializer;
use CCMBenchmark\Ting\Serializer\SerializerFactoryInterface;

class MetadataRepository
{
    /**
     * This array matches a repository (class name) and the corresponding metadata object
     *
     * @var Metadata[]
     */
    protected $metadataList = [];

    /**
     * This array matches an entity name and the corresponding repository name
     * @var array
     */
    protected $entityToRepository = [];

    /**
     * @var array Fast array access to RepositoryClassName
     */
    private $tableWithConnectionToMetadata = array();

    /**
     * @var SerializerFactoryInterface|null
     */
    protected $serializerFactory = null;

    /**
     * @param SerializerFactoryInterface $serializerFactory
     */
    public function __construct(SerializerFactoryInterface $serializerFactory)
    {
        $this->serializerFactory = $serializerFactory;
    }

    /**
     * @param string   $connectionName
     * @param string   $database
     * @param string   $schema
     * @param string   $table
     * @param \Closure $callbackFound   called with applicable Metadata if applicable
     * @param \Closure $callbackNotFound called if unknown table - no parameter
     *
     * @internal
     */
    public function findMetadataForTable(
        $connectionName,
        $database,
        $schema,
        $table,
        \Closure $callbackFound,
        \Closure $callbackNotFound = null
    ) {

        $connectionKey = $connectionName . '#' . $table;

        if (isset($this->tableWithConnectionToMetadata[$connectionKey]) === false) {
            if ($callbackNotFound !== null) {
                $callbackNotFound();
            }
            return;
        }

        if (isset($this->tableWithConnectionToMetadata[$connectionKey][$schema . '#' . $database]) === true) {
            $callbackFound(
                $this->metadataList[$this->tableWithConnectionToMetadata[$connectionKey][$schema . '#' . $database]]
            );
        } else {
            $callbackFound(
                $this->metadataList[current($this->tableWithConnectionToMetadata[$connectionKey])]
            );
        }
    }

    /**
     * @param string $repositoryName
     * @param \Closure $callbackFound Called with applicable Metadata if applicable
     * @param \Closure $callbackNotFound called if unknown entity - no parameter
     *
     * @internal
     */
    public function findMetadataForRepository(
        $repositoryName,
        \Closure $callbackFound,
        \Closure $callbackNotFound = null
    ) {
        if (isset($this->metadataList[$repositoryName]) === true) {
            $callbackFound($this->metadataList[$repositoryName]);
        } elseif ($callbackNotFound !== null) {
            $callbackNotFound();
        }
    }

    /**
     * @param object   $entity
     * @param \Closure $callbackFound Called with applicable Metadata if applicable
     * @param \Closure $callbackNotFound called if unknown entity - no parameter
     *
     * @internal
     */
    public function findMetadataForEntity($entity, \Closure $callbackFound, \Closure $callbackNotFound = null)
    {
        if (isset($this->entityToRepository[get_class($entity)]) === false) {
            $callbackNotFound();
            return;
        }

        $this->findMetadataForRepository(
            $this->entityToRepository[get_class($entity)],
            $callbackFound,
            $callbackNotFound
        );
    }

    /**
     * @param string   $repositoryClass
     * @param Metadata $metadata
     *
     * @internal
     */
    public function addMetadata($repositoryClass, Metadata $metadata)
    {
        $this->metadataList[$repositoryClass] = $metadata;
        $metadataTable = $metadata->getTable();
        $metadataConnection = $metadata->getConnectionName();
        if (isset($this->tableWithConnectionToMetadata[$metadataConnection . '#' . $metadataTable]) === false) {
            $this->tableWithConnectionToMetadata[$metadataConnection . '#' . $metadataTable] = [];
        }

        $this->tableWithConnectionToMetadata
            [$metadataConnection . '#' . $metadataTable]
            [$metadata->getSchema() . '#' . $metadata->getDatabase()] = $repositoryClass;
        $this->entityToRepository[$metadata->getEntity()] = $repositoryClass;
    }

    /**
     * Read every files from given globPattern and load in memory all metadatas
     * This method should be used to discover the files and then create cache,
     * because glob uses directory reading at every hit.
     *
     * @param string $namespace
     * @param string $globPattern
     * @param array  $options Options you can use to custom initialization of Metadata
     * @return array
     */
    public function batchLoadMetadata($namespace, $globPattern, array $options = [])
    {
        $loaded = [];

        if (file_exists(dirname($globPattern)) === false) {
            return $loaded;
        }

        foreach (glob($globPattern) as $metadataFile) {
            $metadataClass = $namespace . '\\' . basename($metadataFile, '.php');

            if (is_subclass_of($metadataClass, MetadataInitializer::class) === true) {
                /** @var Metadata $metadata */
                $metadata = $metadataClass::initMetadata(
                    $this->serializerFactory,
                    $this->getOptionForRepository($metadataClass, $options)
                );

                if ($metadata->getRepository() !== null) {
                    $repository = $metadata->getRepository();
                } else {
                    $repository = $metadataClass;
                }

                $this->addMetadata($repository, $metadata);
                $loaded[$repository] = $metadataClass;
            }
        }

        return $loaded;
    }


    /**
     * Read every classes (should be fully qualified namespaces) to load metadatas in memory.
     * This method is far more efficient than batchLoadMetadata : with opcache enabled, files
     * are not read from disk anymore.
     *
     * @param array $paths
     * @param array $options Options you can use to custom initialization of Metadata
     * @return array
     */
    public function batchLoadMetadataFromCache(array $paths, array $options = [])
    {
        $loaded = [];
        foreach ($paths as $repository => $metadataClass) {
            $this->addMetadata(
                $repository,
                $metadataClass::initMetadata(
                    $this->serializerFactory,
                    $this->getOptionForRepository($metadataClass, $options)
                )
            );
            $loaded[] = $repository;
        }

        return $loaded;
    }

    /**
     * @param string $repository
     * @param array  $options
     * @return array
     */
    protected function getOptionForRepository($repository, array $options)
    {
        if (isset($options['default']) === true) {
            $repositoryOptions = $options['default'];
        } else {
            $repositoryOptions = [];
        }

        if (isset($options[$repository]) === true) {
            $repositoryOptions = array_merge($repositoryOptions, $options[$repository]);
        }

        return $repositoryOptions;
    }
}
