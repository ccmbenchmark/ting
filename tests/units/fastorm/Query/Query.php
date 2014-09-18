<?php

namespace tests\units\CCMBenchmark\Ting\Query;

use \mageekguy\atoum;

class Query extends atoum
{

    public function testConstructorShouldRaiseException()
    {
        $this
            ->exception(function () {
                new \CCMBenchmark\Ting\Query\Query([]);
            })
                ->isInstanceOf('\CCMBenchmark\Ting\Query\QueryException');
    }

    public function testExecuteShouldRaiseException()
    {
        $this
            ->if($query = new \CCMBenchmark\Ting\Query\Query(['sql' => '']))
            ->exception(function () use ($query) {
                $query->execute();
            })
                ->isInstanceOf('\CCMBenchmark\Ting\Query\QueryException');
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
            ->if($driver = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver($mockDriver))
            ->and($collection = new \CCMBenchmark\Ting\Entity\Collection())
            ->and($query = new \CCMBenchmark\Ting\Query\Query(['sql' => 'SELECT * from Bouh']))
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
