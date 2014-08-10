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
            'firstname' => array(
                'type'  => 'string',
                'value' => 'Sylvain'
            ),
            'id' => array(
                'type'  => 'int',
                'value' => 3
            ),
            'old' => array(
                'type'  => 'float',
                'value' => 32.1
            ),
            'description' => array(
                'type'  => 'blob',
                'value' => 'A very long description'
            )
        );
        $paramsOrder = array('firstname' => null, 'id' => null, 'description' => null, 'old' => null);

        $this->calling($driverStatement)->get_result = new \mock\Iterator();

        $this
            ->if($statement = new \fastorm\Driver\Mysqli\Statement())
            ->then($statement->execute($driverStatement, $params, $paramsOrder, $collection))
            ->mock($driverStatement)
                ->call('bind_param')
                    ->withIdenticalArguments('sibd', 'Sylvain', 3, 'A very long description', 32.1)->once();
    }

    public function testExecuteShouldCallDriverStatementExecute()
    {
        $driverStatement = new \mock\Fake\DriverStatement();
        $collection      = new \mock\fastorm\Entity\Collection();

        $this->calling($driverStatement)->get_result = new \mock\Iterator();

        $this
            ->if($statement = new \fastorm\Driver\Mysqli\Statement())
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
            ->then($statement->setCollectionWithResult($driverStatement, $collection))
            ->mock($collection)
                ->call('set')
                    ->once()
            ->object($outerResult)
                ->isCloneOf(new \fastorm\Driver\Mysqli\Result($result));
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
}
