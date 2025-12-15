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
use CCMBenchmark\Ting\Driver\Pgsql\Driver;
use CCMBenchmark\Ting\Connection;
use CCMBenchmark\Ting\ConnectionPool;
use CCMBenchmark\Ting\ContainerInterface;
use CCMBenchmark\Ting\Driver\NeverConnectedException;
use CCMBenchmark\Ting\Entity\NotifyPropertyInterface;
use CCMBenchmark\Ting\Driver\Mysqli;
use CCMBenchmark\Ting\Driver\SphinxQL;
use CCMBenchmark\Ting\Exceptions\DriverException;
use CCMBenchmark\Ting\Exceptions\RepositoryException;
use CCMBenchmark\Ting\MetadataRepository;
use CCMBenchmark\Ting\Query\QueryFactory;
use CCMBenchmark\Ting\Query\Query;
use CCMBenchmark\Ting\Query\PreparedQuery;
use CCMBenchmark\Ting\UnitOfWork;
use Doctrine\Common\Cache\Cache;

/**
 * @template T on \CCMBenchmark\Ting\Entity\NotifyPropertyInterface
 */
abstract class Repository
{
    public const QUERY_SELECT = 'select';
    public const QUERY_INSERT = 'insert';
    public const QUERY_UPDATE = 'update';
    public const QUERY_DELETE = 'delete';

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
     * @param ConnectionPool $connectionPool
     * @param MetadataRepository $metadataRepository
     * @param QueryFactory $queryFactory
     * @param CollectionFactory $collectionFactory
     * @param Cache $cache
     * @param UnitOfWork $unitOfWork
     *
     * @internal
     */
    public function __construct(
        protected ConnectionPool $connectionPool,
        protected MetadataRepository $metadataRepository,
        protected \CCMBenchmark\Ting\Query\QueryFactory $queryFactory,
        protected CollectionFactory $collectionFactory,
        protected Cache $cache,
        protected UnitOfWork $unitOfWork
    ) {
        $class = static::class;
        $this->metadataRepository->findMetadataForRepository(
            $class,
            function (Metadata $metadata): void {
                $this->metadata = $metadata;
            },
            function () use ($class): void {
                throw new RepositoryException(
                    'Metadata not found for ' . $class
                    . ', you probably forgot to call MetadataRepository::batchLoadMetadata'
                );
            }
        );
        $this->connection = $this->metadata->getConnection($this->connectionPool);
        $this->metadataRepository->addMetadata($class, $this->metadata);
    }


    /**
     * @param HydratorInterface<U>|null $hydrator
     * @return Collection<U>
     *
     * @template U
     */
    public function getCollection(?HydratorInterface $hydrator = null): Collection
    {
        return $this->collectionFactory->get($hydrator);
    }

    public function getQuery(string $sql): Query
    {
        return $this->queryFactory->get($sql, $this->connection, $this->collectionFactory);
    }

    public function getPreparedQuery(string $sql): PreparedQuery
    {
        return $this->queryFactory->getPrepared($sql, $this->connection, $this->collectionFactory);
    }

    public function getCachedQuery(string $sql): \CCMBenchmark\Ting\Query\Cached\Query
    {
        return $this->queryFactory->getCached(
            $sql,
            $this->connection,
            $this->cache,
            $this->collectionFactory
        );
    }

    public function getCachedPreparedQuery(string $sql): \CCMBenchmark\Ting\Query\Cached\PreparedQuery
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
     * @throws DriverException
     */
    public function getQueryBuilder(string $type): QueryInterface
    {
        $driver = $this->connectionPool->getDriverClass($this->metadata->getConnectionName());
        $driver = ltrim($driver, '\\');

        switch ($driver) {
            case Driver::class:
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

        $queryBuilder = match ($type) {
            self::QUERY_UPDATE => $queryFactory->newUpdate(),
            self::QUERY_DELETE => $queryFactory->newDelete(),
            self::QUERY_INSERT => $queryFactory->newInsert(),
            default => $queryFactory->newSelect(),
        };

        return $queryBuilder;
    }

    /**
     * Retrieve one object from database
     *
     * @param $primariesKeyValue array|int|string column => value or if one primary : just the value
     * @return T|null
     */
    public function get(mixed $primariesKeyValue, bool $forceMaster = false)
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
    public function getAll($forceMaster = false): CollectionInterface
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
     * @return CollectionInterface<T>
     */
    public function getBy(array $criteria, bool $forceMaster = false, array $order = [], int $limit = 0): CollectionInterface
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
     * @return T|null
     */
    public function getOneBy(array $criteria, bool $forceMaster = false)
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
     */
    public function save(NotifyPropertyInterface $entity): void
    {
        $this->unitOfWork->pushSave($entity)->process();
    }

    /**
     * Delete an entity from database
     */
    public function delete(NotifyPropertyInterface $entity): void
    {
        $this->unitOfWork->pushDelete($entity)->process();
    }

    /**
     * Start a transaction against the master connection
     *
     * @return void
     */
    public function startTransaction(): void
    {
        $this->connection->master()->startTransaction();
    }

    /**
     * Rollback the transaction opened on the master connection
     *
     * @return void
     */
    public function rollback(): void
    {
        $this->connection->master()->rollback();
    }

    /**
     * Commit the transaction opened on the master connection
     *
     * @return void
     */
    public function commit(): void
    {
        $this->connection->master()->commit();
    }

    /**
     * @throws NeverConnectedException when you have not been connected to your database before trying to ping it.
     */
    public function ping(): bool
    {
        return $this->connection->slave()->ping();
    }

    /**
     * @throws NeverConnectedException when you have not been connected to your database before trying to ping it.
     * @return bool
     */
    public function pingMaster(): bool
    {
        return $this->connection->master()->ping();
    }

    /**
     * Returns the repository's corresponding metadata
     *
     * @return Metadata<T>
     */
    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }
}
