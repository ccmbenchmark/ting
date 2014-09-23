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
    public function testConnectionShouldRaiseExceptionWhenConnectionNotFound()
    {
        $this
            ->if($connectionPool = new \CCMBenchmark\Ting\ConnectionPool())
            ->and($connectionPool->setConfig(['connections' => []]))
            ->exception(function () use ($connectionPool) {
                $connectionPool->connect(
                    'bouh',
                    'bouhDb',
                    $connectionPool::CONNECTION_MASTER,
                    function () {
                    }
                );
            })
                ->hasMessage('Connection not found: bouh');
    }

    public function testConnectionShouldCallSetDatabase()
    {
        $this
            ->if($connectionPool = new \CCMBenchmark\Ting\ConnectionPool())
            ->and($connectionPool->setConfig(
                [
                    'bouh' => [
                        'namespace' => '\tests\fixtures\FakeDriver',
                        'master'    => [
                            'host'      => 'localhost.test',
                            'user'      => 'test',
                            'password'  => 'test',
                            'port'      => 3306
                        ]
                    ]
                ]
            ))
            ->then($connectionPool->connect(
                'bouh',
                'bouhDb',
                $connectionPool::CONNECTION_MASTER,
                function ($connection) use (&$outerConnection) {
                    $outerConnection = $connection;
                }
            ))
            ->string($outerConnection->database)
                ->isIdenticalTo('bouhDb');
    }

    public function testConnectionShouldCallCallbackWithConnection()
    {
        $this
            ->if($connectionPool = new \CCMBenchmark\Ting\ConnectionPool())
            ->and($connectionPool->setConfig(
                [
                    'bouh' => [
                        'namespace' => '\tests\fixtures\FakeDriver',
                        'master'    => [
                            'host'      => 'localhost.test',
                            'user'      => 'test',
                            'password'  => 'test',
                            'port'      => 3306
                        ]
                    ]
                ]
            ))
            ->then($connectionPool->connect(
                'bouh',
                'bouhDb',
                $connectionPool::CONNECTION_MASTER,
                function ($connection) use (&$outerConnection) {
                    $outerConnection = $connection;
                }
            ))
            ->object($outerConnection)
                ->isInstanceOf('\tests\fixtures\FakeDriver\Driver');
    }

    public function testConnectionOnMasterIfNoSlave()
    {
        $this
            ->if($connectionPool = new \CCMBenchmark\Ting\ConnectionPool())
            ->then($connectionPool->setConfig(
                [
                    'bouh' => [
                        'namespace' => '\tests\fixtures\FakeDriver',
                        'master'    => [
                            'host'      => 'localhost.test',
                            'user'      => 'test',
                            'password'  => 'test',
                            'port'      => 3306
                        ]
                    ]
                ]
            ))
            ->array(
                $connectionConfig = $connectionPool->retrieveApplicableConnectionConf(
                    'bouh',
                    $connectionPool::CONNECTION_MASTER
                )
            )
                ->isIdenticalTo(
                    [
                        'host'      => 'localhost.test',
                        'user'      => 'test',
                        'password'  => 'test',
                        'port'      => 3306
                    ]
                );
    }

    public function testConnectionOnSameSlaveIfManySlaves()
    {
        $this
            ->if($connectionPool = new \CCMBenchmark\Ting\ConnectionPool())
            ->then($connectionPool->setConfig(
                [
                    'bouh' => [
                        'namespace' => '\tests\fixtures\FakeDriver',
                        'master'    => [
                            'host'      => 'localhost.test',
                            'user'      => 'test',
                            'password'  => 'test',
                            'port'      => 3306
                        ],
                        'slaves'    => [
                            [
                                'host'      => 'slave1',
                                'user'      => 'test_slave1',
                                'password'  => 'test_slave1',
                                'port'      => 3306
                            ],
                            [
                                'host'      => 'slave2',
                                'user'      => 'test_slave2',
                                'password'  => 'test_slave2',
                                'port'      => 3306
                            ]
                        ]
                    ]
                ]
            ))
            ->then(
                $connectionConfig = $connectionPool->retrieveApplicableConnectionConf(
                    'bouh',
                    $connectionPool::CONNECTION_SLAVE
                )
            )
            ->array(
                $connectionConfig2 = $connectionPool->retrieveApplicableConnectionConf(
                    'bouh',
                    $connectionPool::CONNECTION_SLAVE
                )
            )
                ->isIdenticalTo(
                    $connectionConfig
                );
    }
}
