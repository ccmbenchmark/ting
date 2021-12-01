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

namespace tests\units\CCMBenchmark\Ting\Query;

use CCMBenchmark\Ting\Repository\Collection;
use atoum;

class PreparedQuery extends atoum
{

    public function testPrepareQueryShouldCallSlavePrepare()
    {
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockDriver = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();
        $mockConnection = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'database');

        $this->calling($mockConnection)->slave = $mockDriver;
        $this->calling($mockDriver)->prepare = true;

        $this
            ->if($query = new \CCMBenchmark\Ting\Query\PreparedQuery('SELECT', $mockConnection))
            ->object($query->prepareQuery())
                ->isIdenticalTo($query)
            ->mock($mockConnection)
                ->call('slave')
                    ->once()
            ->mock($mockDriver)
                ->call('prepare')
                    ->once()
        ;
    }

    public function testPrepareQueryShouldCallMasterPrepare()
    {
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockDriver = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();
        $mockConnection = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'database');

        $this->calling($mockConnection)->master = $mockDriver;
        $this->calling($mockDriver)->prepare = true;

        $this
            ->if($query = new \CCMBenchmark\Ting\Query\PreparedQuery('SELECT', $mockConnection))
            ->then($query->selectMaster(true))
            ->object($query->prepareQuery())
                ->isIdenticalTo($query)
            ->object($query->prepareQuery())
                ->isIdenticalTo($query)
            ->mock($mockConnection)
                ->call('master')
                    ->once()
            ->mock($mockDriver)
                ->call('prepare')
                    ->once()
        ;
    }

    public function testPrepareExecuteShouldCallMasterPrepare()
    {
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockDriver = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();
        $mockConnection = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'database');

        $this->calling($mockConnection)->master = $mockDriver;
        $this->calling($mockDriver)->prepare = true;

        $this
            ->if($query = new \CCMBenchmark\Ting\Query\PreparedQuery('SELECT', $mockConnection))
            ->then($query->selectMaster(true))
            ->object($query->prepareExecute())
                ->isIdenticalTo($query)
            ->object($query->prepareExecute())
                ->isIdenticalTo($query)
            ->mock($mockConnection)
                ->call('master')
                    ->once()
            ->mock($mockDriver)
                ->call('prepare')
                    ->once()
        ;
    }

    public function testExecuteShouldCallStatementExecute()
    {
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockDriver = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();
        $mockConnection = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'database');
        $mockMysqliStatement = new \mock\Fake\mysqli_stmt();
        $mockStatement = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Statement(
            $mockMysqliStatement,
            [],
            'connectionName',
            'database'
        );

        $this->calling($mockStatement)->execute = true;
        $this->calling($mockDriver)->prepare = $mockStatement;
        $this->calling($mockConnection)->master = $mockDriver;

        $this
            ->if($query = new \CCMBenchmark\Ting\Query\PreparedQuery('SELECT', $mockConnection))
            ->object($query->prepareExecute())
                ->isIdenticalTo($query)
            ->then($query->setParams(['id' => 12]))
            ->then($query->execute())
            ->mock($mockStatement)
                ->call('execute')
                    ->once()
        ;
    }

    public function testQueryShouldCallStatementExecuteAndReturnCollection()
    {
        $services              = new \CCMBenchmark\Ting\Services();
        $mockConnectionPool    = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockDriver            = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();
        $mockConnection        = new \mock\CCMBenchmark\Ting\Connection(
            $mockConnectionPool,
            'connectionName',
            'database'
        );
        $mockMysqliStatement   = new \mock\Fake\mysqli_stmt();
        $mockStatement         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Statement(
            $mockMysqliStatement,
            [],
            'connectionName',
            'database'
        );
        $mockCollectionFactory = new \mock\CCMBenchmark\Ting\Repository\CollectionFactory(
            $services->get('MetadataRepository'),
            $services->get('UnitOfWork'),
            $services->get('Hydrator')
        );

        $collection = new Collection();

        $this->calling($mockStatement)->execute = $collection;
        $this->calling($mockDriver)->prepare = $mockStatement;
        $this->calling($mockConnection)->slave = $mockDriver;
        $this->calling($mockCollectionFactory)->get = $collection;

        $this
            ->if($query = new \CCMBenchmark\Ting\Query\PreparedQuery('SELECT', $mockConnection, $mockCollectionFactory))
            ->object($query->prepareQuery())
                ->isIdenticalTo($query)
            ->then($query->setParams(['id' => 12]))
            ->object($query->query())
                ->isIdenticalTo($collection)
            ->mock($mockStatement)
                ->call('execute')
                    ->once()
        ;
    }

    public function testGetStatementNameShouldReturnAString()
    {
        $services              = new \CCMBenchmark\Ting\Services();
        $mockConnectionPool    = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockConnection        = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'database');
        $mockCollectionFactory = new \mock\CCMBenchmark\Ting\Repository\CollectionFactory(
            $services->get('MetadataRepository'),
            $services->get('UnitOfWork'),
            $services->get('Hydrator')
        );

        $this
            ->if($query = new \CCMBenchmark\Ting\Query\PreparedQuery('SELECT', $mockConnection, $mockCollectionFactory))
            ->string($query->getStatementName())
            ->isNotEmpty();
    }
}
