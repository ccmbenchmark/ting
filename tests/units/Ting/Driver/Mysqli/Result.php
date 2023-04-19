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

namespace tests\units\CCMBenchmark\Ting\Driver\Mysqli;

use atoum;
use Brick\Geo\Point;

class Result extends atoum
{

    public function testIterator()
    {
        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([['value'], ['value2']]);

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

        $this
            ->if($result = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Result())
            ->then($result->setConnectionName('connectionName'))
            ->then($result->setDatabase('database'))
            ->then($result->setResult($mockMysqliResult))
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

    public function testGetNumRows()
    {
        $mockMysqliResult = new \mock\CCMBenchmark\Ting\Driver\ResultInterface();
        $mockMysqliResult->num_rows = 10;

        $this
            ->if($result = new \CCMBenchmark\Ting\Driver\Mysqli\Result())
            ->then($result->setResult($mockMysqliResult))
            ->variable($result->getNumRows())
                ->isEqualTo(10);
    }

    public function testGeometry()
    {
        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([['geo' => "\x00\x00\x00\x00\x01\x01\x00\x00\x00\x00\x00\x00\x00\x00\x00\x24\x40\x00\x00\x00\x00\x00\x00\x34\x40"]]);

        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = [];
            $stdClass = new \stdClass();
            $stdClass->name     = 'geo';
            $stdClass->orgname  = 'geo';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_GEOMETRY;
            $fields[] = $stdClass;

            return $fields;
        };

        $this
            ->given($result = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Result())
            ->and($result->setResult($mockMysqliResult))
            ->and($result->next())
            ->and($row = $result->current())
            ->then
                ->array($row)
                ->hasKey(0)
            ->then
                ->array($row[0])
                ->hasKeys(['name', 'orgName', 'table', 'orgTable', 'value'])
            ->then
                ->object($row[0]['value'])
                ->isInstanceOf(Point::class)
                ->and
                    ->float($row[0]['value']->x())
                    ->isEqualTo(10)
                ->and
                    ->float($row[0]['value']->y())
                    ->isEqualTo(20)
        ;
    }
}
