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

namespace CCMBenchmark\Ting\Repository;

use CCMBenchmark\Ting\Cache\CacheInterface;
use CCMBenchmark\Ting\ConnectionPool;
use CCMBenchmark\Ting\ConnectionPoolInterface;
use CCMBenchmark\Ting\ContainerInterface;
use CCMBenchmark\Ting\Driver\DriverInterface;
use CCMBenchmark\Ting\Exception;
use CCMBenchmark\Ting\MetadataRepository;
use CCMBenchmark\Ting\Query\CachedQuery;
use CCMBenchmark\Ting\Query\Query;
use CCMBenchmark\Ting\Query\QueryAbstract;
use CCMBenchmark\Ting\UnitOfWork;

class Repository
{

    /**
     * @var ContainerInterface
     */
    protected $services = null;
    /**
     * @var Metadata
     */
    protected $metadata = null;

    /**
     * @var \CCMBenchmark\Ting\ConnectionPoolInterface
     */
    protected $connectionPool;

    /**
     * @var UnitOfWork
     */

    protected $unitOfWork;

    /**
     * @var CacheInterface
     */
    protected $cache;

    public function __construct(
        ConnectionPool $connectionPool,
        MetadataRepository $metadataRepository,
        MetadataFactoryInterface $metadataFactory,
        CollectionFactory $collectionFactory,
        UnitOfWork $unitOfWork,
        CacheInterface $cache
    ) {
        $this->connectionPool     = $connectionPool;
        $this->metadataRepository = $metadataRepository;
        $this->collectionFactory  = $collectionFactory;
        $this->unitOfWork         = $unitOfWork;
        $this->cache              = $cache;

        $class = get_class($this);
        $this->metadata = $class::initMetadata($metadataFactory);
        $this->metadataRepository->addMetadata($class, $this->metadata);
    }

    public function get(
        $primaryKeyValue,
        Collection $collection = null,
        $connectionType = ConnectionPoolInterface::CONNECTION_SLAVE
    ) {
        if ($collection === null) {
            $collection = $this->collectionFactory->get();
        }

        $callback = function (DriverInterface $driver) use ($collection, $primaryKeyValue) {
            $this->metadata->generateQueryForPrimary(
                $driver,
                $primaryKeyValue,
                function (Query $query) use ($driver, $collection) {
                    $query->setDriver($driver);
                    $this->execute($query, $collection);
                }
            );
        };

        if ($connectionType === ConnectionPoolInterface::CONNECTION_SLAVE || $connectionType === null) {
            $this->metadata->connectSlave(
                $this->connectionPool,
                $callback
            );
        } else {
            $this->metadata->connectMaster(
                $this->connectionPool,
                $callback
            );
        }
        $collection->rewind();

        return current($collection->current());
    }

    public function executeFromCache(CachedQuery $query, $ttl, $version = 0, CachedCollection $collection = null)
    {
        if ($collection === null) {
            $collection = $this->collectionFactory->getCollectionForCache();
        }

        $query
            ->setTtl($ttl)
            ->setVersion($version)
            ->setCacheDriver($this->cache);
        return $this->execute($query, $collection);
    }

    public function execute(QueryAbstract $query, CollectionInterface $collection = null, $connectionType = null)
    {
        if ($collection === null) {
            $collection = $this->collectionFactory->get();
        }

        $query->execute($this->metadata, $this->connectionPool, $collection, $connectionType);
        return $collection;
    }

    public static function initMetadata(MetadataFactoryInterface $metadataFactory)
    {
        throw new Exception('You should add initMetadata in your class repository');

        /**
         * Example for your repository :
         *
         *  $metadata = $metadataFactory->get();
         *
         *  $metadata->setEntity('myProject\model\Bouh');
         *  $metadata->addField(array(
         *     'primary'    => true,
         *     'fieldName'  => 'aField',
         *     'columnName' => 'COLUMN_NAME',
         *     'type'       => 'int'
         *  ));
         *
         * return $metadata;
         *
         * Supported types:
         *
         * int
         * datetime
         * string
         * float
         *
         */
    }

    public function startTransaction()
    {
        $this->metadata->connectMaster(
            $this->connectionPool,
            function (DriverInterface $driver) {
                $driver->startTransaction();
            }
        );
    }

    public function rollback()
    {
        $this->metadata->connectMaster(
            $this->connectionPool,
            function (DriverInterface $driver) {
                $driver->rollback();
            }
        );
    }

    public function commit()
    {
        $this->metadata->connectMaster(
            $this->connectionPool,
            function (DriverInterface $driver) {
                $driver->commit();
            }
        );
    }
}
