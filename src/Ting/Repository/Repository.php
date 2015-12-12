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
use CCMBenchmark\Ting\Serializer\SerializerFactoryInterface;
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
     * @param SerializerFactoryInterface $serializerFactory
     */
    public function __construct(
        ConnectionPool $connectionPool,
        MetadataRepository $metadataRepository,
        QueryFactory $queryFactory,
        CollectionFactory $collectionFactory,
        CacheInterface $cache,
        UnitOfWork $unitOfWork,
        SerializerFactoryInterface $serializerFactory
    ) {
        $this->connectionPool     = $connectionPool;
        $this->metadataRepository = $metadataRepository;
        $this->queryFactory       = $queryFactory;
        $this->collectionFactory  = $collectionFactory;
        $this->cache              = $cache;
        $this->unitOfWork         = $unitOfWork;

        $class = get_class($this);
        $this->metadataRepository->findMetadataForRepository(
            $class,
            function ($metadata) {
                $this->metadata = $metadata;
            },
            function () use ($class) {
                throw new Exception(
                    'Metadata not found for ' . $class
                    . ', you probably forgot to call MetadataRepository::batchLoadMetadata'
                );
            }
        );
        $this->connection = $this->metadata->getConnection($connectionPool);
        $this->metadataRepository->addMetadata($class, $this->metadata);
    }


    /**
     * @param HydratorInterface $hydrator|null
     * @return Collection
     */
    public function getCollection(HydratorInterface $hydrator = null)
    {
        return $this->collectionFactory->get($hydrator);
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
     * Retrieve one object from database
     *
     * @param $primariesKeyValue array|int|string column => value or if one primary : just the value
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
            (bool)$forceMaster
        );

        $collection = $query->query();
        if ($collection->count() === 0) {
            return null;
        }
        $entity = $collection->getIterator()->current();

        return reset($entity);
    }

    /**
     * @param bool $forceMaster
     * @return CollectionInterface
     */
    public function getAll($forceMaster = false)
    {
        $query = $this->metadata->getAll(
            $this->connection,
            $this->queryFactory,
            $this->collectionFactory,
            (bool)$forceMaster
        );

        return $query->query($this->getCollection(new HydratorSingleObject()));
    }

    /**
     * @param array $criteria
     * @param bool  $forceMaster
     * @return CollectionInterface
     */
    public function getBy(array $criteria, $forceMaster = false)
    {
        $query = $this->metadata->getByCriteria(
            $criteria,
            $this->connection,
            $this->queryFactory,
            $this->collectionFactory,
            (bool)$forceMaster
        );

        return $query->query($this->getCollection(new HydratorSingleObject()));
    }

    /**
     * @param array $criteria
     * @param bool  $forceMaster
     * @return mixed|null
     */
    public function getOneBy(array $criteria, $forceMaster = false)
    {
        $query = $this->metadata->getOneByCriteria(
            $this->connection,
            $this->queryFactory,
            $this->collectionFactory,
            $criteria,
            (bool)$forceMaster
        );
        $collection = $query->query();
        if ($collection->count() === 0) {
            return null;
        }
        $entity = $collection->first();

        return reset($entity);
    }

    /**
     * Save an entity in database (update or insert)
     *
     * @param $entity
     */
    public function save($entity)
    {
        $this->unitOfWork->pushSave($entity)->process();
    }

    /**
     * Delete an entity from database
     *
     * @param $entity
     */
    public function delete($entity)
    {
        $this->unitOfWork->pushDelete($entity)->process();
    }

    /**
     * @param  SerializerFactoryInterface $serializerFactory
     * @param  array                      $options
     * @return \CCMBenchmark\Ting\Repository\Metadata
     * @throws Exception
     */
    public static function initMetadata(SerializerFactoryInterface $serializerFactory, array $options = [])
    {
        throw new Exception('You should add initMetadata in your class repository');

        /**
         * Example for your repository :
         *
         *  $metadata = new Metadata($serializerFactory);
         *
         *  $metadata->setEntity('myProject\model\Bouh');
         *  $metadata->setConnectionName('main');
         *  $metadata->setDatabase('bouh');
         *  $metadata->setTable('T_BOUH_BOO');
         *
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
         * double
         * bool
         *
         */
    }

    /**
     * Start a transaction against the master connection
     *
     * @return void
     */
    public function startTransaction()
    {
        $this->connection->master()->startTransaction();
    }

    /**
     * Rollback the transaction opened on the master connection
     *
     * @return void
     */
    public function rollback()
    {
        $this->connection->master()->rollback();
    }

    /**
     * Commit the transaction opened on the master connection
     *
     * @return void
     */
    public function commit()
    {
        $this->connection->master()->commit();
    }
}
