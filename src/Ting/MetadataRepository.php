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

use Closure;
use ReflectionClass;
use CCMBenchmark\Ting\Repository\Metadata;
use CCMBenchmark\Ting\Repository\MetadataInitializer;
use CCMBenchmark\Ting\Repository\Repository;
use CCMBenchmark\Ting\Serializer\SerializerFactoryInterface;
use Psr\Cache\CacheItemPoolInterface;
use function is_object;

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
    private array $tableWithConnectionToMetadata = [];

    /**
     * @param SerializerFactoryInterface $serializerFactory
     */
    public function __construct(protected SerializerFactoryInterface $serializerFactory, private readonly ?CacheItemPoolInterface $cacheItemPool = null)
    {
    }

    /**
     * @param string   $connectionName
     * @param string   $database
     * @param string   $schema
     * @param string   $table
     * @param Closure $callbackFound called with applicable Metadata if applicable
     * @param Closure $callbackNotFound called if unknown table - no parameter
     *
     * @internal
     */
    public function findMetadataForTable(
        string $connectionName,
        string $database,
        string $schema,
        string $table,
        Closure $callbackFound,
        ?Closure $callbackNotFound = null
    ): void {

        $connectionKey = $connectionName . '#' . $table;

        if (isset($this->tableWithConnectionToMetadata[$connectionKey]) === false) {
            if ($callbackNotFound instanceof Closure) {
                $callbackNotFound();
            }
            return;
        }

        if (isset($this->tableWithConnectionToMetadata[$connectionKey][$schema . '#' . $database])) {
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
     * @param class-string<Repository<T>> $repositoryName
     * @param Closure(Metadata<T>):void $callbackFound Called with applicable Metadata if applicable
     * @param Closure():void $callbackNotFound called if unknown entity - no parameter
     *
     * @template T of object
     *
     * @internal
     */
    public function findMetadataForRepository(
        $repositoryName,
        Closure $callbackFound,
        ?Closure $callbackNotFound = null
    ): void {
        if (isset($this->metadataList[$repositoryName])) {
            $callbackFound($this->metadataList[$repositoryName]);
        } elseif ($callbackNotFound instanceof Closure) {
            $callbackNotFound();
        }
    }

    /**
     * @param T|class-string<T> $entity an instance or the class string of the entity
     * @param Closure(Metadata<T>):void $callbackFound Called with applicable Metadata if applicable
     * @param Closure():void $callbackNotFound called if unknown entity - no parameter
     *
     * @template T of object
     *
     * @internal
     */
    public function findMetadataForEntity($entity, Closure $callbackFound, ?Closure $callbackNotFound = null): void
    {
        if (is_object($entity)) {
            $entity = $entity::class;
        }

        if (isset($this->entityToRepository[$entity]) === false) {
            $callbackNotFound();
            return;
        }

        $this->findMetadataForRepository(
            $this->entityToRepository[$entity],
            $callbackFound,
            $callbackNotFound
        );
    }

    /**
     * @param class-string<Repository<T>> $repositoryClass
     * @param Metadata<T> $metadata
     *
     * @template T of object
     *
     * @internal
     */
    public function addMetadata($repositoryClass, Metadata $metadata): void
    {
        $metadata->propertyAccessor->setCacheItemPool($this->cacheItemPool);
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
    public function batchLoadMetadata(string $namespace, string $globPattern, array $options = []): array
    {
        $loaded = [];

        if (file_exists(dirname($globPattern)) === false) {
            return $loaded;
        }

        foreach (glob($globPattern) as $metadataFile) {
            /** @var class-string<MetadataInitializer> $metadataClass */
            $metadataClass = $namespace . '\\' . basename($metadataFile, '.php');
            $class = new ReflectionClass($metadataClass);
            if ($class->isInterface()) {
                continue;
            }
            if ($class->isAbstract()) {
                continue;
            }
            if (!$class->isSubclassOf(MetadataInitializer::class)) {
                continue;
            }

            /** @var Metadata $metadata */
            $metadata = $metadataClass::initMetadata(
                $this->serializerFactory,
                $this->getOptionForRepository($metadataClass, $options)
            );

            $repository = $metadata->getRepository() ?? $metadataClass;

            $this->addMetadata($repository, $metadata);
            $loaded[$repository] = $metadataClass;
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
    public function batchLoadMetadataFromCache(array $paths, array $options = []): array
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
    protected function getOptionForRepository(string $repository, array $options)
    {
        $repositoryOptions = isset($options['default']) === true ? $options['default'] : [];

        if (isset($options[$repository])) {
            return array_merge($repositoryOptions, $options[$repository]);
        }

        return $repositoryOptions;
    }

    /**
     * @return int[]|string[]
     */
    public function getAllEntities(): array
    {
        return array_keys($this->entityToRepository);
    }
}
