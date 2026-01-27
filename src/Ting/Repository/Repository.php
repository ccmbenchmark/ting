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

use Aura\SqlQuery\QueryFactory as AuraQueryFactory;
use Aura\SqlQuery\QueryInterface;
use CCMBenchmark\Ting\Connection;
use CCMBenchmark\Ting\ConnectionPool;
use CCMBenchmark\Ting\ContainerInterface;
use CCMBenchmark\Ting\Driver\Mysqli;
use CCMBenchmark\Ting\Driver\NeverConnectedException;
use CCMBenchmark\Ting\Driver\Pgsql;
use CCMBenchmark\Ting\Driver\SphinxQL;
use CCMBenchmark\Ting\Exceptions\DriverException;
use CCMBenchmark\Ting\Exceptions\RepositoryException;
use CCMBenchmark\Ting\MetadataRepository;
use CCMBenchmark\Ting\Query\QueryFactory;
use CCMBenchmark\Ting\ResetInterface;
use CCMBenchmark\Ting\Serializer\SerializerFactoryInterface;
use CCMBenchmark\Ting\UnitOfWork;
use Doctrine\Common\Cache\Cache;

/**
 * @template T
 */
abstract class Repository implements ResetInterface
{

    const QUERY_SELECT = 'select';
    const QUERY_INSERT = 'insert';
    const QUERY_UPDATE = 'update';
    const QUERY_DELETE = 'delete';

    /**
     * @var ContainerInterface
     */
    protected $services = null;
    /**
     * @var Metadata
     */
    protected $metadata = null;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var UnitOfWork
     */

    protected $unitOfWork;

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var ConnectionPool
     */
    protected $connectionPool;

    /**
     * @var MetadataRepository
     */
    protected $metadataRepository;

    /**
     * @var QueryFactory
     */
    protected $queryFactory;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param ConnectionPool $connectionPool
     * @param MetadataRepository $metadataRepository
     * @param QueryFactory $queryFactory
     * @param CollectionFactory $collectionFactory
     * @param Cache $cache
     * @param UnitOfWork $unitOfWork
     * @param SerializerFactoryInterface $serializerFactory
     *
     * @internal
     */
    public function __construct(
        ConnectionPool $connectionPool,
        MetadataRepository $metadataRepository,
        QueryFactory $queryFactory,
        CollectionFactory $collectionFactory,
        Cache $cache,
        UnitOfWork $unitOfWork,
        SerializerFactoryInterface $serializerFactory
    ) {
        $this->connectionPool     = $connectionPool;
        $this->metadataRepository = $metadataRepository;
        $this->queryFactory       = $queryFactory;
        $this->collectionFactory  = $collectionFactory;
        $this->cache              = $cache;
        $this->unitOfWork         = $unitOfWork;

        $class = \get_class($this);
        $this->metadataRepository->findMetadataForRepository(
            $class,
            function ($metadata) {
                $this->metadata = $metadata;
            },
            function () use ($class) {
                throw new RepositoryException(
                    'Metadata not found for ' . $class
                    . ', you probably forgot to call MetadataRepository::batchLoadMetadata'
                );
            }
        );
        $this->connection = $this->metadata->getConnection($connectionPool);
        $this->metadataRepository->addMetadata($class, $this->metadata);
    }


    /**
     * @param HydratorInterface<U>|null $hydrator
     * @return Collection<U>
     *
     * @template U
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
     * @param string $type One of the QUERY_ constant
     * @return QueryInterface
     * @throws DriverException
     */
    public function getQueryBuilder($type)
    {
        $driver = $this->connectionPool->getDriverClass($this->metadata->getConnectionName());
        $driver = ltrim($driver, '\\');

        switch ($driver) {
            case Pgsql\Driver::class:
                $queryFactory = new AuraQueryFactory('pgsql');
                break;
            case SphinxQL\Driver::class:
                // SphinxQL and Mysqli are sharing the same driver
            case Mysqli\Driver::class:
                $queryFactory = new AuraQueryFactory('mysql');
                break;
            default:
                throw new DriverException('Driver ' . $driver . ' is unknown to build QueryBuilder');
        }

        switch ($type) {
            case self::QUERY_UPDATE:
                $queryBuilder = $queryFactory->newUpdate();
                break;
            case self::QUERY_DELETE:
                $queryBuilder = $queryFactory->newDelete();
                break;
            case self::QUERY_INSERT:
                $queryBuilder = $queryFactory->newInsert();
                break;
            case self::QUERY_SELECT:
                // We fallback on select for default case
            default:
                $queryBuilder = $queryFactory->newSelect();
        }

        return $queryBuilder;
    }

    /**
     * Retrieve one object from database
     *
     * @param $primariesKeyValue array|int|string column => value or if one primary : just the value
     * @param bool $forceMaster
     * @return T|null
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
     * @return CollectionInterface<T>
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
     * @return CollectionInterface<T>
     */
    public function getBy(array $criteria, $forceMaster = false, array $order = [], int $limit = 0)
    {
        $query = $this->metadata->getByCriteriaWithOrderAndLimit(
            $criteria,
            $order,
            $limit,
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
     * @return T|null
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
     * @param T $entity
     */
    public function save($entity)
    {
        $this->unitOfWork->pushSave($entity)->process();
    }

    /**
     * Delete an entity from database
     *
     * @param T $entity
     */
    public function delete($entity)
    {
        $this->unitOfWork->pushDelete($entity)->process();
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

    /**
     * @throws NeverConnectedException when you have not been connected to your database before trying to ping it.
     * @return bool
     */
    public function ping()
    {
        if (method_exists($this->connection->slave(), 'ping') === true) {
            return $this->connection->slave()->ping();
        }

        return false;
    }

    /**
     * @throws NeverConnectedException when you have not been connected to your database before trying to ping it.
     * @return bool
     */
    public function pingMaster()
    {
        if (method_exists($this->connection->master(), 'ping') === true) {
            return $this->connection->master()->ping();
        }

        return false;
    }

    /**
     * Returns the repository's corresponding metadata
     *
     * @return Metadata<T>
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    public function reset(): void
    {
        $this->unitOfWork->reset();
        $this->connection = $this->metadata->getConnection($this->connectionPool);
    }
}
