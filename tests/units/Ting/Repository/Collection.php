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

    public function testCollectionShouldDoNothingWithoutHydrator()
    {
        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([['Sylvain', 'Robez-Masson']]);
        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = [];
            $stdClass = new \stdClass();
            $stdClass->name     = 'prenom';
            $stdClass->orgname  = 'firstname';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'nom';
            $stdClass->orgname  = 'lastname';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;
            return $fields;
        };

        $this
            ->if($collection = new \CCMBenchmark\Ting\Repository\Collection())
            ->then($collection->set(new \CCMBenchmark\Ting\Driver\Mysqli\Result($mockMysqliResult)))
            ->array($collection->current())
                ->isIdenticalTo(['prenom' => 'Sylvain', 'nom' => 'Robez-Masson']);
    }

    public function testHydrateWithHydratorShouldNotCallHydratorHydrate()
    {
        $services     = new \CCMBenchmark\Ting\Services();
        $mockHydrator = new \mock\CCMBenchmark\Ting\Repository\Hydrator();
        $mockHydrator->setMetadataRepository($services->get('MetadataRepository'));
        $mockHydrator->setUnitOfWork($services->get('UnitOfWork'));

        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([['Sylvain']]);
        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = [];
            $stdClass = new \stdClass();
            $stdClass->name     = 'prenom';
            $stdClass->orgname  = 'firstname';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            return $fields;
        };

        $this->calling($mockHydrator)->isAggregator = false;

        $this
            ->if($collection = new \CCMBenchmark\Ting\Repository\Collection($mockHydrator))
            ->then($collection->set(new \CCMBenchmark\Ting\Driver\Mysqli\Result($mockMysqliResult)))
            ->mock($mockHydrator)
                ->call('hydrate')
                    ->never();
    }

    public function testHydrateWithHydratorAggregatorShouldCallHydratorHydrate()
    {
        $services     = new \CCMBenchmark\Ting\Services();
        $mockHydrator = new \mock\CCMBenchmark\Ting\Repository\Hydrator();
        $mockHydrator->setMetadataRepository($services->get('MetadataRepository'));
        $mockHydrator->setUnitOfWork($services->get('UnitOfWork'));

        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([['Sylvain']]);
        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = [];
            $stdClass = new \stdClass();
            $stdClass->name     = 'prenom';
            $stdClass->orgname  = 'firstname';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            return $fields;
        };

        $this->calling($mockHydrator)->isAggregator = true;

        $data = [
            [
                'name'     => 'prenom',
                'orgName'  => 'firstname',
                'table'    => 'bouh',
                'orgTable' => 'T_BOUH_BOO',
                'value'    => 'Sylvain'
            ]
        ];

        $this
            ->if($collection = new \CCMBenchmark\Ting\Repository\Collection($mockHydrator))
            ->then($collection->set(new \CCMBenchmark\Ting\Driver\Mysqli\Result($mockMysqliResult)))
            ->mock($mockHydrator)
            ->call('hydrate')
                ->withIdenticalArguments($data, $collection)
                ->once();
    }

    public function testFirstShouldReturnNull()
    {
        $this
            ->if($collection = new \CCMBenchmark\Ting\Repository\Collection())
            ->variable($collection->first())
                ->isNull();
    }

    public function testFirstShouldReturnFirstItemOfCollection()
    {
        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([['Sylvain']]);
        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = [];
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
            ->array($collection->first())
                ->isEqualTo(['prenom' => 'Sylvain']);
    }

    public function testIterator()
    {
        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([['Sylvain']]);
        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = [];
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
            ->and($collection->set($result))
            ->and($collection->rewind())
            ->boolean($collection->valid())
                ->isTrue()
            ->array($collection->current())
                ->isEqualTo(['prenom' => 'Sylvain'])
            ->integer($collection->key())
                ->isEqualTo(0)
            ->and($collection->next())
            ->boolean($collection->valid())
                ->isFalse();
    }

    public function testAddWithKey()
    {
        $this
            ->if($collection = new \CCMBenchmark\Ting\Repository\Collection())
            ->and($collection->add(['Bouh'], 'MyKey'))
            ->string($collection->key())
                ->isIdenticalTo('MyKey');
    }

    public function testIsFromCache()
    {
        $this
            ->if($collection = new \CCMBenchmark\Ting\Repository\Collection())
            ->and($collection->setFromCache(false))
                ->boolean($collection->isFromCache())
                    ->isFalse()
            ->and($collection->setFromCache(true))
                ->boolean($collection->isFromCache())
                    ->isTrue()
        ;
    }

    public function testToArrayReturnArray()
    {
        $this
            ->if($collection = new \CCMBenchmark\Ting\Repository\Collection())
            ->array($collection->toArray())
                ->isIdenticalTo([])
        ;
    }

    public function testCount()
    {
        $this
            ->if($collection = new \CCMBenchmark\Ting\Repository\Collection())
            ->then($collection->add(['data' => 'field']))
            ->integer($collection->count())
                ->isIdenticalTo(1)
            ->then($collection->add(['2nddata' => 'field']))
            ->integer($collection->count())
                ->isIdenticalTo(2)
        ;
    }

    public function testSetFromCache()
    {
        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([['Sylvain']]);
        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = [];
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
            ->and($collection->setFromCache(true))
            ->and($collection->set($result))
            ->boolean($collection->isFromCache())
                ->isTrue()
        ;
    }
}
