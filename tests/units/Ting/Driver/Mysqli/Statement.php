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

use mageekguy\atoum;

class Statement extends atoum
{
    public function testExecuteShouldCallDriverStatementBindParams()
    {
        $driverStatement = new \mock\Fake\DriverStatement();
        $this->calling($driverStatement)->close = true;
        $collection      = new \mock\CCMBenchmark\Ting\Repository\Collection();
        $params          = array(
            'firstname'   => 'Sylvain',
            'id'          => 3,
            'old'         => 32.1,
            'description' => 'A very long description',
            'date' => '2014-03-01 14:02:05'
        );
        $paramsOrder = array('firstname' => null, 'id' => null, 'description' => null, 'old' => null, 'date' => null);

        $this->calling($driverStatement)->get_result = new \mock\Iterator();
        $driverStatement->errno = 0;

        $this
            ->if($statement = new \CCMBenchmark\Ting\Driver\Mysqli\Statement(
                $driverStatement,
                $paramsOrder,
                'connectionName',
                'database'
            ))
            ->then($statement->execute($params, $collection))
            ->mock($driverStatement)
                ->call('bind_param')
                    ->withIdenticalArguments(
                        'sisds',
                        'Sylvain',
                        3,
                        'A very long description',
                        32.1,
                        '2014-03-01 14:02:05'
                    )->once();
    }

    public function testExecuteShouldCallDriverStatementExecute()
    {
        $driverStatement = new \mock\Fake\DriverStatement();
        $this->calling($driverStatement)->close = true;
        $collection      = new \mock\CCMBenchmark\Ting\Repository\Collection();

        $this->calling($driverStatement)->get_result = new \mock\Iterator();
        $driverStatement->errno = 0;

        $this
            ->if($statement = new \CCMBenchmark\Ting\Driver\Mysqli\Statement(
                $driverStatement,
                array(),
                'connectionName',
                'database'
            ))
            ->then($statement->execute(array(), $collection))
            ->mock($driverStatement)
                ->call('execute')
                    ->once();
    }

    public function testSetCollectionWithResult()
    {
        $driverStatement = new \mock\Fake\DriverStatement();
        $this->calling($driverStatement)->close = true;
        $collection      = new \mock\CCMBenchmark\Ting\Repository\Collection();
        $result          = new \mock\tests\fixtures\FakeDriver\MysqliResult(
            [
                [
                    'prenom' => 'Sylvain',
                    'nom'    => 'Robez-Masson'
                ],
                [
                    'prenom' => 'Xavier',
                    'nom'    => 'Leune'
                ]
            ]
        );

        $this->calling($result)->fetch_fields = function () {
            $fields = array();
            $stdClass = new \stdClass();
            $stdClass->name     = 'prenom';
            $stdClass->orgname  = 'firstname';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'nom';
            $stdClass->orgname  = 'name';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            return $fields;
        };

        $this->calling($driverStatement)->get_result = $result;
        $this->calling($collection)->set = function ($result) use (&$outerResult) {
            $outerResult = $result;
        };

        $resultReference = new \CCMBenchmark\Ting\Driver\Mysqli\Result();
        $resultReference->setConnectionName('connectionName');
        $resultReference->setDatabase('database');
        $resultReference->setResult($result);

        $this
            ->if($statement = new \CCMBenchmark\Ting\Driver\Mysqli\Statement(
                $driverStatement,
                [],
                'connectionName',
                'database'
            ))
            ->then($statement->setCollectionWithResult($result, $collection))
            ->mock($collection)
                ->call('set')
                    ->once()
            ->object($outerResult)
                ->isCloneOf($resultReference);
    }

    public function testExecuteShouldRaiseQueryExceptionOnError()
    {
        $driverStatement = new \mock\Fake\DriverStatement();
        $this->calling($driverStatement)->close = true;
        $collection      = new \mock\CCMBenchmark\Ting\Repository\Collection();

        $driverStatement->errno = 123;
        $driverStatement->error = 'unknown error';
        $this->calling($driverStatement)->get_result = false;

        $this
            ->if($statement = new \CCMBenchmark\Ting\Driver\Mysqli\Statement(
                $driverStatement,
                [],
                'connectionName',
                'database'
            ))
            ->exception(function () use ($statement, $driverStatement, $collection) {
                $statement->execute([], $collection);
            })
                ->isInstanceOf('\CCMBenchmark\Ting\Driver\QueryException');
    }

    public function testExecuteShouldReturnTrueIfNoError()
    {
        $driverStatement = new \mock\Fake\DriverStatement();
        $this->calling($driverStatement)->close = true;

        $this->calling($driverStatement)->get_result = true;
        $driverStatement->errno = 0;

        $this
            ->if($statement = new \CCMBenchmark\Ting\Driver\Mysqli\Statement(
                $driverStatement,
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
        $driverStatement = new \mock\Fake\DriverStatement();
        $this->calling($driverStatement)->close = true;
        $collection      = new \mock\CCMBenchmark\Ting\Repository\Collection();
        $mockLogger      = new \mock\tests\fixtures\FakeLogger\FakeDriverLogger();

        $this->calling($driverStatement)->get_result = new \mock\Iterator();
        $driverStatement->errno = 0;

        $this
            ->if($statement = new \CCMBenchmark\Ting\Driver\Mysqli\Statement(
                $driverStatement,
                [],
                'connectionName',
                'database'
            ))
            ->and($statement->setLogger($mockLogger))
            ->then($statement->execute(array(), $collection))
                ->mock($mockLogger)
                    ->call('startStatementExecute')
                        ->once()
                    ->call('stopStatementExecute')
                        ->once()
        ;
    }
}
