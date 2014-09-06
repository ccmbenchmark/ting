<?php

namespace tests\units\fastorm;

use \mageekguy\atoum;

class ConnectionPool extends atoum
{
    public function testConnectionShouldRaiseExceptionWhenConnectionNotFound()
    {
        $this
            ->if($connectionPool = new \fastorm\ConnectionPool())
            ->and($connectionPool->setConfig(['connections' => []]))
            ->exception(function () use ($connectionPool) {
                $connectionPool->connect(
                    'bouh',
                    'bouhDb',
                    function () {
                    }
                );
            })
                ->hasMessage('Connection not found: bouh');
    }

    public function testConnectionShouldCallSetDatabase()
    {
        $this
            ->if($connectionPool = new \fastorm\ConnectionPool())
            ->and($connectionPool->setConfig(
                [
                    'bouh' => [
                        'namespace' => '\tests\fixtures\FakeDriver',
                        'host'      => 'localhost.test',
                        'user'      => 'test',
                        'password'  => 'test',
                        'port'      => 3306
                    ]
                ]
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
            ->if($connectionPool = new \fastorm\ConnectionPool())
            ->and($connectionPool->setConfig(
                [
                    'bouh' => [
                        'namespace' => '\tests\fixtures\FakeDriver',
                        'host'      => 'localhost.test',
                        'user'      => 'test',
                        'password'  => 'test',
                        'port'      => 3306
                    ]
                ]
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
