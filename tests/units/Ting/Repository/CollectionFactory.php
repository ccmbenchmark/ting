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

use CCMBenchmark\Ting\Driver\Mysqli\Result;
use atoum;

class CollectionFactory extends atoum
{
    public function testGetShouldReturnInstanceOfCollection()
    {

        $services = new \CCMBenchmark\Ting\Services();

        $this
            ->if($collectionFactory = $services->get('CollectionFactory'))
            ->object($collection = $collectionFactory->get())
                ->isInstanceOf(\CCMBenchmark\Ting\Repository\Collection::class);
    }

    public function testGetShouldReturnInstanceOfCollectionWithNewHydrator()
    {

        $services = new \CCMBenchmark\Ting\Services();

        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([['a-Bouh']]);
        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = [];
            $stdClass = new \stdClass();
            $stdClass->name     = 'name';
            $stdClass->orgname  = 'boo_name';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;
            return $fields;
        };

        $result = new Result();
        $result->setResult($mockMysqliResult);
        $result->setConnectionName('main');
        $result->setDatabase('bouh_world');

        $mockMysqliResult2 = new \mock\tests\fixtures\FakeDriver\MysqliResult([['b-Bouh']]);
        $this->calling($mockMysqliResult2)->fetch_fields = function () {
            $fields = [];
            $stdClass = new \stdClass();
            $stdClass->name     = 'name';
            $stdClass->orgname  = 'boo_name';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;
            return $fields;
        };

        $result2 = new Result();
        $result2->setResult($mockMysqliResult2);
        $result2->setConnectionName('main');
        $result2->setDatabase('bouh_world');

        $this
            ->if($collectionFactory = $services->get('CollectionFactory'))
            ->then($collection = $collectionFactory->get())
            ->then($collection->set($result))
            ->then($collection2 = $collectionFactory->get())
            ->then($collection2->set($result2))
            ->then($stdClass = $collection2->getIterator()->current()[0])
            ->string($stdClass->name)
                ->isIdenticalTo('b-Bouh')
            ->then($stdClass = $collection->getIterator()->current()[0])
            ->string($stdClass->name)
                ->isIdenticalTo('a-Bouh');
    }
}
