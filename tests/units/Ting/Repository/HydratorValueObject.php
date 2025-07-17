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
        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([['Sylvain', 'Robez-Masson']]);
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

        $result = new Result();
        $result->setResult($mockMysqliResult);
        $result->setConnectionName('connectionName');
        $result->setDatabase('database');

        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\HydratorValueObject(Bouh::class))
            ->then($iterator = $hydrator->setResult($result)->getIterator())
            ->then($bouh = $iterator->current())
            ->object($bouh)
                ->isInstanceOf('tests\fixtures\ValueObject\Bouh')
            ->string($bouh->getName())
                ->isIdenticalTo('Robez-Masson')
            ->string($bouh->getFirstname())
                ->isIdenticalTo('Sylvain');
    }

    public function testHydrateShouldSupportNativeTypes()
    {
        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([[1, 4.2, 'Robez-Masson', '2025-01-01 00:00:00'/*, '2025-02-28 13:37:42'*/, 0]]);
        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = [];
            $stdClass = new \stdClass();
            $stdClass->name     = 'nb';
            $stdClass->orgname  = 'boo_nb';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_INT24;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'avg';
            $stdClass->orgname  = 'boo_avg';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_DECIMAL;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'my_name';
            $stdClass->orgname  = 'boo_my_name';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

//            $stdClass = new \stdClass();
//            $stdClass->name     = 'date';
//            $stdClass->orgname  = 'boo_date';
//            $stdClass->table    = 'bouh';
//            $stdClass->orgtable = 'T_BOUH_BOO';
//            $stdClass->type     = MYSQLI_TYPE_DATETIME;
//            $fields[] = $stdClass;
//
//            $stdClass = new \stdClass();
//            $stdClass->name     = 'dateimmutable';
//            $stdClass->orgname  = 'boo_dateimmutable';
//            $stdClass->table    = 'bouh';
//            $stdClass->orgtable = 'T_BOUH_BOO';
//            $stdClass->type     = MYSQLI_TYPE_DATETIME;
//            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'bool';
            $stdClass->orgname  = 'boo_bool';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_INT24;
            $fields[] = $stdClass;

            return $fields;
        };

        $result = new Result();
        $result->setResult($mockMysqliResult);
        $result->setConnectionName('connectionName');
        $result->setDatabase('database');

        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\HydratorValueObject(BouhWithNativeType::class))
            ->then($iterator = $hydrator->setResult($result)->getIterator())
            ->then($bouh = $iterator->current())
            ->object($bouh)
            ->isInstanceOf('tests\fixtures\ValueObject\BouhWithNativeType')
        ;
    }

    public function testHydrateShouldHydrateObjectViaConsructor()
    {
        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([['Sylvain', 'Robez-Masson']]);
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

        $result = new Result();
        $result->setResult($mockMysqliResult);
        $result->setConnectionName('connectionName');
        $result->setDatabase('database');

        $this
            ->given($this->function->method_exists = true)
            ->then($hydrator = new \CCMBenchmark\Ting\Repository\HydratorValueObject(BouhWithConstruct::class))
            ->then($iterator = $hydrator->setResult($result)->getIterator())
            ->when($bouh = $iterator->current())
            ->then($this->function('method_exists')->wasCalled()->once())
            ->object($bouh)
            ->isInstanceOf('tests\fixtures\ValueObject\BouhWithConstruct')
            ->string($bouh->getName())
            ->isIdenticalTo('Robez-Masson')
            ->string($bouh->getFirstname())
            ->isIdenticalTo('Sylvain');
    }

    public function testHydrateShouldThrowAnExceptionWhenResultAndObjectDoNotMatch()
    {
        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([['Sylvain', 'Robez-Masson']]);
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

        $result = new Result();
        $result->setResult($mockMysqliResult);
        $result->setConnectionName('connectionName');
        $result->setDatabase('database');

        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\HydratorValueObject(WrongObject::class))
            ->and($iterator = $hydrator->setResult($result)->getIterator())
            ->exception(function () use ($iterator) {
                $var =  $iterator->current();
            })->isInstanceOf(Exception::class)->hasMessage('There is no setter for column "firstname"')
        ;
    }


    public function testCountShouldReturn2()
    {
        $result = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Result();

        $this->calling($result)->getNumRows = 2;

        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\HydratorSingleObject())
            ->then($hydrator->setResult($result))
            ->integer($hydrator->count())
            ->isIdenticalTo(2);
    }
}
