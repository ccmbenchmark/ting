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
use CCMBenchmark\Ting\Repository\CollectionFactory;
use mageekguy\atoum;
use tests\fixtures\model\Bouh;
use tests\fixtures\model\BouhRepository;

class Repository extends atoum
{
    public function testGet()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockConnection     = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'db');
        $fakeDriver         = new \mock\Fake\Mysqli();
        $mockDriver         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver($fakeDriver);

        $mockQuery = new \mock\CCMBenchmark\Ting\Query\Query('', $mockConnection, $services->get('CollectionFactory'));
        $mockQueryFactory  = new \mock\CCMBenchmark\Ting\Query\QueryFactory();

        $this->calling($mockQueryFactory)->get = $mockQuery;

        $this->calling($mockConnectionPool)->slave  = $mockDriver;

        $entity = new Bouh();
        $entity->setName('Bouh');

        $collection = new \CCMBenchmark\Ting\Repository\Collection();
        $collection->add(['entity' => $entity]);

        $this->calling($mockQuery)->query = $collection;

        $this
            ->if($repository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $mockQueryFactory,
                $services->get('CollectionFactory'),
                $services->get('Cache'),
                $services->get('UnitOfWork')
            ))
            ->variable($repository->get([]))
                ->isIdenticalTo($entity)
            ->mock($mockQuery)
                ->call('query')
                    ->once()
        ;
    }

    public function testGetOnMaster()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockConnection     = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'db');
        $fakeDriver         = new \mock\Fake\Mysqli();
        $mockDriver         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver($fakeDriver);

        $mockQuery = new \mock\CCMBenchmark\Ting\Query\Query('', $mockConnection, $services->get('CollectionFactory'));
        $mockQueryFactory  = new \mock\CCMBenchmark\Ting\Query\QueryFactory();

        $this->calling($mockQueryFactory)->get = $mockQuery;

        $this->calling($mockConnectionPool)->master  = $mockDriver;
        $this->calling($mockQuery)->query = new \CCMBenchmark\Ting\Repository\Collection();

        $this
            ->if($repository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $mockQueryFactory,
                $services->get('CollectionFactory'),
                $services->get('Cache'),
                $services->get('UnitOfWork')
            ))
            ->variable($repository->get([], true))
                ->isNull()
            ->mock($mockQuery)
                ->call('selectMaster')
                    ->withArguments(true)
                    ->once()
                ->call('query')
                    ->once()
        ;
    }

    public function testStartTransactionShouldOpenTransaction()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $fakeDriver         = new \mock\Fake\Mysqli();
        $mockDriver         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver($fakeDriver);

        $this->calling($mockConnectionPool)->master = $mockDriver;

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('QueryFactory'),
                $services->get('CollectionFactory'),
                $services->get('Cache'),
                $services->get('UnitOfWork')
            ))
            ->then($bouhRepository->startTransaction())
            ->mock($mockDriver)
                ->call('startTransaction')
                    ->once()
        ;
    }

    public function testCommitShouldCloseTransaction()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $fakeDriver         = new \mock\Fake\Mysqli();
        $mockDriver         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver($fakeDriver);

        $this->calling($mockConnectionPool)->master = $mockDriver;

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('QueryFactory'),
                $services->get('CollectionFactory'),
                $services->get('Cache'),
                $services->get('UnitOfWork')
            ))
            ->then($bouhRepository->startTransaction())
            ->then($bouhRepository->commit())
            ->mock($mockDriver)
                ->call('commit')
                    ->once()
        ;
    }

    public function testRollbackShouldCloseTransaction()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $fakeDriver         = new \mock\Fake\Mysqli();
        $mockDriver         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver($fakeDriver);

        $this->calling($mockConnectionPool)->master = $mockDriver;

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('QueryFactory'),
                $services->get('CollectionFactory'),
                $services->get('Cache'),
                $services->get('UnitOfWork')
            ))
            ->then($bouhRepository->startTransaction())
            ->then($bouhRepository->rollback())
            ->mock($mockDriver)
                ->call('rollback')
                    ->once()
        ;
    }

    public function testSaveShouldCallUnitOfWorkSaveThenProcess()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockUnitOfWork = new \mock\CCMBenchmark\Ting\UnitOfWork(
            $mockConnectionPool,
            $services->get('MetadataRepository'),
            $services->get('QueryFactory')
        );
        $this->calling($mockUnitOfWork)->pushSave = $mockUnitOfWork;
        $this->calling($mockUnitOfWork)->process = true;

        $entity = new Bouh();

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('QueryFactory'),
                $services->get('CollectionFactory'),
                $services->get('Cache'),
                $mockUnitOfWork
            ))
            ->then($bouhRepository->save($entity))
            ->mock($mockUnitOfWork)
                ->call('pushSave')
                    ->once()
                ->call('process')
                    ->once()
        ;
    }

    public function testDeleteShouldCallUnitOfWorkDeleteThenProcess()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockUnitOfWork = new \mock\CCMBenchmark\Ting\UnitOfWork(
            $mockConnectionPool,
            $services->get('MetadataRepository'),
            $services->get('QueryFactory')
        );
        $this->calling($mockUnitOfWork)->pushDelete = $mockUnitOfWork;
        $this->calling($mockUnitOfWork)->process  = true;

        $entity = new Bouh();

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('QueryFactory'),
                $services->get('CollectionFactory'),
                $services->get('Cache'),
                $mockUnitOfWork
            ))
            ->then($bouhRepository->delete($entity))
            ->mock($mockUnitOfWork)
                ->call('pushDelete')
                    ->once()
                ->call('process')
                    ->once()
        ;
    }

    public function testGetQueryShouldCallQueryFactoryGet()
    {
        $services         = new \CCMBenchmark\Ting\Services();
        $mockQueryFactory = new \mock\CCMBenchmark\Ting\Query\QueryFactory();

        $this->calling($mockQueryFactory)->get = true;

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository(
                $services->get('ConnectionPool'),
                $services->get('MetadataRepository'),
                $mockQueryFactory,
                $services->get('CollectionFactory'),
                $services->get('Cache'),
                $services->get('UnitOfWork')
            ))
            ->then($bouhRepository->getQuery('QUERY'))
            ->mock($mockQueryFactory)
                ->call('get')
                    ->once()
        ;
    }

    public function testGetPreparedQueryShouldCallQueryFactoryGetPrepared()
    {
        $services         = new \CCMBenchmark\Ting\Services();
        $mockQueryFactory = new \mock\CCMBenchmark\Ting\Query\QueryFactory();

        $this->calling($mockQueryFactory)->getPrepared = true;

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository(
                $services->get('ConnectionPool'),
                $services->get('MetadataRepository'),
                $mockQueryFactory,
                $services->get('CollectionFactory'),
                $services->get('Cache'),
                $services->get('UnitOfWork')
            ))
            ->then($bouhRepository->getPreparedQuery('QUERY'))
            ->mock($mockQueryFactory)
                ->call('getPrepared')
                    ->once()
        ;
    }

    public function testGetCachedQueryShouldCallQueryFactoryGetCached()
    {
        $services         = new \CCMBenchmark\Ting\Services();
        $mockQueryFactory = new \mock\CCMBenchmark\Ting\Query\QueryFactory();

        $this->calling($mockQueryFactory)->getCached = true;

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository(
                $services->get('ConnectionPool'),
                $services->get('MetadataRepository'),
                $mockQueryFactory,
                $services->get('CollectionFactory'),
                $services->get('Cache'),
                $services->get('UnitOfWork')
            ))
            ->then($bouhRepository->getCachedQuery('QUERY'))
            ->mock($mockQueryFactory)
                ->call('getCached')
                    ->once()
        ;
    }

    public function testGetCachedPreparedQueryShouldCallQueryFactoryGetCachedPreparedQuery()
    {
        $services         = new \CCMBenchmark\Ting\Services();
        $mockQueryFactory = new \mock\CCMBenchmark\Ting\Query\QueryFactory();

        $this->calling($mockQueryFactory)->getCachedPrepared = true;

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository(
                $services->get('ConnectionPool'),
                $services->get('MetadataRepository'),
                $mockQueryFactory,
                $services->get('CollectionFactory'),
                $services->get('Cache'),
                $services->get('UnitOfWork')
            ))
            ->then($bouhRepository->getCachedPreparedQuery('QUERY'))
            ->mock($mockQueryFactory)
                ->call('getCachedPrepared')
                    ->once()
        ;
    }
}
