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

use mageekguy\atoum;

class Statement extends atoum
{

    public function testExecuteShouldCallTheRightConnection()
    {
        $this->function->pg_execute = function ($connection, $statementName, $values) use (&$outerConnection) {
            $outerConnection = $connection;
            return [];
        };
        $this->function->pg_field_table = 'Bouh';
        $this->function->pg_result_seek = 0;
        $this->function->pg_fetch_array = false;

        $collection = new \mock\CCMBenchmark\Ting\Repository\Collection();

        $this
            ->if($statement = new \CCMBenchmark\Ting\Driver\Pgsql\Statement('MyStatementName', []))
            ->then($statement->setConnection('Awesome connection resource'))
            ->then($statement->setQuery('SELECT firstname FROM Bouh'))
            ->then($statement->execute([], $collection))
            ->string($outerConnection)
                ->isIdenticalTo('Awesome connection resource');
    }

    public function testExecuteShouldCallDriverExecuteWithParameters()
    {
        $this->function->pg_field_table = 'Bouh';
        $this->function->pg_execute     = function ($connection, $statementName, $values) use (&$outerValues) {
            $outerValues = $values;
            return [];
        };
        $this->function->pg_result_seek = 0;
        $this->function->pg_fetch_array = false;

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
            ->if($statement = new \CCMBenchmark\Ting\Driver\Pgsql\Statement('MyStatementName', $paramsOrder))
            ->then($statement->setQuery('SELECT firstname FROM Bouh'))
            ->then($statement->execute($params, $collection))
            ->array($outerValues)
                ->isIdenticalTo(array('Sylvain', 3, 'A very long description', 32.1, '2014-03-01 14:02:05'));
    }

    public function testSetCollectionWithResult()
    {
        $collection      = new \mock\CCMBenchmark\Ting\Repository\Collection();
        $result          = new \ArrayIterator(array(
            array(
                'prenom' => 'Sylvain',
                'nom'    => 'Robez-Masson'
            ),
            array(
                'prenom' => 'Xavier',
                'nom'    => 'Leune'
            )
        ));

        $this->calling($collection)->set = function ($result) use (&$outerResult) {
            $outerResult = $result;
        };

        $this->function->pg_field_table = 'Bouh';

        $resultOk = new \CCMBenchmark\Ting\Driver\Pgsql\Result($result);
        $resultOk->setQuery('SELECT prenom, nom FROM Bouh');

        $this
            ->if($statement = new \CCMBenchmark\Ting\Driver\Pgsql\Statement('MyStatementName', []))
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
        $this->function->pg_execute = false;
        $this->function->pg_result_error = 'unknown error';

        $this
            ->if($statement = new \CCMBenchmark\Ting\Driver\Pgsql\Statement('MyStatementName', []))
            ->exception(function () use ($statement, $collection) {
                $statement->execute([]);
            })
                ->isInstanceOf('\CCMBenchmark\Ting\Driver\QueryException');
    }

    public function testExecuteShouldReturnTrueIfNoError()
    {
        $this->function->pg_execute = true;

        $this
            ->if($statement = new \CCMBenchmark\Ting\Driver\Pgsql\Statement('MyStatementName', []))
            ->boolean($statement->execute([]))
                ->isTrue()
        ;
    }

    public function testCloseShouldExecuteDeallocateQuery()
    {
        $collection = new \mock\CCMBenchmark\Ting\Repository\Collection();

        $this->function->pg_execute = function ($connection, $statementName, $values) use (&$outerValues) {
            $outerValues = $values;
            return [];
        };
        $this->function->pg_result_seek = 0;
        $this->function->pg_fetch_array = false;

        $this->function->pg_query = function ($connection, $query) use (&$outerQuery) {
            $outerQuery = $query;
        };

        $this->function->pg_field_table = 'Bouh';

        $this
            ->if($statement = new \CCMBenchmark\Ting\Driver\Pgsql\Statement('statementNameTest', []))
            ->then($statement->setQuery('SELECT firstname FROM Bouh'))
            ->then($statement->execute([], $collection))
            ->then($statement->close())
            ->string($outerQuery)
                ->isIdenticalTo('DEALLOCATE "statementNameTest"');
    }

    public function testExecuteShouldLogQuery()
    {
        $this->function->pg_execute = [];
        $this->function->pg_result_seek = 0;
        $this->function->pg_fetch_array = false;

        $mockLogger = new \mock\tests\fixtures\FakeLogger\FakeDriverLogger();

        $this
            ->if($statement = new \CCMBenchmark\Ting\Driver\Pgsql\Statement('statementNameTest', []))
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
