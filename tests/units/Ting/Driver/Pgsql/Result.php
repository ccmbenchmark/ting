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
        $this->function->pg_num_fields = 1;
        $this->function->pg_field_table = function ($result, $index) {
            if ($index === 1) {
                return 'table';
            }
            return false;
        };

        $this->function->pg_field_name = function ($result, $index) {
            switch ($index) {
                case 0:
                    return 't.*';
                default:
                    return false;
            }
        };

        $this
            ->if($result = new \CCMBenchmark\Ting\Driver\Pgsql\Result('result resource'))
            ->exception(function () use ($result) {
                $result->setQuery('select t.* from table as t');
            })
                ->hasMessage('Query invalid: usage of asterisk in column definition is forbidden');
    }

    public function testSetQueryShouldRaiseExceptionParseColumns()
    {
        $this->function->pg_num_fields = 0;

        $this
            ->if($result = new \CCMBenchmark\Ting\Driver\Pgsql\Result('result resource'))
            ->exception(function () use ($result) {
                $result->setQuery('selectcolumn from table');
            })
                ->hasMessage('Query invalid: can\'t parse columns');
    }

    public function testDataSeekShouldCallPgResultSeek()
    {
        $this->function->pg_result_seek = function ($result, $index) use (&$outerIndex) {
            $outerIndex = $index;
            return true;
        };

        $this
            ->if($result = new \CCMBenchmark\Ting\Driver\Pgsql\Result('result resource'))
            ->then($result->dataSeek(789))
            ->integer($outerIndex)
                ->isIdenticalTo(789);
    }

    public function testFormat()
    {

        $this->function->pg_num_fields = 2;
        $this->function->pg_field_table = function ($result, $index) {
            if ($index < 2) {
                return 'T_BOUH_BOO';
            }
            return false;
        };

        $this->function->pg_field_name = function ($result, $index) {
            switch ($index) {
                case 0:
                    return 'firstname';
                case 1:
                    return 'name';
                default:
                    return false;
            }
        };

        $this
            ->if($result = new \CCMBenchmark\Ting\Driver\Pgsql\Result('result resource'))
            ->then($result->setQuery('SELECT "firstname", b.name as nom FROM T_BOUH_BOO as b'))
            ->then($row = $result->format(array('firstname' => 'Sylvain', 'name' => 'Robez-Masson')))
            ->string($row[0]['name'])
                ->isIdenticalTo('firstname')
            ->string($row[0]['orgName'])
                ->isIdenticalTo('firstname')
            ->string($row[0]['table'])
                ->isIdenticalTo('b')
            ->string($row[0]['orgTable'])
                ->isIdenticalTo('t_bouh_boo')
            ->string($row[0]['value'])
                ->isIdenticalTo('Sylvain')
            ->string($row[1]['name'])
                ->isIdenticalTo('nom')
            ->string($row[1]['orgName'])
                ->isIdenticalTo('name')
            ->string($row[1]['table'])
                ->isIdenticalTo('b')
            ->string($row[1]['orgTable'])
                ->isIdenticalTo('t_bouh_boo')
            ->string($row[1]['value'])
                ->isIdenticalTo('Robez-Masson');
    }

    public function testFormatWithoutAlias()
    {

        $this->function->pg_num_fields = 2;
        $this->function->pg_field_table = function ($result, $index) {
            if ($index < 2) {
                return 't_bouh_boo';
            }
            return false;
        };

        $this->function->pg_field_name = function ($result, $index) {
            switch ($index) {
                case 0:
                    return 'firstname';
                case 1:
                    return 'name';
                default:
                    return false;
            }
        };

        $this
            ->if($result = new \CCMBenchmark\Ting\Driver\Pgsql\Result('result resource'))
            ->then($result->setQuery('SELECT firstname, T_BOUH_BOO.name FROM T_BOUH_BOO'))
            ->then($row = $result->format(array('firstname' => 'Sylvain', 'name' => 'Robez-Masson')))
            ->string($row[0]['name'])
                ->isIdenticalTo('firstname')
            ->string($row[0]['orgName'])
                ->isIdenticalTo('firstname')
            ->string($row[0]['table'])
                ->isIdenticalTo('t_bouh_boo')
            ->string($row[0]['orgTable'])
                ->isIdenticalTo('t_bouh_boo')
            ->string($row[0]['value'])
                ->isIdenticalTo('Sylvain')
            ->string($row[1]['name'])
                ->isIdenticalTo('name')
            ->string($row[1]['orgName'])
                ->isIdenticalTo('name')
            ->string($row[1]['table'])
                ->isIdenticalTo('t_bouh_boo')
            ->string($row[1]['orgTable'])
                ->isIdenticalTo('t_bouh_boo')
            ->string($row[1]['value'])
                ->isIdenticalTo('Robez-Masson');
    }

    public function testFormatWithSubQuery()
    {

        $this->function->pg_num_fields = 3;
        $this->function->pg_field_table = function ($result, $index) {
            switch ($index) {
                case 0:
                case 2:
                    return 't_bouh_boo';
                case 1:
                    return '';
                default:
                    return false;
            }
        };

        $this->function->pg_field_name = function ($result, $index) {
            switch ($index) {
                case 0:
                    return 'firstname';
                case 1:
                    return '';
                case 2:
                    return 'name';
                default:
                    return false;
            }
        };

        $this
            ->if($result = new \CCMBenchmark\Ting\Driver\Pgsql\Result('result resource'))
            ->then($result->setQuery('
              SELECT firstname, (select old+3 from t_bouh_boo) as old, T_BOUH_BOO.name
              FROM T_BOUH_BOO
              WHERE firstname in (select firstname from t_bouh_boo)')
            )
            ->then($row = $result->format(array('firstname' => 'Sylvain', 'old' => 33, 'name' => 'Robez-Masson')))
            ->string($row[0]['name'])
                ->isIdenticalTo('firstname')
            ->string($row[0]['orgName'])
                ->isIdenticalTo('firstname')
            ->string($row[0]['table'])
                ->isIdenticalTo('t_bouh_boo')
            ->string($row[0]['orgTable'])
                ->isIdenticalTo('t_bouh_boo')
            ->string($row[0]['value'])
                ->isIdenticalTo('Sylvain')
            ->string($row[1]['name'])
                ->isIdenticalTo('old')
            ->string($row[1]['orgName'])
                ->isIdenticalTo('(select old+3 from t_bouh_boo)')
            ->string($row[1]['table'])
                ->isIdenticalTo('')
            ->string($row[1]['orgTable'])
                ->isIdenticalTo('')
            ->integer($row[1]['value'])
                ->isIdenticalTo(33)
            ->string($row[2]['name'])
                ->isIdenticalTo('name')
            ->string($row[2]['orgName'])
                ->isIdenticalTo('name')
            ->string($row[2]['table'])
                ->isIdenticalTo('t_bouh_boo')
            ->string($row[2]['orgTable'])
                ->isIdenticalTo('t_bouh_boo')
            ->string($row[2]['value'])
                ->isIdenticalTo('Robez-Masson');
    }

    public function testFormatShouldReturnNull()
    {
        $this
            ->if($result = new \CCMBenchmark\Ting\Driver\Pgsql\Result('result resource'))
            ->variable($result->format(false))
                ->isNull();
    }

    public function testIterator()
    {
        $this->function->pg_result_seek = true;
        $this->function->pg_fetch_array = array();

        $this
            ->if($result = new \mock\CCMBenchmark\Ting\Driver\Pgsql\Result('result resource'))
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
            ->if($result = new \CCMBenchmark\Ting\Driver\Pgsql\Result('result resource'))
            ->then($result->rewind())
            ->then($result->next())
            ->boolean($result->valid())
                ->isFalse();
    }
}
