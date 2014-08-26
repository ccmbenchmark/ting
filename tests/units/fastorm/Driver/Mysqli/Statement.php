<?php

namespace tests\units\fastorm\Driver\Mysqli;

use \mageekguy\atoum;

class Statement extends atoum
{
    public function testExecuteShouldCallDriverStatementBindParams()
    {
        $driverStatement = new \mock\Fake\DriverStatement();
        $collection      = new \mock\fastorm\Entity\Collection();
        $params          = array(
            'firstname'   => 'Sylvain',
            'id'          => 3,
            'old'         => 32.1,
            'description' => 'A very long description'
        );
        $paramsOrder = array('firstname' => null, 'id' => null, 'description' => null, 'old' => null);

        $this->calling($driverStatement)->get_result = new \mock\Iterator();

        $this
            ->if($statement = new \fastorm\Driver\Mysqli\Statement())
            ->then($statement->setQueryType(\fastorm\Query\Query::TYPE_RESULT))
            ->then($statement->execute($driverStatement, $params, $paramsOrder, $collection))
            ->mock($driverStatement)
                ->call('bind_param')
                    ->withIdenticalArguments('sisd', 'Sylvain', 3, 'A very long description', 32.1)->once();
    }

    public function testExecuteShouldCallDriverStatementExecute()
    {
        $driverStatement = new \mock\Fake\DriverStatement();
        $collection      = new \mock\fastorm\Entity\Collection();

        $this->calling($driverStatement)->get_result = new \mock\Iterator();

        $this
            ->if($statement = new \fastorm\Driver\Mysqli\Statement())
            ->then($statement->setQueryType(\fastorm\Query\Query::TYPE_RESULT))
            ->then($statement->execute($driverStatement, array(), array(), $collection))
            ->mock($driverStatement)
                ->call('execute')
                    ->once();
    }

    public function testSetCollectionWithResult()
    {
        $driverStatement = new \mock\Fake\DriverStatement();
        $collection      = new \mock\fastorm\Entity\Collection();
        $result          = new \mock\tests\fixtures\FakeDriver\MysqliResult(array(
            array(
                'prenom' => 'Sylvain',
                'nom'     => 'Robez-Masson'
            ),
            array(
                'prenom' => 'Xavier',
                'nom' => 'Leune'
            )
        ));

        $this->calling($result)->fetch_fields = function () {
            $fields = array();
            $stdClass = new \stdClass();
            $stdClass->name     = 'prenom';
            $stdClass->orgname  = 'firstname';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'nom';
            $stdClass->orgname  = 'name';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $fields[] = $stdClass;

            return $fields;
        };

        $this->calling($driverStatement)->get_result = $result;
        $this->calling($collection)->set = function ($result) use (&$outerResult) {
            $outerResult = $result;
        };

        $this
            ->if($statement = new \fastorm\Driver\Mysqli\Statement())
            ->then($statement->setQueryType(\fastorm\Query\Query::TYPE_RESULT))
            ->then($statement->setCollectionWithResult($driverStatement, $collection))
            ->mock($collection)
                ->call('set')
                    ->once()
            ->object($outerResult)
                ->isCloneOf(new \fastorm\Driver\Mysqli\Result($result));
    }

    public function testSetCollectionWithResultWithQueryTypeInsertShouldReturnId()
    {
        $driverStatement = new \mock\Fake\DriverStatement();
        $driverStatement->insert_id = 123;

        $this
            ->if($statement = new \fastorm\Driver\Mysqli\Statement())
            ->then($statement->setQueryType(\fastorm\Query\Query::TYPE_RESULT))
            ->then($statement->setQueryType(\fastorm\Query\Query::TYPE_INSERT))
            ->integer($statement->setCollectionWithResult($driverStatement))
                ->isIdenticalTo(123);
    }

    public function testSetCollectionWithResultWithoutCollectionShouldReturnAffectedRows()
    {
        $driverStatement = new \mock\Fake\DriverStatement();
        $driverStatement->affected_rows = 321;

        $this
            ->if($statement = new \fastorm\Driver\Mysqli\Statement())
            ->integer($statement->setCollectionWithResult($driverStatement))
                ->isIdenticalTo(321);
    }

    public function testSetCollectionWithResultWithoutCollectionShouldReturnFalse()
    {
        $driverStatement = new \mock\Fake\DriverStatement();
        $driverStatement->affected_rows = null;

        $this
            ->if($statement = new \fastorm\Driver\Mysqli\Statement())
            ->boolean($statement->setCollectionWithResult($driverStatement))
                ->isFalse();

        $driverStatement->affected_rows = -1;

        $this
            ->if($statement = new \fastorm\Driver\Mysqli\Statement())
            ->boolean($statement->setCollectionWithResult($driverStatement))
                ->isFalse();
    }

    public function testSetCollectionShouldRaiseQueryException()
    {
        $driverStatement = new \mock\Fake\DriverStatement();
        $collection      = new \mock\fastorm\Entity\Collection();

        $driverStatement->errno = 123;
        $driverStatement->error = 'unknown error';
        $this->calling($driverStatement)->get_result = false;

        $this
            ->if($statement = new \fastorm\Driver\Mysqli\Statement())
            ->then($statement->setQueryType(\fastorm\Query\Query::TYPE_RESULT))
            ->exception(function () use ($statement, $driverStatement, $collection) {
                $statement->setCollectionWithResult($driverStatement, $collection);
            })
                ->isInstanceOf('\fastorm\Driver\QueryException');
    }

    public function testCloseShouldCallDriverStatementClose()
    {
        $driverStatement = new \mock\Fake\DriverStatement();
        $collection      = new \mock\fastorm\Entity\Collection();

        $this->calling($driverStatement)->get_result = new \mock\Iterator();

        $this
            ->if($statement = new \fastorm\Driver\Mysqli\Statement())
            ->then($statement->setQueryType(\fastorm\Query\Query::TYPE_RESULT))
            ->then($statement->execute($driverStatement, array(), array(), $collection))
            ->then($statement->close())
            ->mock($driverStatement)
                ->call('close')
                    ->once();
    }

    public function testCloseBeforeExecuteShouldRaiseException()
    {
        $driverStatement = new \mock\Fake\DriverStatement();
        $collection      = new \mock\fastorm\Entity\Collection();

        $this->calling($driverStatement)->get_result = new \mock\Iterator();

        $this
            ->if($statement = new \fastorm\Driver\Mysqli\Statement())
            ->exception(function () use ($statement) {
                $statement->close();
            })
                ->hasMessage('statement->close can\'t be called before statement->execute');
    }

    public function testSetQueryTypeWithInvalidTypeShouldRaisException()
    {
        $this
            ->if($statement = new \fastorm\Driver\Mysqli\Statement())
            ->exception(function () use ($statement) {
                $statement->setQueryType(PHP_INT_MAX);
            })
                ->hasMessage('setQueryType should use one of constant Query::TYPE_*');
    }
}
