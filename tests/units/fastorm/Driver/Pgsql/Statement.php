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

use \mageekguy\atoum;

class Statement extends atoum
{
    public function testExecuteShouldCallTheRightConnection()
    {
        $this->function->pg_execute = function ($connection, $statementName, $values) use (&$outerConnection) {
            $outerConnection = $connection;
            return new \ArrayIterator();
        };
        $this->function->pg_field_table = 'Bouh';

        $collection = new \mock\CCMBenchmark\Ting\Entity\Collection();

        $this
            ->if($statement = new \CCMBenchmark\Ting\Driver\Pgsql\Statement())
            ->then($statement->setConnection('Awesome connection resource'))
            ->then($statement->setQuery('SELECT firstname FROM Bouh'))
            ->then($statement->execute('MyStatementName', array(), array(), $collection))
            ->string($outerConnection)
                ->isIdenticalTo('Awesome connection resource');
    }

    public function testExecuteShouldCallDriverExecuteWithParameters()
    {
        $this->function->pg_field_table = 'Bouh';
        $this->function->pg_execute     = function ($connection, $statementName, $values) use (&$outerValues) {
            $outerValues = $values;
            return new \ArrayIterator();
        };

        $collection      = new \mock\CCMBenchmark\Ting\Entity\Collection();
        $params          = array(
            'firstname'   => 'Sylvain',
            'id'          => 3,
            'old'         => 32.1,
            'description' => 'A very long description'
        );
        $paramsOrder = array('firstname' => null, 'id' => null, 'description' => null, 'old' => null);


        $this
            ->if($statement = new \CCMBenchmark\Ting\Driver\Pgsql\Statement())
            ->then($statement->setQuery('SELECT firstname FROM Bouh'))
            ->then($statement->execute('MyStatementName', $params, $paramsOrder, $collection))
            ->array($outerValues)
                ->isIdenticalTo(array('Sylvain', 3, 'A very long description', 32.1));
    }

    public function testSetCollectionWithResult()
    {
        $collection      = new \mock\CCMBenchmark\Ting\Entity\Collection();
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
            ->if($statement = new \CCMBenchmark\Ting\Driver\Pgsql\Statement())
            ->then($statement->setQuery('SELECT prenom, nom FROM Bouh'))
            ->then($statement->setCollectionWithResult($result, $collection))
            ->mock($collection)
                ->call('set')
                    ->once()
            ->object($outerResult)
                ->isCloneOf($resultOk);
    }

    public function testSetCollectionWithResultWithQueryTypeInsertShouldReturnId()
    {
        $this->function->pg_query = function ($connection, $sql) {
            if ($sql !== 'SELECT lastval()') {
                return false;
            }

            return array(123);
        };

        $this->function->pg_fetch_row = function ($result) {
            return $result;
        };

        $this
            ->if($statement = new \CCMBenchmark\Ting\Driver\Pgsql\Statement())
            ->then($statement->setQueryType(\CCMBenchmark\Ting\Query\QueryAbstract::TYPE_INSERT))
            ->integer($statement->setCollectionWithResult(new \ArrayIterator()))
                ->isIdenticalTo(123);
    }

    public function testSetCollectionWithResultWithoutCollectionShouldReturnAffectedRows()
    {
        $this->function->pg_affected_rows = 321;

        $this
            ->if($statement = new \CCMBenchmark\Ting\Driver\Pgsql\Statement())
            ->integer($statement->setCollectionWithResult(new \ArrayIterator()))
                ->isIdenticalTo(321);
    }

    public function testSetCollectionShouldRaiseQueryException()
    {
        $collection = new \mock\CCMBenchmark\Ting\Entity\Collection();
        $this->function->pg_result_error = 'unknown error';

        $this
            ->if($statement = new \CCMBenchmark\Ting\Driver\Pgsql\Statement())
            ->exception(function () use ($statement, $collection) {
                $statement->setCollectionWithResult(false, $collection);
            })
                ->isInstanceOf('\CCMBenchmark\Ting\Driver\QueryException');
    }

    public function testCloseShouldExecuteDeallocateQuery()
    {
        $collection = new \mock\CCMBenchmark\Ting\Entity\Collection();

        $this->function->pg_execute = function ($connection, $statementName, $values) use (&$outerValues) {
            $outerValues = $values;
            return new \ArrayIterator();
        };

        $this->function->pg_query = function ($connection, $query) use (&$outerQuery) {
            $outerQuery = $query;
        };

        $this->function->pg_field_table = 'Bouh';

        $this
            ->if($statement = new \CCMBenchmark\Ting\Driver\Pgsql\Statement())
            ->then($statement->setQuery('SELECT firstname FROM Bouh'))
            ->then($statement->execute('statementNameTest', array(), array(), $collection))
            ->then($statement->close())
            ->string($outerQuery)
                ->isIdenticalTo('DEALLOCATE "statementNameTest"');
    }

    public function testCloseBeforeExecuteShouldRaiseException()
    {
        $driverStatement = new \mock\Fake\DriverStatement();
        $collection      = new \mock\CCMBenchmark\Ting\Entity\Collection();

        $this->calling($driverStatement)->get_result = new \mock\Iterator();

        $this
            ->if($statement = new \CCMBenchmark\Ting\Driver\Pgsql\Statement())
            ->exception(function () use ($statement) {
                $statement->close();
            })
                ->hasMessage('statement->close can\'t be called before statement->execute');
    }

    public function testSetQueryTypeWithInvalidTypeShouldRaisException()
    {
        $this
            ->if($statement = new \CCMBenchmark\Ting\Driver\Pgsql\Statement())
            ->exception(function () use ($statement) {
                $statement->setQueryType(PHP_INT_MAX);
            })
                ->hasMessage('setQueryType should use one of constant Statement::TYPE_*');
    }
}
