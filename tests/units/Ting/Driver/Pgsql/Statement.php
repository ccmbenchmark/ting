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

class Statement extends atoum
{

    public function testExecuteShouldCallTheRightConnection()
    {
        PGMock::override('pg_execute', function ($connection, $statementName, $values) use (&$outerConnection) {
            $outerConnection = $connection;
            return [];
        });

        PGMock::override('pg_num_fields', 0);
        PGMock::override('pg_field_table', 'Bouh');
        PGMock::override('pg_result_seek', 0);
        PGMock::override('pg_fetch_array', false);
        PGMock::override('pg_query', true);

        $collection = new \mock\CCMBenchmark\Ting\Repository\Collection();

        $this
            ->if($statement = new \CCMBenchmark\Ting\Driver\Pgsql\Statement(
                'MyStatementName',
                [],
                'connectionName',
                'database'
            ))
            ->then($statement->setConnection('Awesome connection resource'))
            ->then($statement->setQuery('SELECT firstname FROM Bouh'))
            ->then($statement->execute([], $collection))
            ->string($outerConnection)
                ->isIdenticalTo('Awesome connection resource');
    }

    public function testExecuteShouldCallDriverExecuteWithParameters()
    {
        PGMock::override('pg_num_fields', 0);
        PGMock::override('pg_field_table', 'Bouh');
        PGMock::override('pg_execute', function ($connection, $statementName, $values) use (&$outerValues) {
            $outerValues = $values;
            return [];
        });
        PGMock::override('pg_result_seek', 0);
        PGMock::override('pg_fetch_array', false);
        PGMock::override('pg_query', true);

        $collection      = new \mock\CCMBenchmark\Ting\Repository\Collection();
        $params          = array(
            'firstname'   => 'Sylvain',
            'id'          => 3,
            'old'         => 32.1,
            'description' => 'A very long description',
            'date' => '2014-03-01 14:02:05'
        );

        $paramsOrder = array('firstname' => null, 'id' => null, 'description' => null, 'old' => null, 'date' => null);

        $this
            ->if($statement = new \CCMBenchmark\Ting\Driver\Pgsql\Statement(
                'MyStatementName',
                $paramsOrder,
                'connectionName',
                'database'
            ))
            ->then($statement->setQuery('SELECT firstname FROM Bouh'))
            ->then($statement->execute($params, $collection))
            ->array($outerValues)
                ->isIdenticalTo(array('Sylvain', 3, 'A very long description', 32.1, '2014-03-01 14:02:05'));
    }

    public function testSetCollectionWithResult()
    {
        $collection = new \mock\CCMBenchmark\Ting\Repository\Collection();
        $result     = new \CCMBenchmark\Ting\Driver\Pgsql\Result();
        $result->setConnectionName('connectionName');
        $result->setDatabase('database');
        $result->setResult([
            [
                'prenom' => 'Sylvain',
                'nom'    => 'Robez-Masson'
            ],
            [
                'prenom' => 'Xavier',
                'nom'    => 'Leune'
            ]
        ]);
        PGMock::override('pg_query', true);

        $this->calling($collection)->set = function ($result) use (&$outerResult) {
            $outerResult = $result;
        };

        PGMock::override('pg_num_fields', 2);
        PGMock::override('pg_field_table', 'Bouh');
        PGMock::override('pg_field_name', function ($result, $index) {
            switch ($index) {
                case 0:
                    return 'prenom';
                case 1:
                    return 'nom';
                default:
                    return false;
            }
        });

        $resultOk = new \CCMBenchmark\Ting\Driver\Pgsql\Result($result);
        $resultOk->setConnectionName('connectionName');
        $resultOk->setDatabase('database');
        $resultOk->setResult($result);
        $resultOk->setQuery('SELECT prenom, nom FROM Bouh');

        $this
            ->if($statement = new \CCMBenchmark\Ting\Driver\Pgsql\Statement(
                'MyStatementName',
                [],
                'connectionName',
                'database'
            ))
            ->then($statement->setQuery('SELECT prenom, nom FROM Bouh'))
            ->then($statement->setCollectionWithResult($result, $collection))
            ->mock($collection)
                ->call('set')
                    ->once()
            ->object($outerResult)
                ->isCloneOf($resultOk);
    }

    public function testExecuteShouldRaiseQueryException()
    {
        $collection = new \mock\CCMBenchmark\Ting\Repository\Collection();
        PGMock::override('pg_execute', false);
        PGMock::override('pg_query', true);
        PGMock::override('pg_errormessage', 'unknown error');

        $this
            ->if($statement = new \CCMBenchmark\Ting\Driver\Pgsql\Statement(
                'MyStatementName',
                [],
                'connectionName',
                'database'
            ))
            ->exception(function () use ($statement, $collection) {
                $statement->execute([]);
            })
                ->isInstanceOf('\CCMBenchmark\Ting\Driver\QueryException');
    }

    public function testExecuteShouldReturnTrueIfNoError()
    {
        PGMock::override('pg_execute', true);
        PGMock::override('pg_query', true);

        $this
            ->if($statement = new \CCMBenchmark\Ting\Driver\Pgsql\Statement(
                'MyStatementName',
                [],
                'connectionName',
                'database'
            ))
            ->boolean($statement->execute([]))
                ->isTrue()
        ;
    }

    public function testExecuteShouldLogQuery()
    {
        PGMock::override('pg_execute', []);
        PGMock::override('pg_result_seek', 0);
        PGMock::override('pg_fetch_array', false);
        PGMock::override('pg_query', true);

        $mockLogger = new \mock\tests\fixtures\FakeLogger\FakeDriverLogger();

        $this
            ->if($statement = new \CCMBenchmark\Ting\Driver\Pgsql\Statement(
                'statementNameTest',
                [],
                'connectionName',
                'database'
            ))
            ->and($statement->setLogger($mockLogger))
            ->and($statement->setQuery('SELECT firstname FROM Bouh'))
            ->then($statement->execute([]))
                ->mock($mockLogger)
                    ->call('startStatementExecute')
                        ->once()
                    ->call('stopStatementExecute')
                        ->once()
        ;
    }
}
