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

namespace tests\units\CCMBenchmark\Ting\Repository;

use CCMBenchmark\Ting\Connection;
use CCMBenchmark\Ting\ConnectionPool;
use CCMBenchmark\Ting\Driver\Mysqli\Result;
use atoum;
use CCMBenchmark\Ting\Query\PreparedQuery;
use CCMBenchmark\Ting\Query\Query;
use tests\fixtures\model\Bouh;

class Repository extends atoum
{
    public function testGet()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockConnection     = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'bouh_world');
        $fakeDriver         = new \mock\Fake\Mysqli();
        $mockDriver         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver($fakeDriver);

        $services->get('MetadataRepository')->batchLoadMetadata(
            'tests\fixtures\model',
            __DIR__ . '/../../../fixtures/model/*Repository.php'
        );

        $mockQuery        = new \mock\CCMBenchmark\Ting\Query\Query('', $mockConnection, $services->get('CollectionFactory'));
        $mockQueryFactory = new \mock\CCMBenchmark\Ting\Query\QueryFactory();

        $this->calling($mockQueryFactory)->get = $mockQuery;

        $this->calling($mockConnectionPool)->slave = $mockDriver;

        $entity = new Bouh();
        $entity->setName('Bouh');

        $mockMysqliResult                               = new \mock\tests\fixtures\FakeDriver\MysqliResult([['Bouh']]);
        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields             = [];
            $stdClass           = new \stdClass();
            $stdClass->name     = 'name';
            $stdClass->orgname  = 'boo_name';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[]           = $stdClass;

            return $fields;
        };

        $result = new Result();
        $result->setResult($mockMysqliResult);
        $result->setConnectionName('main');
        $result->setDatabase('bouh_world');

        $hydrator = new \CCMBenchmark\Ting\Repository\Hydrator();
        $hydrator->setMetadataRepository($services->get('MetadataRepository'));
        $hydrator->setUnitOfWork($services->get('UnitOfWork'));

        $mockCollection = new \mock\CCMBenchmark\Ting\Repository\Collection($hydrator);
        $mockCollection->set($result);
        $this->calling($mockCollection)->count = 1;
        $this->calling($mockQuery)->query      = $mockCollection;

        $this
            ->if($repository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $mockQueryFactory,
                $services->get('CollectionFactory'),
                $services->get('Cache'),
                $services->get('UnitOfWork'),
                $services->get('SerializerFactory')
            ))
            ->then($retrievedEntity = $repository->get([]))
            ->string($retrievedEntity->getName())
                ->isIdenticalTo($entity->getName())
            ->mock($mockQuery)
                ->call('query')
                    ->once();
    }

    public function testGetOnMaster()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockConnection     = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'db');
        $fakeDriver         = new \mock\Fake\Mysqli();
        $mockDriver         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver($fakeDriver);

        $services->get('MetadataRepository')->batchLoadMetadata(
            'tests\fixtures\model',
            __DIR__ . '/../../../fixtures/model/*Repository.php'
        );

        $mockQuery        = new \mock\CCMBenchmark\Ting\Query\Query('', $mockConnection, $services->get('CollectionFactory'));
        $mockQueryFactory = new \mock\CCMBenchmark\Ting\Query\QueryFactory();

        $this->calling($mockQueryFactory)->get = $mockQuery;

        $this->calling($mockConnectionPool)->master = $mockDriver;
        $this->calling($mockQuery)->query           = new \CCMBenchmark\Ting\Repository\Collection();

        $this
            ->if($repository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $mockQueryFactory,
                $services->get('CollectionFactory'),
                $services->get('Cache'),
                $services->get('UnitOfWork'),
                $services->get('SerializerFactory')
            ))
            ->variable($repository->get([], true))
                ->isNull()
            ->mock($mockQuery)
                ->call('selectMaster')
                    ->withArguments(true)
                        ->once()
                ->call('query')
                    ->once();
    }

    public function testStartTransactionShouldOpenTransaction()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $fakeDriver         = new \mock\Fake\Mysqli();
        $mockDriver         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver($fakeDriver);

        $services->get('MetadataRepository')->batchLoadMetadata(
            'tests\fixtures\model',
            __DIR__ . '/../../../fixtures/model/*Repository.php'
        );

        $this->calling($mockConnectionPool)->master = $mockDriver;

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('QueryFactory'),
                $services->get('CollectionFactory'),
                $services->get('Cache'),
                $services->get('UnitOfWork'),
                $services->get('SerializerFactory')
            ))
            ->then($bouhRepository->startTransaction())
            ->mock($mockDriver)
                ->call('startTransaction')
                    ->once();
    }

    public function testCommitShouldCloseTransaction()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $fakeDriver         = new \mock\Fake\Mysqli();
        $mockDriver         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver($fakeDriver);

        $services->get('MetadataRepository')->batchLoadMetadata(
            'tests\fixtures\model',
            __DIR__ . '/../../../fixtures/model/*Repository.php'
        );

        $this->calling($mockConnectionPool)->master = $mockDriver;

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('QueryFactory'),
                $services->get('CollectionFactory'),
                $services->get('Cache'),
                $services->get('UnitOfWork'),
                $services->get('SerializerFactory')
            ))
            ->then($bouhRepository->startTransaction())
            ->then($bouhRepository->commit())
            ->mock($mockDriver)
                ->call('commit')
                    ->once();
    }

    public function testRollbackShouldCloseTransaction()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $fakeDriver         = new \mock\Fake\Mysqli();
        $mockDriver         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver($fakeDriver);

        $services->get('MetadataRepository')->batchLoadMetadata(
            'tests\fixtures\model',
            __DIR__ . '/../../../fixtures/model/*Repository.php'
        );

        $this->calling($mockConnectionPool)->master = $mockDriver;

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('QueryFactory'),
                $services->get('CollectionFactory'),
                $services->get('Cache'),
                $services->get('UnitOfWork'),
                $services->get('SerializerFactory')
            ))
            ->then($bouhRepository->startTransaction())
            ->then($bouhRepository->rollback())
            ->mock($mockDriver)
                ->call('rollback')
                    ->once();
    }

    public function testSaveShouldCallUnitOfWorkSaveThenProcess()
    {
        $services                                 = new \CCMBenchmark\Ting\Services();
        $mockConnectionPool                       = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockUnitOfWork                           = new \mock\CCMBenchmark\Ting\UnitOfWork(
            $mockConnectionPool,
            $services->get('MetadataRepository'),
            $services->get('QueryFactory')
        );
        $this->calling($mockUnitOfWork)->pushSave = $mockUnitOfWork;
        $this->calling($mockUnitOfWork)->process  = true;

        $services->get('MetadataRepository')->batchLoadMetadata(
            'tests\fixtures\model',
            __DIR__ . '/../../../fixtures/model/*Repository.php'
        );

        $entity = new Bouh();

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('QueryFactory'),
                $services->get('CollectionFactory'),
                $services->get('Cache'),
                $mockUnitOfWork,
                $services->get('SerializerFactory')
            ))
            ->then($bouhRepository->save($entity))
            ->mock($mockUnitOfWork)
                ->call('pushSave')
                    ->once()
                ->call('process')
                    ->once();
    }

    public function testDeleteShouldCallUnitOfWorkDeleteThenProcess()
    {
        $services                                   = new \CCMBenchmark\Ting\Services();
        $mockConnectionPool                         = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockUnitOfWork                             = new \mock\CCMBenchmark\Ting\UnitOfWork(
            $mockConnectionPool,
            $services->get('MetadataRepository'),
            $services->get('QueryFactory')
        );
        $this->calling($mockUnitOfWork)->pushDelete = $mockUnitOfWork;
        $this->calling($mockUnitOfWork)->process    = true;

        $services->get('MetadataRepository')->batchLoadMetadata(
            'tests\fixtures\model',
            __DIR__ . '/../../../fixtures/model/*Repository.php'
        );

        $entity = new Bouh();

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('QueryFactory'),
                $services->get('CollectionFactory'),
                $services->get('Cache'),
                $mockUnitOfWork,
                $services->get('SerializerFactory')
            ))
            ->then($bouhRepository->delete($entity))
            ->mock($mockUnitOfWork)
                ->call('pushDelete')
                    ->once()
                ->call('process')
                    ->once();
    }

    public function testGetQueryShouldCallQueryFactoryGet()
    {
        $services         = new \CCMBenchmark\Ting\Services();
        $mockQueryFactory = new \mock\CCMBenchmark\Ting\Query\QueryFactory();
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockConnection = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'db');

        $this->calling($mockQueryFactory)->get = new Query('QUERY', $mockConnection);

        $services->get('MetadataRepository')->batchLoadMetadata(
            'tests\fixtures\model',
            __DIR__ . '/../../../fixtures/model/*Repository.php'
        );

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository(
                $services->get('ConnectionPool'),
                $services->get('MetadataRepository'),
                $mockQueryFactory,
                $services->get('CollectionFactory'),
                $services->get('Cache'),
                $services->get('UnitOfWork'),
                $services->get('SerializerFactory')
            ))
            ->then($bouhRepository->getQuery('QUERY'))
            ->mock($mockQueryFactory)
                ->call('get')
                    ->once();
    }

    public function testGetPreparedQueryShouldCallQueryFactoryGetPrepared()
    {
        $services         = new \CCMBenchmark\Ting\Services();
        $mockQueryFactory = new \mock\CCMBenchmark\Ting\Query\QueryFactory();
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockConnection = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'db');
        $this->calling($mockQueryFactory)->getPrepared = new PreparedQuery('QUERY', $mockConnection);

        $services->get('MetadataRepository')->batchLoadMetadata(
            'tests\fixtures\model',
            __DIR__ . '/../../../fixtures/model/*Repository.php'
        );

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository(
                $services->get('ConnectionPool'),
                $services->get('MetadataRepository'),
                $mockQueryFactory,
                $services->get('CollectionFactory'),
                $services->get('Cache'),
                $services->get('UnitOfWork'),
                $services->get('SerializerFactory')
            ))
            ->then($bouhRepository->getPreparedQuery('QUERY'))
            ->mock($mockQueryFactory)
                ->call('getPrepared')
                    ->once();
    }

    public function testGetCachedQueryShouldCallQueryFactoryGetCached()
    {
        $services         = new \CCMBenchmark\Ting\Services();
        $mockQueryFactory = new \mock\CCMBenchmark\Ting\Query\QueryFactory();
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockConnection = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'db');
        $this->calling($mockQueryFactory)->getCached = new \CCMBenchmark\Ting\Query\Cached\Query('QUERY', $mockConnection);

        $services->get('MetadataRepository')->batchLoadMetadata(
            'tests\fixtures\model',
            __DIR__ . '/../../../fixtures/model/*Repository.php'
        );

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository(
                $services->get('ConnectionPool'),
                $services->get('MetadataRepository'),
                $mockQueryFactory,
                $services->get('CollectionFactory'),
                $services->get('Cache'),
                $services->get('UnitOfWork'),
                $services->get('SerializerFactory')
            ))
            ->then($bouhRepository->getCachedQuery('QUERY'))
            ->mock($mockQueryFactory)
                ->call('getCached')
                    ->once();
    }

    public function testGetCachedPreparedQueryShouldCallQueryFactoryGetCachedPreparedQuery()
    {
        $services         = new \CCMBenchmark\Ting\Services();
        $mockQueryFactory = new \mock\CCMBenchmark\Ting\Query\QueryFactory();
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockConnection = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'db');
        $this->calling($mockQueryFactory)->getCachedPrepared = new \CCMBenchmark\Ting\Query\Cached\PreparedQuery('QUERY', $mockConnection);

        $services->get('MetadataRepository')->batchLoadMetadata(
            'tests\fixtures\model',
            __DIR__ . '/../../../fixtures/model/*Repository.php'
        );

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository(
                $services->get('ConnectionPool'),
                $services->get('MetadataRepository'),
                $mockQueryFactory,
                $services->get('CollectionFactory'),
                $services->get('Cache'),
                $services->get('UnitOfWork'),
                $services->get('SerializerFactory')
            ))
            ->then($bouhRepository->getCachedPreparedQuery('QUERY'))
            ->mock($mockQueryFactory)
                ->call('getCachedPrepared')
                    ->once();
    }

    public function testGetAllShouldReturnAQuery()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockConnection     = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'db');
        $fakeDriver         = new \mock\Fake\Mysqli();
        $mockDriver         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver($fakeDriver);

        $services->get('MetadataRepository')->batchLoadMetadata(
            'tests\fixtures\model',
            __DIR__ . '/../../../fixtures/model/*Repository.php'
        );

        $mockQuery        = new \mock\CCMBenchmark\Ting\Query\Query('', $mockConnection, $services->get('CollectionFactory'));
        $mockQueryFactory = new \mock\CCMBenchmark\Ting\Query\QueryFactory();

        $this->calling($mockQueryFactory)->get = $mockQuery;

        $this->calling($mockConnectionPool)->slave = $mockDriver;

        $entity = new Bouh();
        $entity->setName('Bouh');

        $collection = new \CCMBenchmark\Ting\Repository\Collection();

        $this->calling($mockQuery)->query = $collection;

        $this
            ->if($repository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $mockQueryFactory,
                $services->get('CollectionFactory'),
                $services->get('Cache'),
                $services->get('UnitOfWork'),
                $services->get('SerializerFactory')
            ))
            ->object($repository->getAll())
                ->isInstanceOf(\CCMBenchmark\Ting\Repository\CollectionInterface::class);
    }

    public function testGetByCriteriaShouldReturnAQuery()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockConnection     = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'db');
        $fakeDriver         = new \mock\Fake\Mysqli();
        $mockDriver         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver($fakeDriver);

        $services->get('MetadataRepository')->batchLoadMetadata(
            'tests\fixtures\model',
            __DIR__ . '/../../../fixtures/model/*Repository.php'
        );

        $mockQuery        = new \mock\CCMBenchmark\Ting\Query\Query('', $mockConnection, $services->get('CollectionFactory'));
        $mockQueryFactory = new \mock\CCMBenchmark\Ting\Query\QueryFactory();

        $this->calling($mockQueryFactory)->get = $mockQuery;

        $this->calling($mockConnectionPool)->slave = $mockDriver;

        $entity = new Bouh();
        $entity->setName('Bouh');

        $collection = new \CCMBenchmark\Ting\Repository\Collection();

        $this->calling($mockQuery)->query = $collection;

        $this
            ->if($repository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $mockQueryFactory,
                $services->get('CollectionFactory'),
                $services->get('Cache'),
                $services->get('UnitOfWork'),
                $services->get('SerializerFactory')
            ))
            ->object($repository->getBy(['name' => 'bouh']))
                ->isInstanceOf(\CCMBenchmark\Ting\Repository\CollectionInterface::class);
    }

    public function testGetOneByCriteriaShouldReturnAnEntityOrNull()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockConnection     = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'bouh_world');
        $fakeDriver         = new \mock\Fake\Mysqli();
        $mockDriver         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver($fakeDriver);

        $services->get('MetadataRepository')->batchLoadMetadata(
            'tests\fixtures\model',
            __DIR__ . '/../../../fixtures/model/BouhRepository.php'
        );

        $mockQuery        = new \mock\CCMBenchmark\Ting\Query\Query('', $mockConnection, $services->get('CollectionFactory'));
        $mockQueryFactory = new \mock\CCMBenchmark\Ting\Query\QueryFactory();

        $this->calling($mockQueryFactory)->get = $mockQuery;

        $this->calling($mockConnectionPool)->slave = $mockDriver;

        $entity = new Bouh();
        $entity->setName('Bouh');

        $mockMysqliResult                               = new \mock\tests\fixtures\FakeDriver\MysqliResult([['Bouh']]);
        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields             = [];
            $stdClass           = new \stdClass();
            $stdClass->name     = 'name';
            $stdClass->orgname  = 'boo_name';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[]           = $stdClass;

            return $fields;
        };

        $result = new Result();
        $result->setResult($mockMysqliResult);
        $result->setConnectionName('main');
        $result->setDatabase('bouh_world');

        $hydrator = new \CCMBenchmark\Ting\Repository\Hydrator();
        $hydrator->setMetadataRepository($services->get('MetadataRepository'));
        $hydrator->setUnitOfWork($services->get('UnitOfWork'));

        $mockCollection = new \mock\CCMBenchmark\Ting\Repository\Collection($hydrator);
        $mockCollection->set($result);
        $this->calling($mockCollection)->count = 1;

        $this->calling($mockQuery)->query = $mockCollection;

        $this
            ->if($repository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $mockQueryFactory,
                $services->get('CollectionFactory'),
                $services->get('Cache'),
                $services->get('UnitOfWork'),
                $services->get('SerializerFactory')
            ))
            ->object($repository->getOneBy(['name' => 'Xavier']))
                ->isInstanceOf($entity)
            ->and($emptyCollection = new \CCMBenchmark\Ting\Repository\Collection())
            ->then($this->calling($mockQuery)->query = $emptyCollection)
            ->variable($repository->getOneBy(['name' => 'Xavier']))
                ->isNull();
    }

    public function testGetQueryBuilderShouldThrowExceptionOnUnknownDriver()
    {

        $services = new \CCMBenchmark\Ting\Services();
        $services->get('MetadataRepository')->batchLoadMetadata(
            'tests\fixtures\model',
            __DIR__ . '/../../../fixtures/model/*Repository.php'
        );

        $services->get('ConnectionPool')->setConfig([
            'main' => [
                'namespace' => '\Unknown\Driver\Mysqli'
            ]
        ]);

        $this
            ->if($bouhRepository = $services->get('RepositoryFactory')->get('\tests\fixtures\model\BouhRepository'))
            ->exception(function () use ($bouhRepository): void {
                $bouhRepository->getQueryBuilder($bouhRepository::QUERY_SELECT);
            })
                ->hasMessage('Driver Unknown\Driver\Mysqli\Driver is unknown to build QueryBuilder');
    }

    public function testGetQueryBuilder()
    {

        $services = new \CCMBenchmark\Ting\Services();
        $services->get('MetadataRepository')->batchLoadMetadata(
            'tests\fixtures\model',
            __DIR__ . '/../../../fixtures/model/*Repository.php'
        );

        $this
            ->if($services->get('ConnectionPool')
                ->setConfig(['main' => ['namespace' => '\CCMBenchmark\Ting\Driver\SphinxQL']]))
            ->then($bouhRepository = $services->get('RepositoryFactory')->get('\tests\fixtures\model\BouhRepository'))
            ->object($queryBuilder = $bouhRepository->getQueryBuilder($bouhRepository::QUERY_SELECT))
                ->isInstanceOf(\Aura\SqlQuery\Common\SelectInterface::class)
            ->if($services->get('ConnectionPool')
                ->setConfig(['main' => ['namespace' => 'CCMBenchmark\Ting\Driver\Pgsql']]))
            ->object($queryBuilder = $bouhRepository->getQueryBuilder("unkwnon"))
                ->isInstanceOf(\Aura\SqlQuery\Common\SelectInterface::class)
            ->object($queryBuilder = $bouhRepository->getQueryBuilder($bouhRepository::QUERY_UPDATE))
                ->isInstanceOf(\Aura\SqlQuery\Common\UpdateInterface::class)
            ->if($services->get('ConnectionPool')
                ->setConfig(['main' => ['namespace' => '\CCMBenchmark\Ting\Driver\Mysqli']]))
            ->object($queryBuilder = $bouhRepository->getQueryBuilder($bouhRepository::QUERY_DELETE))
                ->isInstanceOf(\Aura\SqlQuery\Common\DeleteInterface::class)
            ->object($queryBuilder = $bouhRepository->getQueryBuilder($bouhRepository::QUERY_INSERT))
                ->isInstanceOf(\Aura\SqlQuery\Common\InsertInterface::class)
        ;
    }

    public function testPingShouldPingMethodsShouldCallPingOnTheGoodConnections()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $fakeDriver         = new \mock\Fake\Mysqli();
        $mockDriverSlave    = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver($fakeDriver);
        $mockDriverMaster   = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver($fakeDriver);

        $services->get('MetadataRepository')->batchLoadMetadata(
            'tests\fixtures\model',
            __DIR__ . '/../../../fixtures/model/*Repository.php'
        );

        $this->calling($mockConnectionPool)->slave = $mockDriverSlave;
        $this->calling($mockConnectionPool)->master = $mockDriverMaster;
        $this->calling($mockDriverMaster)->ping = true;
        $this->calling($mockDriverSlave)->ping = true;

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('QueryFactory'),
                $services->get('CollectionFactory'),
                $services->get('Cache'),
                $services->get('UnitOfWork'),
                $services->get('SerializerFactory')
            ))
            ->then($bouhRepository->ping())
                ->mock($mockDriverSlave)
                    ->call('ping')
                        ->once()
            ->then($bouhRepository->pingMaster())
                ->mock($mockDriverMaster)
                    ->call('ping')
                        ->once()
        ;
    }

    public function testGetMetadata()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $fakeDriver         = new \mock\Fake\Mysqli();
        $mockDriverSlave    = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver($fakeDriver);
        $mockDriverMaster   = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver($fakeDriver);
        $metadataRepository = new \mock\CCMBenchmark\Ting\MetadataRepository($services->get('SerializerFactory'));
        $metadata           = new \mock\CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory'));
        $mockConnection     = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'db');
        $this->calling($metadata)->getConnection = $mockConnection;

        $this->calling($metadataRepository)
            ->findMetadataForRepository = function ($repository, $callback, $error) use ($metadata): void {
                $callback($metadata);
            };

        $this->calling($mockConnectionPool)->slave = $mockDriverSlave;
        $this->calling($mockConnectionPool)->master = $mockDriverMaster;

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $metadataRepository,
                $services->get('QueryFactory'),
                $services->get('CollectionFactory'),
                $services->get('Cache'),
                $services->get('UnitOfWork'),
                $services->get('SerializerFactory')
            ))
            ->then
                ->object($bouhRepository->getMetadata())
                    ->isIdenticalTo($metadata)
        ;
    }
}
