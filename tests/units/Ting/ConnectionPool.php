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
            ->and($connectionPool->setConfig(['connectionName' => []]))
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
                ]
            ))
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
                ]
            ))
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
                ]
            ))
            ->object($connectionPool->master('bouh', 'bouhDb'))
                ->isInstanceOf('\tests\fixtures\FakeDriver\Driver')
            ->object($connectionPool->slave('bouh', 'bouhDb'))
                ->isInstanceOf('\tests\fixtures\FakeDriver\Driver')
            ;
    }


    public function testCloseAllConnections()
    {
        $mockLogger = new \mock\tests\fixtures\FakeLogger\FakeDriverLogger();

        $this
            ->if($connectionPool = new \CCMBenchmark\Ting\ConnectionPool($mockLogger))
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
                ]
            ))
            ->then($connectionPool->master('bouh', 'bouhDb'))
            ->then($connectionPool->slave('bouh', 'bouhDb'))
            ->then($connectionPool->closeAll())
            ->then($connectionPool->master('bouh', 'bouhDb'))
            ->then($connectionPool->slave('bouh', 'bouhDb'))
                ->mock($mockLogger)
                    ->call('addConnection')
                        ->exactly(4)
        ;
    }

    public function testConnectionPoolShouldLogConnections()
    {
        $mockLogger = new \mock\tests\fixtures\FakeLogger\FakeDriverLogger();

        $this
            ->if($connectionPool = new \CCMBenchmark\Ting\ConnectionPool($mockLogger))
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
                ]
            ))
            ->then($connectionPool->master('bouh', 'bouhDb'))
                ->mock($mockLogger)
                    ->call('addConnection')
                        ->once()
            ->then($connectionPool->slave('bouh', 'bouhDb'))
                ->mock($mockLogger)
                    ->call('addConnection')
                        ->twice()
            ;
    }

    public function testGetDriveClassShouldreturnFakeDriver()
    {
        $this
            ->if($connectionPool = new \CCMBenchmark\Ting\ConnectionPool())
            ->and($connectionPool->setConfig(
                [
                    'connectionName' => [
                        'namespace' => '\tests\fixtures\FakeDriver',
                        'master'    => [
                            'host'      => 'master',
                            'user'      => 'test',
                            'password'  => 'test',
                            'port'      => 3306
                        ]
                    ]
                ]
            ))
            ->string($connectionPool->getDriverClass('connectionName'))
                ->isIdenticalTo('\tests\fixtures\FakeDriver\Driver')
        ;
    }

    public function testConnectShouldReturnADriverWithTheRightConnectionNameWhenManyConnectionsHaveSameParameter()
    {
        $this
            ->if($connectionPool = new \CCMBenchmark\Ting\ConnectionPool())
            ->and($connectionPool->setConfig(
                [
                    'connection1' => [
                        'namespace' => '\tests\fixtures\FakeDriver',
                        'master'    => [
                            'host'      => '127.0.0.1',
                            'user'      => 'test',
                            'password'  => 'test',
                            'port'      => 3306
                        ]
                    ],
                    'connection2' => [
                        'namespace' => '\tests\fixtures\FakeDriver',
                        'master'    => [
                            'host'      => '127.0.0.1',
                            'user'      => 'test',
                            'password'  => 'test',
                            'port'      => 3306
                        ]
                    ]
                ]))
            ->then($driver = $connectionPool->master('connection1', 'databaseOnConnection1'))
            ->string($driver->getName())
                ->isIdenticalTo('connection1')
            ->and($driver2 = $connectionPool->master('connection2', 'databaseOnConnection1'))
                ->string($driver2->getName())
                ->isIdenticalTo('connection2');
    }

    public function testConnectionShouldRetrunDriverWhenTimezoneSetted()
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
                    ]
                ]))
            ->and($connectionPool->setDatabaseOptions([
                "bouhDb" => [
                    'timezone' => 'UTF-8'
                ]
            ]))
            ->object($connectionPool->master('bouh', 'bouhDb'))
            ->isInstanceOf('\tests\fixtures\FakeDriver\Driver');
    }
}
