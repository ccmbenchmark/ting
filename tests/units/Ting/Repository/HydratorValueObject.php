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
use CCMBenchmark\Ting\Exception;
use ReflectionException;
use tests\fixtures\ValueObject\Bouh;
use tests\fixtures\ValueObject\BouhWithConstruct;
use tests\fixtures\ValueObject\BouhWithNativeType;
use tests\fixtures\ValueObject\WrongObject;

class HydratorValueObject extends atoum
{
    public function testHydrateShouldReturnBouhObject()
    {
        $data = ['Sylvain', 'Robez-Masson'];
        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([$data]);
        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = [];
            $stdClass = new \stdClass();
            $stdClass->name     = 'firstname';
            $stdClass->orgname  = 'boo_firstname';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'name';
            $stdClass->orgname  = 'boo_name';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;
            return $fields;
        };

        $this->calling($mockMysqliResult)->fetch_object = function () use ($data) {
            return new Bouh(...$data);
        };

        $result = new Result();
        $result->setResult($mockMysqliResult);
        $result->setConnectionName('connectionName');
        $result->setDatabase('database');

        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\HydratorValueObject(Bouh::class))
            ->then($iterator = $hydrator->setResult($result)->getIterator())
            ->then($bouh = $iterator->current())
            ->mock($mockMysqliResult)
                ->call('fetch_object')
                ->once()
            ->object($bouh)
                ->isInstanceOf('tests\fixtures\ValueObject\Bouh')
            ->string($bouh->getName())
                ->isIdenticalTo('Robez-Masson')
            ->string($bouh->getFirstname())
                ->isIdenticalTo('Sylvain');
    }

    public function testCountShouldReturn2()
    {
        $result = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Result();

        $this->calling($result)->getNumRows = 2;

        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\HydratorValueObject(Bouh::class))
            ->then($hydrator->setResult($result))
            ->integer($hydrator->count())
            ->isIdenticalTo(2);
    }
}
