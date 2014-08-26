<?php

namespace tests\units\fastorm\Driver\Pgsql;

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

        $collection = new \mock\fastorm\Entity\Collection();

        $this
            ->if($statement = new \fastorm\Driver\Pgsql\Statement())
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

        $collection      = new \mock\fastorm\Entity\Collection();
        $params          = array(
            'firstname'   => 'Sylvain',
            'id'          => 3,
            'old'         => 32.1,
            'description' => 'A very long description'
        );
        $paramsOrder = array('firstname' => null, 'id' => null, 'description' => null, 'old' => null);


        $this
            ->if($statement = new \fastorm\Driver\Pgsql\Statement())
            ->then($statement->setQuery('SELECT firstname FROM Bouh'))
            ->then($statement->execute('MyStatementName', $params, $paramsOrder, $collection))
            ->array($outerValues)
                ->isIdenticalTo(array('Sylvain', 3, 'A very long description', 32.1));
    }

    public function testSetCollectionWithResult()
    {
        $collection      = new \mock\fastorm\Entity\Collection();
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

        $resultOk = new \fastorm\Driver\Pgsql\Result($result);
        $resultOk->setQuery('SELECT prenom, nom FROM Bouh');

        $this
            ->if($statement = new \fastorm\Driver\Pgsql\Statement())
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
            ->if($statement = new \fastorm\Driver\Pgsql\Statement())
            ->then($statement->setQueryType(\fastorm\Query\Query::TYPE_INSERT))
            ->integer($statement->setCollectionWithResult(new \ArrayIterator()))
                ->isIdenticalTo(123);
    }

    public function testSetCollectionWithResultWithoutCollectionShouldReturnAffectedRows()
    {
        $this->function->pg_affected_rows = 321;

        $this
            ->if($statement = new \fastorm\Driver\Pgsql\Statement())
            ->integer($statement->setCollectionWithResult(new \ArrayIterator()))
                ->isIdenticalTo(321);
    }

    public function testSetCollectionShouldRaiseQueryException()
    {
        $collection = new \mock\fastorm\Entity\Collection();
        $this->function->pg_result_error = 'unknown error';

        $this
            ->if($statement = new \fastorm\Driver\Pgsql\Statement())
            ->exception(function () use ($statement, $collection) {
                $statement->setCollectionWithResult(false, $collection);
            })
                ->isInstanceOf('\fastorm\Driver\QueryException');
    }

    public function testCloseShouldExecuteDeallocateQuery()
    {
        $collection = new \mock\fastorm\Entity\Collection();

        $this->function->pg_execute = function ($connection, $statementName, $values) use (&$outerValues) {
            $outerValues = $values;
            return new \ArrayIterator();
        };

        $this->function->pg_query = function ($connection, $query) use (&$outerQuery) {
            $outerQuery = $query;
        };

        $this->function->pg_field_table = 'Bouh';

        $this
            ->if($statement = new \fastorm\Driver\Pgsql\Statement())
            ->then($statement->setQuery('SELECT firstname FROM Bouh'))
            ->then($statement->execute('statementNameTest', array(), array(), $collection))
            ->then($statement->close())
            ->string($outerQuery)
                ->isIdenticalTo('DEALLOCATE "statementNameTest"');
    }

    public function testCloseBeforeExecuteShouldRaiseException()
    {
        $driverStatement = new \mock\Fake\DriverStatement();
        $collection      = new \mock\fastorm\Entity\Collection();

        $this->calling($driverStatement)->get_result = new \mock\Iterator();

        $this
            ->if($statement = new \fastorm\Driver\Pgsql\Statement())
            ->exception(function () use ($statement) {
                $statement->close();
            })
                ->hasMessage('statement->close can\'t be called before statement->execute');
    }

    public function testSetQueryTypeWithInvalidTypeShouldRaisException()
    {
        $this
            ->if($statement = new \fastorm\Driver\Pgsql\Statement())
            ->exception(function () use ($statement) {
                $statement->setQueryType(PHP_INT_MAX);
            })
                ->hasMessage('setQueryType should use one of constant Statement::TYPE_*');
    }
}
