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

use mageekguy\atoum;

class ConnectionPool extends atoum
{
    public function testConnectShouldRaiseExceptionWhenConnectionNotFound()
    {
        $this
            ->if($connectionPool = new \CCMBenchmark\Ting\ConnectionPool())
            ->and($connectionPool->setConfig(['connections' => []]))
            ->exception(function () use ($connectionPool) {
                $connectionPool->master(
                    'bouh',
                    'bouhDb'
                );
            })
                ->hasMessage('Connection not found: bouh')
            ->exception(function () use ($connectionPool) {
                $connectionPool->slave(
                    'bouh',
                    'bouhDb'
                );
            })
                ->hasMessage('Connection not found: bouh');
    }

    public function testConnectSlaveShouldAlwaysReturnTheSameInstance()
    {
        $this
            ->if($connectionPool = new \CCMBenchmark\Ting\ConnectionPool())
            ->and($connectionPool->setConfig(
                [
                    'bouh' => [
                        'namespace' => '\tests\fixtures\FakeDriver',
                        'master'    => [
                            'host'      => 'master',
                            'user'      => 'test',
                            'password'  => 'test',
                            'port'      => 3306
                        ],
                        'slaves'    => [
                            [
                                'host'      => 'slave1',
                                'user'      => 'test',
                                'password'  => 'test',
                                'port'      => 3306
                            ],
                            [
                                'host'      => 'slave2',
                                'user'      => 'test',
                                'password'  => 'test',
                                'port'      => 3306
                            ],
                        ]
                    ]
                ]))
            ->object($connectionPool->slave('bouh', 'bouhDb'))
                ->isIdenticalTo($connectionPool->slave('bouh', 'bouhDb'))
                ->isIdenticalTo($connectionPool->slave('bouh', 'bouhDb'))
                ->isIdenticalTo($connectionPool->slave('bouh', 'bouhDb'))
                ->isIdenticalTo($connectionPool->slave('bouh', 'bouhDb'))
            ;
    }

    public function testConnectSlaveShouldReturnMasterIfNoMasterDefined()
    {
        $this
            ->if($connectionPool = new \CCMBenchmark\Ting\ConnectionPool())
            ->and($connectionPool->setConfig(
                [
                    'bouh' => [
                        'namespace' => '\tests\fixtures\FakeDriver',
                        'master'    => [
                            'host'      => 'master',
                            'user'      => 'test',
                            'password'  => 'test',
                            'port'      => 3306
                        ],
                        'slaves'    => []
                    ]
                ]))
            ->object($connectionPool->slave('bouh', 'bouhDb'))
                ->isIdenticalTo($connectionPool->master('bouh', 'bouhDb'))
            ;
    }

    public function testConnectShouldReturnADriver()
    {
        $this
            ->if($connectionPool = new \CCMBenchmark\Ting\ConnectionPool())
            ->and($connectionPool->setConfig(
                [
                    'bouh' => [
                        'namespace' => '\tests\fixtures\FakeDriver',
                        'master'    => [
                            'host'      => 'master',
                            'user'      => 'test',
                            'password'  => 'test',
                            'port'      => 3306
                        ],
                        'slaves'    => [
                            [
                                'host'      => 'slave1',
                                'user'      => 'test',
                                'password'  => 'test',
                                'port'      => 3306
                            ]
                        ]
                    ]
                ]))
            ->object($connectionPool->master('bouh', 'bouhDb'))
                ->isInstanceOf('\tests\fixtures\FakeDriver\Driver')
            ->object($connectionPool->slave('bouh', 'bouhDb'))
                ->isInstanceOf('\tests\fixtures\FakeDriver\Driver')
            ;
    }
}
