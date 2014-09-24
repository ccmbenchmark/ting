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

namespace tests\units\CCMBenchmark\Ting\Repository;

use mageekguy\atoum;

class Collection extends atoum
{

    public function testHydrateNullShouldReturnNull()
    {
        $this
            ->if($collection = new \CCMBenchmark\Ting\Repository\Collection())
            ->variable($collection->hydrate(null))
                ->isNull();
    }

    public function testHydrateShouldDoNothingWithoutHydrator()
    {
        $data = array('Bouh' => array());

        $this
            ->if($collection = new \CCMBenchmark\Ting\Repository\Collection())
            ->array($collection->hydrate($data))
                ->isIdenticalTo($data);
    }

    public function testHydrateWithHydratorShouldCallHydratorHydrate()
    {
        $services     = new \CCMBenchmark\Ting\Services();
        $mockHydrator = new \mock\CCMBenchmark\Ting\Repository\Hydrator(
            $services->get('MetadataRepository'),
            $services->get('UnitOfWork')
        );

        $data = array(
            array(
                'name'     => 'name',
                'orgName'  => 'BOO_NAME',
                'table'    => 'bouh',
                'orgTable' => 'T_BOUH_BOO',
                'value'    => 'Sylvain'
            )
        );

        $this
            ->if($collection = new \CCMBenchmark\Ting\Repository\Collection())
            ->then($collection->hydrator($mockHydrator))
            ->then($collection->hydrate($data))
            ->mock($mockHydrator)
                ->call('hydrate')
                    ->withIdenticalArguments($data)->once();
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

        $result = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Result($mockMysqliResult);

        $this
            ->if($collection = new \CCMBenchmark\Ting\Repository\Collection())
            ->then($collection->set($result))
            ->then($collection->rewind())
            ->mock($result)
                ->call('rewind')->once()
                ->call('next')->once()
            ->then($collection->key())
            ->mock($result)
                ->call('key')->once()
            ->then($collection->next())
            ->mock($result)
                ->call('next')->twice()
            ->then($collection->valid())
            ->mock($result)
                ->call('valid')->once()
            ->then($collection->current())
            ->mock($result)
                ->call('current')->once();
    }
}
