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

namespace tests\units\CCMBenchmark\Ting;

use atoum;
use CCMBenchmark\Ting\Driver\DriverInterface;

class Connection extends atoum
{
    protected $mockConnectionPool;
    protected $mockDriver;

    public function beforeTestMethod($method)
    {
        $this->mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $this->mockDriver         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();
    }

    public function testMasterShouldReturnMasterDriver()
    {
        $this->calling($this->mockConnectionPool)->master = $this->mockDriver;

        $this
            ->if($connection = new \CCMBenchmark\Ting\Connection($this->mockConnectionPool, 'main', 'db'))
            ->object($connection->master())
                ->isInstanceOf(DriverInterface::class)
        ;
    }

    public function testSlaveShouldReturnSlaveDriver()
    {
        $this->calling($this->mockConnectionPool)->slave = $this->mockDriver;

        $this
            ->if($connection = new \CCMBenchmark\Ting\Connection($this->mockConnectionPool, 'main', 'db'))
            ->object($connection->slave())
                ->isInstanceOf(DriverInterface::class)
        ;
    }

    public function testStartTransactionShouldCallMasterStartTransaction()
    {
        $this->calling($this->mockConnectionPool)->master   = $this->mockDriver;
        $this->calling($this->mockDriver)->startTransaction = true;

        $this
            ->if($connection = new \CCMBenchmark\Ting\Connection($this->mockConnectionPool, 'main', 'db'))
            ->then($connection->startTransaction())
                ->mock($this->mockDriver)
                    ->call('startTransaction')
                        ->once()
        ;
    }

    public function testRollbackShouldCallMasterRollback()
    {
        $this->calling($this->mockConnectionPool)->master   = $this->mockDriver;
        $this->calling($this->mockDriver)->rollback = true;

        $this
            ->if($connection = new \CCMBenchmark\Ting\Connection($this->mockConnectionPool, 'main', 'db'))
            ->then($connection->rollback())
                ->mock($this->mockDriver)
                    ->call('rollback')
                        ->once()
        ;
    }

    public function testCommitShouldCallMasterCommit()
    {
        $this->calling($this->mockConnectionPool)->master   = $this->mockDriver;
        $this->calling($this->mockDriver)->commit = true;

        $this
            ->if($connection = new \CCMBenchmark\Ting\Connection($this->mockConnectionPool, 'main', 'db'))
            ->then($connection->commit())
                ->mock($this->mockDriver)
                    ->call('commit')
                        ->once()
        ;
    }
}
