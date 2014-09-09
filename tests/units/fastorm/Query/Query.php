<?php

namespace tests\units\fastorm\Query;

use \mageekguy\atoum;

class Query extends atoum
{

    public function testConstructorShouldRaiseException()
    {
        $this
            ->exception(function () {
                new \fastorm\Query\Query([]);
            })
                ->isInstanceOf('\fastorm\Query\QueryException');
    }

    public function testExecuteShouldRaiseException()
    {
        $this
            ->if($query = new \fastorm\Query\Query(['sql' => '']))
            ->exception(function () use ($query) {
                $query->execute();
            })
                ->isInstanceOf('\fastorm\Query\QueryException');
    }

    public function testExecuteShouldReturnCollection()
    {
        $mockDriver       = new \mock\Fake\Mysqli();
        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([['truc'], ['bouh']]);

        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = array();
            $stdClass = new \stdClass();
            $stdClass->name     = 'prenom';
            $stdClass->orgname  = 'firstname';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            return $fields;
        };

        $this->calling($mockDriver)->query = $mockMysqliResult;

        $this
            ->if($driver = new \mock\fastorm\Driver\Mysqli\Driver($mockDriver))
            ->and($collection = new \fastorm\Entity\Collection())
            ->and($query = new \fastorm\Query\Query(['sql' => 'SELECT * from Bouh']))
            ->and($query->setDriver($driver))
            ->and($query->execute($collection))
            ->and($collection->rewind())
            ->array($collection->current())
                ->isIdenticalTo([[
                    'name'     => 'prenom',
                    'orgName'  => 'firstname',
                    'table'    => 'bouh',
                    'orgTable' => 'T_BOUH_BOO',
                    'value'    => 'truc'
                ]])
            ->array($collection->next()->current())
                ->isIdenticalTo([[
                    'name'     => 'prenom',
                    'orgName'  => 'firstname',
                    'table'    => 'bouh',
                    'orgTable' => 'T_BOUH_BOO',
                    'value'    => 'bouh'
                ]]);
    }
}
