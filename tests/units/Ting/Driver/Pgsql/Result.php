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

namespace tests\units\CCMBenchmark\Ting\Driver\Pgsql;

use mageekguy\atoum;

class Result extends atoum
{
    public function testSetQueryShouldRaiseExceptionOnColumnAsterisk()
    {
        $this
            ->if($result = new \CCMBenchmark\Ting\Driver\Pgsql\Result('connectionName', 'database', 'result resource'))
            ->exception(function () use ($result) {
                $result->setQuery('select t.* from table as t');
            })
                ->hasMessage('Query invalid: usage of asterisk in column definition is forbidden');
    }

    public function testSetQueryShouldRaiseExceptionParseColumns()
    {
        $this
            ->if($result = new \CCMBenchmark\Ting\Driver\Pgsql\Result('connectionName', 'database', 'result resource'))
            ->exception(function () use ($result) {
                $result->setQuery('selectcolumn from table');
            })
                ->hasMessage('Query invalid: can\'t parse columns');
    }

    public function testIterator()
    {
        $this->function->pg_result_seek = true;
        $this->function->pg_fetch_array = [];

        $this
            ->if($result = new \mock\CCMBenchmark\Ting\Driver\Pgsql\Result(
                'connectionName',
                'database',
                'result resource'
            ))
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

    public function testIteratorValidShouldReturnFalse()
    {
        $this->function->pg_result_seek = true;
        $this->function->pg_fetch_array = false;

        $this
            ->if($result = new \CCMBenchmark\Ting\Driver\Pgsql\Result('connectionName', 'database', 'result resource'))
            ->then($result->rewind())
            ->then($result->next())
            ->boolean($result->valid())
                ->isFalse();
    }
}
