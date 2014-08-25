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
            ->if($driver = new \fastorm\Driver\Mysqli\Driver($mockDriver))
            ->object($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
                ->isIdenticalTo($driver);
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
            ->if($driver = new \fastorm\Driver\Mysqli\Driver($mockDriver))
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
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
            ->if($driver = new \fastorm\Driver\Mysqli\Driver())
            ->exception(function () use ($driver) {
                $driver->connect('localhost', 'user.test', 'password.test', 1234);
            })
                ->isInstanceOf('\fastorm\Driver\Exception');
    }

    public function testConnectWithUnresolvableHostShouldRaiseDriverException()
    {
        $this
            ->if($driver = new \fastorm\Driver\Mysqli\Driver())
            ->exception(function () use ($driver) {
                $driver->connect('hostname.test', 'user.test', 'password.test', 1234);
            })
                ->isInstanceOf('\fastorm\Driver\Exception')
                ->error()
                    ->withType(E_WARNING)
                    ->exists();
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
            ->if($driver = new \fastorm\Driver\Mysqli\Driver($mockDriver))
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->then($driver->setDatabase('bouh'))
            ->variable($mockDriver->database)
                ->isIdenticalTo('bouh');
    }

    public function testsetDatabaseWithDatabaseAlreadySetShouldDoNothing()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $mockDriver->error = '';
        $this->calling($mockDriver)->real_connect = $mockDriver;
        $this->calling($mockDriver)->select_db = function ($database) {
                $this->database = $database;
        };

        $this
            ->if($driver = new \fastorm\Driver\Mysqli\Driver($mockDriver))
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->then($driver->setDatabase('bouh'))
            ->then($driver->setDatabase('bouh'))
            ->mock($mockDriver)
                ->call('select_db')
                    ->once();
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
            ->if($driver = new \fastorm\Driver\Mysqli\Driver($mockDriver))
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->object($driver->setDatabase('bouh'))
                ->isIdenticalTo($driver);
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
            ->if($driver = new \fastorm\Driver\Mysqli\Driver($mockDriver))
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->exception(function () use ($driver) {
                $driver->setDatabase('bouh');
            })
                ->isInstanceOf('\fastorm\Driver\Exception');
    }

    public function testIfNotConnectedShouldCallCallback()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $this->calling($mockDriver)->real_connect = false;

        $this
            ->if($driver = new \fastorm\Driver\Mysqli\Driver())
            ->exception(function () use ($driver) {
                $driver->connect('hostname.test', 'user.test', 'password.test', 1234);
            })
                ->error()
                    ->withType(E_WARNING)
                    ->exists()
            ->then($driver->ifIsNotConnected(function () use (&$callable) {
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
            ->if($driver = new \fastorm\Driver\Mysqli\Driver($mockDriver))
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->then($driver->ifIsError(function () use (&$callable) {
                $callable = true;
            }))
            ->boolean($callable)
                ->isTrue();
    }

    public function testPrepareShouldCallCallback()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $this->calling($mockDriver)->real_connect = $mockDriver;

        $this
            ->if($driver = new \fastorm\Driver\Mysqli\Driver($mockDriver))
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->then($driver->prepare(
                'SELECT 1 FROM bouh WHERE first = :first AND second = :second',
                function (
                    $statement,
                    $paramsOrder,
                    $driverStatement
                ) use (
                    &$outerStatement,
                    &$outerParamsOrder,
                    &$outerDriverStatement
                ) {
                    $outerParamsOrder = $paramsOrder;
                }
            ))
            ->array($outerParamsOrder)
                ->isIdenticalTo(array('first' => null, 'second' => null));
    }

    public function testPrepareShouldRaiseQueryException()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $mockDriver->errno = 123;
        $mockDriver->error = 'unknown error';
        $this->calling($mockDriver)->real_connect = $mockDriver;
        $this->calling($mockDriver)->prepare = false;

        $this
            ->if($driver = new \fastorm\Driver\Mysqli\Driver($mockDriver))
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->exception(function () use ($driver) {
                $driver->prepare(
                    'SELECT 1 FROM bouh WHERE first = :first AND second = :second',
                    function (
                        $statement,
                        $paramsOrder,
                        $driverStatement,
                        $collection
                    ) use (
                        &$outerStatement,
                        &$outerParamsOrder,
                        &$outerDriverStatement
                    ) {
                        $outerParamsOrder = $paramsOrder;
                    }
                );
            })
                ->isInstanceOf('\fastorm\Driver\QueryException');
    }

    public function testPrepareShouldCallStatementSetQueryTypeAffected()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $this->calling($mockDriver)->real_connect = $mockDriver;

        $mockStatement = new \mock\fastorm\Driver\Mysqli\Statement();
        $this->calling($mockStatement)->setQueryType = function ($queryType) use (&$outerQueryType) {
            $outerQueryType = $queryType;
        };

        $this
            ->if($driver = new \fastorm\Driver\Mysqli\Driver($mockDriver))
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->then($driver->prepare(
                'UPDATE T_BOUH_BOO SET ...',
                function () {
                },
                $mockStatement
            ))
            ->integer($outerQueryType)
                ->isIdenticalTo(\fastorm\Driver\Mysqli\Statement::TYPE_AFFECTED);
    }

    public function testPrepareShouldCallStatementSetQueryTypeInsert()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $this->calling($mockDriver)->real_connect = $mockDriver;

        $mockStatement = new \mock\fastorm\Driver\Mysqli\Statement();
        $this->calling($mockStatement)->setQueryType = function ($queryType) use (&$outerQueryType) {
            $outerQueryType = $queryType;
        };

        $this
            ->if($driver = new \fastorm\Driver\Mysqli\Driver($mockDriver))
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->then($driver->prepare(
                'INSERT INTO T_BOUH_BOO ...',
                function () {
                },
                $mockStatement
            ))
            ->integer($outerQueryType)
                ->isIdenticalTo(\fastorm\Driver\Mysqli\Statement::TYPE_INSERT);
    }

    public function testPrepareShouldNotTransformEscapedColon()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $this->calling($mockDriver)->real_connect = $mockDriver;
        $this->calling($mockDriver)->prepare = function ($sql) use (&$outerSql) {
            $outerSql = $sql;
        };

        $this
            ->if($driver = new \fastorm\Driver\Mysqli\Driver($mockDriver))
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->then($driver->prepare(
                'SELECT * FROM T_BOUH_BOO WHERE name = "\:bim"',
                function () {
                }
            ))
            ->string($outerSql)
                ->isIdenticalTo('SELECT * FROM T_BOUH_BOO WHERE name = ":bim"');
    }

    public function testEscapeFieldsShouldCallCallbackAndReturnThis()
    {
        $mockDriver = new \mock\Fake\Mysqli();

        $this
            ->if($driver = new \fastorm\Driver\Mysqli\Driver($mockDriver))
            ->object($driver->escapeFields(array('Bouh'), function ($escaped) use (&$outerEscaped) {
                $outerEscaped = $escaped;
            }))
                ->isIdenticalTo($driver)
            ->string($outerEscaped[0])
                ->isIdenticalTo('`Bouh`');
    }

    public function testStartTransactionShouldOpenTransaction()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $this
            ->if($driver = new \fastorm\Driver\Mysqli\Driver($mockDriver))
            ->boolean($driver->isTransactionOpened())
                ->isFalse()
            ->then($driver->startTransaction())
            ->boolean($driver->isTransactionOpened())
                ->isTrue();
    }

    public function testStartTransactionShouldRaiseExceptionIfCalledTwice()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $this
            ->if($driver = new \fastorm\Driver\Mysqli\Driver($mockDriver))
            ->then($driver->startTransaction())
            ->exception(function () use ($driver) {
                    $driver->startTransaction();
            })
                ->isInstanceOf('\fastorm\Driver\Exception')
        ;
    }

    public function testCommitShouldCloseConnection()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $this
            ->if($driver = new \fastorm\Driver\Mysqli\Driver($mockDriver))
            ->then($driver->startTransaction())
            ->then($driver->commit())
            ->boolean($driver->isTransactionOpened())
                ->isFalse()
            ;
    }

    public function testCommitShouldRaiseExceptionIfNoTransaction()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $this
            ->if($driver = new \fastorm\Driver\Mysqli\Driver($mockDriver))
            ->exception(function () use ($driver) {
                    $driver->commit();
            })
                ->isInstanceOf('\fastorm\Driver\Exception')
        ;
    }

    public function testRollbackShouldCloseTransaction()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $this
            ->if($driver = new \fastorm\Driver\Mysqli\Driver($mockDriver))
            ->then($driver->startTransaction())
            ->then($driver->rollback())
            ->boolean($driver->isTransactionOpened())
                ->isFalse()
            ;
    }

    public function testRollbackShouldRaiseExceptionIfNoTransaction()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $this
            ->if($driver = new \fastorm\Driver\Mysqli\Driver($mockDriver))
            ->exception(function () use ($driver) {
                    $driver->rollback();
            })
                ->isInstanceOf('\fastorm\Driver\Exception')
        ;
    }
}
