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

namespace tests\units\CCMBenchmark\Ting\Query\Cached;

use CCMBenchmark\Ting\Repository\Collection;
use CCMBenchmark\Ting\Repository\CollectionInterface;
use mageekguy\atoum;

class PreparedQuery extends atoum
{

    public function testQueryShouldCallOnlyCacheGetIfDataInCache()
    {
        $services              = new \CCMBenchmark\Ting\Services();
        $mockConnectionPool    = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockConnection        = new \mock\CCMBenchmark\Ting\Connection(
            $mockConnectionPool,
            'connectionName',
            'database'
        );
        $mockCollectionFactory = new \mock\CCMBenchmark\Ting\Repository\CollectionFactory(
            $services->get('MetadataRepository'),
            $services->get('UnitOfWork'),
            $services->get('Hydrator')
        );

        $mockMemcached = new \mock\Doctrine\Common\Cache\MemcachedCache();
        $this->calling($mockMemcached)->fetch = function () {
            return [
                'connection' => 'connectionName',
                'database'   => 'database',
                'data' =>
                    [
                        [
                            [
                                'name'     => 'prenom',
                                'orgName'  => 'firstname',
                                'table'    => 'bouh',
                                'orgTable' => 'T_BOUH_BOO',
                                'type'     => MYSQLI_TYPE_VAR_STRING,
                                'value'    => 'Xavier',
                            ]
                        ]
                    ]
            ];
        };

        $collection = new Collection();

        $this
            ->if(
                $query = new \CCMBenchmark\Ting\Query\Cached\PreparedQuery('', $mockConnection, $mockCollectionFactory)
            )
            ->then($query->setCache($mockMemcached))
            ->then($query->setTtl(10)->setCacheKey('myCacheKey'))
            ->object($query->query($collection))
                ->isIdenticalTo($collection)
                ->mock($mockCollectionFactory)
                    ->call('get')
                        ->never()
            ->object($query->query())
                ->isInstanceOf('\CCMBenchmark\Ting\Repository\Collection')
                ->mock($mockCollectionFactory)
                    ->call('get')
                        ->once()
            ->mock($mockMemcached)
                ->call('fetch')
                    ->twice()
                ->call('save')
                    ->never()
        ;
    }

    public function testQueryShouldCallCacheGetThenStoreIfDataNotInCache()
    {
        $mockConnectionPool  = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockConnection      = new \mock\CCMBenchmark\Ting\Connection(
            $mockConnectionPool,
            'connectionName',
            'database'
        );
        $fakeDriver          = new \mock\Fake\Mysqli();
        $mockDriver          = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver($fakeDriver);
        $mockMysqliStatement = new \mock\Fake\mysqli_stmt();
        $mockStatement       = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Statement(
            $mockMysqliStatement,
            [],
            'connectionName',
            'database'
        );
        $mockMemcached = new \mock\Doctrine\Common\Cache\MemcachedCache();

        $this->calling($mockMemcached)->fetch   = false;
        $this->calling($mockMemcached)->save    = true;
        $this->calling($mockConnection)->slave  = $mockDriver;
        $this->calling($mockDriver)->execute    = true;
        $this->calling($mockDriver)->prepare    = $mockStatement;
        $this->calling($mockStatement)->execute = function (array $params, $collection) {
            $collection->set(new \mock\tests\fixtures\FakeDriver\MysqliResult());
            return true;
        };

        $collection = new Collection();

        $this
            ->if($query = new \CCMBenchmark\Ting\Query\Cached\PreparedQuery('', $mockConnection))
            ->then($query->setCache($mockMemcached))
            ->then($query->setTtl(10)->setCacheKey('myCacheKey'))
            ->then($query->prepareQuery())
            ->object($query->query($collection))
                ->isIdenticalTo($collection)
            ->mock($mockMemcached)
                ->call('fetch')
                    ->once()
                ->call('save')
                    ->once()
        ;
    }

    public function testPrepareExecuteShouldCallConnectionPrepare()
    {
        $mockConnectionPool  = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockConnection      = new \mock\CCMBenchmark\Ting\Connection(
            $mockConnectionPool,
            'connectionName',
            'database'
        );
        $fakeDriver          = new \mock\Fake\Mysqli();
        $mockDriver          = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver($fakeDriver);
        $mockMysqliStatement = new \mock\Fake\mysqli_stmt();
        $mockStatement       = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Statement(
            $mockMysqliStatement,
            [],
            'connectionName',
            'database'
        );
        $mockMemcached = new \mock\CCMBenchmark\Ting\Cache\Memcached();

        $this->calling($mockMemcached)->get     = null;
        $this->calling($mockMemcached)->store   = true;
        $this->calling($mockConnection)->master = $mockDriver;
        $this->calling($mockDriver)->execute    = true;
        $this->calling($mockDriver)->prepare    = $mockStatement;

        $this
            ->if($query = new \CCMBenchmark\Ting\Query\Cached\PreparedQuery('SELECT', $mockConnection))
            ->then($query->setTtl(0)->setCacheKey('myCacheKey'))
            ->object($query->prepareExecute())
                ->isIdenticalTo($query->prepareExecute())
            ->mock($mockDriver)
                ->call('prepare')
                    ->once()
        ;
    }

    public function testExecuteShouldCallStatementExecute()
    {
        $mockConnectionPool  = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockConnection      = new \mock\CCMBenchmark\Ting\Connection(
            $mockConnectionPool,
            'connectionName',
            'database'
        );
        $fakeDriver          = new \mock\Fake\Mysqli();
        $mockDriver          = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver($fakeDriver);
        $mockMysqliStatement = new \mock\Fake\mysqli_stmt();
        $mockStatement       = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Statement(
            $mockMysqliStatement,
            [],
            'connectionName',
            'database'
        );
        $mockMemcached = new \mock\CCMBenchmark\Ting\Cache\Memcached();

        $this->calling($mockMemcached)->get     = null;
        $this->calling($mockMemcached)->store   = true;
        $this->calling($mockConnection)->master = $mockDriver;
        $this->calling($mockDriver)->execute    = true;
        $this->calling($mockDriver)->prepare    = $mockStatement;
        $mockMysqliStatement->errno             = 0;

        $this
            ->if($query = new \CCMBenchmark\Ting\Query\Cached\PreparedQuery('SELECT', $mockConnection))
            ->then($query->setTtl(0)->setCacheKey('myCacheKey'))
            ->then($query->execute())
            ->mock($mockStatement)
                ->call('execute')
                    ->once()
        ;
    }
}
