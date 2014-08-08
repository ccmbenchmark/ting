<?php

namespace tests\units\fastorm;

use \mageekguy\atoum;

class ConnectionPool extends atoum
{

    public function testShouldRaiseExceptionWhenNoConfigurationArgument()
    {
        $this
            ->exception(function () {
                \fastorm\ConnectionPool::getInstance();
            })
                ->hasDefaultCode()
                ->hasMessage('First call to ConnectionPool must pass configuration in parameters');
    }

    public function testShouldRaiseExceptionWhenNoConnectionsInConfiguartion()
    {
        $this
            ->exception(function () {
                \fastorm\ConnectionPool::getInstance(array('noConnections'));
            })
                ->hasMessage('Configuration must have "connections" key');
    }

    public function testShouldBeSingleton()
    {
        $this
            ->object(\fastorm\ConnectionPool::getInstance(array('connections' => array())))
            ->isIdenticalTo(\fastorm\ConnectionPool::getInstance());
    }

    public function testConnectionShouldRaiseExceptionWhenConnectionNotFound()
    {
        $this
            ->if($connectionPool = \fastorm\ConnectionPool::getInstance(array('connections' => array())))
            ->exception(function () use ($connectionPool) {
                $connectionPool->connect('bouh', 'bouhDb', function () {});
            })
                ->hasMessage('Connection not found: bouh');
    }

    public function testConnectionShouldCallSetDatabase()
    {
        $this
            ->if($connectionPool = \fastorm\ConnectionPool::getInstance(
                array(
                    'connections' => array(
                        'bouh' => array(
                            'namespace' => '\tests\fixtures\FakeDriver',
                            'host'      => 'localhost.test',
                            'user'      => 'test',
                            'password'  => 'test',
                            'port'      => 3306
                        )
                    )
                )
            ))
            ->then($connectionPool->connect(
                'bouh',
                'bouhDb',
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
            ->if($connectionPool = \fastorm\ConnectionPool::getInstance(
                array(
                    'connections' => array(
                        'bouh' => array(
                            'namespace' => '\tests\fixtures\FakeDriver',
                            'host'      => 'localhost.test',
                            'user'      => 'test',
                            'password'  => 'test',
                            'port'      => 3306
                        )
                    )
                )
            ))
            ->then($connectionPool->connect(
                'bouh',
                'bouhDb',
                function ($connection) use (&$outerConnection) {
                    $outerConnection = $connection;
                }
            ))
            ->object($outerConnection)
                ->isInstanceOf('\tests\fixtures\FakeDriver\Driver');
    }
}
