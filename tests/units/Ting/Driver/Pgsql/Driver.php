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

use atoum;
use CCMBenchmark\Ting\Driver\Pgsql\PGMock;

require_once dirname(__FILE__) . '/../../../../fixtures/mock_native_pgsql.php';

class Driver extends atoum
{
    public function testGetConnectionKeyShouldBeIdempotent()
    {
        $connectionConfig = ['host' => '127.0.0.1', 'user' => 'app_read', 'password' => 'pzefgdfg', 'port' => 3306];
        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->string($driver->getConnectionKey($connectionConfig, 'myDatabase'))
                ->isIdenticalTo($driver->getConnectionKey($connectionConfig, 'myDatabase'))
                ->isIdenticalTo($driver->getConnectionKey($connectionConfig, 'myDatabase'))
        ;
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

    public function testCloseShouldReturnSelf()
    {
        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->object($driver->close())
            ->isIdenticalTo($driver);
    }

    public function testIfNotConnectedCallbackAfterClosedConnection()
    {
        $called = false;

        PGMock::override('pg_connect', true);
        PGMock::override('pg_close', true);

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->then($driver->setDatabase('database.test'))
            ->then($driver->close())
            ->then($driver->ifIsNotConnected(function () use (&$called) {
                $called = true;
            }))
            ->boolean($called)
            ->isTrue();
    }

    public function testSetCharset()
    {
        $mockDriver = new \mock\Fake\Pgsql();
        PGMock::override('pg_set_client_encoding', function ($connection, $charset) use (&$outerCharset) {
            $outerCharset = $charset;
        });

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver($mockDriver))
            ->then($driver->setCharset('utf8'))
            ->variable($outerCharset)
                ->isIdenticalTo('utf8');
    }

    public function testSetCharsetCallingTwiceShouldCallMysqliSetCharsetOnce()
    {
        $mockDriver = new \mock\Fake\Pgsql();
        $called = 0;
        PGMock::override('pg_set_client_encoding', function () use (&$called) {
            $called++;
        });

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver($mockDriver))
            ->then($driver->setCharset('utf8'))
            ->then($driver->setCharset('utf8'))
            ->variable($called)
                ->isIdenticalTo(1);

    }

    public function testSetCharsetWithInvalidCharsetShouldThrowAnException()
    {
        $mockDriver = new \mock\Fake\Pgsql();
        PGMock::override('pg_set_client_encoding', -1);
        PGMock::override('pg_last_error', 'ERROR:  invalid value for parameter "client_encoding": "utf8x"');

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver($mockDriver))
            ->exception(function () use ($driver) {
                $driver->setCharset('BadCharset');
            })
                ->hasMessage(
                    'Can\'t set charset BadCharset (ERROR:  invalid value for parameter "client_encoding": "utf8x")'
                );
    }

    public function testSetDatabaseShouldCompleteGeneratedDsnByConnect()
    {
        PGMock::override('pg_connect', function ($dsn) use (&$outerDsn) {
            $outerDsn = $dsn;
        });

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
        PGMock::override('pg_connect', false);

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->exception(function () use ($driver) {
                $driver->setDatabase('bouh');
            })
                ->isInstanceOf('\CCMBenchmark\Ting\Driver\Exception');
    }

    public function testsetDatabaseWithDatabaseAlreadySetShouldDoNothing()
    {
        $outerCount = 0;
        PGMock::override('pg_connect', function ($dsn) use (&$outerCount) {
            $outerCount++;
            return true;
        });

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
        PGMock::override('pg_connect', true);

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->object($driver->setDatabase('bouh'))
                ->isIdenticalTo($driver);
    }

    public function testIfNotConnectedShouldCallCallback()
    {
        PGMock::override('pg_connect', false);
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
        PGMock::override('pg_connect', true);
        PGMock::override('pg_last_error', 'unknown error');

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

    public function testPrepareShouldRaiseQueryException()
    {
        PGMock::override('pg_connect', true);
        PGMock::override('pg_query', true);
        PGMock::override('pg_prepare', false);
        PGMock::override('pg_last_error', 'unknown error');

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
        PGMock::override('pg_connect', true);
        PGMock::override('pg_query', true);
        PGMock::override('pg_prepare', function ($resource, $statementName, $sql) use (&$outerSql) {
            $outerSql = $sql;
        });

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

    public function testPrepareShouldHandleMultipleNamedPatternWithSameName()
    {
        PGMock::override('pg_connect', true);
        PGMock::override('pg_query', true);
        PGMock::override('pg_prepare', function ($resource, $statementName, $sql) use (&$outerSql) {
            $outerSql = $sql;
        });

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->then($driver->prepare(
                'SELECT * FROM T_BOUH_BOO WHERE name = ":name" OR firstname = ":name" OR lastname = ":lastname"',
                function () {
                }
            ))
            ->string($outerSql)
                ->isIdenticalTo('SELECT * FROM T_BOUH_BOO WHERE name = "$1" OR firstname = "$1" OR lastname = "$2"');
    }

    public function testEscapeFieldShouldReturnEscapedField()
    {
        PGMock::override('pg_connect', true);

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->string($driver->escapeField('Bouh'))
                ->isIdenticalTo('"Bouh"')
        ;
    }

    public function testStartTransactionShouldExecuteQueryBegin()
    {
        PGMock::override('pg_query', function ($connection, $query) use (&$outerQuery) {
            $outerQuery = $query;
        });

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->then($driver->startTransaction())
            ->string($outerQuery)
                ->isIdenticalTo('BEGIN');
    }

    public function testStartTransactionShouldRaiseException()
    {
        PGMock::override('pg_query', function ($connection, $query) use (&$outerQuery) {
            $outerQuery = $query;
        });

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
        PGMock::override('pg_query', function ($connection, $query) use (&$outerQuery) {
            $outerQuery = $query;
        });

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->then($driver->startTransaction())
            ->then($driver->commit())
            ->string($outerQuery)
                ->isIdenticalTo('COMMIT');
    }

    public function testCommitShouldRaiseException()
    {
        PGMock::override('pg_query', function ($connection, $query) use (&$outerQuery) {
            $outerQuery = $query;
        });

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->exception(function () use ($driver) {
                $driver->commit();
            })
                ->hasMessage('Cannot commit no transaction');

    }

    public function testRollbackShouldExecuteQueryRollback()
    {
        PGMock::override('pg_query', function ($connection, $query) use (&$outerQuery) {
            $outerQuery = $query;
        });

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->then($driver->startTransaction())
            ->then($driver->rollback())
            ->string($outerQuery)
                ->isIdenticalTo('ROLLBACK');
    }

    public function testRollbackShouldRaiseException()
    {
        PGMock::override('pg_query', function ($connection, $query) use (&$outerQuery) {
            $outerQuery = $query;
        });

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->exception(function () use ($driver) {
                $driver->rollback();
            })
                ->hasMessage('Cannot rollback no transaction');

    }

    public function testGetAffectedRowsWithoutResultShouldReturn0()
    {
        PGMock::override('pg_affected_rows', 12);
        PGMock::override('pg_affected_rows', 12);

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->integer($driver->getAffectedRows())
                ->isIdenticalTo(0)
        ;
    }

    public function testGetInsertedIdShouldReturnInsertedId()
    {
        PGMock::override('pg_query', function ($connection, $query) use (&$outerQuery) {
            $outerQuery = $query;
        });
        PGMock::override('pg_fetch_row', [8]);


        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->integer($driver->getInsertedId())
                ->isIdenticalTo(8)
            ->string($outerQuery)
                ->isIdenticalTo('SELECT lastval()')
        ;
    }

    public function testgetInsertedIdForSequenceShouldReturnInsertedIdForSequence()
    {
        PGMock::override('pg_query', function ($connection, $query) use (&$outerQuery) {
            $outerQuery = $query;
        });

        PGMock::override('pg_fetch_row', [4]);

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->integer($driver->getInsertedIdForSequence('sequenceName'))
                ->isIdenticalTo(4)
            ->string($outerQuery)
                ->isIdenticalTo("SELECT currval('sequenceName')")
        ;
    }

    public function testgetInsertedIdForSequenceWithWrongSequenceShouldThrowAnException()
    {
        PGMock::override('pg_query', false);
        PGMock::override('pg_last_error', 'A PGSQL error');

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->exception(function () use ($driver) {
                $driver->getInsertedIdForSequence('sequenceName');
            })
                ->isInstanceOf('\CCMBenchmark\Ting\Driver\QueryException')
                ->hasMessage("A PGSQL error (Query: SELECT currval('sequenceName'))");
        ;
    }

    public function testExecuteShouldCallPGQueryParams()
    {
        $count = 0;
        $outerSql = '';
        $outerValues = '';
        PGMock::override('pg_query_params', function (
            $connection,
            $sql,
            $values
        ) use (
            &$count,
            &$outerSql,
            &$outerValues
        ) {
            $count++;
            $outerSql = $sql;
            $outerValues = $values;
        });
        PGMock::override('pg_result_status', \PGSQL_TUPLES_OK);
        PGMock::override('pg_fetch_assoc', null);

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
                ->then($driver->execute('SELECT 1 FROM "myTable" WHERE id = :id', ['id' => 12]))
                    ->array($outerValues)
                        ->isIdenticalTo([0 => 12])
                    ->string($outerSql)
                        ->isIdenticalTo('SELECT 1 FROM "myTable" WHERE id = $1')
                    ->integer($count)
                        ->isIdenticalTo(1)
                ->then($driver->execute(
                    'INSERT INTO "myTable" (date_field) VALUES (:date)',
                    ['date' => '2014-12-31 23:59:59']
                ))
                    ->array($outerValues)
                        ->isIdenticalTo([0 => '2014-12-31 23:59:59'])
                    ->string($outerSql)
                        ->isIdenticalTo('INSERT INTO "myTable" (date_field) VALUES ($1)')
                    ->integer($count)
                        ->isIdenticalTo(2)
        ;
    }

    public function testExecuteWithoutParametersShouldCallPGQuery()
    {
        $pgQueryCalled = false;
        PGMock::override('pg_query', function () use (&$pgQueryCalled) {
            $pgQueryCalled = true;
        });

        PGMock::override('pg_result_status', \PGSQL_TUPLES_OK);
        PGMock::override('pg_fetch_assoc', null);

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->then($driver->execute('SELECT 1 FROM "myTable"'))
            ->boolean($pgQueryCalled)
                ->isTrue();
        ;
    }

    public function testExecuteShouldCallSetOnCollection()
    {
        PGMock::override('pg_connect', true);
        PGMock::override('pg_query_params', true);
        PGMock::override('pg_fetch_array', 'data');
        PGMock::override('pg_result_seek', true);
        PGMock::override('pg_num_fields', 1);
        PGMock::override('pg_field_table', 'myTable');
        PGMock::override('pg_field_name', '1');

        $mockCollection = new \mock\CCMBenchmark\Ting\Repository\Collection();
        $this->calling($mockCollection)->set = true;

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->then($driver->execute('SELECT 1 FROM myTable WHERE id = :id', ['id' => 12], $mockCollection))
            ->mock($mockCollection)
                ->call('set')->once();
        ;
    }

    public function testExecuteShouldReturnArray()
    {
        PGMock::override('pg_connect', true);
        PGMock::override('pg_query_params', true);
        PGMock::override('pg_fetch_assoc', ['Bouh' => 'Hop']);
        PGMock::override('pg_result_status', \PGSQL_TUPLES_OK);

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->array($driver->execute('SELECT 1 FROM myTable WHERE id = :id', ['id' => 12]))
            ->isIdenticalTo(['Bouh' => 'Hop']);
    }

    public function testExecuteShouldOnlyReplaceParameters()
    {
        $outerSql = true;
        $outerValues = [];
        PGMock::override('pg_connect', true);
        PGMock::override('pg_query_params', function ($connection, $sql, $values) use (&$outerSql, &$outerValues) {
            $outerSql = $sql;
            $outerValues = $values;
        });
        PGMock::override('pg_fetch_assoc', ['Bouh' => 'Hop']);
        PGMock::override('pg_result_status', \PGSQL_TUPLES_OK);

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->then($driver->execute("SELECT 'Bouh:Ting', ' ::Ting', ADDTIME('23:59:59', '1:1:1') '
                . ' FROM Bouh WHERE id = :id AND login = :login",
                ['id' => 3, 'login' => 'Sylvain']))
            ->string($outerSql)
                ->isIdenticalTo("SELECT 'Bouh:Ting', ' ::Ting', ADDTIME('23:59:59', '1:1:1') '
                . ' FROM Bouh WHERE id = $1 AND login = $2")
            ->array($outerValues)
                ->isIdenticalTo([3, 'Sylvain']);
    }

    public function testExecuteShouldRaiseExceptionWhenErrorHappensWithQuery()
    {
        PGMock::override('pg_connect', true);
        PGMock::override('pg_query_params', false);
        PGMock::override('pg_fetch_array', 'data');
        PGMock::override('pg_result_seek', true);
        PGMock::override('pg_field_table', 'myTable');
        PGMock::override('pg_last_error', 'Unknown Error');

        $mockCollection = new \mock\CCMBenchmark\Ting\Repository\Collection();
        $this->calling($mockCollection)->set = true;

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
                ->exception(function () use ($driver) {
                    $driver->execute('SELECT 1 FROM myTable WHERE id = :id', ['id' => 12]);
                })
                    ->isInstanceOf('\CCMBenchmark\Ting\Driver\QueryException')
                    ->hasMessage('Unknown Error (Query: SELECT 1 FROM myTable WHERE id = $1)')

        ;
    }

    public function testExecuteShouldLogQuery()
    {
        PGMock::override('pg_query_params', true);
        PGMock::override('pg_fetch_array', 'data');
        PGMock::override('pg_result_seek', true);
        PGMock::override('pg_field_table', 'myTable');
        PGMock::override('pg_result_status', \PGSQL_TUPLES_OK);
        PGMock::override('pg_fetch_assoc', null);

        $mockLogger = new \mock\tests\fixtures\FakeLogger\FakeDriverLogger();

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->and($driver->setLogger($mockLogger))
            ->then($driver->execute('SELECT 1 FROM myTable WHERE id = :id', ['id' => 12]))
                ->mock($mockLogger)
                    ->call('startQuery')
                        ->once()
                    ->call('stopQuery')
                        ->once()
        ;
    }

    public function testPrepareShouldLogQuery()
    {
        PGMock::override('pg_prepare', true);
        PGMock::override('pg_query', true);
        PGMock::override('pg_fetch_array', 'data');
        PGMock::override('pg_result_seek', true);
        PGMock::override('pg_field_table', 'myTable');

        $mockLogger = new \mock\tests\fixtures\FakeLogger\FakeDriverLogger();

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->and($driver->setLogger($mockLogger))
            ->then($driver->prepare('SELECT 1 FROM myTable WHERE id = :id'))
                ->mock($mockLogger)
                    ->call('startPrepare')
                        ->once()
                    ->call('stopPrepare')
                        ->once();
    }

    public function testPrepareCalledTwiceShouldReturnTheSameObject()
    {
        PGMock::override('pg_prepare', true);
        PGMock::override('pg_query', true);

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->then($statement = $driver->prepare('SELECT 1 FROM myTable WHERE id = :id'))
            ->object($driver->prepare('SELECT 1 FROM myTable WHERE id = :id'))
            ->isIdenticalTo($statement);
    }

    public function testCloseStatementShouldRaiseExceptionOnNonExistentStatement()
    {
        PGMock::override('pg_prepare', true);

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->exception(function () use ($driver) {
                $driver->closeStatement('NonExistentStatement');
            })
            ->isInstanceOf('CCMBenchmark\Ting\Driver\Exception')
        ;
    }

    public function testPingShouldCallPingIfConnected()
    {
        PGMock::override('pg_connect', true);
        PGMock::override('pg_ping', true);

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->then($driver->setDatabase('myDatabase'))
            ->boolean($driver->ping())
                ->isTrue()
        ;
    }

    public function testPingShouldCallPingWithCharset()
    {
        PGMock::override('pg_connect', true);
        PGMock::override('pg_ping', true);
        PGMock::override('pg_set_client_encoding', function ($connection, $charset) use (&$outerCharset, &$called) {
            $called++;
            $outerCharset = $charset;
        });

        $mockDriver = new \mock\Fake\Pgsql();

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver($mockDriver))
                ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
                ->then($driver->setDatabase('myDatabase'))
                ->then($driver->setCharset('UTF8'))
            ->boolean($driver->ping())
                ->isTrue()
            ->integer($called)->isIdenticalTo(2)
        ;
    }

    public function testPingShouldCallPingWithTimezone()
    {
        $called = 0;
        $outerArgs = [];
        PGMock::override('pg_connect', true);
        PGMock::override('pg_ping', true);
        PGMock::override('pg_query', function () use (&$called, &$outerArgs) {
            $outerArgs[] = func_get_args();
            $called++;
            return true;
        });

        $mockDriver = new \mock\Fake\Pgsql();

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver($mockDriver))
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->then($driver->setDatabase('myDatabase'))
            ->then($driver->setTimezone('timezone'))
            ->boolean($driver->ping())
                ->isTrue()
            ->integer($called)->isIdenticalTo(2)
            ->array(array_column($outerArgs, 1))
                ->isIdenticalTo(array_fill(0, 2, 'SET timezone = "timezone";'));
        ;
    }

    public function testPingShouldCallRaiseAnExceptionWhenNotConnected()
    {
        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->exception(function () use ($driver) {
                $driver->ping();
            })
            ->isInstanceOf('CCMBenchmark\Ting\Driver\NeverConnectedException')
        ;
    }

    public function testTimezone()
    {
        PGMock::override('pg_connect', true);
        PGMock::override('pg_query', true);

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->then($driver->setDatabase('myDatabase'))
            ->variable($driver->setTimezone('timezone'))
            ->isNull()
        ;
    }

    public function testDefaultTimezone()
    {
        PGMock::override('pg_connect', true);
        PGMock::override('pg_query', true);

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->then($driver->setDatabase('myDatabase'))
            ->variable($driver->setTimezone(null))
            ->isNull()
        ;
    }

    public function testSetTimezoneThenDefaultTimezone()
    {
        PGMock::override('pg_connect', true);
        $outerArgs = [];
        PGMock::override('pg_query', function () use (&$outerArgs) {
            $outerArgs[] = func_get_args();
            return true;
        });

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Pgsql\Driver())
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->then($driver->setDatabase('myDatabase'))
            ->then($driver->setTimezone('timezone'))
            ->variable($outerArgs[0][1])
                ->isIdenticalTo('SET timezone = "timezone";')
            ->then($driver->setTimezone(null))
            ->variable($outerArgs[1][1])
                ->isIdenticalTo('SET timezone = DEFAULT;')
            ->array($outerArgs)
                ->hasSize(2)
        ;
    }
}
