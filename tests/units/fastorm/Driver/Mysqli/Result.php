<?php

namespace tests\units\fastorm\Driver\Mysqli;

use \mageekguy\atoum;

class Result extends atoum
{

    public function testDataSeekShouldCallMysqliResultDataSeek()
    {

        $mockMysqliResult = new \mock\fastorm\Driver\ResultInterface();
        $this->calling($mockMysqliResult)->data_seek = function ($index) {
            return true;
        };

        $this
            ->if($result = new \fastorm\Driver\Mysqli\Result($mockMysqliResult))
            ->then($result->dataSeek(789))
            ->mock($mockMysqliResult)
                ->call('data_seek')
                    ->withIdenticalArguments(789)->once();
    }

    public function testFormat()
    {
        $mockMysqliResult = new \mock\fastorm\Driver\ResultInterface();
        $this->calling($mockMysqliResult)->fetch_fields = function () {
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

            $stdClass = new \stdClass();
            $stdClass->name     = 'age';
            $stdClass->orgname  = 'age';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_TINY;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'date_of_death';
            $stdClass->orgname  = 'date_of_death';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_NULL;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'poids';
            $stdClass->orgname  = 'weight';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_FLOAT;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'argent';
            $stdClass->orgname  = 'cash';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_LONGLONG;
            $fields[] = $stdClass;

            return $fields;
        };

        $this
            ->if($result = new \fastorm\Driver\Mysqli\Result($mockMysqliResult))
            ->array(
                $row = $result->format(
                    array(
                        'firstname'     => 'Sylvain',
                        'name'          => 'Robez-Masson',
                        'age'           => '12',
                        'date_of_death' => 'null',
                        'poids'         => '61.34',
                        'argent'        => '2247483647'
                    )
                )
            )
                ->isIdenticalTo(array(
                    array(
                        'name'     => 'prenom',
                        'orgName'  => 'firstname',
                        'table'    => 'bouh',
                        'orgTable' => 'T_BOUH_BOO',
                        'value'    => 'Sylvain'
                    ),
                    array(
                        'name'     => 'nom',
                        'orgName'  => 'name',
                        'table'    => 'bouh',
                        'orgTable' => 'T_BOUH_BOO',
                        'value'    => 'Robez-Masson'
                    ),
                    array(
                        'name'     => 'age',
                        'orgName'  => 'age',
                        'table'    => 'bouh',
                        'orgTable' => 'T_BOUH_BOO',
                        'value'    => 12
                    ),
                    array(
                        'name'     => 'date_of_death',
                        'orgName'  => 'date_of_death',
                        'table'    => 'bouh',
                        'orgTable' => 'T_BOUH_BOO',
                        'value'    => null
                    ),
                    array(
                        'name'     => 'poids',
                        'orgName'  => 'weight',
                        'table'    => 'bouh',
                        'orgTable' => 'T_BOUH_BOO',
                        'value'    => 61.34
                    ),
                    array(
                        'name'     => 'argent',
                        'orgName'  => 'cash',
                        'table'    => 'bouh',
                        'orgTable' => 'T_BOUH_BOO',
                        'value'    => '2247483647'
                    )
                ));
    }

    public function testFormatShouldReturnNull()
    {
        $mockMysqliResult = new \mock\fastorm\Driver\ResultInterface();

        $this
            ->if($result = new \fastorm\Driver\Mysqli\Result($mockMysqliResult))
            ->variable($result->format(null))
                ->isNull();
    }

    public function testIterator()
    {
        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([['value'], ['value2']]);
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

        $this
            ->if($result = new \mock\fastorm\Driver\Mysqli\Result($mockMysqliResult))
            ->then($result->rewind())
            ->mock($result)
                ->call('next')->once()
            ->then($result->key())
            ->mock($result)
                ->call('key')->once()
            ->then($result->next())
            ->mock($result)
                ->call('next')->twice()
            ->then($result->valid())
            ->mock($result)
                ->call('valid')->once()
            ->then($result->current())
            ->mock($result)
                ->call('current')->once();
    }
}