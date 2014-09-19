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

namespace tests\units\CCMBenchmark\Ting\Driver\Pgsql;

use CCMBenchmark\Ting\Repository\Collection;
use CCMBenchmark\Ting\Query\Query;
use \mageekguy\atoum;

class Driver extends atoum
{

    public function testForConnectionKeyShouldCallCallbackWithConnectionNameAndDatabase()
    {
        $this
            ->if(\CCMBenchmark\Ting\Driver\Pgsql\Driver::forConnectionKey(
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
            ->object(new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->isInstanceOf('\CCMBenchmark\Ting\Driver\DriverInterface');
    }

    public function testConnectShouldReturnSelf()
    {
        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->object($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
                ->isIdenticalTo($driver);
    }

    public function testSetDatabaseShouldCompleteGeneratedDsnByConnect()
    {

        $this->function->pg_connect = function ($dsn) use (&$outerDsn) {
            $outerDsn = $dsn;
        };

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
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
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->exception(function () use ($driver) {
                $driver->setDatabase('bouh');
            })
                ->isInstanceOf('\CCMBenchmark\Ting\Driver\Exception')
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
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
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
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->object($driver->setDatabase('bouh'))
                ->isIdenticalTo($driver);
    }

    public function testIfNotConnectedShouldCallCallback()
    {
        $this->function->pg_connect = false;
        $called = false;

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
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
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
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
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
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
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
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
                ->isInstanceOf('\CCMBenchmark\Ting\Driver\QueryException');
    }

    public function testPrepareShouldNotTransformEscapedColon()
    {
        $this->function->pg_connect = true;
        $this->function->pg_prepare = function ($resource, $statementName, $sql) use (&$outerSql) {
            $outerSql = $sql;
        };

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
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
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
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
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
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
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
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
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
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
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
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
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
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
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->exception(function () use ($driver) {
                $driver->rollback();
            })
                ->hasMessage('Cannot rollback no transaction');

    }

    public function testExecuteInsertShouldCallPgQueryAndReturnInsertId()
    {
        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->and(
                $this->function->pg_query_params =
                function ($connection, $query, $params) use (&$outerQuery, &$outerParams) {
                    $outerQuery = $query;
                    $outerParams = $params;
                }
            )
            ->and(
                $this->function->pg_query =
                function ($connection, $query) use (&$outerQueryLastVal) {
                    $outerQueryLastVal = $query;
                }
            )
            ->and($this->function->pg_fetch_row = [12])
            ->integer(
                $driver->execute(
                    'INSERT INTO T_CITY_CIT (id, name, age) VALUES (:id, :name, :age)',
                    ['id' => 12, 'name' => 'L\'étang du lac', 'age' => 12.6],
                    Query::TYPE_INSERT
                )
            )
                ->isIdenticalTo(12)
            ->string($outerQuery)
                ->isIdenticalTo('INSERT INTO T_CITY_CIT (id, name, age) VALUES ($1, $2, $3)')
            ->array($outerParams)
                ->isIdenticalTo([12, 'L\'étang du lac', 12.6])
            ->string($outerQueryLastVal)
                ->isIdenticalTo('SELECT lastval()')
            ;
    }

    public function testExecuteUpdateShouldCallPgQueryAndReturnAffectedRows()
    {
        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->and(
                $this->function->pg_query_params =
                function ($connection, $query, $params) use (&$outerQuery, &$outerParams) {
                    $outerQuery = $query;
                    $outerParams = $params;
                }
            )
            ->and($this->function->pg_affected_rows = 4)
            ->integer(
                $driver->execute(
                    'UPDATE T_CITY_CIT SET name = :name WHERE id > :id',
                    ['id' => 12, 'name' => 'L\'étang du lac', 'age' => 12.6],
                    Query::TYPE_AFFECTED
                )
            )
                ->isIdenticalTo(4)
            ->string($outerQuery)
                ->isIdenticalTo('UPDATE T_CITY_CIT SET name = $1 WHERE id > $2')
            ->array($outerParams)
                ->isIdenticalTo(['L\'étang du lac', 12])
            ;
    }

    public function testSetCollectionShouldRaiseExceptionOnError()
    {
        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->and(
                $this->function->pg_query_params =
                function ($connection, $query, $params) use (&$outerQuery, &$outerParams) {
                    $outerQuery = $query;
                    $outerParams = $params;
                }
            )
            ->and($this->function->pg_affected_rows = 4)
            ->exception(
                function () use ($driver) {
                    $driver->setCollectionWithResult(
                        false,
                        'UPDATE T_CITY_CIT SET name = :name WHERE id > :id',
                        Query::TYPE_RESULT,
                        new Collection()
                    );
                }
            )
                ->isInstanceOf('\CCMBenchmark\Ting\Driver\QueryException')
            ;
    }

    public function testExecuteSelectShouldCallPgQueryAndReturnTrue()
    {

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->and(
                $this->function->pg_query_params =
                function ($connection, $query, $params) use (&$outerQuery, &$outerParams) {
                    $outerQuery = $query;
                    $outerParams = $params;
                }
            )
            ->and($this->function->pg_field_table = 'T_CITY_CIT')
            ->boolean(
                $driver->execute(
                    'SELECT id FROM T_CITY_CIT WHERE name = :name',
                    ['name' => 'L\'étang du lac'],
                    Query::TYPE_RESULT
                )
            )
                ->isTrue
            ->string($outerQuery)
                ->isIdenticalTo('SELECT id FROM T_CITY_CIT WHERE name = $1')
            ->array($outerParams)
                ->isIdenticalTo(['L\'étang du lac'])
            ;
    }
}
