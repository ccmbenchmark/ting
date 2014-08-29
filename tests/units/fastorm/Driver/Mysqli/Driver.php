<?php

namespace tests\units\fastorm\Driver\Mysqli;

use fastorm\Query\Query;
use \mageekguy\atoum;

class Driver extends atoum
{

    public function testForConnectionKeyShouldCallCallbackWithConnectionName()
    {
        $this
            ->if(\fastorm\Driver\Mysqli\Driver::forConnectionKey(
                'BouhName',
                'BouhDatabase',
                function ($connectionKey) use (&$outerConnectionKey) {
                    $outerConnectionKey = $connectionKey;
                }
            ))
            ->string($outerConnectionKey)
                ->isIdenticalTo('BouhName');
    }

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

    public function testExecuteShouldCallDriverQuery()
    {
        $driverFake          = new \mock\Fake\Mysqli();
        $mockMysqliResult    = new \mock\tests\fixtures\FakeDriver\MysqliResult(array());

        $this->calling($driverFake)->query = $mockMysqliResult;

        $this
            ->if($driver = new \fastorm\Driver\Mysqli\Driver($driverFake))
            ->then($driver->execute('Empty query'))
            ->mock($driverFake)
                ->call('query')
                    ->once();
    }

    public function testExecuteShouldRaiseExceptionIfValueNotDefined()
    {
        $this->if($driver = new \fastorm\Driver\Mysqli\Driver())
            ->exception(function () use ($driver) {
                $driver->execute('SELECT * WHERE id = :id');
            })
                ->isInstanceOf('\fastorm\Driver\QueryException');
    }

    public function testExecuteShouldBuildACorrectQuery()
    {
        $driverFake          = new \mock\Fake\Mysqli();
        $mockMysqliResult    = new \mock\tests\fixtures\FakeDriver\MysqliResult(array());


        $this
            ->if($driver = new \fastorm\Driver\Mysqli\Driver($driverFake))
            ->and(
                $this->calling($driverFake)->real_escape_string = function ($value) {
                    return addcslashes($value, '"');
                }
            )
            ->and(
                $this->calling($driverFake)->query = function ($sql) use (&$outerSql, $mockMysqliResult) {
                    $outerSql = $sql;
                    return $mockMysqliResult;
                }
            )
            ->then(
                $driver->execute(
                    'SELECT population FROM T_CITY_CIT WHERE id = :id AND name = :name AND age = :age',
                    ['id' => 12, 'name' => 'L\'étang du lac', 'age' => 12.6]
                )
            )
            ->string($outerSql)
                ->isEqualTo(
                    'SELECT population FROM T_CITY_CIT WHERE id = 12 AND name = "L\'étang du lac" AND age = 12.6'
                )
            ->mock($driverFake)
                ->call('query')
                    ->once();
    }

    public function testSetCollectionWithIncorrectQueryShouldRaiseException()
    {
        $driverFake          = new \mock\Fake\Mysqli();

        $this
            ->if($driver = new \fastorm\Driver\Mysqli\Driver($driverFake))
            ->and($driverFake->errno = 12)
            ->and($driverFake->error = 'You tried an incorrect query')
            ->exception(
                function () use ($driver) {
                    $driver->setCollectionWithResult(false, Query::TYPE_RESULT);
                }
            )
                ->isInstanceOf('\fastorm\Driver\QueryException')
        ;
    }

    public function testSetCollectionShouldReturnInsertIdOnInsertion()
    {
        $driverFake          = new \mock\Fake\Mysqli();

        $this
            ->if($driver = new \fastorm\Driver\Mysqli\Driver($driverFake))
            ->and($driverFake->insert_id = 3)
            ->integer($driver->setCollectionWithResult(true, Query::TYPE_INSERT))
                ->isEqualTo(3)
        ;
    }

    public function testSetCollectionShouldReturnAffectedRowsNumberOnUpdate()
    {
        $driverFake          = new \mock\Fake\Mysqli();

        $this
            ->if($driver = new \fastorm\Driver\Mysqli\Driver($driverFake))
            ->and($driverFake->affected_rows = 12)
            ->integer($driver->setCollectionWithResult(true, Query::TYPE_AFFECTED))
                ->isEqualTo(12)
        ;
    }

    public function testSetCollectionShouldReturnFalseOnIncorrectResult()
    {
        $driverFake          = new \mock\Fake\Mysqli();

        $this
            ->if($driver = new \fastorm\Driver\Mysqli\Driver($driverFake))
            ->and($driverFake->affected_rows = null)
            ->boolean($driver->setCollectionWithResult(true, Query::TYPE_AFFECTED))
                ->isFalse()
        ;
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
