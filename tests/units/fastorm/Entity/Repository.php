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

namespace tests\units\CCMBenchmark\Ting\Entity;

use \mageekguy\atoum;

class Repository extends atoum
{
    public function testExecuteShouldExecuteQuery()
    {
        $mockDriver         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $this->calling($mockConnectionPool)->connect =
            function ($connectionName, $database, callable $callback) use ($mockDriver) {
                $callback($mockDriver);
            };

        $services  = new \CCMBenchmark\Ting\Services();
        $mockQuery = new \mock\CCMBenchmark\Ting\Query\Query(['sql' => 'SELECT * FROM bouh']);
        $this->calling($mockQuery)->execute =
            function ($collection) use (&$outerCollection) {
                $outerCollection = $collection;
            };

        $collection = new \CCMBenchmark\Ting\Entity\Collection();

        $this
            ->if($repository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('MetadataFactory'),
                $services->get('Collection'),
                $services->get('Hydrator')
            ))
            ->then($repository->execute($mockQuery, $collection))
            ->object($outerCollection)
                ->isIdenticalTo($collection);
    }

    public function testExecuteShouldReturnACollectionIfNoParam()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $mockDriver         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();

        $this->calling($mockConnectionPool)->connect =
            function ($connectionName, $database, callable $callback) use ($mockDriver) {
                $callback($mockDriver);
            };

        $mockQuery = new \mock\CCMBenchmark\Ting\Query\Query(['sql' => 'SELECT * FROM bouh']);
        $this->calling($mockQuery)->execute =
            function ($collection) use (&$outerCollection) {
                $outerCollection = $collection;
            };
        $this
            ->if($repository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('MetadataFactory'),
                $services->get('Collection'),
                $services->get('Hydrator')
            ))
            ->then($repository->execute($mockQuery))
            ->object($outerCollection)
                ->isInstanceOf('\CCMBenchmark\Ting\Entity\Collection');
    }

    public function testExecutePreparedShouldPrepareAndExecuteQuery()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $mockDriver         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();

        $this->calling($mockConnectionPool)->connect =
            function ($connectionName, $database, callable $callback) use ($mockDriver) {
                $callback($mockDriver);
            };

        $mockQuery = new \mock\CCMBenchmark\Ting\Query\PreparedQuery(
            ['sql' => 'SELECT * FROM bouh WHERE truc = :bidule']
        );
        $this->calling($mockQuery)->prepare =
            function () use ($mockQuery) {
                return $mockQuery;
            }
        ;
        $this->calling($mockQuery)->execute =
            function ($collection) use (&$outerCollection) {
                $outerCollection = $collection;
            };

        $collection = new \CCMBenchmark\Ting\Entity\Collection();

        $this
            ->if($repository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('MetadataFactory'),
                $services->get('Collection'),
                $services->get('Hydrator')
            ))
            ->then($repository->executePrepared($mockQuery, $collection))
            ->object($outerCollection)
                ->isIdenticalTo($collection);
    }

    public function testExecutePreparedShouldReturnACollectionIfNoParam()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $mockDriver         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();

        $this->calling($mockConnectionPool)->connect =
            function ($connectionName, $database, callable $callback) use ($mockDriver) {
                $callback($mockDriver);
            };

        $mockQuery = new \mock\CCMBenchmark\Ting\Query\PreparedQuery(
            ['sql' => 'SELECT * FROM bouh WHERE truc = :bidule']
        );
        $this->calling($mockQuery)->prepare =
            function () use ($mockQuery) {
                return $mockQuery;
            }
        ;
        $this->calling($mockQuery)->execute =
            function ($collection) use (&$outerCollection) {
                $outerCollection = $collection;
            };
        $this
            ->if($repository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('MetadataFactory'),
                $services->get('Collection'),
                $services->get('Hydrator')
            ))
            ->then($repository->executePrepared($mockQuery))
            ->object($outerCollection)
                ->isInstanceOf('\CCMBenchmark\Ting\Entity\Collection');
    }

    public function testGet()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $driverFake         = new \mock\Fake\Mysqli();
        $mockDriver         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver($driverFake);
        $mockMysqliResult   = new \mock\tests\fixtures\FakeDriver\MysqliResult(array());

        $this->calling($driverFake)->query = $mockMysqliResult;

        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = array();
            $stdClass = new \stdClass();
            $stdClass->name     = 'id';
            $stdClass->orgname  = 'boo_id';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'prenom';
            $stdClass->orgname  = 'boo_firstname';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            return $fields;
        };

        $this->calling($mockMysqliResult)->fetch_array = function ($type) {
            return array(3, 'Sylvain');
        };

        $this->calling($mockConnectionPool)->connect =
            function ($connectionName, $database, callable $callback) use ($mockDriver) {
                $callback($mockDriver);
            };

        $bouh = new \tests\fixtures\model\Bouh();
        $bouh->setId(3);
        $bouh->setfirstname('Sylvain');

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('MetadataFactory'),
                $services->get('Collection'),
                $services->get('Hydrator')
            ))
            ->and($testBouh = $bouhRepository->get(3))
            ->integer($testBouh->getId())
                ->isIdenticalTo($bouh->getId())
            ->string($testBouh->getFirstname())
                ->isIdenticalTo($bouh->getFirstname());
    }

    public function testStartTransactionShouldOpenTransaction()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $fakeDriver         = new \mock\Fake\Mysqli();
        $mockDriver         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver($fakeDriver);
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();

        $services->set('ConnectionPool', function ($container) use ($mockConnectionPool) {
            return $mockConnectionPool;
        });

        $this->calling($mockConnectionPool)->connect =
            function ($connectionName, $database, callable $callback) use ($mockDriver) {
                $callback($mockDriver);
            };

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('MetadataFactory'),
                $services->get('Collection'),
                $services->get('Hydrator')
            ))
            ->then($bouhRepository->startTransaction())
            ->boolean($mockDriver->isTransactionOpened())
                ->isTrue();
    }

    public function testCommitShouldCloseTransaction()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $fakeDriver         = new \mock\Fake\Mysqli();
        $mockDriver         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver($fakeDriver);
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();

        $this->calling($mockConnectionPool)->connect =
            function ($connectionName, $database, callable $callback) use ($mockDriver) {
                $callback($mockDriver);
            };

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('MetadataFactory'),
                $services->get('Collection'),
                $services->get('Hydrator')
            ))
            ->then($bouhRepository->startTransaction())
            ->then($bouhRepository->commit())
            ->boolean($mockDriver->isTransactionOpened())
                ->isFalse()
        ;
    }

    public function testRollbackShouldCloseTransaction()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $fakeDriver         = new \mock\Fake\Mysqli();
        $mockDriver         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver($fakeDriver);
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();

        $this->calling($mockConnectionPool)->connect =
            function ($connectionName, $database, callable $callback) use ($mockDriver) {
                $callback($mockDriver);
            };

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('MetadataFactory'),
                $services->get('Collection'),
                $services->get('Hydrator')
            ))
            ->then($bouhRepository->startTransaction())
            ->then($bouhRepository->rollback())
            ->boolean($mockDriver->isTransactionOpened())
                ->isFalse()
        ;
    }
}
