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
use CCMBenchmark\Ting\ContainerInterface;
use CCMBenchmark\Ting\Exception;
use CCMBenchmark\Ting\MetadataRepository;
use CCMBenchmark\Ting\Query\QueryFactory;
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
    protected $connection;

    /**
     * @var UnitOfWork
     */

    protected $unitOfWork;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @param ConnectionPool $connectionPool
     * @param MetadataRepository $metadataRepository
     * @param QueryFactory $queryFactory
     * @param CollectionFactory $collectionFactory
     * @param CacheInterface $cache
     * @param UnitOfWork $unitOfWork
     */
    public function __construct(
        ConnectionPool $connectionPool,
        MetadataRepository $metadataRepository,
        QueryFactory $queryFactory,
        CollectionFactory $collectionFactory,
        CacheInterface $cache,
        UnitOfWork $unitOfWork
    ) {
        $this->connectionPool     = $connectionPool;
        $this->metadataRepository = $metadataRepository;
        $this->queryFactory       = $queryFactory;
        $this->collectionFactory  = $collectionFactory;
        $this->cache              = $cache;
        $this->unitOfWork         = $unitOfWork;

        $class            = get_class($this);
        $this->metadata   = $class::initMetadata();
        $this->connection = $this->metadata->getConnection($connectionPool);
        $this->metadataRepository->addMetadata($class, $this->metadata);
    }

    /**
     * @param string $sql
     * @return \CCMBenchmark\Ting\Query\Query
     */
    public function getQuery($sql)
    {
        return $this->queryFactory->get($sql, $this->connection, $this->collectionFactory);
    }

    /**
     * @param string $sql
     * @return \CCMBenchmark\Ting\Query\PreparedQuery
     */
    public function getPreparedQuery($sql)
    {
        return $this->queryFactory->getPrepared($sql, $this->connection, $this->collectionFactory);
    }

    /**
     * @param string $sql
     * @return \CCMBenchmark\Ting\Query\Cached\Query
     */
    public function getCachedQuery($sql)
    {
        return $this->queryFactory->getCached(
            $sql,
            $this->connection,
            $this->cache,
            $this->collectionFactory
        );
    }

    /**
     * @param string $sql
     * @return \CCMBenchmark\Ting\Query\Cached\PreparedQuery
     */
    public function getCachedPreparedQuery($sql)
    {
        return $this->queryFactory->getCachedPrepared(
            $sql,
            $this->connection,
            $this->cache,
            $this->collectionFactory
        );
    }

    /**
     * @param $primariesKeyValue associative array column => value
     * @param bool $forceMaster
     * @return mixed|null
     */
    public function get($primariesKeyValue, $forceMaster = false)
    {
        $query = $this->metadata->getByPrimaries(
            $this->connection,
            $this->queryFactory,
            $this->collectionFactory,
            $primariesKeyValue,
            (bool) $forceMaster
        );

        if ($forceMaster) {
            $query->selectMaster($forceMaster);
        }

        $collection = $query->query();
        if ($collection->count() === 0) {
            return null;
        }
        $entity = current($collection->current());

        return $entity;
    }

    public function insert($entity)
    {
        $this->unitOfWork->persist($entity)->flush();
    }

    public function update($entity)
    {
        $this->unitOfWork->persist($entity)->flush();
    }

    public function delete($entity)
    {
        $this->unitOfWork->delete($entity)->flush();
    }

    /**
     * @throws Exception
     * @return \CCMBenchmark\Ting\Repository\Metadata
     */
    public static function initMetadata()
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

    /**
     * @return void
     */
    public function startTransaction()
    {
        $this->connection->master()->startTransaction();
    }

    /**
     * @return void
     */
    public function rollback()
    {
        $this->connection->master()->rollback();
    }

    /**
     * @return void
     */
    public function commit()
    {
        $this->connection->master()->commit();
    }
}
