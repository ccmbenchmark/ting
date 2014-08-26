<?php

namespace tests\units\fastorm\Driver\Pgsql;

use \mageekguy\atoum;

class Driver extends atoum
{

    public function testForConnectionKeyShouldCallCallbackWithConnectionNameAndDatabase()
    {
        $this
            ->if(\fastorm\Driver\Pgsql\Driver::forConnectionKey(
                'BouhName',
                'BouhDatabase',
                function ($connectionKey) use (&$outerConnectionKey) {
                    $outerConnectionKey = $connectionKey;
                }
            ))
            ->string($outerConnectionKey)
                ->isIdenticalTo('BouhName|BouhDatabase');
    }

    public function testShouldImplementDriverInterface()
    {
        $this
            ->object(new \fastorm\Driver\Pgsql\Driver())
            ->isInstanceOf('\fastorm\Driver\DriverInterface');
    }

    public function testConnectShouldReturnSelf()
    {
        $this
            ->if($driver = new \fastorm\Driver\Pgsql\Driver())
            ->object($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
                ->isIdenticalTo($driver);
    }

    public function testSetDatabaseShouldCompleteGeneratedDsnByConnect()
    {

        $this->function->pg_connect = function ($dsn) use (&$outerDsn) {
            $outerDsn = $dsn;
        };

        $this
            ->if($driver = new \fastorm\Driver\Pgsql\Driver())
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->then($driver->setDatabase('database.test'))
            ->string($outerDsn)
                ->isIdenticalTo(
                    'host=hostname.test user=user.test password=password.test port=1234 dbname=database.test'
                );
    }

    public function testSetDatabaseWhenWrongAuthOrPortShouldRaiseDriverException()
    {
        $this
            ->if($driver = new \fastorm\Driver\Pgsql\Driver())
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->exception(function () use ($driver) {
                $driver->setDatabase('bouh');
            })
                ->isInstanceOf('\fastorm\Driver\Exception')
                ->error()
                    ->withType(E_WARNING)
                    ->exists();
    }

    public function testsetDatabaseWithDatabaseAlreadySetShouldDoNothing()
    {
        $outerCount = 0;
        $this->function->pg_connect = function ($dsn) use (&$outerCount) {
            $outerCount++;
            return true;
        };

        $this
            ->if($driver = new \fastorm\Driver\Pgsql\Driver())
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->then($driver->setDatabase('bouh'))
            ->then($driver->setDatabase('bouh'))
            ->integer($outerCount)
                ->isIdenticalTo(1);
    }

    public function testsetDatabaseShouldReturnSelf()
    {
        $this->function->pg_connect = true;
        $this
            ->if($driver = new \fastorm\Driver\Pgsql\Driver())
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->object($driver->setDatabase('bouh'))
                ->isIdenticalTo($driver);
    }

    public function testIfNotConnectedShouldCallCallback()
    {
        $this->function->pg_connect = false;
        $called = false;

        $this
            ->if($driver = new \fastorm\Driver\Pgsql\Driver())
            ->then($driver->ifIsNotConnected(function () use (&$called) {
                $called = true;
            }))
            ->boolean($called)
                ->isTrue();
    }

    public function testIfIsErrorShouldCallCallable()
    {
        $this->function->pg_connect = true;
        $this->function->pg_last_error = 'unknown error';
        $called = false;

        $this
            ->if($driver = new \fastorm\Driver\Pgsql\Driver())
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->then($driver->setDatabase('database.test'))
            ->then($driver->ifIsError(function () use (&$called) {
                $called = true;
            }))
            ->boolean($called)
                ->isTrue();
    }

    public function testPrepareShouldCallCallback()
    {
        $this->function->pg_connect = true;
        $this->function->pg_prepare = true;

        $this
            ->if($driver = new \fastorm\Driver\Pgsql\Driver())
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->then($driver->setDatabase('database.test'))
            ->then($driver->prepare(
                'SELECT 1 FROM bouh WHERE first = :first AND second = :second',
                function (
                    $statement,
                    $paramsOrder,
                    $collection
                ) use (
                    &$outerParamsOrder
                ) {
                    $outerParamsOrder = $paramsOrder;
                }
            ))
            ->array($outerParamsOrder)
                ->isIdenticalTo(array('first' => null, 'second' => null));
    }

    public function testPrepareShouldRaiseQueryException()
    {
        $this->function->pg_connect = true;
        $this->function->pg_prepare = false;
        $this->function->pg_last_error = 'unknown error';

        $this
            ->if($driver = new \fastorm\Driver\Pgsql\Driver())
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->then($driver->setDatabase('database.test'))
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

    /*public function testPrepareShouldCallStatementSetQueryTypeAffected()
    {
        $this->function->pg_connect = true;
        $this->function->pg_prepare = true;

        $mockStatement = new \mock\fastorm\Driver\Pgsql\Statement();
        $this->calling($mockStatement)->setQueryType = function ($queryType) use (&$outerQueryType) {
            $outerQueryType = $queryType;
        };

        $this
            ->if($driver = new \fastorm\Driver\Pgsql\Driver())
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->then($driver->prepare(
                'UPDATE T_BOUH_BOO SET ...',
                function () {
                },
                $outerQueryType,
                $mockStatement
            ))
            ->integer($outerQueryType)
                ->isIdenticalTo(\fastorm\Query\Query::TYPE_AFFECTED);
    }

    public function testPrepareShouldCallStatementSetQueryTypeInsert()
    {
        $this->function->pg_connect = true;
        $this->function->pg_prepare = true;

        $mockStatement = new \mock\fastorm\Driver\Pgsql\Statement();
        $this->calling($mockStatement)->setQueryType = function ($queryType) use (&$outerQueryType) {
            $outerQueryType = $queryType;
        };

        $this
            ->if($driver = new \fastorm\Driver\Pgsql\Driver())
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->then($driver->prepare(
                'INSERT INTO T_BOUH_BOO ...',
                function () {
                },
                $outerQueryType,
                $mockStatement
            ))
            ->integer($outerQueryType)
                ->isIdenticalTo(\fastorm\Query\Query::TYPE_INSERT);
    }*/

    public function testPrepareShouldNotTransformEscapedColon()
    {
        $this->function->pg_connect = true;
        $this->function->pg_prepare = function ($resource, $statementName, $sql) use (&$outerSql) {
            $outerSql = $sql;
        };

        $this
            ->if($driver = new \fastorm\Driver\Pgsql\Driver())
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
        $this->function->pg_connect = true;

        $this
            ->if($driver = new \fastorm\Driver\Pgsql\Driver())
            ->object($driver->escapeFields(array('Bouh'), function ($escaped) use (&$outerEscaped) {
                $outerEscaped = $escaped;
            }))
                ->isIdenticalTo($driver)
            ->string($outerEscaped[0])
                ->isIdenticalTo('"Bouh"');
    }

    public function testStartTransactionShouldExecuteQueryBegin()
    {
        $this->function->pg_query = function ($connection, $query) use (&$outerQuery) {
            $outerQuery = $query;
        };

        $this
            ->if($driver = new \fastorm\Driver\Pgsql\Driver())
            ->then($driver->startTransaction())
            ->string($outerQuery)
                ->isIdenticalTo('BEGIN');
    }

    public function testStartTransactionShouldRaiseException()
    {
        $this->function->pg_query = function ($connection, $query) use (&$outerQuery) {
            $outerQuery = $query;
        };

        $this
            ->if($driver = new \fastorm\Driver\Pgsql\Driver())
            ->then($driver->startTransaction())
            ->exception(function () use ($driver) {
                $driver->startTransaction();
            })
                ->hasMessage('Cannot start another transaction');

    }

    public function testCommitShouldExecuteQueryCommit()
    {
        $this->function->pg_query = function ($connection, $query) use (&$outerQuery) {
            $outerQuery = $query;
        };

        $this
            ->if($driver = new \fastorm\Driver\Pgsql\Driver())
            ->then($driver->startTransaction())
            ->then($driver->commit())
            ->string($outerQuery)
                ->isIdenticalTo('COMMIT');
    }

    public function testCommitShouldRaiseException()
    {
        $this->function->pg_query = function ($connection, $query) use (&$outerQuery) {
            $outerQuery = $query;
        };

        $this
            ->if($driver = new \fastorm\Driver\Pgsql\Driver())
            ->exception(function () use ($driver) {
                $driver->commit();
            })
                ->hasMessage('Cannot commit no transaction');

    }

    public function testRollbackShouldExecuteQueryRollback()
    {
        $this->function->pg_query = function ($connection, $query) use (&$outerQuery) {
            $outerQuery = $query;
        };

        $this
            ->if($driver = new \fastorm\Driver\Pgsql\Driver())
            ->then($driver->startTransaction())
            ->then($driver->rollback())
            ->string($outerQuery)
                ->isIdenticalTo('ROLLBACK');
    }

    public function testRollbackShouldRaiseException()
    {
        $this->function->pg_query = function ($connection, $query) use (&$outerQuery) {
            $outerQuery = $query;
        };

        $this
            ->if($driver = new \fastorm\Driver\Pgsql\Driver())
            ->exception(function () use ($driver) {
                $driver->rollback();
            })
                ->hasMessage('Cannot rollback no transaction');

    }
}
