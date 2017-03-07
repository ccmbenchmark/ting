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

namespace tests\units\CCMBenchmark\Ting\Driver\Mysqli;

use CCMBenchmark\Ting\Repository\Collection;
use mageekguy\atoum;

class Driver extends atoum
{

    public function testGetConnectionKeyShouldBeIdempotent()
    {
        $mockDriver = new \mock\Fake\Mysqli();

        $connectionConfig = ['host' => '127.0.0.1', 'user' => 'app_read', 'password' => 'pzefgdfg', 'port' => 3306];
        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($mockDriver))
            ->string($driver->getConnectionKey($connectionConfig, 'myDatabase'))
                ->isIdenticalTo($driver->getConnectionKey($connectionConfig, 'myDatabase'))
                ->isIdenticalTo($driver->getConnectionKey($connectionConfig, 'myDatabase'))
        ;
    }

    public function testShouldImplementDriverInterface()
    {
        $this
            ->object(new \CCMBenchmark\Ting\Driver\Mysqli\Driver())
            ->isInstanceOf('\CCMBenchmark\Ting\Driver\DriverInterface');
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
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($mockDriver))
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
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($mockDriver))
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
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver())
            ->exception(function () use ($driver) {
                $driver->connect('localhost', 'user.test', 'password.test', 1234);
            })
                ->isInstanceOf('\CCMBenchmark\Ting\Driver\Exception');
    }

    public function testConnectWithUnresolvableHostShouldRaiseDriverException()
    {
        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver())
            ->exception(function () use ($driver) {
                $driver->connect('hostname.test', 'user.test', 'password.test', 1234);
            })
                ->isInstanceOf('\CCMBenchmark\Ting\Driver\Exception')
                ->error()
                    ->withType(E_WARNING)
                    ->exists();
    }

    public function testCloseShouldReturnSelf()
    {
        $mockDriver = new \mock\Fake\Mysqli();

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($mockDriver))
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->object($driver->close())
            ->isIdenticalTo($driver);
    }

    public function testIfNotConnectedCallbackAfterClosedConnection()
    {
        $called = false;

        $mockDriver = new \mock\Fake\Mysqli();
        $this->calling($mockDriver)->real_connect = true;

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($mockDriver))
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->then($driver->close())
            ->then($driver->ifIsNotConnected(function () use (&$called) {
                $called = true;
            }))
            ->boolean($called)
            ->isTrue();
    }

    public function testSetCharset()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $this->calling($mockDriver)->set_charset = function ($charset) {
            $this->charset = $charset;
        };

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($mockDriver))
            ->then($driver->setCharset('utf8'))
            ->variable($mockDriver->charset)
            ->isIdenticalTo('utf8');
    }

    public function testSetCharsetCallingTwiceShouldCallMysqliSetCharsetOnce()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $this->calling($mockDriver)->set_charset = function ($charset) {
            $this->charset = $charset;
        };

        $this
            ->if($driver = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver($mockDriver))
            ->then($driver->setCharset('utf8'))
            ->then($driver->setCharset('utf8'))
            ->mock($mockDriver)
                ->call('set_charset')
                    ->once();
    }

    public function testSetCharsetWithInvalidCharsetShouldThrowAnException()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $mockDriver->error = 'Invalid characterset or character set not supported';
        $this->calling($mockDriver)->set_charset = function ($charset) {
            return false;
        };

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($mockDriver))
            ->exception(function () use ($driver) {
                $driver->setCharset('BadCharset');
            })
                ->hasMessage('Can\'t set charset BadCharset (Invalid characterset or character set not supported)');
    }

    public function testSetDatabase()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $mockDriver->error = '';
        $this->calling($mockDriver)->real_connect = $mockDriver;
        $this->calling($mockDriver)->select_db = function ($database) {
                $this->database = $database;
        };

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($mockDriver))
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
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($mockDriver))
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
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($mockDriver))
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
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($mockDriver))
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->exception(function () use ($driver) {
                $driver->setDatabase('bouh');
            })
                ->isInstanceOf('\CCMBenchmark\Ting\Driver\Exception');
    }

    public function testIfNotConnectedShouldCallCallback()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $this->calling($mockDriver)->real_connect = false;

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver())
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
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($mockDriver))
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->then($driver->ifIsError(function () use (&$callable) {
                $callable = true;
            }))
            ->boolean($callable)
                ->isTrue();
    }

    public function testPrepareShouldRaiseQueryException()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $mockDriver->errno = 123;
        $mockDriver->error = 'unknown error';
        $this->calling($mockDriver)->real_connect = $mockDriver;
        $this->calling($mockDriver)->prepare = false;

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($mockDriver))
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->exception(function () use ($driver) {
                $driver->prepare(
                    'SELECT 1 FROM bouh WHERE first = :first AND second = :second'
                );
            })
                ->isInstanceOf('\CCMBenchmark\Ting\Driver\QueryException');
    }

    public function testExecuteShouldCallDriverQuery()
    {
        $driverFake          = new \mock\Fake\Mysqli();
        $mockMysqliResult    = new \mock\tests\fixtures\FakeDriver\MysqliResult([]);

        $this->calling($driverFake)->query = $mockMysqliResult;

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($driverFake))
            ->then($driver->execute('Empty query'))
            ->mock($driverFake)
                ->call('query')
                    ->once();
    }

    public function testExecuteShouldRaiseExceptionIfValueNotDefined()
    {
        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver())
            ->exception(function () use ($driver) {
                $driver->execute('SELECT * WHERE id = :id');
            })
                ->isInstanceOf('\CCMBenchmark\Ting\Driver\QueryException');
    }

    public function testExecuteShouldOnlyReplaceParameters()
    {
        $driverFake = new \mock\Fake\Mysqli();
        $driverFake->error = 'none';
        $driverFake->errno = 0;
        $this->calling($driverFake)->query = false;
        $this->calling($driverFake)->real_escape_string = function ($value) {
            return $value;
        };

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($driverFake))
            ->exception(function () use ($driver) {
                $driver->execute("SELECT 'Bouh:Ting', ' ::Ting', ADDTIME('23:59:59', '1:1:1') '
                . ' FROM Bouh WHERE id = :id AND login = :login",
                    ['id' => 3, 'login' => 'Sylvain']);
            })
                ->hasCode(0)
            ->mock($driverFake)
                ->call('query')
                    ->withIdenticalArguments("SELECT 'Bouh:Ting', ' ::Ting', ADDTIME('23:59:59', '1:1:1') '
                . ' FROM Bouh WHERE id = 3 AND login = \"Sylvain\"")
                        ->once();
    }

    public function testExecuteShouldReturnACollection()
    {
        $driverFake          = new \mock\Fake\Mysqli();
        $mockMysqliResult    = new \mock\tests\fixtures\FakeDriver\MysqliResult([]);

        $collection = new Collection();

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($driverFake))
            ->and(
                $this->calling($driverFake)->real_escape_string = function ($value) {
                    if ($value instanceof \DateTime) {
                        $value = $value->format('Y-m-d H:i:s');
                    }
                    return addcslashes($value, '"');
                }
            )
            ->and(
                $this->calling($driverFake)->query = function ($sql) use (&$outerSql, $mockMysqliResult) {
                    $outerSql = $sql;
                    return $mockMysqliResult;
                }
            )
            ->object(
                $driver->execute(
                    'SELECT population FROM T_CITY_CIT WHERE id = :id
                    AND name = :name AND age = :age AND last_modified = :date',
                    [
                        'id' => 12,
                        'name' => 'L\'étang du lac',
                        'age' => 12.6,
                        'date' => \DateTime::createFromFormat('Y-m-d H:i:s', '2014-03-01 14:02:05')
                    ],
                    $collection
                )
            )
                ->isInstanceOf($collection)
        ;
    }

    public function testExecuteShouldThrowExceptionOnErrorWithQuery()
    {
        $driverFake          = new \mock\Fake\Mysqli();
        $mockMysqliResult    = new \mock\tests\fixtures\FakeDriver\MysqliResult([]);

        $collection = new Collection();

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($driverFake))
            ->and(
                $this->calling($driverFake)->real_escape_string = function ($value) {
                    if ($value instanceof \DateTime) {
                        $value = $value->format('Y-m-d H:i:s');
                    }
                    return addcslashes($value, '"');
                }
            )
            ->and($this->calling($driverFake)->query = false)
            ->and($driverFake->error = 'Undefined Error')
            ->and($driverFake->errno = 127)
            ->exception(function () use ($driver, $collection) {
                $driver->execute(
                    'SELECT population FROM T_CITY_CIT WHERE id = :id AND name = :name AND age = :age AND last_modified = :date',
                    [
                        'id' => 12,
                        'name' => 'L\'étang du lac',
                        'age' => 12.6,
                        'date' => \DateTime::createFromFormat('Y-m-d H:i:s', '2014-03-01 14:02:05')
                    ],
                    $collection
                );
            })
                ->isInstanceOf('\CCMBenchmark\Ting\Driver\QueryException')
                ->hasMessage('Undefined Error (Query: SELECT population FROM T_CITY_CIT WHERE id = 12 AND name = "L\'étang du lac" AND age = 12.6 AND last_modified = "2014-03-01 14:02:05")')
        ;
    }

    public function testExecuteShouldBuildACorrectQuery()
    {
        $driverFake       = new \mock\Fake\Mysqli();
        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult(['hop' => 'la']);

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($driverFake))
            ->and(
                $this->calling($driverFake)->real_escape_string = function ($value) {
                    if ($value instanceof \DateTime) {
                        $value = $value->format('Y-m-d H:i:s');
                    }

                    return addcslashes($value, '"');
                }
            )
            ->and(
                $this->calling($driverFake)->query = function ($sql) use (&$outerSql, $mockMysqliResult) {
                    $outerSql = $sql;
                    return $mockMysqliResult;
                }
            )
            ->array(
                $driver->execute(
                    'SELECT population FROM T_CITY_CIT WHERE id = :id
                    AND name = :name AND age = :age AND last_modified = :date',
                    [
                        'id' => 12,
                        'name' => 'L\'étang du lac',
                        'age' => 12.6,
                        'date' => \DateTime::createFromFormat('Y-m-d H:i:s', '2014-03-01 14:02:05')
                    ]
                )
            )
                ->isIdenticalTo(['hop' => 'la'])
            ->string($outerSql)
                ->isEqualTo(
                    'SELECT population FROM T_CITY_CIT WHERE id = 12
                    AND name = "L\'étang du lac" AND age = 12.6 AND last_modified = "2014-03-01 14:02:05"'
                )
            ->mock($driverFake)
                ->call('query')
                    ->once();
    }

    public function testExecuteShouldReturnTrue()
    {
        $driverFake = new \mock\Fake\Mysqli();
        $this->calling($driverFake)->query = function ($sql) {
            return true;
        };

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($driverFake))
            ->boolean($driver->execute('UPDATE Bouh SET id = 3'))
                ->isTrue();
    }

    public function testPrepareShouldNotTransformEscapedColon()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $this->calling($mockDriver)->real_connect = $mockDriver;
        $driverStatement = new \mock\Fake\DriverStatement();
        $this->calling($driverStatement)->close = true;

        $this->calling($mockDriver)->prepare = function ($sql) use (&$outerSql, $driverStatement) {
            $outerSql = $sql;

            return $driverStatement;
        };

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($mockDriver))
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->then($driver->prepare(
                'SELECT * FROM T_BOUH_BOO WHERE name = "\:bim"'
            ))
            ->string($outerSql)
                ->isIdenticalTo('SELECT * FROM T_BOUH_BOO WHERE name = ":bim"');
    }

    public function testPrepareCalledTwiceShouldReturnTheSameObject()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $this->calling($mockDriver)->real_connect = $mockDriver;
        $driverStatement = new \mock\Fake\DriverStatement();
        $this->calling($driverStatement)->close = true;

        $this->calling($mockDriver)->prepare = function ($sql) use (&$outerSql, $driverStatement) {
            $outerSql = $sql;

            return $driverStatement;
        };

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($mockDriver))
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->then($statement = $driver->prepare(
                'SELECT * FROM T_BOUH_BOO WHERE name = "\:bim"'
            ))
            ->object($driver->prepare(
                'SELECT * FROM T_BOUH_BOO WHERE name = "\:bim"'
            ))
            ->isIdenticalTo($statement);
    }

    public function testEscapeFieldShouldEscapeField()
    {
        $mockDriver = new \mock\Fake\Mysqli();

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($mockDriver))
            ->string($driver->escapeField('Bouh'))
                ->isIdenticalTo('`Bouh`')
        ;
    }

    public function testStartTransactionShouldRaiseExceptionIfCalledTwice()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($mockDriver))
            ->then($driver->startTransaction())
            ->exception(function () use ($driver) {
                    $driver->startTransaction();
            })
                ->isInstanceOf('\CCMBenchmark\Ting\Driver\Exception')
        ;
    }

    public function testCommitShouldCloseTransaction()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($mockDriver))
            ->then($driver->startTransaction())
            ->then($driver->commit())
            ->exception(function () use ($driver) {
                $driver->commit();
            })
                ->hasMessage('Cannot commit no transaction')
            ;
    }

    public function testCommitShouldRaiseExceptionIfNoTransaction()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($mockDriver))
            ->exception(function () use ($driver) {
                    $driver->commit();
            })
                ->isInstanceOf('\CCMBenchmark\Ting\Driver\Exception')
        ;
    }

    public function testRollbackShouldCloseTransaction()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($mockDriver))
            ->then($driver->startTransaction())
            ->then($driver->rollback())
            ->exception(function () use ($driver) {
                $driver->rollback();
            })
                ->isInstanceOf('\CCMBenchmark\Ting\Driver\Exception')
                ->hasMessage('Cannot rollback no transaction')
            ;
    }

    public function testRollbackShouldRaiseExceptionIfNoTransaction()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($mockDriver))
            ->exception(function () use ($driver) {
                    $driver->rollback();
            })
                ->isInstanceOf('\CCMBenchmark\Ting\Driver\Exception')
        ;
    }

    public function testGetInsertIdShouldReturnInsertedId()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $this->calling($mockDriver)->real_connect = $mockDriver;
        $mockDriver->insert_id = 3;

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($mockDriver))
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->integer($driver->getInsertId())
            ->isIdenticalTo(3)
        ;
    }

    public function testGetAffectedRowsShouldReturnAffectedRows()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $this->calling($mockDriver)->real_connect = $mockDriver;
        $mockDriver->affected_rows = 12;

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($mockDriver))
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->integer($driver->getAffectedRows())
            ->isIdenticalTo(12)
        ;
    }

    public function testGetAffectedRowsShouldReturn0OnError()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $this->calling($mockDriver)->real_connect = $mockDriver;
        $mockDriver->affected_rows = -1;

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($mockDriver))
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->integer($driver->getAffectedRows())
            ->isIdenticalTo(0)
        ;
    }

    public function testExecuteMustLogQuery()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $mockLogger = new \mock\tests\fixtures\FakeLogger\FakeDriverLogger();

        $this->calling($mockDriver)->query = true;

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($mockDriver))
            ->and($driver->setLogger($mockLogger))
            ->then($driver->execute('Empty query'))
            ->mock($mockLogger)
                ->call('startQuery')
                    ->once()
                ->call('stopQuery')
                    ->once()
        ;
    }

    public function testPrepareShouldLogQuery()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $driverStatement = new \mock\Fake\DriverStatement();
        $this->calling($mockDriver)->prepare = $driverStatement;
        $this->calling($driverStatement)->close = true;

        $mockLogger = new \mock\tests\fixtures\FakeLogger\FakeDriverLogger();

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($mockDriver))
            ->and($driver->setLogger($mockLogger))
            ->then($driver->prepare('Empty query'))
            ->mock($mockLogger)
                ->call('startPrepare')
                    ->once()
                ->call('stopPrepare')
                    ->once();
    }

    public function testCloseStatementShouldRaiseExceptionOnNonExistentStatement()
    {
        $mockDriver = new \mock\Fake\Mysqli();

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($mockDriver))
            ->exception(function () use ($driver) {
                $driver->closeStatement('NonExistentStatementName');
            })
            ->isInstanceOf('CCMBenchmark\Ting\Driver\Exception')
        ;
    }

    public function testPingShouldCallPingIfConnected()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $this->calling($mockDriver)->real_connect = true;
        $this->calling($mockDriver)->ping = true;

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($mockDriver))
            ->then($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->boolean($driver->ping())
                ->isTrue()
            ->mock($mockDriver)
                ->call('ping')
                    ->once()
        ;
    }

    public function testPingShouldReconnect()
    {
        $mockDriver = new \mock\Fake\Mysqli();
        $this->calling($mockDriver)->ping = false;
        $this->calling($mockDriver)->real_connect = true;

        $this
            ->given($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($mockDriver))
            ->and($driver->connect('hostname.test', 'user.test', 'password.test', 1234))
            ->boolean($driver->ping())
                ->isTrue()
            ->mock($mockDriver)
                ->call('real_connect')
                    // 1 call for connect() + 1 call for ping()
                    ->exactly(2);
    }

    public function testPingShouldCallRaiseAnExceptionWhenNotConnected()
    {
        $mockDriver = new \mock\Fake\Mysqli();

        $this
            ->if($driver = new \CCMBenchmark\Ting\Driver\Mysqli\Driver($mockDriver))
            ->exception(function () use ($driver) {
                $driver->ping();
            })
                ->isInstanceOf('CCMBenchmark\Ting\Driver\NeverConnectedException')
        ;
    }
}
