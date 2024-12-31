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

class Query extends atoum
{
    public function testSetParamsShouldReturnThis()
    {
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockConnection = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'database');
        $this
            ->if($query = new \CCMBenchmark\Ting\Query\Query('SELECT', $mockConnection))
            ->object($query->setParams([]))
                ->isIdenticalTo($query)
        ;
    }

    public function testExecuteShouldCallExecuteOnMasterDriver()
    {
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockDriver = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();
        $mockConnection = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'database');

        $this->calling($mockConnection)->master = $mockDriver;
        $this->calling($mockDriver)->execute = true;

        $this
            ->if($query = new \CCMBenchmark\Ting\Query\Query('INSERT', $mockConnection))
            ->then($query->execute())
                ->mock($mockConnection)
                    ->call('master')
                        ->once()
                ->mock($mockDriver)
                    ->call('execute')
                        ->once()
        ;
    }

    public function testQueryShouldCallExecuteOnSlaveDriver()
    {
        $services              = new \CCMBenchmark\Ting\Services();
        $mockConnectionPool    = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockDriver            = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();
        $mockConnection        = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'database');
        $mockCollectionFactory = new \mock\CCMBenchmark\Ting\Repository\CollectionFactory(
            $services->get('MetadataRepository'),
            $services->get('UnitOfWork'),
            $services->get('Hydrator')
        );

        $this->calling($mockConnection)->slave = $mockDriver;
        $this->calling($mockDriver)->execute = true;
        $this->calling($mockCollectionFactory)->get = new Collection($services->get('Hydrator'));

        $this
            ->if($query = new \CCMBenchmark\Ting\Query\Query('SELECT', $mockConnection, $mockCollectionFactory))
            ->then($query->query())
                ->mock($mockConnection)
                    ->call('slave')
                        ->once()
                ->mock($mockDriver)
                    ->call('execute')
                        ->once()
                ->mock($mockCollectionFactory)
                    ->call('get')
                        ->once()
        ;
    }

    public function testQueryShouldCallExecuteOnMasterDriver()
    {
        $services              = new \CCMBenchmark\Ting\Services();
        $mockConnectionPool    = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockDriver            = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();
        $mockConnection        = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'database');
        $mockCollectionFactory = new \mock\CCMBenchmark\Ting\Repository\CollectionFactory(
            $services->get('MetadataRepository'),
            $services->get('UnitOfWork'),
            $services->get('Hydrator')
        );

        $this->calling($mockConnection)->master = $mockDriver;
        $this->calling($mockDriver)->execute = true;
        $this->calling($mockCollectionFactory)->get = new Collection();

        $this
            ->if($query = new \CCMBenchmark\Ting\Query\Query('SELECT', $mockConnection, $mockCollectionFactory))
            ->then($query->selectMaster(true))
            ->then($query->query())
                ->mock($mockConnection)
                    ->call('master')
                        ->once()
                ->mock($mockDriver)
                    ->call('execute')
                        ->once()
                ->mock($mockCollectionFactory)
                    ->call('get')
                        ->once()
        ;
    }

    public function testGetInsertIdShouldCallMasterDriver()
    {
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockDriver = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();
        $mockConnection = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'database');

        $this->calling($mockConnection)->master = $mockDriver;
        $this->calling($mockDriver)->getInsertedId = 1;

        $this
            ->if($query = new \CCMBenchmark\Ting\Query\Query('INSERT', $mockConnection))
            ->integer($query->getInsertedId())
                ->isIdenticalTo(1)
            ->mock($mockConnection)
                ->call('master')
                    ->once()
            ->mock($mockDriver)
                ->call('getInsertedId')
                    ->once()
        ;
    }

    public function testGetAffectedRowsShouldCallMasterDriver()
    {
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockDriver = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();
        $mockConnection = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'database');

        $this->calling($mockConnection)->master = $mockDriver;
        $this->calling($mockDriver)->getAffectedRows = 4;

        $this
            ->if($query = new \CCMBenchmark\Ting\Query\Query('INSERT', $mockConnection))
            ->integer($query->getAffectedRows())
                ->isIdenticalTo(4)
            ->mock($mockConnection)
                ->call('master')
                    ->once()
            ->mock($mockDriver)
                ->call('getAffectedRows')
                    ->once()
        ;
    }
}
