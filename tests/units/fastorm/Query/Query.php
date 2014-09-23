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

namespace tests\units\CCMBenchmark\Ting\Query;

use mageekguy\atoum;

class Query extends atoum
{

    public function testExecuteShouldRaiseException()
    {
        $this
            ->if($query = new \CCMBenchmark\Ting\Query\Query(''))
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
            ->and($collection = new \CCMBenchmark\Ting\Repository\Collection())
            ->and($query = new \CCMBenchmark\Ting\Query\Query('SELECT * from Bouh'))
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
