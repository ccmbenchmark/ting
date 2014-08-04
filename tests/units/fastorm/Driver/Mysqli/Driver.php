<?php

namespace tests\units\fastorm\Driver\Mysqli;

use \mageekguy\atoum;

class Driver extends atoum
{

    public function testShouldImplementDriverInterface()
    {
        $this
            ->object(new \fastorm\Driver\Mysqli\Driver())
            ->isInstanceOf('\fastorm\Driver\DriverInterface');
    }

    public function testConnectShouldReturnSelf()
    {

        $mockDriver = new \mock\Fake\Mysqli();
        $this->calling($mockDriver)->real_connect =
            function ($hostname, $username, $password, $database, $port) {
                $this->hostname = $hostname;
                $this->username = $username;
                $this->password = $password;
                $this->database = $database;
                $this->port     = $port;
            };

        $this
            ->if($object = new \fastorm\Driver\Mysqli\Driver($mockDriver))
            ->object($object->connect('hostname.test', 'user.test', 'password.test', 1234))
                ->isIdenticalTo($object);
    }

    public function testConnectParameters()
    {

        $mockDriver = new \mock\Fake\Mysqli();
        $this->calling($mockDriver)->real_connect =
            function ($hostname, $username, $password, $database, $port) {
                $this->hostname = $hostname;
                $this->username = $username;
                $this->password = $password;
                $this->database = $database;
                $this->port     = $port;
            };

        $this
            ->if($object = new \fastorm\Driver\Mysqli\Driver($mockDriver))
            ->then($object->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->variable($mockDriver->hostname)
                ->isIdenticalTo('hostname.test')
            ->variable($mockDriver->username)
                ->isIdenticalTo('user.test')
            ->variable($mockDriver->password)
                ->isIdenticalTo('password.test')
            ->variable($mockDriver->database)
                ->isIdenticalTo(null)
            ->variable($mockDriver->port)
                ->isIdenticalTo(1234);
    }

    public function testConnectWithWrongAuthOrPortShouldRaiseDriverException()
    {
        $this
            ->if($object = new \fastorm\Driver\Mysqli\Driver())
            ->exception(function () use($object) {
                $object->connect('localhost', 'user.test', 'password.test', 1234);
            })
                ->isInstanceOf('\fastorm\Driver\Exception');
    }

    public function testConnectWithUnresolvableHostShouldRaiseDriverException()
    {
        $this
            ->if($object = new \fastorm\Driver\Mysqli\Driver())
            ->exception(function () use($object) {
                $object->connect('hostname.test', 'user.test', 'password.test', 1234);
            })
                ->isInstanceOf('\fastorm\Driver\Exception');
    }

    public function testsetDatabase()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $mockDriver->error = '';
        $this->calling($mockDriver)->real_connect = $mockDriver;
        $this->calling($mockDriver)->select_db = function ($database) {
                $this->database = $database;
        };

        $this
            ->if($object = new \fastorm\Driver\Mysqli\Driver($mockDriver))
            ->then($object->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->then($object->setDatabase('bouh'))
            ->variable($mockDriver->database)
                ->isIdenticalTo('bouh');
    }

    public function testsetDatabaseShouldReturnSelf()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $mockDriver->error = '';
        $this->calling($mockDriver)->real_connect = $mockDriver;
        $this->calling($mockDriver)->select_db = function ($database) {
                $this->database = $database;
        };

        $this
            ->if($object = new \fastorm\Driver\Mysqli\Driver($mockDriver))
            ->then($object->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->object($object->setDatabase('bouh'))
                ->isIdenticalTo($object);
    }

    public function testsetDatabaseShouldRaiseDriverException()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $mockDriver->errno = 123;
        $mockDriver->error = 'unknown database';
        $this->calling($mockDriver)->real_connect = $mockDriver;
        $this->calling($mockDriver)->select_db = function ($database) {
            $this->database = $database;
        };

        $this
            ->if($object = new \fastorm\Driver\Mysqli\Driver($mockDriver))
            ->then($object->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->exception(function () use ($object) {
                $object->setDatabase('bouh');
            })
                ->isInstanceOf('\fastorm\Driver\Exception');
    }

    public function testPrepareShouldCallConnectionPrepare()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $this->calling($mockDriver)->real_connect = $mockDriver;
        $this->calling($mockDriver)->prepare = function ($sql) {
            $this->sql = $sql;
        };

        $this
            ->if($object = new \fastorm\Driver\Mysqli\Driver($mockDriver))
            ->then($object->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->then($object->prepare('SELECT * FROM bouh', function () {}))
            ->variable($mockDriver->sql)
                ->isIdenticalTo('SELECT * FROM bouh');
    }

    public function testPrepareShouldReplaceNamedParameters()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $this->calling($mockDriver)->real_connect = $mockDriver;
        $this->calling($mockDriver)->prepare = function ($sql) {
            $this->sql = $sql;
        };

        $this
            ->if($object = new \fastorm\Driver\Mysqli\Driver($mockDriver))
            ->then($object->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->then($object->prepare('SELECT * FROM bouh WHERE id = :id AND name = :name', function () {}))
            ->variable($mockDriver->sql)
                ->isIdenticalTo('SELECT * FROM bouh WHERE id = ? AND name = ?');
    }

    public function testPrepareShouldConserveParametersOrder()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $this->calling($mockDriver)->real_connect = $mockDriver;
        $this->calling($mockDriver)->prepare = function ($sql) {
            $this->sql = $sql;
        };

        $mockStatement = new \mock\fastorm\Driver\StatementInterface();
        $this->calling($mockStatement)->setParamsOrder = function ($params) {
            $this->params = $params;
        };

        $this
            ->if($object = new \fastorm\Driver\Mysqli\Driver($mockDriver))
            ->then($object->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->then($object->prepare(
                'SELECT * FROM bouh WHERE id = :id AND name = :name',
                function () {},
                $mockStatement
            ))
            ->variable($mockStatement->params)
                ->isIdenticalTo(array('id' => null, 'name' => null));
    }

    public function testPrepareShouldThrowQueryExceptionWhenInvalidQuery()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $mockDriver->errno = 123;
        $mockDriver->error = 'wrong query';
        $this->calling($mockDriver)->real_connect = $mockDriver;
        $this->calling($mockDriver)->prepare = false;

        $mockStatement = new \mock\Fake\Statement();
        $this->calling($mockStatement)->setParamsOrder = function ($params) {
            $this->params = $params;
        };

        $this
            ->if($object = new \fastorm\Driver\Mysqli\Driver($mockDriver))
            ->then($object->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->exception(function () use($object) {
                $object->prepare('simulated wrong query', function () {});
            })
                ->IsInstanceOf('\fastorm\Driver\QueryException');
    }

    public function testPrepareShouldReturnSelf()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $this->calling($mockDriver)->real_connect = $mockDriver;

        $this
            ->if($object = new \fastorm\Driver\Mysqli\Driver($mockDriver))
            ->then($object->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->object($object->prepare('SELECT * FROM bouh', function () {}))
                ->IsInstanceOf($object);
    }

    public function testIfNotConnectedShouldCallCallback()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $this->calling($mockDriver)->real_connect = false;

        $this
            ->if($object = new \fastorm\Driver\Mysqli\Driver())
            ->exception(function () use($object) {
                $object->connect('hostname.test', 'user.test', 'password.test', 1234);
            })
            ->then($object->ifIsNotConnected(function () use(&$callable) {
                $callable = true;
            }))
            ->boolean($callable)
                ->isTrue();
    }

    public function testIfIsErrorShouldCallCallable()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $mockDriver->errno = 123;
        $mockDriver->error = 'unknown error';
        $this->calling($mockDriver)->real_connect = $mockDriver;

        $this
            ->if($object = new \fastorm\Driver\Mysqli\Driver($mockDriver))
            ->then($object->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->then($object->ifIsError(function () use(&$callable) {
                $callable = true;
            }))
            ->boolean($callable)
                ->isTrue();
    }
}
